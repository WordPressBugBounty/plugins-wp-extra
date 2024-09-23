<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Enqueuer;

class CheckboxMultiple extends OptionAbstract
{
    public $view = 'checkbox-multiple';

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
        Enqueuer::add('wps-checkbox-multiple', function () {
            wp_enqueue_script('wp-settings');
            wp_add_inline_script('wp-settings', "
                jQuery(function($) {
                    $('.select-all').on('click', function() {
                        $(this).closest('td').find('input[type=\"checkbox\"]').prop('checked', true);
                    });
                    $('.deselect').on('click', function() {
                        $(this).closest('td').find('input[type=\"checkbox\"]').prop('checked', false);
                    });
                });
            ");
        });
    }
}
