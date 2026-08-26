<?php
namespace WPEXtra\Modules\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class Branding extends Base {
    
    private $wp_login_enabled = false;
    
    public function __construct() {
		parent::__construct();
        $preset = Helper::get_option('login_preset', 'default');
        if (
            ($preset && $preset !== 'default') ||
            Helper::get_option('login_color') ||
            Helper::get_option('login_bg_color') ||
            Helper::get_option('login_bg_image') ||
            Helper::get_option('login_logo') ||
            Helper::get_option('login_logo_hide') ||
            Helper::get_option('login_form_radius') ||
            Helper::get_option('login_link_form') ||
            Helper::get_option('login_placeholder')
        ) {
            add_action('login_enqueue_scripts', [$this, 'login_styles']);
        }

        if (Helper::get_option('login_placeholder') || Helper::get_option('login_remember')) {
            add_action('login_head', [$this, 'login_scripts'], 1);
        }
        
        if (Helper::get_option('turnstile')) {
            add_action('login_enqueue_scripts', [$this, 'enqueue_turnstile_script']);
            add_action('login_form', [$this, 'display_turnstile_captcha']);
            add_filter('wp_authenticate_user', [$this, 'verify_turnstile_captcha'], 10, 3);
        }

        if (Helper::get_option('turnstile_my_account') && Helper::is_woo_active()) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_turnstile_script']);
            add_action('woocommerce_login_form', [$this, 'display_turnstile_captcha']);
            add_action('woocommerce_register_form', [$this, 'display_turnstile_captcha']);
            add_action('woocommerce_lostpassword_form', [$this, 'display_turnstile_captcha']);
            add_action('flatsome_account_login_lightbox', [$this, 'display_turnstile_captcha']);
            add_filter('woocommerce_process_login_errors', [$this, 'verify_woocommerce_turnstile'], 10, 3);
            add_filter('woocommerce_process_registration_errors', [$this, 'verify_woocommerce_turnstile'], 10, 4);
            add_action('wp_footer', [$this, 'flatsome_modal_turnstile_script'], 99);
        }
    }

    public function login_styles() {
        $preset    = Helper::get_option('login_preset', 'default');
        $login_css = '
            html, body.login {
                height: 100% !important;
                min-height: 100vh !important;
            }
            body.login {
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                margin: 0 !important;
                padding: 30px 16px !important;
                box-sizing: border-box !important;
            }
            body.login #login {
                padding: 0 !important;
                width: 100% !important;
                max-width: 390px !important;
                margin: auto !important;
                box-sizing: border-box !important;
            }
            .login form p.forgetmenot {
                display: inline-flex;
                align-items: center;
                margin: 0;
                min-height: 42px;
                float: left;
            }
            .login form p.forgetmenot label {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 13px;
                cursor: pointer;
                line-height: 1.4;
            }
            .login form p.forgetmenot input[type="checkbox"] {
                margin: 0 8px 0 0 !important;
                border-radius: 4px;
                width: 18px !important;
                height: 18px !important;
                min-width: 18px !important;
                float: none !important;
                vertical-align: middle !important;
                position: relative;
            }
            .login form p.submit {
                float: right;
                margin: 0;
            }
            .login form p.submit .button-primary {
                min-height: 42px !important;
                padding: 0 22px !important;
                line-height: 40px !important;
                font-size: 14px !important;
                border-radius: 8px !important;
                font-weight: 600 !important;
            }
            .login form::after {
                content: "";
                display: table;
                clear: both;
            }
            .login #nav {
                float: right;
                text-align: right;
                padding: 0;
                margin: 16px 0 0;
                max-width: 50%;
                box-sizing: border-box;
            }
            .login #backtoblog {
                float: left;
                text-align: left;
                padding: 0;
                margin: 16px 0 0;
                max-width: 50%;
                box-sizing: border-box;
            }
            .login #nav a, .login #backtoblog a {
                font-size: 13px;
                font-weight: 500;
                text-decoration: none !important;
                display: inline-block;
                transition: opacity 0.2s ease, color 0.2s ease;
            }
            .login #nav a:hover, .login #backtoblog a:hover {
                text-decoration: underline !important;
            }
            .login .language-switcher {
                clear: both;
                padding-top: 16px;
                margin-top: 16px;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
            }
            .login .language-switcher form {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                display: inline-flex !important;
                align-items: center !important;
                gap: 8px !important;
                justify-content: center !important;
            }
            .login .language-switcher label {
                display: inline-flex !important;
                align-items: center !important;
                gap: 6px !important;
                margin: 0 !important;
                padding: 0 !important;
                position: static !important;
                font-size: 13px !important;
                font-weight: 500 !important;
                line-height: 1.4 !important;
            }
            .login .language-switcher label .screen-reader-text {
                position: static !important;
                width: auto !important;
                height: auto !important;
                clip: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 13px !important;
                font-weight: 500 !important;
                white-space: nowrap !important;
                order: 1 !important;
            }
            .login .language-switcher label .dashicons,
            .login .language-switcher label .dashicons-translation {
                font-size: 18px !important;
                width: 18px !important;
                height: 18px !important;
                line-height: 18px !important;
                display: inline-block !important;
                margin: 0 0 0 2px !important;
                position: static !important;
                float: none !important;
                order: 2 !important;
                vertical-align: middle !important;
            }
            .login .language-switcher select {
                display: inline-flex !important;
                align-items: center !important;
                border-radius: 8px !important;
                height: 38px !important;
                line-height: 38px !important;
                padding: 0 34px 0 12px !important;
                margin: 0 !important;
                font-size: 13px !important;
                cursor: pointer;
                appearance: none !important;
                -webkit-appearance: none !important;
                -moz-appearance: none !important;
                background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23475569%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E") !important;
                background-repeat: no-repeat !important;
                background-position: right 10px center !important;
                background-size: 16px 16px !important;
                min-width: 140px;
                max-width: 180px;
                box-sizing: border-box !important;
                vertical-align: middle !important;
            }
            .login .language-switcher .button {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                height: 38px !important;
                line-height: 38px !important;
                padding: 0 14px !important;
                margin: 0 !important;
                border-radius: 8px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                cursor: pointer;
                box-sizing: border-box !important;
                vertical-align: middle !important;
                transition: all 0.2s ease;
            }
        ';

        if ($preset === 'minimal_white') {
            $login_css .= '
                body.login { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
                .login h1 a { background-size: contain; width: auto !important; max-width: 220px; }
                .login form { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02); padding: 30px 26px; }
                .login form label { font-weight: 500; color: #475569; font-size: 13px; }
                .login input[type=text], .login input[type=password] { border-radius: 8px !important; border: 1px solid #cbd5e1 !important; font-size: 14px !important; padding: 6px 12px !important; }
                .login input[type=text]:focus, .login input[type=password]:focus { border-color: #2563eb !important; box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2) !important; }
                .wp-core-ui .button.button-large { background: #2563eb !important; border-color: #2563eb !important; border-radius: 8px !important; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2) !important; transition: all 0.2s ease; }
                .wp-core-ui .button.button-large:hover { background: #1d4ed8 !important; border-color: #1d4ed8 !important; }
                .login #nav a, .login #backtoblog a { color: #64748b; }
                .login #nav a:hover, .login #backtoblog a:hover { color: #2563eb; }
                .login .language-switcher label { color: #64748b; }
                .login .language-switcher select { background-color: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #334155 !important; }
                .login .language-switcher .button { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; }
                .login .language-switcher .button:hover { background: #f1f5f9; color: #0f172a; }
            ';
        } elseif ($preset === 'dark_modern') {
            $login_css .= '
                body.login { background: #0f172a; color: #cbd5e1; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
                .login h1 a { filter: brightness(0) invert(1); background-size: contain; width: auto !important; max-width: 220px; }
                .login form { background: rgba(30, 41, 59, 0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); padding: 30px 26px; color: #e2e8f0; }
                .login form label { font-weight: 500; color: #94a3b8; font-size: 13px; }
                .login input[type=text], .login input[type=password] { background: #1e293b !important; color: #f8fafc !important; border: 1px solid #334155 !important; border-radius: 8px !important; font-size: 14px !important; padding: 6px 12px !important; }
                .login input[type=text]:focus, .login input[type=password]:focus { border-color: #6366f1 !important; box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25) !important; }
                .wp-core-ui .button.button-large { background: linear-gradient(135deg, #6366f1, #8b5cf6) !important; border: none !important; border-radius: 8px !important; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4) !important; text-shadow: none; transition: all 0.2s ease; }
                .wp-core-ui .button.button-large:hover { opacity: 0.95; transform: translateY(-1px); }
                .login #nav a, .login #backtoblog a { color: #94a3b8; }
                .login #nav a:hover, .login #backtoblog a:hover { color: #c7d2fe; }
                .login .language-switcher label { color: #94a3b8; }
                .login .language-switcher select { background-color: #1e293b !important; border: 1px solid #334155 !important; color: #f8fafc !important; background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2020%2020%22%20fill%3D%22%23cbd5e1%22%3E%3Cpath%20fill-rule%3D%22evenodd%22%20d%3D%22M5.293%207.293a1%201%200%20011.414%200L10%2010.586l3.293-3.293a1%201%200%20111.414%201.414l-4%204a1%201%200%2001-1.414%200l-4-4a1%201%200%20010-1.414z%22%20clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E") !important; }
                .login .language-switcher .button { background: #334155 !important; border: 1px solid #475569 !important; color: #f8fafc !important; }
                .login .language-switcher .button:hover { background: #475569 !important; }
            ';
        } elseif ($preset === 'gradient_vibrant') {
            $login_css .= '
                body.login { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%); background-attachment: fixed; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
                .login h1 a { filter: brightness(0) invert(1); background-size: contain; width: auto !important; max-width: 220px; }
                .login form { background: rgba(255, 255, 255, 0.96); border: none; border-radius: 16px; box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.25); padding: 30px 26px; }
                .login form label { font-weight: 500; color: #334155; font-size: 13px; }
                .login input[type=text], .login input[type=password] { border-radius: 8px !important; border: 1px solid #cbd5e1 !important; font-size: 14px !important; padding: 6px 12px !important; }
                .login input[type=text]:focus, .login input[type=password]:focus { border-color: #7c3aed !important; box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.2) !important; }
                .wp-core-ui .button.button-large { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important; border: none !important; border-radius: 8px !important; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.4) !important; text-shadow: none; }
                .login #nav a, .login #backtoblog a { color: #ffffff !important; opacity: 0.95; font-weight: 500; text-shadow: 0 1px 3px rgba(0,0,0,0.3); }
                .login #nav a:hover, .login #backtoblog a:hover { opacity: 1; text-decoration: underline !important; }
                .login .language-switcher label { color: #ffffff !important; font-weight: 500; text-shadow: 0 1px 3px rgba(0,0,0,0.3); }
                .login .language-switcher select { background-color: rgba(255, 255, 255, 0.95) !important; border: 1px solid rgba(255, 255, 255, 0.5) !important; color: #1e293b !important; }
                .login .language-switcher .button { background: rgba(255, 255, 255, 0.25) !important; border: 1px solid rgba(255, 255, 255, 0.4) !important; color: #ffffff !important; backdrop-filter: blur(8px); }
                .login .language-switcher .button:hover { background: rgba(255, 255, 255, 0.4) !important; }
            ';
        }

        // Common Branding Options (Logo, Placeholders, Footer Links) - Applies to ALL Presets
        $loginLogo = Helper::get_image_url('login_logo');
        if (Helper::get_option('login_logo_hide')) {
            $login_css .= 'body.login #login h1, .login h1, .login .wp-login-logo { display: none !important; }';
        } elseif ($loginLogo) {
            $login_css .= "body.login div#login h1 a, .login h1 a { background-image: url({$loginLogo}) !important; background-size: contain !important; width: auto !important; max-width: 100% !important; }";
        }

        $links = (array) Helper::get_option('login_link_form', []);
        if (in_array('remember', $links, true)) {
            $login_css .= '.login form .forgetmenot { display: none !important; } .login .button-primary { width: 100% !important; min-height: 42px !important; font-size: 15px !important; }';
        }
        if (in_array('privacy', $links, true)) {
            $login_css .= '.login .privacy-policy-page-link { display: none !important; }';
        }
        if (Helper::get_option('login_placeholder')) {
            $login_css .= "
                .login label[for=user_login], .login label[for=user_pass] { display: none !important; }
                .login input.password-input { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI'; }
                .login input::-webkit-input-placeholder { padding-left: 5px; transition: transform 150ms cubic-bezier(0.4, 0, 0.2, 1), opacity 150ms cubic-bezier(0.4, 0, 0.2, 1); font-size: 13px; }
                .login input:focus::-webkit-input-placeholder { transform: translateX(0px) translateY(-15px); font-size: 10px; color: #999; }
            ";
        }

        // Custom Layout Settings (Color, Background, Radius)
        if ($preset === 'custom' || $preset === 'default') {
            $loginButton = Helper::get_option('login_color');
            $loginBg     = Helper::get_option('login_bg_color');
            $loginBgImg  = Helper::get_image_url('login_bg_image');
            $loginRadius = Helper::get_option('login_form_radius');

            if ($loginBg) {
                $login_css .= "body { background: {$loginBg} !important; }";
            }
            if ($loginBgImg) {
                $login_css .= "body { background-image: url({$loginBgImg}) !important; background-size: cover !important; background-repeat: repeat !important; }";
            }
            if ($loginButton) {
                $login_css .= "
                    .login #nav a:hover, .login #backtoblog a:hover, .login h1 a:hover, .login #nav a:focus, .login #backtoblog a:focus, .login h1 a:focus { color: {$loginButton} !important; }
                    input[type=text]:focus, input[type=password]:focus, input[type=checkbox]:focus { border-color: {$loginButton} !important; box-shadow: 0 0 2px {$loginButton} !important; }
                    .wp-core-ui .button-group.button-large .button, .wp-core-ui .button.button-large { background: {$loginButton} !important; border-color: {$loginButton} !important; box-shadow: 0 1px 0 {$loginButton} !important; }";
            }
            if ($loginRadius) {
                $login_css .= ".login form { border-radius: {$loginRadius}px !important; }";
            }
        }

        if (!empty($login_css)) {
            wp_add_inline_style('login', Helper::minifyCSS($login_css));
        }
    }
    
	protected $features = [
		'login_title',
		'login_url',
		'donot_copy',
		'login_logo_url',
		'login_link_form',
		'adminfooter_version',
		'adminfooter_custom',
		'donot_back',
		'limit_login',
	];
    
    public function login_title() {
        add_filter('login_title', [$this, 'custom_title']);
    }
    
    public function custom_title($title = '') {
        $custom = Helper::get_option('login_title');
        return !empty($custom) ? esc_html($custom) : ($title ?: get_option('blogname'));
    }
    
    public function login_url() {
        add_filter('site_url', [$this, 'site_url_custom'], 10, 4);
        add_action('plugins_loaded', [$this, 'plugins_loaded_custom'], 2);
        add_action('wp_loaded', [$this, 'wp_loaded_custom']);
        add_filter('wp_redirect', [$this, 'wp_redirect_custom'], 10, 2);
    }

    private function filter_wp_login($url, $scheme = null) {
        if (strpos($url, 'wp-login.php') !== false) {
            if (is_ssl()) {
                $scheme = 'https';
            }
            $query_string = explode('?', $url);
            if (isset($query_string[1])) {
                parse_str($query_string[1], $query_string);
                if (isset($query_string['login'])) {
                    $query_string['login'] = rawurlencode($query_string['login']);
                }
                $url = add_query_arg($query_string, $this->login_url_custom($scheme));
            } else {
                $url = $this->login_url_custom($scheme);
            }
        }
        return $url;
    }

    private function login_url_custom($scheme = null) {
        $slug = $this->slug_custom();
        if (empty($slug)) {
            return site_url('wp-login.php', $scheme);
        }
        if (get_option('permalink_structure')) {
            return $this->trailingslashit_custom(home_url('/' . $slug, $scheme));
        } else {
            return home_url('/', $scheme) . '?' . $slug;
        }
    }

    private function trailingslashit_custom($string) {
        if (substr(get_option('permalink_structure'), -1, 1) === '/') {
            return trailingslashit($string);
        } else {
            return untrailingslashit($string);
        }
    }

    private function slug_custom() {
        return trim(Helper::get_option('login_url', ''), '/');
    }

    public function site_url_custom($url, $path, $scheme, $blog_id) {
        return $this->filter_wp_login($url, $scheme);
    }

    public function wp_redirect_custom($location, $status) {
        return $this->filter_wp_login($location);
    }

    public function plugins_loaded_custom() {
        global $pagenow;
        $slug = $this->slug_custom();
        if (empty($slug)) {
            return;
        }

        $URI                = wp_parse_url($_SERVER['REQUEST_URI'] ?? '');
        $req_path           = untrailingslashit($URI['path'] ?? '');
        $home_path          = untrailingslashit(wp_parse_url(home_url(), PHP_URL_PATH) ?? '');
        $site_path          = untrailingslashit(wp_parse_url(site_url(), PHP_URL_PATH) ?? '');
        $expected_slug_path = untrailingslashit($home_path . '/' . $slug);

        $is_login_page = ($req_path === untrailingslashit($site_path . '/wp-login.php')) ||
                         (strpos(rawurldecode($_SERVER['REQUEST_URI'] ?? ''), 'wp-login.php') !== false) ||
                         ($req_path === untrailingslashit($site_path . '/wp-login'));

        $is_register_page = ($req_path === untrailingslashit($site_path . '/wp-register.php')) ||
                            ($req_path === untrailingslashit($site_path . '/wp-signup.php')) ||
                            ($req_path === untrailingslashit($site_path . '/wp-register'));

        if (!is_admin() && $is_login_page) {
            $this->wp_login_enabled = true;
            $_SERVER['REQUEST_URI'] = $this->trailingslashit_custom('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        } elseif (!is_admin() && $is_register_page) {
            $this->wp_login_enabled = true;
            $_SERVER['REQUEST_URI'] = $this->trailingslashit_custom('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        } elseif ($req_path === $expected_slug_path || isset($_GET[$slug])) {
            $pagenow = 'wp-login.php';
        }
    }

    public function wp_loaded_custom() {
        if (!apply_filters('login_url_custom', true)) {
            return;
        }
        global $pagenow;
        $URI = wp_parse_url($_SERVER['REQUEST_URI'] ?? '');
        if (is_admin() && !is_user_logged_in() && !defined('WP_CLI') && !defined('DOING_AJAX') && $pagenow !== 'admin-post.php' && (isset($_GET) && empty($_GET['adminhash']) && empty($_GET['newuseremail']))) {
            $this->disable_login_url();
        }
        if ($pagenow === 'wp-login.php' && isset($URI['path']) && $URI['path'] !== $this->trailingslashit_custom($URI['path']) && get_option('permalink_structure')) {
            $URL = $this->trailingslashit_custom($this->login_url_custom()) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            wp_safe_redirect($URL);
            exit;
        } elseif ($this->wp_login_enabled) {
            $this->disable_login_url();
        } elseif ($pagenow === 'wp-login.php') {
            if (is_user_logged_in() && !isset($_REQUEST['action'])) {
                wp_safe_redirect(admin_url());
                exit;
            }
            @require_once ABSPATH . 'wp-login.php';
            exit;
        }
    }

    public function enqueue_turnstile_script() {
        wp_enqueue_script('cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, true);
    }

    public function display_turnstile_captcha() {
        $site_key = Helper::get_option('turnstile_site_key', '');
        if (!empty($site_key)) {
            echo '<div class="cf-turnstile" data-sitekey="' . esc_attr($site_key) . '" style="margin-bottom: 15px; margin-top: 5px;"></div>';
        }
    }

    public function verify_turnstile_captcha($user, $username, $password = '') {
        if (isset($_POST['wp-submit'])) {
            $secret_key = Helper::get_option('turnstile_secret_key', '');
            $response_token = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');

            if (empty($secret_key)) {
                return $user;
            }

            if (empty($response_token)) {
                return new \WP_Error('authentication_failed', __('<strong>ERROR</strong>: Please complete the security captcha.', 'wp-extra'));
            }

            $remote_ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

            $response = wp_safe_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'body' => [
                    'secret'   => $secret_key,
                    'response' => $response_token,
                    'remoteip' => $remote_ip
                ],
                'timeout' => 10,
            ]);

            if (is_wp_error($response)) {
                return new \WP_Error('authentication_failed', __('<strong>ERROR</strong>: Unable to verify captcha.', 'wp-extra'));
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($body['success'])) {
                return new \WP_Error('authentication_failed', __('<strong>ERROR</strong>: Captcha verification failed.', 'wp-extra'));
            }
        }
        return $user;
    }

    public function verify_woocommerce_turnstile($validation_error, $username = '', $password = '') {
        $secret_key = Helper::get_option('turnstile_secret_key', '');
        $response_token = sanitize_text_field($_POST['cf-turnstile-response'] ?? '');

        if (empty($secret_key)) {
            return $validation_error;
        }

        if (is_wp_error($validation_error) && $validation_error->get_error_code()) {
            return $validation_error;
        }

        if (empty($response_token)) {
            $error = new \WP_Error();
            $error->add('authentication_failed', __('<strong>ERROR</strong>: Please complete the security captcha.', 'wp-extra'));
            return $error;
        }

        $remote_ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

        $response = wp_safe_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'body' => [
                'secret'   => $secret_key,
                'response' => $response_token,
                'remoteip' => $remote_ip
            ],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            $error = new \WP_Error();
            $error->add('authentication_failed', __('<strong>ERROR</strong>: Unable to verify captcha.', 'wp-extra'));
            return $error;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['success'])) {
            $error = new \WP_Error();
            $error->add('authentication_failed', __('<strong>ERROR</strong>: Captcha verification failed.', 'wp-extra'));
            return $error;
        }

        return $validation_error;
    }

    public function flatsome_modal_turnstile_script() {
        if (!Helper::get_option('turnstile_my_account') || !Helper::is_woo_active()) {
            return;
        }
        $site_key = Helper::get_option('turnstile_site_key', '');
        if (empty($site_key)) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(function($) {
            function initTurnstileInPopups() {
                if (window.turnstile && typeof window.turnstile.render === 'function') {
                    $('#login-form-popup .cf-turnstile, .mfp-content .cf-turnstile').each(function() {
                        if (!$(this).children().length) {
                            var sk = $(this).data('sitekey') || '<?php echo esc_js($site_key); ?>';
                            window.turnstile.render(this, { sitekey: sk });
                        }
                    });
                }
            }

            $(document).on('mfpOpen click', '.account-item > a, a[href="#header-account-register"], a[href="#header-account-login"], a[data-open="#login-form-popup"]', function() {
                setTimeout(initTurnstileInPopups, 120);
            });
        });
        </script>
        <?php
    }

    private function disable_login_url() {
        wp_safe_redirect(home_url());
        exit;
    }
    
    public function donot_copy() {
        add_action('wp_enqueue_scripts', [$this, 'donot_scripts']);
    }

    public function donot_scripts() {
		if (is_user_logged_in()) {
			return;
		}
		$copyright   = Helper::get_option('donot_copyright', 'WP EXtra');
		$select_text = (bool) Helper::get_option('donot_content');
		wp_enqueue_script('donotcopy', plugins_url('/assets/js/copyright.min.js', WPEX_FILE), ['jquery']);
		wp_localize_script('donotcopy', 'wpEXtra', ['copyright' => $copyright, 'select_text' => $select_text]);
	}

    public function login_logo_url() {
        add_filter('login_headerurl', [$this, 'logo_url']);
        add_filter('login_headertext', [$this, 'logo_url']);
    }

    public function logo_url() {
        return Helper::get_option('login_logo_url') ?: home_url('/');
    }

    public function login_link_form() {
        $links = (array) Helper::get_option('login_link_form', []);
        if (in_array('language', $links, true)) {
            add_filter('login_display_language_dropdown', '__return_false');
        }
        if (in_array('lost', $links, true)) {
            add_filter('option_users_can_register', [$this, 'remove_siteuser']);
            add_filter('lost_password_html_link', [$this, 'remove_site_link']);
        }
        if (in_array('backto', $links, true)) {
            add_filter('login_site_html_link', [$this, 'remove_site_link']);
        }
    }

    public function remove_siteuser($value) {
        $script = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
        if ($script === 'wp-login.php') {
            $value = false;
        }
        return $value;
    }

    public function remove_site_link($link) {
        return '';
    }

    public function login_scripts() {
        $loginPlaceholder = Helper::get_option('login_placeholder');
        $loginRemember    = Helper::get_option('login_remember');
        ?>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if ($loginPlaceholder): ?>
            var u = document.getElementById("user_login");
            var p = document.getElementById("user_pass");
            if (u) u.placeholder = "<?php echo esc_js(__('Username or Email Address')); ?>";
            if (p) p.placeholder = "<?php echo esc_js(__('Password')); ?>";
            <?php endif; ?>
            <?php if ($loginRemember): ?>
            var r = document.getElementById("rememberme");
            if (r) r.checked = true;
            <?php endif; ?>
        });
        </script>
        <?php
    }

    public function adminfooter_version() {
        add_filter('update_footer', '__return_empty_string', 99);
    }

    public function adminfooter_custom() {
        add_filter('admin_footer_text', [$this, 'render_admin_footer_custom'], 99);
    }

    public function render_admin_footer_custom($footer_text) {
        $custom = Helper::get_option('adminfooter_custom');
        if (!empty($custom)) {
            return wp_kses_post(wpautop($custom));
        }
        return $footer_text;
    }

    public function donot_back() {
        add_action('wp_enqueue_scripts', [$this, 'donot_back_scripts']);
    }

    public function donot_back_scripts() {
        if (is_user_logged_in()) {
            return;
        }
        wp_enqueue_script('history-back', plugins_url('/assets/js/back.min.js', WPEX_FILE), ['jquery']);
    }

    public function limit_login() {
        add_action('wp_login_failed', [$this, 'handle_failed_login']);
        add_action('wp_login', [$this, 'clear_failed_login_transient'], 10, 2);
        add_filter('authenticate', [$this, 'check_lockout'], 30, 3);
    }

    public function check_lockout($user, $username, $password) {
        if (empty($username)) {
            return $user;
        }
        $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = 'wpex_failed_login_' . md5(sanitize_user($username) . '_' . $ip);
        $attempts = get_transient($key);
        $max_limit = intval(Helper::get_option('limit_login', 5));

        if ($attempts && $attempts['count'] >= $max_limit) {
            $remaining_time = time() - $attempts['last_attempt'];
            if ($remaining_time < 600) {
                $wait_minutes = ceil((600 - $remaining_time) / 60);
                return new \WP_Error('lockout', sprintf(__('Too many failed login attempts. Please wait %d minutes before trying again.', 'wp-extra'), $wait_minutes));
            } else {
                delete_transient($key);
            }
        }

        return $user;
    }

    public function handle_failed_login($username) {
        if (empty($username)) {
            return;
        }
        $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = 'wpex_failed_login_' . md5(sanitize_user($username) . '_' . $ip);
        $attempts = get_transient($key);

        if (!$attempts) {
            $attempts = ['count' => 1, 'last_attempt' => time()];
        } else {
            $attempts['count']++;
            $attempts['last_attempt'] = time();
        }

        set_transient($key, $attempts, 600);
    }

    public function clear_failed_login_transient($user_login, $user) {
        $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        delete_transient('wpex_failed_login_' . md5(sanitize_user($user_login) . '_' . $ip));
    }

}