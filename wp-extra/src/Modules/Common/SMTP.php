<?php
namespace WPEXtra\Modules\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class SMTP extends Base {
    
    private static $active_account_key = null;
    private static $active_account_name = 'Default';
    private static $current_mail_data = null;

    public function __construct() {
		parent::__construct();

		$from_email = Helper::get_option('from_email');
		if (!empty($from_email)) {
			add_filter('wp_mail_from', function($email) use ($from_email) {
				return sanitize_email($from_email);
			});
		}

		$from_name = Helper::get_option('from_name');
		if (!empty($from_name)) {
			add_filter('wp_mail_from_name', function($name) use ($from_name) {
				return $from_name;
			});
		}

        add_action('phpmailer_init', [$this, 'process_mail']);
        add_filter('wp_mail', [$this, 'capture_mail_data']);
        add_action('wp_mail_succeeded', [$this, 'on_mail_succeeded']);
        add_action('wp_mail_failed', [$this, 'on_mail_failed']);

        if (Helper::get_option('email_domain')) {
            add_action('register_post', [$this, 'is_valid_email_domain'], 10, 3);
        }

        if (is_admin()) {
            add_action('admin_notices', [$this, 'display_quota_admin_notices']);
        }

        // Initialize table if logging is enabled
        if (Helper::get_option('email_log')) {
            $this->maybe_create_logs_table();
        }
    }

    public function display_quota_admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $today = current_time('Y-m-d');
        $accounts = (array) Helper::get_option('smtp_accounts', []);
        if (empty($accounts)) {
            return;
        }

        $total_accounts = 0;
        $exhausted_accounts = [];

        foreach ($accounts as $idx => $acc) {
            if (empty($acc['username']) || empty($acc['password'])) {
                continue;
            }
            $total_accounts++;
            $limit = intval($acc['daily_limit'] ?? 0);
            $sent_today = intval(get_option('wpex_smtp_sent_' . $idx . '_' . $today, 0));
            $title = !empty($acc['title']) ? $acc['title'] : ($acc['username'] ?? 'Account #' . ($idx + 1));

            if ($limit > 0 && $sent_today >= $limit) {
                $exhausted_accounts[] = [
                    'title' => $title,
                    'sent'  => $sent_today,
                    'limit' => $limit
                ];
            }
        }

        if (empty($exhausted_accounts)) {
            return;
        }

