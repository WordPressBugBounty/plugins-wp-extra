<?php
namespace WPEXtra\Modules\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class Code extends Base {
    
    private static $css_hooked = false;

    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'code_header',
		'code_body',
		'code_footer',
		'css_all',
		'css_tablet',
		'css_mobile',
	];
    
    public function code_header() {
        add_action('wp_head', [$this, 'insertHeaderCode']);
    }

    public function insertHeaderCode() {
        if (!is_admin()) {
            $code = Helper::get_option('code_header');
            if (!empty($code)) {
                echo wp_unslash($code) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
    }
    
    public function code_body() {
        if (function_exists('wp_body_open')) {
            add_action('wp_body_open', [$this, 'insertBodyCode']);
        }
    }

    public function insertBodyCode() {
        if (!is_admin()) {
            $code = Helper::get_option('code_body');
            if (!empty($code)) {
                echo wp_unslash($code) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
    }
    
    public function code_footer() {
        add_action('wp_footer', [$this, 'insertFooterCode']);
    }

    public function insertFooterCode() {
        if (!is_admin()) {
            $code = Helper::get_option('code_footer');
            if (!empty($code)) {
                echo wp_unslash($code) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
        }
    }
    
    public function css_all() {
        $this->hook_custom_css();
    }
    
    public function css_tablet() {
        $this->hook_custom_css();
    }
    
    public function css_mobile() {
        $this->hook_custom_css();
    }

    private function hook_custom_css() {
        if (!self::$css_hooked) {
            self::$css_hooked = true;
            add_action('wp_head', [$this, 'renderCustomCSS'], 100);
        }
    }

    public function renderCustomCSS() {
        if (is_admin()) {
            return;
        }

        $all_css    = Helper::get_option('css_all');
        $tablet_css = Helper::get_option('css_tablet');
        $mobile_css = Helper::get_option('css_mobile');

        $output = '';

        if (!empty($all_css)) {
            $output .= wp_strip_all_tags($all_css) . "\n";
        }
        if (!empty($tablet_css)) {
            $output .= '@media (max-width: 1024px) {' . wp_strip_all_tags($tablet_css) . "}\n";
        }
        if (!empty($mobile_css)) {
            $output .= '@media (max-width: 767px) {' . wp_strip_all_tags($mobile_css) . "}\n";
        }

        $output = trim($output);
        if (!empty($output)) {
            echo '<style id="wpex-custom-css" type="text/css">' . Helper::minifyCSS($output) . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}
