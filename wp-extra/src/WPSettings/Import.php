<?php

namespace WPEXtra\WPSettings;

use WPVNTeam\WPSettings\Options\OptionAbstract;

class Import extends OptionAbstract
{
    public $view = 'import';

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);
        parent::__construct($section, $args);
    }

    public function enqueue()
    {
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');

        $settings_name = str_replace('-', '_', $this->get_id_attribute());

        wp_localize_script('jquery', $settings_name, wp_enqueue_code_editor(['type' => $this->get_arg('editor_type', 'text/nginx')]));

        wp_add_inline_script('wp-theme-plugin-editor', 'jQuery(function($){
            if($("#'.$this->get_id_attribute().'").length > 0) {
                wp.codeEditor.initialize($("#'.$this->get_id_attribute().'"), '.$settings_name.');
            }
        });');
    }

    public function sanitize($value)
    {
        return $value;
    }
    
    public function render()
    {
        $import_path = ABSPATH . $this->get_arg('description');
        if (!empty($_POST[$this->get_id_attribute()]) && isset($_POST[$this->section->tab->settings->option_name][$this->get_name()])) {
            $import_content = trim(stripslashes($_POST[$this->section->tab->settings->option_name][$this->get_name()]));
            if ($import_content) {
                $operation_result = @file_put_contents($import_path, $import_content);
            } else {
                $import_backup = $this->get_arg('default');

                if ($import_backup) {
                    $operation_result = @file_put_contents($import_path, $import_backup);
                } else {
                    $operation_result = @unlink($import_path);
                }
            }
            if ($operation_result !== false) {
                add_settings_error(
                    $this->get_name(),
                    'file-operation-success',
                    __( 'Request added successfully.' ),
                    'notice-success is-dismissible'
                );
            } else {
                add_settings_error(
                    $this->get_name(),
                    'file-operation-error',
                    __( 'No importers are available.' ),
                    'notice-error is-dismissible'
                );
            }
        }
        settings_errors($this->get_name());
        ?>
        <tr valign="top" class="<?php echo $this->get_hide_class_attribute(); ?>">
            <th scope="row" class="titledesc">
                <label for="<?php echo $this->get_id_attribute(); ?>" class="<?php echo $this->get_label_class_attribute(); ?>"><?php echo $this->get_label(); ?>
                <?php if($link = $this->get_arg('link')) { ?>
                    <a target="_blank" href="<?php echo esc_url($link); ?>" tooltip="<?php _e('Help'); ?>"><span class="dashicons dashicons-editor-help"></span></a>
                <?php } ?>
                </label>
            </th>
            <td class="forminp forminp-text">
                <textarea name="<?php echo esc_attr($this->get_name_attribute()); ?>" id="<?php echo $this->get_id_attribute(); ?>" class="wp-settings-code-editor <?php echo $this->get_input_class_attribute(); ?>"><?php echo wp_unslash($this->get_value_attribute()); ?></textarea>
                <?php if ($description = $this->get_arg('description')) { ?>
                    <p class="description"><?php echo $description; ?></p>
                <?php } ?>
                    <p><input type="submit" name="<?php echo $this->get_id_attribute(); ?>" class="button" value="<?php _e('Import'); ?>"></p>

                <?php if ($error = $this->has_error()) { ?>
                    <div class="wps-error-feedback"><?php echo $error; ?></div>
                <?php } ?>
            </td>
        </tr>
        <?php
    }
}
