<?php
/**
 * @author Penny Black <engineers@pennyblack.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PennyBlackWoo\Admin;

use PennyBlack\Exception\PennyBlackException;
use PennyBlackWoo\Api\OrderTransmitter;
use PennyBlackWoo\Factory\OrderTransmitterFactory;
use PennyBlackWoo\Factory\PrintRequesterFactory;
use PennyBlackWoo\PennyBlackPlugin;

defined( 'ABSPATH' ) || exit;

class OrderAdminExtension
{
    private string $message;
    private string $messageType;

    public function initialize()
    {
        // Add actions and display to View/Edit Order page
        add_action('woocommerce_order_actions', [$this, 'addOrderMetaBoxActions']);
        add_action('woocommerce_order_action_penny_black_send', [$this, 'sendOrder']);
        add_action('woocommerce_order_action_penny_black_print', [$this, 'printOrder']);
        add_action('add_meta_boxes', [$this, 'addOrderMetaBox']);

        // Add bulk order admin actions
        add_filter('bulk_actions-edit-shop_order', [$this, 'addBatchPrintBulkOption'], 20, 1);
        add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handleBatchPrintRequest'], 10, 3);

        // Shared admin notices (must be passed as query params on page redirect then pull and displayed)
        add_action('admin_notices', [$this, 'addNotification']);
    }

    public function addOrderMetaBoxActions($actions)
    {
        $order = $this->getCurrentOrder();

        if ($order) {
            $status = $order->get_meta(OrderTransmitter::STATUS_META_KEY);

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

    public function addOrderMetaBox()
    {
        add_meta_box('pb_status_box', 'Penny Black Status', [$this, 'renderOrderMetaBox'], 'shop_order', 'side', 'core');
    }

    public function renderOrderMetaBox()
    {
        $order = $this->getCurrentOrder();

        include_once __DIR__ . '/../../inc/views/html-order-metabox.php';
    }

    public function addBatchPrintBulkOption($actions)
    {
        $orderAdminEnabled = \WC_Admin_Settings::get_option(Settings::FIELD_ENABLE_ORDER_EXTENSIONS);

        if ($orderAdminEnabled && $orderAdminEnabled !== 'no') {
            $actions['penny_black_batch_print'] = 'Print via Penny Black';
        }

        return $actions;
    }

    public function sendOrder($order)
    {
        $orderTransmitter = OrderTransmitterFactory::create();
        if ($this->message = $orderTransmitter->transmitOrder($order)) {
            $this->messageType = 'error';
            add_filter('redirect_post_location', [$this, 'addNotificationQueryVars'], 99, 2);
        }
    }

    public function printOrder($order)
    {
        $printRequester = PrintRequesterFactory::create();
        try {
            $this->message = "Penny Black: " . $printRequester->print($order);
            $this->messageType = 'success';
            add_filter('redirect_post_location', [$this, 'addNotificationQueryVars'], 99, 2);
        } catch (PennyBlackException $e) {
            $this->message = $e->getMessage();
            $this->messageType = 'error';
            add_filter('redirect_post_location', [$this, 'addNotificationQueryVars'], 99, 2);
        }
    }

    public function handleBatchPrintRequest($redirectTo, $action, $postIds)
    {
        if ($action !== 'penny_black_batch_print') {
            return $redirectTo; // Exit
        }

        $orderNumbers = [];
        foreach ($postIds as $postId) {
            $order = wc_get_order($postId);
            $orderNumbers[] = $order->get_order_number();
        }

        if (!count($orderNumbers)) {
            return esc_url(add_query_arg([
                'pb_msg_type' => 'warning',
                'pb_msg' => 'Please select some orders'
            ], $redirectTo));
        }

        $printRequester = PrintRequesterFactory::create();
        try {
            $message = "Penny Black: " . $printRequester->printBatch($orderNumbers);
        } catch (PennyBlackException $e) {
            return esc_url(add_query_arg([
                'pb_msg_type' => 'error',
                'pb_msg' => $e->getMessage()
            ], $redirectTo));
        }

        return esc_url(add_query_arg([
            'pb_msg_type' => 'success',
            'pb_msg' => $message
        ], $redirectTo));
    }

    public function addNotificationQueryVars($location)
    {
        return esc_url(add_query_arg(['pb_msg_type' => $this->messageType, 'pb_msg' => $this->message], $location));
    }

    public function addNotification()
    {
        if (!isset($_GET['pb_msg_type']) || !isset($_GET['pb_msg'])) {
            return;
        }

        $messageType = sanitize_text_field($_GET['pb_msg_type']);
        $message = sanitize_text_field($_GET['pb_msg']);

        $class = 'notice is-dismissible notice-' . $messageType;

        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }

    public function getCurrentOrder()
    {
        global $post;

        $order_id = isset($post->ID) ? intval($post->ID) : '';

        if ($order_id) {
            return wc_get_order($order_id);
        }
    }
}
