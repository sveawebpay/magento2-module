<?php
/**
 * Can cancel handler
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

class CanCancelHandler implements ValueHandlerInterface
{
    /**
     * @var \Webbhuset\SveaWebpay\Helper\Order
     */
    protected $helper;

    /**
     * CanCancelHandler constructor.
     * @param \Webbhuset\SveaWebpay\Helper\Order $helper
     */
    public function __construct(
        \Webbhuset\SveaWebpay\Helper\Order $helper
    ) {
        $this->helper = $helper;
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
        $paymentDO  = $subject['payment'];
        $payment    = $paymentDO->getPayment();
        $order      = $payment->getOrder();
        $sveaOrder  = $this->helper->fetchSveaOrder($order);

        $invalidStatuses = [
            'CONFIRMED',
            'SUCCESS',
            'DELIVERED',
        ];

        if (isset($sveaOrder->status) && in_array($sveaOrder->status, $invalidStatuses)) {
            return false;
        }

        return true;
    }
}
