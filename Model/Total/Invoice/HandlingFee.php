<?php
/**
 * Invoice handling fee totals
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Total\Invoice;

class HandlingFee extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    public function collect(
        \Magento\Sales\Model\Order\Invoice $invoice
    ) {
        $invoice->setHandlingFeeAmount(0);
        $invoice->setBaseHandlingFeeAmount(0);
        $handlingFeeAmount = $invoice->getOrder()->getHandlingFeeAmount();
        $baseHandlingFeeAmount = $invoice->getOrder()->getBaseHandlingFeeAmount();
        if ($handlingFeeAmount) {
            /**
             * Check shipping amount in previous invoices
             */
            foreach ($invoice->getOrder()->getInvoiceCollection() as $previousInvoice) {
                if ((double)$previousInvoice->getHandlingFeeAmount() && !$previousInvoice->isCanceled()) {
                    return $this;
                }
            }
            $invoice->setHandlingFeeAmount($handlingFeeAmount);
            $invoice->setBaseHandlingFeeAmount($baseHandlingFeeAmount);

            $invoice->setGrandTotal($invoice->getGrandTotal() + $handlingFeeAmount);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseHandlingFeeAmount);
        }

        return $this;
    }
}
