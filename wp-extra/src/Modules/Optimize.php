<?php

namespace WPEXtra\Modules;

class Optimize {

    public function __construct() {
        if (wp_extra_get_option('remove_global_styles')) {
            add_action('init', [$this, 'removeGlobalStyles']);
        }
        if (wp_extra_get_option('disable_emojis')) {
            add_action('init', [$this, 'disableEmojis']);
        }
        if (wp_extra_get_option('disable_dashicons')) {
            add_action('wp_enqueue_scripts', [$this, 'disableDashicons']);
        }
        if (wp_extra_get_option('gutenberg')) {
            add_action( 'wp_enqueue_scripts', [$this, 'remove_wp_block_library_css'], 100 );
        }
		if(wp_extra_get_option('to_home')) {
			add_action('template_redirect', [$this, 'redirect_404_to'], 1);
		}
		if(wp_extra_get_option('minify_html')) {
			add_action('init', [$this, 'clearWhitespace']);
		}
		if(wp_extra_get_option('defer_css')) {
			add_filter('style_loader_tag', [$this, 'add_rel_preload'], 10, 4);
		}
		if(wp_extra_get_option('defer_js') && wp_extra_get_option('defer_js_type') == 'php') {
			add_filter('script_loader_tag', [$this, 'deferScripts'], 10, 3);
		} elseif (wp_extra_get_option('defer_js')) {
			add_action('wp_enqueue_scripts', [$this, 'deferToScripts']);
		}
		if(wp_extra_get_option('query_strings')) {
			add_filter( 'script_loader_src', [$this, 'remove_script_version'], 15, 1 );
			add_filter( 'style_loader_src', [$this, 'remove_script_version'], 15, 1 );
		}
    }

    public function remove_wp_block_library_css() {
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
        wp_dequeue_style( 'wc-block-style' );
        wp_dequeue_style( 'global-styles' );
    }

    public function removeGlobalStyles() {
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
        remove_action( 'wp_footer', 'wp_enqueue_global_styles' );
        remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
        remove_action( 'in_admin_header', 'wp_global_styles_render_svg_filters' );
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

    public function disableDashicons() {
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
            wp_deregister_style('dashicons');
        }
    }
    
	public function redirect_404_to() {
        if (is_404()) {
            $toHomeOption = wp_extra_get_option('to_home');

            if ($toHomeOption == 'random') {
                $randomPost = get_posts('numberposts=1&orderby=rand');

                if ($randomPost) {
                    $redirectUrl = get_permalink($randomPost[0]->ID);
                    wp_redirect($redirectUrl, 301);
                    exit;
                }
            } elseif ($toHomeOption == 'home') {
                wp_redirect(home_url(), 301);
                exit;
            }
        }
    }


    public function minifyHTML($buffer){
        $search = ['/\\n/', '/\\>[^\\S ]+/s', '/[^\\S ]+\\</s', '/(\\s)+/s', '~<!--//(.*?)-->~s'];
        $replace = [' ', '>', '<', '\\1', ''];
        $buffer = preg_replace($search, $replace, $buffer);
        return $buffer;
    }

    public function clearWhitespace(){
		if (!is_admin() && !is_user_logged_in()) {
        	ob_start(array($this, 'minifyHTML'));
		}
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

    public function deferScripts($tag, $handle, $src) {
		if (!is_admin() && !is_user_logged_in()) {
			$defer_handles = explode(PHP_EOL, wp_extra_get_option('defer_js_list'));
			if (in_array($handle, $defer_handles)) {
				$tag = str_replace(' src', ' defer src', $tag);
			}
		}
        return $tag;
    }

	public function deferToScripts() {
		if (!is_admin() && !is_user_logged_in()) {
			$defer_js_list = explode(PHP_EOL, wp_extra_get_option('defer_js_list'));
			wp_enqueue_script('defer', plugins_url( '/assets/js/defer.js', WPEX_FILE ), array('jquery'), null, true);
			wp_localize_script('defer', 'js_data_object', $defer_js_list);
		}
	}

    public function remove_script_version($src) {
        $parts = explode( '?', $src );
        return $parts[0];
    }

}