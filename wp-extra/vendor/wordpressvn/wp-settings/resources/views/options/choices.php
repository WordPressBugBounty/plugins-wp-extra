<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>">
    <th scope="row">
        <?php echo $option->get_label(); ?>
        <?php if($link = $option->get_arg('link')) { ?>
            <a target="_blank" class="tooltip" href="<?php echo esc_url($link); ?>" title="<?php _e('Help'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z"></path></svg></a>
        <?php } ?>
    </th>
    <td>
        <ul>
        <?php foreach($option->get_arg('options', []) as $key => $label) { ?>
            <li class="components-radio-control__option">
                <label for="<?php echo $option->get_id_attribute(); ?>_<?php echo $key; ?>">
                    <input name="<?php echo esc_attr($option->get_name_attribute()); ?>" id="<?php echo $option->get_id_attribute(); ?>_<?php echo $key; ?>" type="radio" value="<?php echo $key; ?>" <?php checked($key, $option->get_value_attribute()); ?> class="components-radio-control__input <?php echo $option->get_input_class_attribute(); ?>">
                    <?php echo $label; ?>
                </label>
            </li>
        <?php } ?>
        </ul>

        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>

        <?php if($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>
