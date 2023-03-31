<?php
/**
 * @author Penny Black <engineers@pennyblack.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PennyBlackWoo\Hook;

use PennyBlackWoo\Admin\Settings;
use PennyBlackWoo\Api\OrderTransmitter;
use PennyBlackWoo\Factory\OrderTransmitterFactory;
use PennyBlackWoo\Factory\PrintRequesterFactory;

defined( 'ABSPATH' ) || exit;

class OrderHook
{
    public static function initialize()
    {
        /*
         * Ensure every order is sent.
         * Orders can arrive at different statuses, and we want to ensure we get the details as early as possible
         * (but only once!)
         * It's possible that with this slightly heavy-handed approach we will be lacking some order information
         * such as billing address, but we should have enough to do most segmentation and be able to print orders
         * instantly upon processing.
         * The trade-off is better this way than not having order info or not having a PDF generated ready for
         * the fulfilment flow
         */
        add_action('woocommerce_order_status_pending', __CLASS__ . '::transmitToPennyBlack', 1);
        add_action('woocommerce_order_status_on-hold', __CLASS__ . '::transmitToPennyBlack', 1);
        add_action('woocommerce_order_status_processing', __CLASS__ . '::transmitToPennyBlack', 1);
    }

    public static function transmitToPennyBlack($orderId)
    {
        $isTransmitEnabled = \WC_Admin_Settings::get_option(Settings::FIELD_ENABLE_TRANSMIT);

        if (!$isTransmitEnabled || $isTransmitEnabled === 'no') {
            return;
        }

        try {
            $order = wc_get_order($orderId);

            $orderTransmitter = OrderTransmitterFactory::create();
            if ($orderTransmitter->hasAlreadyBeenTransmitted($order)) {
                return;
            }
            $orderTransmitter->transmitOrder($order);
        } catch (\Exception $e) {
            // Catch any possible additional errors. We're not expecting any, but we won't break checkout/order flow
            update_post_meta(
                $order->get_id(),
                OrderTransmitter::STATUS_META_KEY,
                "ERROR - unexpected, please contact Penny Black support. Details: " . $e->getMessage()
            );
        }
    }

    public static function triggerPrintRequest($orderId)
    {
        // TODO:

        $order = wc_get_order($orderId);

        $printRequester = PrintRequesterFactory::create();
        $printRequester->print($order);
    }
}
