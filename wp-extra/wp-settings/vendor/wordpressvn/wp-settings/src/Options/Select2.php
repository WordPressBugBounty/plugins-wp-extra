<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Enqueuer;

class Select2 extends OptionAbstract
{
    public $view = 'select2';
    
    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);
        
        parent::__construct($section, $args);
    }

    public function get_name_attribute()
    {
        $name = parent::get_name_attribute();

        return "{$name}[]";
    }

    public function sanitize($value)
    {
        return (array) $value;
    }

    public function enqueue()
    {
        Enqueuer::add('wps-select2', function () {
            $select2_assets = apply_filters('wps_select2_assets', [
                'js' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js',
                'css' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css'
            ]);

            wp_enqueue_style('wp-select2', $select2_assets['css']);
            wp_enqueue_script('wp-select2', $select2_assets['js'], ['jquery']);

            wp_add_inline_script('wp-select2', 'jQuery(function($){$(\'.select2\').select2();})'); 
        });
    }
}
