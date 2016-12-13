<?php
/**
 * Bank methodlist model
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model;

use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;
use Svea\WebPay\Constant\SystemPaymentMethod;
use Svea\WebPay\WebPay;

class MethodList
{

    const CACHE_PREFIX = 'svea_bank_methods_';
    const CACHE_LIFETIME =  60 * 60 * 24;
    /**
     * @var CacheInterface
     */
    protected $cache;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepository;

    /**
     * Banks with label and logo image name
     * @var array
     */
    protected $bankMethods = [
        [
            'code' => SystemPaymentMethod::BANKAXESS,
            'logo' => '',
            'label' => 'BankAxess',
        ],
        [
            'code' => SystemPaymentMethod::DBAKTIAFI,
            'logo' => '',
            'label' => 'Aktia',
        ],
        [
            'code' => SystemPaymentMethod::DBALANDSBANKENFI,
            'logo' => '',
            'label' => 'BankAxess',
        ],
        [
            'code' => SystemPaymentMethod::DBDANSKEBANKSE,
            'logo' => 'danskebank.svg',
            'label' => 'Danske Bank',
        ],
        [
            'code' => SystemPaymentMethod::DBNORDEAEE,
            'logo' => 'nordea.png',
            'label' => 'Nordea',
        ],
        [
            'code' => SystemPaymentMethod::DBNORDEAFI,
            'logo' => 'nordea.png',
            'label' => 'Nordea',
        ],
        [
            'code' => SystemPaymentMethod::DBNORDEASE,
            'logo' => 'nordea.png',
            'label' => 'Nordea',
        ],
        [
            'code' => SystemPaymentMethod::DBPOHJOLAFI,
            'logo' => '',
            'label' => 'Pohjola',
        ],
        [
            'code' => SystemPaymentMethod::DBSAMPOFI,
            'logo' => 'danskebank.svg',
            'label' => 'Danske Bank',
        ],
        [
            'code' => SystemPaymentMethod::DBSEBSE,
            'logo' => 'seb.jpg',
            'label' => 'SEB',
        ],
        [
            'code' => SystemPaymentMethod::DBSEBFTGSE,
            'logo' => 'seb.jpg',
            'label' => 'SEB',
        ],
        [
            'code' => SystemPaymentMethod::DBSHBFI,
            'logo' => 'handelsbanken.svg',
            'label' => 'Handelsbanken',
        ],
        [
            'code' => SystemPaymentMethod::DBSHBSE,
            'logo' => 'handelsbanken.svg',
            'label' => 'Handelsbanken',
        ],
        [
            'code' => SystemPaymentMethod::DBSPANKKIFI,
            'logo' => '',
            'label' => 'Soumen Pankki',
        ],
        [
            'code' => SystemPaymentMethod::DBSWEDBANKSE,
            'logo' => 'swedbank.png',
            'label' => 'Swedbank',
        ],
        [
            'code' => SystemPaymentMethod::DBTAPIOLAFI,
            'logo' => '',
            'label' => 'Tapiola',
        ],
    ];

    /**
     * MethodList constructor.
     * @param Config\Api\Configuration $apiConfig
     * @param \Webbhuset\SveaWebpay\Helper\Data $helper
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Webbhuset\SveaWebpay\Model\Config\Api\Configuration $apiConfig,
        \Webbhuset\SveaWebpay\Helper\Data $helper,
        CacheInterface $cache,
        LoggerInterface $logger,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        $this->apiConfig = $apiConfig;
        $this->helper = $helper;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->assetRepository = $assetRepository;
    }

    /**
     * Fetch available banks from Svea
     *
     * @return array
     */
    public function fetchMethods()
    {
        $countryCode = $this->helper->getStoreCountryCode();

        if (!$countryCode) {
            return [];
        }

        $cacheKey = self::CACHE_PREFIX . $countryCode;
        $bankMethods = json_decode($this->cache->load($cacheKey), true);

        if (!$bankMethods) {

            $response = WebPay::listPaymentMethods($this->apiConfig)
                ->setCountryCode($countryCode)
                ->doRequest();

            if (!$response->accepted) {
                return [];
            }

            $methods = $response->paymentmethods;
            $bankMethods = $this->filterBankMethods($methods);

            $this->cache->save(json_encode($bankMethods), $cacheKey, [], self::CACHE_LIFETIME);
        }

        return $bankMethods;
    }


    /**
     * Filter banks
     * @param array $methods
     * @return array
     */
    public function filterBankMethods(array $methods)
    {
        $availableMethods = [];
        foreach ($this->bankMethods as $method) {
            if (in_array($method['code'], $methods)) {

                if ($method['logo']) {
                    // Replace logo with url
                    $asset = $this->assetRepository
                        ->createAsset("Webbhuset_SveaWebpay::images/bank_logos/{$method['logo']}");
                    $method['logo'] = $asset->getUrl();
                }

                $availableMethods[] = $method;
            }
        }

        return $availableMethods;

    }
}