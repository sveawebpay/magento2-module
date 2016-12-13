<?php
/**
 * Paymentplan capture order request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Paymentplan;

use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPay;

class CaptureOrderBuilder implements \Webbhuset\SveaWebpay\Gateway\Request\OrderActionBuilderInterface
{
    /**
     * @var Configuration
     */
    protected $apiConfig;
    protected $helper;

    public function __construct(
        OrderHelper $helper,
        Configuration $apiConfig
    ) {

        $this->apiConfig = $apiConfig;
        $this->helper = $helper;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {

        $order = $payment->getOrder();
        $sveaOrderId = $order->getExtOrderId();
        $countryCode = $this->helper->getCountryCode($order);

        $request = WebPay::deliverOrder($this->apiConfig)
            ->setOrderId($sveaOrderId)
            ->setCountryCode($countryCode);

        return $request->deliverPaymentPlanOrder();
    }
}
