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

        $sveaOrderId = $response->sveaOrderId;
        $order->setExtOrderId($sveaOrderId);

        $payment->setTransactionId($sveaOrderId)
            ->setIsTransactionClosed(false);
    }
}
