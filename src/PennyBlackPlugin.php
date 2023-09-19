<?php
/**
 * @author Penny Black <engineers@pennyblack.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PennyBlackWoo;

use PennyBlackWoo\Admin\Settings;
use PennyBlackWoo\Admin\OrderAdminExtension;
use PennyBlackWoo\Hook\OrderHook;

defined( 'ABSPATH' ) || exit;

class PennyBlackPlugin
{
    private static string $VERSION;

    public function initialize()
    {
        $settings = new Settings();
        $settings->register();

        $orderHook = new OrderHook();
        $orderHook->initialize();

        if (is_admin()) {
            add_filter('plugin_action_links_penny-black/penny-black.php', [$this, "createSettingsLink"]);
            $orderAdminExtension = new OrderAdminExtension();
            $orderAdminExtension->initialize();
        }
    }

    /**
     * A settings link on the left-hand side of the plugins list entry,
     * to take you to the WC settings tab
     */
    public static function createSettingsLink($links)
    {
        $url = admin_url('admin.php?page=wc-settings&tab=settings_penny_black');
        $settingsLink = "<a href='$url'>" . __( 'Settings' ) . '</a>';
        array_unshift($links, $settingsLink);

        return $links;
    }

    public static function getVersion(): string
    {
        if (!isset(self::$VERSION)) {
            $pluginData = get_file_data(__DIR__ . '/../penny-black.php', array('Version' => 'Version'), false);
            self::$VERSION = $pluginData['Version'];
        }
        return self::$VERSION;
    }
}
