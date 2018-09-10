<?php
/**
 * New order request validator
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Validator\Request;

class NewOrderRequestValidator extends AbstractValidator
{
    public function validate($request, \Magento\Sales\Model\Order\Payment $payment)
    {
        $requestTotals = $request->getRequestTotals();
        $requestGrandTotal = $requestTotals['total_incvat'];
        $grandTotal = $payment->getOrder()->getGrandTotal();

        $gt =  (int) round($requestGrandTotal * 100);
        $rgt = (int) round($grandTotal * 100);

        if ($gt != $rgt) {
            return $this->createResult(
                false,
                [__('Svea error 1100 : Totals do not match')]
            );
        }

        return $this->createResult(true, []);
    }
}
