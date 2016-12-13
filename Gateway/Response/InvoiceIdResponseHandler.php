<?php
/**
 * Handle invoice id in response
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Webbhuset\SveaWebpay\Helper\Order as OrderHelper;

class InvoiceIdResponseHandler implements HandlerInterface
{
    protected $helper;

    /**
     * InvoiceIdResponseHandler constructor.
     *
     * @param OrderHelper $helper
     */
    public function __construct(
        OrderHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $invoice = $order->getInvoiceCollection()->getLastItem();

        // Set invoice id on invoice so we can refund later
        $invoice->setSveaInvoiceId($response['svea']->invoiceId);
    }
}
