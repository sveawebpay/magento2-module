<?php
/**
 * Add address to order observer
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;

class SetSsnAddressObserver implements ObserverInterface
{
    protected $orderHelper;

    public function __construct(\Webbhuset\SveaWebpay\Helper\Order $orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order      = $observer->getOrder();
        $payment    = $order->getPayment();
        $method     = $payment->getMethod();

        $addressMethods = [
            'svea_paymentplan',
            'svea_invoice',
        ];

        if (!in_array($method, $addressMethods)) {
            return;
        }

        $countryCode = $this->orderHelper->getCountryCode($order);
        $customerType = $this->orderHelper->getCustomerType($order);

        if (!$this->orderHelper->shouldReplaceAddress($countryCode, $customerType)) {
            return;
        }

        $addresses = $payment->getAdditionalInformation(Configuration::ADDRESS_RESPONSE_KEY);
        if (!$addresses) {
            throw new \Exception('Svea error 2020 : Address could not be set on order');
        }

        $selector = $payment->getAdditionalInformation(Configuration::SELECTED_ADDRESS_SELECTOR_KEY);
        if (!$selector) {
            throw new \Exception('Svea error 2021 : Address could not be set on order');
        }

        $selectedAddress = $this->findAddress($addresses, $selector);
        if (!$selectedAddress) {
            throw new \Exception('Svea error 2022 : Address could not be set on order');
        }

        $billingAddress     = $order->getBillingAddress();
        $shippingAddress    = $order->getShippingAddress();

        $newBilling         = $this->mergeAddress($billingAddress, $selectedAddress);
        $newShipping        = $this->mergeAddress($shippingAddress, $selectedAddress);

        $order->setBillingAddress($newBilling);
        $order->setShippingAddress($newShipping);
    }

    /**
     * Get address from selector
     *
     * @param $addresses
     * @param $selector
     *
     * @return array | false
     */
    protected function findAddress(array $addresses, $selector)
    {
        foreach ($addresses as $address) {
            if ($address['addressSelector'] == $selector) {

                return $address;
            }
        }

        return false;
    }

    /**
     * Merge magento address with svea address
     *
     * @param $address
     * @param $data
     *
     * @return \Magento\Customer\Model\Address\AbstractAddress
     */
    protected function mergeAddress($address, array $data)
    {
        $updateFields = [
            'firstname',
            'lastname',
            'street',
            'telephone',
            'postcode',
            'city',
            'country_id',
            'company',
            'customer_type'
        ];

        foreach ($updateFields as $field) {
            $address->setData($field, $data[$field]);
        }

        $address->setData('street', implode($data['street']));

        return $address;
    }
}
