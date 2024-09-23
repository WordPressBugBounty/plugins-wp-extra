<?php

namespace WPEXtra\Modules;

use WPEXtra\Core\ClassEXtra;
use WP_Error;
use WP_Term;
use WP_Query;

class Posts {

    public function __construct() {

		if(wp_extra_get_option('mce_classic')) {
			add_action( 'current_screen', [$this, 'this_screen_gutenberg_remove'] );
			add_filter( 'page_row_actions', [$this, 'classic_editor_add_edit_links'], 15, 2 );
			add_filter( 'post_row_actions', [$this, 'classic_editor_add_edit_links'], 15, 2 );
			if ( isset( $_GET['classic-editor'] )) {
				add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
			}
			add_filter( 'redirect_post_location', [$this, 'classic_editor_redirect_location' ]);
            if(wp_extra_get_option('mce_plugin')) {
                add_filter( 'mce_external_plugins', [$this, 'wpex_mce_plugin' ]);
                add_filter( 'mce_buttons', [$this, 'wpex_mce_buttons' ]);
                add_filter( 'mce_buttons_2', [$this, 'wpex_mce_buttons_2']);
                add_filter( 'mce_buttons_2', [$this, 'wpex_remove_mce_buttons_2'], 2020 );
            }
		}
		if ( 'flatsome' === wp_get_theme()->template && wp_extra_get_option('mce_plugin') )  {
			add_action( 'admin_head',[$this, 'remove_ux_mce'], 1 );
		}

		if(wp_extra_get_option('signature')) {
			add_shortcode('signature', [$this, 'shortcode_signature']);
			if(wp_extra_get_option('signature_pos') == 'top') {
				add_filter('the_content', [$this, 'add_signature_top']);
			}
			if(wp_extra_get_option('signature_pos') == 'bottom') {
				add_filter('the_content', [$this, 'add_signature_bottom']);
			}
		}
		if (wp_extra_get_option('mce_plugin') && in_array('nofollow', wp_extra_get_option('mce_plugin')) && !class_exists( 'RankMath' )) {
            add_action( 'admin_enqueue_scripts',  [$this, 'overwrite_wplink'], 999 );
		}
		if(wp_extra_get_option('publish_btn')) {
            add_action( 'admin_enqueue_scripts',  [$this, 'publish_button_enqueue'], 20 );
		}
	
		if(wp_extra_get_option('classic_widget')) {
			add_filter('gutenberg_use_widgets_block_editor', '__return_false');
			add_filter('use_widgets_block_editor', '__return_false');
		}
		
		if(wp_extra_get_option('limit_post_revisions')) {
			if(defined('WP_POST_REVISIONS')) {
				add_action('admin_notices', [$this, 'notice_post_revisions']);
			} else {
				define('WP_POST_REVISIONS', wp_extra_get_option('limit_post_revisions'));
			}
		}

		if(wp_extra_get_option('autosave_interval')) {
			if(defined('AUTOSAVE_INTERVAL')) {
				add_action('admin_notices', [$this, 'notice_autosave_interval']);
			} else {
				define('AUTOSAVE_INTERVAL', wp_extra_get_option('autosave_interval'));
			}
		}

		if(wp_extra_get_option('redirect_single_post')) {
			add_action('template_redirect', [$this, 'search_results_return_one_post']);
		}
    
		if(wp_extra_get_option('mce_category') && !class_exists( 'RankMath' )) {
            add_filter('term_description', 'do_shortcode');
            add_filter('category_edit_form_fields', [$this, 'category_description_editor']);
        }
        if (wp_extra_get_option('mce_plugin') && in_array('toc', wp_extra_get_option('mce_plugin'))) {
            add_action('wp_enqueue_scripts', [$this, 'mce_toc_scripts']);
        }
		if (wp_extra_get_option('page_extension')) {
			add_action('init', [$this, 'init_page_slash'], -1);
            add_filter('user_trailingslashit', [$this, 'no_page_slash'], 66, 2);
        }

    }
    
	public function category_description_editor( $term ) {
		?>
		<tr class="form-field term-description-wrap">
			<th scope="row"><label for="description"><?php esc_html_e( 'Description' ); ?></label></th>
			<td>
				<?php
				wp_editor(
					html_entity_decode( $term->description, ENT_QUOTES, 'UTF-8' ),
					'category_description_editor',
					[
						'textarea_name' => 'description',
						'textarea_rows' => 20,
					]
				);
				?>
			</td>
			<script>
				jQuery('textarea#description').closest('.form-field').remove();
			</script>
		</tr>
		<?php
	}
    
