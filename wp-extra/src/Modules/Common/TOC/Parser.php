<?php
/**
 * TOC Parser
 *
 * Lightweight, high-performance heading extractor and anchor generator using WordPress core functions.
 *
 * @package WPEXtra\Modules\Common\TOC
 */

namespace WPEXtra\Modules\Common\TOC;

use WPEXtra\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Parser {

	/**
	 * Unique anchor IDs tracked per request to avoid collisions.
	 *
	 * @var array<string, int>
	 */
	private $anchor_counts = array();

	/**
	 * Parse content and extract TOC headings.
	 *
	 * @param string $content Raw HTML content.
	 * @param array  $custom_options Optional runtime option overrides.
	 * @return array{content: string, headings: array, count: int}
	 */
	public function parse( $content, $custom_options = array() ) {
		$this->anchor_counts = array();

		if ( empty( $content ) || ! is_string( $content ) ) {
			return array(
				'content'  => $content,
				'headings' => array(),
				'count'    => 0,
			);
		}

		$heading_levels = isset( $custom_options['heading_levels'] )
			? (array) $custom_options['heading_levels']
			: (array) Helper::get_option( 'heading_levels', array( '1', '2', '3', '4', '5', '6' ) );

		if ( empty( $heading_levels ) ) {
			return array(
				'content'  => $content,
				'headings' => array(),
				'count'    => 0,
			);
		}

		$levels_pattern = implode( '', array_map( 'intval', $heading_levels ) );
		$pattern        = '/<h([' . $levels_pattern . '])([^>]*)>(.*?)<\/h\1>/is';

		if ( ! preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
			return array(
				'content'  => $content,
				'headings' => array(),
				'count'    => 0,
			);
		}

		$min_start = isset( $custom_options['start'] )
			? (int) $custom_options['start']
			: (int) Helper::get_option( 'start', 4 );

		$exclude_pattern = isset( $custom_options['exclude'] )
			? (string) $custom_options['exclude']
			: (string) Helper::get_option( 'exclude', '' );

		$exclude_classes = isset( $custom_options['exclude_by_class'] )
			? (string) $custom_options['exclude_by_class']
			: (string) Helper::get_option( 'exclude_by_class', '' );

		$exclude_class_list = array_filter( array_map( 'trim', explode( ',', $exclude_classes ) ) );
		$exclude_patterns   = array_filter( array_map( 'trim', explode( '|', $exclude_pattern ) ) );

		$headings_flat = array();
		$replacements  = array();

		foreach ( $matches as $index => $match ) {
			$full_tag   = $match[0];
			$level      = (int) $match[1];
			$attributes = $match[2];
			$inner_html = $match[3];

			$clean_text = trim( wp_strip_all_tags( $inner_html ) );
			if ( '' === $clean_text ) {
				continue;
			}

			// Check exclude by class
			if ( ! empty( $exclude_class_list ) && preg_match( '/class=["\']([^"\']+)["\']/i', $attributes, $class_match ) ) {
				$tag_classes = preg_split( '/\s+/', $class_match[1] );
				if ( array_intersect( $tag_classes, $exclude_class_list ) ) {
					continue;
				}
			}

			// Check exclude by wildcard pattern
			if ( ! empty( $exclude_patterns ) && $this->is_excluded_by_pattern( $clean_text, $exclude_patterns ) ) {
				continue;
			}

			// Extract or generate anchor ID
			$anchor_id = '';
			if ( preg_match( '/id=["\']([^"\']+)["\']/i', $attributes, $id_match ) ) {
				$anchor_id = sanitize_title( $id_match[1] );
			}

			if ( empty( $anchor_id ) ) {
				$anchor_id = $this->generate_unique_anchor( $clean_text );
				$new_attrs = ' id="' . esc_attr( $anchor_id ) . '"' . $attributes;
				$new_tag   = '<h' . $level . $new_attrs . '>' . $inner_html . '</h' . $level . '>';
				$replacements[ $full_tag ] = $new_tag;
			}

			$headings_flat[] = array(
				'id'         => $anchor_id,
				'title'      => $clean_text,
				'inner_html' => $inner_html,
				'level'      => $level,
			);
		}

		$total_count = count( $headings_flat );
		if ( $total_count < $min_start ) {
			return array(
				'content'  => $content,
				'headings' => array(),
				'count'    => $total_count,
			);
		}

		// Apply ID replacements to content
		if ( ! empty( $replacements ) ) {
			$content = str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
		}

		$tree = $this->build_hierarchy_tree( $headings_flat );

		return array(
			'content'  => $content,
			'headings' => $tree,
			'count'    => $total_count,
		);
	}

	/**
	 * Generate a unique anchor ID from heading text.
	 *
	 * @param string $text
	 * @return string
	 */
	private function generate_unique_anchor( $text ) {
		$slug = sanitize_title( $text );
		if ( empty( $slug ) ) {
			$slug = 'heading';
		}

		if ( ! isset( $this->anchor_counts[ $slug ] ) ) {
			$this->anchor_counts[ $slug ] = 1;
			return $slug;
		}

		$this->anchor_counts[ $slug ]++;
		return $slug . '-' . $this->anchor_counts[ $slug ];
	}

	/**
	 * Check if heading matches any exclusion wildcard pattern.
	 *
	 * @param string $text
	 * @param array  $patterns
	 * @return bool
	 */
	private function is_excluded_by_pattern( $text, array $patterns ) {
		foreach ( $patterns as $pattern ) {
			$regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/iu';
			if ( preg_match( $regex, $text ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Convert flat heading list into a nested hierarchical tree.
	 *
	 * @param array $headings
	 * @return array
	 */
	private function build_hierarchy_tree( array $headings ) {
		$tree  = array();
		$stack = array();

		foreach ( $headings as &$item ) {
			$item['children'] = array();

			while ( ! empty( $stack ) && end( $stack )['level'] >= $item['level'] ) {
				array_pop( $stack );
			}

			if ( empty( $stack ) ) {
				$tree[] = &$item;
			} else {
				$stack[ count( $stack ) - 1 ]['children'][] = &$item;
			}
			$stack[] = &$item;
		}
		unset( $item );

		return $tree;
	}
}
