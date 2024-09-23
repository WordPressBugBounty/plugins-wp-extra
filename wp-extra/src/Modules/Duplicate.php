<?php

namespace WPEXtra\Modules;

class Duplicate {

	public function __construct() {
        if(wp_extra_get_option('duplicate')) {
            add_action('admin_action_duplicate_as_draft', [$this, 'duplicate_as_draft']);
            add_filter('post_row_actions', [$this, 'duplicate_post_link'], 10, 2);
            add_filter('page_row_actions', [$this, 'duplicate_post_link'], 10, 2);
        }
        
        if(wp_extra_get_option('duplicate_tax')) {
            add_action( 'admin_head', [ $this, 'add_row_actions' ] );
            add_action( 'admin_post_duplicate-term', [ $this, 'process_duplicate_term' ] );
            add_action( 'admin_notices', [ $this, 'add_admin_notice' ] );
        }
	}
    
    public function duplicate_as_draft()
    {
        $nonce = sanitize_text_field($_REQUEST['nonce'] ?? '');
        $post_id = intval($_REQUEST['post'] ?? 0);
        
        if (empty($nonce) || empty($post_id)) {
            wp_die(esc_html__('Invalid request status.'));
        }

        if (!wp_verify_nonce($nonce, 'duplicate-page-'.$post_id)) {
            wp_die(esc_html__('Security check failed.'));
        }

        $current_user_id = get_current_user_id();
        $post = get_post($post_id);

        if (current_user_can('manage_options') || current_user_can('edit_others_posts') ||
            (current_user_can('edit_posts') && $current_user_id == $post->post_author)) {
            $this->duplicate_edit_post($post_id);
        } elseif (current_user_can('contributor') && $current_user_id == $post->post_author) {
            $this->duplicate_edit_post($post_id, 'pending');
        } else {
            wp_die(esc_html__('Unauthorized to modify setting due to capability.'));
        }
    }

    public function duplicate_edit_post($post_id, $post_status = 'draft')
    {
        if (!isset($_REQUEST['post'])) {
            wp_die(esc_html__('Sorry, the post could not be created.'));
        }

        $post = get_post($post_id);
        
        if (empty($post)) {
            wp_die(esc_html__('No posts found.'));
        }

        $current_user = wp_get_current_user();
        $new_post_author = $current_user->ID;

        $args = array(
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'post_author' => $new_post_author,
            'post_content' => wp_slash($post->post_content),
            'post_excerpt' => $post->post_excerpt,
            'post_parent' => $post->post_parent,
            'post_password' => $post->post_password,
            'post_status' => $post_status,
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'to_ping' => $post->to_ping,
            'menu_order' => $post->menu_order,
        );

        $new_post_id = wp_insert_post($args);

        if (is_wp_error($new_post_id)) {
            wp_die($new_post_id->get_error_message());
        }

        $taxonomies = array_map('sanitize_text_field', get_object_taxonomies($post->post_type));

        if (!empty($taxonomies) && is_array($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
            }
        }

        $post_meta_keys = get_post_custom_keys($post_id);

        if (!empty($post_meta_keys)) {
            foreach ($post_meta_keys as $meta_key) {
                $meta_values = get_post_custom_values($meta_key, $post_id);

                foreach ($meta_values as $meta_value) {
                    $meta_value = maybe_unserialize($meta_value);
                    update_post_meta($new_post_id, $meta_key, wp_slash($meta_value));
                }
            }
        }
        if ($post->post_type == 'page'){
            wp_redirect(esc_url_raw(admin_url('edit.php?post_type='.$post->post_type))); 
        } else {
            wp_redirect(esc_url_raw(admin_url('post.php?action=edit&post='.$new_post_id))); 
        }
        exit;
    }

