<?php
/**
 * Plugin name:         WP EXtra
 * Plugin URI:          https://wordpress.org/plugins/wp-extra/
 * Description:         ❤ This is a simple and perfect tool to use as your website’s functionality plugin. Awesome !!!
 * Version:             8.5.5
 * Requires at least:   6.2
 * Requires PHP:        7.4
 * Author:              TienCOP
 * Author URI:          https://wpvnteam.com
 * Text Domain:         wp-extra
 * Domain Path:         /languages
 * License:             GPLv2
 */
 
defined('ABSPATH') || die;
if ( ! defined( 'WPEX_VERSION' ) ) {
    define('WPEX_VERSION', '8.5.5');
}
if ( ! defined( 'WPEX_BASE' ) ) {
    define( 'WPEX_BASE', dirname( plugin_basename( __FILE__ ) ) );
}
if ( ! defined( 'WPEX_FILE' ) ) {
    define('WPEX_FILE', __FILE__);
}
if ( ! defined( 'WPEX_DIR' ) ) {
    define('WPEX_DIR', __DIR__);
}

use WPEXtra\WPEXtra;
if (! class_exists(WPEXtra::class)) {
    if (is_file(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
    }
}

/**
 * Plugin instance.
 *
 * @return WPEXtra
 */
function run_wp_extra()
{
    return WPEXtra::instance();
}
run_wp_extra();

