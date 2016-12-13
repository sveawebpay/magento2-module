<?php
/**
 * Svea redirect controller for bank/card payments
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Controller\Hosted;

use Magento\Checkout\Model\Session;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Webbhuset\SveaWebpay\Model\Hosted\Form;

class Redirect extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $checkoutSession;
    protected $context;
    protected $hostedForm;

    /**
     * Redirect constructor.
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     * @param Form $hostedForm
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        Form $hostedForm
    ) {
        $this->context = $context;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->hostedForm = $hostedForm;

        parent::__construct($context);
    }

    /**
     * Get hosted form that can be posted to get to Svea Bank/Card
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $request = $this->getRequest();

        $formKeyIsValid = $this->context->getFormKeyValidator()->validate($request);
        $isPost = $this->getRequest()->isPost();

        if (!$formKeyIsValid || !$isPost) {
            return $this->errorResponse(__('Svea error 9001 : An error occured'));
        }

        $order = $this->checkoutSession->getLastRealOrder();

        $form = $this->hostedForm->getForm($order);

        $result = $this->resultJsonFactory->create();
        $result->setData(['form' => $form->rawFields]);

        return $result;
    }

    /**
     * Return json error message
     *
     * @param $errorMessage
     * @return \Magento\Framework\Controller\Result\Json
     */
    protected function errorResponse($errorMessage)
    {
        $response = $this->resultJsonFactory->create();
        $response->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST)
            ->setData(array('message' => $errorMessage));

        return $response;
    }
}
