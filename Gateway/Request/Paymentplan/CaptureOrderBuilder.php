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
    protected $messageManager;

    public function __construct(
        OrderHelper $helper,
        Configuration $apiConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->apiConfig = $apiConfig;
        $this->helper = $helper;
        $this->messageManager = $messageManager;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order = $payment->getOrder();
        $sveaOrderId = $order->getExtOrderId();
        $countryCode = $this->helper->getCountryCode($order);
        $sveaOrder   = $this->helper->fetchSveaOrder($order);

        if ($sveaOrder->orderDeliveryStatus == 'Delivered') {
            $this->messageManager->addSuccessMessage(__('Order is already delivered in Svea. Syncing status.'));

            return false;
        }

        $request = WebPay::deliverOrder($this->apiConfig)
            ->setOrderId($sveaOrderId)
            ->setCountryCode($countryCode);

        return $request->deliverPaymentPlanOrder();
    }
}
