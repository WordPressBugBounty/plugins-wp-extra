<?php
namespace WPEXtra\Modules\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
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
		'font_preconnect',
		'query_strings',
	];
    
    public function remove_global_styles() {
        add_action('init', [$this, 'removeGlobalStyles']);
    }

    public function removeGlobalStyles() {
        remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
        remove_action('wp_footer', 'wp_enqueue_global_styles');
        remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        remove_action('in_admin_header', 'wp_global_styles_render_svg_filters');
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_style('global-styles');
            wp_dequeue_style('classic-theme-styles');
        }, 100);
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
        if (!is_admin()) {
            add_filter('emoji_svg_url', '__return_false');
        }
    }

    public function disableEmojisTinyMCE($plugins) {
        return is_array($plugins) ? array_diff($plugins, ['wpemoji']) : [];
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
        add_action('wp_enqueue_scripts', [$this, 'remove_wp_block_library_css'], 100);
    }

    public function remove_wp_block_library_css() {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('wc-block-style');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    }

    public function font_preconnect() {
        add_action('wp_head', [$this, 'render_font_preconnect'], 1);
    }

    public function render_font_preconnect() {
        if (!is_admin()) {
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        }
    }

	public function query_strings() {
        add_filter('script_loader_src', [$this, 'remove_parameter'], 9999);
        add_filter('style_loader_src', [$this, 'remove_parameter'], 9999);
    }
    
	public function remove_parameter($src) {
		if (is_admin() || empty($src)) {
			return $src;
		}
		return strpos($src, 'ver=') !== false ? remove_query_arg('ver', $src) : $src;
	}

}