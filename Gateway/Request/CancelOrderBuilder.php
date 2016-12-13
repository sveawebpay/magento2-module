<?php
/**
 * Cancel order builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPayAdmin;

class CancelOrderBuilder implements OrderActionBuilderInterface
{
    /**
     * @var Order
     */
    protected $helper;
    /**
     * @var Configuration
     */
    protected $apiConfig;

    public function __construct(
        OrderHelper $helper,
        Configuration $apiConfig
    ) {
        $this->helper = $helper;
        $this->apiConfig = $apiConfig;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order = $payment->getOrder();
        $sveaOrderId = $order->getExtOrderId();

        $countryCode = $this->helper->getCountryCode($order);

        $request = WebPayAdmin::cancelOrder($this->apiConfig)
            ->setOrderId($sveaOrderId) // Uses order id for invoice, paymentplan
            ->setTransactionId($sveaOrderId) // Uses Transaction id for card orders
            ->setCountryCode($countryCode);

        return $request;
    }
}
