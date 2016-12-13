<?php
/**
 * Paymentplan refund client
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Http\SveaClient\Paymentplan;

use Webbhuset\SveaWebpay\Gateway\Http\SveaClient\SveaClientInterface;

class RefundClient implements SveaClientInterface
{
    public function placeRequest($request)
    {
        return $request->creditPaymentplanOrderRows()->doRequest();
    }
}
