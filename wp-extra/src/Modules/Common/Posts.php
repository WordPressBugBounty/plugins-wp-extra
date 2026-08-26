<?php
namespace WPEXtra\Modules\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class Posts extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'mce_classic',
		'mce_plugins',
		'signature',
		'classic_widget',
		'disable_widget',
		'publish_btn',
		'post_revisions',
		'delete_attached',
		'lock_modified',
		'show_modified',
		'img_column',
		'disable_tags',
	];

    public function disable_widget() {
        add_action('widgets_init', [$this, 'disable_sidebar_widgets'], 100);
    }
    
    public function disable_sidebar_widgets() {
        if (!is_admin() || (isset($_GET['page']) && $_GET['page'] === 'wp-extra')) {
            return;
        }
        $widgets = (array) Helper::get_option('disable_widget', []);
        if (!empty($widgets)) {
            foreach ($widgets as $widget_class) {
                if (class_exists($widget_class)) {
                    unregister_widget($widget_class);
                }
            }
        }
    }
    
    public function mce_classic() {
        add_action( 'current_screen', [$this, 'remove_gutenberg'] );
        add_filter( 'page_row_actions', [$this, 'classic_editor_add_edit_links'], 15, 2 );
        add_filter( 'post_row_actions', [$this, 'classic_editor_add_edit_links'], 15, 2 );
        if ( isset( $_GET['classic-editor'] )) {
            add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
            add_filter( 'tiny_mce_before_init', [$this, 'disable_wpautop_for_page_classic'] );
        }
        add_filter( 'redirect_post_location', [$this, 'classic_editor_redirect' ]);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_getimage_image', [$this, 'ajax_download_image']);
        
    }
    
    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_localize_script('jquery', 'EXTRA_DL', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('extra_dl_nonce'),
                'i18n' => [
                    'no_image'      => __('Please select an image in the editor.', 'wp-extra'),
                    'already_local' => __('This image is already in your Media Library.', 'wp-extra'),
                    'success'       => __('✅ Image successfully downloaded and replaced.', 'wp-extra'),
                    'error'         => __('❌ Failed to download the image.', 'wp-extra'),
                    'connection'    => __('❌ Connection error.', 'wp-extra'),
                ],
            ]);
        }
    }

    public function ajax_download_image() {
        check_ajax_referer('extra_dl_nonce', 'nonce');

        $url     = esc_url_raw($_POST['url'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        $alt     = sanitize_text_field($_POST['alt'] ?? '');
        $title   = sanitize_text_field($_POST['title'] ?? '');

        if (empty($url) || !$post_id || !wp_http_validate_url($url)) {
            wp_send_json_error(['message' => __('Missing or invalid data.', 'wp-extra')]);
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('You do not have permission to edit this post.', 'wp-extra')]);
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found.', 'wp-extra')]);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            wp_send_json_error(['message' => sprintf(__('Download failed: %s', 'wp-extra'), $tmp->get_error_message())]);
        }

        $slug = sanitize_title($post->post_name ?: $post->post_title);
        $ext  = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';

        if (empty($slug)) {
            $filename = basename(parse_url($url, PHP_URL_PATH));
        } else {
            $filename = "{$slug}.{$ext}";
        }

        $file = [
            'name'     => $filename,
            'type'     => mime_content_type($tmp),
            'tmp_name' => $tmp,
            'size'     => filesize($tmp),
        ];

        $attachment_id = media_handle_sideload($file, $post_id);

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            wp_send_json_error(['message' => __('Could not import image.', 'wp-extra')]);
        }

        if ($alt) update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
        if ($title) wp_update_post(['ID' => $attachment_id, 'post_title' => $title]);

        $new_url = wp_get_attachment_url($attachment_id);

        wp_send_json_success(['new_url' => $new_url]);
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
			array_splice( $actions, $edit_offset, 0, $edit_action );
		}
		return $actions;
	}
    
    public function disable_wpautop_for_page_classic( $init ) {
        if ( ! is_admin() ) {
            return $init;
        }
        if ( ! function_exists( 'get_current_screen' ) ) {
            return $init;
        }
        $screen = get_current_screen();
        if ( $screen->post_type !== 'page' ) {
            return $init;
        }
        $init['wpautop'] = false;
        $init['forced_root_block'] = false;
        return $init;
    }

	public function classic_editor_redirect ( $location ) {
		if ( isset( $_REQUEST['classic-editor'] ) || ( isset( $_POST['_wp_http_referer'] ) && strpos( $_POST['_wp_http_referer'], '&classic-editor' ) !== false ) ) {
			$location = add_query_arg( 'classic-editor', '', $location );
		}
		return $location;
	}
    
    public function mce_plugins() {
		if ( 'flatsome' === wp_get_theme()->template )  {
			add_action( 'admin_head', [$this, 'remove_ux_mce'], 1 );
		}
        add_filter( 'mce_external_plugins', [$this, 'mce_plugin' ]);
        add_filter( 'mce_buttons', [$this, 'mce_buttons' ]);
        add_filter( 'mce_buttons_2', [$this, 'mce_buttons_2']);
        add_action( 'wp_enqueue_scripts', [$this, 'enqueue_frontend_styles'] );
        add_filter( 'mce_css', [$this, 'add_editor_styles'] );
		if(Settings::get_option('signature')) {
			add_shortcode('signature', [$this, 'shortcode_signature']);
			if(Settings::get_option('signature_pos') == 'top') {
				add_filter('the_content', [$this, 'add_signature_top']);
			}
			if(Settings::get_option('signature_pos') == 'bottom') {
				add_filter('the_content', [$this, 'add_signature_bottom']);
			}
		}
		if (Settings::get_option('mce_plugins') && !class_exists( 'RankMath' )) {
            add_action( 'admin_enqueue_scripts',  [$this, 'overwrite_wplink'], 999 );
		}
    }

    public function enqueue_frontend_styles() {
        wp_enqueue_style( 'wpex-checklist', plugins_url('/assets/css/checklist.min.css', WPEX_FILE), [], defined('WPEX_VERSION') ? WPEX_VERSION : null );
    }

    public function add_editor_styles( $mce_css ) {
        $checklist_css = plugins_url('/assets/css/checklist.min.css', WPEX_FILE);
        if ( ! empty( $mce_css ) ) {
            $mce_css .= ',' . $checklist_css;
        } else {
            $mce_css = $checklist_css;
        }
        return $mce_css;
    }

	public function mce_plugin( $init ) {
        $plugins = [];
        if ( Settings::get_option( 'mce_plugins' ) ) {
            $plugins = [
                'table',
                'visualblocks',
                'searchreplace',
                'letterspacing',
                'changecase',
                'cleanhtml',
                'ultable',
                'getimage',
                'checklist',
            ];
        }
        if ( Settings::get_option( 'signature' ) ) {
            $plugins[] = 'signature';
        }
		foreach ($plugins as $item) {
			$init[$item] = plugins_url('/assets/tinymce/' . $item . '/plugin.min.js', WPEX_FILE);
		}
		return $init;
	}

	public function remove_ux_mce() {
		remove_filter('mce_buttons', 'flatsome_mce_buttons_2');
		remove_filter('mce_buttons_2', 'flatsome_font_buttons');
	}

	public function mce_buttons( $buttons ) {
		array_splice( $buttons, 3, 0, 'underline' );
		array_splice( $buttons, 4, 0, 'strikethrough' );
		//array_splice( $buttons, 5, 0, 'hr' );
		array_splice( $buttons, 11, 0, 'alignjustify' );
        array_splice( $buttons, 13, 0, 'unlink' );
		array_splice( $buttons, 14, 0, 'visualblocks' );
		array_splice( $buttons, 15, 0, 'searchreplace' );
		array_splice( $buttons, 16, 0, 'wp_code' );

        // Place checklist dropdown right next to list buttons (numlist / bullist)
        $pos = array_search( 'numlist', $buttons, true );
        if ( false !== $pos ) {
            array_splice( $buttons, $pos + 1, 0, 'checklist' );
        } else {
            $pos = array_search( 'bullist', $buttons, true );
            if ( false !== $pos ) {
                array_splice( $buttons, $pos + 1, 0, 'checklist' );
            } else {
                $buttons[] = 'checklist';
            }
        }

		return $buttons;
	}

	public function mce_buttons_2( $buttons ) {
		if(Settings::get_option('signature')) {
			array_splice( $buttons, 6, 0, 'signature' );
		}
		array_splice( $buttons, 1, 0, 'fontselect' );
		array_splice( $buttons, 2, 0, 'fontsizeselect' );
        array_splice( $buttons, 3, 0, 'letterspacing' );
        array_splice( $buttons, 4, 0, 'changecase' );
		array_splice( $buttons, 7, 0, 'backcolor' );
		array_splice( $buttons, 9, 0, 'table' );
		array_splice( $buttons, 10, 0, 'cleanhtml' );
		array_splice( $buttons, 12, 0, 'getimage' );
		array_splice( $buttons, 20, 0, 'ultable' );
		return $buttons;
	}

	public function remove_mce_buttons_2( $buttons ) {
		$remove = array( 'hr', 'charmap', 'strikethrough', 'wp_help' );
		return array_diff( $buttons, $remove );
	}
    
	public function overwrite_wplink() {
		wp_deregister_script( 'wplink' );
		wp_register_script( 'wplink', plugins_url('/assets/js/wplink.min.js', WPEX_FILE ), [ 'jquery', 'wp-a11y' ], '1.0', true );
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
            wp_enqueue_script('publish-button', plugins_url('/assets/js/publish-button.min.js', WPEX_FILE ), array('jquery'), '1.0', true );
		} 
	}
            
    public function post_revisions() {
        add_filter('wp_revisions_to_keep', [$this, 'limit_revisions'], 10, 2);
    }

    public function limit_revisions($num, $post) {
        $limit = Settings::get_option('post_revisions');
        if ($limit === '' || $limit === null || !is_numeric($limit)) {
            return $num;
        }
        return max(0, (int) $limit);
    }

    public function delete_attached() {
        add_action('before_delete_post', [$this, 'delete_attachments']);
    }
    
    public function delete_attachments($post_id) {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        $attachments = get_attached_media('', $post_id);
        if (empty($attachments)) {
            return;
        }
        global $wpdb;
        foreach ($attachments as $attachment) {
            if ($attachment->post_parent !== (int)$post_id) {
                continue;
            }
            $other_thumb = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d AND post_id != %d LIMIT 1",
                $attachment->ID,
                $post_id
            ));

            if ($other_thumb) {
                wp_update_post([
                    'ID'          => $attachment->ID,
                    'post_parent' => (int)$other_thumb,
                ]);
            } else {
                wp_delete_attachment($attachment->ID, true);
            }
        }
    }
    
    public function show_modified() {
        add_filter('manage_post_posts_columns', [$this, 'modified_column_register']);
        add_action('manage_post_posts_custom_column', [$this, 'modified_column_display'], 10, 2);
        add_filter('manage_edit-post_sortable_columns', [$this, 'modified_column_register_sortable']);

        add_action('admin_footer', [$this, 'script_modified']);
        add_action('wp_ajax_convert_post_date', [$this, 'convert_post_date']);
    }

    public function modified_column_register($columns) {
        $columns['modified'] = __('Last Modified');
        return $columns;
    }

    public function modified_column_display($column_name, $post_id) {
        if ($column_name !== 'modified') return;

        $author_id = get_post_field('post_modified_by', $post_id);
        if ($author_id) {
            echo '<small>' . esc_html(get_the_author_meta('display_name', $author_id)) . '</small><br>';
        }

        echo '<button class="button-link convert-date-btn" data-id="' . esc_attr($post_id) . '">
                <span class="dashicons dashicons-backup"></span>
              </button> ';

        echo sprintf(
            esc_html__('%1$s at %2$s'),
            esc_html(get_the_modified_date('d/m/Y', $post_id)),
            esc_html(get_the_modified_time('', $post_id))
        );
    }

    public function modified_column_register_sortable($columns) {
        $columns['modified'] = 'modified';
        return $columns;
    }

    public function script_modified() {
        $screen = get_current_screen();

        if (!$screen || $screen->base !== 'edit' || $screen->post_type !== 'post') return;

        $nonce = wp_create_nonce('convert_post_date_nonce');
        ?>
        <script>
        jQuery(function($){

            const confirmText = "<?php echo esc_js(sprintf('%s → %s?', __('Last Modified'), __('Published'))); ?>";
            const done  = "<?php echo esc_js(__('Done')); ?>";
            const error = "<?php echo esc_js(__('An error occurred.')); ?>";

            $(document).on('click', '.convert-date-btn', function(){
                if (!confirm(confirmText)) return;

                $.post(ajaxurl, {
                    action: 'convert_post_date',
                    post_id: $(this).data('id'),
                    nonce: '<?php echo $nonce; ?>'
                }, function(res){
                    if(res.success){
                        alert(done);
                        location.reload();
                    } else {
                        alert(error);
                    }
                });
            });

        });
        </script>
        <?php
    }

    public function convert_post_date() {
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], 'convert_post_date_nonce')
        ) {
            wp_send_json_error();
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error();
        }

        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error();
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            wp_send_json_error();
        }

        global $wpdb;

        $publish_date = $post->post_date;
        $gmt = get_gmt_from_date($publish_date);

        $result = $wpdb->update(
            $wpdb->posts,
            [
                'post_modified'     => $publish_date,
                'post_modified_gmt' => $gmt
            ],
            ['ID' => $post_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error();
        }

        clean_post_cache($post_id);

        wp_send_json_success();
    }
    
    public function lock_modified() {
        add_filter('wp_insert_post_data', [$this, 'disable_post_modified'], 99, 2);
    }

    public function disable_post_modified($data, $postarr) {
        if (empty($postarr['ID'])) return $data;

        $post = get_post($postarr['ID']);
        if (!$post || $post->post_type !== 'post') return $data;

        if ($post->post_status !== 'publish') return $data;

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $data;

        $data['post_modified']     = $post->post_modified;
        $data['post_modified_gmt'] = $post->post_modified_gmt;

        return $data;
    }

    public function img_column() {
        add_filter('manage_post_posts_columns', [$this, 'add_img_column']);
        add_action('manage_post_posts_custom_column', [$this, 'manage_img_column'], 10, 2);
        add_action('admin_head-edit.php', [$this, 'img_column_css']);
    }

    public function img_column_css() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'post') {
            return;
        }

        echo '<style>
            .wp-list-table th.column-thumbnail,.wp-list-table td.column-thumbnail{width:52px!important;text-align:center!important;vertical-align:top!important;padding:10px 4px!important;box-sizing:border-box!important}
            .wp-list-table th.check-column,.wp-list-table td.check-column{vertical-align:top!important;padding-top:14px!important}
            .wp-list-table td.column-title{vertical-align:top!important}
            .wpex-thumb-box{width:40px;height:40px;margin:0 auto;display:flex;align-items:center;justify-content:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:4px;overflow:hidden;box-sizing:border-box}
            .wpex-thumb-box a{display:flex;align-items:center;justify-content:center;width:100%;height:100%;text-decoration:none}
            .wpex-thumb-box img{width:100%!important;height:100%!important;object-fit:cover!important;display:block!important}
            .wpex-thumb-box .dashicons{color:#94a3b8;font-size:18px;width:18px;height:18px;line-height:18px;display:block}
        </style>';
    }

    public function add_img_column($columns) {
        $new_columns = [];
        foreach ($columns as $key => $title) {
            if ($key === 'title') {
                $new_columns['thumbnail'] = '<span class="screen-reader-text">' . esc_html__('Thumbnail', 'wp-extra') . '</span>';
            }
            $new_columns[$key] = $title;
        }
        return $new_columns;
    }

    public function manage_img_column($column_name, $post_id) {
        if ('thumbnail' === $column_name) {
            $edit_link = get_edit_post_link($post_id);
            echo '<div class="wpex-thumb-box">';
            if (has_post_thumbnail($post_id)) {
                $img_html = get_the_post_thumbnail($post_id, [80, 80], ['loading' => 'lazy']);
                echo $edit_link ? '<a href="' . esc_url($edit_link) . '">' . $img_html . '</a>' : $img_html;
            } else {
                $placeholder = '<span class="dashicons dashicons-format-image"></span>';
                echo $edit_link ? '<a href="' . esc_url($edit_link) . '">' . $placeholder . '</a>' : $placeholder;
            }
            echo '</div>';
        }
    }

    public function disable_tags() {
        add_action('init', [$this, 'unregister_post_tags']);
        add_action('admin_menu', [$this, 'remove_tags_admin_menu']);
        add_action('template_redirect', [$this, 'block_tag_archives']);
    }

    public function unregister_post_tags() {
        unregister_taxonomy_for_object_type('post_tag', 'post');
    }

    public function remove_tags_admin_menu() {
        remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
    }

    public function block_tag_archives() {
        if (is_tag()) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
        }
    }
        
}
