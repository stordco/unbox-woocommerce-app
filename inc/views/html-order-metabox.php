<?php
if (!defined('ABSPATH')) {
    exit;
}
if (!isset($order)) {
    return;
}

$status = $order->get_meta(\PennyBlackWoo\Api\OrderTransmitter::STATUS_META_KEY);

if (!$status) {
    echo "<h4>Not yet transmitted</h4>";
} elseif (substr($status, 0, 5) === 'ERROR') {
    echo "<h4 style='color:red;'>" . esc_html($status) . "</h4>";
} else {
    echo "<h4 style='color:green;'>" . esc_html($status) . "</h4>";
}
