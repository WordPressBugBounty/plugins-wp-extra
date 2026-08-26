<?php
/**
 * TOC Compatibility Module
 *
 * Handles compatibility with third-party builders, themes, and plugins (Divi, Elementor, Flatsome, WooCommerce, Rank Math).
 *
 * @package WPEXtra\Modules\Common\TOC
 */

namespace WPEXtra\Modules\Common\TOC;

use WPEXtra\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Compatibility {

	/**
	 * Initialize compatibility hooks.
	 */
	public static function init() {
		// Category & Archive descriptions
		add_filter( 'term_description', array( __CLASS__, 'filter_term_description' ), 99, 2 );
		add_filter( 'woocommerce_taxonomy_archive_description_raw', array( __CLASS__, 'filter_woo_category_description' ), 99, 2 );
		add_filter( 'wp_kses_allowed_html', array( __CLASS__, 'allow_svg_in_category_description' ), 10, 2 );

		// Rank Math SEO
		add_filter( 'rank_math/researches/toc_plugins', array( __CLASS__, 'rank_math_compat' ) );

		// Elementor
		add_action( 'elementor/init', array( __CLASS__, 'init_elementor' ) );

		// Divi
		add_action( 'et_pb_admin_excluded_shortcodes', array( __CLASS__, 'divi_excluded_shortcodes' ) );

		// Gutenberg Reusable Blocks
		add_filter( 'wp_toc_modify_process_page_content', array( __CLASS__, 'gutenberg_reusable_blocks' ), 10, 1 );
	}

	/**
	 * Filter term description for category/tag TOC.
	 *
	 * @param string $description
	 * @param int    $term_id
	 * @return string
	 */
	public static function filter_term_description( $description, $term_id ) {
		if ( ! is_admin() && ! empty( $description ) && Helper::get_option( 'include_category', false ) ) {
			if ( is_category() || is_tag() || is_tax() ) {
				$module = \WPEXtra\Modules\Common\TOC::instance();
				if ( $module ) {
					return $module->process_content( $description );
				}
			}
		}
		return $description;
	}

	/**
	 * Filter WooCommerce product category description.
	 *
	 * @param string $description
	 * @param object $term
	 * @return string
	 */
	public static function filter_woo_category_description( $description, $term ) {
		if ( ! is_admin() && ! empty( $description ) && Helper::get_option( 'include_product_category', false ) ) {
			$module = \WPEXtra\Modules\Common\TOC::instance();
			if ( $module ) {
				return $module->process_content( $description );
			}
		}
		return $description;
	}

	/**
	 * WooCommerce: Allow SVG tags in category descriptions.
	 *
	 * @param array  $allowed_tags
	 * @param string $context
	 * @return array
	 */
	public static function allow_svg_in_category_description( $allowed_tags, $context = '' ) {
		if ( function_exists( 'is_product_category' ) && is_product_category() && Helper::get_option( 'include_product_category', false ) ) {
			$allowed_tags['svg'] = array(
				'width'   => true,
				'height'  => true,
				'viewbox' => true,
				'xmlns'   => true,
				'fill'    => true,
				'stroke'  => true,
				'style'   => true,
				'class'   => true,
			);
			$allowed_tags['path'] = array(
				'd'      => true,
				'fill'   => true,
				'stroke' => true,
			);
			$allowed_tags['span']['style'] = true;
		}
		return $allowed_tags;
	}

	/**
	 * Rank Math SEO TOC plugin declaration.
	 *
	 * @param array $toc_plugins
	 * @return array
	 */
	public static function rank_math_compat( $toc_plugins ) {
		$plugin_basename = defined( 'WPEX_BASENAME' ) ? WPEX_BASENAME : 'wp-extra-pro/wp-extra.php';
		$toc_plugins[ $plugin_basename ] = 'WP Extra TOC';
		return $toc_plugins;
	}

	/**
	 * Elementor compatibility.
	 */
	public static function init_elementor() {
		if ( class_exists( '\Elementor\Plugin' ) ) {
			add_action( 'elementor/frontend/after_enqueue_scripts', function() {
				if ( Helper::get_option( 'smooth_scroll', true ) ) {
					wp_enqueue_script( 'wptoc-scroll-scriptjs' );
				}
			} );
		}
	}

	/**
	 * Divi builder excluded shortcodes.
	 *
	 * @param array $shortcodes
	 * @return array
	 */
	public static function divi_excluded_shortcodes( $shortcodes ) {
		$shortcodes[] = 'toc';
		$shortcodes[] = 'wp-toc';
		return $shortcodes;
	}

	/**
	 * Gutenberg Reusable Blocks compatibility.
	 *
	 * @param string $content
	 * @return string
	 */
	public static function gutenberg_reusable_blocks( $content ) {
		if ( function_exists( 'do_blocks' ) ) {
			if ( has_block( 'easytoc/toc' ) ) {
				$content = str_replace( '<!-- wp:easytoc/toc /-->', 'wptoctempblock', $content );
				$content = do_blocks( $content );
				$content = str_replace( 'wptoctempblock', '<!-- wp:easytoc/toc /-->', $content );
			} else {
				$content = do_blocks( $content );
			}
		}
		return $content;
	}
}
