<?php
/**
 * Disable payment if virtual observer
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DisablePaymentVirtualObserver implements ObserverInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * DisablePaymentVirtualObserver constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }


    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();
        if (!$quote) {
            return;
        }

        $items = $quote->getAllItems();

        $code = $observer->getMethodInstance()->getCode();
        $key = strtolower("payment/{$code}/disable_on_virtual");
        $disabledOnVirtual = $this->scopeConfig
            ->getValue($key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if (!$disabledOnVirtual) {
            return;
        }

        $result = $observer->getResult();

        foreach ($items as $item) {
            $isVirtual = $item->getIsVirtual();
            if ($isVirtual) {
                return $result->setIsAvailable(false);
            }
        }
    }
}
