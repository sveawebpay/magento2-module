<?php
/**
 * Distribution type config values
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class DistributionType implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => \Svea\WebPay\Constant\DistributionType::EMAIL,
                'label' => __('Email'),
            ],
            [
                'value' => \Svea\WebPay\Constant\DistributionType::POST,
                'label' => __('Post'),
            ],
        ];
    }
}
