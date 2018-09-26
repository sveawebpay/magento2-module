<?php
/**
 * Response form block
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model\Hosted;

use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\Store;
use Webbhuset\SveaWebpay\Gateway\Request\Hosted\NewOrderBuilder;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;

class Form
{
    /**
     * @var NewOrderBuilder
     */
    protected $builder;
    /**
     * @var Configuration
     */
    protected $apiConfig;
    /**
     * @var Store
     */
    protected $store;

    protected $locale;

    /**
     * Form constructor.
     * @param NewOrderBuilder $builder
     * @param Configuration $apiConfig
     * @param Store $store
     * @param Resolver $locale
     */
    public function __construct(
        NewOrderBuilder $builder,
        Configuration $apiConfig,
        Resolver $locale
    ) {
        $this->builder      = $builder;
        $this->apiConfig    = $apiConfig;
        $this->locale       = $locale;
    }


    public function getForm($order)
    {
        $request = $this->builder->build($order->getPayment());
        $returnUrl = $this->apiConfig->getHostedReturnUrl();
        $callbackUrl = $this->apiConfig->getHostedCallbackUrl($order->getId());
        $language = $this->getISO639language();

        $form = $request->setReturnUrl($returnUrl)
            ->setCallbackUrl($callbackUrl)
            ->setCardPageLanguage($language)
            ->getPaymentForm();

        return $form;
    }

    protected function getISO639language()
    {
        $locale = $this->locale->getLocale();
        $language = substr($locale, 0, 2);

        return $language;
    }
}
