<?php
/**
 * WP Extra Table of Contents (TOC) Master Module
 *
 * Coordinates parsing, rendering, asset enqueueing, placement, shortcodes, and integrations.
 *
 * @package WPEXtra\Modules\Common
 */

namespace WPEXtra\Modules\Common;

use WPEXtra\Base;
use WPEXtra\Helper;
use WPEXtra\Modules\Common\TOC\Parser;
use WPEXtra\Modules\Common\TOC\Renderer;
use WPEXtra\Modules\Common\TOC\Assets;
use WPEXtra\Modules\Common\TOC\Schema;
use WPEXtra\Modules\Common\TOC\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TOC extends Base {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Headings tree cached for current request sticky drawer & schema.
	 *
	 * @var array
	 */
	private $current_headings = array();

	/**
	 * Flag to prevent infinite loop or double execution in the_content.
	 *
	 * @var bool
	 */
	private $is_processing = false;

	/**
	 * Active parser instance.
	 *
	 * @var Parser
	 */
	private $parser;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		self::$instance = $this;
		$this->parser   = new Parser();
		$this->init();
	}

	/**
	 * Initialize module.
	 */
	private function init() {
		// Init submodules
		Assets::init();
		Schema::init();
		Compatibility::init();

		// Content filters
		add_filter( 'the_content', array( $this, 'filter_content' ), 100 );
		add_filter( 'ux_post_content', array( $this, 'filter_content' ), 100 );

		// Shortcodes
		add_shortcode( 'toc', array( $this, 'shortcode_toc' ) );
		add_shortcode( 'wp-toc', array( $this, 'shortcode_toc' ) );

		// Footer sticky drawer
		add_action( 'wp_footer', array( $this, 'render_sticky_footer' ), 15 );

		// Backwards compatibility aliases
		$this->register_backward_aliases();
	}

	/**
	 * Main the_content filter handler.
	 *
	 * @param string $content
	 * @return string
	 */
	public function filter_content( $content ) {
		if ( empty( $content ) || ! is_string( $content ) || $this->is_processing ) {
			return $content;
		}

		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return $content;
		}

		if ( ! is_singular() && ! is_page() && ! is_single() && ! is_front_page() ) {
			return $content;
		}

		if ( ! $this->is_eligible_post() ) {
			return $content;
		}

		return $this->process_content( $content );
	}

	/**
	 * Check if current post is eligible for auto-insert.
	 *
	 * @return bool
	 */
	private function is_eligible_post() {
		$post = get_post();
		if ( ! $post ) {
			return false;
		}

		// Check homepage inclusion
		if ( is_front_page() && ! Helper::get_option( 'include_homepage', false ) ) {
			return false;
		}

		// Check post type auto insertion
		$auto_insert_types = (array) Helper::get_option( 'auto_insert_post_types', array( 'post' ) );
		if ( ! in_array( $post->post_type, $auto_insert_types, true ) ) {
			return false;
		}

		// Check excluded post IDs
		$exclude_posts = Helper::get_option( 'exclude_post_ids', '' );
		if ( ! empty( $exclude_posts ) ) {
			$exclude_ids = array_map( 'intval', array_map( 'trim', explode( ',', $exclude_posts ) ) );
			if ( in_array( (int) $post->ID, $exclude_ids, true ) ) {
				return false;
			}
		}

		// Check excluded category IDs
		$exclude_cats = Helper::get_option( 'exclude_category_ids', '' );
		if ( ! empty( $exclude_cats ) ) {
			$exclude_cat_ids = array_map( 'intval', array_map( 'trim', explode( ',', $exclude_cats ) ) );
			$post_cats       = wp_get_post_categories( $post->ID );
			if ( array_intersect( $post_cats, $exclude_cat_ids ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Process content: extract headings, inject IDs, and place TOC box.
	 *
	 * @param string $content
	 * @param array  $custom_options
	 * @return string
	 */
	public function process_content( $content, $custom_options = array() ) {
		$this->is_processing = true;

		$parsed = $this->parser->parse( $content, $custom_options );
		$content = $parsed['content'];
		$headings = $parsed['headings'];

		if ( ! empty( $headings ) ) {
			$this->current_headings = $headings;
			Schema::set_headings( $headings );

			// Don't auto insert if content already has shortcode
			$has_shortcode = has_shortcode( $content, 'toc' ) || has_shortcode( $content, 'wp-toc' );

			if ( ! $has_shortcode ) {
				$toc_html = Renderer::render( $headings, $custom_options );
				$content  = $this->insert_toc( $content, $toc_html );
			}
		}

		$this->is_processing = false;
		return $content;
	}

	/**
	 * Insert TOC HTML into content based on position option.
	 *
	 * @param string $content
	 * @param string $toc_html
	 * @return string
	 */
	private function insert_toc( $content, $toc_html ) {
		if ( empty( $toc_html ) ) {
			return $content;
		}

		$position = Helper::get_option( 'position', 'before' );

		switch ( $position ) {
			case 'top':
				return $toc_html . $content;

			case 'bottom':
				return $content . $toc_html;

			case 'after':
				// After first heading
				if ( preg_match( '/(<h[1-6][^>]*>.*?<\/h[1-6]>)/is', $content, $match, PREG_OFFSET_CAPTURE ) ) {
					$pos = $match[0][1] + strlen( $match[0][0] );
					return substr( $content, 0, $pos ) . $toc_html . substr( $content, $pos );
				}
				return $toc_html . $content;

			case 'afterpara':
				// After first paragraph
				if ( preg_match( '/(<\/p>)/i', $content, $match, PREG_OFFSET_CAPTURE ) ) {
					$pos = $match[0][1] + strlen( $match[0][0] );
					return substr( $content, 0, $pos ) . $toc_html . substr( $content, $pos );
				}
				return $toc_html . $content;

			case 'afterimg':
				// After first image or figure
				if ( preg_match( '/(<\/figure>|<img[^>]*>)/i', $content, $match, PREG_OFFSET_CAPTURE ) ) {
					$pos = $match[0][1] + strlen( $match[0][0] );
					return substr( $content, 0, $pos ) . $toc_html . substr( $content, $pos );
				}
				return $toc_html . $content;

			case 'before':
			default:
				// Before first heading
				if ( preg_match( '/(<h[1-6][^>]*>)/is', $content, $match, PREG_OFFSET_CAPTURE ) ) {
					$pos = $match[0][1];
					return substr( $content, 0, $pos ) . $toc_html . substr( $content, $pos );
				}
				return $toc_html . $content;
		}
	}

	/**
	 * Handle [toc] shortcode execution.
	 *
	 * @param array  $atts
	 * @param string $content
	 * @return string
	 */
	public function shortcode_toc( $atts = array(), $content = '' ) {
		$atts = shortcode_atts(
			array(
				'heading_levels'            => '',
				'counter'                   => '',
				'subheading_collapse_mode'  => '',
				'heading_text'              => '',
				'start'                     => '',
				'exclude'                   => '',
				'exclude_by_class'          => '',
			),
			$atts,
			'toc'
		);

		$custom_options = array();
		if ( ! empty( $atts['heading_levels'] ) ) {
			$custom_options['heading_levels'] = explode( ',', $atts['heading_levels'] );
		}
		if ( ! empty( $atts['counter'] ) ) {
			$custom_options['counter'] = $atts['counter'];
		}
		if ( ! empty( $atts['subheading_collapse_mode'] ) ) {
			$custom_options['subheading_collapse_mode'] = $atts['subheading_collapse_mode'];
		}
		if ( ! empty( $atts['heading_text'] ) ) {
			$custom_options['heading_text'] = $atts['heading_text'];
		}
		if ( ! empty( $atts['start'] ) ) {
			$custom_options['start'] = intval( $atts['start'] );
		}
		if ( ! empty( $atts['exclude'] ) ) {
			$custom_options['exclude'] = $atts['exclude'];
		}
		if ( ! empty( $atts['exclude_by_class'] ) ) {
			$custom_options['exclude_by_class'] = $atts['exclude_by_class'];
		}

		Assets::enqueue_toc_assets();

		if ( ! empty( $this->current_headings ) ) {
			return Renderer::render( $this->current_headings, $custom_options );
		}

		global $post;
		if ( $post && ! empty( $post->post_content ) ) {
			$parsed = $this->parser->parse( $post->post_content, $custom_options );
			if ( ! empty( $parsed['headings'] ) ) {
				$this->current_headings = $parsed['headings'];
				Schema::set_headings( $parsed['headings'] );
				return Renderer::render( $parsed['headings'], $custom_options );
			}
		}

		return '';
	}

	/**
	 * Render sticky drawer & FAB floating button in wp_footer.
	 */
	public function render_sticky_footer() {
		if ( empty( $this->current_headings ) || is_admin() || is_feed() ) {
			return;
		}

		echo Renderer::render_sticky_footer( $this->current_headings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Register backward compatibility aliases for third-party integrations.
	 */
	private function register_backward_aliases() {
		if ( ! class_exists( 'wpTOC', false ) ) {
			class_alias( __CLASS__, 'wpTOC' );
		}
		if ( ! class_exists( 'wpTOC_Renderer', false ) ) {
			class_alias( 'WPEXtra\Modules\Common\TOC\Renderer', 'wpTOC_Renderer' );
		}
		if ( ! class_exists( 'wpTOC_Assets', false ) ) {
			class_alias( 'WPEXtra\Modules\Common\TOC\Assets', 'wpTOC_Assets' );
		}
		if ( ! class_exists( 'wpTOC_Schema', false ) ) {
			class_alias( 'WPEXtra\Modules\Common\TOC\Schema', 'wpTOC_Schema' );
		}
		if ( ! class_exists( 'wpTOC_Compatibility', false ) ) {
			class_alias( 'WPEXtra\Modules\Common\TOC\Compatibility', 'wpTOC_Compatibility' );
		}
	}
}
