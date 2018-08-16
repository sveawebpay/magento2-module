<?php
/**
 * Order helper
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Helper;

use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPayAdmin;

class Order
{
    protected $apiConfig;
    protected $registry;

    const REGISTRY_KEY = 'svea_order';
    /**
     * @var \Magento\Backend\App\ConfigInterface
     */
    protected $config;

    /**
     * Order constructor.
     * @param Configuration $apiConfig
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config $config
     */
    function __construct(
        Configuration $apiConfig,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config $config
    ) {
        $this->apiConfig = $apiConfig;
        $this->registry = $registry;
        $this->config = $config;
    }

    /**
     * Fetch Svea order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool|mixed|\Svea\WebPay\HostedService\HostedResponse\HostedAdminResponse\HostedAdminResponse
     * @throws \Exception
     */
    public function fetchSveaOrder(\Magento\Sales\Model\Order $order)
    {
        if ($sveaOrder = $this->registry->registry(self::REGISTRY_KEY)) {
            return $sveaOrder;
        }

        $payment = $order->getPayment();

        if (!$payment) {
            return false;
        }

        $method = $payment->getMethod();
        $sveaOrderId = $order->getExtOrderId();

        if (!$sveaOrderId) {
            return false;
        }

        $countryCode = $this->getCountryCode($order);

        $request = WebPayAdmin::queryOrder($this->apiConfig)
            ->setOrderId($sveaOrderId)
            ->setTransactionId($sveaOrderId)
            ->setCountryCode($countryCode);

        switch ($method) {
            case 'svea_invoice':
                $sveaOrder = $request->queryInvoiceOrder()->doRequest();
                break;
            case 'svea_paymentplan':
                $sveaOrder = $request->queryPaymentPlanOrder()->doRequest();
                break;
            case 'svea_card':
                $sveaOrder = $request->queryCardOrder()->doRequest();
                break;
            case 'svea_direct_bank':
                $sveaOrder = $request->queryDirectBankOrder()->doRequest();
                break;
            default:
                $sveaOrder = false;
                break;
        }

        if (!$sveaOrder) {
            throw new \Exception('Svea order could not be fetched');
        }

        $this->registry->register(self::REGISTRY_KEY, $sveaOrder);

        return $sveaOrder;
    }

    /**
     * Get billing address country code
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getCountryCode(\Magento\Sales\Model\Order $order)
    {
        $billingAddress = $order->getBillingAddress();
        $countryCode = $billingAddress->getCountryId();

        return $countryCode;
    }

    /**
     * Get customer type from order
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getCustomerType(\Magento\Sales\Model\Order $order)
    {
        $payment = $order->getPayment();

        $customerType = $payment->getAdditionalInformation(
            \Webbhuset\SveaWebpay\Model\Config\Api\Configuration::CUSTOMER_TYPE_KEY
        ) ?: \Webbhuset\SveaWebpay\Model\Address::PRIVATE_TYPE;

        return $customerType;
    }

    /**
     * Get address config
     *
     * @return mixed
     */
    public function getSveaAddressConfig()
    {
        return $this->config->getValue('svea_webpay/svea_address');
    }

    /**
     * Check if address should be replaced
     *
     * @param $countryCode
     * @param $customerType
     * @return bool
     */
    public function shouldReplaceAddress($countryCode, $customerType)
    {
        $config = $this->getSveaAddressConfig();

        if (isset($config[$countryCode][$customerType]['replace_address'])) {
            return (bool) $config[$countryCode][$customerType]['replace_address'];
        }

        return true;
    }
}
