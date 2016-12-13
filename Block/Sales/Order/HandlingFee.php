<?php
/**
 * Handling fee block
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Block\Sales\Order;

class HandlingFee extends \Magento\Sales\Block\Order\Totals
{

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_source;

    /**
     * HandlingFee constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
    }

    /**
     * Initialize all order totals relates with tax
     *
     */
    protected function initTotals()
    {
        $order = $this->getSource();
        $handlingFee = $order->getHandlingFeeInclTax();

        if (!(int) $handlingFee) {
            return $this;
        }

        $fee = new \Magento\Framework\DataObject(
            [
                'code' => 'handling_fee',
                'value' => $handlingFee,
                'label' => __('Payment handling fee'),
            ]
        );

        $this->getParentBlock()->addTotalBefore($fee, 'grand_total');

        return $this;
    }
}
