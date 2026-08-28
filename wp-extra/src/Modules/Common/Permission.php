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
		'menu_chunk_saver',
		'adminplugin_list',
		'disable_widgets',
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
        add_filter('show_password_fields', [$this, 'filter_show_password_fields']);
    }

    public function filter_show_password_fields($show_password_fields) {
        if (!current_user_can('administrator')) {
            return false;
        }
        return $show_password_fields;
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

    public function menu_chunk_saver() {
        add_action('admin_enqueue_scripts', [$this, 'menu_chunk_saver_scripts']);
        add_filter('walker_nav_menu_start_el', [$this, 'handle_non_link_menu_items'], 10, 4);
        add_action('wp_ajax_wpex_save_menu_chunk', [$this, 'ajax_save_menu_chunk']);
        add_action('wp_ajax_kn_save_menu_chunk', [$this, 'ajax_save_menu_chunk']);
    }

    public function menu_chunk_saver_scripts($hook) {
        if ('nav-menus.php' !== $hook) {
            return;
        }
        add_action('admin_footer', [$this, 'render_menu_chunk_saver_script'], 99);
    }

    public function handle_non_link_menu_items($item_output, $item, $depth, $args) {
        if (empty($item->url) || '#' === trim($item->url)) {
            $item_output = str_replace('href="#"', 'href="javascript:void(0)" class="wpex-menu-no-link"', $item_output);
        }
        return $item_output;
    }

    public function ajax_save_menu_chunk() {
        $nonce = $_POST['security'] ?? '';
        if (!wp_verify_nonce($nonce, 'wpex_menu_chunk_nonce') && !wp_verify_nonce($nonce, 'kn_menu_chunk_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'wp-extra')]);
        }

        if (!current_user_can('edit_theme_options')) {
            wp_send_json_error(['message' => __('You do not have permission to edit menus.', 'wp-extra')]);
        }

        $menu_id = isset($_POST['menu_id']) ? intval($_POST['menu_id']) : 0;
        $chunk_index = isset($_POST['chunk_index']) ? intval($_POST['chunk_index']) : 0;
        $total_chunks = isset($_POST['total_chunks']) ? intval($_POST['total_chunks']) : 1;
        $is_first_chunk = !empty($_POST['is_first_chunk']);
        $is_last_chunk = !empty($_POST['is_last_chunk']);

        if (!$menu_id || !is_nav_menu($menu_id)) {
            wp_send_json_error(['message' => __('Invalid menu.', 'wp-extra')]);
        }

        if ($is_first_chunk) {
            if (!empty($_POST['menu_name'])) {
                $menu_name = sanitize_text_field(wp_unslash($_POST['menu_name']));
                wp_update_nav_menu_object($menu_id, ['menu-name' => $menu_name]);
            }

            if (!empty($_POST['deleted_items']) && is_array($_POST['deleted_items'])) {
                foreach ($_POST['deleted_items'] as $del_id) {
                    $del_id = intval($del_id);
                    if ($del_id > 0 && is_nav_menu_item($del_id)) {
                        wp_delete_post($del_id, true);
                    }
                }
            }

            if (isset($_POST['menu_locations']) && is_array($_POST['menu_locations'])) {
                $locations = get_nav_menu_locations();
                foreach ($_POST['menu_locations'] as $loc => $checked) {
                    $loc = sanitize_key($loc);
                    if ($checked) {
                        $locations[$loc] = $menu_id;
                    } elseif (isset($locations[$loc]) && $locations[$loc] === $menu_id) {
                        unset($locations[$loc]);
                    }
                }
                set_theme_mod('nav_menu_locations', $locations);
            }
        }

        $items = isset($_POST['items']) && is_array($_POST['items']) ? wp_unslash($_POST['items']) : [];
        $id_mapping = [];

        foreach ($items as $item_data) {
            if (!is_array($item_data)) {
                continue;
            }

            $db_id = isset($item_data['menu-item-db-id']) ? intval($item_data['menu-item-db-id']) : 0;
            $temp_id = isset($item_data['_temp_id']) ? sanitize_text_field($item_data['_temp_id']) : '';

            $args = [
                'menu-item-object-id'   => isset($item_data['menu-item-object-id']) ? sanitize_text_field($item_data['menu-item-object-id']) : '',
                'menu-item-object'      => isset($item_data['menu-item-object']) ? sanitize_text_field($item_data['menu-item-object']) : '',
                'menu-item-parent-id'   => isset($item_data['menu-item-parent-id']) ? intval($item_data['menu-item-parent-id']) : 0,
                'menu-item-position'    => isset($item_data['menu-item-position']) ? intval($item_data['menu-item-position']) : 0,
                'menu-item-type'        => isset($item_data['menu-item-type']) ? sanitize_text_field($item_data['menu-item-type']) : 'custom',
                'menu-item-title'       => isset($item_data['menu-item-title']) ? wp_kses_post($item_data['menu-item-title']) : '',
                'menu-item-url'         => isset($item_data['menu-item-url']) ? esc_url_raw($item_data['menu-item-url']) : '',
                'menu-item-description' => isset($item_data['menu-item-description']) ? wp_kses_post($item_data['menu-item-description']) : '',
                'menu-item-attr-title'  => isset($item_data['menu-item-attr-title']) ? sanitize_text_field($item_data['menu-item-attr-title']) : '',
                'menu-item-target'      => isset($item_data['menu-item-target']) ? sanitize_text_field($item_data['menu-item-target']) : '',
                'menu-item-classes'     => isset($item_data['menu-item-classes']) ? sanitize_text_field($item_data['menu-item-classes']) : '',
                'menu-item-xfn'         => isset($item_data['menu-item-xfn']) ? sanitize_text_field($item_data['menu-item-xfn']) : '',
                'menu-item-status'      => 'publish',
            ];

            $new_id = wp_update_nav_menu_item($menu_id, $db_id > 0 ? $db_id : 0, $args);

            if (!is_wp_error($new_id) && $new_id) {
                if ($temp_id) {
                    $id_mapping[$temp_id] = $new_id;
                }

                $custom_meta_fields = [
                    'menu-item-design'             => '_menu_item_design',
                    'menu-item-image'              => '_menu_item_image',
                    'menu-item-icon'               => '_menu_item_icon',
                    'menu-item-custom_label'       => '_menu_item_custom_label',
                    'menu-item-custom_label_color' => '_menu_item_custom_label_color',
                    'menu-item-badge_color'        => '_menu_item_badge_color',
                ];

                foreach ($custom_meta_fields as $post_key => $meta_key) {
                    if (isset($item_data[$post_key])) {
                        update_post_meta($new_id, $meta_key, sanitize_text_field($item_data[$post_key]));
                    }
                }
            }
        }

        wp_send_json_success([
            'chunk_index'  => $chunk_index,
            'total_chunks' => $total_chunks,
            'is_last'      => $is_last_chunk,
            'id_mapping'   => $id_mapping,
        ]);
    }

    public function render_menu_chunk_saver_script() {
        ?>
        <style>
            @keyframes wpex-btn-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .wpex-btn-spinner-icon {
                display: inline-block;
                width: 14px;
                height: 14px;
                border: 2px solid rgba(255, 255, 255, 0.4);
                border-radius: 50%;
                border-top-color: #ffffff;
                animation: wpex-btn-spin 0.75s linear infinite;
                vertical-align: middle;
                margin-right: 6px;
            }
            .button.menu-save.is-saving {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                pointer-events: none !important;
                opacity: 0.9 !important;
                cursor: not-allowed !important;
            }
            .item-controls .item-group-toggle {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                gap: 4px;
                height: 38px;
                padding: 0 8px !important;
                margin: 0 !important;
                color: #64748b;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                cursor: pointer;
                text-decoration: none;
                outline: none;
                transition: all 0.15s ease;
                vertical-align: middle;
            }
            .item-controls .item-group-toggle:hover {
                color: #2563eb !important;
                background: #f1f5f9 !important;
            }
            .item-controls .item-group-toggle .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                line-height: 14px;
                transition: transform 0.2s ease;
            }
            .item-controls .item-group-toggle .wpex-group-badge {
                font-size: 11px;
                font-weight: 700;
                background: #e2e8f0;
                color: #475569;
                padding: 1px 6px;
                border-radius: 10px;
                line-height: 1.2;
            }
            .item-controls .item-group-toggle.is-collapsed .wpex-group-badge {
                background: #2563eb;
                color: #ffffff;
            }
            li.menu-item.wpex-has-collapsed-children {
                border-left: 3px solid #2563eb !important;
            }
            .wpex-menu-single-toggle-wrapper {
                position: absolute !important;
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%) !important;
            }
            .wpex-menu-single-toggle-wrapper .button {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                font-size: 13px !important;
                height: 30px !important;
                padding: 0 12px !important;
                line-height: 1 !important;
                vertical-align: middle !important;
            }
            .wpex-menu-single-toggle-wrapper .button .dashicons {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 16px !important;
                width: 16px !important;
                height: 16px !important;
                line-height: 16px !important;
                margin: 0 !important;
                padding: 0 !important;
                vertical-align: middle !important;
            }
        </style>

        <script type="text/javascript">
        jQuery(document).ready(function ($) {
            function getItemDepth($li) {
                var cls = $li.attr('class') || '';
                var match = cls.match(/menu-item-depth-(\d+)/);
                return match ? parseInt(match[1], 10) : 0;
            }

            function getGroupChildren($parentLi) {
                var parentDepth = getItemDepth($parentLi);
                var $children = $();
                var $next = $parentLi.next('li.menu-item');
                if ($next.length && getItemDepth($next) > parentDepth) {
                    while ($next.length && getItemDepth($next) > parentDepth) {
                        $children = $children.add($next);
                        $next = $next.next('li.menu-item');
                    }
                }
                return $children;
            }

            function updateGroupToggles() {
                $('#menu-to-edit > li.menu-item').each(function () {
                    var $item = $(this);
                    var depth = getItemDepth($item);
                    var $controls = $item.find('.item-controls').first();
                    var $editBtn = $controls.find('.item-edit');
                    var $next = $item.next('li.menu-item');

                    var hasChildren = $next.length > 0 && getItemDepth($next) > depth;
                    var $children = hasChildren ? getGroupChildren($item) : $();
                    var childCount = $children.length;
                    var $toggleBtn = $controls.find('.item-group-toggle');

                    if (hasChildren && childCount > 0) {
                        if (!$toggleBtn.length) {
                            $toggleBtn = $('<button type="button" class="button-link item-group-toggle" title="<?php esc_attr_e('Collapse / Expand', 'wp-extra'); ?>" aria-label="<?php esc_attr_e('Toggle group', 'wp-extra'); ?>">' +
                                '<span class="dashicons dashicons-arrow-down-alt2"></span>' +
                                '<span class="wpex-group-badge">' + childCount + '</span>' +
                                '</button>');

                            if ($editBtn.length) {
                                $editBtn.after($toggleBtn);
                            } else {
                                $controls.append($toggleBtn);
                            }
                        } else {
                            $toggleBtn.find('.wpex-group-badge').text(childCount);
                        }
                    } else {
                        if ($toggleBtn.length) {
                            $toggleBtn.remove();
                        }
                        $item.removeClass('wpex-has-collapsed-children');
                    }
                });
            }

            if ($('#nav-menu-footer .major-publishing-actions').length && !$('.wpex-menu-single-toggle-wrapper').length) {
                var singleToggleHtml = $('<div class="wpex-menu-single-toggle-wrapper">' +
                    '<button type="button" class="button wpex-toggle-all-groups" title="<?php esc_attr_e('Collapse / Expand', 'wp-extra'); ?>">' +
                    '<span class="dashicons dashicons-arrow-right-alt2"></span> <?php esc_html_e('Collapse', 'wp-extra'); ?>' +
                    '</button>' +
                    '</div>');

                $('#nav-menu-footer .major-publishing-actions .publishing-action').before(singleToggleHtml);
            }

            $(document).on('click', '.wpex-toggle-all-groups', function (e) {
                e.preventDefault();
                var $btn = $(this);
                var isAllCollapsed = $btn.hasClass('is-all-collapsed');

                if (!isAllCollapsed) {
                    $('#menu-to-edit > li.menu-item').each(function () {
                        var $item = $(this);
                        var depth = getItemDepth($item);
                        var $toggle = $item.find('.item-group-toggle');
                        if (depth === 0 && $toggle.length && !$toggle.hasClass('is-collapsed')) {
                            $toggle.trigger('click');
                        }
                    });
                    $btn.addClass('is-all-collapsed');
                    $btn.html('<span class="dashicons dashicons-arrow-down-alt2"></span> <?php esc_html_e('Expand', 'wp-extra'); ?>');
                } else {
                    $('#menu-to-edit > li.menu-item').each(function () {
                        var $item = $(this);
                        var depth = getItemDepth($item);
                        var $toggle = $item.find('.item-group-toggle');
                        if (depth === 0 && $toggle.length && $toggle.hasClass('is-collapsed')) {
                            $toggle.trigger('click');
                        }
                    });
                    $btn.removeClass('is-all-collapsed');
                    $btn.html('<span class="dashicons dashicons-arrow-right-alt2"></span> <?php esc_html_e('Collapse', 'wp-extra'); ?>');
                }
            });

            $(document).on('click', '.item-group-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var $item = $btn.closest('li.menu-item');
                var $children = getGroupChildren($item);
                var isCollapsed = $btn.hasClass('is-collapsed');

                if (isCollapsed) {
                    $children.show();
                    $btn.removeClass('is-collapsed');
                    $btn.find('.dashicons').removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                    $btn.attr('title', '<?php esc_attr_e('Collapse', 'wp-extra'); ?>');
                    $item.removeClass('wpex-has-collapsed-children');
                } else {
                    $children.hide();
                    $btn.addClass('is-collapsed');
                    $btn.find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                    $btn.attr('title', '<?php esc_attr_e('Expand', 'wp-extra'); ?> (' + $children.length + ')');
                    $item.addClass('wpex-has-collapsed-children');
                }
            });

            updateGroupToggles();

            var debounceTimer = null;
            function triggerUpdateToggles() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(updateGroupToggles, 100);
            }

            var menuTreeObserver = new MutationObserver(function () {
                triggerUpdateToggles();
            });
            var menuToEditEl = document.getElementById('menu-to-edit');
            if (menuToEditEl) {
                menuTreeObserver.observe(menuToEditEl, { childList: true, subtree: true });
            }

            $('#menu-to-edit').on('sortstop', function () {
                triggerUpdateToggles();
            });

            var $urlInput = $('#custom-menu-item-url');
            if ($urlInput.length) {
                $urlInput.attr('type', 'text').removeAttr('required');
                if ($urlInput.val() === 'https://' || $urlInput.val() === 'http://') {
                    $urlInput.val('').attr('placeholder', '# (leave empty for text-only item)');
                }

                $('#submit-custommenuitem').on('click mousedown', function () {
                    var val = $.trim($urlInput.val());
                    if (val === '' || val === 'https://' || val === 'http://') {
                        $urlInput.val('#');
                    }
                });
            }

            var $form = $('#update-nav-menu');
            if (!$form.length) return;

            var CHUNK_SIZE = 30;

            $form.on('submit', function (e) {
                $('#menu-to-edit > li.menu-item:hidden').show();

                var $menuItems = $('#menu-to-edit > li.menu-item');
                var totalItems = $menuItems.length;

                e.preventDefault();

                var menuId = $('#menu').val() || 0;
                if (!menuId || menuId === '0') {
                    $form.off('submit').submit();
                    return;
                }

                var $submitInputs = $('#save_menu_header, #save_menu_footer, .menu-save');
                $('.publishing-action .spinner, #nav-menu-header .spinner').addClass('is-active');

                $submitInputs.each(function () {
                    var $btn = $(this);
                    var loadingText = '<span class="wpex-btn-spinner-icon"></span> <?php esc_html_e('Saving...', 'wp-extra'); ?>';

                    if ($btn.is('input')) {
                        var $newBtn = $('<button type="submit" class="' + $btn.attr('class') + ' is-saving" id="' + $btn.attr('id') + '" disabled>' + loadingText + '</button>');
                        $btn.replaceWith($newBtn);
                    } else {
                        $btn.addClass('is-saving').prop('disabled', true).html(loadingText);
                    }
                });

                var allItemsData = [];
                $menuItems.each(function (index) {
                    var $item = $(this);
                    var itemObj = {};

                    itemObj['menu-item-position'] = index + 1;

                    var itemId = $item.find('.menu-item-data-db-id').val();
                    itemObj['menu-item-db-id'] = itemId || 0;
                    itemObj['_temp_id'] = $item.attr('id') || ('item_' + index);

                    $item.find(':input').each(function () {
                        var name = this.name;
                        if (!name || name.indexOf('menu-item-') === -1) return;

                        var cleanKey = name.replace(/\[[^\]]*\]/g, '');
                        if (this.type === 'checkbox' || this.type === 'radio') {
                            if (this.checked) itemObj[cleanKey] = $(this).val();
                        } else {
                            itemObj[cleanKey] = $(this).val();
                        }
                    });

                    allItemsData.push(itemObj);
                });

                var chunks = [];
                for (var i = 0; i < allItemsData.length; i += CHUNK_SIZE) {
                    chunks.push(allItemsData.slice(i, i + CHUNK_SIZE));
                }
                if (chunks.length === 0) {
                    chunks.push([]);
                }

                var totalChunks = chunks.length;
                var currentChunk = 0;

                var deletedItems = [];
                $('input[name^="delete-menu-item"]').each(function () {
                    deletedItems.push($(this).val());
                });

                var menuLocations = {};
                $('.menu-theme-locations :checkbox').each(function () {
                    var locName = $(this).attr('name').replace('menu-locations[', '').replace(']', '');
                    menuLocations[locName] = $(this).is(':checked') ? 1 : 0;
                });

                var menuName = $('#menu-name').val();

                function sendNextChunk() {
                    var isFirst = (currentChunk === 0);
                    var isLast = (currentChunk === totalChunks - 1);

                    var postData = {
                        action: 'wpex_save_menu_chunk',
                        security: '<?php echo esc_js(wp_create_nonce('wpex_menu_chunk_nonce')); ?>',
                        menu_id: menuId,
                        chunk_index: currentChunk,
                        total_chunks: totalChunks,
                        is_first_chunk: isFirst ? 1 : 0,
                        is_last_chunk: isLast ? 1 : 0,
                        items: chunks[currentChunk]
                    };

                    if (isFirst) {
                        postData.menu_name = menuName;
                        postData.deleted_items = deletedItems;
                        postData.menu_locations = menuLocations;
                    }

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: postData,
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                currentChunk++;
                                if (currentChunk < totalChunks) {
                                    sendNextChunk();
                                } else {
                                    $('.menu-save').html('✓ <?php esc_html_e('Saved!', 'wp-extra'); ?>');
                                    $('.publishing-action .spinner, #nav-menu-header .spinner').removeClass('is-active');
                                    setTimeout(function () {
                                        window.location.reload();
                                    }, 400);
                                }
                            } else {
                                alert('Error saving menu: ' + (response.data ? response.data.message : 'Unknown error'));
                                window.location.reload();
                            }
                        },
                        error: function (xhr, status, error) {
                            alert('Server connection error: ' + error);
                            window.location.reload();
                        }
                    });
                }

                sendNextChunk();
            });
        });
        </script>
        <?php
    }

    public function disable_widgets() {
        add_action('widgets_init', [$this, 'unregister_all_default_widgets'], 99);
    }

    public function unregister_all_default_widgets() {
        $default_widgets = [
            'WP_Widget_Pages',
            'WP_Widget_Calendar',
            'WP_Widget_Archives',
            'WP_Widget_Links',
            'WP_Widget_Media_Audio',
            'WP_Widget_Media_Image',
            'WP_Widget_Media_Video',
            'WP_Widget_Media_Gallery',
            'WP_Widget_Custom_HTML',
            'WP_Widget_Meta',
            'WP_Widget_Search',
            'WP_Widget_Text',
            'WP_Widget_Categories',
            'WP_Widget_Recent_Posts',
            'WP_Widget_Recent_Comments',
            'WP_Widget_RSS',
            'WP_Widget_Tag_Cloud',
            'WP_Nav_Menu_Widget',
            'WP_Widget_Block',
        ];

        foreach ($default_widgets as $widget) {
            unregister_widget($widget);
        }
    }

}