    public function duplicate_post_link($actions, $post)
    {
        if ($post->post_type == 'acf-field-group') {
            return $actions;
        }

        $duplicate_type = wp_extra_get_option('duplicate');

        if (current_user_can('edit_posts') && in_array($post->post_type, $duplicate_type)) {
            $actions['duplicate'] = isset($post) ? '<a href="admin.php?action=duplicate_as_draft&amp;post='.intval($post->ID).'&amp;nonce='.wp_create_nonce( 'duplicate-page-'.intval($post->ID) ).'">'.__('Duplicate', 'wp-extra').'</a>' : '';
        }

        return $actions;
    }

	public function add_row_actions() {
		$screen = get_current_screen();
		if ( $screen->base !== 'edit-tags' ) {
			return;
		}
		foreach ( get_taxonomies() as $taxonomy ) {
			add_filter( "{$taxonomy}_row_actions", [ $this, 'add_tag_row_action' ], 10, 2 );
		}
	}

	public function add_admin_notice() {
		if ( isset( $_GET['duplicated'] ) && $_GET['duplicated'] === 'true' ) {
			echo '<div class="notice notice-success"><p>' . __( 'Success!' ) . '</p></div>';
		}
	}
    
	public function process_duplicate_term() {
		check_admin_referer( 'duplicate-term' );

		$term_id  = (int) filter_input( INPUT_GET, 'term_id' );
		$taxonomy = filter_input( INPUT_GET, 'taxonomy' );

		$term = $this->duplicate_term( $term_id, $taxonomy );

		if ( is_wp_error( $term ) ) {
			wp_die( esc_html__($term->get_error_message()) );
		}

		$url = wp_get_referer();
		$url = add_query_arg( 'duplicated', 'true', $url );
		wp_safe_redirect( $url );
		die();
	}

	public function add_tag_row_action( $actions, $tag ) {
        $duplicate_tax = wp_extra_get_option('duplicate_tax');
        if (current_user_can('edit_posts') && in_array($tag->taxonomy, $duplicate_tax)) {
            $actions['duplicate'] = sprintf( "<a href=\"%s\">%s</a>", esc_url( $this->get_duplicate_term_url( $tag ) ), __('Duplicate', 'wp-extra') );
        }
		return $actions;
	}

	private function get_duplicate_term_url( $tag ) {
		return wp_nonce_url( add_query_arg( [
			'term_id'  => $tag->term_id,
			'taxonomy' => $tag->taxonomy,
			'action'   => 'duplicate-term',
		], admin_url( 'admin-post.php' ) ), 'duplicate-term' );
	}

	private function get_new_term_name( $name, $taxonomy, $parent ) {
        $i = 1;
        do {
            // Translators: %1$s is a placeholder for the name, %2$d is a placeholder for the index.
            $new_name = sprintf( __( "%1\$s (%2\$d)"), $name, $i++ );
        } while ( term_exists( $new_name, $taxonomy, $parent ) );
        return $new_name;
    }

	private function duplicate_term( $term_id, $taxonomy ) {
		global $wpdb;
		$term = get_term( $term_id, $taxonomy );
		if ( is_wp_error( $term ) ) {
			return $term;
		}
        $post_type = get_taxonomy( $taxonomy )->object_type;
		$new_term = wp_insert_term( $this->get_new_term_name( $term->name, $term->taxonomy, $term->parent ), $term->taxonomy, array(
			'description' => $term->description,
			'parent'      => $term->parent,
		) );

		if ( is_wp_error( $new_term ) ) {
			return $new_term;
		}
        $set_term_id = $this->duplicate_posts_by_taxonomy( $term_id, $new_term['term_id'], $post_type );
		return $set_term_id;
	}

    private function duplicate_posts_by_taxonomy( $old_term_id, $new_term_id, $post_type ) {
        $args = array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => get_term( $old_term_id )->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $old_term_id,
                ),
            ),
        );

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                wp_set_post_terms( get_the_ID(), array( (int) $new_term_id ), get_term( $new_term_id )->taxonomy, true );
            }
            wp_reset_postdata();
        }
    }
}