<?php

namespace WPVNTeam\WPSettings\Options;

class Widget extends OptionAbstract
{
    public $view = 'widget';
    
    public function get_name_attribute()
    {
        $name = parent::get_name_attribute();

        return "{$name}[]";
    }

    public function sanitize($value)
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_map('sanitize_text_field', $value));
    }
    
    public function use_widgets_block_editor()
    {
        if (function_exists('wp_use_widgets_block_editor')) {
            return wp_use_widgets_block_editor();
        }
        return false;
    }
    
    public function get_widgets_to_hide_from_legacy_widget_block()
    {
        if (function_exists('get_legacy_widget_block_editor_settings')) {
            return get_legacy_widget_block_editor_settings()['widgetTypesToHideFromLegacyWidgetBlock'];
        }
        return [];
    }
}
