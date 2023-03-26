<?php

namespace PennyBlackWoo\Api;

use PennyBlack\Model\Customer;
use PennyBlack\Model\Order;

defined( 'ABSPATH' ) || exit;

class OrderAdaptor
{
    const VALID_HISTORY_ORDER_STATES = [
        'wc-processing',
        'wc-on-hold',
        'wc-completed',
        'wc-refunded',
    ];

    const ORIGIN_WOOCOMMERCE = 'woocommerce';

    public function adaptOrder(\WC_Order $order)
    {
        /** @var \WC_User $user */
        $user = $order->get_user();

        $allCustomerOrders = $this->getCustomerOrderHistory($order->get_customer_id());

        $skus = [];
        $titles = [];
        /** @var \WC_Order_Item $item */
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $skus[] = $product->get_sku();
                $titles[] = $product->get_title();
            }
        }

        return [
            "customer" => [
                "vendor_customer_id" => (int) $order->get_customer_id(),
                "first_name" =>  $order->get_shipping_first_name() ?: ($user ? $user->first_name : ''),
                "last_name" => $order->get_shipping_last_name() ?: ($user ? $user->last_name : ''),
                "email" => $order->get_billing_email(),
                "language" => $this->mapLocaleToLanguage($user->get_locale()),
                "marketing_consent" => null, // TODO: Unknown by default, needs a plugin
                "total_orders" => count($allCustomerOrders),
                "tags" => [], // TODO: Tagging isn't native, it's provided by plugins
                "total_spent" => array_sum(array_map(function ($order) {
                    return $order->get_total();
                }, $allCustomerOrders)),
            ],
            "order" => [
                "id" => (string) $order->get_id(),
                "number" => $order->get_order_number(),
                "created_at" => $order->get_date_created() ? $order->get_date_created()->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                "total_amount" => (float) $order->get_total(),
                "total_items" => (int) $order->get_item_count(),
                "billing_country" => (string) $order->get_billing_country(),
                "billing_postcode" => (string) $order->get_billing_postcode(),
                "billing_city" => (string) $order->get_billing_city(),
                "shipping_country" => (string) $order->get_shipping_country(),
                "shipping_postcode" => (string) $order->get_shipping_postcode(),
                "shipping_city" => (string) $order->get_shipping_city(),
                "currency" => (string) $order->get_currency(),
                "gift_message" => '', // TODO: Gift message isn't native, it's provided by plugins.
                "skus" => $skus,
                "product_titles" => $titles,
                "promo_codes" => $order->get_coupon_codes(),
                "is_subscription_reorder" => false,
            ],
            "origin" => self::ORIGIN_WOOCOMMERCE,
        ];
    }

    private function mapLocaleToLanguage(string $locale)
    {
        $parts = explode('_', $locale);
        if (count($parts) === 2) {
            return $parts[0];
        }
        return '';
    }

    private function getCustomerOrderHistory(int $customerId)
    {
        return wc_get_orders([
            'customer_id' => $customerId,
            'post_status' => self::VALID_HISTORY_ORDER_STATES,
            'post_type' => 'shop_order',
        ]);
    }
}