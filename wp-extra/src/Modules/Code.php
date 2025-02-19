<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class Code extends Base {
    
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
        echo wp_unslash(Settings::get_option('code_header')); // @codingStandardsIgnoreLine.
    }
    
    public function code_body() {
        if(function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2' , '>=')) {
            add_action('wp_body_open', [$this, 'insertBodyCode']);
        }
    }

    public function insertBodyCode() {
        echo wp_unslash(Settings::get_option('code_body')); // @codingStandardsIgnoreLine.
    }
    
    public function code_footer() {
        add_action('wp_footer', [$this, 'insertFooterCode']);
    }

    public function insertFooterCode() {
        echo wp_unslash(Settings::get_option('code_footer')); // @codingStandardsIgnoreLine.
    }
    
    public function css_all() {
        add_action('wp_head', [$this, 'insertAllCSS'], 100 );
    }
    
    public function css_tablet() {
        add_action('wp_head', [$this, 'insertTabletCSS'], 100 );
    }
    
    public function css_mobile() {
        add_action('wp_head', [$this, 'insertMobileCSS'], 100 );
    }
    
    public function insertAllCSS() {
        ob_start();
        ?>
        <style id="all-css" type="text/css">
        <?php 
        if(Settings::get_option('css_all')) {
            echo wp_strip_all_tags(Settings::get_option('css_all')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        } ?>
        </style>
        <?php
        $css = ob_get_clean();
        echo Settings::minifyCSS($css);
    }
    
    public function insertTabletCSS() {
        ob_start();
        ?>
        <style id="tablet-css" type="text/css">
        <?php 
        if(Settings::get_option('css_tablet')) {
            echo '@media (max-width: 849px){';
            echo wp_strip_all_tags(Settings::get_option('css_tablet')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '}';
        } ?>
        </style>
        <?php
        $css = ob_get_clean();
        echo Settings::minifyCSS($css);
    }
    
    public function insertMobileCSS() {
        ob_start();
        ?>
        <style id="mobile-css" type="text/css">
        <?php 
        if(Settings::get_option('css_mobile')) {
            echo '@media (max-width: 549px){';
            echo wp_strip_all_tags(Settings::get_option('css_mobile')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '}';
        } ?>
        </style>
        <?php
        $css = ob_get_clean();
        echo Settings::minifyCSS($css);
    }
}
