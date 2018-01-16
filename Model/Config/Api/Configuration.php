<?php
/**
 * Api config
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Config\Api;

use Svea\WebPay\Config\ClientNumber;
use Svea\WebPay\Config\ConfigurationService;

/**
 * Svea configuration provider that uses values from system config
 *
 * @package Webbhuset\SveaWebpay\Model
 */
class Configuration implements \Svea\WebPay\Config\ConfigurationProvider
{
    const SSN_KEY = 'svea_ssn';
    const ADDRESS_RESPONSE_KEY = 'svea_address_response';
    const SELECTED_ADDRESS_SELECTOR_KEY = 'svea_selected_address';
    const SELECTED_HOSTED_METHOD_KEY = 'selected_hosted_method';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Configuration constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
    }


    /**
     * Get config value
     *
     * @param $type
     * @param $key
     * @return mixed|string
     */
    protected function getConfigValue($type, $key)
    {
        $method = $this->sveaCodeToConfigCode($type);

        $fullKey = strtolower("payment/{$method}/{$key}");

        $value = $this->scopeConfig->getValue($fullKey, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $value;
    }

    /**
     * Get return callback url for hosted methods
     *
     * @return string
     */
    public function getHostedReturnUrl()
    {
        return $this->urlBuilder->getUrl('sveawebpay/callback/index', ['secure' => true]);
    }

    /**
     * Get Svea Webpay method code from Magneto payment method code
     *
     * @param $methodCode Magento payment method code
     * @return string Svea Webpay constant for the method
     * @throws \Exception If $methodCode isn't valid
     */
    public function paymentMethodCodeToSveaMethodCode($methodCode)
    {
        switch ($methodCode) {
            case 'svea_invoice':
                return self::INVOICE_TYPE;
            case 'svea_paymentplan':
                return self::PAYMENTPLAN_TYPE;
            case 'svea_card':
            case 'svea_direct_bank':
                return self::HOSTED_TYPE;
            default:
                throw new \Exception("Invalid method code");
        }
    }


    /**
     * Map svea internal payment method code to our method codes
     *
     * @param $sveaCode
     * @return string
     * @throws \Exception
     */
    protected function sveaCodeToConfigCode($sveaCode)
    {
        if (strpos($sveaCode, 'svea_') !== false) {
            return $sveaCode;
        }
        $codes = [
            self::HOSTED_TYPE       => self::HOSTED_TYPE,
            self::HOSTED_ADMIN_TYPE => self::HOSTED_TYPE,
            self::INVOICE_TYPE      => self::INVOICE_TYPE,
            self::PAYMENTPLAN_TYPE  => self::PAYMENTPLAN_TYPE,
            self::ADMIN_TYPE        => self::INVOICE_TYPE,
        ];

        if (!isset($codes[$sveaCode])) {
            throw new \Exception('Payment method config not found');
        }

        return "svea_{$codes[$sveaCode]}";
    }


    /**
     * Get stored campaigns
     *
     * @return mixed|string
     */
    public function getCampaigns()
    {
        return $this->getConfigValue(self::PAYMENTPLAN_TYPE, 'campaigns');
    }

    /**
     * Check if payment method is active
     *
     * @param string $type Payment method type, like 'invoice', _not_ payment method code.
     *
     * @return bool
     */
    public function isActive($type)
    {
        $value = $this->getConfigValue($type, 'active');

        return (bool) $value;
    }

    /**
     * @inheritDoc
     */
    public function getUsername($type, $country) {
        $key = "username_{$country}";
        $value = $this->getConfigValue($type, $key);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getPassword($type, $country) {
        $key = "password_{$country}";
        $value = $this->getConfigValue($type, $key);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getClientNumber($type, $country) {
        $key = "client_number_{$country}";
        $value = $this->getConfigValue($type, $key);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getMerchantId($type, $country) {
        $key = "merchant_id";
        $value = $this->getConfigValue($type, $key);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function getSecret($type, $country) {
        if ($type == self::HOSTED_TYPE) {
            $key = "secret";
        } else {
            $key = "secret_{$country}";

        }
        $value = $this->getConfigValue($type, $key);

        return $value;
    }

    /**
     * Check for test mode
     *
     * @param $type
     * @return bool
     */
    public function isTestMode($type) {
        $value = $this->getConfigValue($type, 'test_mode');

        return (bool) $value;
    }

    /**
     * @inheritDoc
     */
    public function getEndPoint($type)
    {
        if ($this->isTestMode($type)) {
            $urls = $this->getTestUrls();
        } else {
            $urls = $this->getProdUrls();
        }

        if (isset($urls[$type])) {
            return $urls[$type];
        }
        throw new \Exception("Invalid type.");
    }

    /**
     * Get should capture on order confirmation from svea
     *
     * @return null|string
     */
    public function getShouldCaptureOnConfirmation()
    {
        return $this->getConfigValue('svea_card', 'order_confirmation_capture');
    }

    /**
     * This method is not used in this module, but must be here to satisfy the interface
     */
    public function getCheckoutSecret($country = NULL)
    {
        return;
    }

    /**
     * This method is not used in this module, but must be here to satisfy the interface
     */
    public function getCheckoutMerchantId($country = NULL)
    {
        return;
    }

    /**
     * @return array
     */
    private static function getTestUrls()
    {
        return array(
            self::HOSTED_TYPE       => ConfigurationService::SWP_TEST_URL,
            self::INVOICE_TYPE      => ConfigurationService::SWP_TEST_WS_URL,
            self::PAYMENTPLAN_TYPE  => ConfigurationService::SWP_TEST_WS_URL,
            self::HOSTED_ADMIN_TYPE => ConfigurationService::SWP_TEST_HOSTED_ADMIN_URL,
            self::ADMIN_TYPE        => ConfigurationService::SWP_TEST_ADMIN_URL,
            self::PREPARED_URL      => ConfigurationService::SWP_TEST_PREPARED_URL,
            self::CHECKOUT          => ConfigurationService::CHECKOUT_TEST_BASE_URL,
            self::CHECKOUT_ADMIN    => ConfigurationService::CHECKOUT_ADMIN_TEST_BASE_URL
        );
    }

    /**
     * @return array
     */
    private static function getProdUrls()
    {
        return array(
            self::HOSTED_TYPE       => ConfigurationService::SWP_PROD_URL,
            self::INVOICE_TYPE      => ConfigurationService::SWP_PROD_WS_URL,
            self::PAYMENTPLAN_TYPE  => ConfigurationService::SWP_PROD_WS_URL,
            self::HOSTED_ADMIN_TYPE => ConfigurationService::SWP_PROD_HOSTED_ADMIN_URL,
            self::ADMIN_TYPE        => ConfigurationService::SWP_PROD_ADMIN_URL,
            self::PREPARED_URL      => ConfigurationService::SWP_PROD_PREPARED_URL,
            self::CHECKOUT          => ConfigurationService::CHECKOUT_PROD_BASE_URL,
            self::CHECKOUT_ADMIN    => ConfigurationService::CHECKOUT_ADMIN_PROD_BASE_URL
        );
    }

}
