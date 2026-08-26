<?php

namespace WPVNTeam\WPSettings;

use Adbar\Dot;

class Helper
{
    /**
     * In-memory cache for raw option arrays to avoid redundant DB reads.
     *
     * @var array
     */
    private static $cache = [];

    /**
     * In-memory cache for Dot instances.
     *
     * @var array
     */
    private static $dot_cache = [];

    /**
     * Flush in-memory cache for an option or all options.
     *
     * @param string|null $option_name
     * @return void
     */
    public static function flush_cache($option_name = null)
    {
        if ($option_name !== null) {
            unset(self::$cache[$option_name], self::$dot_cache[$option_name]);
        } else {
            self::$cache = [];
            self::$dot_cache = [];
        }
    }

    /**
     * Get an option value with dot-notation support and in-memory caching.
     *
     * Examples:
     *   Helper::get('wp_extra', 'modules', [])
     *   Helper::get('wp_extra.smtp_accounts.0.host', 'smtp.gmail.com')
     *   Helper::get('wp_extra')
     *
     * @param string      $target   Option name or full dot path (e.g. 'wp_extra.smtp.host').
     * @param string|null $key      Nested key if $target is option name.
     * @param mixed       $default  Fallback value if not found.
     * @return mixed
     */
    public static function get($target, $key = null, $default = null)
    {
        if ($key === null && strpos($target, '.') !== false) {
            $parts = explode('.', $target, 2);
            $option_name = $parts[0];
            $key_path = $parts[1];
        } else {
            $option_name = $target;
            $key_path = $key;
        }

        if ($key_path === null || $key_path === '') {
            $options = self::get_all($option_name);
            return !empty($options) ? $options : $default;
        }

        if (!isset(self::$dot_cache[$option_name])) {
            $options = self::get_all($option_name);
            self::$dot_cache[$option_name] = new Dot($options);
        }

        return self::$dot_cache[$option_name]->get($key_path, $default);
    }

    /**
     * Get all options array for a given option_name with auto-repair and RAM cache.
     *
     * @param string $option_name
     * @return array
     */
    public static function get_all($option_name)
    {
        if (isset(self::$cache[$option_name])) {
            return self::$cache[$option_name];
        }

        $options = get_option($option_name, null);

        if (is_array($options) && !empty($options)) {
            return self::$cache[$option_name] = $options;
        }

        // Automatic repair fallback for serialized strings damaged during site migrations
        if ($options === false || $options === null || empty($options)) {
            $repaired = self::repair($option_name);
            if (!empty($repaired)) {
                return self::$cache[$option_name] = $repaired;
            }
        }

        return self::$cache[$option_name] = (is_array($options) ? $options : []);
    }

    /**
     * Set a nested option value without overwriting sibling keys.
     *
     * Examples:
     *   Helper::set('wp_extra', 'smtp.host', 'smtp.mailgun.org')
     *   Helper::set('wp_extra.modules', ['security', 'smtp'])
     *
     * @param string      $target Option name or full dot path.
     * @param string|null $key    Nested key path.
     * @param mixed       $value  Value to set.
     * @return bool
     */
    public static function set($target, $key = null, $value = null)
    {
        if ($value === null && strpos($target, '.') !== false) {
            $parts = explode('.', $target, 2);
            $option_name = $parts[0];
            $key_path = $parts[1];
            $val_to_set = $key;
        } else {
            $option_name = $target;
            $key_path = $key;
            $val_to_set = $value;
        }

        $options = self::get_all($option_name);
        $dot = new Dot($options);

        if ($key_path === null || $key_path === '') {
            if (is_array($val_to_set)) {
                $result = update_option($option_name, $val_to_set);
                self::flush_cache($option_name);
                return $result;
            }
            return false;
        }

        $dot->set($key_path, $val_to_set);
        $updated_data = $dot->all();
        $result = update_option($option_name, $updated_data);

        self::flush_cache($option_name);
        return $result;
    }

    /**
     * Check if a specific option key exists and is non-empty.
     *
     * @param string      $target
     * @param string|null $key
     * @return bool
     */
    public static function has($target, $key = null)
    {
        $val = self::get($target, $key);
        return !empty($val) && $val !== '0';
    }

    /**
     * Delete a specific key from the options array.
     *
     * @param string      $target
     * @param string|null $key
     * @return bool
     */
    public static function delete($target, $key = null)
    {
        if ($key === null && strpos($target, '.') !== false) {
            $parts = explode('.', $target, 2);
            $option_name = $parts[0];
            $key_path = $parts[1];
        } else {
            $option_name = $target;
            $key_path = $key;
        }

        if ($key_path === null || $key_path === '') {
            $result = delete_option($option_name);
            self::flush_cache($option_name);
            return $result;
        }

        $options = self::get_all($option_name);
        $dot = new Dot($options);
        $dot->delete($key_path);

        $result = update_option($option_name, $dot->all());
        self::flush_cache($option_name);
        return $result;
    }

    /**
     * Resolve image URL from attachment ID, relative path (/wp-content/...), or external URL.
     *
     * @param mixed  $value
     * @param string $default
     * @return string
     */
    public static function image($value, $default = '')
    {
        if (empty($value)) {
            return $default;
        }

        if (is_numeric($value)) {
            $url = wp_get_attachment_url((int) $value);
            return $url ?: $default;
        }

        if (is_string($value)) {
            if (strpos($value, '/') === 0) {
                return home_url($value);
            }
            return esc_url($value);
        }

        return $default;
    }

    /**
     * Lightweight CSS Minifier.
     *
     * @param string $css
     * @return string
     */
    public static function minify_css($css)
    {
        if (empty($css) || !is_string($css)) {
            return '';
        }

        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Remove space after colons
        $css = str_replace(': ', ':', $css);
        // Remove whitespaces and newlines
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    '], '', $css);

        return trim($css);
    }

    /**
     * Repair serialized database strings corrupted during domain search-replace.
     *
     * @param string $option_name
     * @return array|null
     */
    public static function repair($option_name)
    {
        global $wpdb;
        if (empty($wpdb) || !is_object($wpdb) || empty($wpdb->options)) {
            return null;
        }
        $raw = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $option_name));

        if (!empty($raw) && is_string($raw)) {
            $repaired = preg_replace_callback('!s:(\d+):"(.*?)";!s', function ($m) {
                return 's:' . strlen($m[2]) . ':"' . $m[2] . '";';
            }, $raw);

            $unserialized = @unserialize($repaired);
            if (is_array($unserialized) && !empty($unserialized)) {
                update_option($option_name, $unserialized);
                return $unserialized;
            }
        }

        return null;
    }
}
