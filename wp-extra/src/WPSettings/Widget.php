<?php

namespace WPEXtra\WPSettings;

use WPVNTeam\WPSettings\Options\OptionAbstract;
use WPVNTeam\WPSettings\Enqueuer;

class Widget extends OptionAbstract
{
    public $view = 'widget';
    
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
    
	public function use_widgets_block_editor() {
		if ( function_exists( 'wp_use_widgets_block_editor' ) ) {
			return wp_use_widgets_block_editor();
		}
		return false;
	}
    
	public function get_widgets_to_hide_from_legacy_widget_block() {
		if ( function_exists( 'get_legacy_widget_block_editor_settings' ) ) {
			return get_legacy_widget_block_editor_settings()['widgetTypesToHideFromLegacyWidgetBlock'];
		}
		return [];
	}
    
    public function enqueue()
    {
        Enqueuer::add('wps-widget', function () {
            wp_enqueue_script('wp-settings');
            wp_add_inline_script('wp-settings', "
                jQuery(function($) {
                    $('.select-all').on('click', function() {
                        $(this).closest('td').find('input[type=\"checkbox\"]').prop('checked', true);
                    });
                    $('.deselect').on('click', function() {
                        $(this).closest('td').find('input[type=\"checkbox\"]').prop('checked', false);
                    });
                });
            ");
        });
    }
    
    public function render()
    {
        ?>
        <tr valign="top" class="<?php echo $this->get_hide_class_attribute(); ?>">
            <th scope="row">
                <label for="<?php echo $this->get_id_attribute(); ?>" class="<?php echo $this->get_label_class_attribute(); ?>"><?php echo $this->get_label(); ?>
                <?php if($link = $this->get_arg('link')) { ?>
                    <a target="_blank" href="<?php echo esc_url($link); ?>" tooltip="<?php _e('Help'); ?>"><span class="dashicons dashicons-editor-help"></span></a>
                <?php } ?>
                </label>
            </th>
            <td>
            <ul>
                <?php 
                
                $widgets = [];

                if ( ! empty( $GLOBALS['wp_widget_factory'] ) ) {
                    $widgets = $GLOBALS['wp_widget_factory']->widgets;
                }

                $widgets = wp_list_sort( $widgets, [ 'name' => 'ASC' ], null, true );

                if ( ! $widgets ) {
                    printf(
                        '<p>%s</p>',
                        __( 'Oops, we could not retrieve the sidebar widgets! Maybe there is another plugin already managing them?', 'wp-widget-disable' )
                    );
                    return;
                }
                foreach ( $widgets as $key => $label ) {
                    ?>
                    <li class="components-checkbox-control">
                        <span class="components-checkbox-control__input-container">
                            <input type="checkbox" id="<?php echo $this->get_id_attribute(); ?>_<?php echo $key; ?>" name="<?php echo esc_attr($this->get_name_attribute()); ?>" value="<?php echo $key; ?>" <?php echo in_array($key, $this->get_value_attribute() ?? []) ? 'checked' : ''; ?>  class="components-checkbox-control__input <?php echo $this->get_input_class_attribute(); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>
                        </span><label for="<?php echo $this->get_id_attribute(); ?>_<?php echo $key; ?>">
                            <?php echo $label->name; ?> <code><?php echo $key; ?></code>
                        </label>
                    </li>
                <?php } ?>
                </ul>
        
                <p><a href="javascript:void(0);" class="select-all components-button is-compact is-tertiary"><?php _e('Select all'); ?></a> | <a href="javascript:void(0);" class="deselect components-button is-compact is-tertiary"><?php _e('Deselect'); ?></a></p>

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
