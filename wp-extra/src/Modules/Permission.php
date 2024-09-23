<?php

namespace WPEXtra\Modules;

use WPEXtra\Core\ClassEXtra;

class Permission {
    public function __construct() {
        add_action( 'admin_bar_menu', [$this, 'removeNodes'], 999);
        
        if (wp_extra_get_option('wp_adminbar') && wp_extra_get_option('wp_adminbar_visible') && in_array('site-admin',  wp_extra_get_option('wp_adminbar_visible'))) {
            add_action( 'admin_enqueue_scripts',  [$this, 'wpAdminbar' ]);
        }
        if (wp_extra_get_option('wp_adminbar') && wp_extra_get_option('wp_adminbar_visible') && in_array('homepage',  wp_extra_get_option('wp_adminbar_visible'))) {
            add_action('after_setup_theme', [$this, 'wpAdminbar_filter']);
        }
        add_action( 'admin_bar_menu', [$this, 'vnex_adminbar_menu'], 150 );
        if(wp_extra_get_option('adminmenu_list')) {
            add_action('admin_menu', [$this, 'wpex_admin_menu_roles'], 9999);
        }
        if(wp_extra_get_option('adminplugin_list')) {
            add_action('pre_current_active_plugins', [$this, 'wpex_hide_plugins']);
        }
		if(wp_extra_get_option('restricted_backend')) {
			add_action( 'admin_init', [$this, 'redirect_non_admin_user']);
		}
		if(wp_extra_get_option('scrolltotop')) {
            add_action( 'admin_footer',  [$this, 'scroll_to_top' ]);
		}
		if(wp_extra_get_option('application_passwords')) {
            add_filter( 'wp_is_application_passwords_available', '__return_false' );
		}
		
		if(wp_extra_get_option('themeplugin_edits')) {
			if(defined('DISALLOW_FILE_EDIT') || defined('DISALLOW_FILE_MODS')) {
				add_action('admin_notices', [$this, 'notice_disallow_file']);
			} else {
				define( 'DISALLOW_FILE_EDIT', true );
				define( 'DISALLOW_FILE_MODS', true );
			}
		}
		if(wp_extra_get_option('core_updates')) {
			if(defined('WP_AUTO_UPDATE_CORE')) {
				add_action('admin_notices', [$this, 'notice_auto_update_core']);
			} else {
				define( 'WP_AUTO_UPDATE_CORE', false );
			}
		}
    }
    
    public function vnex_adminbar_menu( $meta = true ) {  
        global $wp_admin_bar;  
        if ( ! is_user_logged_in() ) { return; }  
        if ( ! is_super_admin() || ! is_admin_bar_showing() ) { return; } 
        $wp_admin_bar->add_menu( array(   
            'id'     => 'wp-extra',
            'title'  => __('WP EXtra'),
            'href'   => admin_url( 'admin.php?page=wp-extra' ),
            //'meta'   => array( 'target' => '_blank' )
        ) );  
    }
    
    
    public function removeNodes($wp_admin_bar) {
        $toolbar_options = wp_extra_get_option('wp_toolbar');
        if ($toolbar_options) {
            foreach ($toolbar_options as $menu_id) {
                $wp_admin_bar->remove_node($menu_id);
            }
        }
    }

    public function wpAdminbar_filter() {
        $user = wp_get_current_user();
        $allowed_roles = (array) wp_extra_get_option('wp_adminbar_roles');
        if ( !array_intersect( $allowed_roles, $user->roles ) ) {
            return;
        }
        if(wp_extra_get_option('wp_adminbar_auto')) { 
            return;
        }
        add_filter('show_admin_bar', '__return_false');
    } 

    public function wpAdminbar() {
        $user = wp_get_current_user();
        $allowed_roles = (array) wp_extra_get_option('wp_adminbar_roles');
        if ( !array_intersect( $allowed_roles, $user->roles ) ) {
            return;
        }
        $wpex_css = "";
        if(wp_extra_get_option('wp_adminbar') && !wp_extra_get_option('wp_adminbar_auto')) {
            $wpex_css .= "
                html.wp-toolbar {
                    padding-top: 0 !important;
                }
                #adminmenu {
                    margin: 0 !important;
                }
                .show-admin-bar {
                    display: none;
                } 
                #wpadminbar { 
                    display:none; 
                }
                @media (min-width: 850px) {
                    .mfp-content, .stuck, button.mfp-close {
                        margin-top: -32px!important;
                    }
                }
                ";

        } 
        wp_add_inline_style('admin-bar', ClassEXtra::minifyCSS($wpex_css));
    }
    
    public function wpex_admin_menu_roles() {
        if ( !is_admin() ) {
            return;
        }
    
        /* $user = wp_get_current_user();
        $allowed_roles = (array) wp_extra_get_option('adminmenu_roles');
        if ( !array_intersect( $allowed_roles, $user->roles ) ) {
            return;
        } */
    
        $admin_menus = (array) wp_extra_get_option('adminmenu_list');
    
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'wp-extra' ) {
            return;
        }
    
        foreach ( $admin_menus as $menu_page ) {
            remove_menu_page( $menu_page );
        }
    }

    public function wpex_hide_plugins() {
        global $wp_list_table;
        /* $user = wp_get_current_user();
        $allowed_roles = (array) wp_extra_get_option('adminplugin_roles');
    
        if ( !array_intersect( $allowed_roles, $user->roles ) ) {
            return;
        } */
    
        $hide_plugins = (array) wp_extra_get_option('adminplugin_list');
        $my_plugins = $wp_list_table->items;
    
        foreach ( $my_plugins as $key => $val ) {
            if ( in_array( $key, $hide_plugins ) ) {
                unset( $wp_list_table->items[ $key ] );
            }
        }
    }

	public function redirect_non_admin_user(){
		if ( !defined( 'DOING_AJAX' ) && !current_user_can('administrator') ){
			wp_redirect( site_url() );  exit;
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

	public function notice_auto_update_core() {
		$message = sprintf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s %s</p></div>',
			__('Warning:'),
			__('WP_AUTO_UPDATE_CORE'),
			__('is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'wp-extra')
		);
	
		echo $message;
	}
    
    function scroll_to_top() { 
        echo '<a id="backtotop" class="button" href="#" style="position: fixed; right: 10px; bottom: 15px; background: #3858e9; padding: 4px; border-radius: 3px; fill: rgb(255, 255, 255); height: 32px; box-shadow: rgba(0, 0, 0, 0.2) 0px 4px 8px;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 3.9 6.5 9.5l1 1 3.8-3.7V20h1.5V6.8l3.7 3.7 1-1z"></path></svg></a>';
        ?>
        <script>
            jQuery(window).on('scroll', function() {
                var scrollPosition = jQuery(window).scrollTop();
                if (scrollPosition > 200) {
                    jQuery('#backtotop').fadeIn('slow');
                } else {
                    jQuery('#backtotop').fadeOut('slow');
                }
            });
            jQuery('#backtotop').on('click', function(e) {
                e.preventDefault();
                jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
            });
        </script>
        <?php
    }

}