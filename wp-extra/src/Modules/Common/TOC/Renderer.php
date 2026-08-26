<?php
/**
 * TOC Renderer
 *
 * Modern, clean HTML and SVG generator for TOC layouts, counters, toggle icons, and sticky drawer.
 *
 * @package WPEXtra\Modules\Common\TOC
 */

namespace WPEXtra\Modules\Common\TOC;

use WPEXtra\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renderer {

	/**
	 * Render standard Table of Contents box.
	 *
	 * @param array $headings Nested tree of headings.
	 * @param array $options Optional runtime option overrides.
	 * @return string
	 */
	public static function render( array $headings, array $options = array() ) {
		if ( empty( $headings ) ) {
			return '';
		}

		$heading_text  = isset( $options['heading_text'] ) ? $options['heading_text'] : Helper::get_option( 'heading_text', esc_html__( 'Table of Contents', 'wp-extra' ) );
		$counter_style = isset( $options['counter'] ) ? $options['counter'] : Helper::get_option( 'counter', 'none' );
		$collapse_mode = isset( $options['subheading_collapse_mode'] ) ? $options['subheading_collapse_mode'] : Helper::get_option( 'subheading_collapse_mode', 'open_first' );
		$is_init_hide  = (bool) Helper::get_option( 'visibility_hide_by_default', false );
		$show_toggle   = (bool) Helper::get_option( 'visibility', true );

		$container_classes = array(
			'wptoc-container',
			'wptoc-layout-default',
			'wptoc-counter-' . sanitize_html_class( $counter_style ),
			'wptoc-mode-' . sanitize_html_class( $collapse_mode ),
		);

		if ( $is_init_hide ) {
			$container_classes[] = 'wptoc-collapsed';
			$container_classes[] = 'toc_close';
		}

		$title_icon  = self::get_title_prefix_icon();
		$toggle_icon = $show_toggle ? self::get_toggle_icon() : '';

		ob_start();
		?>
		<div id="wptoc-container" class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>" role="navigation" aria-label="<?php echo esc_attr( $heading_text ); ?>">
			<div class="wptoc-title-container">
				<p class="wptoc-title">
					<?php echo $title_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="wptoc-title-text"><?php echo esc_html( $heading_text ); ?></span>
				</p>
				<?php if ( $show_toggle ) : ?>
					<button type="button" class="wptoc-title-toggle wptoc-toggle" aria-label="<?php echo esc_attr__( 'Toggle table of contents', 'wp-extra' ); ?>" aria-expanded="<?php echo $is_init_hide ? 'false' : 'true'; ?>">
						<?php echo $toggle_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				<?php endif; ?>
			</div>
			<nav<?php echo $is_init_hide ? ' style="display: none;"' : ''; ?>>
				<?php echo self::render_list( $headings, 1, $collapse_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</nav>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Recursive nested list renderer.
	 *
	 * @param array  $items
	 * @param int    $depth
	 * @param string $collapse_mode
	 * @return string
	 */
	public static function render_list( array $items, $depth = 1, $collapse_mode = 'open_first' ) {
		if ( empty( $items ) ) {
			return '';
		}

		$html = '<ul class="wptoc-list wptoc-depth-' . intval( $depth ) . '">';

		foreach ( $items as $index => $item ) {
			$has_children = ! empty( $item['children'] );
			$is_active    = ( 0 === $index && 1 === $depth );
			$li_classes   = array(
				'wptoc-item',
				'wptoc-heading-level-' . intval( $item['level'] ),
			);

			if ( $is_active ) {
				$li_classes[] = 'active';
			}

			if ( $has_children ) {
				$li_classes[] = 'wptoc-has-children';
				if ( 'open_all' === $collapse_mode || ( 'open_first' === $collapse_mode && 0 === $index && 1 === $depth ) ) {
					$li_classes[] = 'wptoc-open';
				} else {
					$li_classes[] = 'wptoc-closed';
				}
			}

			$html .= '<li class="' . esc_attr( implode( ' ', $li_classes ) ) . '">';
			$html .= '<div class="wptoc-item-row' . ( $is_active ? ' active' : '' ) . '">';

			$html .= '<a href="#' . esc_attr( $item['id'] ) . '" class="wptoc-link">'
				. '<span class="wptoc-text">' . esc_html( $item['title'] ) . '</span>'
				. '</a>';

			if ( $has_children ) {
				$html .= '<button type="button" class="wptoc-toggle-sub" aria-label="' . esc_attr__( 'Toggle subheadings', 'wp-extra' ) . '">'
					. '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>'
					. '</button>';
			}

			$html .= '</div>';

			if ( $has_children ) {
				$html .= self::render_list( $item['children'], $depth + 1, $collapse_mode );
			}

			$html .= '</li>';
		}

		$html .= '</ul>';
		return $html;
	}

	/**
	 * Render Sticky TOC Popup & FAB floating button in wp_footer.
	 *
	 * @param array $headings
	 * @return string
	 */
	public static function render_sticky_footer( array $headings ) {
		if ( empty( $headings ) || ! Helper::get_option( 'sticky-toggle', true ) ) {
			return '';
		}

		$sticky_pos   = Helper::get_option( 'sticky-toggle-position', 'bottom-left' );
		$pos_class    = 'wptoc-pos-' . sanitize_html_class( $sticky_pos );
		$fab_text     = trim( (string) Helper::get_option( 'sticky_button_text', '' ) );
		$has_fab_text = ! empty( $fab_text );
		$fab_class    = $has_fab_text ? 'wptoc-fab-pill' : 'wptoc-fab-icon-only';
		$heading_text = Helper::get_option( 'heading_text', esc_html__( 'Table of Contents', 'wp-extra' ) );
		$counter_style = Helper::get_option( 'counter', 'none' );
		$collapse_mode = Helper::get_option( 'subheading_collapse_mode', 'open_first' );

		ob_start();
		?>
		<div class="wptoc-sticky-wrapper <?php echo esc_attr( $pos_class ); ?>" role="navigation" aria-label="<?php echo esc_attr__( 'Table of Contents Sticky Navigation', 'wp-extra' ); ?>">
			<div id="wptoc-sticky-popup" class="wptoc-sticky-popup hide wptoc-counter-<?php echo sanitize_html_class( $counter_style ); ?> <?php echo esc_attr( $pos_class ); ?>">
				<div class="wptoc-title-container">
					<p class="wptoc-title">
						<?php echo self::get_title_prefix_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="wptoc-title-text"><?php echo esc_html( $heading_text ); ?></span>
					</p>
					<button type="button" class="wptoc-sticky-close-btn" onclick="wpTOC_hideBar(event)" aria-label="<?php echo esc_attr__( 'Close table of contents', 'wp-extra' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
					</button>
				</div>
				<nav class="wptoc-sidebar">
					<?php echo self::render_list( $headings, 1, $collapse_mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</nav>
			</div>
			<a class="wptoc-open-icon wptoc-fab-button <?php echo esc_attr( $pos_class . ' ' . $fab_class ); ?>" href="#" role="button" onclick="wpTOC_showBar(event)" aria-label="<?php echo esc_attr__( 'Open table of contents', 'wp-extra' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
					<line x1="8" y1="6" x2="21" y2="6"></line>
					<line x1="8" y1="12" x2="21" y2="12"></line>
					<line x1="8" y1="18" x2="21" y2="18"></line>
					<line x1="3" y1="6" x2="3.01" y2="6"></line>
					<line x1="3" y1="12" x2="3.01" y2="12"></line>
					<line x1="3" y1="18" x2="3.01" y2="18"></line>
				</svg>
				<?php if ( $has_fab_text ) : ?>
					<span class="wptoc-fab-text"><?php echo esc_html( $fab_text ); ?></span>
				<?php endif; ?>
			</a>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render Header Toggle Icon SVG.
	 *
	 * @return string
	 */
	public static function get_toggle_icon() {
		$icon_style = Helper::get_option( 'toggle_icon_style', 'chevron' );
		$svg        = '';

		switch ( $icon_style ) {
			case 'chevron':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';
				break;
			case 'caret':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>';
				break;
			case 'plus_minus':
				$svg = '<svg class="wptoc-svg-icon-minus" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>'
					 . '<svg class="wptoc-svg-icon-plus" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>';
				break;
			case 'grid':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="6" cy="6" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="18" r="2.5"/></svg>';
				break;
			case 'list':
			default:
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>';
				break;
		}

		return '<span class="wptoc-icon-toggle-span wptoc-icon-style-' . sanitize_html_class( $icon_style ) . '">' . $svg . '</span>';
	}

	/**
	 * Render Title Prefix Icon SVG.
	 *
	 * @return string
	 */
	public static function get_title_prefix_icon() {
		$style = Helper::get_option( 'title_icon_style', 'none' );
		if ( empty( $style ) || 'none' === $style ) {
			return '';
		}

		$svg = '';
		switch ( $style ) {
			case 'toc_tree':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="currentColor" focusable="false"><path d="M5 5c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 1 1 0-2Zm3 0h11c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 1 1 0-2Zm-3 8c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 0 1 0-2Zm3 0h11c.6 0 1 .4 1 1s-.4 1-1 1H8a1 1 0 0 1 0-2Zm0-4c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 1 1 0-2Zm3 0h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2Zm-3 8c.6 0 1 .4 1 1s-.4 1-1 1a1 1 0 0 1 0-2Zm3 0h8c.6 0 1 .4 1 1s-.4 1-1 1h-8a1 1 0 0 1 0-2Z" fill-rule="evenodd"></path></svg>';
				break;
			case 'list':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>';
				break;
			case 'bookmark':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>';
				break;
			case 'book':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>';
				break;
			case 'layers':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>';
				break;
			case 'sparkle':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>';
				break;
			case 'settings':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';
				break;
			case 'bullet':
				$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>';
				break;
			default:
				$svg = '';
				break;
		}

		return ! empty( $svg ) ? '<span class="wptoc-title-icon">' . $svg . '</span>' : '';
	}
}
