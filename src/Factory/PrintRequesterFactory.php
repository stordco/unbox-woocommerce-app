<?php

namespace PennyBlackWoo\Factory;

use PennyBlackWoo\Api\PrintRequester;

class PrintRequesterFactory
{
    public static function create(): PrintRequester
    {
        return new PrintRequester(ApiFactory::create());
    }
}
