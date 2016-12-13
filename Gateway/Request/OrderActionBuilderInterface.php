<?php
/**
 * Builder interface
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

interface OrderActionBuilderInterface
{
    public function build(\Magento\Sales\Model\Order\Payment $payment);
}
