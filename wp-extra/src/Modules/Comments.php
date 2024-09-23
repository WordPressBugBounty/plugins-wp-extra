<?php

namespace WPEXtra\Modules;

use WPEXtra\Core\ClassEXtra;

class Comments {

	public function __construct() {
        if (wp_extra_get_option('cm_antispam')) {
            add_action('init', [$this, 'antispam_blacklist']);
            if (!wp_extra_get_option('cm_traffic')) {
                add_filter('preprocess_comment', [$this, 'antispam_comment']);
            }
        }
        if(wp_extra_get_option('disable_comments')) {
			add_action('widgets_init', [$this, 'disableRecentComments']);
			add_action('template_redirect', [$this, 'disableCommentsFeed'], 9);
			add_action('template_redirect', [$this, 'removeCommentAdminBar']); 
			add_action('admin_init', [$this, 'removeCommentAdminBar']);
			add_action('wp_loaded', [$this, 'loadedDisableComments']);
		}
        if(wp_extra_get_option('cm_media')) {
            add_filter('comments_open', array($this, 'filter_media_comment_status'), 10 , 2);
            add_filter('manage_media_columns', array($this, 'hide_media_comments_column'));
        }
    }

    public function antispam_blacklist()
    {
        if (get_option('disallowed_keys') === false) {
            $github_url = 'https://raw.githubusercontent.com/splorp/wordpress-comment-blacklist/master/blacklist.txt';
            $response = wp_remote_get($github_url);

            if (!is_wp_error($response) && $response['response']['code'] === 200) {
                $blacklist_content = wp_remote_retrieve_body($response);
                update_option('disallowed_keys', $blacklist_content);
                update_option('comment_max_links', 0);
            }
        } else {
            remove_filter('comment_text', 'make_clickable', 9);
        }
    }
    
    public function antispam_comment($comment_data) {
        $comment_content = $comment_data['comment_content'];
        if (strpos($comment_content, 'http://') !== false || strpos($comment_content, 'https://') !== false) {
            wp_die(esc_html__('This comment is already marked as spam.'));
            exit;
        }
        return $comment_data;
    }
    
    public function disableRecentComments() {
		unregister_widget('WP_Widget_Recent_Comments');
		add_filter('show_recent_comments_widget_style', '__return_false');
	}

	public function disableCommentsFeed() {
		if(is_comment_feed()) {
			wp_die(esc_html__('Comments are closed.'), '', array('response' => 403));
		}
	}

	public function removeCommentAdminBar() {
		if(is_admin_bar_showing()) {
			remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		}
	}

	public function loadedDisableComments() {
		$post_types = get_post_types(array('public' => true), 'names');
		if(!empty($post_types)) {
			foreach($post_types as $post_type) {
				if(post_type_supports($post_type, 'comments')) {
					remove_post_type_support($post_type, 'comments');
					remove_post_type_support($post_type, 'trackbacks');
				}
			}
		}

		add_filter('comments_array', function() { return array(); }, 20, 2);
		add_filter('comments_open', function() { return false; }, 20, 2);
		add_filter('pings_open', function() { return false; }, 20, 2);

		if(is_admin()) {
			add_action('admin_menu', [$this, 'removeCommentsMenu'], 9999);
			add_action('admin_print_styles-index.php', [$this, 'hideDashboardComments']);
			add_action('admin_print_styles-profile.php', [$this, 'hideProfileComments']);
			add_action('wp_dashboard_setup', [$this, 'removeRecentCommentsMeta']);
			add_filter('pre_option_default_pingback_flag', '__return_zero');
		}
		else {
			add_filter('comments_template', [$this, 'BlankCommentsTemplate'], 20);
			wp_deregister_script('comment-reply');
			add_filter('feed_links_show_comments_feed', '__return_false');
		}
	}

	public function removeCommentsMenu() {
		global $pagenow;
		remove_menu_page('edit-comments.php');
		remove_submenu_page('options-general.php', 'options-discussion.php');
		if($pagenow == 'comment.php' || $pagenow == 'edit-comments.php') {
			wp_die(esc_html__('Comments are closed.'), '', array('response' => 403));
		}
		if($pagenow == 'options-discussion.php') {
			wp_die(esc_html__('Comments are closed.'), '', array('response' => 403));
		}
	}

	public function hideDashboardComments(){
		echo "<style>#dashboard_right_now .comment-count, #dashboard_right_now .comment-mod-count, #latest-comments, #welcome-panel .welcome-comments {display: none !important;}</style>";
	}

	public function hideProfileComments(){
		echo "<style>.user-comment-shortcuts-wrap {display: none !important;}</style>";
	}

	public function removeRecentCommentsMeta(){
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	}

	public function BlankCommentsTemplate() {
		return dirname(__FILE__) . '/comments-template.php';
	}
    
    public function filter_media_comment_status( $open, $post_id ) {
        $post = get_post( $post_id );
        if( $post->post_type == 'attachment' ) {
            return false;
        }
        return $open;
    }
    
    public function hide_media_comments_column( $columns ) {
        if ( isset( $columns['comments'] ) ) {
            unset( $columns['comments'] );
        }
        return $columns;
    }

}