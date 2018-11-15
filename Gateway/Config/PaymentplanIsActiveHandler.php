<?php
/**
 * Payment plan is active handler
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;

class PaymentplanIsActiveHandler implements ValueHandlerInterface
{
    /**
     * @var \Webbhuset\SveaWebpay\Helper\Order
     */
    protected $helper;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * PaymentplanIsActiveHandler constructor.
     * @param \Webbhuset\SveaWebpay\Helper\Order $helper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Webbhuset\SveaWebpay\Helper\Order $helper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $subject, $storeId = null)
    {
        $key = strtolower("payment/svea_paymentplan/campaigns");
        $campaigns = $this->scopeConfig
            ->getValue($key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $isActive = $this->scopeConfig
            ->getValue('payment/svea_paymentplan/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $isActive && (bool) $campaigns;
    }
}
