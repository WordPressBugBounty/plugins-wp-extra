<div id="poststuff">
    <div id="post-body" class="<?php echo $settings->get_sidebar() ? 'columns-2' : ''; ?>">
        <div id="post-body-content">
        <form method="post" action="<?php echo $settings->get_full_url(); ?>" data-ajax-save="<?php echo $settings->ajax_save ? '1' : '0'; ?>" data-action="wps_save_<?php echo esc_attr($settings->option_name); ?>">
            <?php WPVNTeam\WPSettings\view('section-menu', compact('settings')); ?>            
            <div class="nav-tab-content">
                <?php 
                $active_sections = $settings->get_active_tab() ? $settings->get_active_tab()->get_active_sections() : [];
                foreach ($active_sections as $section) { 
                    WPVNTeam\WPSettings\view('section', compact('section')); 
                } 
                ?>
            </div>
            <input type="hidden" name="tab" value="<?php echo esc_attr($settings->get_active_tab() ? $settings->get_active_tab()->slug : ''); ?>" />
            <?php wp_nonce_field('wp_settings_save_' . $settings->option_name, '_wpnonce'); ?>            
            <?php if (!$settings->get_active_tab() || $settings->get_active_tab()->slug !== 'donate') { ?>
            <div class="components-panel__row">
                <?php
                submit_button(__('Save'), 'components-button is-primary is-compact', 'submit', false);
                submit_button(__('Restore'), 'components-button is-compact is-tertiary', 'do_reset', false, [
                    'onclick' => 'return confirmReset();'
                ]);
                ?>
                <script type="text/javascript">
                function confirmReset() {
                    return confirm("<?php _e( 'Are you sure you want to do this?' ); ?>");
                }
                </script>
                </div>
            <?php } ?>
        </form>
        </div>
        <?php if ($sidebars = $settings->get_sidebar()) { ?>
            <div id="postbox-container-1" class="postbox-container sidebar">
                <?php foreach ($sidebars as $sidebar) { ?>
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle"><span><?php echo $sidebar['title']; ?></span></h2>
                        </div>
                        <div class="inside"><?php echo $sidebar['message']; ?></div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>