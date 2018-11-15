<?php
/**
 * Get campaigns controller
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Controller\Adminhtml\Campaigns;
use Magento\Framework\Controller\Result\JsonFactory;
use \Webbhuset\SveaWebpay\Model\Campaign;

/**
 * fetch campaigns
 *
 * @package Webbhuset\SveaWebpay\Controller\Address
 */
class Get extends \Magento\Backend\App\Action
{

    protected $resultJsonFactory;
    protected $campaign;

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

        $this->resultJsonFactory = $resultJsonFactory;
        $this->campaign = $campaign;
    }

    /**
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        try {
            $campaigns = $this->campaign->fetchCampaignsFromSvea($params);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }

        $campaignsJson = json_encode($campaigns);

        $response = $this->resultJsonFactory->create();
        $response->setData($campaignsJson);

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
