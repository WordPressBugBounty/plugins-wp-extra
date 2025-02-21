<?php
/**
 * Plugin name:         WP EXtra
 * Plugin URI:          https://wordpress.org/plugins/wp-extra/
 * Description:         ❤ This is a simple and perfect tool to use as your website’s functionality plugin. Awesome !!!
 * Version:             8.6.1
 * Requires at least:   6.2
 * Requires PHP:        7.4
 * Author:              TienCOP
 * Author URI:          https://wpvnteam.com
 * Text Domain:         wp-extra
 * Domain Path:         /languages
 * License:             GPLv2
 */

namespace WPEXtra;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'WPEX_VERSION', '8.6.1' );
define( 'WPEX_FILE', __FILE__ );
define( 'WPEX_DIR', __DIR__ );
if (! class_exists('\WPVNTeam\WPSettings\WPSettings')) {
    include_once __DIR__ . '/wp-settings/wp-settings.php';
}
require_once __DIR__ . '/vendor/autoload.php';

new Language;
new Settings;
new WPEXtra;

if ( is_admin() ) {
	new Core;
}
