<?php
/**
 * Invoice cancel client
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Gateway\Http\SveaClient\Invoice;

use Webbhuset\SveaWebpay\Gateway\Http\SveaClient\SveaClientInterface;

class CancelClient implements SveaClientInterface
{
    public function placeRequest($request)
    {
        return $request->cancelInvoiceOrder()->doRequest();
    }
}
