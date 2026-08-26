<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Enqueuer;

class Repeater extends OptionAbstract
{
    public $view = 'repeater';

    public function enqueue()
    {
        Enqueuer::add('wps-repeater', function () {
            wp_enqueue_editor();
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('wp-settings');
        });
    }

    public function sanitize($value)
    {
        if (!is_array($value)) {
            return [];
        }

        $sanitized = [];
        $fields = $this->get_arg('fields', []);

        foreach ($value as $row_index => $row_data) {
            if (!is_array($row_data) || $row_index === '__INDEX__') {
                continue;
            }
            $clean_row = [];
            foreach ($fields as $field) {
                $f_name = $field['name'] ?? '';
                $f_type = $field['type'] ?? 'text';
                $f_val = $row_data[$f_name] ?? '';

                if ($f_type === 'url') {
                    $clean_row[$f_name] = esc_url_raw($f_val);
                } elseif ($f_type === 'email') {
                    $clean_row[$f_name] = sanitize_email($f_val);
                } elseif ($f_type === 'number') {
                    $clean_row[$f_name] = is_numeric($f_val) ? floatval($f_val) : 0;
                } elseif (in_array($f_type, ['textarea', 'editor', 'wp-editor', 'wp_editor', 'html'], true)) {
                    $clean_row[$f_name] = wp_kses_post($f_val);
                } else {
                    $clean_row[$f_name] = sanitize_text_field($f_val);
                }
            }
            if (!empty(array_filter($clean_row))) {
                $sanitized[] = $clean_row;
            }
        }

        return array_values($sanitized);
    }
}
