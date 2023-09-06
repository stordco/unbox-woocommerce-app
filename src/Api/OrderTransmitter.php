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
        try {
            $this->saveStatus($order->get_id(), 'Transmitting... ' . $this->getNowTime());
            $this->api->sendOrder(
                $this->orderAdaptor->adaptOrder($order),
                $this->orderAdaptor->adaptCustomer($order),
                self::ORIGIN_WOOCOMMERCE
            );
            $this->saveStatus($order->get_id(), 'Transmitted at ' . $this->getNowTime());
        } catch (PennyBlackException $e) {
            $this->saveStatus(
                $order->get_id(),
                'ERROR transmitting at ' . $this->getNowTime() . '. Details: ' . $e->getMessage()
            );
            return $e->getMessage();
        }
        return '';
    }

    /**
     * returns true if currently transmitting or has been transmitted successfully
     */
    public function hasAlreadyBeenTransmitted(\WC_Order $order)
    {
        $status = get_post_meta($order->get_id(), self::STATUS_META_KEY, true);

        return substr($status, 0, 9) === 'Transmitt';
    }

    private function saveStatus($orderId, $status)
    {
        update_post_meta($orderId, self::STATUS_META_KEY, $status);
    }

    private function getNowTime()
    {
        $now = new \DateTime();
        return $now->format('d/m/Y H:i:s');
    }
}
