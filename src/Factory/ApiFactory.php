<?php

namespace PennyBlackWoo\Factory;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Client;
use PennyBlack\Api;
use PennyBlackWoo\Admin\Settings;
use PennyBlackWoo\Exception\MissingApiConfigException;
use PennyBlackWoo\PennyBlackPlugin;

defined( 'ABSPATH' ) || exit;

class ApiFactory
{
    /**
     * @return Api
     * @throws MissingApiConfigException
     */
    public static function create(): Api
    {
        $apiKey = \WC_Admin_Settings::get_option(Settings::FIELD_API_KEY);
        $isTest = \WC_Admin_Settings::get_option(Settings::FIELD_ENVIRONMENT) === Settings::ENVIRONMENT_TEST;

        if ($apiKey === null) {
            throw new MissingApiConfigException('Cannot instantiate PennyBlack API because API key is not set.');
        }

        $httpClient = new Client();
        $streamFactory = new HttpFactory();
        $requestFactory = new HttpFactory();

        return new Api($httpClient, $requestFactory, $streamFactory, $apiKey, $isTest, PennyBlackPlugin::getVersion());
    }
}
