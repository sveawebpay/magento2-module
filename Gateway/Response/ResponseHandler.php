<?php
/**
 * Handle response
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class ResponseHandler implements HandlerInterface
{
    /**
     * Constructor
     *
     * @param \Webbhuset\SveaWebpay\Helper\Order $orderHelper
     */
    public function __construct(\Webbhuset\SveaWebpay\Helper\Order $orderHelper)
    {
        $this->orderHelper = $orderHelper;
    }

    /**
     * Set data on order and payment
     *
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $order->setForcedShipmentWithInvoice(true);
        $order->setCanShipPartially(true);

        $response = $response['svea'];

        $countryCode = $this->orderHelper->getCountryCode($order);
        $customerType = $this->orderHelper->getCustomerType($order);

        if (!$this->orderHelper->shouldReplaceAddress($countryCode, $customerType)) {
            $this->updateOrderAddresses($response->customerIdentity, $order);
        }

        $sveaOrderId = $response->sveaOrderId;
        $order->setExtOrderId($sveaOrderId);

        $payment->setTransactionId($sveaOrderId)
            ->setIsTransactionClosed(false);
    }

    /**
     * Update order addresses
     *
     * @param \Svea\WebPay\WebService\WebServiceResponse\CustomerIdentity\CreateOrderIdentity $customer
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return void
     */
    protected function updateOrderAddresses(
        \Svea\WebPay\WebService\WebServiceResponse\CustomerIdentity\CreateOrderIdentity $customer,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $shippingAddress = $order->getShippingAddress();
        $this->updateAddress($customer, $shippingAddress);

        $billingAddress = $order->getBillingAddress();
        $this->updateAddress($customer, $billingAddress);
    }

    /**
     * Update address
     *
     * @param \Svea\WebPay\WebService\WebServiceResponse\CustomerIdentity\CreateOrderIdentity $customer
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $address
     * @return void
     */
    protected function updateAddress(
        \Svea\WebPay\WebService\WebServiceResponse\CustomerIdentity\CreateOrderIdentity $customer,
        \Magento\Sales\Api\Data\OrderAddressInterface $address
    ) {
        $fullName = explode(' ', $customer->fullName);
        $a = $fullName;
        $firstName = array_shift($a);
        $lastName = implode(' ', array_splice($fullName, 1));

        $addressData = [
            'firstname'     => $firstName,
            'lastname'      => $lastName,
            'street'        => [ $customer->street, $customer->coAddress ],
            'postcode'      => $customer->zipCode,
            'city'          => $customer->locality
        ];

        $address->setData(array_merge($address->getData(), $addressData));
    }
}
