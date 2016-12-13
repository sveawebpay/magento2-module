<?php
/**
 * Hosted refund request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Hosted;


use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Helper\RequestBuilder;
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
     * @var RequestBuilder
     */
    protected $requestBuilderHelper;

    /**
     * RefundOrderBuilder constructor.
     * @param OrderHelper $helper
     * @param RequestBuilder $requestBuilderHelper
     * @param Configuration $apiConfig
     */
    public function __construct(
        OrderHelper $helper,
        RequestBuilder $requestBuilderHelper,
        Configuration $apiConfig
    ) {
        $this->helper = $helper;
        $this->requestBuilderHelper = $requestBuilderHelper;
        $this->apiConfig = $apiConfig;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \Svea\WebPay\BuildOrder\CreateOrderRowsBuilder
     */
    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $creditMemo = $payment->getCreditmemo();
        $order              = $creditMemo->getOrder();

        $countryCode        = $this->helper->getCountryCode($order);

        $request = WebPayAdmin::creditOrderRows($this->apiConfig)
            ->setOrderId($order->getExtOrderId())
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
