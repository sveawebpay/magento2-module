<?php
/**
 * "simple" client, when you just have to doRequest
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Http\SveaClient;

class SimpleClient implements SveaClientInterface
{
    /**
     * @param $request
     * @return mixed
     */
    public function placeRequest($request)
    {
        return $request->doRequest();
    }
}
