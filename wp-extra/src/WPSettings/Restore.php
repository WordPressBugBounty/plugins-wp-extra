<?php

namespace WPEXtra\WPSettings;

use WPVNTeam\WPSettings\Options\OptionAbstract;

use WPVNTeam\WPSettings\Enqueuer;

class Restore extends OptionAbstract
{
    public $view = 'restore';

    public function __construct($section, $args = [])
    {
        add_action('admin_init', [$this, 'import'], 20);
        parent::__construct($section, $args);
    }

    public function import()
    {
        if (! isset($_POST['_wpnonce_import']) || ! wp_verify_nonce($_POST['_wpnonce_import'], 'wp_settings_save_' . $this->section->tab->settings->option_name)) {
            return;
        }

        if (!is_admin() || !current_user_can('manage_options')) {
            wp_die(__('You need a higher level of permission.'));
        }
        $data = isset($_POST['data']) ? sanitize_text_field($_POST['data']) : '';
		$settings_json = unserialize(base64_decode($data));
        $settings_array = json_decode($settings_json, true);
    
        if ($settings_array) {
            update_option($this->section->tab->settings->option_name, $settings_array);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Request added successfully.') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __('Invalid translation type.') . '</p></div>';
            });
        }
    }
    
    public function render()
    {
        $nonce = 'wp_settings_save_' . $this->section->tab->settings->option_name;
        ?>
        <tr valign="top" class="<?php echo $this->get_hide_class_attribute(); ?>">
            <th scope="row" class="titledesc">
                <label for="<?php echo $this->get_id_attribute(); ?>" class="<?php echo $this->get_label_class_attribute(); ?>"><?php echo $this->get_label(); ?></label>
            </th>
            <td class="forminp forminp-text">
                <textarea name="data" id="<?php echo $this->get_id_attribute(); ?>" rows="8" class="large-text code"><?php echo base64_encode(serialize(json_encode(get_option($this->section->tab->settings->option_name, [])))); ?></textarea>
                <input type="hidden" name="_wpnonce_import" value="<?php echo wp_create_nonce($nonce); ?>" />
                <input
                    name="restore"
                    id="<?php echo $this->get_id_attribute(); ?>"
                    type="submit"
                    value="<?php _e( 'Restore' ); ?>"
                    class="button components-button is-primary is-compact" 
                    onclick="return confirmImport();">
                <script type="text/javascript">
                function confirmImport() {
                    return confirm("<?php _e( 'Are you sure you want to do this?' ); ?>");
                }
                </script>
                <?php if($description = $this->get_arg('description')) { ?>
                    <p class="description"><?php echo $description; ?></p>
                <?php } ?>

                <?php if($error = $this->has_error()) { ?>
                    <div class="wps-error-feedback"><?php echo $error; ?></div>
                <?php } ?>
            </td>
        </tr>
        <?php
    }
}
