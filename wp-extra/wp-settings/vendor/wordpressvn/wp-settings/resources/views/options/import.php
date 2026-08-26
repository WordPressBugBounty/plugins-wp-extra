<?php
$path_info = $option->get_file_path();
$safe_rel_path = $path_info['rel'];
$import_path = $path_info['abs'];
$backup_path = $import_path . '.bak';

// Read real file content from disk if exists
$display_val = '';
if (file_exists($import_path)) {
    $disk_content = @file_get_contents($import_path);
    if ($disk_content !== false) {
        $display_val = $disk_content;
    }
}
if ($display_val === '') {
    $display_val = (string) ($option->get_value_attribute() ?: $option->get_arg('default'));
}
$has_backup = file_exists($backup_path);
?>
<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>" <?php echo $option->get_show_if_attribute(); ?>>
    <th scope="row" class="titledesc">
        <label for="<?php echo $option->get_id_attribute(); ?>"
            class="<?php echo $option->get_label_class_attribute(); ?>">
            <?php echo $option->get_label(); ?>
            <?php if ($link = $option->get_arg('link')) { ?>
                <a target="_blank" href="<?php echo esc_url($link); ?>" ="<?php _e('Help'); ?>"><span
                        class="dashicons dashicons-editor-help"></span></a>
            <?php } ?>
        </label>
    </th>
    <td class="forminp forminp-text">
        <textarea name="<?php echo esc_attr($option->get_name_attribute()); ?>"
            id="<?php echo $option->get_id_attribute(); ?>"
            class="wps-file-code-editor <?php echo $option->get_input_class_attribute(); ?>" rows="12"
            style="width:100%;font-family:monospace;"><?php echo esc_textarea($display_val); ?></textarea>
        <?php if ($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>
        <p style="display: flex; gap: 8px; align-items: center; margin-top: 10px; flex-wrap: wrap;">
            <button type="button" class="button button-primary wps-save-file-btn wpex-save-file-btn"
                data-target="<?php echo $option->get_id_attribute(); ?>"
                data-filepath="<?php echo esc_attr($safe_rel_path); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"
                    style="display: block; flex-shrink: 0;" aria-hidden="true" focusable="false">
                    <path
                        d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
                </svg>
                <span><?php esc_html_e('Save to File'); ?></span>
            </button>
            <button type="button" class="button wps-reload-file-btn wpex-reload-file-btn"
                data-target="<?php echo $option->get_id_attribute(); ?>"
                data-filepath="<?php echo esc_attr($safe_rel_path); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"
                    style="display: block; flex-shrink: 0;" aria-hidden="true" focusable="false">
                    <path
                        d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z" />
                </svg>
                <span><?php _e('Reload from Disk'); ?></span>
            </button>
            <button type="button" class="button wps-restore-file-btn wpex-restore-file-btn"
                data-target="<?php echo $option->get_id_attribute(); ?>"
                data-filepath="<?php echo esc_attr($safe_rel_path); ?>"
                style="<?php echo $has_backup ? '' : 'display:none;'; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"
                    style="display: block; flex-shrink: 0;" aria-hidden="true" focusable="false">
                    <path
                        d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z" />
                </svg>
                <span><?php _e('Restore Backup'); ?></span>
            </button>
        </p>

        <?php if ($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>