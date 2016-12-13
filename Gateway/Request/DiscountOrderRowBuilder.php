<?php
/**
 * Discount order rows builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Svea\WebPay\WebPayItem;

class DiscountOrderRowBuilder
{
    public function build($item)
    {
        $discount = $item->getDiscountAmount();
        $taxPercent = (int) round($item->getTaxPercent());
        $name = "discount-{$item->getQuoteItemId()}";

        $discountRow = WebPayItem::fixedDiscount()
            ->setName($name)
            ->setAmountExVat($discount)
            ->setVatPercent($taxPercent);

        return $discountRow;
    }
}
