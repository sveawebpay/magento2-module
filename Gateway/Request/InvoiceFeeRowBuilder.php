<?php
/**
 * Invoice fee request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Svea\WebPay\WebPayItem;

class InvoiceFeeRowBuilder
{
    public function build($order)
    {
        $invoiceFee = WebPayItem::invoiceFee()
            ->setAmountExVat($order->getHandlingFeeAmount())
            ->setAmountIncVat($order->getHandlingFeeInclTax())
            ->setName('Payment Handling Fee');

        return $invoiceFee;
    }
}
