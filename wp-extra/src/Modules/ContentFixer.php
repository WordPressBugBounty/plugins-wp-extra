<?php

namespace WPEXtra\Modules;

class ContentFixer {
    
    private static $instance;
    public $http_urls = [];
    public $mixed_content_fixer = false;

    function __construct()
    {
        if (isset(self::$instance)) wp_die();
        self::$instance = $this;

        $this->mixed_content_fixer = is_ssl();
        if (!is_admin() || (is_admin() && $this->mixed_content_fixer)) {
            $this->setup_buffering();
        }
    }

    public static function instance()
    {
        return self::$instance;
    }

    public function setup_buffering()
    {
        if (defined('JSON_REQUEST') || defined('XMLRPC_REQUEST')) return;

        $this->prepare_urls();

        $hook = 'template_redirect';
        add_action($hook, [$this, 'start_buffer']);
        add_action('shutdown', [$this, 'end_buffer'], 999);
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
        if (ob_get_length()) ob_end_flush();
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
}