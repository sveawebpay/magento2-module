<?php
/**
 * Invoice refund request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Invoice;

use Magento\Framework\Exception\LocalizedException;
use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Helper\RequestBuilder as RequestBuilderHelper;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPayAdmin;
use Svea\WebPay\WebPayItem;

class RefundOrderBuilder implements \Webbhuset\SveaWebpay\Gateway\Request\OrderActionBuilderInterface
{
    protected $apiConfig;
    /**
     * @var OrderHelper
     */
    protected $helper;
    /**
     * @var RequestBuilderHelper
     */
    protected $requestBuilderHelper;

    public function __construct(
        OrderHelper $helper,
        RequestBuilderHelper $requestBuilderHelper,
        Configuration $apiConfig
    ) {
        $this->helper = $helper;
        $this->requestBuilderHelper = $requestBuilderHelper;
        $this->apiConfig = $apiConfig;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $creditMemo = $payment->getCreditmemo();

        return $this->getCreditMemoRequest($creditMemo);
    }


    public function getCreditMemoRequest($creditMemo)
    {
        $order              = $creditMemo->getOrder();
        $itemCollection     = $creditMemo->getItems();
        $creditTotal        = $creditMemo->getGrandTotal();
        $taxAmount          = $creditMemo->getTaxAmount();
        $creditTotalExVat   = $creditTotal - $taxAmount;
        $items              = $this->requestBuilderHelper->getItemData($itemCollection);
        $sveaOrder          = $this->helper->fetchSveaOrder($order);
        $sortedOrderRows    = $this->requestBuilderHelper->sortOrderRows($sveaOrder->numberedOrderRows);
        $articleRows        = $this->requestBuilderHelper->getRowsWithType('articles', $sortedOrderRows);
        $discountRows       = $this->requestBuilderHelper->getRowsWithType('discounts', $sortedOrderRows);
        $shippingRows       = $this->requestBuilderHelper->getRowsWithType('shipping', $sortedOrderRows);
        $adjustmentRows     = $this->requestBuilderHelper->getRowsWithType('adjustment', $sortedOrderRows);
        $invoice            = $creditMemo->getInvoice();
        $invoiceId          = $invoice->getSveaInvoiceId();
        $countryCode        = $this->helper->getCountryCode($order);
        $distributionType   = $this->requestBuilderHelper->getDistributionType($order);

        $request = WebPayAdmin::creditOrderRows($this->apiConfig)
            ->setInvoiceId($invoiceId)
            ->setInvoiceDistributionType($distributionType)
            ->setCountryCode($countryCode);

        // Keep track of sum, so we can validate it
        $sveaTotal = 0;
        $rowsToCredit = [];
        foreach ($articleRows as $row) {
            if (!isset($row->invoiceId) || ($row->invoiceId != $invoiceId)) {
                continue;
            }

            $item = $this->requestBuilderHelper->findItemBySku($items, $row->articleNumber);

            // If item found, row should be credited
            if (!$item) {
                continue;
            }

            $sveaTotal += ($row->amountExVat * $row->quantity);

            $rowsToCredit[] = $row->rowNumber;
            if ($item['discount']) {
                $discountRow = $this->requestBuilderHelper->findMatchingDiscountRow($discountRows, $item, false, $invoiceId);
                $rowsToCredit[] = $discountRow->rowNumber;
                $sveaTotal += $discountRow->amountExVat;
            }
        }

        // Credit whole shipping row and adjust amount if it differs
        $shippingAmount = $creditMemo->getShippingAmount();
        $shippingAdjustment = $shippingAmount;
        if ($shippingAmount) {
            foreach ($shippingRows as $row) {
                $shippingAdjustment -= $row->amountExVat;
                $sveaTotal += $row->amountExVat;
                $rowsToCredit[] = $row->rowNumber;
            }
        }

        foreach ($adjustmentRows as $row) {
            $rowsToCredit[] = $row->rowNumber;
        }

        $handlingFee = $creditMemo->getHandlingFeeAmount();
        if ($handlingFee) {
            $handlingFeeRows = $this->requestBuilderHelper
                ->getRowsWithType(RequestBuilderHelper::ROW_TYPE_INVOICE_FEE, $sortedOrderRows);

            foreach ($handlingFeeRows as $row) {
                $sveaTotal += $row->amountExVat;
                $rowsToCredit[] = $row->rowNumber;
            }
        }

        $request->setRowsToCredit($rowsToCredit);
        // Credit memo adjustment fees, not to be mixed with svea adjustment
        $adjustment = $creditMemo->getAdjustment() + $shippingAdjustment;

        if ($adjustment) {
            $adjustmentRow = WebPayItem::orderRow()
                ->setAmountIncVat($adjustment)
                ->setAmountExVat($creditTotalExVat)
                ->setQuantity(1)
                ->setDescription("Adjustment fee");

            $request->addCreditOrderRow($adjustmentRow);
        }

        $sveaTotal = $sveaTotal + $adjustment;

        $grandTotalExTax = $creditMemo->getGrandTotal() - $creditMemo->getTaxAmount();
        if ($sveaTotal != $grandTotalExTax) {
            throw new LocalizedException(__('Svea sum and magento sum do not match'));
        }

        return $request;
    }

}