<?php

namespace WPEXtra\Modules;

use WPEXtra\Core\ClassEXtra;

class Branding {
    
    private $wp_login_enabled = false;
    
    public function __construct() {
        add_action( 'login_enqueue_scripts', [$this, 'loginStyle' ]);
        add_filter('login_title', [$this, 'loginTitle']);
        if (wp_extra_get_option('login_url')) {
            add_action('plugins_loaded', array($this, 'wpex_login_url_plugins_loaded'), 2);
            add_action('wp_loaded', array($this, 'wpex_wp_loaded'));
            add_filter('site_url', array($this, 'wpex_site_url'), 10, 4);
            add_filter('wp_redirect', array($this, 'wpex_wp_redirect'), 10, 2);
        }
        if(wp_extra_get_option('adminfooter_version')) {
            add_filter( 'update_footer', '__return_empty_string', 11 );
        }
        add_filter( 'admin_footer_text', [$this, 'wpex_footer_text']);
		if(wp_extra_get_option('donot_copy')) {
			add_action( 'wp_enqueue_scripts', [$this, 'donot_scripts']);
		}
    }

    public function wpex_site_url($url, $path, $scheme, $blog_id) {
        return $this->wpex_filter_wp_login($url, $scheme);
    }

    public function wpex_wp_redirect($location, $status) {
        return $this->wpex_filter_wp_login($location);
    }

    private function wpex_filter_wp_login($url, $scheme = null) {
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
                $url = add_query_arg($query_string, $this->wpex_login_url($scheme));
            } else {
                $url = $this->wpex_login_url($scheme);
            }
        }

        return $url;
    }

    private function wpex_login_url($scheme = null) {
        if (get_option('permalink_structure')) {
            return $this->wpex_trailingslashit(home_url('/', $scheme) . $this->wpex_login_slug());
        } else {
            return home_url('/', $scheme) . '?' . $this->wpex_login_slug();
        }
    }

    private function wpex_trailingslashit($string) {
        if ((substr(get_option('permalink_structure'), -1, 1)) === '/') {
            return trailingslashit($string);
        } else {
            return untrailingslashit($string);
        }
    }

    private function wpex_login_slug() {
        return wp_extra_get_option('login_url');
    }

    public function wpex_login_url_plugins_loaded() {
        global $pagenow;

        $URI = wp_parse_url($_SERVER['REQUEST_URI']);
        $path = !empty($URI['path']) ? untrailingslashit($URI['path']) : '';
        $slug = $this->wpex_login_slug();

        if (!is_admin() && (strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-login.php') !== false || $path === site_url('wp-login', 'relative'))) {
            $this->wp_login_enabled = true;
            $_SERVER['REQUEST_URI'] = $this->wpex_trailingslashit('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        } elseif (!is_admin() && (strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-register.php') !== false || strpos(rawurldecode($_SERVER['REQUEST_URI']), 'wp-signup.php') !== false || $path === site_url('wp-register', 'relative'))) {
            $this->wp_login_enabled = true;
            $_SERVER['REQUEST_URI'] = $this->wpex_trailingslashit('/' . str_repeat('-/', 10));
            $pagenow = 'index.php';
        } elseif ($path === home_url($slug, 'relative') || (!get_option('permalink_structure') && isset($_GET[$slug]) && empty($_GET[$slug]))) {
            $pagenow = 'wp-login.php';
        }
    }

    public function wpex_wp_loaded() {
        if (!apply_filters('wpex_login_url', true)) {
            return;
        }

        global $pagenow;

        $URI = wp_parse_url($_SERVER['REQUEST_URI']);

        if (is_admin() && !is_user_logged_in() && !defined('WP_CLI') && !defined('DOING_AJAX') && $pagenow !== 'admin-post.php' && (isset($_GET) && empty($_GET['adminhash']) && empty($_GET['newuseremail']))) {
            $this->wpex_disable_login_url();
        }

        if ($pagenow === 'wp-login.php' && $URI['path'] !== $this->wpex_trailingslashit($URI['path']) && get_option('permalink_structure')) {
            $URL = $this->wpex_trailingslashit($this->wpex_login_url()) . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            wp_safe_redirect($URL);
            die();
        } elseif ($this->wp_login_enabled) {
            $this->wpex_disable_login_url();
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

    private function wpex_disable_login_url() {
        wp_redirect(home_url());
        exit();
    }
    
    public function loginTitle() {
        $loginText = wp_extra_get_option('login_title') ? wp_extra_get_option('login_title') : get_option('blogname');
        return $loginText;
    }

    public function loginStyle() {
        $minify = ClassEXtra::instance();
        $loginButton = wp_extra_get_option('login_color');
        $loginBg = wp_extra_get_option('login_bg_color');
        $loginBgImg = wp_extra_get_option('login_bg_image');
        if (is_numeric($loginBgImg)) {
            $loginBgImg = wp_get_attachment_url(wp_extra_get_option('login_bg_image'));
        }
        $loginRadius = wp_extra_get_option('login_form_radius');
        $loginLogo = wp_get_attachment_url(wp_extra_get_option('login_logo'));
        $login_css = "";
        if (wp_extra_get_option('login_logo_hide') ) {
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
        wp_add_inline_style( 'login', ClassEXtra::minifyCSS($login_css) );
    }
    
    public function wpex_footer_text( $footer_text ) {
        if(wp_extra_get_option('adminfooter_custom') && ClassEXtra::isPro()) {
            return wp_kses_post(wp_extra_get_option('adminfooter_custom'));
        } else {
            return $footer_text;
        }
    }

    public function donot_scripts() {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		$copyright = wp_extra_get_option('donot_copyright') ? wp_extra_get_option('donot_copyright') : 'WP EXtra';
		$select_text = wp_extra_get_option('donot_content') ? true : false;
		wp_enqueue_script( 'donotcopy', plugins_url( '/assets/js/copyright.js', WPEX_FILE ), array( 'jquery' ) );
		wp_localize_script( 'donotcopy', 'wpEXtra', array( 'copyright' => $copyright, 'select_text' => $select_text ) );
	}


}