        if (count($exhausted_accounts) >= $total_accounts && $total_accounts > 0) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong><?php esc_html_e('WP EXtra - SMTP Quota Alert:', 'wp-extra'); ?></strong> 
                    <?php printf(
                        esc_html__('All %d configured SMTP accounts have reached their daily sending limit for today (%s). Outgoing WordPress emails may fail or be delayed.', 'wp-extra'),
                        $total_accounts,
                        esc_html($today)
                    ); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=wp-extra')); ?>"><?php esc_html_e('Manage SMTP Settings &rarr;', 'wp-extra'); ?></a>
                </p>
            </div>
            <?php
        } else {
            $names = implode(', ', array_map(function($a) {
                return sprintf('<strong>%s</strong> (%d/%d)', esc_html($a['title']), $a['sent'], $a['limit']);
            }, $exhausted_accounts));
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php esc_html_e('WP EXtra - SMTP Limit Reached:', 'wp-extra'); ?></strong> 
                    <?php printf(
                        esc_html__('The following account(s) reached their daily quota today: %s. Outgoing emails are automatically routed to your next available SMTP account.', 'wp-extra'),
                        $names
                    ); ?>
                </p>
            </div>
            <?php
        }
    }

    public function maybe_create_logs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpex_email_logs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                to_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) DEFAULT '',
                message LONGTEXT DEFAULT '',
                headers TEXT DEFAULT '',
                status VARCHAR(20) NOT NULL DEFAULT 'success',
                error_message TEXT DEFAULT '',
                mailer_account VARCHAR(100) DEFAULT '',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY status (status),
                KEY to_email (to_email(191)),
                KEY created_at (created_at)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
	public function process_mail($phpmailer) {
        $today = current_time('Y-m-d');
        $accounts = (array) Helper::get_option('smtp_accounts', []);

        // Fallback for legacy single account settings if repeater is empty
        if (empty($accounts) && Helper::get_option('smtp_username') && Helper::get_option('smtp_password')) {
            $accounts = [
                [
                    'title'      => 'Main Account',
                    'host'       => Helper::get_option('smtp') ? 'smtp.gmail.com' : Helper::get_option('smtp_host', 'smtp.gmail.com'),
                    'port'       => Helper::get_option('smtp') ? 465 : intval(Helper::get_option('smtp_port', 465)),
                    'encryption' => Helper::get_option('smtp') ? 'ssl' : Helper::get_option('smtp_encryption', 'ssl'),
                    'username'   => Helper::get_option('smtp_username'),
                    'password'   => Helper::get_option('smtp_password'),
                    'daily_limit'=> 0
                ]
            ];
        }

        if (empty($accounts)) {
            self::$active_account_name = 'WP Mail (PHP)';
            return $phpmailer;
        }

        $selected_account = null;
        $selected_idx = null;

        // Find the first account that hasn't exceeded its daily quota
        foreach ($accounts as $idx => $acc) {
            if (empty($acc['username']) || empty($acc['password'])) {
                continue;
            }
            $limit = intval($acc['daily_limit'] ?? 0);
            $sent_today = intval(get_option('wpex_smtp_sent_' . $idx . '_' . $today, 0));

            if ($limit === 0 || $sent_today < $limit) {
                $selected_account = $acc;
                $selected_idx = $idx;
                break;
            }
        }

        // If all accounts reached their limit, fallback to the first active account
        if ($selected_account === null) {
            foreach ($accounts as $idx => $acc) {
                if (!empty($acc['username']) && !empty($acc['password'])) {
                    $selected_account = $acc;
                    $selected_idx = $idx;
                    break;
                }
            }
        }

        if ($selected_account) {
            $providers = [
                'gmail'      => ['host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
                'mailgun'    => ['host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls'],
                'outlook'    => ['host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls'],
                'yahoo'      => ['host' => 'smtp.mail.yahoo.com', 'port' => 587, 'encryption' => 'tls'],
                'aws_ses'    => ['host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
                'zoho'       => ['host' => 'smtp.zoho.com', 'port' => 587, 'encryption' => 'tls'],
                'sendgrid'   => ['host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls'],
                'sendinblue' => ['host' => 'smtp-relay.brevo.com', 'port' => 587, 'encryption' => 'tls'],
            ];

            $prov_key = $selected_account['provider'] ?? 'gmail';
            $preset   = $providers[$prov_key] ?? null;

            self::$active_account_key = $selected_idx;
            self::$active_account_name = !empty($selected_account['title']) ? $selected_account['title'] : (!empty($selected_account['username']) ? $selected_account['username'] : 'SMTP Account #' . ($selected_idx + 1));

            $host       = !empty($selected_account['host']) ? $selected_account['host'] : ($preset['host'] ?? 'smtp.gmail.com');
            $port       = !empty($selected_account['port']) ? intval($selected_account['port']) : ($preset['port'] ?? 465);
            $encryption = !empty($selected_account['encryption']) && $selected_account['encryption'] !== 'none' ? $selected_account['encryption'] : ($preset['encryption'] ?? 'ssl');

            $phpmailer->Host       = $host;
            $phpmailer->Port       = $port;
            $phpmailer->SMTPSecure = ($encryption !== 'none') ? $encryption : '';
            $phpmailer->SMTPAuth   = true;
            $phpmailer->Username   = $selected_account['username'];
            
            $raw_pass = $selected_account['password'] ?? '';
            $decoded = base64_decode($raw_pass, true);
            $phpmailer->Password   = ($decoded !== false && base64_encode($decoded) === $raw_pass) ? $decoded : $raw_pass;

            $global_from_email = Helper::get_option('from_email');
            $global_from_name  = Helper::get_option('from_name');

            $phpmailer->From = !empty($global_from_email) ? sanitize_email($global_from_email) : sanitize_email($selected_account['username']);
            if (!empty($global_from_name)) {
                $phpmailer->FromName = $global_from_name;
            }
        }

        $smtp_options = (array) Helper::get_option('smtp_options', []);
        if (in_array('noverifyssl', $smtp_options, true)) {
            $phpmailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];
        }

        $phpmailer->IsSMTP();
        return $phpmailer;
    }

    public function capture_mail_data($mail_args) {
        self::$current_mail_data = $mail_args;
        return $mail_args;
    }

    public function on_mail_succeeded() {
        $today = current_time('Y-m-d');

        // Increment daily sent counter
        if (self::$active_account_key !== null) {
            $opt_name = 'wpex_smtp_sent_' . self::$active_account_key . '_' . $today;
            $count = intval(get_option($opt_name, 0));
            update_option($opt_name, $count + 1, false);
        }

        // Record log if enabled
        if (Helper::get_option('email_log') && !empty(self::$current_mail_data)) {
            $this->save_email_log('success');
        }
    }

    public function on_mail_failed($wp_error) {
        if (Helper::get_option('email_log') && !empty(self::$current_mail_data)) {
            $error_msg = ($wp_error instanceof \WP_Error) ? $wp_error->get_error_message() : '';
            $this->save_email_log('failed', $error_msg);
        }
    }

    private function save_email_log($status = 'success', $error_msg = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wpex_email_logs';

        $data = self::$current_mail_data;
        if (empty($data)) {
            return;
        }

        $to = is_array($data['to']) ? implode(', ', $data['to']) : (string)$data['to'];
        $subject = isset($data['subject']) ? (string)$data['subject'] : '';
        $message = isset($data['message']) ? (string)$data['message'] : '';
        $headers = isset($data['headers']) ? (is_array($data['headers']) ? maybe_serialize($data['headers']) : (string)$data['headers']) : '';

        $wpdb->insert(
            $table_name,
            [
                'to_email'       => $to,
                'subject'        => $subject,
                'message'        => $message,
                'headers'        => $headers,
                'status'         => $status,
                'error_message'  => $error_msg,
                'mailer_account' => self::$active_account_name,
                'created_at'     => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        // Auto purge old logs based on retention days
        $retention = intval(Helper::get_option('email_log_retention', 30));
        if ($retention > 0 && rand(1, 20) === 1) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention
            ));
        }
    }
    
    public function is_valid_email_domain($login, $email, $errors) {
        $valid_email_domains_string = Helper::get_option('email_domain', '');
        $valid_email_domains = array_filter(array_map('trim', explode("\n", $valid_email_domains_string)));
        if (empty($valid_email_domains)) {
            return;
        }
        $email_domain = substr(strrchr($email, "@"), 1);
        if (!in_array($email_domain, $valid_email_domains, true)) {
            $errors->add('domain_whitelist_error', __('<strong>ERROR</strong>: you can only register using allowed email domains', 'wp-extra'));
        }
    }
    
	protected $features = [
		'smtp_options',
		'no_emails',
	];
    
    public function smtp_options() {
        $options = (array) Helper::get_option('smtp_options', []);
		if (in_array('antispam', $options, true)) {
			add_action('wp_enqueue_scripts', [$this, 'smtpmail_scripts'], 99);
		}
    }
    
	public function smtpmail_scripts() {
		$anti_spam_form = in_array('antispam', (array) Helper::get_option('smtp_options', []), true) ? 1 : 0;
		wp_enqueue_script('security', plugins_url('/assets/js/security.min.js', WPEX_FILE), ['jquery'], defined('WPEX_VERSION') ? WPEX_VERSION : null, true);
		wp_localize_script('security', 'security_setting', ['anti_spam_form' => $anti_spam_form]);
	}
    
    public function no_emails() {
        $no_emails = (array) Helper::get_option('no_emails', []);
		if (in_array('remove_admin', $no_emails, true)) {
            add_filter('admin_email_check_interval', '__return_false');
		}
		if (in_array('auto_update', $no_emails, true)) {
            add_filter('send_core_update_notification_email', '__return_false');
            add_filter('auto_plugin_update_send_email', '__return_false');
            add_filter('auto_theme_update_send_email', '__return_false');
		}
		if (in_array('new_user', $no_emails, true)) {
            add_filter('wp_send_new_user_notification_to_admin', '__return_false');
		}
		if (in_array('password_reset', $no_emails, true)) {
            remove_action('after_password_reset', 'wp_password_change_notification');
            add_filter('send_password_change_email', '__return_false');
            add_filter('woocommerce_disable_password_change_notification', '__return_false');
		}
    }
    
}