<?php
namespace WPEXtra\Modules\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class Permission extends Base {
    
    public function __construct() {
		parent::__construct();
        add_action('admin_bar_menu', [$this, 'admin_bar_extra'], 150);
    }
    
    public function admin_bar_extra($meta = true) {  
        global $wp_admin_bar;  
        if (!is_user_logged_in() || !is_super_admin() || !is_admin_bar_showing()) {
            return;
        } 
        $wp_admin_bar->add_menu([   
            'id'    => 'wp-extra',
            'title' => __('WP EXtra', 'wp-extra'),
            'href'  => admin_url('admin.php?page=wp-extra'),
        ]);  
    }
    
	protected $features = [
		'wp_toolbar',
		'admin_site_link',
		'wp_adminbar',
		'wp_adminbar_auto',
		'adminmenu_list',
		'adminplugin_list',
		'scrolltotop',
		'registration_date',
		'last_login',
		'application_passwords',
		'profile_pw',
		'profile_email',
		'no_backend',
		'adminmenu_extra',
	];

    public function scrolltotop() {
        if (is_admin()) {
            add_action('admin_footer', [$this, 'render_scrolltotop']);
        }
    }

    public function render_scrolltotop() {
        $screen = get_current_screen();
        if ($screen && !empty($screen->is_block_editor)) {
            return;
        }
        ?>
        <style>
            #wpex-backtotop {
                position: fixed;
                right: 20px;
                bottom: 20px;
                z-index: 99999;
                width: 40px;
                height: 40px;
                display: none;
                align-items: center;
                justify-content: center;
                background: var(--wp-admin-theme-color, #2271b1);
                color: #ffffff;
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.16);
                text-decoration: none;
                cursor: pointer;
                transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.2s ease;
                outline: none;
                border: none;
                padding: 0;
                margin: 0;
                box-sizing: border-box;
            }
            .rtl #wpex-backtotop {
                right: auto;
                left: 20px;
            }
            #wpex-backtotop:hover {
                background: var(--wp-admin-theme-color-darker-10, #135e96);
                color: #ffffff;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.22);
            }
            #wpex-backtotop:active {
                transform: translateY(0);
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
            }
            #wpex-backtotop svg {
                width: 20px;
                height: 20px;
                display: block;
                fill: currentColor;
            }
        </style>
        <a id="wpex-backtotop" href="#" title="<?php esc_attr_e('Scroll to top', 'wp-extra'); ?>" aria-label="<?php esc_attr_e('Scroll to top', 'wp-extra'); ?>">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                <path d="M12 4.5l-7 7 1.4 1.4 4.6-4.6V19.5h2V8.3l4.6 4.6 1.4-1.4-7-7z"/>
            </svg>
        </a>
        <script>
            jQuery(function($) {
                var $btn = $('#wpex-backtotop');
                $(window).on('scroll', function() {
                    if ($(this).scrollTop() > 180) {
                        if (!$btn.is(':visible')) {
                            $btn.css('display', 'flex').hide().fadeIn(200);
                        }
                    } else {
                        if ($btn.is(':visible')) {
                            $btn.fadeOut(200);
                        }
                    }
                });
                $btn.on('click', function(e) {
                    e.preventDefault();
                    $('html, body').animate({ scrollTop: 0 }, 250);
                });
            });
        </script>
        <?php
    }
    
    public function registration_date() {
        add_filter('manage_users_columns', [$this, 'registration_date_columns']);
        add_filter('manage_users_custom_column', [$this, 'registration_date_custom_column'], 10, 3);
        add_filter('manage_users_sortable_columns', [$this, 'registration_date_sortable_columns']);
    }
    
    public function registration_date_columns($columns) {
        $columns['registration_date'] = __('User Registration Date', 'wp-extra');
        return $columns;
    }
    
    public function registration_date_custom_column($row_output, $column_id_attr, $user_id) {
        if ($column_id_attr === 'registration_date') {
            $registered = get_the_author_meta('registered', $user_id);
            return $registered ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($registered)) : '';
        }
        return $row_output;
    }
    
    public function registration_date_sortable_columns($columns) {
        return wp_parse_args(['registration_date' => 'registered'], $columns);
    }
    
    public function last_login() {
        add_filter('manage_users_columns', [$this, 'last_login_columns']);
        add_filter('manage_users_custom_column', [$this, 'last_login_custom_column'], 10, 3);
        add_filter('manage_users_sortable_columns', [$this, 'last_login_sortable_columns']);
        add_action('wp_login', [$this, 'update_last_login'], 10, 2);
    }
    
    public function last_login_columns($columns) {
        $columns['last_login'] = __('Last Login', 'wp-extra');
        return $columns;
    }
    
    public function last_login_custom_column($row_output, $column_id_attr, $user_id) {
        if ($column_id_attr === 'last_login') {
            $last_login = get_user_meta($user_id, 'last_login', true);
            if (!empty($last_login)) {
                return sprintf(__('%s ago', 'wp-extra'), human_time_diff(strtotime($last_login), current_time('timestamp')));
            }
            return __('Never', 'wp-extra');
        }
        return $row_output;
    }
    
    public function update_last_login($login, $user = null) {
        if (!$user) {
            $user = get_user_by('login', $login);
        }
        if ($user) {
            update_user_meta($user->ID, 'last_login', current_time('mysql'));
        }
    }
    
    public function last_login_sortable_columns($columns) {
        return wp_parse_args(['last_login' => 'lastlogin'], $columns);
    }
    
    public function wp_toolbar() {
        add_action('admin_bar_menu', [$this, 'remove_nodes'], 999);
    }
    
    public function admin_site_link() {
        add_action('admin_bar_menu', [$this, 'custom_site_name_link'], 99);
    }

    public function custom_site_name_link($wp_admin_bar) {
        if (Helper::get_option('admin_site_link')) {
            $node = $wp_admin_bar->get_node('site-name');
            if ($node) {
                $node->href = admin_url();
                $wp_admin_bar->add_node((array) $node);
            }
        }
    }
    
    public function remove_nodes($wp_admin_bar) {
        $toolbar_nodes = Helper::get_option('wp_toolbar');
        if (is_array($toolbar_nodes)) {
            foreach ($toolbar_nodes as $menu_id) {
                $wp_admin_bar->remove_node($menu_id);
            }
        }
    }

    public function wp_adminbar() {
        add_filter('show_admin_bar', [$this, 'filter_show_admin_bar'], 99);
    }

    public function filter_show_admin_bar($show) {
        if (is_admin()) {
            return $show;
        }
        return false;
    }

    public function wp_adminbar_auto() {
        add_action('admin_enqueue_scripts', [$this, 'adminbar_auto_css']);
        add_action('wp_enqueue_scripts', [$this, 'adminbar_auto_css']);
    }

    public function adminbar_auto_css() {
        if (!is_admin_bar_showing()) {
            return;
        }
        $css = '@media (min-width: 783px) {
            html.wp-toolbar { padding-top: 0 !important; }
            html { margin-top: 0 !important; }
            body.admin-bar { margin-top: 0 !important; }
            #wpadminbar {
                top: 0 !important;
                transform: translateY(-100%);
                opacity: 0;
                pointer-events: none;
                transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 999999 !important;
            }
            #wpadminbar::after {
                content: "";
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                height: 12px;
                pointer-events: auto;
            }
            #wpadminbar:hover,
            #wpadminbar:focus-within {
                transform: translateY(0);
                opacity: 1;
                pointer-events: auto;
            }
        }';
        wp_add_inline_style('admin-bar', Helper::minifyCSS($css));
    }
    
    public function adminmenu_list() {
        add_action('admin_menu', [$this, 'admin_menu_roles'], 9999);
    }
    
    public function admin_menu_roles() {
        if (!is_admin()) {
            return;
        }
        $admin_menus = (array) Helper::get_option('adminmenu_list', []);
        if (isset($_GET['page']) && $_GET['page'] === 'wp-extra') {
            return;
        }
        foreach ($admin_menus as $menu_page) {
            remove_menu_page($menu_page);
        }
    }
    
    public function adminplugin_list() {
        add_filter('all_plugins', [$this, 'filter_hidden_plugins']);
    }

    public function filter_hidden_plugins($plugins) {
        $hide_plugins = (array) Helper::get_option('adminplugin_list', []);
        if (!empty($hide_plugins) && is_array($plugins)) {
            foreach ($hide_plugins as $plugin_file) {
                if (isset($plugins[$plugin_file])) {
                    unset($plugins[$plugin_file]);
                }
            }
        }
        return $plugins;
    }

    public function application_passwords() {
        add_filter('wp_is_application_passwords_available', '__return_false');
    }

    public function profile_pw() {
        if (!current_user_can('administrator')) {
            add_filter('show_password_fields', '__return_false');
        }
    }

    public function profile_email() {
        add_action('personal_options_update', [$this, 'disableEmailChangeBack']);
        add_action('edit_user_profile_update', [$this, 'disableEmailChangeBack']);
        add_action('show_user_profile', [$this, 'disableEmailChangeBackFront']);
        add_action('edit_user_profile', [$this, 'disableEmailChangeBackFront']);
    }

    public function disableEmailChangeBack($user_id) {
        if (!current_user_can('administrator')) {
            $user = get_user_by('ID', $user_id);
            if ($user) {
                $_POST['email'] = $user->user_email;
            }
        }
    }

    public function disableEmailChangeBackFront($user) {
        if (!current_user_can('administrator')) {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function(){
                var emailInput = document.getElementById('email');
                if (emailInput) {
                    emailInput.setAttribute('disabled', 'disabled');
                }
            });
            </script>
            <?php
        }
    }

    public function no_backend() {
        add_action('admin_init', [$this, 'redirect_non_admin_user']);
    }

    public function redirect_non_admin_user() {
        if (wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (current_user_can('administrator')) {
            return;
        }

        $user = wp_get_current_user();
        $blocked_roles = (array) Helper::get_option('no_backend', []);

        if (!empty($blocked_roles) && !empty($user->roles) && array_intersect($blocked_roles, (array)$user->roles)) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }

    public function adminmenu_extra() {
        add_action('admin_menu', [$this, 'restrict_settings_access'], 999);
        add_action('admin_init', [$this, 'block_settings_page_access']);
    }

    public function restrict_settings_access() {
        $super_admins = (array) Helper::get_option('adminmenu_extra', []);
        if (!empty($super_admins)) {
            $current_user_id = (string) get_current_user_id();
            if (!in_array($current_user_id, array_map('strval', $super_admins), true)) {
                remove_menu_page('wp-extra');
                remove_submenu_page('wp-extra', 'wp-extra');
                remove_submenu_page('wp-extra', 'wpex-email-logs');
            }
        }
    }

    public function block_settings_page_access() {
        $super_admins = (array) Helper::get_option('adminmenu_extra', []);
        if (!empty($super_admins) && isset($_GET['page']) && in_array($_GET['page'], ['wp-extra', 'wpex-email-logs'], true)) {
            $current_user_id = (string) get_current_user_id();
            if (!in_array($current_user_id, array_map('strval', $super_admins), true)) {
                wp_die(esc_html__('You do not have permission to access WP EXtra settings.', 'wp-extra'), 403);
            }
        }
    }

}