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
use Magento\Framework\App\Config\ScopeConfigInterface;

class OrderRowBuilder
{
/**
 * @var \Webbhuset\SveaWebpay\Gateway\Request\CampaignDataBuilder
 */
    protected $scopeConfigInterface;

    public function __construct(
        ScopeConfigInterface $scopeConfigInterface
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
    }


    public function build($item, $multiplier = 1)
    {
        $orderRow = WebPayItem::orderRow();
        $qty = $item->getQtyOrdered() ?: $item->getQty() * $multiplier;

        $quoteItemId = $item->getQuoteItemId();
        $sku = $item->getSku();

        $prefixed = "{$quoteItemId}-{$sku}";
        $name     = mb_substr($item->getName() . $this->getItemOptions($item), 0, 40);

        $orderRow->setArticleNumber($prefixed)
            ->setDescription($item->getDescription())
            ->setName($name)
            ->setAmountExVat((float)$item->getPrice())
            ->setVatPercent((int)$item->getTaxPercent())
            ->setQuantity((int) $qty);

        return $orderRow;
    }

    /**
     * Get item options from orderitem.
     *
     * @param $item
     *
     * @return string
     */
    protected function getItemOptions($item)
    {
        $isEnabled = $this->scopeConfigInterface->getValue(
            'payment/svea_hosted/svea_include_options_on_order_rows',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            return '';
        }

        $itemOptions = [];

        $options     = $item->getProductOptions();

        if ($options) {
            if (isset($options['options']) && !empty($options['options'])) {
                $itemOptions = array_merge($itemOptions, $options['options']);
            }
            if (isset($options['additional_options']) && !empty($options['additional_options'])) {
                $itemOptions = array_merge($itemOptions, $options['additional_options']);
            }
            if (isset($options['attributes_info']) && !empty($options['attributes_info'])) {
                $itemOptions = array_merge($itemOptions, $options['attributes_info']);
            }
        }
        $options = [];
        foreach ($itemOptions as $option) {
            $options[] = implode(': ', array_intersect_key($option,array_flip(['label','value'])));
        }
        $options = implode(', ', $options);

        if (!empty(trim($options))) {

            return ' ' . $options;
        }
    }
}
