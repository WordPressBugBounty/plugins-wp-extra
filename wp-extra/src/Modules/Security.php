<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
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
		'remove_wlwmanifest_link',
		'remove_rsd_link',
		'remove_shortlink',
		'disable_rss_feeds',
		'remove_feed_links',
		'disable_self_pingbacks',
		'disable_rest_api',
		'remove_rest_api_links',
		'disable_heartbeat',
		'heartbeat_frequency',
		'remove_blocks',
	];
    
    public function disable_embeds() {
        add_action('init', [$this, 'disable_embed'], 9999);
    }

	public function disable_embed() {
		global $wp;
		$wp->public_query_vars = array_diff($wp->public_query_vars, array('embed'));
		add_filter('embed_oembed_discover', '__return_false');
		remove_action('wp_head', 'wp_oembed_add_discovery_links');
		remove_action('wp_head', 'wp_oembed_add_host_js');
		remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
		remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
		add_filter('tiny_mce_plugins', [$this, 'disableEmbedsTinyMCE']);
		add_filter('rewrite_rules_array', [$this, 'disableEmbedsRewrites']);
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
    
	public function remove_jquery_migrate() {
        add_filter('wp_default_scripts', [$this, 'jquery_migrate']);
    }

	public function jquery_migrate(&$scripts) {
		if(!is_admin()) {
			$scripts->remove('jquery');
			$scripts->add('jquery', false, array( 'jquery-core' ), '1.12.4');
		}
	}
    
	public function remove_wp_version() {
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', 'hide_version');
    }

	public function hide_version() {
		return '';
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
	}

	public function rss_feed() {
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
    
	public function remove_feed_links() {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }
    
	public function disable_self_pingbacks() {
        add_action('pre_ping', [$this, 'self_pingbacks']);
    }

	public function self_pingbacks(&$links) {
		$home = get_option('home');
		foreach($links as $l => $link) {
			if(strpos($link, $home) === 0) {
				unset($links[$l]);
			}
		}
	}
    
	public function disable_rest_api() {
        add_filter('rest_authentication_errors', [$this, 'restAuthenticationErrors'], 20);
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
                if (is_array($rest_route) && in_array($exception, $rest_route)) {
                    return;
                }
            }
            $disableOptions = Settings::get_option('disable_rest_api');
            if (!is_array($disableOptions)) {
                $disableOptions = array();
            }
            if (in_array('all', $disableOptions)) {
                $disabled = true;
            } elseif (in_array('non_admins', $disableOptions) && !current_user_can('manage_options')) {
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
    
	public function remove_rest_api_links() {
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11, 0);
    }

	public function disable_heartbeat() {
        add_action('init', [$this, 'disableHeartbeat'], 1);
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
		if(Settings::get_option('disable_heartbeat')) {
			if(Settings::get_option('disable_heartbeat') == 'everywhere') {
				$this->replaceHearbeat();
			}
			elseif(Settings::get_option('disable_heartbeat') == 'allow_posts') {
				global $pagenow;
				if($pagenow != 'post.php' && $pagenow != 'post-new.php') {
					$this->replaceHearbeat();
				}
			}
		}
	}

	private function replaceHearbeat() {
		wp_deregister_script('heartbeat');
		if(is_admin() && Settings::get_option('disable_heartbeat')) {
			wp_register_script('hearbeat', plugins_url('/assets/js/heartbeat.js', WPEX_FILE ));
			wp_enqueue_script('hearbeat', plugins_url('/assets/js/heartbeat.js', WPEX_FILE ));
		}
	}
    
	public function heartbeat_frequency() {
        add_filter('heartbeat_settings', [$this, 'heartbeatFrequency']);
    }

	public function heartbeatFrequency($settings) {
		if(Settings::get_option('heartbeat_frequency')) {
			$settings['interval'] = Settings::get_option('heartbeat_frequency');
		}
		return $settings;
	}
    
    public function remove_blocks() {
        add_filter('allowed_block_types', [$this, 'remove_default_blocks']);
    }
    
    public function remove_default_blocks($allowed_blocks) {
        $registered_blocks = \WP_Block_Type_Registry::get_instance()->get_all_registered();
        $filtered_blocks = array();
        
        foreach ($registered_blocks as $block) {
            if (strpos($block->name, 'core/') === false) {
                if (!class_exists('WooCommerce') || strpos($block->name, 'woocommerce/') === false) {
                    array_push($filtered_blocks, $block->name);
                }
            }
        }
        return $filtered_blocks;
    }

}