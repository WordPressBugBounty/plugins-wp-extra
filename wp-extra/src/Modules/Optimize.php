<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class Optimize extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'remove_global_styles',
		'disable_emojis',
		'disable_dashicons',
		'gutenberg',
		'defer_css',
		'defer_js',
		'query_strings',
	];
    
    public function remove_global_styles() {
        add_action('init', [$this, 'removeGlobalStyles']);
    }

    public function removeGlobalStyles() {
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
        remove_action( 'wp_footer', 'wp_enqueue_global_styles' );
        remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
        remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
    }
    
    public function disable_emojis() {
        add_action('init', [$this, 'disableEmojis']);
    }

    public function disableEmojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', [$this, 'disableEmojisTinyMCE']);
        add_filter('wp_resource_hints', [$this, 'disableEmojisDNSPrefetch'], 10, 2);
        if(!is_admin()) {
            add_filter('emoji_svg_url', '__return_false');
        }
    }

    public function disableEmojisTinyMCE($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, ['wpemoji']);
        } else {
            return [];
        }
    }

    public function disableEmojisDNSPrefetch($urls, $relation_type) {
        if ($relation_type === 'dns-prefetch') {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/2.2.1/svg/');
            $urls = array_diff($urls, [$emoji_svg_url]);
        }
        return $urls;
    }
    
    public function disable_dashicons() {
        add_action('wp_enqueue_scripts', [$this, 'disableDashicons']);
    }

    public function disableDashicons() {
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
            wp_deregister_style('dashicons');
        }
    }
    
    public function gutenberg() {
        add_action( 'wp_enqueue_scripts', [$this, 'remove_wp_block_library_css'], 100 );
    }

    public function remove_wp_block_library_css() {
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'wc-block-style' );
        wp_dequeue_style( 'global-styles' );
    }
    
    public function defer_css() {
        add_filter('style_loader_tag', [$this, 'add_rel_preload'], 10, 4);
    }

    public function add_rel_preload($html, $handle, $href, $media) {
		if (!is_admin() && !is_user_logged_in()) {
			$html = sprintf(
				'<link rel="stylesheet" href="%s" media="print" onload="this.media=\'all\'" id="%s" crossorigin="anonymous"/><noscript><link rel="preload" href="%s" crossorigin="anonymous"></noscript>',
				esc_url($href),
				esc_attr($handle),
				esc_url($href)
			);
		}
		return $html;
	}
    
    public function defer_js() {
		if(Settings::get_option('defer_js_type') == 'php') {
			add_filter('script_loader_tag', [$this, 'deferScripts'], 10, 3);
		} else {
			add_action('wp_enqueue_scripts', [$this, 'deferToScripts']);
		}
    }

    public function deferScripts($tag, $handle, $src) {
		if (!is_admin() && !is_user_logged_in()) {
			$defer_handles = explode(PHP_EOL, Settings::get_option('defer_js_list'));
			if (in_array($handle, $defer_handles)) {
				$tag = str_replace(' src', ' defer src', $tag);
			}
		}
        return $tag;
    }

	public function deferToScripts() {
		if (!is_admin() && !is_user_logged_in()) {
			$defer_js_list = explode(PHP_EOL, Settings::get_option('defer_js_list'));
			wp_enqueue_script('defer', plugins_url( '/assets/js/defer.js', WPEX_FILE ), array('jquery'), null, true);
			wp_localize_script('defer', 'js_data_object', $defer_js_list);
		}
	}

	public function query_strings() {
        add_filter( 'script_loader_src', [$this, 'remove_parameter'], 9999 );
        add_filter( 'style_loader_src', [$this, 'remove_parameter'], 9999 );
    }
    
	public function remove_parameter( $src ) {
		if( is_admin() )
			return $src;
		return strpos( $src, 'ver=' ) ? remove_query_arg( 'ver', $src ) : $src;
	}

}