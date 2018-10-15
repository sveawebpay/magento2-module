<?php
/**
 * Cancel order builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;

use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPayAdmin;

class CancelOrderBuilder implements OrderActionBuilderInterface
{
    /**
     * @var Order
     */
    protected $helper;
    /**
     * @var Configuration
     */
    protected $apiConfig;
    protected $messageManager;

    public function __construct(
        OrderHelper $helper,
        Configuration $apiConfig,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->helper = $helper;
        $this->apiConfig = $apiConfig;
        $this->messageManager = $messageManager;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order = $payment->getOrder();
        $sveaOrderId = $order->getExtOrderId();
        $sveaOrder = $this->helper->fetchSveaOrder($order);

        if ($this->isCancelled($sveaOrder)) {
            $this->messageManager->addSuccessMessage(__('Order is already cancelled in Svea. Syncing status.'));

            return false;
        }

        $countryCode = $this->helper->getCountryCode($order);

        $request = WebPayAdmin::cancelOrder($this->apiConfig)
            ->setOrderId($sveaOrderId) // Uses order id for invoice, paymentplan
            ->setTransactionId($sveaOrderId) // Uses Transaction id for card orders
            ->setCountryCode($countryCode);

        return $request;
    }

    protected function isCancelled($sveaOrder) {
        if (isset($sveaOrder->orderDeliveryStatus) && $sveaOrder->orderDeliveryStatus == 'Cancelled') {
            return true;
        }

        if (isset($sveaOrder->status) && $sveaOrder->status == 'ANNULLED') {
            return true;
        }

        return false;
    }
}
