<?php
/**
 * Check if payment plan is in range observer
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webbhuset\SveaWebpay\Model\Campaign;

class CheckPaymentplanRangeObserver implements ObserverInterface
{
    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * CheckPaymentplanRangeObserver constructor.
     * @param Campaign $campaign
     */
    public function __construct(
        Campaign $campaign
    ) {
        $this->campaign = $campaign;
    }

    /**
     * Disable svea paymentplan if sum is out of range for it
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();

        $method = $observer->getMethodInstance();
        if (!$quote || $method->getCode() != 'svea_paymentplan') {
            return;
        }

        $result = $observer->getResult();
        $total = $quote->getGrandTotal();
        $campaigns = $this->campaign->getPaymentplayCampaigns();

        foreach ($campaigns as $campaign) {
            $inRange = ($total > $campaign->fromAmount) && ($total < $campaign->toAmount);
            if ($inRange) {
                return;
            }
        }

        // If no campaign is in range, we disable it
        return $result->setIsAvailable(false);
    }
}
