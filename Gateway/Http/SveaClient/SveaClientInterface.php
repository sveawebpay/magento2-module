<?php
/**
 * Client Interface
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Http\SveaClient;

interface SveaClientInterface
{
    /**
     * Place request
     * @param $request
     * @return mixed
     */
    public function placeRequest($request);
}
