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
        // TODO: Should we choose an earlier status?
        // When status is set to processing is usually after payment has been taken.
        // This should be the optimal time, although potentially it could come earlier before payment.
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
            $orderTransmitter->transmitOrder($order);
        } catch (\Exception $e) {
            // Catch any possible additional errors. We're not expecting any, but we won't break checkout/order flow
            update_post_meta($order->get_id(), OrderTransmitter::STATUS_META_KEY, "ERROR - unexpected, please contact Penny Black support. Details: " . $e->getMessage());
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