	public function overwrite_wplink() {
		wp_deregister_script( 'wplink' );
		wp_register_script( 'wplink', plugins_url('/assets/js/wplink.js', WPEX_FILE ), [ 'jquery', 'wp-a11y' ], '1.0', true );
		wp_localize_script(
			'wplink',
			'wpLinkL10n',
			[
				'title'             => esc_html__( 'Insert/edit link', 'wp-extra' ),
				'update'            => esc_html__( 'Update', 'wp-extra' ),
				'save'              => esc_html__( 'Add Link', 'wp-extra' ),
				'noTitle'           => esc_html__( '(no title)', 'wp-extra' ),
				'noMatchesFound'    => esc_html__( 'No matches found.', 'wp-extra' ),
				'linkSelected'      => esc_html__( 'Link selected.', 'wp-extra' ),
				'linkInserted'      => esc_html__( 'Link inserted.', 'wp-extra' ),
				'relCheckbox'       => __( 'Add <code>rel="nofollow"</code>', 'wp-extra' ),
				'sponsoredCheckbox' => __( 'Add <code>rel="sponsored"</code>', 'wp-extra' ),
				'linkTitle'         => esc_html__( 'Link Title', 'wp-extra' ),
			]
		);
	}

	public function this_screen_gutenberg_remove() {
		$current_screen = get_current_screen();
		if($current_screen->id !== 'page' ) { //All post type
			add_filter('use_block_editor_for_post_type', '__return_false', 100);
		}
	}

