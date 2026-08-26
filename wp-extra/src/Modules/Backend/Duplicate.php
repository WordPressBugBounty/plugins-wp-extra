<?php
namespace WPEXtra\Modules\Backend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Helper;
use WPEXtra\Base;

class Duplicate extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'duplicate',
		'duplicate_tax',
	];
    
    public function duplicate() {
        add_action('admin_action_duplicate_as_draft', [$this, 'duplicate_as_draft']);
        add_filter('post_row_actions', [$this, 'duplicate_post_link'], 10, 2);
        add_filter('page_row_actions', [$this, 'duplicate_post_link'], 10, 2);
    }
    
    public function duplicate_tax() {
        add_filter('tag_row_actions', [$this, 'add_tag_row_action'], 10, 2);
        add_action('admin_post_duplicate-term', [$this, 'process_duplicate_term']);
        add_action('admin_notices', [$this, 'add_admin_notice']);
	}
    
    public function duplicate_as_draft() {
        $nonce = sanitize_text_field($_REQUEST['nonce'] ?? '');
        $post_id = intval($_REQUEST['post'] ?? 0);
        if (empty($nonce) || empty($post_id)) {
            wp_die(esc_html__('Invalid request status.', 'wp-extra'));
        }
        if (!wp_verify_nonce($nonce, 'duplicate-page-' . $post_id)) {
            wp_die(esc_html__('Security check failed.', 'wp-extra'));
        }
        $post = get_post($post_id);
        if (empty($post)) {
            wp_die(esc_html__('No posts found.', 'wp-extra'));
        }
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(esc_html__('Unauthorized to duplicate this item.', 'wp-extra'));
        }

        $post_status = current_user_can('publish_posts') ? 'draft' : 'pending';
        $this->duplicate_edit_post($post_id, $post_status);
    }

    public function duplicate_edit_post($post_id, $post_status = 'draft') {
        $post = get_post($post_id);
        if (empty($post)) {
            wp_die(esc_html__('No posts found.', 'wp-extra'));
        }
        $current_user = wp_get_current_user();
        $args = [
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
            'post_author'    => $current_user->ID,
            'post_content'   => wp_slash($post->post_content),
            'post_excerpt'   => wp_slash($post->post_excerpt),
            'post_parent'    => $post->post_parent,
            'post_password'  => $post->post_password,
            'post_status'    => $post_status,
            'post_title'     => wp_slash($post->post_title . ' ' . __('(Copy)', 'wp-extra')),
            'post_type'      => $post->post_type,
            'to_ping'        => $post->to_ping,
            'menu_order'     => $post->menu_order,
        ];
        $new_post_id = wp_insert_post($args);
        if (is_wp_error($new_post_id)) {
            wp_die(esc_html($new_post_id->get_error_message()));
        }

        // Copy taxonomies
        $taxonomies = get_object_taxonomies($post->post_type);
        if (!empty($taxonomies) && is_array($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
                if (!empty($post_terms) && !is_wp_error($post_terms)) {
                    wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                }
            }
        }

        // Copy post meta (excluding internal locks and edits)
        $exclude_meta = ['_edit_lock', '_edit_last'];
        $post_meta = get_post_custom($post_id);
        if (!empty($post_meta) && is_array($post_meta)) {
            foreach ($post_meta as $meta_key => $meta_values) {
                if (in_array($meta_key, $exclude_meta, true)) {
                    continue;
                }
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
        }

        // Copy post format if supported
        if (current_theme_supports('post-formats') && post_type_supports($post->post_type, 'post-formats')) {
            $format = get_post_format($post_id);
            if ($format) {
                set_post_format($new_post_id, $format);
            }
        }

        wp_safe_redirect(esc_url_raw(admin_url('post.php?action=edit&post=' . intval($new_post_id))));
        exit;
    }

    public function duplicate_post_link($actions, $post) {
        if ($post->post_type === 'acf-field-group') {
            return $actions;
        }
        $duplicate_types = (array) Helper::get_option('duplicate', []);
        if (current_user_can('edit_post', $post->ID) && in_array($post->post_type, $duplicate_types, true)) {
            $actions['duplicate'] = '<a href="' . esc_url(admin_url('admin.php?action=duplicate_as_draft&post=' . intval($post->ID) . '&nonce=' . wp_create_nonce('duplicate-page-' . intval($post->ID)))) . '">' . __('Copy', 'wp-extra') . '</a>';
        }
        return $actions;
    }

	public function add_admin_notice() {
		if (isset($_GET['duplicated']) && $_GET['duplicated'] === 'true') {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Term duplicated successfully.', 'wp-extra') . '</p></div>';
		}
	}
    
	public function process_duplicate_term() {
		check_admin_referer('duplicate-term');
		$term_id  = (int) filter_input(INPUT_GET, 'term_id');
		$taxonomy = sanitize_key(filter_input(INPUT_GET, 'taxonomy'));
		$tax_obj  = get_taxonomy($taxonomy);
		if (!$tax_obj || !current_user_can($tax_obj->cap->edit_terms)) {
			wp_die(esc_html__('Sorry, you are not allowed to edit this taxonomy.', 'wp-extra'));
		}
		$new_term = $this->duplicate_term($term_id, $taxonomy);
		if (is_wp_error($new_term)) {
			wp_die(esc_html($new_term->get_error_message()));
		}
		$url = wp_get_referer() ?: admin_url("edit-tags.php?taxonomy={$taxonomy}");
		$url = add_query_arg('duplicated', 'true', $url);
		wp_safe_redirect($url);
		exit;
	}

	public function add_tag_row_action($actions, $tag) {
        $duplicate_tax = (array) Helper::get_option('duplicate_tax', []);
        $tax_obj = get_taxonomy($tag->taxonomy);
        if ($tax_obj && current_user_can($tax_obj->cap->edit_terms) && in_array($tag->taxonomy, $duplicate_tax, true)) {
            $actions['duplicate'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($this->get_duplicate_term_url($tag)),
                __('Copy', 'wp-extra')
            );
        }
		return $actions;
	}

	private function get_duplicate_term_url($tag) {
		return wp_nonce_url(add_query_arg([
			'term_id'  => $tag->term_id,
			'taxonomy' => $tag->taxonomy,
			'action'   => 'duplicate-term',
		], admin_url('admin-post.php')), 'duplicate-term');
	}

	private function get_new_term_name($name, $taxonomy, $parent) {
        $i = 1;
        do {
            $new_name = sprintf(__('%1$s (%2$d)', 'wp-extra'), $name, $i++);
        } while (term_exists($new_name, $taxonomy, $parent));
        return $new_name;
    }

	private function duplicate_term($term_id, $taxonomy) {
		$term = get_term($term_id, $taxonomy);
		if (is_wp_error($term) || !$term) {
			return $term ?: new \WP_Error('invalid_term', __('Term not found.', 'wp-extra'));
		}
		$new_term = wp_insert_term($this->get_new_term_name($term->name, $term->taxonomy, $term->parent), $term->taxonomy, [
			'description' => $term->description,
			'parent'      => $term->parent,
		]);
		if (is_wp_error($new_term)) {
			return $new_term;
		}

        $new_term_id = (int) $new_term['term_id'];

        // Duplicate term meta
        $term_meta = get_term_meta($term_id);
        if (!empty($term_meta) && is_array($term_meta)) {
            foreach ($term_meta as $meta_key => $meta_values) {
                foreach ($meta_values as $meta_value) {
                    add_term_meta($new_term_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
        }

        // Fast & memory-safe batch assignment of posts to the new term
        $this->duplicate_posts_by_taxonomy($term_id, $new_term_id, $taxonomy);

		return $new_term;
	}

    private function duplicate_posts_by_taxonomy($old_term_id, $new_term_id, $taxonomy) {
        $object_ids = get_objects_in_term((int)$old_term_id, $taxonomy);
        if (!empty($object_ids) && !is_wp_error($object_ids)) {
            foreach (array_chunk($object_ids, 200) as $chunk) {
                foreach ($chunk as $object_id) {
                    wp_set_object_terms((int)$object_id, (int)$new_term_id, $taxonomy, true);
                }
            }
        }
    }
}