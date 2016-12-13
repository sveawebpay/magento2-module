<?php
/**
 * handling fee total plugin
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Plugin;
use Magento\Quote\Api\Data\TotalSegmentExtensionFactory;
use \Magento\Checkout\Model\Session as CheckoutSession;


/**
 * Class HandlingFeeTotalSegmentPlugin
 * @package Webbhuset\SveaWebpay\Plugin
 */
class HandlingFeeTotalSegmentPlugin
{
    protected $totalSegmentExtensionFactory;
    protected $checkoutSession;

    /**
     * HandlingFeeTotalSegmentPlugin constructor.
     * @param TotalSegmentExtensionFactory $totalSegmentExtensionFactory
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        TotalSegmentExtensionFactory $totalSegmentExtensionFactory,
        CheckoutSession $checkoutSession
    ) {
        $this->totalSegmentExtensionFactory = $totalSegmentExtensionFactory;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Set handling fee extension attribute after process
     *
     * @param \Magento\Quote\Model\Cart\TotalsConverter $subject
     * @param $result
     * @return mixed
     */
    public function afterProcess(
        \Magento\Quote\Model\Cart\TotalsConverter $subject,
        $result
    ) {
        if (!isset($result['handling_fee'])) {
            return $result;
        }

        $quote = $this->checkoutSession->getQuote();

        $attributes = $result['handling_fee']->getExtensionAttributes();

        if ($attributes === null) {
            $attributes = $this->totalSegmentExtensionFactory->create();
        }

        $attributes->setHandlingFeeInclTax($quote->getHandlingFeeInclTax());
        $result['handling_fee']->setExtensionAttributes($attributes);

        return $result;
    }
}
