<?php
/**
 * Hosted cancel client
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Http\SveaClient\Hosted;

use Webbhuset\SveaWebpay\Gateway\Http\SveaClient\SveaClientInterface;

class CancelClient implements SveaClientInterface
{
    public function placeRequest($request)
    {
        return $request->cancelCardOrder()->doRequest();
    }
}
