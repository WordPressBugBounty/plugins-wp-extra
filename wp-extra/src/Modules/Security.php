<?php

namespace WPEXtra\Modules;

use WPEXtra\Core\ClassEXtra;

class Security {

	public function __construct() {
      
		if (wp_extra_get_option('disable_embeds')) {
			add_action('init', [$this, 'disableEmbeds'], 9999);
		}
		if(wp_extra_get_option('disable_xmlrpc')) {
			add_filter('xmlrpc_enabled', '__return_false');
			add_filter('wp_headers', [$this, 'removeXpingback']);
			add_filter('pings_open', '__return_false', 9999);
			add_filter('pre_update_option_enable_xmlrpc', '__return_false');
			add_filter('pre_option_enable_xmlrpc', '__return_zero');
			add_filter('wpex_output_buffer_template_redirect', [$this, 'removePingbackLinks'], 2);
			add_action('init', [$this, 'interceptXmlrpcHeader']);
		}
		
		if(wp_extra_get_option('remove_jquery_migrate')) {
			add_filter('wp_default_scripts', [$this, 'removeJqueryMigrate']);
		}
		if(wp_extra_get_option('remove_wp_version')) {
			remove_action('wp_head', [$this, 'wp_generator']);
			add_filter('the_generator', [$this, 'hideWPversion']);
		}
		if(wp_extra_get_option('remove_wlwmanifest_link')) {
			remove_action('wp_head', [$this, 'wlwmanifest_link']);
		}
		if(wp_extra_get_option('remove_rsd_link')) {
			remove_action('wp_head', [$this, 'rsd_link']);
		}
		if(wp_extra_get_option('remove_shortlink')) {
			remove_action('wp_head', [$this, 'wp_shortlink_wp_head']);
			remove_action ('template_redirect', [$this, 'wp_shortlink_header'], 11, 0);
		}
		if(wp_extra_get_option('disable_rss_feeds')) {
			add_action('template_redirect', [$this, 'disableRSSFeeds'], 1);
		}
		if(wp_extra_get_option('remove_feed_links')) {
			remove_action('wp_head', [$this, 'feed_links'], 2);
			remove_action('wp_head', [$this, 'feed_links_extra'], 3);
		}
		if(wp_extra_get_option('disable_self_pingbacks')) {
			add_action('pre_ping', [$this, 'disableSelfPingbacks']);
		}

		if(wp_extra_get_option('disable_rest_api')) {
			add_filter('rest_authentication_errors', [$this, 'restAuthenticationErrors'], 20);
		}
		if(wp_extra_get_option('remove_rest_api_links')) {
			remove_action('xmlrpc_rsd_apis', [$this, 'rest_output_rsd']);
			remove_action('wp_head', [$this, 'rest_output_link_wp_head']);
			remove_action('template_redirect', [$this, 'rest_output_link_header'], 11, 0);
		}
		if(wp_extra_get_option('disable_heartbeat')) {
			add_action('init', [$this, 'disableHeartbeat'], 1);
		}
		if(wp_extra_get_option('heartbeat_frequency')) {
			add_filter('heartbeat_settings', [$this, 'heartbeatFrequency']);
		}
    }

	public function disableEmbeds() {
		global $wp;
		$wp->public_query_vars = array_diff($wp->public_query_vars, array('embed'));
		add_filter('embed_oembed_discover', '__return_false');
		remove_filter('oembed_dataparse', [$this, 'wp_filter_oembed_result'], 10);
		remove_action('wp_head', [$this, 'wp_oembed_add_discovery_links']);
		remove_action('wp_head', [$this, 'wp_oembed_add_host_js']);
		add_filter('tiny_mce_plugins', [$this, 'disableEmbedsTinyMCE']);
		add_filter('rewrite_rules_array', [$this, 'disableEmbedsRewrites']);
		remove_filter('pre_oembed_result', [$this, 'wp_filter_pre_oembed_result'], 10);
	}

	public function disableEmbedsTinyMCE($plugins) {
		return array_diff($plugins, array('wpembed'));
	}

	public function disableEmbedsRewrites($rules) {
		foreach($rules as $rule => $rewrite) {
			if(false !== strpos($rewrite, 'embed=true')) {
				unset($rules[$rule]);
			}
		}
		return $rules;
	}

