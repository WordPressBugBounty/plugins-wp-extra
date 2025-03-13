<?php
/**
 * Plugin name:         WP EXtra
 * Plugin URI:          https://wordpress.org/plugins/wp-extra/
 * Description:         ❤ This is a simple and perfect tool to use as your website’s functionality plugin. Awesome !!!
 * Version:             8.6.2
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
//Check Pro
if (is_dir(__DIR__ . '/src/Pro')) {
    include_once __DIR__ . '/src/Pro/Pro.php';
}

define( 'WPEX_VERSION', '8.6.2' );
define( 'WPEX_FILE', __FILE__ );
define( 'WPEX_DIR', __DIR__ );

if (! class_exists('\WPVNTeam\WPSettings\WPSettings')) {
    include_once __DIR__ . '/wp-settings/wp-settings.php';
}
require_once __DIR__ . '/vendor/autoload.php';

if (is_dir(__DIR__ . '/src/Pro')) {
    include_once __DIR__ . '/src/Pro/constants.php';
    new Pro\WPEXtraPro;
}

new Language;
new Settings;
new WPEXtra;

if ( is_admin() ) {
	new Core;
}
