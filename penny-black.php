<?php
/**
 * @author Stord <engineering@stord.com>
 * @date 24/09/2025
 *
 * @wordpress-plugin
 * Plugin Name: Stord Unbox integration for WooCommerce
 * Plugin Uri: https://wordpress.org/plugins/penny-black/
 * Description: Integrate with Unbox to share data to power personalised printing.
 * Author: Stord
 * Author URI: https://github.com/stordco/
 * WC requires at least: 7.1
 * WC tested up to: 8.2.2
 * Requires PHP: 7.4
 * Version: 1.3.1
 */

use PennyBlackWoo\PennyBlackPlugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once( trailingslashit(__DIR__) . 'vendor/autoload.php' );

$pbPlugin = new PennyBlackPlugin();

add_action('init', [$pbPlugin, "initialize"]);
