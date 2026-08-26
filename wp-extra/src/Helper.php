<?php

namespace WPEXtra;

if (!defined('ABSPATH')) {
    exit;
}

use WPVNTeam\WPSettings\Helper as BaseHelper;

class Helper extends BaseHelper
{
    /**
     * Shorthand to get WP Extra option with dot notation support.
     *
     * Example: Helper::get('smtp_accounts.0.host')
     */
    public static function get($target = 'wp_extra', $key = null, $default = null)
    {
        if ($key === null && strpos($target, '.') === false && $target !== 'wp_extra') {
            return parent::get('wp_extra', $target, $default);
        }
        return parent::get($target, $key, $default);
    }

    /**
     * Backward-compatible get_option wrapper.
     */
    public static function get_option($key, $fallback = null)
    {
        $value = self::get('wp_extra', $key, $fallback);
        $array_keys = ['smtp_options', 'no_emails', 'smtp_accounts', 'modules'];
        if (is_array($fallback) || in_array($key, $array_keys, true)) {
            return (array) $value;
        }
        return $value;
    }

    /**
     * Backward-compatible get_all_options wrapper.
     */
    public static function get_all_options()
    {
        return parent::get_all('wp_extra');
    }

    /**
     * Backward-compatible image URL resolver.
     */
    public static function get_image_url($key, $fallback = '')
    {
        $val = self::get_option($key, $fallback);
        return parent::image($val, $fallback);
    }

    /**
     * Check if a specific WP Extra feature is active.
     */
    public static function is_feature_active($key)
    {
        return parent::has('wp_extra', $key);
    }

    /**
     * Minify CSS string.
     */
    public static function minifyCSS($css)
    {
        return parent::minify_css($css);
    }

    /**
     * Check if WooCommerce is active.
     */
    public static function is_woo_active()
    {
        return class_exists('WooCommerce') || in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', (array) get_option('active_plugins', [])));
    }

    /**
     * Normalize string for SEO friendly filenames.
     */
    public static function normalizeString($str = '')
    {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode($str, ENT_QUOTES, 'utf-8');
        $str = htmlentities($str, ENT_QUOTES, 'utf-8');
        $str = preg_replace('/(&)([a-z])([a-z]+;)/i', '$2', $str);
        $str = str_replace(' ', '-', $str);
        $str = rawurlencode($str);
        $str = str_replace(['%', '.jpeg'], ['-', '.jpg'], $str);
        return $str;
    }
}
