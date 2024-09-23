<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Enqueuer;

class Password extends OptionAbstract
{
    public $view = 'password';
    
    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }

    public function sanitize($value)
    {
        return base64_encode($value);
    }

    public function enqueue()
    {
        Enqueuer::add('user-profile', function () {
            wp_enqueue_script('user-profile', admin_url('js/user-profile.min.js'), array('jquery'), null, true);
        });
    }

}
