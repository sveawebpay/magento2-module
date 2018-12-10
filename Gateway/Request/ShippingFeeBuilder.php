<?php
/**
 * Shipping fee builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Svea\WebPay\WebPayItem;

class ShippingFeeBuilder
{
    protected $orderHelper;

    public function __construct(
        \Webbhuset\SveaWebpay\Helper\Order $orderHelper
    ) {
        $this->orderHelper = $orderHelper;
    }

    public function build($data)
    {
        $feeIncVat = $data['fee_inc_vat'];
        $feeExVat = $data['fee_ex_vat'];

        $translatedName = $this->orderHelper
            ->getTranslatedRowName(\Webbhuset\SveaWebpay\Helper\Order::TRANSLATE_SHIPPING_FEE);

        $feeItem = WebPayItem::shippingFee()
            ->setAmountIncVat($feeIncVat)
            ->setAmountExVat($feeExVat)
            ->setName($translatedName);

        return $feeItem;
    }
}