	public function classic_editor_redirect_location ( $location ) {
		if ( isset( $_REQUEST['classic-editor'] ) || ( isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], '&classic-editor' ) !== false ) ) {
			$location = add_query_arg( 'classic-editor', '', $location );
		}
		return $location;
	}

	public function classic_editor_add_edit_links ( $actions, $post ) {
		if ( 'trash' === $post->post_status || ! post_type_supports( $post->post_type, 'editor' ) ) {
			return $actions;
		}
		$edit_url = get_edit_post_link( $post->ID, 'raw' );
		if ( ! $edit_url ) {
			return $actions;
		}
		if ( $post->post_type == 'page' ) {
			$edit_url = add_query_arg( 'classic-editor', '', $edit_url );
			$title       = _draft_or_post_title( $post->ID );
			$edit_action = array(
                'classic' => sprintf(
                    '<a href="%s" aria-label="%s">%s</a>',
                    esc_url( $edit_url ),
                    esc_attr( sprintf(
                        __( 'Classic Block Keyboard Shortcuts' ),
                        $title
                    ) ),
                    __('Edit Classic')
                ),
            );
			$edit_offset = array_search( 'edit', array_keys( $actions ), true );
			array_splice( $actions, $edit_offset + 1, 0, $edit_action );
		}
		return $actions;
	}

	public function remove_ux_mce() {
		//remove_filter('mce_buttons', 'flatsome_mce_buttons_2');
		remove_filter('mce_buttons_2', 'flatsome_font_buttons');
	}

	public function wpex_mce_plugin( $initArray ) {
        $pro_check = ClassEXtra::instance();
		$mceplugins = array();
        if (in_array('table', wp_extra_get_option('mce_plugin'))) {
            $mceplugins[] = 'table';
        }
        if (in_array('visualblocks', wp_extra_get_option('mce_plugin'))) {
            $mceplugins[] = 'visualblocks';
        }
        if (in_array('searchreplace', wp_extra_get_option('mce_plugin'))) {
            $mceplugins[] = 'searchreplace';
        }
        if (in_array('letterspacing', wp_extra_get_option('mce_plugin'))) {
            $mceplugins[] = 'letterspacing';
        }
        if (in_array('changecase', wp_extra_get_option('mce_plugin'))) {
            $mceplugins[] = 'changecase';
        }
        if (in_array('cleanhtml', wp_extra_get_option('mce_plugin')) && $pro_check->isPro()) {
            $mceplugins[] = 'cleanhtml';
        }
        if (in_array('toc', wp_extra_get_option('mce_plugin')) && $pro_check->isPro()) {
            $mceplugins[] = 'toc';
        }
		if (wp_extra_get_option('signature')) {
			$mceplugins[] = 'signature';
		}
		foreach ($mceplugins as $item) {
			$initArray[$item] = plugins_url('/tinymce/' . $item . '/plugin.min.js', __FILE__);
		}
		return $initArray;
	}

	public function wpex_mce_buttons( $buttons ) {
		array_splice( $buttons, 3, 0, 'underline' );
		array_splice( $buttons, 4, 0, 'strikethrough' );
		//array_splice( $buttons, 5, 0, 'hr' );
		array_splice( $buttons, 11, 0, 'alignjustify' );
        if (in_array('unlink', wp_extra_get_option('mce_plugin'))) {
            array_splice( $buttons, 13, 0, 'unlink' );
        }
		//array_splice( $buttons, 18, 0, 'fullscreen' );
		return $buttons;
	}

	public function wpex_mce_buttons_2( $buttons ) {
        $pro_check = ClassEXtra::instance();
		if(wp_extra_get_option('signature')) {
			array_splice( $buttons, 6, 0, 'signature' );
		}
		if($pro_check->isPro()) {
			array_splice( $buttons, 6, 0, 'cleanhtml' );
		}
		if($pro_check->isPro()) {
			array_splice( $buttons, 6, 0, 'toc' );
		}
		array_splice( $buttons, 1, 0, 'fontselect' );
		array_splice( $buttons, 2, 0, 'fontsizeselect' );
        array_splice( $buttons, 3, 0,  'letterspacing' );
        array_splice( $buttons, 4, 0,  'changecase' );
		array_splice( $buttons, 5, 0, 'backcolor' );
		array_splice( $buttons, 7, 0, 'table' );
		array_splice( $buttons, 8, 0, 'visualblocks' );
		array_splice( $buttons, 19, 0, 'searchreplace' );
		array_splice( $buttons, 20, 0, 'wp_code' );
		return $buttons;
	}

	public function wpex_remove_mce_buttons_2( $buttons ) {
		$remove = array( 'hr', 'charmap', 'strikethrough', 'wp_help' );
		return array_diff( $buttons, $remove );
	}
    
    public function mce_toc_scripts() {
        wp_enqueue_style('toc-style', plugins_url('/assets/css/toc.css', WPEX_FILE));
        wp_enqueue_script('toc-scripts', plugins_url('/assets/js/toc.js', WPEX_FILE ), array('jquery'), null, true);
    }

	public function shortcode_signature() {
		return stripslashes(wp_extra_get_option('signature_content'));
	}

	public function add_signature_top($content) {
        if ( ! is_singular( 'post' ) && ! is_singular( 'product' ) ) {
            return $content;
        }

		$signature = do_shortcode('[signature]');
		$content_with_signature_top = $signature . $content;
		return $content_with_signature_top;
	}

	public function add_signature_bottom($content) {
        if ( ! is_singular( 'post' ) && ! is_singular( 'product' ) ) {
            return $content;
        }

		$signature = do_shortcode('[signature]');
		$content_with_signature_bottom = $content . $signature;
		return $content_with_signature_bottom;
	}

	public function publish_button_enqueue() {
		global $pagenow;
		if ( is_admin() && ($pagenow == 'post.php' || $pagenow == 'post-new.php') ) {
			$post_type = get_post_type();
			$publish_button = wp_extra_get_option('publish_btn');
			if ( $publish_button && in_array( $post_type, $publish_button ) ) {
				wp_enqueue_script('publish-button', plugins_url('/assets/js/publish-button.js', WPEX_FILE ), array('jquery'), '1.0', true );
			}
		} 
	}

	public function notice_post_revisions() {
        printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s %s</p></div>',
            esc_html__('Warning:'),
            esc_html__('WP_POST_REVISIONS'),
            esc_html__('is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'wp-extra')
        );
    }

	public function notice_autosave_interval() {
        printf(
            '<div class="notice notice-error"><p><strong>%s</strong> %s %s</p></div>',
            esc_html__('Warning:'),
            esc_html__('AUTOSAVE_INTERVAL'),
            esc_html__('is already enabled somewhere else on your site. We suggest only enabling this feature in one place.', 'wp-extra')
        );
    }

    public function search_results_return_one_post() {
        if (is_search()) {
            global $wp_query;
            if ($wp_query->post_count == 1 && $wp_query->max_num_pages == 1) {
                wp_redirect(get_permalink($wp_query->posts[0]->ID));
                exit;
            }
        }
    }
    
    public function init_page_slash() {
        global $wp_rewrite;
        $page_slash = wp_extra_get_option('page_slash') ? wp_extra_get_option('page_slash') : '.html';
        if (!strpos($wp_rewrite->get_page_permastruct(), $page_slash)) {
            $wp_rewrite->page_structure = $wp_rewrite->page_structure . $page_slash;
        }
    }
    
    public function no_page_slash($string, $type) {
        global $wp_rewrite;
        if ($wp_rewrite->using_permalinks() && $wp_rewrite->use_trailing_slashes == true && $type == 'page') {
            return untrailingslashit($string);
        }
        return $string;
    }


}
