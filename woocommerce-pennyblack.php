<?php
/**
 * @author Penny Black <engineers@pennyblack.io>
 * @date 17/03/2023
 *
 * @wordpress-plugin
 * Plugin Name: Penny Black Integration for WooCommerce
 * Plugin Uri: https://github.com/pennyblack-io/woocommerce-pennyblack
 * Description: Configure your integration with Penny Black to transmit orders and trigger your personalised prints.
 * Author: Penny Black
 * Author URI: https://github.com/pennyblack-io/
 * WC requires at least: 7.1
 * WC tested up to: 7.3.0
 * Requires PHP: 7.4
 * Version: 1.0.0
 */

use PennyBlackWoo\PennyBlackPlugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once( trailingslashit(__DIR__) . 'vendor/autoload.php' );

$pbPlugin = new PennyBlackPlugin();

add_action('init', [$pbPlugin, "initialize"]);
