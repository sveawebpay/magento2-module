<?php
/**
 * Paymentplan new order request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Paymentplan;


use Webbhuset\SveaWebpay\Gateway\Request\CustomerBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\DiscountOrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\OrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\ShippingFeeBuilder;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPay;
use Webbhuset\SveaWebpay\Gateway\Request\AbstractNewOrderBuilder;

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
    /**
     * @var \Webbhuset\SveaWebpay\Gateway\Request\CampaignDataBuilder
     */
    protected $campaignDataBuilder;

    public function __construct(
        OrderRowBuilder $rowBuilder,
        DiscountOrderRowBuilder $discountBuilder,
        CustomerBuilder $customerBuilder,
        ShippingFeeBuilder $shippingFeeBuilder,
        CampaignDataBuilder $campaignDataBuilder,
        Configuration $apiConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\SveaWebpay\Helper\Order $orderHelper
    ) {
        $this->rowBuilder = $rowBuilder;
        $this->apiConfig = $apiConfig;
        $this->discountBuilder = $discountBuilder;
        $this->customerBuilder = $customerBuilder;
        $this->shippingFeeBuilder = $shippingFeeBuilder;
        $this->campaignDataBuilder = $campaignDataBuilder;
        $this->orderHelper = $orderHelper;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {
        $order = $payment->getOrder();
        $apiConfig = $this->apiConfig;
        $sveaOrder = WebPay::createOrder($apiConfig);

        $items = $this->getAllVisibleItems($order);
        $address = $order->getBillingAddress();
        $nationalIdNumber = $payment->getAdditionalInformation(Configuration::SSN_KEY);

        $sveaOrder->setOrderDate(date('c'));
        $this->addOrderItems($sveaOrder, $items);

        $customerData = [
            'address' => $address,
            'national_id_number' => $nationalIdNumber,
        ];

        $customer = $this->customerBuilder->build($customerData);
        $sveaOrder->addCustomerDetails($customer);

        $sveaOrder->setCountryCode($address->getCountryId());
        $sveaOrder->setClientOrderNumber($order->getIncrementId());

        $this->addShippingFee($order, $sveaOrder);

        $campaignCode = $this->campaignDataBuilder->build($payment);
        $request = $sveaOrder->usePaymentPlanPayment($campaignCode);

        $requestTotals = $request->getRequestTotals();
        $requestGrandTotal = $requestTotals['total_incvat'];
        $grandTotal = $payment->getOrder()->getGrandTotal();

        $adjustmentAmount = $requestGrandTotal - $grandTotal;
        if (abs($adjustmentAmount) > 0) {
            $this->addAdjustment($sveaOrder, $adjustmentAmount);

            $request = $sveaOrder->usePaymentPlanPayment($campaignCode);
        }

        return $request;
    }

    protected function addShippingFee($order, $sveaOrder)
    {
        $shippingData = [
            'fee_inc_vat'   => $order->getShippingInclTax(),
            'fee_ex_vat'    => $order->getShippingAmount(),
        ];

        $feeItem = $this->shippingFeeBuilder->build($shippingData);
        $sveaOrder->addFee($feeItem);
    }
}
