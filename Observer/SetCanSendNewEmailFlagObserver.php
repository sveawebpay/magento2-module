<?php
/**
 * Set can send email observer
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;

class SetCanSendNewEmailFlagObserver implements ObserverInterface
{
    protected $orderHelper;

    /**
     * SetCanSendNewEmailFlagObserver constructor.
     *
     * @param \Webbhuset\SveaWebpay\Helper\Order $orderHelper
     */
    public function __construct(\Webbhuset\SveaWebpay\Helper\Order $orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order      = $observer->getOrder();
        $payment    = $order->getPayment();
        $method     = $payment->getMethod();

        $addressMethods = [
            'svea_direct_bank',
            'svea_card',
        ];

        if (!in_array($method, $addressMethods)) {
            return;
        }

        $order->setCanSendNewEmailFlag(false);
    }
}
