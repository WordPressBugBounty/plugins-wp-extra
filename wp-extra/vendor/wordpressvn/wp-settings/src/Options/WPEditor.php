<?php

namespace WPVNTeam\WPSettings\Options;

class WPEditor extends OptionAbstract
{
    public $view = 'wp-editor';

    public function sanitize($value)
    {
        return wp_kses_post($value);
    }
}
