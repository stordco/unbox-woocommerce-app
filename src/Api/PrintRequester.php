<?php

namespace PennyBlackWoo\Api;

use PennyBlack\Api;

class PrintRequester
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function print(\WC_Order $order)
    {
        // TODO: More here...
        $this->api->requestPrint($order->get_number());
    }
}