<?php
/**
 * Webpay helper
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Helper;

use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Data
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * cdn domain for logos
     *
     * @var string
     */
    const LOGO_CND_DOMAIN = 'https://cdn.svea.com';
    /**
     * Constant for small logo size
     *
     * @var string
     */
    const LOGO_SIZE_SMALL = 'small';
    /**
     * Constant for medium logo size
     *
     * @var string
     */
    const LOGO_SIZE_MEDIUM = 'medium';
    /**
     * Constant for large logo size
     *
     * @var string
     */
    const LOGO_SIZE_LARGE = 'large';
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Get current stores country code
     * @return string
     */
    public function getStoreCountryCode()
    {
        $countryCode = $this->scopeConfig
            ->getValue(
                Custom::XML_PATH_GENERAL_COUNTRY_DEFAULT,
                ScopeInterface::SCOPE_STORE
            );

        if (!$countryCode) {
            $this->logger->alert('Store default country not set. Svea Webpay needs it for api requests');
        }

        return $countryCode;
    }

    /**
     * Get url for logo
     *
     * @param $countryCode
     * @param string $size
     * @return string
     */
    public function getLogoUrl($countryCode, $size = self::LOGO_SIZE_SMALL)
    {
        $color = "rgb";

        switch (strtoupper($countryCode)) {
            case 'NO':
            case 'DK':
            case 'NL':
                $type = 'finans';
                break;
            case 'SE':
            case 'FI':
            case 'DE':
            default:
                $type = 'ekonomi';
                break;
        }

        switch ($type) {
            case 'ekonomi':
                $path = "/sveaekonomi/{$color}_ekonomi_{$size}.png";
                break;
            case 'finans':
                $path = "/sveafinans/{$color}_svea-finans_{$size}.png";
                break;
        }
        return self::LOGO_CND_DOMAIN . $path;

    }

    /**
     * Get payment plan campaigns
     *
     * @return array
     */
    public function getPaymentplayCampaigns()
    {
        $key = "payment/svea_paymentplan/campaigns";

        $campaignDataJson = $this->scopeConfig
            ->getValue($key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $campaignData = json_decode($campaignDataJson);

        if (!$campaignData) {
            return [];
        }

        return $campaignData->campaignCodes;
    }
}
