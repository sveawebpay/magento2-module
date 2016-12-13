<?php
/**
 * Get address controller
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Controller\Address;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Webbhuset\SveaWebpay\Model\Address;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;

/**
 * Get address based on SSN
 *
 * @package Webbhuset\SveaWebpay\Controller\Address
 */
class Get extends \Magento\Framework\App\Action\Action
{

    protected $resultJsonFactory;
    protected $cart;
    protected $address;
    protected $checkoutSession;
    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     * @param Address $address
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        Address $address
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->address = $address;
        $this->context = $context;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Retrieve address from SVEA based on ssn.
     *
     * This action takes the query parameters `ssn`, `countryId`, `customerType` and 'methodCode'.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();

        $formKeyIsValid = $this->context->getFormKeyValidator()->validate($request);

        if (!$formKeyIsValid) {
            return $this->errorResponse('Error 1001 : An error occured');
        }

        $quote = $this->checkoutSession->getQuote();
        $payment = $quote->getPayment();

        $requiredParams = [
            'paymentMethodCode' => __('Svea error 2011 : Method code not provided.'),
            'countryId'         => __('Svea error 2012 : Country code not provided.'),
            'ssn'               => __('Svea error 2013 : Ssn not provided.'),
            'customerType'      => __('Svea error 2014 : Customer type not provided'),
        ];

        $addressData = [];

        foreach ($requiredParams as $key => $errorMessage) {
            $param = trim($this->getRequest()->getParam($key));
            if (!$param) {
                return $this->errorResponse($errorMessage);
            }

            $addressData[$key] = $param;
        }

        try {
            $addressResponse = $this->address->getAddress($addressData);
        } catch (\Exception $e) {
            return $this->errorResponse(__("An error occured when making the request"));
        }


        if (!$addressResponse) {
            return $this->errorResponse(__('Svea error 2001 : Address not found.'));
        }

        $this->setAddressOnPayment($payment, $addressResponse);

        $response = $this->resultJsonFactory->create();
        $response->setData($addressResponse);

        return $response;
    }

    /**
     * Set svea address on payment
     *
     * @param $payment
     * @param $addressResponse
     */
    protected function setAddressOnPayment($payment, $addressResponse)
    {
        $payment->setAdditionalInformation(Configuration::ADDRESS_RESPONSE_KEY, $addressResponse)
            ->save();
    }

    /**
     * Create json error response
     * @param $errorMessage
     * @param int $code
     * @return \Magento\Framework\Controller\Result\Json
     */
    protected function errorResponse($errorMessage, $code = \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST)
    {
        $response = $this->resultJsonFactory->create();
        $response->setHttpResponseCode($code)
            ->setData(array('message' => $errorMessage));

        return $response;
    }
}
