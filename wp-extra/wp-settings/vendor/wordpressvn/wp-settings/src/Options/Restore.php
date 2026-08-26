<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Helper;

class Restore extends OptionAbstract
{
    public $view = 'restore';

    public function __construct($section, $args = [])
    {
        self::boot();
        parent::__construct($section, $args);
    }

    public static function boot()
    {
        static $booted = false;
        if ($booted) {
            return;
        }
        $booted = true;

        add_action('admin_post_wp_settings_export_backup', [self::class, 'handle_export_json']);
        add_action('admin_post_wpex_export_backup', [self::class, 'handle_export_json']);
        add_action('wp_ajax_wp_settings_import_backup', [self::class, 'handle_ajax_import_json']);
        add_action('wp_ajax_wpex_import_backup', [self::class, 'handle_ajax_import_json']);
    }

    public function get_option_name()
    {
        return $this->section->tab->settings->option_name ?? 'wp_settings';
    }

    public static function handle_export_json()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.'));
        }

        $nonce = $_GET['_wpnonce'] ?? '';
        $verified = wp_verify_nonce($nonce, 'wp_settings_export_backup_nonce') || wp_verify_nonce($nonce, 'wpex_export_backup_nonce');

        if (!$verified) {
            wp_die(esc_html__('Security verification failed. Please refresh the page and try again.'));
        }

        $option_name = sanitize_key($_GET['option_name'] ?? 'wp_settings');
        $settings = get_option($option_name, []);

        $payload = [
            'generator'   => 'WPSettings',
            'version'     => '2.8.2',
            'option_name' => $option_name,
            'exported_at' => current_time('mysql'),
            'site_url'    => home_url(),
            'data'        => $settings,
        ];

        $raw_json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Base64 Encoded Export String
        $export_data = base64_encode($raw_json);
        $filename = sanitize_title($option_name) . '-backup-' . gmdate('Y-m-d-His') . '.json';

        // Clear output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $export_data;
        exit;
    }

    protected static function sanitize_recursive($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $k => $v) {
                $cleaned_key = sanitize_text_field((string)$k);
                $cleaned[$cleaned_key] = self::sanitize_recursive($v);
            }
            return $cleaned;
        }

        if (is_string($data)) {
            return wp_unslash($data);
        }

        return $data;
    }

    public static function handle_ajax_import_json()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.')]);
        }

        $nonce = $_POST['nonce'] ?? ($_POST['_wpnonce'] ?? '');
        $verified = wp_verify_nonce($nonce, 'wp_settings_transfer_backup_nonce') || wp_verify_nonce($nonce, 'wpex_transfer_backup_nonce');

        if (!$verified) {
            wp_send_json_error(['message' => __('Security verification failed. Please refresh the page and try again.')]);
        }

        if (empty($_FILES['backup_file']) || empty($_FILES['backup_file']['tmp_name'])) {
            wp_send_json_error(['message' => __('Please select a valid .json backup file.')]);
        }

        $file = $_FILES['backup_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('File upload failed. Error code: ' . $file['error'])]);
        }

        // Limit file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(['message' => __('Uploaded file is too large. Maximum allowed size is 5MB.')]);
        }

        $content = @file_get_contents($file['tmp_name']);
        if (empty($content)) {
            wp_send_json_error(['message' => __('Uploaded file is empty.')]);
        }

        $content = trim($content);
        $decoded = null;

        // 1. Try decrypting Base64 encoded payload first
        $raw_decoded = base64_decode($content, true);
        if ($raw_decoded !== false && is_string($raw_decoded)) {
            $decoded = json_decode($raw_decoded, true);
        }

        // 2. Fallback to direct raw JSON
        if (!is_array($decoded)) {
            $decoded = json_decode($content, true);
        }

        // 3. Fallback to nested payload key
        if (isset($decoded['payload']) && is_string($decoded['payload'])) {
            $sub_decoded = base64_decode($decoded['payload'], true);
            if ($sub_decoded !== false) {
                $decoded = json_decode($sub_decoded, true);
            }
        }

        if (!is_array($decoded)) {
            wp_send_json_error(['message' => __('Invalid or corrupted backup file format.')]);
        }

        $option_name = sanitize_key($_POST['option_name'] ?? 'wp_settings');
        $settings_data = null;

        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $settings_data = $decoded['data'];
        } elseif (isset($decoded['settings']) && is_array($decoded['settings'])) {
            $settings_data = $decoded['settings'];
        } elseif (isset($decoded[$option_name]) && is_array($decoded[$option_name])) {
            $settings_data = $decoded[$option_name];
        } elseif (!isset($decoded['generator']) && is_array($decoded)) {
            $settings_data = $decoded;
        }

        if (!is_array($settings_data)) {
            wp_send_json_error(['message' => __('Could not find valid settings data in the uploaded file.')]);
        }

        $clean_data = self::sanitize_recursive($settings_data);

        update_option($option_name, $clean_data);
        Helper::flush_cache($option_name);

        wp_send_json_success([
            'message' => __('Settings imported successfully! Reloading page...')
        ]);
    }
}
