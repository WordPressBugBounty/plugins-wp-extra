<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class Permission extends Base {
    
    public function __construct() {
		parent::__construct();
        add_action( 'admin_bar_menu', [$this, 'admin_bar_extra'], 150 );
    }
    
    public function admin_bar_extra( $meta = true ) {  
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
    
	protected $features = [
		'wp_toolbar',
		'wp_adminbar_visible',
		'adminmenu_list',
		'adminplugin_list',
		'registration_date',
		'last_login',
	];
    
    public function registration_date() {
        add_filter( 'manage_users_columns', [$this, 'registration_date_columns'] );
        add_filter( 'manage_users_custom_column', [$this, 'registration_date_custom_column'], 10, 3 );
        add_filter( 'manage_users_sortable_columns', [$this, 'registration_date_sortable_columns'] );
    }
    
    public function registration_date_columns( $columns ) {
        $columns['registration_date'] = __('User Registration Date');
        return $columns;
    }
    
    public function registration_date_custom_column( $row_output, $column_id_attr, $user_id ) {
        $date_format = 'Y-m-d H:i:s';
        switch ( $column_id_attr ) {
            case 'registration_date' :
                return date( $date_format, strtotime( get_the_author_meta( 'registered', $user_id ) ) );
                break;
            default:
        }
        return $row_output;
    }
    
    public function registration_date_sortable_columns( $columns ) {
        return wp_parse_args( array( 'registration_date' => 'registered' ), $columns );
    }
    
    public function last_login() {
        add_filter( 'manage_users_columns', [$this, 'last_login_columns'] );
        add_filter( 'manage_users_custom_column', [$this, 'last_login_custom_column'], 10, 3 );
        add_filter( 'manage_users_sortable_columns', [$this, 'last_login_sortable_columns'] );
        add_action( 'wp_login', [$this, 'update_last_login'] );
    }
    
    public function last_login_columns( $columns ) {
        $columns['last_login'] = __('Last Login');
        return $columns;
    }
    
    public function last_login_custom_column( $row_output, $column_id_attr, $user_id ) {
        $last_login = get_user_meta($user_id, "last_login", true);
        if (!empty($last_login)) {
            $now = new DateTime();
            $last_login = new DateTime($last_login);
            $interval = $now->diff($last_login);
            if ($interval->y > 0) {
                $value = $interval->format("%y years");
            } elseif ($interval->m > 0) {
                $value = $interval->format("%m months");
            } elseif ($interval->d > 0) {
                $value = $interval->format("%d days");
            } elseif ($interval->h > 0) {
                $value = $interval->format("%h hours");
            } elseif ($interval->i > 0) {
                $value = $interval->format("%i minutes");
            } else {
                $value = $interval->format("%s seconds");
            }
            $value .= " ago";
        } else {
            $value = "Never";
        }
        switch ( $column_id_attr ) {
            case 'last_login' :
                return $value;
                break;
            default:
        }
        return $row_output;
    }
    
    public function update_last_login($login) {
        $user = get_user_by('login', $login);
        update_user_meta($user->ID, 'last_login', date('Y-m-d H:i:s'));
    }
    
    public function last_login_sortable_columns( $columns ) {
        return wp_parse_args( array( 'last_login' => 'lastlogin' ), $columns );
    }
    
    public function wp_toolbar() {
        add_action( 'admin_bar_menu', [$this, 'remove_nodes'], 999);
    }
    
    public function remove_nodes($wp_admin_bar) {
        $toolbar_nodes = Settings::get_option('wp_toolbar');
        if (is_array($toolbar_nodes)) {
            foreach ($toolbar_nodes as $menu_id) {
                $wp_admin_bar->remove_node($menu_id);
            }
        }
    }
    public function wp_adminbar_visible() {
        if (Settings::get_option('wp_adminbar_visible') && in_array('site-admin',  Settings::get_option('wp_adminbar_visible'))) {
            add_action( 'admin_enqueue_scripts',  [$this, 'adminbar_visible_admin' ]);
        }
        if (Settings::get_option('wp_adminbar_visible') && in_array('homepage',  Settings::get_option('wp_adminbar_visible'))) {
            if (Settings::get_option('wp_adminbar_pos')) {
                add_action( 'wp_enqueue_scripts',  [$this, 'adminbar_bottom_homepage' ]);
            } elseif (Settings::get_option('wp_adminbar')) {
                add_action('after_setup_theme', [$this, 'adminbar_visible_homepage']);
            }
        }
    }

    public function adminbar_visible_admin() {
        $user = wp_get_current_user();
        $allowed_roles = (array) Settings::get_option('wp_adminbar_roles');
        if ( !array_intersect( $allowed_roles, $user->roles ) ) {
            return;
        }
        if (Settings::get_option('wp_adminbar')) {
            wp_add_inline_style('admin-bar', 'html.wp-toolbar{padding-top:0}#wpadminbar,.show-admin-bar{display:none}');
        } elseif (Settings::get_option('wp_adminbar_pos')) {
            wp_add_inline_style('admin-bar', 'html.wp-toolbar{padding-top:0}html #wpadminbar{top:auto;bottom:0}#wpadminbar .menupop .ab-sub-wrapper{bottom: 32px}');
        }
    }

    public function adminbar_visible_homepage() {
        $user = wp_get_current_user();
        $allowed_roles = (array) Settings::get_option('wp_adminbar_roles');
        if ( !array_intersect( $allowed_roles, $user->roles ) ) {
            return;
        }
        if(Settings::get_option('wp_adminbar_auto')) { 
            return;
        }
        add_filter('show_admin_bar', '__return_false');
    }

    public function adminbar_bottom_homepage() {
        $user = wp_get_current_user();
        $allowed_roles = (array) Settings::get_option('wp_adminbar_roles');
        if ( !array_intersect( $allowed_roles, $user->roles ) ) {
            return;
        }
        wp_add_inline_style('admin-bar', 'html.wp-toolbar{padding-top:0}html #wpadminbar{top:auto;bottom:0}#wpadminbar .menupop .ab-sub-wrapper{bottom:32px}');
    }
    
    public function adminmenu_list() {
        add_action('admin_menu', [$this, 'admin_menu_roles'], 9999);
    }
    
    public function admin_menu_roles() {
        if ( !is_admin() ) {
            return;
        }
        $admin_menus = (array) Settings::get_option('adminmenu_list');
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'wp-extra' ) {
            return;
        }
        foreach ( $admin_menus as $menu_page ) {
            remove_menu_page( $menu_page );
        }
    }
    
    public function adminplugin_list() {
        add_action('pre_current_active_plugins', [$this, 'adminplugin_roles']);
    }

    public function adminplugin_roles() {
        global $wp_list_table;
        $hide_plugins = (array) Settings::get_option('adminplugin_list');
        $my_plugins = $wp_list_table->items;
        foreach ( $my_plugins as $key => $val ) {
            if ( in_array( $key, $hide_plugins ) ) {
                unset( $wp_list_table->items[ $key ] );
            }
        }
    }

}