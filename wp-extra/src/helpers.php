<?php

if (!function_exists('wp_extra_get_option')) {
    function wp_extra_get_option($key, $fallback = null) {
        $options = get_option('wp_extra', []);
        return array_key_exists($key, $options) && $options[$key] !== '' ? $options[$key] : $fallback;
    }
}

if (!function_exists('wp_extra_key')) {
    function wp_extra_key() {
        return wp_extra_get_option('license_status') === 'valid';
    }
}

/* if(!function_exists('wp_extra_view')) {
    function wp_extra_view($view, $data = [], $buffer = false)
    {
        $file = plugin_dir_path(WPEX_FILE) . 'views/' . $view . '.php';
        if (! file_exists($file)) {
            return;
        }
        extract($data);
        if($buffer) {
            ob_start();
        }
        include $file;
        if($buffer) {
            return ob_get_clean();
        }
    }
} */