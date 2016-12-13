<?php
/**
 * Handle hosted payment response
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Hosted;


use Magento\Payment\Gateway\Command\CommandException;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;
use Webbhuset\SveaWebpay\Gateway\Validator\Response\ResponseValidator;
use Svea\WebPay\Response\SveaResponse;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;


class Response
{
    /**
     * @var \Webbhuset\SveaWebpay\Model\Config\Api\Configuration
     */
    protected $apiConfig;
    /**
     * @var \Webbhuset\SveaWebpay\Helper\Order
     */
    protected $helper;

    protected $responseHandler;
    /**
     * @var \Webbhuset\SveaWebpay\Gateway\Validator\ResponseValidator
     */
    protected $validator;
    /**
     * @var PaymentDataObjectFactoryInterface
     */
    protected $paymentDataObjectFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var OrderRepository
     */
    protected $orderRepository;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var OrderSender
     */
    protected $orderSender;

    /**
     * Response constructor.
     * @param \Webbhuset\SveaWebpay\Model\Config\Api\Configuration $apiConfig
     * @param \Webbhuset\SveaWebpay\Helper\Order $helper
     * @param ResponseValidator $validator
     * @param PaymentDataObjectFactoryInterface $paymentDataObjectFactory
     * @param LoggerInterface $logger
     * @param OrderRepository $orderRepository
     * @param Session $session
     * @param OrderSender $orderSender
     */
    public function __construct(
        \Webbhuset\SveaWebpay\Model\Config\Api\Configuration $apiConfig,
        \Webbhuset\SveaWebpay\Helper\Order $helper,
        ResponseValidator $validator,
        PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        LoggerInterface $logger,
        OrderRepository $orderRepository,
        Session $session,
        OrderSender $orderSender

    ) {
        $this->apiConfig = $apiConfig;
        $this->helper = $helper;
        $this->validator = $validator;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->session = $session;
        $this->orderSender = $orderSender;
    }


    public function createResponse($payment)
    {
        $order = $payment->getOrder();
        $countryCode = $this->helper->getCountryCode($order);

        $sveaResponse = (new SveaResponse($_REQUEST, $countryCode, $this->apiConfig))
            ->getResponse();

        $response = ['svea' => $sveaResponse];

        return $response;
    }


    public function handle($payment)
    {
        $response = $this->createResponse($payment);

        $validationSubject = [
            'payment' => $this->paymentDataObjectFactory->create($payment),
            'response' => $response,
        ];

        $result = $this->validator->validate(
            $validationSubject
        );

        if (!$result->isValid()) {
            $this->cancelOrder($payment);

            $this->logExceptions($result->getFailsDescription());
            throw new CommandException(
                __($response['svea']->errormessage)
            );
        }

        $this->confirmOrder($payment, $response['svea']);
    }


    public function cancelOrder($payment)
    {
        $order = $payment->getOrder();
        $order->cancel();
        $this->orderRepository->save($order);

        $this->session->restoreQuote();

        $this->logger->info("Canceled order {$order->getId()}");
    }

    /**
     *
     * @param $payment
     * @param $sveaResponse
     */
    public function confirmOrder($payment, $sveaResponse)
    {
        $order = $payment->getOrder();

        $transactionId = $sveaResponse->transactionId;

        $payment->setTransactionId("svea-{$transactionId}")->setIsTransactionClosed(false);
        $method = $payment->getMethod();

        $captureOnConfirm = $method == 'svea_card' ? $this->apiConfig->getShouldCaptureOnConfirmation() : true;

        if ($captureOnConfirm) {
            $payment->registerCaptureNotification($order->getBaseTotalDue());
            $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING);
        } else {
            $payment->authorize(false, $order->getBaseTotalDue());
        }

        $responseAsArray = (array) $sveaResponse;
        $payment->setAdditionalInformation('svea_response', $responseAsArray)
            ->save();

        $forcedShipmentWithInvoice = !$captureOnConfirm;

        $order->setExtOrderId($transactionId)
            ->setForcedShipmentWithInvoice($forcedShipmentWithInvoice);

        $this->orderRepository->save($order);

        // Send confirmation email
        $this->orderSender->send($order);


        $this->logger->info("Confirmed order {$order->getId()}");
    }


    /**
     * @param Phrase[] $fails
     * @return void
     */
    protected function logExceptions(array $fails)
    {
        foreach ($fails as $failPhrase) {
            $this->logger->critical((string) $failPhrase);
        }
    }

}
