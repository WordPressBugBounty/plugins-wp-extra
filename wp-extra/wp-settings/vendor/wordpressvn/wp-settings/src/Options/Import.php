<?php

namespace WPVNTeam\WPSettings\Options;

class Import extends OptionAbstract
{
    public $view = 'import';

    private static $enqueued_elements = [];

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);
        add_action('wp_ajax_wpex_save_disk_file', [$this, 'ajax_save_file']);
        add_action('wp_ajax_wpex_reload_disk_file', [$this, 'ajax_reload_file']);
        add_action('wp_ajax_wpex_restore_disk_file', [$this, 'ajax_restore_file']);
        add_action('wp_ajax_wp_settings_save_disk_file', [$this, 'ajax_save_file']);
        add_action('wp_ajax_wp_settings_reload_disk_file', [$this, 'ajax_reload_file']);
        add_action('wp_ajax_wp_settings_restore_disk_file', [$this, 'ajax_restore_file']);

        parent::__construct($section, $args);
    }

    public function get_file_path()
    {
        $desc = $this->get_arg('description') ?: $this->get_arg('file');
        $safe_rel_path = trim(str_replace(['../', '..\\', chr(0)], '', (string) $desc));
        $safe_rel_path = ltrim($safe_rel_path, '/\\');

        return [
            'rel' => $safe_rel_path,
            'abs' => wp_normalize_path(ABSPATH . $safe_rel_path)
        ];
    }

    public function is_allowed_file($rel_path)
    {
        $rel_path = ltrim(wp_normalize_path($rel_path), '/\\');
        $allowed = apply_filters('wp_settings_allowed_disk_files', ['.htaccess', 'robots.txt']);

        if (!in_array($rel_path, $allowed, true)) {
            return false;
        }

        // Strict Path Traversal Prevention
        $target_abs = wp_normalize_path(ABSPATH . $rel_path);
        $abspath_norm = wp_normalize_path(ABSPATH);

        if (strpos($target_abs, $abspath_norm) !== 0) {
            return false;
        }

        return true;
    }

    protected function verify_ajax_auth()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized: You do not have permission to manage options.'));
        }

        $nonce = $_POST['_nonce'] ?? ($_POST['nonce'] ?? '');
        $verified = wp_verify_nonce($nonce, 'wp_settings_disk_file_nonce') || wp_verify_nonce($nonce, 'wpex_reload_disk_file');

        if (!$verified) {
            wp_send_json_error(__('Security verification failed. Please refresh the page and try again.'));
        }
    }

    public function ajax_save_file()
    {
        $this->verify_ajax_auth();

        $rel_path = sanitize_text_field($_POST['filepath'] ?? '');
        $safe_rel_path = ltrim(str_replace(['../', '..\\', chr(0)], '', $rel_path), '/\\');

        if (!$this->is_allowed_file($safe_rel_path)) {
            wp_send_json_error(__('Invalid or unauthorized file target.'));
        }

        $file_path = wp_normalize_path(ABSPATH . $safe_rel_path);
        $backup_path = $file_path . '.bak';
        $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';

        // Safe Backup Creation before overwriting
        if (file_exists($file_path)) {
            $old_content = @file_get_contents($file_path);
            if ($old_content !== false && $old_content !== '' && $old_content !== $content) {
                @file_put_contents($backup_path, $old_content);
            }
        }

        $res = @file_put_contents($file_path, $content);
        if ($res !== false) {
            wp_send_json_success([
                'message' => __('Saved successfully to file.'),
                'has_backup' => file_exists($backup_path)
            ]);
        } else {
            wp_send_json_error(__('Could not write to file. Please check server directory write permissions.'));
        }
    }

    public function ajax_reload_file()
    {
        $this->verify_ajax_auth();

        $rel_path = sanitize_text_field($_POST['filepath'] ?? '');
        $safe_rel_path = ltrim(str_replace(['../', '..\\', chr(0)], '', $rel_path), '/\\');

        if (!$this->is_allowed_file($safe_rel_path)) {
            wp_send_json_error(__('Invalid or unauthorized file target.'));
        }

        $file_path = wp_normalize_path(ABSPATH . $safe_rel_path);

        if (file_exists($file_path)) {
            $content = @file_get_contents($file_path);
            wp_send_json_success(['content' => $content !== false ? $content : '']);
        } else {
            wp_send_json_error(__('File does not exist on disk yet.'));
        }
    }

    public function ajax_restore_file()
    {
        $this->verify_ajax_auth();

        $rel_path = sanitize_text_field($_POST['filepath'] ?? '');
        $safe_rel_path = ltrim(str_replace(['../', '..\\', chr(0)], '', $rel_path), '/\\');

        if (!$this->is_allowed_file($safe_rel_path)) {
            wp_send_json_error(__('Invalid or unauthorized file target.'));
        }

        $file_path = wp_normalize_path(ABSPATH . $safe_rel_path);
        $backup_path = $file_path . '.bak';

        if (file_exists($backup_path)) {
            $content = @file_get_contents($backup_path);
            $time_str = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($backup_path));

            if ($content !== false) {
                @file_put_contents($file_path, $content);
            }
            @unlink($backup_path);

            wp_send_json_success([
                'content' => $content !== false ? $content : '',
                'message' => sprintf(__('Successfully restored backup from %s and cleaned up the temporary backup file.'), $time_str)
            ]);
        } else {
            wp_send_json_error(__('No backup file found.'));
        }
    }

    public function enqueue()
    {
        $element_id = $this->get_id_attribute();

        if (isset(self::$enqueued_elements[$element_id])) {
            return;
        }
        self::$enqueued_elements[$element_id] = true;

        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');

        $settings_name = str_replace('-', '_', $element_id);

        $editor_settings = wp_enqueue_code_editor([
            'type' => $this->get_arg('editor_type', 'text/plain'),
            'codemirror' => [
                'autoRefresh' => true,
                'mode' => 'text/plain',
                'lineNumbers' => true,
                'lineWrapping' => true,
                'indentWithTabs' => false,
                'tabSize' => 2,
            ]
        ]);

        wp_localize_script('jquery', $settings_name, $editor_settings);

        wp_add_inline_script('wp-theme-plugin-editor', 'jQuery(function($){
            var el = document.getElementById("' . $element_id . '");
            if (el && typeof wp !== "undefined" && wp.codeEditor) {
                if ($(el).data("codemirrorInstance") || $(el).next(".CodeMirror").length) {
                    return;
                }
                var inst = wp.codeEditor.initialize(el, ' . $settings_name . ');
                if (inst && inst.codemirror) {
                    $(el).data("codemirrorInstance", inst.codemirror);
                    inst.codemirror.on("change", function(cm){
                        cm.save();
                    });
                }
            }
        });');
    }

    public function sanitize($value)
    {
        $path_info = $this->get_file_path();
        $import_path = $path_info['abs'];
        $backup_path = $import_path . '.bak';
        $import_content = wp_unslash((string) $value);

        if ($import_content !== '') {
            if (file_exists($import_path)) {
                $old_content = @file_get_contents($import_path);
                if ($old_content !== false && $old_content !== '' && $old_content !== $import_content) {
                    @file_put_contents($backup_path, $old_content);
                }
            }
            @file_put_contents($import_path, $import_content);
        }
        return $value;
    }
}
