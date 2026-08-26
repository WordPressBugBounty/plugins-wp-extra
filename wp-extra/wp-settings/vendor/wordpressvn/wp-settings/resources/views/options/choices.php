<tr valign="top" class="<?php echo $option->get_hide_class_attribute(); ?>" <?php echo $option->get_show_if_attribute(); ?>>
    <th scope="row">
        <?php echo $option->get_label(); ?>
        <?php if($link = $option->get_arg('link')) { ?>
            <a target="_blank"  href="<?php echo esc_url($link); ?>" title="<?php _e('Help'); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 4.75a7.25 7.25 0 100 14.5 7.25 7.25 0 000-14.5zM3.25 12a8.75 8.75 0 1117.5 0 8.75 8.75 0 01-17.5 0zM12 8.75a1.5 1.5 0 01.167 2.99c-.465.052-.917.44-.917 1.01V14h1.5v-.845A3 3 0 109 10.25h1.5a1.5 1.5 0 011.5-1.5zM11.25 15v1.5h1.5V15h-1.5z"></path></svg></a>
        <?php } ?>
    </th>
    <td>
        <?php 
        $options = $option->get_arg('options', []);
        $has_images = false;
        if (is_array($options)) {
            foreach ($options as $opt_val) {
                if (is_array($opt_val) && (
                    !empty($opt_val['image']) || 
                    !empty($opt_val['svg']) || 
                    !empty($opt_val['svg_path']) || 
                    !empty($opt_val['path'])
                )) {
                    $has_images = true;
                    break;
                }
            }
        }
        ?>
        <ul class="<?php echo $has_images ? 'wps-choices-grid' : 'wps-choices-list'; ?>">
        <?php if (is_array($options)) {
            foreach($options as $key => $data) { 
                $item_label    = is_array($data) ? ($data['label'] ?? $key) : $data;
                $item_image    = is_array($data) ? ($data['image'] ?? '') : '';
                $item_svg      = is_array($data) ? ($data['svg'] ?? '') : '';
                $item_svg_path = is_array($data) ? ($data['svg_path'] ?? ($data['path'] ?? '')) : '';
                $item_viewbox  = is_array($data) ? ($data['viewBox'] ?? ($data['viewbox'] ?? '0 0 24 24')) : '0 0 24 24';
                $item_desc     = is_array($data) ? ($data['description'] ?? '') : '';
                $is_checked    = checked($key, $option->get_value_attribute(), false);

                // Determine if we have image or svg
                $has_visual = !empty($item_image) || !empty($item_svg) || !empty($item_svg_path);
            ?>
                <li class="components-radio-control__option <?php echo $has_visual ? 'wps-choice-item-has-image' : ''; ?>">
                    <label for="<?php echo $option->get_id_attribute(); ?>_<?php echo esc_attr($key); ?>" class="<?php echo $has_visual ? 'wps-choice-label-wrapper' : ''; ?> <?php echo $is_checked ? 'is-selected' : ''; ?>">
                        <input name="<?php echo esc_attr($option->get_name_attribute()); ?>" id="<?php echo $option->get_id_attribute(); ?>_<?php echo $key; ?>" type="radio" value="<?php echo esc_attr($key); ?>" <?php echo $is_checked; ?> class="components-radio-control__input <?php echo $option->get_input_class_attribute(); ?>">
                        <?php if ($has_visual) { ?>
                            <div class="wps-choice-preview">
                                <?php 
                                if (!empty($item_svg)) {
                                    if (strpos(trim($item_svg), '<svg') === 0) {
                                        echo $item_svg;
                                    } elseif (strpos(trim($item_svg), '<path') === 0) {
                                        echo '<svg viewBox="' . esc_attr($item_viewbox) . '" width="100%" height="100%" aria-hidden="true">' . $item_svg . '</svg>';
                                    } else {
                                        echo '<svg viewBox="' . esc_attr($item_viewbox) . '" width="100%" height="100%" aria-hidden="true"><path d="' . esc_attr($item_svg) . '" fill="currentColor" /></svg>';
                                    }
                                } elseif (!empty($item_svg_path)) {
                                    if (strpos(trim($item_svg_path), '<path') === 0) {
                                        echo '<svg viewBox="' . esc_attr($item_viewbox) . '" width="100%" height="100%" aria-hidden="true">' . $item_svg_path . '</svg>';
                                    } else {
                                        echo '<svg viewBox="' . esc_attr($item_viewbox) . '" width="100%" height="100%" aria-hidden="true"><path d="' . esc_attr($item_svg_path) . '" fill="currentColor" /></svg>';
                                    }
                                } elseif (!empty($item_image)) {
                                    if (strpos(trim($item_image), '<svg') === 0) {
                                        echo $item_image;
                                    } elseif (strpos(trim($item_image), '<path') === 0) {
                                        echo '<svg viewBox="' . esc_attr($item_viewbox) . '" width="100%" height="100%" aria-hidden="true">' . $item_image . '</svg>';
                                    } else {
                                        echo '<img src="' . esc_url($item_image) . '" alt="' . esc_attr(wp_strip_all_tags($item_label)) . '" />';
                                    }
                                }
                                ?>
                            </div>
                        <?php } ?>
                        <span class="wps-choice-text">
                            <?php echo $item_label; ?>
                            <?php if ($item_desc) { ?>
                                <small class="wps-choice-subtext"><?php echo esc_html($item_desc); ?></small>
                            <?php } ?>
                        </span>
                    </label>
                </li>
            <?php } 
        } ?>
        </ul>

        <?php if($description = $option->get_arg('description')) { ?>
            <p class="description"><?php echo $description; ?></p>
        <?php } ?>

        <?php if($error = $option->has_error()) { ?>
            <div class="wps-error-feedback"><?php echo $error; ?></div>
        <?php } ?>
    </td>
</tr>
