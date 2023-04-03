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

    public function print(\WC_Order $order): string
    {
        return $this->api->requestPrint($order->get_order_number());
    }

    public function printBatch(array $orders): string
    {
        $response = $this->api->requestBatchPrint($orders);

        if (isset($response['message'])) {
            return $response['message'];
        }
        return print_r($response['message'], true);
    }
}
