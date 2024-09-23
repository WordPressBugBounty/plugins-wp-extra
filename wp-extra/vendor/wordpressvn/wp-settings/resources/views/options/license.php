<?php
if (isset($_POST['license_activate'])) {
    $option->activate();
} elseif (isset($_POST['license_deactivate'])) {
    $option->deactivate();
}
$license = get_option($option->section->tab->settings->option_name);
$license_status = isset($license['license_status']) ? trim($license['license_status']) : 'unverified';
$is_valid = $option->get_value_attribute() && $license_status === 'valid';
$status = $is_valid ? __('Your account is now active!') : __('Invalid activation key.');
$color = $is_valid ? 'background: rgba(18, 183, 106, 0.15); border: 1px solid rgba(18, 183, 106, 0.24); color: rgb(18, 183, 106); ' : 'background: rgba(209, 55, 55, 0.24); color: rgba(209, 55, 55, 1); ';
$activate_btn_style = $is_valid ? 'display:none;' : '';
$deactivate_btn_style = $is_valid ? '' : 'display:none;';
$input_disabled = $is_valid ? ' disabled' : '';
$expiration_date = isset($license['license_expires']) ? date('d/m/Y', strtotime($license['license_expires'])) : __('N/A');
?>
<tr valign="top">
    <th scope="row">
        <label for="<?php echo $option->get_id_attribute(); ?>" class="<?php echo $option->get_label_class_attribute(); ?>">
            <?php echo $option->get_label(); ?>
        </label>
        <?php if($link = $option->get_arg('link')) { ?>
            <a target="_blank" class="tooltip" href="<?php echo esc_url($link); ?>" title="<?php _e('Help'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z"></path></svg></a>
        <?php } ?>
    </th>
    <td>
        <input
            name="<?php echo esc_attr($option->get_name_attribute()); ?>"
            id="<?php echo $option->get_id_attribute(); ?>"
            type="password"
            value="<?php echo $option->get_value_attribute(); ?>"
            class="regular-text <?php echo $option->get_input_class_attribute(); ?>"
            <?php echo $input_disabled; ?>
        >
        <span style="<?php echo $activate_btn_style; ?>">
            <?php submit_button( __( 'Activate' ), 'components-button is-primary is-compact', 'license_activate', false ); ?>
        </span>
        <span style="<?php echo $deactivate_btn_style; ?>">
            <?php submit_button( __( 'Deactivate' ), 'components-button is-compact is-tertiary', 'license_deactivate', false ); ?>
        </span>
        
        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description">
                <a class="components-external-link" href="<?php echo $description; ?>" target="_blank" rel="external noreferrer noopener"><span class="components-external-link__contents"><?php _e('Don\'t have a license? Click here to purchase.', 'wp-extra'); ?></span><span class="components-external-link__icon">↗</span></a>
            </p>
        <?php } ?>
        
        <?php if ($error = $option->has_error()) : ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php endif; ?>
    </td>
</tr>
<tr valign="top">
    <th><?php _e('Status'); ?></th>
    <td>
        <span style="height: 24px; line-height: 24px; border-radius: 2px; padding: 5px 10px; font-size:12px; <?php echo $color; ?>">
            <?php echo $status; ?>
        </span>
    </td>
</tr>
<tr valign="top">
    <th><?php _e('Expiration'); ?></th>
    <td><?php echo esc_html($expiration_date); ?></td>
</tr>