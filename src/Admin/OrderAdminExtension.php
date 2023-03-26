<?php
/**
 * @author Penny Black <engineers@pennyblack.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PennyBlackWoo\Admin;

use PennyBlackWoo\Api\OrderTransmitter;
use PennyBlackWoo\PennyBlackPlugin;

defined( 'ABSPATH' ) || exit;

class OrderAdminExtension
{
    public static function initialize()
    {
        // Add actions and display to View/Edit Order page
        add_action('woocommerce_order_actions', [OrderAdminExtension::class, 'addOrderMetaBoxActions']);
        add_action('woocommerce_order_action_penny_black_send', [OrderAdminExtension::class, 'sendOrder']);
        add_action('woocommerce_order_action_penny_black_print', [OrderAdminExtension::class, 'printOrder']);
        add_action('add_meta_boxes', __CLASS__ . '::addOrderMetaBox');

        // TODO: Add batch printing button to Orders List page
    }

    public static function addOrderMetaBoxActions($actions)
    {
        $order = self::getCurrentOrder();

        if ($order) {
            $status = $order->get_meta(\PennyBlackWoo\Api\OrderTransmitter::STATUS_META_KEY);

            if (!$status || substr($status, 0, 5) === 'ERROR') {
                $actions['penny_black_send'] = 'Send to Penny Black';
            } else {
                $orderAdminEnabled = \WC_Admin_Settings::get_option(Settings::FIELD_ENABLE_ORDER_EXTENSIONS);

                if ($orderAdminEnabled && $orderAdminEnabled !== 'no') {
                    $actions['penny_black_print'] = 'Print via Penny Black';
                }
            }
        }

        return $actions;
    }

    public static function sendOrder($order)
    {
        $orderTransmitter = new OrderTransmitter();
        $orderTransmitter->transmitOrder($order);
    }

    public static function printOrder($order)
    {
        $printRequester = new PrintRequester();
        $printRequester->print($order);

        // TODO: Add an admin notice to show the result?
    }

    public static function addOrderMetaBox()
    {
        add_meta_box('pb_status_box', 'Penny Black Status', __CLASS__ . '::renderOrderMetaBox', 'shop_order', 'side', 'core' );
    }

    public static function renderOrderMetaBox()
    {
        $order = self::getCurrentOrder();

        $template_path = PennyBlackPlugin::getViewsPath() . 'html-order-metabox.php';
        include_once $template_path;
    }

    public static function getCurrentOrder()
    {
        global $post;

        $order_id = isset($post->ID) ? intval($post->ID) : '';

        if (!is_null($order_id)) {
            return wc_get_order($order_id);
        }
    }
}

