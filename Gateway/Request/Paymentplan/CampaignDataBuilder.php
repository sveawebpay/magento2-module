<?php
/**
 * Paymentplan campaign data request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Paymentplan;

class CampaignDataBuilder
{
    public function build($payment)
    {
        $campaignCode = $payment->getAdditionalInformation('selected_campaign_code');
        if (!$campaignCode) {
            throw new \Magento\Framework\Exception\ValidatorException(__('No campaign provided.'));
        }

        return $campaignCode;
    }
}
