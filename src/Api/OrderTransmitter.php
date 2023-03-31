<?php

namespace PennyBlackWoo\Api;

use PennyBlack\Api;
use PennyBlack\Exception\PennyBlackException;

defined( 'ABSPATH' ) || exit;

class OrderTransmitter
{
    const STATUS_META_KEY = '_penny_black_transmit_status';
    const ORIGIN_WOOCOMMERCE = 'woocommerce';

    private OrderAdaptor $orderAdaptor;
    private Api $api;

    public function __construct(Api $api, OrderAdaptor $orderAdaptor)
    {
        $this->api = $api;
        $this->orderAdaptor = $orderAdaptor;
    }

    public function transmitOrder(\WC_Order $order)
    {
        $now = new \DateTime();
        try {
            $this->api->sendOrder(
                $this->orderAdaptor->adaptOrder($order),
                $this->orderAdaptor->adaptCustomer($order),
                self::ORIGIN_WOOCOMMERCE
            );
            update_post_meta(
                $order->get_id(),
                self::STATUS_META_KEY,
                "Transmitted at " . $now->format('d/m/Y H:i:s')
            );
        } catch (PennyBlackException $e) {
            update_post_meta(
                $order->get_id(),
                self::STATUS_META_KEY,
                "ERROR transmitting at " . $now->format('d/m/Y H:i:s') . ". Details: " . $e->getMessage()
            );
        }
    }
}
