<?php

namespace WPEXtra\Modules;

class Widgets {
    
    public function __construct() {
        add_action('widgets_init', [ $this, 'disable_sidebar_widgets' ], 100);
    }
    
    public function disable_sidebar_widgets() {
        if (isset($_GET['page']) && $_GET['page'] === 'wp-extra') {
            return;
        }

        $widgets = wp_extra_get_option('disable_widget');

        if (is_array($widgets) && !empty($widgets)) {
            foreach ($widgets as $widget_class) {
                if (class_exists($widget_class)) {
                    unregister_widget($widget_class);
                }
            }
        }
    }
}
