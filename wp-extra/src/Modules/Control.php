<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class Control extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'no_backend',
		'application_passwords',
		'themeplugin_edits',
		'core_updates',
	];
    
    public function no_backend() {
        add_action( 'admin_init', [$this, 'redirect_non_admin_user']);
    }

	public function redirect_non_admin_user() {
        if ( defined( 'DOING_AJAX' ) || current_user_can( 'administrator' ) ) {
            return;
        }

        $user = wp_get_current_user();
        $allowed_roles = (array) Settings::get_option('no_backend');

        if ( array_intersect( $allowed_roles, $user->roles ) ) {
            wp_redirect( site_url() );
            exit;
        }
    }
    
	public function application_passwords(){
        add_filter( 'wp_is_application_passwords_available', '__return_false' );
    }
    
	public function themeplugin_edits(){
        if(defined('DISALLOW_FILE_EDIT') || defined('DISALLOW_FILE_MODS')) {
            add_action('admin_notices', [$this, 'notice_disallow_file']);
        } else {
            define( 'DISALLOW_FILE_EDIT', true );
            define( 'DISALLOW_FILE_MODS', true );
        }
    }

	public function notice_disallow_file() {
		$message = sprintf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s %s</p></div>',
			__('Warning:'),
			__('DISALLOW_FILE_EDIT / DISALLOW_FILE_MODS'),
			__('is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'wp-extra')
		);
		echo $message;
	}
    
	public function core_updates(){
        if(defined('WP_AUTO_UPDATE_CORE')) {
            add_action('admin_notices', [$this, 'notice_auto_update_core']);
        } else {
            define( 'WP_AUTO_UPDATE_CORE', false );
        }
    }

	public function notice_auto_update_core() {
		$message = sprintf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s %s</p></div>',
			__('Warning:'),
			__('WP_AUTO_UPDATE_CORE'),
			__('is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'wp-extra')
		);
		echo $message;
	}

}