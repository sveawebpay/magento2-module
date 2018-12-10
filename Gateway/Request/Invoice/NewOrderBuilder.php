<?php
/**
 * Invoice new order request builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request\Invoice;

use Webbhuset\SveaWebpay\Gateway\Request\CustomerBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\DiscountOrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\InvoiceFeeRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\OrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\Paymentplan\CampaignDataBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\ShippingFeeBuilder;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPay;
use Webbhuset\SveaWebpay\Gateway\Request\AbstractNewOrderBuilder;

class NewOrderBuilder extends AbstractNewOrderBuilder
{
    protected $rowBuilder;
    protected $apiConfig;
    protected $discountBuilder;
    protected $customerBuilder;
    protected $shippingFeeBuilder;
    protected $campaignDataBuilder;
    protected $invoiceFeeRowBuilder;

    /**
     * NewOrderBuilder constructor.
     * @param OrderRowBuilder $rowBuilder
     * @param DiscountOrderRowBuilder $discountBuilder
     * @param CustomerBuilder $customerBuilder
     * @param ShippingFeeBuilder $shippingFeeBuilder
     * @param InvoiceFeeRowBuilder $invoiceFeeRowBuilder
     * @param CampaignDataBuilder $campaignDataBuilder
     * @param Configuration $apiConfig
     */
    public function __construct(
        OrderRowBuilder $rowBuilder,
        DiscountOrderRowBuilder $discountBuilder,
        CustomerBuilder $customerBuilder,
        ShippingFeeBuilder $shippingFeeBuilder,
        InvoiceFeeRowBuilder $invoiceFeeRowBuilder,
        CampaignDataBuilder $campaignDataBuilder,
        Configuration $apiConfig
    ) {
        $this->rowBuilder = $rowBuilder;
        $this->apiConfig = $apiConfig;
        $this->discountBuilder = $discountBuilder;
        $this->customerBuilder = $customerBuilder;
        $this->shippingFeeBuilder = $shippingFeeBuilder;
        $this->invoiceFeeRowBuilder = $invoiceFeeRowBuilder;
        $this->campaignDataBuilder = $campaignDataBuilder;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return \Svea\WebPay\WebService\Payment\InvoicePayment
     */
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
            'address'               => $address,
            'national_id_number'    => $nationalIdNumber,
        ];

        $customer = $this->customerBuilder->build($customerData);
        $sveaOrder->addCustomerDetails($customer);

        $sveaOrder->setCountryCode($address->getCountryId());
        $sveaOrder->setClientOrderNumber($order->getIncrementId());

        $this->addShippingFee($order, $sveaOrder);

        if ((float) $order->getHandlingFeeAmount()) {
            $invoiceFee = $this->invoiceFeeRowBuilder->build($order);
            $sveaOrder->addFee($invoiceFee);
        }

        $request = $sveaOrder->useInvoicePayment();

        $requestTotals = $request->getRequestTotals();
        $requestGrandTotal = $requestTotals['total_incvat'];
        $grandTotal = $payment->getOrder()->getGrandTotal();

        $adjustmentAmount = number_format($requestGrandTotal, 4) - number_format($grandTotal, 4);
        if (abs($adjustmentAmount) > 0) {
            $this->addAdjustment($sveaOrder, $adjustmentAmount);

            $request = $sveaOrder->useInvoicePayment();
        }

        return $request;
    }
}
