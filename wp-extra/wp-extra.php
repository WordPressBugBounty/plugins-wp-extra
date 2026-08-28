<?php
/**
 * Plugin name:         WP EXtra
 * Plugin URI:          https://wordpress.org/plugins/wp-extra/
 * Description:         This is a simple and perfect tool to use as your website’s functionality plugin. Awesome !!!
 * Version:             8.7.1
 * Requires at least:   6.8
 * Requires PHP:        8.0
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

define( 'WPEX_VERSION', '8.7.1' );
define( 'WPEX_FILE', __FILE__ );
define( 'WPEX_DIR', __DIR__ );
define( 'WPEX_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( '\WPVNTeam\WPSettings\WPSettings' ) ) {
    include_once __DIR__ . '/wp-settings/wp-settings.php';
}

new Language;
new WPEXtra;

if ( is_admin() || wp_doing_ajax() ) {
    new Settings;
    if ( is_admin() ) {
        new Core;
    }
}
