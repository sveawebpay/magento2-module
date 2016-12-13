<?php
/**
 * Response validator
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Validator\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class ResponseValidator extends AbstractValidator
{
    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $paymentDO = SubjectReader::readPayment($validationSubject);

        $response = SubjectReader::readResponse($validationSubject);
        $orderIsValid = $this->isSuccessfulTransaction($response) && !$this->hasError($response);

        if ($orderIsValid) {
            return $this->createResult(
                true,
                []
            );
        } else {
            return $this->createResult(
                false,
                [$response['svea']->errormessage]
            );
        }
    }

    protected function hasError($response)
    {
        if ((int) $response['svea']->resultcode > 0) {
            return true;
        }

        return false;
    }

    /**
     * Check flags on response to see if transaction is successful
     *
     * @param  array
     *
     * @return boolean
     */
    protected function isSuccessfulTransaction(array $response)
    {
        if (!isset($response['svea']->accepted)) {
            return false;
        }
        return $response['svea']->accepted;
    }
}
