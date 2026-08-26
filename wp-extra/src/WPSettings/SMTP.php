<?php
namespace WPEXtra\WPSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPVNTeam\WPSettings\Options\OptionAbstract;
use WPEXtra\Helper;

class SMTP extends OptionAbstract
{
    public $view = 'smtp';

    public function __construct($name = null, $args = [])
    {
        parent::__construct($name, $args);
        add_action('wp_ajax_wpex_smtp_send_test_ajax', [$this, 'handle_ajax_test_email']);
    }

    public function handle_ajax_test_email()
    {
        check_ajax_referer('wpex_smtp_test_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have sufficient permissions to perform this action.', 'wp-extra')
            ]);
        }

        $to = isset($_POST['to_email']) ? sanitize_email($_POST['to_email']) : '';
        if (empty($to) || !is_email($to)) {
            wp_send_json_error([
                'message' => __('Please enter a valid recipient email address.', 'wp-extra')
            ]);
        }

        $site_name   = get_bloginfo('name');
        $site_url    = home_url('/');
        $from_name   = Helper::get_option('from_name', get_bloginfo('name'));
        $from_email  = Helper::get_option('from_email', get_option('admin_email'));
        $sent_time   = current_time('mysql');
        $wp_version  = get_bloginfo('version');

        $subject = sprintf(__('[%s] WP EXtra - SMTP Test Email Delivery', 'wp-extra'), $site_name);

        $html_message = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { margin:0; padding:0; background:#f4f6f8; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif; color:#2c3338; }
.wrapper { max-width:600px; margin:30px auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); border:1px solid #e2e8f0; }
.header { background:#2271b1; padding:26px 30px; text-align:center; color:#ffffff; }
.header h1 { margin:0; font-size:20px; font-weight:700; color:#ffffff; }
.body { padding:28px 30px; }
.badge { display:inline-block; background:#ecfdf5; color:#065f46; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; margin-bottom:16px; border:1px solid #a7f3d0; }
.info-table { width:100%; border-collapse:collapse; margin:20px 0; }
.info-table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; font-size:13.5px; }
.info-table td.label { font-weight:600; color:#64748b; width:35%; }
.footer { background:#f8fafc; padding:16px 30px; text-align:center; font-size:12px; color:#94a3b8; border-top:1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>WP EXtra - SMTP Test Delivery</h1>
  </div>
  <div class="body">
    <div class="badge">&#10004; ' . esc_html__('SMTP Connection Successful', 'wp-extra') . '</div>
    <h2 style="margin:0 0 10px; font-size:17px; color:#1e293b;">' . esc_html__('Your email delivery system is functioning perfectly!', 'wp-extra') . '</h2>
    <p style="margin:0 0 16px; font-size:13.5px; line-height:1.6; color:#475569;">' . sprintf(esc_html__('Congratulations! This test message confirms that %s is properly connected to your configured SMTP mail service and is ready to deliver notifications, order confirmations, and passwords directly to customer inboxes.', 'wp-extra'), '<strong>' . esc_html($site_name) . '</strong>') . '</p>
    
    <table class="info-table">
      <tr><td class="label">' . esc_html__('Website', 'wp-extra') . '</td><td><a href="' . esc_url($site_url) . '" style="color:#2271b1; text-decoration:none;">' . esc_html($site_name) . '</a></td></tr>
      <tr><td class="label">' . esc_html__('Recipient (To)', 'wp-extra') . '</td><td><strong>' . esc_html($to) . '</strong></td></tr>
      <tr><td class="label">' . esc_html__('Sender (From)', 'wp-extra') . '</td><td>' . esc_html($from_name) . ' &lt;' . esc_html($from_email) . '&gt;</td></tr>
      <tr><td class="label">' . esc_html__('Delivery Time', 'wp-extra') . '</td><td>' . esc_html($sent_time) . '</td></tr>
    </table>
  </div>
  <div class="footer">
    ' . sprintf(esc_html__('Sent via WP EXtra SMTP Mailer | WordPress %s', 'wp-extra'), esc_html($wp_version)) . '
  </div>
</div>
</body>
</html>';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'X-WPEXtra-Test: 1'
        ];

        $start_time = microtime(true);
        $mail_error = null;
        $error_callback = function($wp_error) use (&$mail_error) {
            $mail_error = $wp_error;
        };
        add_action('wp_mail_failed', $error_callback);

        $sent = wp_mail($to, $subject, $html_message, $headers);

        remove_action('wp_mail_failed', $error_callback);
        $elapsed = round((microtime(true) - $start_time) * 1000);

        if ($sent) {
            wp_send_json_success([
                'message' => sprintf(__('Test email sent successfully to %s in %sms! Please check your inbox or spam folder.', 'wp-extra'), '<strong>' . esc_html($to) . '</strong>', $elapsed),
                'latency' => $elapsed,
                'to'      => $to
            ]);
        } else {
            $error_detail = ($mail_error instanceof \WP_Error) ? $mail_error->get_error_message() : __('Failed to establish connection to SMTP server.', 'wp-extra');
            
            // Build troubleshooting hint
            $hint = '';
            if (stripos($error_detail, 'authenticate') !== false || stripos($error_detail, 'password') !== false || stripos($error_detail, 'credentials') !== false) {
                $hint = __('Troubleshooting hint: Check your username and App Password. If using Gmail, make sure 2-Factor Authentication is on and use a 16-character Google App Password.', 'wp-extra');
            } elseif (stripos($error_detail, 'timeout') !== false || stripos($error_detail, 'connect') !== false) {
                $hint = __('Troubleshooting hint: Connection timed out. Make sure your server allows outgoing connections on Port 587 (TLS) or Port 465 (SSL).', 'wp-extra');
            }

            wp_send_json_error([
                'message'      => sprintf(__('SMTP Error: %s', 'wp-extra'), esc_html($error_detail)),
                'error_detail' => $error_detail,
                'hint'         => $hint,
                'latency'      => $elapsed
            ]);
        }
    }

    public function render()
    {
        $default_to = wp_get_current_user()->user_email ?: get_option('admin_email');
        ?>
        <tr valign="top">
            <th scope="row">
                <label><?php echo esc_html($this->get_label()); ?></label>
                <?php if ($desc = $this->get_arg('description')) { ?>
                    <p class="description" style="margin-top: 4px; font-weight: normal;"><?php echo esc_html($desc); ?></p>
                <?php } ?>
            </th>
            <td class="forminp forminp-text">
                <div class="wpex-smtp-test-card">
                    <div style="font-weight: 600; font-size: 13.5px; margin-bottom: 8px; color: #1d2327;">
                        <?php esc_html_e('Recipient Email Address', 'wp-extra'); ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <input id="wpex_test_email_recipient" type="email" value="<?php echo esc_attr($default_to); ?>" class="regular-text" style="flex: 1; height: 36px; padding: 0 10px; font-size: 13.5px;" placeholder="name@example.com" />
                        <button type="button" id="wpex_btn_send_smtp_test" class="button button-primary" style="height: 36px; display: inline-flex; align-items: center; gap: 6px; padding: 0 16px; font-weight: 600;">
                            <span class="dashicons dashicons-email-alt" style="font-size: 16px; width: 16px; height: 16px; line-height: 16px;"></span>
                            <span class="wpex-btn-text"><?php esc_html_e('Send Test Email', 'wp-extra'); ?></span>
                        </button>
                    </div>

                    <div id="wpex_smtp_test_result" style="display: none; margin-top: 12px; border-radius: 4px; padding: 12px 14px; font-size: 13px; line-height: 1.5;"></div>
                </div>
            </td>
        </tr>
        <?php
    }
}
