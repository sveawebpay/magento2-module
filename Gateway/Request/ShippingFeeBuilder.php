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
    public function build($data)
    {
        $feeIncVat = $data['fee_inc_vat'];
        $feeExVat = $data['fee_ex_vat'];

        $feeItem = WebPayItem::shippingFee()
            ->setAmountIncVat($feeIncVat)
            ->setAmountExVat($feeExVat)
            ->setName('ShippingFee');

        return $feeItem;
    }
}
