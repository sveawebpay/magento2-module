<?php
/**
 * Order row builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Svea\WebPay\WebPayItem;

class OrderRowBuilder
{

    public function build($item)
    {
        $orderRow = WebPayItem::orderRow();
        $qty = $item->getQtyOrdered() ?: $item->getQty();

        $quoteItemId = $item->getQuoteItemId();
        $sku = $item->getSku();

        $prefixed = "{$quoteItemId}-{$sku}";

        $orderRow->setArticleNumber($prefixed)
            ->setDescription($item->getDescription())
            ->setName($item->getName())
            ->setAmountExVat((float)$item->getPrice())
            ->setVatPercent((int)$item->getTaxPercent())
            ->setQuantity((int) $qty);

        return $orderRow;
    }
}
