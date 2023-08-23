<?php

namespace PennyBlackWoo\Api;

use PennyBlack\Model\Customer;
use PennyBlack\Model\Order;
use PennyBlackWoo\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class OrderAdaptor
{
    const VALID_HISTORY_ORDER_STATES = [
        'wc-processing',
        'wc-on-hold',
        'wc-completed',
        'wc-refunded',
    ];

    public function adaptCustomer(\WC_Order $order): Customer
    {
        if ($order->get_billing_email()) {
            $allCustomerOrders = $this->getCustomerOrderHistory($order);
            $orderCount = count($allCustomerOrders);
            $totalSpent = array_sum(array_map(function ($pastOrder) {
                return $pastOrder->get_total();
            }, $allCustomerOrders));
        } else {
            // Just in case we can't get a billing email, assume it's the first order
            $orderCount = 1;
            $totalSpent = $order->get_total();
        }
        $totalSpent = round((float)$totalSpent, 2);

        $customer = new Customer();
        $customer->setVendorCustomerId((string) $order->get_customer_id())
            ->setFirstName($order->get_shipping_first_name() ?: $order->get_billing_first_name())
            ->setLastName($order->get_shipping_last_name() ?: $order->get_billing_first_name())
            ->setEmail($order->get_billing_email())
            ->setTotalOrders($orderCount)
            ->setTotalSpent($totalSpent);

        // TODO: Additional fields we'll want to add by configuration in future
        // $customer->setMarketingConsent(null)
        // $customer->setTags([])

        // This won't be of much use since many/most customers will be guest customers.
        // If we want to segment on language we'll want it for all users and it will come from a multilingual plugin
        // $customer->setLanguage($this->mapLocaleToLanguage($user->get_locale()));

        return $customer;
    }

    public function adaptOrder(\WC_Order $wc_order): Order
    {
        $skus = [];
        $titles = [];
        /** @var \WC_Order_Item $item */
        foreach ($wc_order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $skus[] = $product->get_sku();
                $titles[] = $product->get_title();
            }
        }

        $order = new Order();
        $order->setId((string) $wc_order->get_id())
            ->setNumber($wc_order->get_order_number())
            ->setCreatedAt($wc_order->get_date_created() ? $wc_order->get_date_created() : new \DateTime())
            ->setTotalAmount((float) $wc_order->get_total())
            ->setTotalItems((int) $wc_order->get_item_count())
            ->setCurrency((string) $wc_order->get_currency())
            ->setBillingCountry((string) $wc_order->get_billing_country())
            ->setBillingCity((string) $wc_order->get_billing_city())
            ->setBillingPostcode((string) $wc_order->get_billing_postcode())
            ->setShippingCountry((string) $wc_order->get_shipping_country())
            ->setShippingCity((string) $wc_order->get_shipping_city())
            ->setShippingPostcode((string) $wc_order->get_shipping_postcode())
            ->setSkus($skus)
            ->setProductTitles($titles)
            ->setPromoCodes($wc_order->get_coupon_codes());
            // TODO: unknown by default, these need plugins
            //  ->isSubscriptionReorder(false)

        $giftMessageMetaField = \WC_Admin_Settings::get_option(Settings::FIELD_GIFT_MESSAGE_META_FIELD);

        if ($giftMessageMetaField) {
            $order->setGiftMessage(get_post_meta($wc_order->get_id(), $giftMessageMetaField, true));
        }

        return $order;
    }

    private function mapLocaleToLanguage(string $locale)
    {
        $parts = explode('_', $locale);
        if (count($parts) === 2) {
            return $parts[0];
        }
        return '';
    }

    /**
     * Many orders will be by guest customers.
     * The only way of accurately consolidating a list of past orders
     * is to use the billing email.
     */
    private function getCustomerOrderHistory(\WC_Order $order)
    {
        $history = wc_get_orders([
            'billing_email' => $order->get_billing_email(),
            'post_status' => self::VALID_HISTORY_ORDER_STATES,
            'post_type' => 'shop_order',
        ]);

        // Ensure the current order is in the history, in case the above doesn't
        // find it due to custom order status flows
        foreach ($history as $pastOrder) {
            if ($pastOrder->get_id() === $order->get_id()) {
                return $history;
            }
        }
        $history[] = $order;

        return $history;
    }
}
