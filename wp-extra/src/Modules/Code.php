<?php

namespace WPEXtra\Modules;

use WPEXtra\Core\ClassEXtra;

class Code {

    public function __construct() {
        if(wp_extra_get_option('code_header')) {
            add_action('wp_head', [$this, 'insertHeaderCode']);
        }
        if(function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2' , '>=') && wp_extra_get_option('code_body')) {
            add_action('wp_body_open', [$this, 'insertBodyCode']);
        }
        if(wp_extra_get_option('code_footer')) {
            add_action('wp_footer', [$this, 'insertFooterCode']);
        }
        if(wp_extra_get_option('css_all') || wp_extra_get_option('css_tablet') || wp_extra_get_option('css_mobile')) {
            add_action('wp_head', [$this, 'insertCustomCSS'], 100 );
        }
    }

    public function insertHeaderCode() {
        echo wp_unslash(wp_extra_get_option('code_header')); // @codingStandardsIgnoreLine.
    }

    public function insertBodyCode() {
        echo wp_unslash(wp_extra_get_option('code_body')); // @codingStandardsIgnoreLine.
    }

    public function insertFooterCode() {
        echo wp_unslash(wp_extra_get_option('code_footer')); // @codingStandardsIgnoreLine.
    }
    
    public function insertCustomCSS() {
        ob_start();
        ?>
        <style id="extra-css" type="text/css">
        <?php 
        if(wp_extra_get_option('css_all')) {
            echo wp_strip_all_tags(wp_extra_get_option('css_all')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        if(wp_extra_get_option('css_tablet')) {
            echo '@media (max-width: 849px){';
            echo wp_strip_all_tags(wp_extra_get_option('css_tablet')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '}';
        }
        if(wp_extra_get_option('css_mobile')) {
            echo '@media (max-width: 549px){';
            echo wp_strip_all_tags(wp_extra_get_option('css_mobile')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '}';
        } ?>
        </style>
        <?php
        $css = ob_get_clean();
        echo ClassEXtra::minifyCSS($css);
    }
}
