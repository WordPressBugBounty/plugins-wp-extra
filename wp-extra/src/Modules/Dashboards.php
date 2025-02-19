<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class Dashboards extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'dashboard',
		'dashboard_welcome',
		'tab_help',
		'tab_screen',
	];
    
    public function dashboard() {
        add_action('wp_dashboard_setup', [$this, 'remove_dashboard']);
        add_action('admin_enqueue_scripts', [$this, 'full_dashboard']);
        if ( !function_exists( 'wpforms' ) ) {
            add_filter('wpforms_admin_dashboardwidget', '__return_false' );
        }
    }

    public function remove_dashboard() {
        global $wp_meta_boxes;
        unset($wp_meta_boxes['dashboard']);
        remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'side');
        if (class_exists('WooCommerce')) {
            remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
            remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
        }
    }

    public function full_dashboard($hook) {
        if ($hook === 'index.php') {
            echo '<style type="text/css">#dashboard-widgets-wrap {overflow: unset !important;}.postbox-container{min-width: 100% !important;}.meta-box-sortables.ui-sortable.empty-container,.wrap > h1{display: none;}</style>';
        }
    }
    
    public function dashboard_welcome() {
        remove_action('welcome_panel', 'wp_welcome_panel');
        add_action('wp_dashboard_setup', [$this, 'add_dashboard']);
    }

    public function add_dashboard() {
        $dashboardTitle = Settings::get_option('dashboard_title') ?: sprintf(__('This notice was triggered by the %s handle.'), 'WP EXtra');
        wp_add_dashboard_widget('notice_widget', $dashboardTitle, [$this, 'add_dashboard_content']);
    }
    
    public function add_dashboard_content() {
        $rss_feed_url = Settings::get_option('dashboard_rss_feed');
        $dashboard_content = Settings::get_option('dashboard_content');
        if ($dashboard_content === null) {
            $dashboard_content = '';
        }
        $dashboard_content = apply_filters('the_content', wp_kses_post($dashboard_content));
        $content = "<div id='activity-widget'><div id='published-posts' class='activity-block'>";
        $content .= wp_kses_post($dashboard_content);
        if ($rss_feed_url) {
            $content .= "<ul>";
            libxml_use_internal_errors(true);
            $xml = simplexml_load_file($rss_feed_url);

            if ($xml !== false) {
                $in = 1;
                foreach ($xml->channel->item as $entry) {
                    $formattedDate = date("d/m/Y H:i", strtotime($entry->pubDate));
                    if ($in <= 10) {
                        $content .= "<li><span>" . esc_html($formattedDate) . "</span> <a href='" . esc_url($entry->link) . "' title='" . esc_attr($entry->title) . "' target='_blank'>" . esc_html($entry->title) . "</a></li>";
                        $in++;
                    }
                }
            } else {
                $content .= "<li>" . esc_html__('RSS Error') . "</li>";
            }
            libxml_clear_errors();
            $content .= "</ul>";
        }
        $content .= "</div></div>";
        echo wp_kses_post($content);
    }
    
    public function tab_help() {
        add_filter( 'admin_head', [$this, 'remove_help_tabs']);
    }
    
    public function remove_help_tabs() {
        $screen = get_current_screen();
        $screen->remove_help_tabs();
    }
    
    public function tab_screen() {
        add_filter( 'screen_options_show_screen', '__return_false' );
    }
}
