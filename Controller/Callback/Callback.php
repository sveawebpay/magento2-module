<?php
/**
 * Callback controller on asynchronous calls from Svea
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Controller\Callback;

use Svea\WebPay\Response\SveaResponse;

class Callback extends \Magento\Framework\App\Action\Action
{
    protected $apiConfig;
    protected $orderRepository;
    protected $hostedResponse;
    protected $resultJsonFactory;
    protected $orderHelper;
    protected $orderRepo;
    protected $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Webbhuset\SveaWebpay\Model\Hosted\Response $hostedResponse
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Webbhuset\SveaWebpay\Helper\Order $orderHelper
     * @param \Magento\Sales\Model\OrderRepository $orderRepo
     * @param \Webbhuset\SveaWebpay\Model\Config\Api\Configuration $apiConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webbhuset\SveaWebpay\Model\Hosted\Response $hostedResponse,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Webbhuset\SveaWebpay\Helper\Order $orderHelper,
        \Magento\Sales\Model\OrderRepository $orderRepo,
        \Webbhuset\SveaWebpay\Model\Config\Api\Configuration $apiConfig,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->hostedResponse           = $hostedResponse;
        $this->resultJsonFactory        = $resultJsonFactory;
        $this->orderHelper              = $orderHelper;
        $this->orderRepo                = $orderRepo;
        $this->apiConfig                = $apiConfig;
        $this->logger                   = $logger;

        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id');

        try {
            $order = $this->orderRepo->get($id);
            $countryCode = $this->orderHelper->getCountryCode($order);

            $sveaResponse = (new SveaResponse($_REQUEST, $countryCode, $this->apiConfig))
                ->getResponse();

        } catch (\Exception $e) {
            $this->logger->err("Svea callback error");
            $this->logger->err($e->getMessage());

            return $this->sendResponse(400, "Order not found");
        }

        if ($this->hasError($sveaResponse)) {
            try {
                $order->cancel();
                $this->orderRepo->save($order);
            } catch (\Exception $e) {
                $this->logger->err($orderId . $e->getMessage());
                $this->messageManager->addErrorMessage($e->getMessage());

                return $this->sendResponse(400, "Order cancel error");
            }

            $orderId = $order->getId();
            $resultCode = property_exists($sveaResponse, "resultcode") ? (int) $sveaResponse->resultcode : $sveaResponse;
            $this->logger->info("Order {$orderId} cancelled, responseCode {$resultCode}");

            return $this->sendResponse(200, "Order cancelled");
        }

        return $this->sendResponse(200, "Nothing to do with order");
    }

    /**
     * Check if response has error
     *
     * @param \Svea\WebPay\HostedService\HostedResponse\HostedPaymentResponse $response
     * @return boolean
     */
    protected function hasError($response)
    {
        if (!property_exists($response, "resultcode") || (int) $response->resultcode > 0) {
            return true;
        }

        return false;
    }

    protected function sendResponse($code, $msg)
    {
        $response = $this->resultJsonFactory->create();
        $response->setHttpResponseCode($code)
            ->setData(array('message' => $msg));

        return $response;
    }
}
