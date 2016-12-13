<?php
/**
 * Svea info block
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Block\Info;

use Magento\Backend\Block\Template\Context;
use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;

class SveaInfo extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Webbhuset_SveaWebpay::info/svea_order_info.phtml';

    protected $helper;

    protected $properties = [
        'status'                => 'Status',
        'transactionId'         => 'Transaction Id',
        'orderId'               => 'Svea Order Id',
        'creditStatus'          => 'Credit Status',
        'orderStatus'           => 'Order Status',
        'orderDeliveryStatus'   => 'Order Delivery Status',
        'paymentMethod'         => 'Payment method',
        'errorMessage'          => 'Error Message',
        'accepted'              => 'Is Accepted',
    ];

    /**
     * SveaInfo constructor.
     * @param Context $context
     * @param OrderHelper $helper
     * @param array $data
     */
    function __construct(
        Context $context,
        OrderHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get svea order
     *
     * @return bool|mixed|\Svea\WebPay\HostedService\HostedResponse\HostedAdminResponse\HostedAdminResponse
     */
    public function getSveaOrder()
    {
        $order = $this->getInfo()->getOrder();

        return $this->helper->fetchSveaOrder($order);
    }

    /**
     * Get order data
     *
     * @return array
     */
    public function getOrderData()
    {
        $sveaOrder = $this->getSveaOrder();
        $data = [];

        $data[] = $this->getOrderDataItem('Method', $this->getMethod()->getTitle());

        foreach ($this->properties as $property => $label) {
            if (!isset($sveaOrder->$property)) {
                continue;
            }

            $data[] = $this->getOrderDataItem($label, $sveaOrder->$property);
        }

        return $data;
    }

    /**
     * Get data item
     *
     * @param $label
     * @param $value
     * @return array
     */
    protected function getOrderDataItem($label, $value)
    {
        return [
            'label' => $label,
            'value' => $value,
        ];
    }
}
