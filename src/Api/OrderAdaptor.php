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

    public function adaptCustomer(\WC_Order $order): Customer
    {
        /** @var \WC_User $user */
        $user = $order->get_user();

        $allCustomerOrders = $this->getCustomerOrderHistory($order->get_customer_id());

        $customer = new Customer();
        $customer->setVendorCustomerId((string) $order->get_customer_id())
            ->setFirstName($order->get_shipping_first_name() ?: ($user ? $user->first_name : ''))
            ->setLastName($order->get_shipping_last_name() ?: ($user ? $user->last_name : ''))
            ->setEmail($order->get_billing_email())
            ->setLanguage($this->mapLocaleToLanguage($user->get_locale()))
            // TODO: Unknown by default, these need plugins
            // ->setMarketingConsent(null)
            // ->setTags([])
            ->setTotalOrders(count($allCustomerOrders))
            ->setTotalSpent(array_sum(array_map(function ($order) {
                return $order->get_total();
            }, $allCustomerOrders)));

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
            //  ->setGiftMessage('')

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

    private function getCustomerOrderHistory(int $customerId)
    {
        return wc_get_orders([
            'customer_id' => $customerId,
            'post_status' => self::VALID_HISTORY_ORDER_STATES,
            'post_type' => 'shop_order',
        ]);
    }
}
