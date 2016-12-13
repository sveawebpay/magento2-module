<?php
/**
 * Card icon config values
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Config\Source\Card;

/**
 * Svea Card Icon options class
 *
 * @package Webbhuset\SveaWebpay\Model\Source
 */
class Icon implements \Magento\Framework\Option\ArrayInterface
{
    
    const VISA = 'visa';
    const MASTERCARD = 'mastercard';
    const AMEX = 'amex';

    public function toOptionArray($isMultiselect = false)
    {
        $options = [
            [
                'value' => self::VISA,
                'label' => 'Visa',
            ],
            [
                'value' => self::MASTERCARD,
                'label' => 'MasterCard',
            ],
            [
                'value' => self::AMEX,
                'label' => 'American Express',
            ],
        ];
        
        if (!$isMultiselect) {
            array_unshift($options, ['value' => '', 'label' => __('--Please Select--')]);
        }

        return $options;
    }
    
}