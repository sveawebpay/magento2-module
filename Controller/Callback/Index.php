<?php
/**
 * Callback controller when returning from Svea
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Controller\Callback;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Payment\Gateway\Command\CommandException;
use Webbhuset\SveaWebpay\Model\Hosted\Response;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $checkoutSession;
    protected $orderRepository;
    protected $hostedResponse;

    /**
     * Index constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param Response $hostedResponse
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Response $hostedResponse
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->hostedResponse = $hostedResponse;

        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order->getPayment();

        try {
            $this->hostedResponse->handle($payment);
        } catch (CommandException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->_redirect('checkout/cart/index');
        }

        return $this->_redirect('checkout/onepage/success');
    }
}
