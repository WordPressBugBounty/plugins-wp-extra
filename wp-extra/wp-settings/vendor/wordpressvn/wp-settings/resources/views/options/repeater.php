<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>" <?php echo $option->get_show_if_attribute(); ?>>
    <th scope="row">
        <label class="<?php echo $option->get_label_class_attribute(); ?>">
            <?php echo $option->get_label(); ?>
        </label>
        <?php if($link = $option->get_arg('link')) { ?>
            <a target="_blank"  href="<?php echo esc_url($link); ?>" title="<?php _e('Help'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z"></path></svg></a>
        <?php } ?>
    </th>
    <td>
        <?php
        $fields = $option->get_arg('fields', []);
        $rows = $option->get_value_attribute();
        if (!is_array($rows)) {
            $rows = [];
        }
        $name_attr = $option->get_name_attribute();
        $button_text = $option->get_arg('button_text', __('+ Add Row', 'wp-settings'));
        ?>
        <div class="wps-repeater-container" data-name="<?php echo esc_attr($name_attr); ?>">
            <div class="wps-repeater-rows">
                <?php foreach ($rows as $i => $row) { 
                    $row_title = !empty($row['title']) ? $row['title'] : (!empty($row['name']) ? $row['name'] : sprintf(__('Widget #%d', 'wp-settings'), $i + 1));
                ?>
                    <div class="wps-repeater-card">
                        <div class="wps-repeater-card-header">
                            <div class="wps-repeater-card-title">
                                <span class="dashicons dashicons-menu"></span>
                                <strong class="wps-card-label"><?php echo esc_html($row_title); ?></strong>
                            </div>
                            <div class="wps-repeater-card-actions">
                                <button type="button" class="wps-card-btn wps-repeater-remove" title="<?php esc_attr_e('Remove', 'wp-settings'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                                <button type="button" class="wps-card-btn wps-repeater-toggle" title="<?php esc_attr_e('Toggle', 'wp-settings'); ?>">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                </button>
                            </div>
                        </div>
                        <div class="wps-repeater-card-body">
                            <div class="wps-repeater-fields-grid">
                                <?php foreach ($fields as $field) { 
                                    $f_name  = $field['name'] ?? '';
                                    $f_label = $field['label'] ?? ucfirst($f_name);
                                    $f_type  = $field['type'] ?? 'text';
                                    $f_val   = $row[$f_name] ?? ($field['default'] ?? '');
                                    $f_opts  = $field['options'] ?? [];
                                    $is_editor = in_array($f_type, ['editor', 'wp-editor', 'wp_editor'], true);
                                    $is_full   = !empty($field['full_width']) || $is_editor || $f_type === 'textarea';
                                    $editor_id = 'wps_ed_' . $i . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $f_name);
                                ?>
                                    <div class="wps-repeater-field-item <?php echo $is_full ? 'wps-field-full' : 'wps-field-half'; ?>">
                                        <label class="wps-field-label"><?php echo esc_html($f_label); ?></label>
                                        
                                        <?php if ($is_editor) { ?>
                                            <div class="wps-editor-wrap">
                                                <?php 
                                                \wp_editor(
                                                    html_entity_decode($f_val ?? '', ENT_QUOTES, 'UTF-8'),
                                                    $editor_id,
                                                    [
                                                        'textarea_name' => $name_attr . '[' . $i . '][' . $f_name . ']',
                                                        'textarea_rows' => $field['rows'] ?? 5,
                                                        'teeny'         => false,
                                                        'media_buttons' => true,
                                                        'quicktags'     => true,
                                                        'tinymce'       => true,
                                                    ]
                                                );
                                                ?>
                                            </div>
                                        <?php } elseif ($f_type === 'textarea') { ?>
                                            <textarea rows="<?php echo esc_attr($field['rows'] ?? 4); ?>" name="<?php echo esc_attr($name_attr . '[' . $i . '][' . $f_name . ']'); ?>" class="large-text" placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"><?php echo esc_textarea($f_val); ?></textarea>
                                        <?php } elseif ($f_type === 'select') { ?>
                                            <select name="<?php echo esc_attr($name_attr . '[' . $i . '][' . $f_name . ']'); ?>" class="regular-text widefat">
                                                <?php foreach ($f_opts as $opt_k => $opt_l) { ?>
                                                    <option value="<?php echo esc_attr($opt_k); ?>" <?php selected($f_val, $opt_k); ?>><?php echo esc_html($opt_l); ?></option>
                                                <?php } ?>
                                            </select>
                                        <?php } elseif ($f_type === 'password') { ?>
                                            <div class="wps-password-wrap" style="position: relative; display: flex; align-items: center;">
                                                <input type="password" name="<?php echo esc_attr($name_attr . '[' . $i . '][' . $f_name . ']'); ?>" value="<?php echo esc_attr($f_val); ?>" class="regular-text widefat" style="padding-right: 36px;" placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" autocomplete="new-password" />
                                                <button type="button" class="wps-toggle-password-btn" style="position: absolute; right: 4px; border: none; background: transparent; cursor: pointer; color: #50575e; display: inline-flex; align-items: center; justify-content: center; height: 100%; padding: 0 4px;" title="<?php esc_attr_e('Show/Hide password', 'wp-extra'); ?>">
                                                    <span class="dashicons dashicons-visibility" style="font-size: 18px; width: 18px; height: 18px; line-height: 18px;"></span>
                                                </button>
                                            </div>
                                        <?php } else { ?>
                                            <input type="<?php echo esc_attr($f_type); ?>" name="<?php echo esc_attr($name_attr . '[' . $i . '][' . $f_name . ']'); ?>" value="<?php echo esc_attr($f_val); ?>" class="regular-text widefat <?php echo ($f_name === 'title' || $f_name === 'name') ? 'wps-title-sync' : ''; ?>" placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" />
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            
            <template class="wps-repeater-template">
                <div class="wps-repeater-card">
                    <div class="wps-repeater-card-header">
                        <div class="wps-repeater-card-title">
                            <span class="dashicons dashicons-menu"></span>
                            <strong class="wps-card-label"><?php echo esc_html(__('New Widget', 'wp-settings')); ?></strong>
                        </div>
                        <div class="wps-repeater-card-actions">
                            <button type="button" class="wps-card-btn wps-repeater-remove" title="<?php esc_attr_e('Remove', 'wp-settings'); ?>">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                            <button type="button" class="wps-card-btn wps-repeater-toggle" title="<?php esc_attr_e('Toggle', 'wp-settings'); ?>">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                    </div>
                    <div class="wps-repeater-card-body">
                        <div class="wps-repeater-fields-grid">
                            <?php foreach ($fields as $field) { 
                                $f_name  = $field['name'] ?? '';
                                $f_label = $field['label'] ?? ucfirst($f_name);
                                $f_type  = $field['type'] ?? 'text';
                                $f_def   = $field['default'] ?? '';
                                $f_opts  = $field['options'] ?? [];
                                $is_editor = in_array($f_type, ['editor', 'wp-editor', 'wp_editor'], true);
                                $is_full   = !empty($field['full_width']) || $is_editor || $f_type === 'textarea';
                            ?>
                                <div class="wps-repeater-field-item <?php echo $is_full ? 'wps-field-full' : 'wps-field-half'; ?>">
                                    <label class="wps-field-label"><?php echo esc_html($f_label); ?></label>
                                    
                                    <?php if ($is_editor) { ?>
                                        <div class="wps-editor-wrap">
                                            <textarea rows="<?php echo esc_attr($field['rows'] ?? 5); ?>" data-name-template="<?php echo esc_attr($name_attr . '[__INDEX__][' . $f_name . ']'); ?>" data-editor-template="true" class="large-text wps-dynamic-editor" placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"><?php echo esc_textarea($f_def); ?></textarea>
                                        </div>
                                    <?php } elseif ($f_type === 'textarea') { ?>
                                        <textarea rows="<?php echo esc_attr($field['rows'] ?? 4); ?>" data-name-template="<?php echo esc_attr($name_attr . '[__INDEX__][' . $f_name . ']'); ?>" class="large-text" placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>"><?php echo esc_textarea($f_def); ?></textarea>
                                    <?php } elseif ($f_type === 'select') { ?>
                                        <select data-name-template="<?php echo esc_attr($name_attr . '[__INDEX__][' . $f_name . ']'); ?>" class="regular-text widefat">
                                            <?php foreach ($f_opts as $opt_k => $opt_l) { ?>
                                                <option value="<?php echo esc_attr($opt_k); ?>" <?php selected($f_def, $opt_k); ?>><?php echo esc_html($opt_l); ?></option>
                                            <?php } ?>
                                        </select>
                                    <?php } elseif ($f_type === 'password') { ?>
                                        <div class="wps-password-wrap" style="position: relative; display: flex; align-items: center;">
                                            <input type="password" data-name-template="<?php echo esc_attr($name_attr . '[__INDEX__][' . $f_name . ']'); ?>" value="<?php echo esc_attr($f_def); ?>" class="regular-text widefat" style="padding-right: 36px;" placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" autocomplete="new-password" />
                                            <button type="button" class="wps-toggle-password-btn" style="position: absolute; right: 4px; border: none; background: transparent; cursor: pointer; color: #50575e; display: inline-flex; align-items: center; justify-content: center; height: 100%; padding: 0 4px;" title="<?php esc_attr_e('Show/Hide password', 'wp-extra'); ?>">
                                                <span class="dashicons dashicons-visibility" style="font-size: 18px; width: 18px; height: 18px; line-height: 18px;"></span>
                                            </button>
                                        </div>
                                    <?php } else { ?>
                                        <input type="<?php echo esc_attr($f_type); ?>" data-name-template="<?php echo esc_attr($name_attr . '[__INDEX__][' . $f_name . ']'); ?>" value="<?php echo esc_attr($f_def); ?>" class="regular-text widefat <?php echo ($f_name === 'title' || $f_name === 'name') ? 'wps-title-sync' : ''; ?>" placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>" />
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </template>

            <div class="wps-repeater-actions">
                <button type="button" class="button button-primary wps-repeater-add" style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 14px; height: 34px; line-height: 1;">
                    <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px; margin: 0;"></span>
                    <span><?php echo esc_html(ltrim($button_text, '+ ')); ?></span>
                </button>
            </div>
        </div>

        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>

        <?php if($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
        <script>
        jQuery(function($){
            if (!window.wpsPassToggleBound) {
                window.wpsPassToggleBound = true;
                $(document).on('click', '.wps-toggle-password-btn', function(e){
                    e.preventDefault();
                    var $btn = $(this);
                    var $inp = $btn.siblings('input');
                    var $icon = $btn.find('.dashicons');
                    if ($inp.attr('type') === 'password') {
                        $inp.attr('type', 'text');
                        $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    } else {
                        $inp.attr('type', 'password');
                        $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    }
                });
            }
        });
        </script>
    </td>
</tr>
