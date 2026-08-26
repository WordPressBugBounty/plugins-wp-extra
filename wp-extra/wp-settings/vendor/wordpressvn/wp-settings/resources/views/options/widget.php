<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>">
    <th scope="row">
        <label for="<?php echo $option->get_id_attribute(); ?>" class="<?php echo $option->get_label_class_attribute(); ?>">
            <?php echo $option->get_label(); ?>
            <?php if ($link = $option->get_arg('link')) { ?>
                <a target="_blank" href="<?php echo esc_url($link); ?>" ="<?php _e('Help'); ?>"><span class="dashicons dashicons-editor-help"></span></a>
            <?php } ?>
        </label>
    </th>
    <td>
        <ul>
        <?php 
        $widgets = [];
        if (!empty($GLOBALS['wp_widget_factory'])) {
            $widgets = $GLOBALS['wp_widget_factory']->widgets;
        }

        $widgets = wp_list_sort($widgets, ['name' => 'ASC'], null, true);

        if (!$widgets) {
            printf(
                '<p>%s</p>',
                __('Oops, we could not retrieve the sidebar widgets! Maybe there is another plugin already managing them?', 'wp-extra')
            );
            return;
        }
        foreach ($widgets as $key => $label) {
            ?>
            <li class="components-checkbox-control">
                <span class="components-checkbox-control__input-container">
                    <input type="checkbox" id="<?php echo $option->get_id_attribute(); ?>_<?php echo $key; ?>" name="<?php echo esc_attr($option->get_name_attribute()); ?>" value="<?php echo $key; ?>" <?php echo in_array($key, $option->get_value_attribute() ?? []) ? 'checked' : ''; ?> class="components-checkbox-control__input <?php echo $option->get_input_class_attribute(); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>
                </span><label for="<?php echo $option->get_id_attribute(); ?>_<?php echo $key; ?>">
                    <?php echo $label->name; ?> <code><?php echo $key; ?></code>
                </label>
            </li>
        <?php } ?>
        </ul>

        <p><a href="javascript:void(0);" class="select-all components-button is-compact is-tertiary"><?php _e('Select all'); ?></a> | <a href="javascript:void(0);" class="deselect components-button is-compact is-tertiary"><?php _e('Deselect'); ?></a></p>

        <?php if ($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>

        <?php if ($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>
