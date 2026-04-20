<?php

namespace WPVNTeam\WPSettings\Options;

class CodeEditor extends OptionAbstract
{
    public $view = 'code-editor';
    
    private static $scripts_loaded = false;

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }

    public function enqueue()
    {
        if (!self::$scripts_loaded) {
            self::$scripts_loaded = true;
            wp_enqueue_style('wp-codemirror');
            wp_enqueue_script('wp-theme-plugin-editor');

            wp_enqueue_script(
                'wp-settings-code-editor',
                plugin_dir_url(__FILE__) . '../../resources/js/wp-settings-code-editor.js',
                ['wp-theme-plugin-editor'],
                false,
                true
            );
        }
    }

    public function get_editor_config()
    {
        return wp_enqueue_code_editor([
            'type' => $this->get_arg('editor_type', 'text/html'),
            'codemirror' => [
                'autoRefresh'   => true,
                'mode'          => 'htmlmixed',
                'indentWithTabs'=> false,
                'tabSize'       => 2,
            ]
        ]);
    }

    public function sanitize($value)
    {
        return $value;
    }
}