<?php
/**
 * Credit memo handling fee total
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Total\Creditmemo;


class HandlingFee extends \Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal
{
    public function collect(
        \Magento\Sales\Model\Order\Creditmemo $creditmemo
    ) {
        $creditmemo->setHandlingFeeAmount(0);
        $creditmemo->setBaseHandlingFeeAmount(0);
        $handlingFeeAmount = $creditmemo->getOrder()->getHandlingFeeAmount();
        $baseHandlingFeeAmount = $creditmemo->getOrder()->getBaseHandlingFeeAmount();
        if ($handlingFeeAmount) {
            /**
             * Check shipping amount in previous invoices
             */
            foreach ($creditmemo->getOrder()->getInvoiceCollection() as $previousInvoice) {
                if ((double)$previousInvoice->getHandlingFeeAmount() && !$previousInvoice->isCanceled()) {
                    return $this;
                }
            }
            $creditmemo->setHandlingFeeAmount($handlingFeeAmount);
            $creditmemo->setBaseHandlingFeeAmount($baseHandlingFeeAmount);

            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $handlingFeeAmount);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseHandlingFeeAmount);
        }

        return $this;
    }
}
