<?php

namespace WPVNTeam\WPSettings\Options;

class Module extends OptionAbstract
{
    public $view = 'module';

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

        return array_values(array_map('sanitize_key', $value));
    }
}
