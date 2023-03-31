<?php

namespace PennyBlackWoo\Factory;

use PennyBlackWoo\Api\OrderAdaptor;
use PennyBlackWoo\Api\OrderTransmitter;

class OrderTransmitterFactory
{
    public static function create(): OrderTransmitter
    {
        return new OrderTransmitter(ApiFactory::create(), new OrderAdaptor());
    }
}
