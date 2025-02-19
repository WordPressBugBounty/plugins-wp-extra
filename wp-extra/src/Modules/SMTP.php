<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class SMTP extends Base {
    
    public function __construct() {
		parent::__construct();
		$from_email = Settings::get_option('from_email');
		if ( ! empty( $from_email ) ) {
			add_filter(
				'wp_mail_from',
				function( $email ) use ( $from_email ) {
					return $from_email;
				}
			);
		}

		$from_name = Settings::get_option('from_name');
		if ( ! empty( $from_name ) ) {
			add_filter(
				'wp_mail_from_name',
				function( $email ) use ( $from_name ) {
					return $from_name;
				}
			);
		}
        if (Settings::get_option('smtp_username') && Settings::get_option('smtp_password')) {
            add_action( 'phpmailer_init', [$this, 'process_mail' ] );
        }
        if (Settings::get_option('email_domain')) {
            add_action('register_post', [$this, 'is_valid_email_domain'], 10, 3);
        }
    }
    
	public function process_mail( $phpmailer ) {
        if (Settings::get_option('smtp')) {
            $phpmailer->Host       = "smtp.gmail.com";
            $phpmailer->Port       =  465;
            $phpmailer->SMTPSecure = "ssl";
            $phpmailer->SMTPAuth   = true;
        } else {
            $phpmailer->Host     = Settings::get_option('smtp_host');
            $phpmailer->Port = Settings::get_option('smtp_port');
            $phpmailer->SMTPSecure = Settings::get_option('smtp_encryption');
            $phpmailer->SMTPAuth = Settings::get_option('smtp_auth');
        };
        $phpmailer->Username = Settings::get_option('smtp_username');
        $phpmailer->Password = base64_decode(Settings::get_option('smtp_password'));
        if (in_array('noverifyssl', Settings::get_option('smtp_options'))) {
            $phpmailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        }
        $phpmailer->IsSMTP();
        return $phpmailer;
    }
    
    public function is_valid_email_domain($login, $email, $errors) {
        $valid_email_domains_string = Settings::get_option('email_domain');
        $valid_email_domains = array_filter(array_map('trim', explode("\n", $valid_email_domains_string)));
        $email_domain = substr(strrchr($email, "@"), 1);
        if (!in_array($email_domain, $valid_email_domains)) {
            $errors->add('domain_whitelist_error', __('<strong>ERROR</strong>: you can only register using allowed email domains'));
        }
    }
    
	protected $features = [
		'smtp_options',
		'no_emails',
	];
    
    public function smtp_options() {
		if (in_array('antispam', Settings::get_option('smtp_options'))) {
			add_action( 'wp_enqueue_scripts', [$this, 'smtpmail_scripts'], 99 );
		}
    }
    
	public function smtpmail_scripts() {
		$anti_spam_form = in_array('antispam', (array) Settings::get_option('smtp_options'), true) ? 1 : 0;
		wp_enqueue_script( 'security', plugins_url('/assets/js/security.js', WPEX_FILE ),  ['jquery'], null, true );
		wp_localize_script( 'security', 'security_setting', array('anti_spam_form' => $anti_spam_form) );
	}
    
    public function no_emails() {
		if (in_array('remove_admin', Settings::get_option('no_emails'))) {
            add_filter( 'admin_email_check_interval', '__return_false' );
		}
		if (in_array('auto_update', Settings::get_option('no_emails'))) {
            add_filter( 'send_core_update_notification_email', '__return_false' );
            add_filter( 'auto_plugin_update_send_email', '__return_false' );
            add_filter( 'auto_theme_update_send_email', '__return_false' );
		}
		if (in_array('new_user', Settings::get_option('no_emails'))) {
            add_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );
		}
		if (in_array('password_reset', Settings::get_option('no_emails'))) {
            remove_action( 'after_password_reset', 'wp_password_change_notification' );
            add_filter( 'send_password_change_email', '__return_false' );
            add_filter( 'woocommerce_disable_password_change_notification', '__return_false' );
		}
    }
    
}