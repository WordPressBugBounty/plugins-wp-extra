<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>">
    <th scope="row">
        <label class="<?php echo $option->get_label_class_attribute(); ?>"><?php echo $option->get_label(); ?></label>
    </th>
    <td class="wps-media-wrapper" data-media-library="<?php echo esc_attr(json_encode($option->get_media_library_config())); ?>">
        <div class="site-icon-section">
            <?php if($preview = $option->get_preview_url()) { ?>
                <div class="wps-media-preview" style="display: flex;">
                    <img src="<?php echo $preview; ?>" />
                </div>
            <?php } else { ?>
                <div class="wps-media-preview"></div>
            <?php } ?>
            
            <input name="<?php echo esc_attr($option->get_name_attribute()); ?>" id="<?php echo $option->get_id_attribute(); ?>" type="hidden" value="<?php echo $option->get_value_attribute(); ?>" class="wps-media-target <?php echo $option->get_input_class_attribute(); ?>">

            <button class="wps-media-open components-button is-secondary is-compact"><?php echo $option->get_arg('button_open_text', _e('Select')); ?></button>
            
            <button type="button" class="wps-media-clear components-button is-compact is-tertiary has-icon" aria-label="<?php echo _e('Clear'); ?>" style="<?php echo empty($option->get_value_attribute()) ? 'display: none;' : ''; ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"></path></svg></button>
        </div>

        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>

        <?php if($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>