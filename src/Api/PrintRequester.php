<?php

namespace PennyBlackWoo\Api;

class PrintRequester
{
    private $clientFactory;

    public function __construct()
    {
        $this->clientFactory = new ClientFactory();
    }

    public function print(\WC_Order $order)
    {
        $api = $this->clientFactory->getApiClient();
        $api->requestPrint($order->get_number());
    }
}