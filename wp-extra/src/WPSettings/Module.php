<?php

namespace WPEXtra\WPSettings;

use WPVNTeam\WPSettings\Options\OptionAbstract;
use WPVNTeam\WPSettings\Enqueuer;

class Module extends OptionAbstract
{
    public $view = 'module';

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }
    
    public function get_name_attribute()
    {
        $name = parent::get_name_attribute();

        return "{$name}[]";
    }

    public function sanitize($value)
    {
        return (array) $value;
    }
    
    public function enqueue()
    {
        Enqueuer::add('wps-module', function () {
            ?>
            <style>
                .wps-module {
                    padding: 10px 0 0 20px !important;
                }

                .wps-module li {
                    border-radius: 4px; padding-top:10px;
                }

                .wps-module label svg {
                    float: right;
                }
            </style>
            <?php
        });
    }
    
    public function handle_redirect()
    {
        if (isset($_POST['submit'])) {
            $slug = str_replace('_', '-', $this->section->tab->settings->option_name);
            wp_safe_redirect(admin_url('admin.php?page=' . $slug));
            exit;
        }
    }
    
    public function render()
    {
        $this->handle_redirect();
        ?>
        <tr valign="top" class="<?php echo $this->get_hide_class_attribute(); ?>">
            <td colspan="2" class="wps-module">
                <ul>
                <?php foreach ($this->get_arg('options', []) as $key => $label) { ?>
                    <li class="color-option components-checkbox-control">
                        <span class="components-checkbox-control__input-container">
                            <input type="checkbox" id="<?php echo $this->get_id_attribute(); ?>_<?php echo $key; ?>" name="<?php echo esc_attr($this->get_name_attribute()); ?>" value="<?php echo $key; ?>" <?php echo in_array($key, $this->get_value_attribute() ?? []) ? 'checked' : ''; ?>  class="components-checkbox-control__input <?php echo $this->get_input_class_attribute(); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>
                        </span>
                        <label for="<?php echo $this->get_id_attribute(); ?>_<?php echo $key; ?>"><?php echo $label; ?></label>
                    </li>
                <?php } ?>
                </ul>
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
