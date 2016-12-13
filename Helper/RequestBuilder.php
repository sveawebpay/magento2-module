<?php
/**
 * Request helper
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Svea\WebPay\WebPayItem;

class RequestBuilder
{
    const ROW_TYPE_ARTICLE = 'article';
    const ROW_TYPE_DISCOUNT = 'discount';
    const ROW_TYPE_SHIPPING = 'shipping';
    const ROW_TYPE_INVOICE_FEE = 'invoice_fee';

    protected $scopeConfig;

    /**
     * RequestBuilder constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get distribution type
     *
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    public function getDistributionType(\Magento\Sales\Model\Order $order)
    {
        $method = $order->getPayment()->getMethod();
        $xmlPath = "payment/{$method}/distribution_type";

        $countryCode = $this->scopeConfig
            ->getValue(
                $xmlPath,
                ScopeInterface::SCOPE_STORE
            );

        return $countryCode;
    }

    /**
     * Split a Svea row so you can partially deliver order
     *
     * @param $addRequest
     * @param $updateRequest
     * @param array $item
     * @param $row
     * @param null $discountRow
     */
    public function splitRow($addRequest, $updateRequest, array $item, $row, $discountRow = null)
    {
        $this->splitOrderRow($addRequest, $updateRequest, $item, $row);

        if ($discountRow) {
            $this->splitDiscountRow($addRequest, $updateRequest, $item, $discountRow);
        }
    }

    /**
     * Split order row, add new row with rest of qty
     * @param $addRequest
     * @param $updateRequest
     * @param array $item
     * @param $row
     */
    protected function splitOrderRow($addRequest, $updateRequest, array $item, $row)
    {
        $invoiceQty = $item['qty'];
        $restQty = $row->quantity - $invoiceQty;

        $partialActionRow = WebPayItem::numberedOrderRow()
            ->setRowNumber($row->rowNumber)
            ->setArticleNumber($row->articleNumber)
            ->setAmountExVat($row->amountExVat)
            ->setVatPercent($row->vatPercent)
            ->setQuantity($invoiceQty);

        $updateRequest->updateOrderRow($partialActionRow);

        $restOfRow = WebPayItem::orderRow()
            ->setArticleNumber($row->articleNumber)
            ->setDescription($row->description)
            ->setAmountExVat($row->amountExVat)
            ->setVatPercent($row->vatPercent)
            ->setQuantity($restQty);

        $addRequest->addOrderRow($restOfRow);
    }

    /**
     * Split a discount row
     *
     * @param $addRequest
     * @param $updateRequest
     * @param array $item
     * @param $discountRow
     */
    protected function splitDiscountRow($addRequest, $updateRequest, array $item, $discountRow)
    {
        $invoiceQty = $item['qty'];
        $amountExVat = -($item['discount'] * $invoiceQty);
        $restAmountExVat = $discountRow->amountExVat - $amountExVat;

        $partialActionRow = WebPayItem::numberedOrderRow()
            ->setRowNumber($discountRow->rowNumber)
            ->setDescription($discountRow->description)
            ->setAmountExVat($amountExVat)
            ->setVatPercent($discountRow->vatPercent)
            ->setQuantity($discountRow->quantity);

        $updateRequest->updateOrderRow($partialActionRow);

        $restOfRow = WebPayItem::orderRow()
            ->setArticleNumber($discountRow->articleNumber)
            ->setName($discountRow->description)
            ->setAmountExVat($restAmountExVat)
            ->setVatPercent($discountRow->vatPercent)
            ->setQuantity($discountRow->quantity);

        $addRequest->addOrderRow($restOfRow);
    }

    /**
     * Find discount row belonging to order row
     *
     * @param array $rows
     * @param array $item
     * @param bool $skipDelivered
     * @param null $invoiceId
     * @return bool|mixed
     * @throws \Exception
     */
    public function findMatchingDiscountRow(array $rows, array $item, $skipDelivered = true, $invoiceId = null)
    {
        if (!$item['discount']) {
            return false;
        }

        $itemId = $item['item_id'];
        $discountId = 'discount-' . $itemId;
        foreach ($rows as $row) {
            if ($skipDelivered && $row->status == 'Delivered') {
                continue;
            }
            if ($invoiceId && $row->invoiceId != $invoiceId) {
                continue;
            }

            if ($row->description == $discountId || $row->name == $discountId) {
                return $row;
            }
        }

        throw new \Exception("Discount row for {$itemId} not found");
    }

    /**
     * Get item data as array
     *
     * @param $itemCollection
     * @return array
     */
    public function getItemData($itemCollection)
    {
        $items = [];
        foreach ($itemCollection as $item) {
            $qty        = $item->getQty();
            $price      = $item->getPriceInclTax();
            $discount   = ($item->getDiscountAmount() - $item->getDiscountTaxCompensationAmount());
            $sku        = $item->getSku();
            $itemId     = $item->getOrderItem()->getQuoteItemId();

            $prefixed = $itemId . '-' . $sku;

            $items[$itemId] = [
                'qty'       => $qty,
                'price'     => $price,
                'discount'  => $discount,
                'sku'       => $prefixed,
                'item_id'   => $itemId,
            ];
        }

        return $items;
    }

    /**
     * Extract quote item id from sku
     * @param $sku
     * @return bool
     */
    public function extractQuoteItemId($sku)
    {
        // Ex 123-r3st0fsku
        $pattern = "/^(\d+)-.*$/";
        preg_match($pattern, $sku, $matches);

        if (!isset($matches[1])) {
            return false;
        }
        $quoteItemId = $matches[1];

        return $quoteItemId;
    }

    /**
     * Find matching row from sku
     *
     * @param array $items
     * @param $sku
     * @return bool|mixed
     * @throws \Exception
     */
    public function findItemBySku(array $items, $sku)
    {
        $quoteItemId = $this->extractQuoteItemId($sku);

        if (!$quoteItemId) {
            throw new \Exception('Couldnt find quote item id on sku ' . $sku);
        }

        foreach ($items as $item) {
            if ($item['item_id'] == $quoteItemId) {
                return $item;
            }
        }

        return false;
    }

    /**
     * Sort order rows in articles, discounts and shipping
     *
     * @param array $rows
     * @return array
     * @throws \Exception
     */
    public function sortOrderRows(array $rows)
    {
        $sorted = [
            'articles'  => [],
            'shipping'  => [],
            'discounts' => [],
        ];

        foreach ($rows as $row) {
            $rowType = $this->getRowType($row);
            if ($rowType == self::ROW_TYPE_ARTICLE) {
                $sorted['articles'][] = $row;
                continue;
            }

            if ($rowType == self::ROW_TYPE_DISCOUNT) {
                $sorted['discounts'][] = $row;
                continue;
            }

            if ($rowType == self::ROW_TYPE_SHIPPING) {
                $sorted['shipping'][] = $row;
                continue;
            }

            if ($rowType == self::ROW_TYPE_INVOICE_FEE) {
                $sorted['invoice_fee'][] = $row;
                continue;
            }

            throw new \Exception('Row not recognized.');
        }

        return $sorted;
    }

    protected function getRowType($row)
    {
        if ($this->isArticleRow($row)) {
            return self::ROW_TYPE_ARTICLE;
        }

        if ($this->isDiscountRow($row)) {
            return self::ROW_TYPE_DISCOUNT;
        }

        if ($this->isShippingFeeRow($row)) {
            return self::ROW_TYPE_SHIPPING;
        }

        if ($this->isInvoiceFeeRow($row)) {
            return self::ROW_TYPE_INVOICE_FEE;
        }

        throw new \Exception('Row is not recognized');
    }

    /**
     * Check if row is article row
     * @param \Svea\WebPay\BuildOrder\RowBuilders\NumberedOrderRow $row
     * @return bool
     */
    protected function isArticleRow(\Svea\WebPay\BuildOrder\RowBuilders\NumberedOrderRow $row)
    {
        return (bool) $row->articleNumber;
    }

    /**
     * Check if row is a discount row
     * @param \Svea\WebPay\BuildOrder\RowBuilders\NumberedOrderRow $row
     * @return bool
     */
    protected function isDiscountRow(\Svea\WebPay\BuildOrder\RowBuilders\NumberedOrderRow $row)
    {
        if (strpos($row->description, 'discount-') !== false) {
            return true;
        }

        if (strpos($row->name, 'discount-') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Check if row is a shipping fee row
     *
     * @param \Svea\WebPay\BuildOrder\RowBuilders\NumberedOrderRow $row
     * @return bool
     */
    protected function isShippingFeeRow(\Svea\WebPay\BuildOrder\RowBuilders\NumberedOrderRow $row)
    {
        if ($row->description == 'ShippingFee') {
            return true;
        }

        if ($row->name == 'ShippingFee') {
            return true;
        }

        return false;
    }

    /**
     * Check if row is an invoice fee row
     *
     * @param $row
     * @return bool
     */
    protected function isInvoiceFeeRow($row)
    {
        if ($row->description == 'Payment Handling Fee') {
            return true;
        }

        if ($row->name == 'Payment Handling Fee') {
            return true;
        }

        return false;
    }

    /**
     * Get rows with type
     *
     * @param $type
     * @param array $sortedOrderRows
     * @return array|mixed
     */
    public function getRowsWithType($type, array $sortedOrderRows)
    {
        if (isset($sortedOrderRows[$type])) {
            return $sortedOrderRows[$type];
        }

        return [];
    }
}
