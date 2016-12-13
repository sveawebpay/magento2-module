<?php
/**
 *  Order plugin
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Plugin;


/**
 * Class OrderPlugin
 * @package Webbhuset\SveaWebpay\Plugin
 */
class OrderPlugin
{
    protected $sveaOrder;

    /**
     * OrderPlugin constructor.
     * @param \Webbhuset\SveaWebpay\Helper\Order $helper
     */
    public function __construct(\Webbhuset\SveaWebpay\Helper\Order $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Check if svea can cancel order
     *
     * @param \Magento\Sales\Model\Order $subject
     * @param callable $proceed
     * @return bool
     */
    public function aroundCanCancel(\Magento\Sales\Model\Order $subject, callable $proceed)
    {
        $payment = $subject->getPayment();
        $method = $payment->getMethod();

        $isSveaOrder = strpos($method, 'svea_') !== false;
        if (!$isSveaOrder) {
            return $proceed();
        }

        $this->sveaOrder = $this->helper->fetchSveaOrder($subject);

        if (isset($this->sveaOrder->isPossibleToCancel) && !$this->sveaOrder->isPossibleToCancel) {
            return false;
        }

        return $proceed();
    }
}
