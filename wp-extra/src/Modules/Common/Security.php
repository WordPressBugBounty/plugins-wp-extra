<?php
namespace WPEXtra\Modules\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class Security extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'disable_embeds',
		'disable_xmlrpc',
		'remove_jquery_migrate',
		'remove_wp_version',
		'clean_head_links',
		'remove_wlwmanifest_link',
		'remove_rsd_link',
		'remove_shortlink',
		'disable_rss_feeds',
		'disable_self_pingbacks',
		'block_user_enumeration',
		'security_headers',
		'themeplugin_edits',
		'core_updates',
		'http_request',
		'disable_rest_api',
		'remove_rest_api_links',
		'disable_heartbeat',
		'heartbeat_frequency',
		'remove_blocks',
	];

	public function clean_head_links() {
		$this->remove_wlwmanifest_link();
		$this->remove_rsd_link();
		$this->remove_shortlink();
		$this->disable_self_pingbacks();
	}
    
    public function disable_embeds() {
        add_action('init', [$this, 'disable_embed'], 9999);
    }

	public function disable_embed() {
		global $wp;
		if (isset($wp->public_query_vars) && is_array($wp->public_query_vars)) {
			$wp->public_query_vars = array_diff($wp->public_query_vars, ['embed']);
		}
		add_filter('embed_oembed_discover', '__return_false');
		remove_action('wp_head', 'wp_oembed_add_discovery_links');
		remove_action('wp_head', 'wp_oembed_add_host_js');
		remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
		remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
		add_filter('tiny_mce_plugins', [$this, 'disableEmbedsTinyMCE']);
		add_filter('rewrite_rules_array', [$this, 'disableEmbedsRewrites']);
	}

	public function disableEmbedsTinyMCE($plugins) {
		return is_array($plugins) ? array_diff($plugins, ['wpembed']) : $plugins;
	}

	public function disableEmbedsRewrites($rules) {
		if (is_array($rules)) {
			foreach ($rules as $rule => $rewrite) {
				if (false !== strpos($rewrite, 'embed=true')) {
					unset($rules[$rule]);
				}
			}
		}
		return $rules;
	}
    
	public function disable_xmlrpc() {
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('pings_open', '__return_false', 9999);
        add_filter('pre_update_option_enable_xmlrpc', '__return_false');
        add_filter('pre_option_enable_xmlrpc', '__return_zero');
        add_filter('wp_headers', [$this, 'remove_xpingback']);
        add_action('init', [$this, 'intercept_xmlrpc_header']);
    }

	public function remove_xpingback($headers) {
		unset($headers['X-Pingback'], $headers['x-pingback']);
		return $headers;
	}

	public function intercept_xmlrpc_header() {
		if (!isset($_SERVER['SCRIPT_FILENAME'])) {
			return;
		}
		if ('xmlrpc.php' !== basename($_SERVER['SCRIPT_FILENAME'])) {
			return;
		}
		$header = 'HTTP/1.1 403 Forbidden';
		header($header);
		echo esc_html($header);
		exit;
	}
    
	public function remove_jquery_migrate() {
        add_filter('wp_default_scripts', [$this, 'jquery_migrate']);
    }

	public function jquery_migrate(&$scripts) {
		if (!is_admin()) {
			$scripts->remove('jquery');
			$scripts->add('jquery', false, ['jquery-core']);
		}
	}
    
	public function remove_wp_version() {
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
    }

	public function remove_wlwmanifest_link() {
        remove_action('wp_head', 'wlwmanifest_link');
	}

	public function remove_rsd_link() {
        remove_action('wp_head', 'rsd_link');
	}

	public function remove_shortlink() {
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('template_redirect', 'wp_shortlink_header', 11, 0);
	}

	public function disable_rss_feeds() {
        add_action('template_redirect', [$this, 'rss_feed'], 1);
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
	}

	public function rss_feed() {
		if (!is_feed() || is_404()) {
			return;
		}
		if (isset($_GET['feed'])) {
			wp_safe_redirect(esc_url_raw(remove_query_arg('feed')), 301);
			exit;
		}
		if (get_query_var('feed') !== 'old') {
			set_query_var('feed', '');
		}
		redirect_canonical();
		wp_die(sprintf(esc_html__("No feed available, please visit the <a href='%s'>homepage</a>!", 'wp-extra'), esc_url(home_url('/'))));
	}
    
	public function disable_self_pingbacks() {
        add_action('pre_ping', [$this, 'self_pingbacks']);
    }

	public function self_pingbacks(&$links) {
		$home = get_option('home');
		foreach ($links as $l => $link) {
			if (strpos($link, $home) === 0) {
				unset($links[$l]);
			}
		}
	}

	public function block_user_enumeration() {
		if (!is_admin()) {
			// Block query string ?author=N for non-logged-in visitors
			if (isset($_REQUEST['author']) && (is_numeric($_REQUEST['author']) || '' !== $_REQUEST['author'])) {
				wp_safe_redirect(home_url(), 301);
				exit;
			}
			// Hide REST API user list from unauthorized visitors
			add_filter('rest_endpoints', [$this, 'filter_rest_user_endpoints']);
		}
	}

	public function filter_rest_user_endpoints($endpoints) {
		if (!is_user_logged_in()) {
			if (isset($endpoints['/wp/v2/users'])) {
				unset($endpoints['/wp/v2/users']);
			}
			if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
				unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
			}
		}
		return $endpoints;
	}

	public function security_headers() {
		add_filter('wp_headers', [$this, 'send_security_headers']);
	}

	public function send_security_headers($headers) {
		if (!is_admin()) {
			$headers['X-Frame-Options'] = 'SAMEORIGIN';
			$headers['X-Content-Type-Options'] = 'nosniff';
			$headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
			$headers['Permissions-Policy'] = 'camera=(), microphone=(), geolocation=()';
		}
		return $headers;
	}

    
	public function themeplugin_edits() {
		add_filter('map_meta_cap', [$this, 'disable_file_editor_caps'], 10, 2);
		if (!defined('DISALLOW_FILE_EDIT')) {
			define('DISALLOW_FILE_EDIT', true);
		}
	}

	public function disable_file_editor_caps($caps, $cap) {
		if (in_array($cap, ['edit_themes', 'edit_plugins', 'edit_files'], true)) {
			$caps[] = 'do_not_allow';
		}
		return $caps;
	}

	public function core_updates() {
		add_filter('auto_update_core', '__return_false');
		add_filter('automatic_updater_disabled', '__return_true');
	}

	public function http_request() {
		add_filter('pre_http_request', [$this, 'pass_reject_request'], 10, 3);
	}

	public function pass_reject_request($preempt, $parsed_args, $url) {
		$value = Helper::get_option('http_request', '');
		$blocked_domains = array_filter(array_map('trim', explode("\n", $value)));
		foreach ($blocked_domains as $domain) {
			if (strpos($url, $domain) !== false) {
				return new \WP_Error('http_request_block', esc_html__('Blocked by WP EXtra', 'wp-extra'));
			}
		}
		return $preempt;
	}

	public function disable_rest_api() {
        add_filter('rest_authentication_errors', [$this, 'restAuthenticationErrors'], 20);
	}
    
	public function restAuthenticationErrors($result) {
        if (!empty($result)) {
            return $result;
        }

        $rest_route = isset($GLOBALS['wp']->query_vars['rest_route']) ? $GLOBALS['wp']->query_vars['rest_route'] : '';
        $exceptions = apply_filters('wpex_rest_api_exceptions', [
            'contact-form-7',
            'wordfence',
            'elementor',
            'woocommerce',
            'fluentform',
            'wpforms'
        ]);

        foreach ($exceptions as $exception) {
            if (!empty($rest_route) && strpos($rest_route, $exception) !== false) {
                return $result;
            }
        }

        $disabled = false;
        $disableOption  = Helper::get_option('disable_rest_api', '');
        $disableOptions = is_array($disableOption) ? $disableOption : [$disableOption];

        if (in_array('all', $disableOptions, true)) {
            $disabled = true;
        } elseif (in_array('non_admins', $disableOptions, true) && !current_user_can('manage_options')) {
            $disabled = true;
        } elseif (in_array('logged_out', $disableOptions, true) && !is_user_logged_in()) {
            $disabled = true;
        }

        if ($disabled) {
            return new \WP_Error('rest_authentication_error', __('Sorry, you do not have permission to make REST API requests.', 'wp-extra'), ['status' => 401]);
        }
        return $result;
    }
    
	public function remove_rest_api_links() {
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
    }

	public function disable_heartbeat() {
        add_action('init', [$this, 'disableHeartbeat'], 1);
    }

	public function disableHeartbeat() {
		if (is_admin()) {
			global $pagenow;
			if (!empty($pagenow)) {
				if ($pagenow === 'admin.php' && !empty($_GET['page'])) {
					$exceptions = [
						'gf_edit_forms',
						'gf_entries',
						'gf_settings'
					];
					if (in_array($_GET['page'], $exceptions, true)) {
						return;
					}
				}
				if ($pagenow === 'site-health.php') {
					return;
				}
			}
		}
        $setting = Helper::get_option('disable_heartbeat');
		if ($setting) {
			if ($setting === 'everywhere') {
				$this->replaceHearbeat();
			} elseif ($setting === 'allow_posts') {
				global $pagenow;
				if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') {
					$this->replaceHearbeat();
				}
			}
		}
	}

	private function replaceHearbeat() {
		wp_deregister_script('heartbeat');
		if (is_admin() && Helper::get_option('disable_heartbeat')) {
			wp_register_script('heartbeat', plugins_url('/assets/js/heartbeat.min.js', WPEX_FILE));
			wp_enqueue_script('heartbeat', plugins_url('/assets/js/heartbeat.min.js', WPEX_FILE));
		}
	}
    
	public function heartbeat_frequency() {
        add_filter('heartbeat_settings', [$this, 'heartbeatFrequency']);
    }

	public function heartbeatFrequency($settings) {
        $freq = Helper::get_option('heartbeat_frequency');
		if ($freq) {
			$settings['interval'] = intval($freq);
		}
		return $settings;
	}
    
    public function remove_blocks() {
        add_filter('allowed_block_types_all', [$this, 'remove_default_blocks']);
        add_filter('allowed_block_types', [$this, 'remove_default_blocks']);
    }
    
    public function remove_default_blocks($allowed_blocks) {
        if (!class_exists('\WP_Block_Type_Registry')) {
            return $allowed_blocks;
        }
        $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $filtered_blocks = [];
        
        foreach ($registered_blocks as $block) {
            if (strpos($block->name, 'core/') === false) {
                if (!class_exists('WooCommerce') || strpos($block->name, 'woocommerce/') === false) {
                    $filtered_blocks[] = $block->name;
                }
            }
        }
        return $filtered_blocks;
    }

}