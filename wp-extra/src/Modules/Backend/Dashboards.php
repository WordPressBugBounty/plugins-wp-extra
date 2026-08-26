<?php
namespace WPEXtra\Modules\Backend;

if (!defined('ABSPATH')) {
    exit;
}

use WPEXtra\Helper;
use WPEXtra\Base;

class Dashboards extends Base
{

    public function __construct()
    {
        parent::__construct();
        add_action('wp_dashboard_setup', [$this, 'add_custom_widgets'], 1000);
    }

    protected $features = [
        'dashboard',
        'dashboard_welcome',
        'tab_help',
        'tab_screen',
        'dashboard_sysinfo',
    ];

    public function dashboard_sysinfo()
    {
        add_action('wp_dashboard_setup', [$this, 'add_sysinfo_widget'], 1000);
    }

    public function dashboard()
    {
        add_action('wp_dashboard_setup', [$this, 'remove_all_dashboard_widgets'], 999);
        add_action('admin_enqueue_scripts', [$this, 'full_dashboard']);
        $this->dashboard_welcome();
        if (!function_exists('wpforms')) {
            add_filter('wpforms_admin_dashboardwidget', '__return_false');
        }
    }

    public function dashboard_welcome()
    {
        remove_action('welcome_panel', 'wp_welcome_panel');
        add_action('load-index.php', [$this, 'hide_welcome_panel']);
        add_filter('get_user_metadata', [$this, 'filter_show_welcome_panel'], 10, 4);
        add_action('admin_head-index.php', [$this, 'hide_welcome_panel_css']);
    }

    public function hide_welcome_panel()
    {
        $user_id = get_current_user_id();
        if ($user_id && (int) get_user_meta($user_id, 'show_welcome_panel', true) === 1) {
            update_user_meta($user_id, 'show_welcome_panel', 0);
        }
    }

    public function filter_show_welcome_panel($null, $object_id, $meta_key, $single)
    {
        if ($meta_key === 'show_welcome_panel') {
            return $single ? 0 : [0];
        }
        return $null;
    }

    public function hide_welcome_panel_css()
    {
        echo '<style>#welcome-panel,.welcome-panel,label[for="wp_welcome_panel-hide"],.metabox-prefs label:has(#wp_welcome_panel-hide){display:none!important}</style>';
    }

