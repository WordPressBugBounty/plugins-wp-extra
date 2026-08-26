<?php
/**
 * TOC Assets Module
 *
 * Handles script & style registration, enqueueing, localization, and dynamic inline CSS generation.
 *
 * @package WPEXtra\Modules\Common\TOC
 */

namespace WPEXtra\Modules\Common\TOC;

use WPEXtra\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	/**
	 * Initialize asset hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ), 10 );
		add_action( 'admin_head', array( __CLASS__, 'add_editor_button' ) );
	}

	/**
	 * Register core scripts and styles.
	 */
	public static function register_assets() {
		$ver        = defined( 'WPEX_VERSION' ) ? WPEX_VERSION : '1.0.0';
		$assets_url = defined( 'WPEX_URL' ) ? WPEX_URL . 'assets/' : plugins_url( 'assets/', dirname( dirname( __DIR__ ) ) );

		// Register Styles
		wp_register_style( 'wptoc', $assets_url . 'css/toc.min.css', array(), $ver );
		wp_register_style( 'wptoc-sticky', $assets_url . 'css/toc-sticky.min.css', array(), $ver );
		wp_register_style( 'wptoc-counter-decimal', $assets_url . 'css/toc-counter-decimal.min.css', array( 'wptoc' ), $ver );
		wp_register_style( 'wptoc-counter-decimal-circle', $assets_url . 'css/toc-counter-decimal-circle.min.css', array( 'wptoc' ), $ver );
		wp_register_style( 'wptoc-counter-disc', $assets_url . 'css/toc-counter-disc.min.css', array( 'wptoc' ), $ver );
		wp_register_style( 'wptoc-counter-checkmark', $assets_url . 'css/toc-counter-checkmark.min.css', array( 'wptoc' ), $ver );
		wp_register_style( 'wptoc-counter-none', $assets_url . 'css/toc-counter-none.min.css', array( 'wptoc' ), $ver );

		// Register Scripts
		wp_register_script( 'wptoc-sticky', $assets_url . 'js/toc-sticky.min.js', array( 'jquery' ), $ver, true );
		wp_register_script( 'wptoc-js', $assets_url . 'js/toc.min.js', array( 'jquery' ), $ver, true );
		wp_register_script( 'wptoc-scroll-scriptjs', $assets_url . 'js/toc-smooth-scroll.min.js', array( 'jquery' ), $ver, true );
		wp_register_script( 'wptoc-anchor-fix', $assets_url . 'js/toc-anchor-fix.min.js', array(), $ver, true );

		self::localize_scripts();
	}

	/**
	 * Localize variables for frontend scripts.
	 */
	public static function localize_scripts() {
		$scroll_offset = (int) Helper::get_option( 'smooth_scroll_offset', 30 );
		if ( wp_is_mobile() ) {
			$mobile_offset = Helper::get_option( 'mobile_smooth_scroll_offset', 0 );
			if ( ! empty( $mobile_offset ) ) {
				$scroll_offset = (int) $mobile_offset;
			}
		}

		wp_localize_script(
			'wptoc-js',
			'wpTOC',
			array(
				'smooth_scroll'              => (bool) Helper::get_option( 'smooth_scroll', true ),
				'visibility_hide_by_default' => (bool) Helper::get_option( 'visibility_hide_by_default', false ),
				'subheading_collapse_mode'   => Helper::get_option( 'subheading_collapse_mode', 'open_first' ),
				'scroll_offset'              => $scroll_offset,
				'fallbackIcon'               => '<span class="wptoc-js-icon-con">' . Renderer::get_toggle_icon() . '</span>',
			)
		);

		wp_localize_script(
			'wptoc-scroll-scriptjs',
			'wptoc_smooth_local',
			array(
				'drop_down'     => Helper::get_option( 'heading-text-direction', 'ltr' ),
				'scroll_offset' => $scroll_offset,
			)
		);

		wp_localize_script(
			'wptoc-sticky',
			'wptoc_sticky_local',
			array(
				'position'     => Helper::get_option( 'sticky-toggle-position', 'bottom-left' ),
				'sticky_width' => Helper::get_option( 'sticky_width', 270 ),
				'sticky_scale' => Helper::get_option( 'sticky_scale', '90' ),
			)
		);
	}

	/**
	 * Enqueue styles & scripts on eligible frontend requests.
	 */
	public static function enqueue_frontend_assets() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		if ( function_exists( 'wptoc_is_plugin_active' ) && wptoc_is_plugin_active( 'elementor/elementor.php' ) ) {
			wp_enqueue_script( 'wptoc-anchor-fix' );
		}

		$enabled_post_types = (array) Helper::get_option( 'enabled_post_types', array( 'post' ) );
		$current_post_type  = get_post_type();

		if ( is_singular() && in_array( $current_post_type, $enabled_post_types, true ) ) {
			self::enqueue_toc_assets();
		}
	}

	/**
	 * Force enqueue TOC assets (e.g. when shortcode is executed).
	 */
	public static function enqueue_toc_assets() {
		wp_enqueue_style( 'wptoc' );
		self::enqueue_counter_style();

		$dynamic_css = self::build_dynamic_css();
		if ( ! empty( $dynamic_css ) ) {
			wp_add_inline_style( 'wptoc', $dynamic_css );
		}

		if ( Helper::get_option( 'smooth_scroll', true ) ) {
			wp_enqueue_script( 'wptoc-scroll-scriptjs' );
		}
		wp_enqueue_script( 'wptoc-js' );

		if ( Helper::get_option( 'sticky-toggle', true ) ) {
			wp_enqueue_style( 'wptoc-sticky' );
			$sticky_css = self::build_sticky_inline_css();
			if ( ! empty( $sticky_css ) ) {
				wp_add_inline_style( 'wptoc-sticky', $sticky_css );
			}
			wp_enqueue_script( 'wptoc-sticky' );
		}
	}

	/**
	 * Enqueue selected counter style stylesheet.
	 *
	 * @param string|null $counter_override
	 */
	public static function enqueue_counter_style( $counter_override = null ) {
		$counter = ! empty( $counter_override ) ? $counter_override : Helper::get_option( 'counter', 'none' );

		switch ( $counter ) {
			case 'decimal-circle':
				wp_enqueue_style( 'wptoc-counter-decimal-circle' );
				break;
			case 'decimal':
			case 'numeric':
				wp_enqueue_style( 'wptoc-counter-decimal' );
				break;
			case 'disc':
				wp_enqueue_style( 'wptoc-counter-disc' );
				break;
			case 'checkmark':
				wp_enqueue_style( 'wptoc-counter-checkmark' );
				break;
			case 'none':
			default:
				wp_enqueue_style( 'wptoc-counter-none' );
				break;
		}
	}

	/**
	 * Build dynamic inline CSS from plugin customizer options.
	 *
	 * @return string
	 */
	public static function build_dynamic_css() {
		$css = '';

		$title_font_size = Helper::get_option( 'title_font_size', 16 );
		if ( ! empty( $title_font_size ) && is_numeric( $title_font_size ) ) {
			$css .= 'div#wptoc-container .wptoc-title, div#wptoc-sticky-popup .wptoc-sticky-title { font-size: ' . esc_attr( intval( $title_font_size ) ) . 'px !important; }';
		}

		$font_size = Helper::get_option( 'font_size', 14 );
		if ( ! empty( $font_size ) && is_numeric( $font_size ) ) {
			$css .= 'div#wptoc-container ul.wptoc-list li a, div#wptoc-sticky-popup ul.wptoc-list li a { font-size: ' . esc_attr( intval( $font_size ) ) . 'px !important; }';
		}

		$child_font_size = Helper::get_option( 'child_font_size', 13 );
		if ( ! empty( $child_font_size ) && is_numeric( $child_font_size ) ) {
			$css .= 'div#wptoc-container ul.wptoc-list ul li a, div#wptoc-sticky-popup ul.wptoc-list ul li a, #wptoc-container li.wptoc-heading-level-3 a, #wptoc-sticky-popup li.wptoc-heading-level-3 a { font-size: ' . esc_attr( intval( $child_font_size ) ) . 'px !important; }';
		}

		$width_custom = Helper::get_option( 'width_custom' );
		if ( ! empty( $width_custom ) && is_numeric( $width_custom ) ) {
			$css .= 'div#wptoc-container { width: ' . esc_attr( intval( $width_custom ) ) . 'px !important; max-width: 100% !important; }';
		}

		$child_indent = Helper::get_option( 'child_indent', 40 );
		$indent_val   = ( '' !== $child_indent && is_numeric( $child_indent ) ) ? intval( $child_indent ) : 40;

		if ( $indent_val <= 0 ) {
			$css .= 'div#wptoc-container ul.wptoc-list ul, div#wptoc-sticky-popup ul.wptoc-list ul { padding-left: 0 !important; margin-left: 0 !important; }';
		} else {
			$css .= 'div#wptoc-container ul.wptoc-list ul > li > .wptoc-item-row > a, div#wptoc-sticky-popup ul.wptoc-list ul > li > .wptoc-item-row > a { padding-left: ' . esc_attr( $indent_val ) . 'px !important; }';
			$css .= 'div#wptoc-container li.wptoc-heading-level-4 > .wptoc-item-row > a, div#wptoc-sticky-popup li.wptoc-heading-level-4 > .wptoc-item-row > a { padding-left: calc(' . esc_attr( $indent_val ) . 'px * 2) !important; }';
			$css .= 'div#wptoc-container li.wptoc-heading-level-5 > .wptoc-item-row > a, div#wptoc-sticky-popup li.wptoc-heading-level-5 > .wptoc-item-row > a { padding-left: calc(' . esc_attr( $indent_val ) . 'px * 3) !important; }';
		}

		$theme_primary = '#2271b1';
		$theme_text    = '#334155';
		if ( function_exists( 'get_theme_mod' ) ) {
			$fp = get_theme_mod( 'color_primary', get_theme_mod( 'color_links' ) );
			if ( ! empty( $fp ) ) {
				$theme_primary = $fp;
			}
			$ft = get_theme_mod( 'type_texts_color' );
			if ( ! empty( $ft ) ) {
				$theme_text = $ft;
			}
		}

		$custom_bg        = Helper::get_option( 'custom_background_colour' );
		$custom_header_bg = Helper::get_option( 'custom_header_background_colour' );
		$custom_border    = Helper::get_option( 'custom_border_colour' );
		$custom_border_w  = Helper::get_option( 'custom_border_width' );
		$custom_border_s  = Helper::get_option( 'custom_border_style', 'solid' );
		$custom_border_r  = Helper::get_option( 'custom_border_radius' );
		$custom_title     = Helper::get_option( 'custom_title_colour' );
		$custom_link      = Helper::get_option( 'custom_link_colour' );
		$custom_hover     = Helper::get_option( 'custom_link_hover_colour' );
		$custom_counter   = Helper::get_option( 'custom_counter_colour' );

		$active_primary = ! empty( $custom_hover ) ? $custom_hover : $theme_primary;
		$active_text    = ! empty( $custom_link ) ? $custom_link : $theme_text;

		$css .= ':root {';
		$css .= '--wptoc-primary: ' . esc_attr( $active_primary ) . ' !important;';
		$css .= '--wptoc-text: ' . esc_attr( $active_text ) . ' !important;';
		if ( ! empty( $custom_counter ) ) {
			$css .= '--wptoc-counter-color: ' . esc_attr( $custom_counter ) . ' !important;';
		}
		if ( ! empty( $custom_title ) ) {
			$css .= '--wptoc-title-color: ' . esc_attr( $custom_title ) . ' !important;';
		}
		if ( ! empty( $custom_border ) ) {
			$css .= '--wptoc-border: ' . esc_attr( $custom_border ) . ' !important;';
		}
		$css .= '}';

		if ( ! empty( $custom_bg ) ) {
			$css .= 'div#wptoc-container { background-color: ' . esc_attr( $custom_bg ) . ' !important; }';
		}
		if ( ! empty( $custom_header_bg ) ) {
			$css .= 'div#wptoc-container .wptoc-title-container { background-color: ' . esc_attr( $custom_header_bg ) . ' !important; }';
		}
		if ( ! empty( $custom_border ) ) {
			$css .= 'div#wptoc-container { border-color: ' . esc_attr( $custom_border ) . ' !important; }';
		}
		if ( ! empty( $custom_border_w ) && is_numeric( $custom_border_w ) ) {
			$css .= 'div#wptoc-container { border-width: ' . esc_attr( intval( $custom_border_w ) ) . 'px !important; }';
		}
		if ( ! empty( $custom_border_s ) && 'solid' !== $custom_border_s ) {
			$css .= 'div#wptoc-container { border-style: ' . esc_attr( $custom_border_s ) . ' !important; }';
		}
		if ( ! empty( $custom_border_r ) && is_numeric( $custom_border_r ) ) {
			$css .= 'div#wptoc-container { border-radius: ' . esc_attr( intval( $custom_border_r ) ) . 'px !important; }';
		}
		if ( ! empty( $custom_title ) ) {
			$css .= 'div#wptoc-container .wptoc-title { color: ' . esc_attr( $custom_title ) . ' !important; }';
		}

		$custom_title_icon = Helper::get_option( 'custom_title_icon_colour' );
		if ( ! empty( $custom_title_icon ) ) {
			$css .= 'div#wptoc-container .wptoc-title-icon svg { stroke: ' . esc_attr( $custom_title_icon ) . ' !important; }';
		}

		$container_padding = Helper::get_option( 'container_padding' );
		if ( ! empty( $container_padding ) ) {
			$pad_val = is_numeric( $container_padding ) ? intval( $container_padding ) . 'px' : esc_attr( trim( $container_padding ) );
			$css .= 'div#wptoc-container { padding: ' . $pad_val . ' !important; }';
		}

		$nav_padding = Helper::get_option( 'nav_padding' );
		if ( ! empty( $nav_padding ) ) {
			$nav_pad_val = is_numeric( $nav_padding ) ? intval( $nav_padding ) . 'px' : esc_attr( trim( $nav_padding ) );
			$css .= 'div#wptoc-container nav { padding: ' . $nav_pad_val . ' !important; }';
		}

		return apply_filters( 'wptoc_inline_css', $css );
	}

	/**
	 * Build Sticky TOC specific inline CSS.
	 *
	 * @return string
	 */
	public static function build_sticky_inline_css() {
		$css = '';

		$sticky_width = Helper::get_option( 'sticky_width' );
		if ( ! empty( $sticky_width ) && is_numeric( $sticky_width ) ) {
			$css .= '#wptoc-sticky-popup { width: ' . esc_attr( intval( $sticky_width ) ) . 'px !important; }';
		}

		$sticky_height = Helper::get_option( 'sticky_height' );
		if ( ! empty( $sticky_height ) && is_numeric( $sticky_height ) ) {
			$s_height = intval( $sticky_height );
			$css .= '#wptoc-sticky-popup { max-height: ' . esc_attr( $s_height ) . 'px !important; }';
			$css .= '#wptoc-sticky-popup nav.wptoc-sidebar { max-height: ' . esc_attr( max( 100, $s_height - 50 ) ) . 'px !important; }';
		}

		$sticky_scale = Helper::get_option( 'sticky_scale', '90' );
		if ( ! empty( $sticky_scale ) && is_numeric( $sticky_scale ) ) {
			$scale_val   = floatval( $sticky_scale );
			$scale_float = ( $scale_val > 5 ) ? round( $scale_val / 100, 2 ) : $scale_val;
			if ( $scale_float > 0 && 1 !== (int) $scale_float ) {
				$css .= '#wptoc-sticky-popup.show { transform: scale(' . esc_attr( $scale_float ) . ') translateY(0) !important; }';
			}
		}

		$sticky_highlight_bg = Helper::get_option( 'sticky_highlight_bg_colour' );
		if ( ! empty( $sticky_highlight_bg ) ) {
			$css .= '#wptoc-sticky-popup ul.wptoc-list li.active > .wptoc-item-row { background-color: ' . esc_attr( $sticky_highlight_bg ) . ' !important; }';
		}

		$sticky_highlight_title = Helper::get_option( 'sticky_highlight_title_colour' );
		if ( ! empty( $sticky_highlight_title ) ) {
			$css .= '#wptoc-sticky-popup ul.wptoc-list li.active > .wptoc-item-row > a { color: ' . esc_attr( $sticky_highlight_title ) . ' !important; }';
		}

		$sticky_mobile_width = Helper::get_option( 'sticky_mobile_width' );
		$sticky_mobile_height = Helper::get_option( 'sticky_mobile_height' );
		if ( ! empty( $sticky_mobile_width ) || ! empty( $sticky_mobile_height ) ) {
			$css .= '@media screen and (max-width: 768px) {';
			if ( ! empty( $sticky_mobile_width ) ) {
				$m_w = is_numeric( $sticky_mobile_width ) ? intval( $sticky_mobile_width ) . 'px' : esc_attr( $sticky_mobile_width );
				$css .= '#wptoc-sticky-popup { width: ' . $m_w . ' !important; max-width: calc(100vw - 32px) !important; }';
			}
			if ( ! empty( $sticky_mobile_height ) ) {
				$m_h = is_numeric( $sticky_mobile_height ) ? intval( $sticky_mobile_height ) . 'px' : esc_attr( $sticky_mobile_height );
				$css .= '#wptoc-sticky-popup { max-height: ' . $m_h . ' !important; }';
			}
			$css .= '}';
		}

		return apply_filters( 'wptoc_sticky_inline_css', $css );
	}

	/**
	 * Register TinyMCE Classic Editor button.
	 */
	public static function add_editor_button() {
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( 'true' === get_user_option( 'rich_editing' ) ) {
			add_filter( 'mce_external_plugins', array( __CLASS__, 'toc_add_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( __CLASS__, 'toc_register_mce_button' ) );
		}
	}

	public static function toc_register_mce_button( $buttons ) {
		array_push( $buttons, 'wptoc' );
		return $buttons;
	}

	public static function toc_add_tinymce_plugin( $plugin_array ) {
		$assets_url = defined( 'WPEX_URL' ) ? WPEX_URL . 'assets/' : plugins_url( 'assets/', dirname( dirname( __DIR__ ) ) );
		$plugin_array['wptoc'] = $assets_url . 'tinymce/toc/plugin.min.js';
		return $plugin_array;
	}
}
