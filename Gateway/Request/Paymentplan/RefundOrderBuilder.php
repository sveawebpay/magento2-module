<?php
/**
 * Paymentplan refund request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Paymentplan;

use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Helper\RequestBuilder as RequestBuilderHelper;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPayAdmin;
use Svea\WebPay\WebPayItem;

class RefundOrderBuilder implements \Webbhuset\SveaWebpay\Gateway\Request\OrderActionBuilderInterface
{
    protected $apiConfig;
    /**
     * @var Order
     */
    protected $helper;
    /**
     * @var RequestBuilder
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
        $order      = $creditMemo->getOrder();
        $sveaOrder  = $this->helper->fetchSveaOrder($order);

        $countryCode = $this->helper->getCountryCode($order);

        $request = WebPayAdmin::creditOrderRows($this->apiConfig)
            ->setContractNumber($sveaOrder->paymentPlanDetailsContractNumber)
            ->setCountryCode($countryCode);

        $creditTotal = $creditMemo->getGrandTotal();
        $tax = $creditMemo->getTaxAmount();

        $creditTotalExVat = $creditTotal - $tax;

        $creditRow = WebPayItem::orderRow()
            ->setAmountIncVat($creditTotal)
            ->setAmountExVat($creditTotalExVat)
            ->setQuantity(1)
            ->setDescription("Credit row");

        $request->addCreditOrderRow($creditRow);

        return $request;
    }
}