    public function remove_all_dashboard_widgets()
    {
        global $wp_meta_boxes;
        $wp_meta_boxes['dashboard'] = [
            'normal' => ['core' => [], 'high' => [], 'sorted' => [], 'default' => [], 'low' => []],
            'side' => ['core' => [], 'high' => [], 'sorted' => [], 'default' => [], 'low' => []],
        ];

        remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'side');
        if (class_exists('WooCommerce')) {
            remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
            remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        }
    }

    public function full_dashboard($hook)
    {
        if ($hook === 'index.php') {
            $css = '#dashboard-widgets-wrap{overflow:unset!important}.postbox-container{min-width:100%!important}.meta-box-sortables.ui-sortable.empty-container,.wrap>h1{display:none}';
            wp_add_inline_style('dashboard', $css);
        }
    }

    public function get_custom_widgets_data()
    {
        $widgets = (array) Helper::get_option('dashboard_custom_widgets', []);

        // Backward compatibility for single widget setup
        if (empty($widgets) && (!empty(Helper::get_option('dashboard_content')) || !empty(Helper::get_option('dashboard_title')))) {
            $widgets[] = [
                'title' => Helper::get_option('dashboard_title', 'WP EXtra Notice'),
                'content' => Helper::get_option('dashboard_content', ''),
                'style' => Helper::get_option('dashboard_widget_style', 'standard'),
                'role' => 'all',
                'cta_text' => Helper::get_option('dashboard_cta_text', ''),
                'cta_url' => Helper::get_option('dashboard_cta_url', ''),
            ];
        }

        return array_filter($widgets, fn($w) => !empty($w['title']) || !empty($w['content']));
    }

    public function add_custom_widgets()
    {
        $widgets = $this->get_custom_widgets_data();
        if (empty($widgets)) {
            return;
        }

        $user = wp_get_current_user();
        $user_roles = $user ? (array) $user->roles : [];

        foreach ($widgets as $index => $widget) {
            $target_role = $widget['role'] ?? 'all';

            // Check permissions
            if ($target_role !== 'all' && !empty($target_role)) {
                if (!in_array($target_role, $user_roles, true) && !in_array('administrator', $user_roles, true)) {
                    continue;
                }
            }

            $widget_id = 'wpex_custom_notice_' . $index;
            $widget_title = !empty($widget['title']) ? $widget['title'] : __('Notice', 'wp-extra');

            wp_add_dashboard_widget(
                $widget_id,
                esc_html($widget_title),
                function () use ($widget) {
                    $this->render_custom_widget_content($widget);
                }
            );
        }
    }

    public function render_custom_widget_content($widget)
    {
        $content = $widget['content'] ?? '';
        echo '<div class="wpex-custom-widget-body" style="font-size:14px;line-height:1.7;color:#2c3338;">';
        echo do_shortcode(wpautop(wp_kses_post($content)));
        echo '</div>';
    }

    public function add_sysinfo_widget()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        wp_add_dashboard_widget(
            'wpex_sysinfo_widget',
            __('System & Environment Overview', 'wp-extra'),
            [$this, 'render_sysinfo_widget']
        );
    }

    public function render_sysinfo_widget()
    {
        global $wpdb;

        $php_version = phpversion();
        $wp_version = get_bloginfo('version');
        $memory_limit = ini_get('memory_limit');
        $wp_memory = WP_MEMORY_LIMIT;
        $max_upload = size_format(wp_max_upload_size());
        $server_soft = sanitize_text_field($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');
        $mysql_version = $wpdb->db_version();
        $active_plugins = count((array) get_option('active_plugins', []));

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px">';

        $stats = [
            ['label' => __('WordPress', 'wp-extra'), 'value' => ltrim($wp_version, 'vV'), 'icon' => 'dashicons-wordpress'],
            ['label' => __('PHP Version', 'wp-extra'), 'value' => ltrim($php_version, 'vV'), 'icon' => 'dashicons-editor-code'],
            ['label' => __('MySQL / MariaDB', 'wp-extra'), 'value' => ltrim($mysql_version, 'vV'), 'icon' => 'dashicons-database'],
            ['label' => __('WP Memory Limit', 'wp-extra'), 'value' => $wp_memory, 'icon' => 'dashicons-performance'],
            ['label' => __('PHP Memory Limit', 'wp-extra'), 'value' => $memory_limit, 'icon' => 'dashicons-chart-pie'],
            ['label' => __('Max Upload Size', 'wp-extra'), 'value' => $max_upload, 'icon' => 'dashicons-upload'],
            ['label' => __('Active Plugins', 'wp-extra'), 'value' => $active_plugins . ' ' . __('Plugins', 'wp-extra'), 'icon' => 'dashicons-admin-plugins'],
            ['label' => __('Web Server', 'wp-extra'), 'value' => explode('/', $server_soft)[0] ?? 'Server', 'icon' => 'dashicons-networking'],
        ];

        foreach ($stats as $stat) {
            echo '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 8px;text-align:center;">';
            echo '<span class="dashicons ' . esc_attr($stat['icon']) . '" style="color:#2271b1;font-size:20px;width:20px;height:20px;margin-bottom:4px;display:inline-block;"></span>';
            echo '<div style="font-size:11px;color:#64748b;font-weight:500;">' . esc_html($stat['label']) . '</div>';
            echo '<div style="font-size:13px;font-weight:600;color:#1e293b;margin-top:2px;">' . esc_html($stat['value']) . '</div>';
            echo '</div>';
        }

        echo '</div>';
    }

    public function tab_help()
    {
        add_filter('admin_head', [$this, 'remove_help_tabs']);
    }

    public function remove_help_tabs()
    {
        $screen = get_current_screen();
        if ($screen) {
            $screen->remove_help_tabs();
        }
    }

    public function tab_screen()
    {
        add_filter('screen_options_show_screen', '__return_false');
    }
}
