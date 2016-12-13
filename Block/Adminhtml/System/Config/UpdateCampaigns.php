<?php
/**
 * Update campaigns block
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class UpdateCampaigns
    extends Field
{
    protected $_template = 'Webbhuset_SveaWebpay::system/config/update_campaigns.phtml';

    protected $element;

    /**
     * UpdateCampaigns constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->element = $element;

        return $this->toHtml();
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        if (!$this->getTemplate()) {
            return '';
        }

        return $this->fetchView($this->getTemplateFile());
    }

    /**
     * Get knockout component config
     *
     * @return array
     */
    public function getComponentConfig()
    {
        $url                    = $this->getFetchCampaignsUrl();
        $currentCampaigns       = $this->getCurrentCampaigns();
        $elementName            = $this->getElementName();

        $config = [
            'getCampaignsUrl'   => $url,
            'campaigns'         => $currentCampaigns,
            'configName'        => $elementName,
        ];

        return $config;
    }

    /**
     * Get url to fetch campaigns
     *
     * @return string
     */
    protected function getFetchCampaignsUrl()
    {
        return $this->getUrl('sveawebpay/campaigns/get');
    }

    /**
     * Get current campaigns
     *
     * @return array|mixed
     */
    protected function getCurrentCampaigns() {
        if (!$this->element) {
            return [];
        }

        $value = json_decode($this->element->getValue());

        if ($value) {
            return $value;
        }
        return [];
    }

    /**
     * Get element name
     *
     * @return string
     */
    protected function getElementName()
    {
        if (!$this->element) {
            return '';
        }

        return $this->element->getName();
    }
}
