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
    protected $orderHelper;

    public function __construct(
        \Webbhuset\SveaWebpay\Helper\Order $orderHelper
    ) {
        $this->orderHelper = $orderHelper;
    }

    public function build($order)
    {
        $translatedName = $this->orderHelper
            ->getTranslatedRowName(\Webbhuset\SveaWebpay\Helper\Order::TRANSLATE_PAYMENT_HANDLING_FEE);

        $invoiceFee = WebPayItem::invoiceFee()
            ->setAmountExVat($order->getHandlingFeeAmount())
            ->setAmountIncVat($order->getHandlingFeeInclTax())
            ->setName($translatedName);

        return $invoiceFee;
    }
}
