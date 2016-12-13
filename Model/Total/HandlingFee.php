<?php
/**
 * Handling fee total
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Total;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Model\Config;

class HandlingFee extends AbstractTotal
{
    protected $priceCurrency;
    protected $scopeConfig;

    const XML_PATH_DISPLAY_CART_PAYMENT_HANDLING_FEE = 'tax/cart_display/payment_handling_fee';

    /**
     * HandlingFee constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @inheritDoc
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $method         = $quote->getPayment()->getMethod();
        $baseFeeAmount  = $this->getFeeAmount($method);
        $store          = $quote->getStore();

        $feeAmount = $this->priceCurrency->convert(
            $baseFeeAmount,
            $store
        );

        $total->setTotalAmount($this->getCode(), $feeAmount);
        $total->setBaseTotalAmount($this->getCode(), $baseFeeAmount);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $value = $total->getHandlingFeeAmount();

        return [
            'code'  => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLabel()
    {
        return __('Payment Handling Fee');
    }

    /**
     * Get fee amount from admin
     *
     * @param $method
     * @return mixed
     */
    protected function getFeeAmount($method)
    {
        $key = strtolower("payment/{$method}/payment_handling_fee");

        $value = $this->scopeConfig
            ->getValue($key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $value;
    }
}
