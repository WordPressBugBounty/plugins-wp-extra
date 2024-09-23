<?php

namespace WPEXtra\Modules;

class SMTP {

	public function __construct() {
		if (wp_extra_get_option('smtp_options') && in_array('antispam', wp_extra_get_option('smtp_options'))) {
			add_action( 'wp_enqueue_scripts', [$this, 'smtpmail_scripts'], 99 );
		}
		if (wp_extra_get_option('no_emails') && in_array('remove_admin', wp_extra_get_option('no_emails'))) {
            add_filter( 'admin_email_check_interval', '__return_false' );
		}
		if (wp_extra_get_option('no_emails') && in_array('auto_update', wp_extra_get_option('no_emails'))) {
            add_filter( 'send_core_update_notification_email', '__return_false' );
            add_filter( 'auto_plugin_update_send_email', '__return_false' );
            add_filter( 'auto_theme_update_send_email', '__return_false' );
		}
		if (wp_extra_get_option('no_emails') && in_array('new_user', wp_extra_get_option('no_emails'))) {
            add_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );
		}
		if (wp_extra_get_option('no_emails') && in_array('password_reset', wp_extra_get_option('no_emails'))) {
            remove_action( 'after_password_reset', 'wp_password_change_notification' );
            add_filter( 'send_password_change_email', '__return_false' );
            add_filter( 'woocommerce_disable_password_change_notification', '__return_false' );
		}

		$from_email = wp_extra_get_option('from_email');
		if ( ! empty( $from_email ) ) {
			add_filter(
				'wp_mail_from',
				function( $email ) use ( $from_email ) {
					return $from_email;
				}
			);
		}

		$from_name = wp_extra_get_option('from_name');
		if ( ! empty( $from_name ) ) {
			add_filter(
				'wp_mail_from_name',
				function( $email ) use ( $from_name ) {
					return $from_name;
				}
			);
		}
        if (wp_extra_get_option('smtp_username') && wp_extra_get_option('smtp_password')) {
            add_action( 'phpmailer_init', [$this, 'process_mail' ] );
        }
        if (wp_extra_get_option('email_domain')) {
            add_action('register_post', [$this, 'is_valid_email_domain'], 10, 3);
        }
	}

	public function smtpmail_scripts() 
	{
		$anti_spam_form = in_array('antispam', (array) wp_extra_get_option('smtp_options'), true) ? 1 : 0;
		wp_enqueue_script( 'security', plugins_url('/assets/js/security.js', WPEX_FILE ),  array('jquery'), '1.2.13', true );
		wp_localize_script( 'security', 'security_setting', array('anti_spam_form' => $anti_spam_form) );
	}

	public function process_mail( $phpmailer ) {
        if (wp_extra_get_option('smtp')) {
            $phpmailer->Host     = wp_extra_get_option('smtp_host');
            $phpmailer->Port = wp_extra_get_option('smtp_port');
            $phpmailer->SMTPSecure = wp_extra_get_option('smtp_encryption');
            $phpmailer->SMTPAuth = wp_extra_get_option('smtp_auth');
        } else {
            $phpmailer->Host       = "smtp.gmail.com";
            $phpmailer->Port       =  465;
            $phpmailer->SMTPSecure = "ssl";
            $phpmailer->SMTPAuth   = true;
        };
        $phpmailer->Username = wp_extra_get_option('smtp_username');
        $phpmailer->Password = base64_decode(wp_extra_get_option('smtp_password'));
        if (in_array('noverifyssl', wp_extra_get_option('smtp_options'))) {
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
        $valid_email_domains_string = wp_extra_get_option('email_domain');
        $valid_email_domains = array_filter(array_map('trim', explode("\n", $valid_email_domains_string)));
        $email_domain = substr(strrchr($email, "@"), 1);
        if (!in_array($email_domain, $valid_email_domains)) {
            $errors->add('domain_whitelist_error', __('<strong>ERROR</strong>: you can only register using allowed email domains'));
        }
    }
    
}