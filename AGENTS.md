# Agent guidance

WordPress plugin that connects WooCommerce stores to Unbox (Penny Black): transmit orders for segmentation/print PDFs, and optionally trigger single/batch prints from the Woo order admin. Distributed on WordPress.org as slug **`penny-black`** (`https://wordpress.org/plugins/penny-black/`). GitHub: `stordco/unbox-woocommerce-app`. Default branch: `main`.

This is **not** a Node/Python Lambda app. There is no `package.json`, Taskfile, PHPUnit suite, or CI test job.

## Stack and commands

- PHP **>= 7.4** (`composer.json` `config.platform.php` is `7.4.0`). Keep new code 7.4-compatible.
- WooCommerce headers in `penny-black.php`: WC requires at least 7.1, WC tested up to 8.2.2; WordPress `readme.txt` tested up to 6.8.
- Composer PSR-4: `PennyBlackWoo\` → `src/`. Runtime SDK: `stordco/unbox-php-sdk` (`PennyBlack\Api`, `PennyBlack\Model\*`) from private git `git@github.com:stordco/unbox-php-sdk.git` (SSH required). Also `guzzlehttp/guzzle`.
- Install: `composer install` (dev) or `composer install --no-dev` for a release zip. Plugin folder on disk **must** be named `penny-black` (settings link is `penny-black/penny-black.php`).
- No lint/test scripts are defined in this repo.

## Layout

- `penny-black.php` — WP plugin bootstrap (`WPINC` guard, autoload, `init` → `PennyBlackPlugin::initialize`)
- `src/Admin/Settings.php` — WooCommerce settings tab `settings_penny_black`
- `src/Hook/OrderHook.php` — transmit on configurable order statuses
- `src/Admin/OrderAdminExtension.php` — order actions, metabox, bulk print
- `src/Api/` — `OrderAdaptor`, `OrderTransmitter`, `PrintRequester`
- `src/Factory/` — construct SDK/`Api` (do not new-up `PennyBlack\Api` in hooks)
- `inc/views/` — admin HTML (metabox)
- `readme.txt` — WordPress.org listing metadata (not GitHub README)

`.distignore` is what 10up deploy sends to SVN: **include `vendor`**, exclude `.github`. `.gitignore` ignores `vendor`.

## Conventions (from this repo)

- `defined('ABSPATH') || exit;` (or `WPINC`) at the top of PHP files.
- WooCommerce APIs: settings tabs, `wc_get_order`, `woocommerce_order_actions`, `WC_Admin_Settings`.
- Transmit is **fail-safe**: `OrderHook` swallows exceptions so checkout/status transitions are not broken. Status is stored in post meta `_penny_black_transmit_status`. `hasAlreadyBeenTransmitted` treats any value starting with `Transmitt` as done (covers `Transmitting...` and `Transmitted at`).
- Guest customers are common: order history is keyed by **billing email**, not WP user id. History statuses: `wc-processing`, `wc-on-hold`, `wc-completed`, `wc-refunded`.
- Gift message is **not** a fixed key; merchants set `pb_gift_message_meta_field` in settings.
- Saving settings calls `$api->installStore($hostname)` with the site host; invalid API key disables transmit.
- Order admin print UI is gated by `pb_enable_order_extensions` (self-fulfilment). Transmission is a separate checkbox.

## Release and pitfalls

- Bump version in **three** files together: `penny-black.php`, `readme.txt` (`Stable tag`), `composer.json`. If the SDK version changes, update `composer.json` and run `composer update`.
- GitHub **tag** → `.github/workflows/deploy-on-tag-to-svn.yml` (`composer install --no-dev`, then 10up WordPress plugin deploy, SVN slug `penny-black`).
- Push to `main` that only changes `readme.txt` and/or `.wordpress-org/` → asset/readme update on WP.org (`IGNORE_OTHER_FILES: true`).
- Admin order UI is classic post-type (`shop_order` metabox, `bulk_actions-edit-shop_order`, `$post->ID`). HPOS-only hooks are not used today; changing that is a compatibility project, not a drive-by.
- `OrderAdaptor` last-name fallback is shipping last name then **billing first name** (existing behaviour).
- Origin string sent to the API is `woocommerce`. Environment is Live vs Test (`pb_environment`), not Shopify/BigCommerce staging apps.
