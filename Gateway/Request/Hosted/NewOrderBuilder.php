<?php
/**
 * Hosted new request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Hosted;


use Webbhuset\SveaWebpay\Gateway\Request\CustomerBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\DiscountOrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\OrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\ShippingFeeBuilder;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPay;
use Webbhuset\SveaWebpay\Gateway\Request\AbstractNewOrderBuilder;
use Magento\Store\Model\Store;

class NewOrderBuilder extends AbstractNewOrderBuilder
{
    protected $rowBuilder;
    /**
     * @var \Webbhuset\SveaWebpay\Model\Config\Api\Config
     */
    protected $apiConfig;
    /**
     * @var \Webbhuset\SveaWebpay\Gateway\Request\OrderRowDiscountBuilder
     */
    protected $discountBuilder;
    /**
     * @var CustomerBuilder
     */
    protected $customerBuilder;
    /**
     * @var ShippingFeeBuilder
     */
    protected $shippingFeeBuilder;
    protected $store;

    /**
     * NewOrderBuilder constructor.
     * @param OrderRowBuilder $rowBuilder
     * @param DiscountOrderRowBuilder $discountBuilder
     * @param CustomerBuilder $customerBuilder
     * @param ShippingFeeBuilder $shippingFeeBuilder
     * @param Configuration $apiConfig
     * @param Store $store
     */
    public function __construct(
        OrderRowBuilder $rowBuilder,
        DiscountOrderRowBuilder $discountBuilder,
        CustomerBuilder $customerBuilder,
        ShippingFeeBuilder $shippingFeeBuilder,
        Configuration $apiConfig,
        Store $store,
        \Webbhuset\SveaWebpay\Helper\Order $orderHelper
    ) {
        $this->rowBuilder = $rowBuilder;
        $this->apiConfig = $apiConfig;
        $this->discountBuilder = $discountBuilder;
        $this->customerBuilder = $customerBuilder;
        $this->shippingFeeBuilder = $shippingFeeBuilder;
        $this->store = $store;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     */
    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order = $payment->getOrder();
        $apiConfig = $this->apiConfig;
        $sveaOrder = WebPay::createOrder($apiConfig);

        $items = $this->getAllVisibleItems($order);
        $address = $order->getBillingAddress();

        $sveaOrder->setOrderDate(date('c'));
        $this->addOrderItems($sveaOrder, $items);

        $nationalIdNumber = $payment->getAdditionalInformation(Configuration::SSN_KEY);

        $customerData = [
            'address' => $address,
            'national_id_number' => $nationalIdNumber,
        ];

        $customer = $this->customerBuilder->build($customerData);
        $sveaOrder->addCustomerDetails($customer);

        $sveaOrder->setCountryCode($address->getCountryId());
        $sveaOrder->setClientOrderNumber($order->getIncrementId());

        $this->addShippingFee($order, $sveaOrder);

        $currency = $this->store->getCurrentCurrencyCode();
        $method = $payment->getAdditionalInformation(Configuration::SELECTED_HOSTED_METHOD_KEY);

        $request = $sveaOrder->setCurrency($currency)
            ->usePaymentMethod($method);

        $requestTotals = $request->calculateRequestValues();
        $grandTotal = $order->getGrandTotal();
        $requestTotal = $requestTotals['amount'] / 100;

        $adjustmentAmount = floatval(number_format(($requestGrandTotal - $grandTotal), 4));
        if (abs($adjustmentAmount) > 0) {
            $this->addAdjustment($sveaOrder, $adjustmentAmount);

            $request = $sveaOrder->setCurrency($currency)
                ->usePaymentMethod($method);
        }

        return $request;
    }
}
