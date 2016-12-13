<?php
/**
 * Ui config provider. Handles frontend payment info
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Webbhuset\SveaWebpay\Gateway\Config\Config;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Webbhuset\SveaWebpay\Model\Campaign;
use Svea\WebPay\Constant\SystemPaymentMethod;
use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Escaper
     */
    protected $escaper;
    protected $assetRepository;
    protected $paymentHelper;

    const INVOICE_CODE      = 'svea_invoice';
    const CARD_CODE         = 'svea_card';
    const PAYMENTPLAN_CODE  = 'svea_paymentplan';
    const DIRECT_BANK_CODE  = 'svea_direct_bank';
    const SVEA_ADDRESS      = 'svea_address';

    const REDIRECT_FORM_URL     = 'sveawebpay/hosted/redirect';
    const PRICE_PER_MONTH_URL   = 'sveawebpay/campaigns/calculate';
    const GET_ADDRESS_URL       = 'sveawebpay/address/get';

    const XML_PATH_DISPLAY_CART_PAYMENT_HANDLING_FEE = 'tax/cart_display/payment_handling_fee';


    /**
     * @var \Webbhuset\SveaWebpay\Model\MethodList
     */
    protected $methodList;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var OrderHelper
     */
    protected $orderHelper;
    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * ConfigProvider constructor.
     *
     * @param Escaper $escaper
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param OrderHelper $orderHelper
     * @param \Webbhuset\SveaWebpay\Model\MethodList $methodList
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Campaign $campaign
     */
    public function __construct(
        Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Payment\Helper\Data $paymentHelper,
        OrderHelper $orderHelper,
        \Webbhuset\SveaWebpay\Model\MethodList $methodList,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        Campaign $campaign
    ) {
        $this->escaper          = $escaper;
        $this->assetRepository  = $assetRepository;
        $this->paymentHelper    = $paymentHelper;
        $this->methodList       = $methodList;
        $this->scopeConfig      = $scopeConfig;
        $this->urlBuilder       = $urlBuilder;
        $this->orderHelper      = $orderHelper;
        $this->campaign         = $campaign;
    }

    /**
     * Get configuration
     */
    public function getConfig()
    {
        $data = [];

        if ((int) $this->getMethod(self::INVOICE_CODE)->isActive()) {
            $data[self::INVOICE_CODE] = [
                'additionalDataSsnKey' => Configuration::SSN_KEY,
            ];
        }

        if ((int) $this->getMethod(self::PAYMENTPLAN_CODE)->isActive()) {
            $data[self::PAYMENTPLAN_CODE] = [
                'campaigns' => $this->campaign->getPaymentplayCampaigns(),
                'additionalDataSelectedCampaignKey' => 'selected_campaign_code',
                'additionalDataSsnKey' => Configuration::SSN_KEY,
                'price_per_month_url' => $this->urlBuilder->getUrl(self::PRICE_PER_MONTH_URL),
            ];
        }

        if ((int) $this->getMethod(self::CARD_CODE)->isActive()) {
            $data[self::CARD_CODE] = [
                'redirectOnSuccessUrl' => $this->urlBuilder->getUrl(self::REDIRECT_FORM_URL),
                'icons' => $this->getIcons(),
                'selected_hosted_method_key' => Configuration::SELECTED_HOSTED_METHOD_KEY,
                'selected_hosted_method_value' => SystemPaymentMethod::SVEACARDPAY,
            ];
        }

        if ((int) $this->getMethod(self::DIRECT_BANK_CODE)->isActive()) {
            $data[self::DIRECT_BANK_CODE] = [
                'banks' => $this->getAvailableMethods(),
                'selected_hosted_method_key' => Configuration::SELECTED_HOSTED_METHOD_KEY,
            ];
        }

        $data[self::SVEA_ADDRESS] = [
            'config' => $this->orderHelper->getSveaAddressConfig(),
            'getAddressUrl' => $this->urlBuilder->getUrl(self::GET_ADDRESS_URL),
            'additionalDataSelectedAddressKey' => Configuration::SELECTED_ADDRESS_SELECTOR_KEY,
        ];

        return [
            'payment' => $data,
            'reviewHandlingFeeDisplayMode' => $this->getHandlingFeeDisplayMode(),
        ];
    }

    /**
     * Get handling fee display mode
     *
     * @param null $store
     * @return string
     */
    protected function getHandlingFeeDisplayMode($store = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_DISPLAY_CART_PAYMENT_HANDLING_FEE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if ($value == \Magento\Tax\Model\Config::DISPLAY_TYPE_BOTH) {
            return 'both';
        } elseif ($value == \Magento\Tax\Model\Config::DISPLAY_TYPE_EXCLUDING_TAX) {
            return 'excluding';
        }
        return 'including';
    }

    /**
     * Get available bank methods
     *
     * @return array
     */
    protected function getAvailableMethods()
    {
        return $this->methodList->fetchMethods();
    }

    /**
     * Get list of icons that should be displayed together with the payment method
     *
     * @return array List of icons
     */
    protected function getIcons()
    {
        $method = $this->getMethod(self::CARD_CODE);
        $icons = $method->getConfigData('icons');

        if (!$icons) {
            return [];
        }

        $icons = explode(',', $icons);
        $result = [];
        foreach ($icons as $icon) {
            $asset = $this->assetRepository->createAsset("Webbhuset_SveaWebpay::images/icon/svea_{$icon}.png");
            $result[] = [
                'label' => __($icon),
                'url' => $asset->getUrl(),
            ];
        }
        return $result;
    }

    /**
     * Get payment method for code
     *
     * @param $code
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getMethod($code)
    {
        return $this->paymentHelper->getMethodInstance($code);
    }
}
