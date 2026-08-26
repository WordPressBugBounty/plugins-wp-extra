<?php
$options = $option->get_arg('options', []);
$active_values = (array) ($option->get_value_attribute() ?? []);
$id_attr = $option->get_id_attribute();
$name_attr = esc_attr($option->get_name_attribute());

// Flatten items if categorized
$flat_items = [];
if (isset($options[0]['items'])) {
    foreach ($options as $group) {
        foreach ($group['items'] as $k => $v) {
            $flat_items[$k] = $v;
        }
    }
} else {
    $flat_items = $options;
}
?>
<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>" <?php echo $option->get_show_if_attribute(); ?>>
    <td colspan="2" style="padding: 10px 0 !important;">
        <div class="wps-modules-grid">
            <?php foreach ($flat_items as $key => $item) {
                $is_active = in_array($key, $active_values, true);
                $title = is_array($item) ? ($item['title'] ?? $key) : $item;
                $desc = is_array($item) ? ($item['desc'] ?? '') : '';
                $icon = is_array($item) ? ($item['icon'] ?? '') : '';
                ?>
                <div class="wps-module-item <?php echo $is_active ? 'is-active' : ''; ?>"
                    data-module="<?php echo esc_attr($key); ?>" title="<?php echo esc_attr($desc); ?>">
                    <div class="wps-module-item-left">
                        <?php if (!empty($icon)) { ?>
                            <div class="wps-module-item-icon">
                                <?php echo $icon; ?>
                            </div>
                        <?php } ?>
                        <div class="wps-module-item-text">
                            <h4 class="wps-module-item-title"><?php echo esc_html($title); ?></h4>
                            <?php if (!empty($desc)) { ?>
                                <p class="wps-module-item-desc"><?php echo esc_html($desc); ?></p>
                            <?php } ?>
                        </div>
                    </div>
                    <label class="wps-switch-toggle" for="<?php echo $id_attr; ?>_<?php echo $key; ?>"
                        onclick="event.stopPropagation();">
                        <input type="checkbox" id="<?php echo $id_attr; ?>_<?php echo $key; ?>"
                            name="<?php echo $name_attr; ?>" value="<?php echo esc_attr($key); ?>" <?php checked($is_active); ?> class="<?php echo $option->get_input_class_attribute(); ?>">
                        <span class="wps-switch-track"></span>
                    </label>
                </div>
            <?php } ?>
        </div>
        <?php if ($description = $option->get_arg('description')) { ?>
            <p class="description" style="margin-top: 15px;"><?php echo $description; ?></p>
        <?php } ?>
        <?php if ($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>
