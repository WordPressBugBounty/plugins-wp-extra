<?php
/**
 * TOC Schema Module
 *
 * Generates JSON-LD SiteNavigationElement structured data and integrates with Yoast SEO schema graph.
 *
 * @package WPEXtra\Modules\Common\TOC
 */

namespace WPEXtra\Modules\Common\TOC;

use WPEXtra\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Schema {

	/**
	 * Extracted heading nodes for current page request.
	 *
	 * @var array
	 */
	private static $current_headings = array();

	/**
	 * Initialize schema hooks.
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'output_json_ld' ), 20 );
		add_filter( 'wpseo_schema_graph', array( __CLASS__, 'integrate_yoast_schema' ), 10, 2 );
	}

	/**
	 * Store current headings for schema generation.
	 *
	 * @param array $headings
	 */
	public static function set_headings( array $headings ) {
		self::$current_headings = $headings;
	}

	/**
	 * Flatten hierarchical tree into 1D list for schema items.
	 *
	 * @param array $tree
	 * @return array
	 */
	private static function flatten_tree( array $tree ) {
		$flat = array();
		foreach ( $tree as $item ) {
			$flat[] = array(
				'id'    => $item['id'],
				'title' => $item['title'],
			);
			if ( ! empty( $item['children'] ) ) {
				$flat = array_merge( $flat, self::flatten_tree( $item['children'] ) );
			}
		}
		return $flat;
	}

	/**
	 * Output JSON-LD SiteNavigationElement in wp_footer if Yoast is not active.
	 */
	public static function output_json_ld() {
		if ( empty( self::$current_headings ) || defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		$permalink = get_permalink();
		if ( ! $permalink ) {
			return;
		}

		$items    = self::flatten_tree( self::$current_headings );
		$elements = array();

		foreach ( $items as $index => $item ) {
			$elements[] = array(
				'@context'  => 'https://schema.org',
				'@type'     => 'SiteNavigationElement',
				'id'        => $permalink . '#' . $item['id'],
				'name'      => $item['title'],
				'url'       => $permalink . '#' . $item['id'],
			);
		}

		if ( ! empty( $elements ) ) {
			echo "\n<!-- WP Extra Table of Contents Schema -->\n";
			echo '<script type="application/ld+json">' . wp_json_encode( $elements, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
		}
	}

	/**
	 * Integrate TOC items with Yoast SEO schema graph.
	 *
	 * @param array  $graph
	 * @param object $context
	 * @return array
	 */
	public static function integrate_yoast_schema( $graph, $context ) {
		if ( empty( self::$current_headings ) ) {
			return $graph;
		}

		$permalink = get_permalink();
		if ( ! $permalink ) {
			return $graph;
		}

		$items = self::flatten_tree( self::$current_headings );

		foreach ( $items as $item ) {
			$graph[] = array(
				'@context' => 'https://schema.org',
				'@type'    => 'SiteNavigationElement',
				'@id'      => $permalink . '#' . $item['id'],
				'name'     => $item['title'],
				'url'      => $permalink . '#' . $item['id'],
			);
		}

		return $graph;
	}
}
