<?php
/**
 * Hosted refund client
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Http\SveaClient\Hosted;

use Webbhuset\SveaWebpay\Gateway\Http\SveaClient\SveaClientInterface;

class RefundClient implements SveaClientInterface
{
    public function placeRequest($request)
    {
        // creditCardOrderRows is the same
        return $request->creditDirectBankOrderRows()->doRequest();
    }
}
