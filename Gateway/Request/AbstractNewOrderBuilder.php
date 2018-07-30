<?php
/**
 * Abstract new order builder
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Request;


use Webbhuset\SveaWebpay\Gateway\Request\CustomerBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\DiscountOrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\OrderRowBuilder;
use Webbhuset\SveaWebpay\Gateway\Request\ShippingFeeBuilder;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;
use Svea\WebPay\WebPayItem;

class AbstractNewOrderBuilder implements \Webbhuset\SveaWebpay\Gateway\Request\OrderActionBuilderInterface
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
        Configuration $apiConfig
    ) {
        $this->rowBuilder = $rowBuilder;
        $this->apiConfig = $apiConfig;
        $this->discountBuilder = $discountBuilder;
        $this->customerBuilder = $customerBuilder;
        $this->shippingFeeBuilder = $shippingFeeBuilder;
    }

    public function build(\Magento\Sales\Model\Order\Payment $payment)
    {

    }

    public function addOrderItems($order, $items)
    {
        foreach ($items as $item) {
            if ($item->getProductType() == 'bundle') {
                continue;
            }

            $orderRow = $this->rowBuilder->build($item);
            $order->addOrderRow($orderRow);

            if ((float) $item->getDiscountAmount()) {
                $discountRow = $this->discountBuilder->build($item);
                $order->addDiscount($discountRow);
            }
        }

        return $order;
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

    /**
     * Add a small adjustment so the svea sum matches the magento sum
     *
     * @param $sveaOrder
     * @param $amount
     */
    protected function addAdjustment($sveaOrder, $amount)
    {
        $sveaOrder->addDiscount( WebPayItem::fixedDiscount()
            ->setAmountIncVat($amount)    // a negative discount shows up as a positive adjustment
            ->setDescription('Adjustment')
            ->setVatPercent(0)
        );
    }

    /**
     * Since order->getAllVisibleItems() is not working
     * before the order is saved, we do this here
     *
     * @param [type] $order
     * @return void
     */
    protected function getAllVisibleItems(
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $items = [];
        foreach ($order->getItems() as $item) {
            $parentQuoteItemId = $item->getParentItem()
                ? $item->getParentItem()->getQuoteItemId()
                : null;

            if (!$item->isDeleted() && !$parentQuoteItemId) {
                $items[] = $item;
            }
        }
        return $items;
    }
}
