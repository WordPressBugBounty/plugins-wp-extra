<?php

namespace WPVNTeam\WPSettings\Options;

class Textarea extends OptionAbstract
{
    public $view = 'textarea';

    public function sanitize($value)
    {
        return wp_unslash(sanitize_textarea_field($value));
    }
}
