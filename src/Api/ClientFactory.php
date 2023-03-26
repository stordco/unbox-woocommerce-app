<?php

namespace PennyBlackWoo\Api;

use PennyBlack\Api;
use PennyBlack\Client\PennyBlackClient;
use PennyBlackWoo\Admin\Settings;
use PennyBlackWoo\Exception\MissingApiConfigException;

defined( 'ABSPATH' ) || exit;

class ClientFactory
{
    /**
     * @return Api
     * @throws MissingApiConfigException
     */
    public function getApiClient()
    {
        $apiKey = \WC_Admin_Settings::get_option(Settings::FIELD_API_KEY);
        $sandboxMode = \WC_Admin_Settings::get_option(Settings::FIELD_ENVIRONMENT) === Settings::ENVIRONMENT_TEST;

        if ($apiKey === null) {
            throw new MissingApiConfigException('Cannot instantiate PennyBlack API because API key is not set.');
        }

        return new Api(new PennyBlackClient($apiKey, $sandboxMode));
    }
}