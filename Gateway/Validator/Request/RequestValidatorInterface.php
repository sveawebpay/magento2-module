<?php
/**
 * Request validator interface
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Validator\Request;


interface RequestValidatorInterface
{
    public function validate($request, \Magento\Sales\Model\Order\Payment $payment);
}
