<?php
/**
 * Svea Command
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Command;

use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Psr\Log\LoggerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Webbhuset\SveaWebpay\Gateway\Http\SveaClient\SveaClientInterface;
use Webbhuset\SveaWebpay\Gateway\Request\OrderActionBuilderInterface;
use Webbhuset\SveaWebpay\Gateway\Validator\Response\ResponseValidator;
use Webbhuset\SveaWebpay\Gateway\Validator\Request\RequestValidatorInterface;

class SveaCommand implements CommandInterface
{
    protected $adapter;
    protected $validator;
    protected $resultFactory;
    protected $handler;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var SveaClientInterface
     */
    protected $client;
    /**
     * @var OrderActionBuilderInterface
     */
    protected $builder;
    /**
     * @var RequestValidatorInterface
     */
    protected $requestValidator;

    /**
     * SveaCommand constructor.
     * @param ArrayResultFactory $resultFactory
     * @param ResponseValidator $responseValidator
     * @param OrderActionBuilderInterface $builder
     * @param LoggerInterface $logger
     * @param SveaClientInterface $client
     * @param HandlerInterface|null $handler
     * @param RequestValidatorInterface|null $requestValidator
     */
    public function __construct(
        ArrayResultFactory $resultFactory,
        ResponseValidator $responseValidator,
        OrderActionBuilderInterface $builder,
        LoggerInterface $logger,
        SveaClientInterface $client,
        HandlerInterface $handler = null,
        RequestValidatorInterface $requestValidator = null
    ) {
        $this->resultFactory    = $resultFactory;
        $this->validator        = $responseValidator;
        $this->logger           = $logger;
        $this->client           = $client;
        $this->handler          = $handler;
        $this->builder          = $builder;
        $this->requestValidator = $requestValidator;
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);
        $payment = $paymentDO->getPayment();

        $request = $this->builder->build($payment);

        if ($this->requestValidator) {
            $result = $this->requestValidator->validate($request, $payment);

            if (!$result->isValid()) {
                $this->logExceptions($result->getFailsDescription());

                throw new CommandException(
                    __($result->getFailsDescription()[0])
                );
            }
        }

        $response = [];
        try {
            $response['svea'] = $this->client->placeRequest($request);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());

            throw new CommandException(
                __($e->getMessage())
            );
        }

        $result = $this->validator->validate(
            array_merge($commandSubject, ['response' => $response])
        );
        if (!$result->isValid()) {
            $code = $response['svea']->resultcode;
            $errorMessage = "Error_{$code}";

            throw new CommandException(
                __($errorMessage, $code)
            );
        }

        if ($this->handler) {
            $this->handler->handle(
                $commandSubject,
                $response
            );
        }
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
