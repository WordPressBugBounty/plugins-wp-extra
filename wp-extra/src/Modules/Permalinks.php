<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;

class Permalinks {
    
    public $http_urls = [];
    public $mixed_content_fixer = false;

    public function __construct()
    {
        if (Settings::get_option('sslfix')) {
            $this->mixed_content_fixer = is_ssl();
            if (!is_admin() || (is_admin() && $this->mixed_content_fixer)) {
                $this->setup_buffering();
            }
        }
        if (Settings::get_option('page_extension')) {
            add_action('init', [$this, 'init_page_slash'], -1);
            add_filter('user_trailingslashit', [$this, 'no_page_slash'], 66, 2);
        }
        if (Settings::get_option('auto_nofollow')) {
            add_filter('the_content', [$this, 'add_nofollow_external_links']);
        }
        if (Settings::get_option('redirect_attachment')) {
            add_action('template_redirect', [$this, 'redirect_attachment_page']);
        }
    }
    
    public function add_nofollow_external_links($content) {
        return preg_replace_callback('/<a(.*?)href="(http[s]?:\/\/[^"]+)"(.*?)>(.*?)<\/a>/i', function ($matches) {
            if (strpos($matches[2], home_url()) === false) {
                $link = $matches[0];
                if (strpos($matches[0], 'rel=') === false) {
                    $link = str_replace('<a', '<a rel="nofollow"', $matches[0]);
                } elseif (!preg_match('/rel="[^"]*nofollow[^"]*"/i', $matches[0])) {
                    $link = str_replace('rel="', 'rel="nofollow ', $matches[0]);
                }
            }
            return $matches[0];
        }, $content);
    }
    
    public function init_page_slash() {
        global $wp_rewrite;
        $page_slash = Settings::get_option('page_slash') ? Settings::get_option('page_slash') : '.html';
        if (!strpos($wp_rewrite->get_page_permastruct(), $page_slash)) {
            $wp_rewrite->page_structure = $wp_rewrite->page_structure . $page_slash;
        }
    }
    
    public function no_page_slash($string, $type) {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks() && $wp_rewrite->use_trailing_slashes == true && $type == 'page') {
            return untrailingslashit($string);
        }
        return $string;
    }

    public function setup_buffering()
    {
        if (defined('JSON_REQUEST') || defined('XMLRPC_REQUEST')) return;

        $this->prepare_urls();

        if (did_action('template_redirect')) {
            $this->start_buffer();
        } else {
            add_action('template_redirect', [$this, 'start_buffer']);
        }
        add_action('shutdown', [$this, 'end_buffer'], PHP_INT_MAX);
    }

    public function filter_buffer($buffer)
    {
        if ($this->mixed_content_fixer) {
            $buffer = $this->replace_insecure_links($buffer);
        }
        return apply_filters("ssl_fixer_output", $buffer);
    }

    public function start_buffer()
    {
        ob_start([$this, 'filter_buffer']);
    }

    public function end_buffer()
    {
        if (ob_get_length()) {
            ob_end_flush();
        }
    }

    public function prepare_urls()
    {
        $home = str_replace("https://", "http://", get_option('home'));
        $root = str_replace("://www.", "://", $home);
        $this->http_urls = [
            str_replace("://", "://www.", $root),
            $root,
            str_replace("/", "\/", $home),
            "src='http://",
            'src="http://',
        ];
    }

    public function replace_insecure_links($str)
    {
        if (strpos($str, "<?xml") === 0) return $str;

        $search = $this->http_urls;
        $replace = str_replace(["http://", "http:\/\/"], ["https://", "https:\/\/"], $search);
        $str = str_replace($search, $replace, $str);

        $patterns = [
            '/url\([\'"]?\K(http:\/\/)(?=[^)]+)/i',
            '/<link [^>]*?href=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
            '/<meta property="og:image" [^>]*?content=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
            '/<form [^>]*?action=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
        ];
        $str = preg_replace($patterns, 'https://', $str);
        $str = preg_replace_callback('/<img[^\>]*[^\>\S]+srcset=[\'"]\K((?:[^"\'\s,]+\s*(?:\s+\d+[wx])(?:,\s*)?)+)["\']/', [$this, 'replace_src_set'], $str);
        return str_replace("<body", '<body data-rsssl=1', $str);
    }

    public function replace_src_set($matches)
    {
        return str_replace("http://", "https://", $matches[0]);
    }
    
    public function redirect_attachment_page() {
        if ( is_attachment() ) {
            global $post;
            if ( $post && $post->post_parent ) {
                wp_safe_redirect( esc_url( get_permalink( $post->post_parent ) ), 301 );
                exit;
            } else {
                wp_safe_redirect( esc_url( home_url( '/' ) ), 301 );
                exit;
            }
        }
    }

}