	public function removeXpingback($headers) {
		unset($headers['X-Pingback'], $headers['x-pingback']);
		return $headers;
	}

	public function interceptXmlrpcHeader() {
		if(!isset($_SERVER['SCRIPT_FILENAME'])) {
			return;
		}
		if('xmlrpc.php' !== basename($_SERVER['SCRIPT_FILENAME'])) {
			return;
		}
		$header = 'HTTP/1.1 403 Forbidden';
		header($header);
		echo esc_html($header);
		die();
	}

	public function hideWPversion() {
		return '';
	}

	public function removeJqueryMigrate(&$scripts) {
		if(!is_admin()) {
			$scripts->remove('jquery');
			$scripts->add('jquery', false, array( 'jquery-core' ), '1.12.4');
		}
	}


	public function disableRSSFeeds() {
		if(!is_feed() || is_404()) {
			return;
		}
		global $wp_rewrite;
		global $wp_query;

		if(isset($_GET['feed'])) {
			wp_redirect(esc_url_raw(remove_query_arg('feed')), 301);
			exit;
		}

		if(get_query_var('feed') !== 'old') {
			set_query_var('feed', '');
		}
		
		redirect_canonical();

        // Translators: %s is a placeholder for the homepage URL.
		wp_die(sprintf(esc_html__("No feed available, please visit the <a href='%s'>homepage</a>!"), esc_url(home_url('/'))));
	}

	public function disableSelfPingbacks(&$links) {
		$home = get_option('home');
		foreach($links as $l => $link) {
			if(strpos($link, $home) === 0) {
				unset($links[$l]);
			}
		}
	}

	public function restAuthenticationErrors($result) {
        if (!empty($result)) {
            return $result;
        } else {
            $disabled = false;
            $rest_route = $GLOBALS['wp']->query_vars['rest_route'];
            $exceptions = apply_filters('wpex_rest_api_exceptions', array(
                'contact-form-7',
                'wordfence',
                'elementor'
            ));

            foreach ($exceptions as $exception) {
                // Check if $rest_route is an array before using in_array
                if (is_array($rest_route) && in_array($exception, $rest_route)) {
                    return;
                }
            }

            $disableOptions = wp_extra_get_option('disable_rest_api');

            // Ensure $disableOptions is an array
            if (!is_array($disableOptions)) {
                $disableOptions = array();
            }

            if (in_array('non_admins', $disableOptions) && !current_user_can('manage_options')) {
                $disabled = true;
            } elseif (in_array('logged_out', $disableOptions) && !is_user_logged_in()) {
                $disabled = true;
            }
        }

        if ($disabled) {
            return new WP_Error('rest_authentication_error', __('Sorry, you do not have permission to make REST API requests.', 'wp-extra'), array('status' => 401));
        }

        return $result;
    }


	public function disableHeartbeat() {
		if(is_admin()) {
			global $pagenow;
			if(!empty($pagenow)) {
				if($pagenow == 'admin.php') {
					if(!empty($_GET['page'])) {
						$exceptions = array(
							'gf_edit_forms',
							'gf_entries',
							'gf_settings'
						);
						if(in_array($_GET['page'], $exceptions)) {
							return;
						}
					}
				}
				if($pagenow == 'site-health.php') {
					return;
				}
			}
		}
		if(wp_extra_get_option('disable_heartbeat')) {
			if(wp_extra_get_option('disable_heartbeat') == 'everywhere') {
				$this->replaceHearbeat();
			}
			elseif(wp_extra_get_option('disable_heartbeat') == 'allow_posts') {
				global $pagenow;
				if($pagenow != 'post.php' && $pagenow != 'post-new.php') {
					$this->replaceHearbeat();
				}
			}
		}
	}

	public function replaceHearbeat() {
		wp_deregister_script('heartbeat');
		if(is_admin() && wp_extra_get_option('disable_heartbeat')) {
			wp_register_script('hearbeat', plugins_url('/assets/js/heartbeat.js', WPEX_FILE ));
			wp_enqueue_script('hearbeat', plugins_url('/assets/js/heartbeat.js', WPEX_FILE ));
		}
	}

	public function heartbeatFrequency($settings) {
		if(wp_extra_get_option('heartbeat_frequency')) {
			$settings['interval'] = wp_extra_get_option('heartbeat_frequency');
		}
		return $settings;
	}

}