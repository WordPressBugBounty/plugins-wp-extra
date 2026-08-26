<?php
$option_name = $option->get_option_name();
$export_nonce = wp_create_nonce('wp_settings_export_backup_nonce');
$export_url = admin_url('admin-post.php?action=wp_settings_export_backup&option_name=' . urlencode($option_name) . '&_wpnonce=' . $export_nonce);
?>
<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>" <?php echo $option->get_show_if_attribute(); ?>>
    <th scope="row" class="titledesc">
        <label for="<?php echo $option->get_id_attribute(); ?>"
            class="<?php echo $option->get_label_class_attribute(); ?>">
            <?php echo $option->get_label() ?: esc_html__('Transfer Options (.json)', 'wp-extra'); ?>
        </label>
    </th>
    <td class="forminp forminp-custom">
        <div class="wps-transfer-wrapper">
            <!-- Export Action -->
            <div class="wps-transfer-card">
                <h4 class="wps-transfer-title">
                    <?php echo esc_html__('Export Settings', 'wp-extra'); ?>
                </h4>
                <p class="wps-transfer-desc">
                    <?php echo esc_html__('Download all your WP EXtra settings into a .json file to backup or transfer to another site.', 'wp-extra'); ?>
                </p>
                <a href="<?php echo esc_url($export_url); ?>" class="components-button is-secondary is-compact"
                    style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; height: 32px; padding: 0 12px; font-weight: 500;">
                    <span class="dashicons dashicons-download"
                        style="font-size: 15px; width: 15px; height: 15px;"></span>
                    <?php echo esc_html__('Download .json File', 'wp-extra'); ?>
                </a>
            </div>

            <!-- Import Action -->
            <div class="wps-transfer-card">
                <h4 class="wps-transfer-title">
                    <?php echo esc_html__('Import Settings', 'wp-extra'); ?>
                </h4>
                <p class="wps-transfer-desc">
                    <?php echo esc_html__('Upload a .json backup file previously exported from WP EXtra to restore configurations.', 'wp-extra'); ?>
                </p>
                <div class="wps-transfer-row">
                    <input type="file" class="wps-import-file-input" accept=".json,application/json"
                        style="font-size: 12px; max-width: 220px;" />
                    <button type="button" class="wps-btn-import-file components-button is-primary is-compact"
                        data-option="<?php echo esc_attr($option_name); ?>"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('wp_settings_transfer_backup_nonce')); ?>"
                        style="display: inline-flex; align-items: center; gap: 5px; height: 32px; padding: 0 14px; font-weight: 500;">
                        <span class="dashicons dashicons-upload"
                            style="font-size: 15px; width: 15px; height: 15px;"></span>
                        <?php echo esc_html__('Import .json File', 'wp-extra'); ?>
                    </button>
                    <span class="spinner wps-import-spinner" style="float: none; margin: 0;"></span>
                </div>
                <div class="wps-transfer-feedback"></div>
            </div>
        </div>
    </td>
</tr>