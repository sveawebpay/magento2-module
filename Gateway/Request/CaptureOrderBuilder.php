<?php
/**
 * Capture order builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Helper\RequestBuilder as RequestBuilderHelper;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\BuildOrder\RowBuilders\NumberedOrderRow;
use Svea\WebPay\WebPayAdmin;

class CaptureOrderBuilder implements \Webbhuset\SveaWebpay\Gateway\Request\OrderActionBuilderInterface
{
    /**
     * @var Order
     */
    protected $helper;
    /**
     * @var Configuration
     */
    protected $apiConfig;
    /**
     * @var RequestBuilder
     */
    protected $requestBuilderHelper;
    protected $messageManager;

    public function __construct(
        OrderHelper $helper,
        RequestBuilderHelper $requestBuilderHelper,
        Configuration $apiConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->helper = $helper;
        $this->apiConfig = $apiConfig;
        $this->requestBuilderHelper = $requestBuilderHelper;
        $this->messageManager = $messageManager;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order = $payment->getOrder();

        return $this->getDeliverOrderRequest($order);
    }

    public function getDeliverOrderRequest(\Magento\Sales\Model\Order $order)
    {
        $billingAddress = $order->getBillingAddress();
        $countryCode    = $billingAddress->getCountryId();
        $invoice        = $order->getInvoiceCollection()->getLastItem();
        $sveaOrderId    = $order->getExtOrderId();
        $sveaOrder      = $this->helper->fetchSveaOrder($order);
        $payment        = $order->getPayment();

        if ($sveaOrder->orderDeliveryStatus == 'Delivered') {
            $this->messageManager->addSuccessMessage(__('Order is already delivered in Svea. Syncing status.'));

            return false;
        }
        $sortedOrderRows    = $this->requestBuilderHelper->sortOrderRows($sveaOrder->numberedOrderRows, $payment);

        $distributionType = $this->requestBuilderHelper->getDistributionType($order);

        $request = WebPayAdmin::deliverOrderRows($this->apiConfig)
            ->setOrderId($order->getExtOrderId())
            ->setCountryCode($countryCode)
            ->setInvoiceDistributionType($distributionType);  // Distribution type is only used for Invoice

        $itemCollection = $invoice->getItemsCollection();
        $items          = $this->requestBuilderHelper->getItemData($itemCollection);
        $articleRows    = $this->requestBuilderHelper->getRowsWithType('articles',$sortedOrderRows);
        $discountRows   = $this->requestBuilderHelper->getRowsWithType('discounts', $sortedOrderRows);

        $rowsToDeliver = [];
        // Add articles
        foreach ($articleRows as $row) {
            if ($row->status == NumberedOrderRow::ORDERROWSTATUS_DELIVERED) {
                continue;
            }

            $item = $this->requestBuilderHelper->findItemBySku($items, $row->articleNumber);
            if (!$item) {
                continue;
            }

            $invoiceQty = $item['qty'];
            if (!(float)$invoiceQty) {
                // do not capture items with qty 0
                continue;
            }
            $needsUpdate = $row->quantity != $invoiceQty;

            $discountRow = $this->requestBuilderHelper->findMatchingDiscountRow($discountRows, $item);

            if ($needsUpdate) {
                // Here we have to send requests to split rows if necessary
                $updateRequest = WebPayAdmin::updateOrderRows($this->apiConfig)
                    ->setOrderId($sveaOrderId)
                    ->setCountryCode($countryCode);

                $addRequest = WebPayAdmin::addOrderRows($this->apiConfig)
                    ->setOrderId($sveaOrderId)
                    ->setCountryCode($countryCode);

                $this->requestBuilderHelper->splitRow($addRequest, $updateRequest, $item, $row, $discountRow);

                // Make the split row requests
                $updateRequest->updateInvoiceOrderRows()->doRequest();
                $addRequest->addInvoiceOrderRows()->doRequest();
            }

            $rowsToDeliver[] = $row->rowNumber;
            if ($discountRow) {
                $rowsToDeliver[] = $discountRow->rowNumber;
            }
        }
        if (!$rowsToDeliver) {
            throw new \Magento\Framework\Exception\LocalizedException(__('No order rows to send. Order is out of sync with svea.'));
        }


        // Add nondelivered shipping rows
        foreach ($this->requestBuilderHelper->getRowsWithType('shipping', $sortedOrderRows) as $row) {
            if ($row->status == NumberedOrderRow::ORDERROWSTATUS_DELIVERED) {
                continue;
            }

            $rowsToDeliver[] = $row->rowNumber;
        }

        foreach ($this->requestBuilderHelper->getRowsWithType('invoice_fee', $sortedOrderRows) as $row) {
            if ($row->status == NumberedOrderRow::ORDERROWSTATUS_DELIVERED) {
                continue;
            }

            $rowsToDeliver[] = $row->rowNumber;
        }

        foreach ($this->requestBuilderHelper->getRowsWithType('adjustment', $sortedOrderRows) as $row) {
            if ($row->status == NumberedOrderRow::ORDERROWSTATUS_DELIVERED) {
                continue;
            }

            $rowsToDeliver[] = $row->rowNumber;
        }

        return $request->setRowsToDeliver($rowsToDeliver);
    }
}
