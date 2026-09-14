# Stord Unbox WooCommerce Plugin

This plugin integrates WooCommerce with Stord Unbox to provide the data for, and optionally trigger, personalised prints.

The WordPress.org slug remains `penny-black` so existing installs keep updating.

## Installation

### Quick Install

The easiest way to install this plugin is to search for it within your WordPress system, or download the latest from the [WordPress Plugin Directory](https://wordpress.org/plugins/penny-black/).

### Manual build and install

- Download the latest release from the [releases page](https://github.com/stordco/unbox-woocommerce-app/releases)
- Extract the zip file and rename the folder to `penny-black`, removing the version number component.
- Inside the folder run `composer install --no-dev`.
- Create a zip file from the `penny-black` folder.
- Upload the folder to your WordPress site, either manually to the plugins directory, or via the admin interface on the plugins page.
- Install the plugin from the WordPress admin panel.
- Follow the settings link on the plugins page to configure the plugin:
  - Set the API key from your Unbox account settings
  - Enable order transmission
  - If you fulfil your own orders through WooCommerce then enable the order admin extensions.

## Development

### Releasing a new version of the plugin

- If a new version of the Unbox PHP SDK is being used remember to update its version number in `composer.json`, and run `composer update`.
- Bump the plugin version number in the following 3 files:
  - `penny-black.php`
  - `readme.txt`
  - `composer.json`
- Create a new release on GitHub, with the version number as the tag, and the version number as the title.
- On publishing a new tag a GitHub action will automatically deploy the updated version to the WordPress plugin repository. See our page [here](https://wordpress.org/plugins/penny-black/).

### Releasing updated assets (screenshots, banners logos) to WP.org

There is a GitHub action listening for changes to these files. Any commit to `main` that contains _only_ changes to `readme.txt` and/or `.wordpress-org/` folders will trigger a push to update them on WP.org in their respective folders
