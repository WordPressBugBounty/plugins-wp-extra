<?php

namespace WPEXtra\Modules;

class Dashboards {
    public function __construct() {
        add_action('wp_dashboard_setup', [$this, 'setupDashboard']);
        if (wp_extra_get_option('dashboard')) {
            add_filter( 'wpforms_admin_dashboardwidget', '__return_false' );
        }

        add_action('admin_enqueue_scripts', [$this, 'enqueueCustomDashboardWidgetStyles']);
        add_action('admin_enqueue_scripts', [$this, 'applyCustomAdminCss']);
    }

    public function setupDashboard() {
        if (wp_extra_get_option('dashboard')) {
            $this->removeDashboardWidgets();
        }

        if (wp_extra_get_option('dashboard_welcome')) {
            remove_action('welcome_panel', 'wp_welcome_panel');
            $this->addCustomDashboardWidgets();
        }
    }

    public function removeDashboardWidgets() {
        global $wp_meta_boxes;
        unset($wp_meta_boxes['dashboard']);
        remove_meta_box('wpseo-dashboard-overview', 'dashboard', 'side');
    }

    public function enqueueCustomDashboardWidgetStyles($hook) {
        if ($hook === 'index.php' && wp_extra_get_option('dashboard')) {
            echo '<style type="text/css">#dashboard-widgets-wrap {overflow: unset !important;}.postbox-container{min-width: 100% !important;}.meta-box-sortables.ui-sortable.empty-container,.wrap > h1{display: none;}</style>';
        }
    }

    public function addCustomDashboardWidgets() {
        $dashboardTitle = wp_extra_get_option('dashboard_title');
        if (empty($dashboardTitle)) {
            // Translators: %s is a placeholder for the name of the plugin.
            $dashboardTitle = sprintf(__('This notice was triggered by the %s handle.'), 'WP EXtra');
        }
        wp_add_dashboard_widget('notice_widget', $dashboardTitle, [$this, 'displayCustomDashboardWidgetContent']);
    }
    
    public function displayCustomDashboardWidgetContent()
    {
        $rss_feed_url = wp_extra_get_option('dashboard_rss_feed');
        $dashboard_content = apply_filters('the_content', wp_kses_post(wp_extra_get_option('dashboard_content')));
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


    public function applyCustomAdminCss() {
        $tabhelp = wp_extra_get_option('tab_help');
        $tabscreen = wp_extra_get_option('tab_screen');
    
        if ($tabhelp || $tabscreen) {
            $cssHelp = [];
    
            if ($tabhelp) {
                $cssHelp[] = '#contextual-help-link-wrap { display: none; }';
            }
    
            if ($tabscreen) {
                $cssHelp[] = '#screen-options-link-wrap { display: none; }';
            }
    
            if (!empty($cssHelp)) {
                echo '<style type="text/css">' . esc_attr(implode(' ', $cssHelp)) . '</style>';
            }
        }
    }
}
