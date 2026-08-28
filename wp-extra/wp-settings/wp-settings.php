<?php
/**
 * Plugin Name:         WP Settings
 * Plugin URI:          https://github.com/wordpressvn/wp-settings
 * Description:         Handy wrapper to make creating WordPress settings pages a breeze.
 * Version:             2.8.3
 * Author:              WordPress Vietnam Team
 * Author URI:          https://wpvnteam.com
 * License:             GPLv3
 */

defined( 'ABSPATH' ) || exit;

( function () {
	$current_ver   = '2.8.3';
	$autoload_file = __DIR__ . '/vendor/autoload.php';

	if ( isset( $GLOBALS['wp_settings_loaded_version'] ) && version_compare( $GLOBALS['wp_settings_loaded_version'], $current_ver, '>=' ) ) {
		return;
	}

	$GLOBALS['wp_settings_loaded_version'] = $current_ver;

	if ( file_exists( $autoload_file ) ) {
		$loader = require_once $autoload_file;
		// Prepend autoloader to the top of SPL queue to prioritize this latest version
		if ( is_object( $loader ) && method_exists( $loader, 'register' ) ) {
			$loader->register( true );
		}

		// Early boot persistent hooks
		if ( class_exists( 'WPVNTeam\WPSettings\Options\Restore' ) ) {
			\WPVNTeam\WPSettings\Options\Restore::boot();
		}
	}
} )();