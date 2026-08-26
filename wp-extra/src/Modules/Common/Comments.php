<?php
namespace WPEXtra\Modules\Common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Settings;
use WPEXtra\Helper;
use WPEXtra\Base;

class Comments extends Base {
    
    public function __construct() {
		parent::__construct();
    }
    
	protected $features = [
		'cm_antispam',
		'disable_comments',
		'cm_media',
	];
    
    public function cm_antispam() {
        add_action('init', [$this, 'antispam_blacklist']);
        add_filter('preprocess_comment', [$this, 'antispam_comment']);
    }
    
    public function disable_comments() {
        add_action('widgets_init', [$this, 'disableRecentComments']);
        add_action('template_redirect', [$this, 'disableCommentsFeed'], 9);
        add_action('template_redirect', [$this, 'removeCommentAdminBar']); 
        add_action('admin_init', [$this, 'removeCommentAdminBar']);
        add_action('wp_loaded', [$this, 'loadedDisableComments']);
    }
    
    public function cm_media() {
        add_filter('comments_open', [$this, 'filter_media_comment_status'], 10, 2);
        add_filter('manage_media_columns', [$this, 'hide_media_comments_column']);
    }

    public function antispam_blacklist() {
        remove_filter('comment_text', 'make_clickable', 9);
    }
    
    public function antispam_comment($comment_data) {
        $comment_content = $comment_data['comment_content'] ?? '';
        if (strpos($comment_content, 'http://') !== false || strpos($comment_content, 'https://') !== false) {
            wp_die(esc_html__('Comments containing links or URLs are not allowed.', 'wp-extra'), esc_html__('Comment Blocked', 'wp-extra'), ['response' => 403, 'back_link' => true]);
        }
        return $comment_data;
    }
    
    public function disableRecentComments() {
		unregister_widget('WP_Widget_Recent_Comments');
		add_filter('show_recent_comments_widget_style', '__return_false');
	}

	public function disableCommentsFeed() {
		if (is_comment_feed()) {
			wp_die(esc_html__('Comments are closed.', 'wp-extra'), '', ['response' => 403]);
		}
	}

	public function removeCommentAdminBar() {
		if (is_admin_bar_showing()) {
			remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
		}
	}

	public function loadedDisableComments() {
		$post_types = get_post_types(['public' => true], 'names');
		if (!empty($post_types)) {
			foreach ($post_types as $post_type) {
				if (post_type_supports($post_type, 'comments')) {
					remove_post_type_support($post_type, 'comments');
					remove_post_type_support($post_type, 'trackbacks');
				}
			}
		}

		add_filter('comments_array', '__return_empty_array', 20);
		add_filter('comments_open', '__return_false', 20);
		add_filter('pings_open', '__return_false', 20);

		if (is_admin()) {
			add_action('admin_menu', [$this, 'removeCommentsMenu'], 9999);
			add_action('admin_print_styles-index.php', [$this, 'hideDashboardComments']);
			add_action('admin_print_styles-profile.php', [$this, 'hideProfileComments']);
			add_action('wp_dashboard_setup', [$this, 'removeRecentCommentsMeta']);
			add_filter('pre_option_default_pingback_flag', '__return_zero');
		} else {
			add_filter('comments_template', [$this, 'BlankCommentsTemplate'], 20);
			wp_deregister_script('comment-reply');
			add_filter('feed_links_show_comments_feed', '__return_false');
		}
	}

	public function removeCommentsMenu() {
		global $pagenow;
		remove_menu_page('edit-comments.php');
		remove_submenu_page('options-general.php', 'options-discussion.php');
		if ($pagenow === 'comment.php' || $pagenow === 'edit-comments.php' || $pagenow === 'options-discussion.php') {
			wp_die(esc_html__('Comments are closed.', 'wp-extra'), '', ['response' => 403]);
		}
	}

	public function hideDashboardComments() {
		echo '<style>#dashboard_right_now .comment-count,#dashboard_right_now .comment-mod-count,#latest-comments,#welcome-panel .welcome-comments{display:none!important;}</style>';
	}

	public function hideProfileComments() {
		echo '<style>.user-comment-shortcuts-wrap{display:none!important;}</style>';
	}

	public function removeRecentCommentsMeta() {
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	}

	public function BlankCommentsTemplate() {
		return __DIR__ . '/empty-comments.php';
	}
    
    public function filter_media_comment_status($open, $post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'attachment') {
            return false;
        }
        return $open;
    }
    
    public function hide_media_comments_column($columns) {
        if (isset($columns['comments'])) {
            unset($columns['comments']);
        }
        return $columns;
    }

}