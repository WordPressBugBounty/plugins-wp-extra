<div id="poststuff">
    <div id="post-body" class="<?php echo (!$settings->get_sidebar()) ?: 'columns-2'; ?>">
        <div id="post-body-content">
        <form method="post" action="<?php echo $settings->get_full_url(); ?>">
            <?php WPVNTeam\WPSettings\view('section-menu', compact('settings')); ?>            
            <div class="nav-tab-content">
                <?php foreach ($settings->get_active_tab()->get_active_sections() as $section) { ?>
                    <?php WPVNTeam\WPSettings\view('section', compact('section')); ?>
                <?php } ?>
            </div>
            <?php wp_nonce_field('wp_settings_save_' . $settings->option_name, '_wpnonce'); ?>            
            <?php if (strpos(strval($section->slug), '1') === false) { ?>
            <div class="components-panel__row">
                <?php
                submit_button(__('Save'), 'components-button is-primary is-compact', 'submit', false);
                submit_button(__('Restore'), 'components-button is-compact is-tertiary', 'reset', false, [
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
                        <h3 class="hndle"><?php echo $sidebar['title']; ?></h3>
                        <div class="inside"><?php echo $sidebar['message']; ?></div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>