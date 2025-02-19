<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class Branding extends Base {
    
    private $wp_login_enabled = false;
    
    public function __construct() {
		parent::__construct();
        add_action( 'login_enqueue_scripts', [$this, 'login_styles' ]);
    }

    public function login_styles() {
        $loginButton = Settings::get_option('login_color');
        $loginBg = Settings::get_option('login_bg_color');
        $loginBgImg = Settings::get_option('login_bg_image');
        if (is_numeric($loginBgImg)) {
            $loginBgImg = wp_get_attachment_url(Settings::get_option('login_bg_image'));
        }
        $loginRadius = Settings::get_option('login_form_radius');
        $loginLogo = wp_get_attachment_url(Settings::get_option('login_logo'));
        $login_css = "";
        if (Settings::get_option('login_logo_hide') ) {
            $login_css .= "
                .login h1 {
                    display: none;
                }
                body, html {
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }
                ";
        }
        if ($loginBg) {
            $login_css .= " 
                body {
                    background: {$loginBg};
                }";
        }
        if ($loginBgImg) {
            $login_css .= " 
                body {
                background-image: url({$loginBgImg}); 
                background-size: cover;
                background-repeat: repeat;
            }";
        }
        if ($loginButton) {
            $login_css .= " 
                .login #nav a:hover,
                .login #backtoblog a:hover,
                .login h1 a:hover,
                .login #nav a:focus,
                .login #backtoblog a:focus,
                .login h1 a:focus {
                    color: {$loginButton};
                }
                input[type=text]:focus,
                input[type=password]:focus,
                input[type=checkbox]:focus {
                    border-color: {$loginButton};
                    box-shadow: 0 0 2px {$loginButton};
                }
                .wp-core-ui .button-group.button-large .button, .wp-core-ui .button.button-large {
                    background: {$loginButton};
                    border-color: {$loginButton};
                    box-shadow: 0 1px 0 {$loginButton};
                }";
        }
        if ($loginRadius) {
            $login_css .= " 
                .login form {
                    border: 0px;
                    border-radius: {$loginRadius}px;
                }";
        }
        if ($loginLogo) {
            $login_css .= "
                body.login div#login h1 a {
                background-image: url({$loginLogo});
                background-size: contain; 
                width:auto!important;
                max-width:100%;
            }";
        }
        wp_add_inline_style( 'login', Settings::minifyCSS($login_css) );
    }
    
	protected $features = [
		'login_title',
		'login_url',
		'adminfooter_version',
		'adminfooter_custom',
		'donot_copy',
	];
    
    public function login_title() {
        add_filter('login_title', [$this, 'custom_title']);
    }
    
    public function custom_title() {
        $title = Settings::get_option('login_title') ?: get_option('blogname');
        return $title;
    }
    
    public function login_url() {
        add_filter('site_url', array($this, 'site_url_custom'), 10, 4);
        add_action('plugins_loaded', array($this, 'plugins_loaded_custom'), 2);
        add_action('wp_loaded', array($this, 'wp_loaded_custom'));
        add_filter('wp_redirect', array($this, 'wp_redirect_custom'), 10, 2);
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
        if (get_option('permalink_structure')) {
            return $this->trailingslashit_custom(home_url('/', $scheme) . $this->slug_custom());
        } else {
            return home_url('/', $scheme) . '?' . $this->slug_custom();
        }
    }

    private function trailingslashit_custom($string) {
        if ((substr(get_option('permalink_structure'), -1, 1)) === '/') {
            return trailingslashit($string);
        } else {
            return untrailingslashit($string);
        }
    }

    private function slug_custom() {
        return Settings::get_option('login_url');
    }

    public function site_url_custom($url, $path, $scheme, $blog_id) {
        return $this->filter_wp_login($url, $scheme);
    }

    public function wp_redirect_custom($location, $status) {
        return $this->filter_wp_login($location);
    }

    public function plugins_loaded_custom() {
        global $pagenow;
        $URI = wp_parse_url($_SERVER['REQUEST_URI']);
        $path = !empty($URI['path']) ? untrailingslashit($URI['path']) : '';
        $slug = $this->slug_custom();
        if (!is_admin() && (strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-login.php') !== false || $path === site_url('wp-login', 'relative'))) {
            $this->wp_login_enabled = true;
            $_SERVER['REQUEST_URI'] = $this->trailingslashit_custom('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        } elseif (!is_admin() && (strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-register.php') !== false || strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-signup.php') !== false || $path === site_url('wp-register', 'relative'))) {
            $this->wp_login_enabled = true;
            $_SERVER['REQUEST_URI'] = $this->trailingslashit_custom('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        } elseif ($path === home_url($slug, 'relative') || (!get_option('permalink_structure') && isset($_GET[$slug]) && empty($_GET[$slug]))) {
            $pagenow = 'wp-login.php';
        }
    }

    public function wp_loaded_custom() {
        if (!apply_filters('login_url_custom', true)) {
            return;
        }
        global $pagenow;
        $URI = wp_parse_url($_SERVER['REQUEST_URI']);
        if (is_admin() && !is_user_logged_in() && !defined('WP_CLI') && !defined('DOING_AJAX') && $pagenow !== 'admin-post.php' && (isset($_GET) && empty($_GET['adminhash']) && empty($_GET['newuseremail']))) {
            $this->disable_login_url();
        }
        if ($pagenow === 'wp-login.php' && $URI['path'] !== $this->trailingslashit_custom($URI['path']) && get_option('permalink_structure')) {
            $URL = $this->trailingslashit_custom($this->login_url_custom()) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            wp_safe_redirect($URL);
            die();
        } elseif ($this->wp_login_enabled) {
            $this->disable_login_url();
        } elseif ($pagenow === 'wp-login.php') {
            global $error, $interim_login, $action, $user_login;

            if (is_user_logged_in() && !isset($_REQUEST['action'])) {
                wp_safe_redirect(admin_url());
                die();
            }
            @require_once ABSPATH . 'wp-login.php';
            die();
        }
    }

    private function disable_login_url() {
        wp_redirect(home_url());
        exit();
    }
    
    public function adminfooter_version() {
        add_filter( 'update_footer', '__return_empty_string', 11 );
    }
    
    public function adminfooter_custom() {
        add_filter( 'admin_footer_text', [$this, 'admin_footer_custom']);
    }
    
    public function admin_footer_custom( $footer_text ) {
        if(Settings::get_option('adminfooter_custom') && Settings::isPro()) {
            return wp_kses_post(Settings::get_option('adminfooter_custom'));
        } else {
            return $footer_text;
        }
    }
    
    public function donot_copy() {
        add_action( 'wp_enqueue_scripts', [$this, 'donot_scripts']);
    }

    public function donot_scripts() {
		if ( is_user_logged_in() ) {
			return;
		}
		$copyright = Settings::get_option('donot_copyright', 'WP EXtra');
		$select_text = Settings::get_option('donot_content') ? true : false;
		wp_enqueue_script( 'donotcopy', plugins_url( '/assets/js/copyright.js', WPEX_FILE ), array( 'jquery' ) );
		wp_localize_script( 'donotcopy', 'wpEXtra', array( 'copyright' => $copyright, 'select_text' => $select_text ) );
	}


}