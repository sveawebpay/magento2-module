<?php
/**
 * Hosted deliver request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Hosted;

use Webbhuset\SveaWebpay\Gateway\Request\OrderActionBuilderInterface;
use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPay;

class DeliverOrderBuilder implements OrderActionBuilderInterface
{
    protected $apiConfig;
    protected $helper;

    /**
     * DeliverOrderBuilder constructor.
     * @param Configuration $apiConfig
     * @param OrderHelper $helper
     */
    public function __construct(
        Configuration $apiConfig,
        OrderHelper $helper
    ) {
        $this->apiConfig = $apiConfig;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \Svea\WebPay\HostedService\HostedAdminRequest\ConfirmTransaction
     */
    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order = $payment->getOrder();
        $countryCode = $this->helper->getCountryCode($order);

        $request = WebPay::deliverOrder($this->apiConfig)
            ->setTransactionId($order->getExtOrderId())
            ->setCountryCode($countryCode)
            ->setCaptureDate(date('c'));

        return $request->deliverCardOrder();
    }
}
