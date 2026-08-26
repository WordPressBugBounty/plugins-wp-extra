<?php
namespace WPEXtra\WPSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPVNTeam\WPSettings\Options\OptionAbstract;

class ProtectHtaccess extends OptionAbstract
{
    public function __construct($section, $args = [])
    {
        add_action('wp_ajax_wpex_toggle_htaccess_protect', [$this, 'ajax_toggle_protect']);

        parent::__construct($section, $args);
    }

    public function get_target()
    {
        return $this->get_arg('target', 'includes');
    }

    public static function get_target_file($target)
    {
        if ($target === 'content') {
            return WP_CONTENT_DIR . '/.htaccess';
        }
        return ABSPATH . WPINC . '/.htaccess';
    }

    public static function get_target_rules($target)
    {
        if ($target === 'content') {
            return '<FilesMatch "\.(?i:php)$">
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
</FilesMatch>';
        }

        return '<Files wp-tinymce.php>
allow from all
</Files>
<Files ms-files.php>
allow from all
</Files>
<FilesMatch "\.(?i:php)$">
  <IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
  </IfModule>
  <IfModule mod_authz_core.c>
    Require all denied
  </IfModule>
</FilesMatch>
<Files wp-tinymce.php>
  Allow from all
</Files>
<Files ms-files.php>
  Allow from all
</Files>';
    }

    public function ajax_toggle_protect()
    {
        check_ajax_referer('wpex_htaccess_protect_nonce', '_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized permission.', 'wp-extra')]);
        }

        $target = sanitize_text_field($_POST['target'] ?? 'includes');
        $action = sanitize_text_field($_POST['protect_action'] ?? 'create');

        $file_path = self::get_target_file($target);
        $rules = self::get_target_rules($target);

        if ($action === 'create') {
            $dir = dirname($file_path);
            if (!is_writable($dir) && !file_exists($file_path)) {
                wp_send_json_error(['message' => sprintf(__('Directory %s is not writable by server.', 'wp-extra'), $dir)]);
            }

            $res = @file_put_contents($file_path, trim($rules));
            if ($res !== false) {
                wp_send_json_success([
                    'message'   => __('Protected file .htaccess created successfully.', 'wp-extra'),
                    'is_active' => true,
                    'file_path' => $file_path,
                ]);
            } else {
                wp_send_json_error(['message' => __('Could not write .htaccess file. Please check server permissions.', 'wp-extra')]);
            }
        } elseif ($action === 'remove') {
            if (file_exists($file_path)) {
                $deleted = @unlink($file_path);
                if ($deleted) {
                    wp_send_json_success([
                        'message'   => __('Protected file .htaccess has been removed.', 'wp-extra'),
                        'is_active' => false,
                        'file_path' => $file_path,
                    ]);
                } else {
                    wp_send_json_error(['message' => __('Could not delete .htaccess file. Please check file permissions.', 'wp-extra')]);
                }
            } else {
                wp_send_json_success([
                    'message'   => __('File .htaccess does not exist.', 'wp-extra'),
                    'is_active' => false,
                    'file_path' => $file_path,
                ]);
            }
        }

        wp_send_json_error(['message' => __('Invalid action.', 'wp-extra')]);
    }



    public function render()
    {
        $target = $this->get_target();
        $file_path = self::get_target_file($target);
        $is_active = file_exists($file_path);
        ?>
        <tr valign="top" class="<?php echo $this->get_hide_class_attribute(); ?>" <?php echo $this->get_show_if_attribute(); ?>>
            <th scope="row">
                <label class="<?php echo $this->get_label_class_attribute(); ?>">
                    <?php echo $this->get_label(); ?>
                </label>
            </th>
            <td>
                <div class="wpex-protect-wrap">
                    <div class="wpex-protect-row">
                        <?php if ($is_active) { ?>
                            <span class="wpex-protect-badge is-active">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Protected', 'wp-extra'); ?>
                            </span>
                            <button type="button" class="button wpex-protect-btn is-danger" data-target="<?php echo esc_attr($target); ?>" data-action="remove">
                                <span class="dashicons dashicons-trash" style="font-size:14px;width:14px;height:14px;line-height:14px;"></span>
                                <?php esc_html_e('Remove Protection', 'wp-extra'); ?>
                            </button>
                        <?php } else { ?>
                            <span class="wpex-protect-badge is-inactive">
                                <span class="dashicons dashicons-shield"></span>
                                <?php esc_html_e('Not Protected', 'wp-extra'); ?>
                            </span>
                            <button type="button" class="button button-primary wpex-protect-btn" data-target="<?php echo esc_attr($target); ?>" data-action="create">
                                <span class="dashicons dashicons-plus-alt2" style="font-size:14px;width:14px;height:14px;line-height:14px;"></span>
                                <?php esc_html_e('Create Protection File', 'wp-extra'); ?>
                            </button>
                        <?php } ?>
                    </div>

                    <?php if ($description = $this->get_arg('description')) { ?>
                        <p class="description"><?php echo $description; ?></p>
                    <?php } ?>
                </div>
            </td>
        </tr>
        <?php
    }
}
