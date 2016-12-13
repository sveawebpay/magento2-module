<?php
/**
 * Assign data from frontend to payment observer
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class DataAssignObserver
 */
class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * Assign data from frontend to payment
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $method = $data->getMethod();
        $isSveaOrder = strpos($method, 'svea_') !== false;

        if (!$isSveaOrder) {
            return;
        }

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $payment = $this->readPaymentModelArgument($observer);

        foreach ($additionalData as $key => $value) {

            if (is_object($value)) {
                continue;
            }

            $payment->setAdditionalInformation(
                $key,
                $value
            );
        }
    }
}
