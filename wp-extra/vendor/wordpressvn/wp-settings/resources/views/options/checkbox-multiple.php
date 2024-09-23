<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>">
    <th scope="row">
        <?php echo $option->get_label(); ?>
        <?php if($link = $option->get_arg('link')) { ?>
            <a target="_blank" class="tooltip" href="<?php echo esc_url($link); ?>" title="<?php _e('Help'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z"></path></svg></a>
        <?php } ?>
    </th>
    <td>
        <ul>
        <?php foreach ($option->get_arg('options', []) as $key => $label) { ?>
            <li class="components-checkbox-control">
                <span class="components-checkbox-control__input-container">
                    <input type="checkbox" id="<?php echo $option->get_id_attribute(); ?>_<?php echo $key; ?>" name="<?php echo esc_attr($option->get_name_attribute()); ?>" value="<?php echo $key; ?>" <?php echo in_array($key, $option->get_value_attribute() ?? []) ? 'checked' : ''; ?>  class="components-checkbox-control__input <?php echo $option->get_input_class_attribute(); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>
                </span><label for="<?php echo $option->get_id_attribute(); ?>_<?php echo $key; ?>">
                <?php if($option->get_arg('del') === true) { ?>
                    <?php echo in_array($key, $option->get_value_attribute() ?? []) ? "<del>$label</del>" : $label; ?>
                <?php } else { ?>
                    <?php echo $label; ?>
                <?php } ?>
                </label>
            </li>
        <?php } ?>
        </ul>
        
        <?php if($option->get_arg('select') === true) { ?>
            <p><a href="javascript:void(0);" class="select-all components-button is-compact is-tertiary"><?php _e('Select all'); ?></a> | <a href="javascript:void(0);" class="deselect components-button is-compact is-tertiary"><?php _e('Deselect'); ?></a></p>
        <?php } ?>
        
        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>
        
        <?php if($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>
