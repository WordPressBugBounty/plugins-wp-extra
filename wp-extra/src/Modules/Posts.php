<?php
namespace WPEXtra\Modules;

use WPEXtra\Settings;
use WPEXtra\Base;

class Posts extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'mce_classic',
		'mce_plugin',
		'signature',
		'classic_widget',
		'publish_btn',
		'post_revisions',
		'autosave_interval',
		'to_home',
		'redirect_single_post',
		'mce_category',
		'mce_excerpt',
		'media_default',
		'delete_attached',
		'scrolltotop',
	];
    
    public function mce_classic() {
        add_action( 'current_screen', [$this, 'remove_gutenberg'] );
        add_filter( 'page_row_actions', [$this, 'classic_editor_add_edit_links'], 15, 2 );
        add_filter( 'post_row_actions', [$this, 'classic_editor_add_edit_links'], 15, 2 );
        if ( isset( $_GET['classic-editor'] )) {
            add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
        }
        add_filter( 'redirect_post_location', [$this, 'classic_editor_redirect' ]);
    }
    
	public function remove_gutenberg() {
		$current_screen = get_current_screen();
		if($current_screen->id !== 'page' ) {
			add_filter('use_block_editor_for_post_type', '__return_false', 100);
		}
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

	public function classic_editor_redirect ( $location ) {
		if ( isset( $_REQUEST['classic-editor'] ) || ( isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], '&classic-editor' ) !== false ) ) {
			$location = add_query_arg( 'classic-editor', '', $location );
		}
		return $location;
	}
    
    public function mce_plugin() {
		if ( 'flatsome' === wp_get_theme()->template )  {
			add_action( 'admin_head', [$this, 'remove_ux_mce'], 1 );
		}
        add_filter( 'mce_external_plugins', [$this, 'mce_plugins' ]);
        add_filter( 'mce_buttons', [$this, 'mce_buttons' ]);
        add_filter( 'mce_buttons_2', [$this, 'mce_buttons_2']);
        add_filter( 'mce_buttons_2', [$this, 'remove_mce_buttons_2'], 2020 );
		if(Settings::get_option('signature')) {
			add_shortcode('signature', [$this, 'shortcode_signature']);
			if(Settings::get_option('signature_pos') == 'top') {
				add_filter('the_content', [$this, 'add_signature_top']);
			}
			if(Settings::get_option('signature_pos') == 'bottom') {
				add_filter('the_content', [$this, 'add_signature_bottom']);
			}
		}
		if (in_array('nofollow', Settings::get_option('mce_plugin')) && !class_exists( 'RankMath' )) {
            add_action( 'admin_enqueue_scripts',  [$this, 'overwrite_wplink'], 999 );
		}
        if (in_array('toc', Settings::get_option('mce_plugin'))) {
            add_action('wp_enqueue_scripts', [$this, 'mce_toc_scripts']);
        }
    }

	public function mce_plugins( $initArray ) {
		$mceplugins = array();
        if (in_array('table', Settings::get_option('mce_plugin'))) {
            $mceplugins[] = 'table';
        }
        if (in_array('visualblocks', Settings::get_option('mce_plugin'))) {
            $mceplugins[] = 'visualblocks';
        }
        if (in_array('searchreplace', Settings::get_option('mce_plugin'))) {
            $mceplugins[] = 'searchreplace';
        }
        if (in_array('letterspacing', Settings::get_option('mce_plugin'))) {
            $mceplugins[] = 'letterspacing';
        }
        if (in_array('changecase', Settings::get_option('mce_plugin'))) {
            $mceplugins[] = 'changecase';
        }
        if (in_array('cleanhtml', Settings::get_option('mce_plugin')) && Settings::isPro()) {
            $mceplugins[] = 'cleanhtml';
        }
        if (in_array('toc', Settings::get_option('mce_plugin')) && Settings::isPro()) {
            $mceplugins[] = 'toc';
        }
		if (Settings::get_option('signature')) {
			$mceplugins[] = 'signature';
		}
		foreach ($mceplugins as $item) {
			$initArray[$item] = plugins_url('/tinymce/' . $item . '/plugin.min.js', __FILE__);
		}
		return $initArray;
	}

	public function remove_ux_mce() {
		//remove_filter('mce_buttons', 'flatsome_mce_buttons_2');
		remove_filter('mce_buttons_2', 'flatsome_font_buttons');
	}

	public function mce_buttons( $buttons ) {
		array_splice( $buttons, 3, 0, 'underline' );
		array_splice( $buttons, 4, 0, 'strikethrough' );
		//array_splice( $buttons, 5, 0, 'hr' );
		array_splice( $buttons, 11, 0, 'alignjustify' );
        if (in_array('unlink', Settings::get_option('mce_plugin'))) {
            array_splice( $buttons, 13, 0, 'unlink' );
        }
		//array_splice( $buttons, 18, 0, 'fullscreen' );
		return $buttons;
	}

	public function mce_buttons_2( $buttons ) {
		if(Settings::get_option('signature')) {
			array_splice( $buttons, 6, 0, 'signature' );
		}
		if(Settings::isPro()) {
			array_splice( $buttons, 6, 0, 'cleanhtml' );
		}
		if(Settings::isPro()) {
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

	public function remove_mce_buttons_2( $buttons ) {
		$remove = array( 'hr', 'charmap', 'strikethrough', 'wp_help' );
		return array_diff( $buttons, $remove );
	}
    
	public function overwrite_wplink() {
		wp_deregister_script( 'wplink' );
		wp_register_script( 'wplink', plugins_url('/assets/js/wplink.js', WPEX_FILE ), [ 'jquery', 'wp-a11y' ], '1.0', true );
		wp_localize_script(
			'wplink',
			'wpLinkL10n',
			[
				'title'             => esc_html__( 'Insert/edit link' ),
				'update'            => esc_html__( 'Update' ),
				'save'              => esc_html__( 'Add Link' ),
				'noTitle'           => esc_html__( '(no title)' ),
				'noMatchesFound'    => esc_html__( 'No matches found.' ),
				'linkSelected'      => esc_html__( 'Link selected.' ),
				'linkInserted'      => esc_html__( 'Link inserted.' ),
				'relCheckbox'       => __( 'Add <code>rel="nofollow"</code>' ),
				'sponsoredCheckbox' => __( 'Add <code>rel="sponsored"</code>' ),
				'linkTitle'         => esc_html__( 'Link Title' ),
			]
		);
	}
    
    public function mce_toc_scripts() {
        wp_enqueue_style('toc-style', plugins_url('/assets/css/toc.css', WPEX_FILE));
        wp_enqueue_script('toc-scripts', plugins_url('/assets/js/toc.js', WPEX_FILE ), array('jquery'), null, true);
    }

	public function shortcode_signature() {
		return do_shortcode(Settings::get_option('signature_content'));
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

    public function signature() {
        add_action('wp_ajax_get_signature_content', [$this, 'get_signature_content_callback']);
        add_action('wp_ajax_nopriv_get_signature_content', [$this, 'get_signature_content_callback']); 
    }
    
    public function get_signature_content_callback() {
        wp_send_json_success(do_shortcode('[signature]'));
    }
    
	public function classic_widget() {
        add_filter('gutenberg_use_widgets_block_editor', '__return_false');
        add_filter('use_widgets_block_editor', '__return_false');
    }

    public function publish_btn() {
        add_action( 'admin_enqueue_scripts',  [$this, 'publish_button_enqueue'], 20 );
    }

	public function publish_button_enqueue() {
		global $pagenow;
		if ( is_admin() && ($pagenow == 'post.php' || $pagenow == 'post-new.php') ) {
			if ( in_array( get_post_type(), Settings::get_option('publish_btn') ) ) {
				wp_enqueue_script('publish-button', plugins_url('/assets/js/publish-button.js', WPEX_FILE ), array('jquery'), '1.0', true );
			}
		} 
	}
            
    public function post_revisions() {
        add_action( 'admin_init', [$this, 'disable_revisions'] );
    }
    
    public function disable_revisions() {
        $post_types = Settings::get_option('post_revisions');
        if ( !is_array( $post_types ) || empty( $post_types ) ) {
            return;
        }
        foreach ( $post_types as $post_type ) {
            remove_post_type_support( $post_type, 'revisions' );
        }
    }
    
    public function to_home() {
        add_shortcode('redirect', [$this, 'redirect_shortcode']);
        if ( 'flatsome' === wp_get_theme()->template )  {
            add_action('flatsome_after_404', [$this, 'add_redirect_shortcode_to_404']);
		}
    }
    
    public function redirect_shortcode($atts) {
        $atts = shortcode_atts(['url' => home_url(), 'time' => 10], $atts, 'redirect');
        $url = esc_url($atts['url']);
        $time = absint($atts['time']);
        $output = '<p class="is-xlarge" align="center">' . sprintf(__("You will be redirected in %s seconds", 'wp-extra' ), '<span id="count-rc">'.$time.'</span>') . '</p>';
        $output .= "
        <script>
            var countdown = {$time}; 
            var cdElement = document.getElementById('count-rc');
            var interval = setInterval(function() {
                cdElement.textContent = --countdown;
                if (countdown < 0) {
                    clearInterval(interval);
                    window.location.href = '{$url}';
                }
            }, 1000);
        </script>";
        return $output;
    }

    public function add_redirect_shortcode_to_404() {
        echo do_shortcode('[redirect]');
    }

	public function redirect_single_post() {
        add_action('template_redirect', [$this, 'search_results_return_one_post']);
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
    
    public function mce_category() {
		if(!class_exists( 'RankMath' )) {
            add_filter('term_description', 'do_shortcode');
            add_filter('category_edit_form_fields', [$this, 'category_description_editor']);
        }
    }
    
    public function mce_excerpt() {
        add_action('add_meta_boxes', [$this, 'replace_metabox']);
        add_action('enqueue_block_editor_assets', [$this, 'remove_panel_from_block_editor']);
    }
    
    public function replace_metabox() {
        $allowed_post_types = Settings::get_option('mce_excerpt');
        $current_post_type = get_post_type();
        if (in_array($current_post_type, $allowed_post_types)) {
            remove_meta_box('postexcerpt', $current_post_type, 'normal');
            add_meta_box(
                'postexcerpt',
                __('Excerpt'),
                [$this, 'render_metabox'],
                $current_post_type,
                'normal',
                'high',
                [ '__back_compat_meta_box' => false ]
            );
        }
    }

    public function render_metabox($post) {
        $settings = [
            'media_buttons' => false,
        ];
        wp_editor(html_entity_decode($post->post_excerpt), 'excerpt', $settings);
    }

    public function remove_panel_from_block_editor() {
        wp_add_inline_script(
            'wp-edit-post',
            'wp.data.dispatch("core/edit-post").removeEditorPanel("post-excerpt");'
        );
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
    
    public function media_default() {
        add_filter( 'get_post_metadata', [$this, 'set_media_default'], 10, 4 );
    }
    
    public function set_media_default( $null, $object_id, $meta_key, $single ) {
        $allowed_post_types = array( 'post', 'product' );
        if ( ! in_array( get_post_type( $object_id ), $allowed_post_types ) ) {
            return $null;
        }
        
        if ( is_single($object_id)) {
            return null;
        }
        if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
            return $null;
        }
        if ( empty( $meta_key ) || $meta_key !== '_thumbnail_id' ) {
            return $null;
        }
        if ( ! post_type_supports( get_post_type( $object_id ), 'thumbnail' ) ) {
            return $null;
        }
        $meta_cache = wp_cache_get( $object_id, 'post_meta' );
        if ( ! $meta_cache ) {
            $meta_cache = update_meta_cache( 'post', array( $object_id ) );
            $meta_cache = $meta_cache[ $object_id ] ?? array();
        }
        if ( ! empty( $meta_cache['_thumbnail_id'][0] ) ) {
            return $null;
        }
        $meta_cache['_thumbnail_id'][0] = Settings::get_option('media_default');
        wp_cache_set( $object_id, $meta_cache, 'post_meta' );
        return $null;
    }

    public function delete_attached() {
        add_action('before_delete_post', [$this, 'delete_attachments']);
    }
    
    public function delete_attachments( $post_id ) {
        $attachments = get_attached_media( '', $post_id );
        foreach ($attachments as $attachment) {
            $attachment_used_in = $this->get_posts_by_attachment_id($attachment->ID);
            $is_parent = $attachment->post_parent === $post_id;
            if( $is_parent ) {
                $other_posts_exits_content = array_diff( $attachment_used_in['content'],[$post_id]);
                $other_posts_exits_thumb = array_diff( $attachment_used_in['thumbnail'],[$post_id]);
                $other_posts_exits = array_merge($other_posts_exits_content, $other_posts_exits_thumb);
                if( !empty($other_posts_exits) ) {
                    wp_update_post([
                        'ID' => $attachment->ID,
                        'post_parent' => $other_posts_exits[0]
                    ]);
                } else {
                    wp_delete_attachment( $attachment->ID, true );
                }
            }
        }
    }
    
    private function get_posts_by_attachment_id( $attachment_id ) {
        $used_as_thumbnail = array();
        if ( wp_attachment_is_image( $attachment_id ) ) {
            $thumbnail_query = new \WP_Query( array(
                'meta_key'       => '_thumbnail_id',
                'meta_value'     => $attachment_id,
                'post_type'      => 'any',
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'posts_per_page' => - 1,
                'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
            ) );

            $used_as_thumbnail = $thumbnail_query->posts;
        }
        $attachment_urls = array( wp_get_attachment_url( $attachment_id ) );
        if ( wp_attachment_is_image( $attachment_id ) ) {
            foreach ( get_intermediate_image_sizes() as $size ) {
                $intermediate = image_get_intermediate_size( $attachment_id, $size );
                if ( $intermediate ) {
                    $attachment_urls[] = $intermediate['url'];
                }
            }
        }
        $used_in_content = array();
        foreach ( $attachment_urls as $attachment_url ) {
            $content_query = new \WP_Query( array(
                's'              => $attachment_url,
                'post_type'      => 'any',
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'posts_per_page' => - 1,
                'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
            ) );
            $used_in_content = array_merge( $used_in_content, $content_query->posts );
        }
        $used_in_content = array_unique( $used_in_content );
        return array(
            'thumbnail' => $used_as_thumbnail,
            'content'   => $used_in_content,
        );
    }
    
    public function scrolltotop() {
        add_action('admin_footer', [$this, 'scroll_to_top']);
    }
    
    public function scroll_to_top() { 
        $screen = get_current_screen();
        if ($screen && $screen->is_block_editor) {
            return;
        }
        echo '<a id="backtotop" class="button button-primary components-button is-primary is-compact" href="#" style="position: fixed; right: 10px; bottom: 15px; box-shadow: rgba(0, 0, 0, 0.2) 0px 4px 8px; padding: 6px; height:32px;"><span class="dashicons dashicons-arrow-up-alt"></span></a>';
        ?>
        <script>
            jQuery(window).on('scroll', function() {
                var scrollPosition = jQuery(window).scrollTop();
                if (scrollPosition > 200) {
                    jQuery('#backtotop').fadeIn('slow');
                } else {
                    jQuery('#backtotop').fadeOut('slow');
                }
            });
            jQuery('#backtotop').on('click', function(e) {
                e.preventDefault();
                jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
            });
        </script>
        <?php
    }
}
