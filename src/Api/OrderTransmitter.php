<?php

namespace PennyBlackWoo\Api;

use PennyBlack\Exception\PennyBlackException;

defined( 'ABSPATH' ) || exit;

class OrderTransmitter
{
    const STATUS_META_KEY = '_penny_black_transmit_status';

    private $orderAdaptor;
    private $clientFactory;

    public function __construct()
    {
        $this->orderAdaptor = new OrderAdaptor();
        $this->clientFactory = new ClientFactory();
    }

    public function transmitOrder(\WC_Order $order)
    {
        $now = new \DateTime();
        try {
            $api = $this->clientFactory->getApiClient();
            $api->sendOrder($this->orderAdaptor->adaptOrder($order));
            update_post_meta($order->get_id(), self::STATUS_META_KEY, "Transmitted at " . $now->format('d/m/Y H:i:s'));
        } catch (PennyBlackException $e) {
            update_post_meta($order->get_id(), self::STATUS_META_KEY, "ERROR transmitting at " . $now->format('d/m/Y H:i:s') . ". Details: " . $e->getMessage());
        }
    }
}