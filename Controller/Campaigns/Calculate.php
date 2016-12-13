<?php
/**
 * Calculate campaigns price controller
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Controller\Campaigns;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use \Webbhuset\SveaWebpay\Model\Campaign;

/**
 * fetch campaigns
 *
 * @package Webbhuset\SveaWebpay\Controller\Address
 */
class Calculate extends \Magento\Framework\App\Action\Action
{

    protected $resultJsonFactory;
    protected $campaign;
    protected $formKeyValidator;
    protected $context;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Campaign $campaign
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        JsonFactory $resultJsonFactory,
        Campaign $campaign
    ) {
        parent::__construct($context);

        $this->context = $context;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->campaign = $campaign;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $request = $this->getRequest();

        $formKeyIsValid = $this->context->getFormKeyValidator()->validate($request);
        $isPost = $this->getRequest()->isPost();
        $amount = $request->getPostValue('amount');

        if (!$formKeyIsValid || !$isPost || !$amount) {
            return $this->errorResponse(__('Svea error 6001 : An error occured when calculating price'));
        }

        $pricePerMonth = $this->campaign->getPricePerMonth($amount);

        $response = $this->resultJsonFactory->create();
        $response->setData($pricePerMonth);

        return $response;
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
