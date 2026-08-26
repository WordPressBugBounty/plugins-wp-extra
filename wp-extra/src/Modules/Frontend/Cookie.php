<?php
namespace WPEXtra\Modules\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPEXtra\Helper;

class Cookie {

	public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'cookie_enqueue_scripts']);
        add_action('wp_footer', [$this, 'display_cookie_info']);
    }
    
    public function cookie_enqueue_scripts() {
        if (!is_admin()) {
            wp_enqueue_style('cookie', plugins_url('/assets/css/cookie.min.css', WPEX_FILE), [], defined('WPEX_VERSION') ? WPEX_VERSION : null);
            wp_enqueue_script('cookie', plugins_url('/assets/js/cookie.min.js', WPEX_FILE), [], defined('WPEX_VERSION') ? WPEX_VERSION : null, true);
        }
    }
    
    public function display_cookie_info() {
        if (is_admin()) {
            return;
        }

        $preset = Helper::get_option('cookie_preset', 'default');

        // Presets color mapping
        switch ($preset) {
            case 'dark_modern':
                $background_color        = '#1e293b';
                $text_color              = '#f8fafc';
                $button_background_color = '#6366f1';
                $button_text_color       = '#ffffff';
                break;
            case 'soft_warm':
                $background_color        = '#fef3c7';
                $text_color              = '#78350f';
                $button_background_color = '#d97706';
                $button_text_color       = '#ffffff';
                break;
            case 'custom':
                $background_color        = Helper::get_option('cookie_bgcolor', '#ffffff');
                $text_color              = Helper::get_option('cookie_textcolor', '#666666');
                $button_background_color = Helper::get_option('cookie_btnbgcolor', '#1e58b1');
                $button_text_color       = Helper::get_option('cookie_btntextcolor', '#ffffff');
                break;
            case 'default':
            default:
                $background_color        = '#ffffff';
                $text_color              = '#333333';
                $button_background_color = '#1e58b1';
                $button_text_color       = '#ffffff';
                break;
        }

        $cookie_message          = Helper::get_option('cookie_message', __('This site uses cookies to improve your online experience, allow you to share content on social media, measure traffic to this website and display customised ads based on your browsing activity.', 'wp-extra'));
        $cookie_info_button      = Helper::get_option('cookie_button', __('Accept Cookies', 'wp-extra'));
        $show_policy_privacy     = Helper::get_option('cookie_privacy');
        $cookie_info_placement   = Helper::get_option('cookie_placement', 'bottom');
        $cookie_expire_time      = Helper::get_option('cookie_expire', '30');
        $privacy_policy_url      = get_privacy_policy_url();
        $placement_css           = ($cookie_info_placement === 'top') ? 'top: 0;' : 'bottom: 0;';
        ?>
        <div class="cookie-box cookie-hidden" style="background-color: <?php echo esc_attr($background_color); ?>; <?php echo esc_attr($placement_css); ?>" id="cookie-box">
            <div id="cookie-form"> 
                <div id="extra-cookie-info" style="color: <?php echo esc_attr($text_color); ?>"><?php echo wp_kses_post($cookie_message); ?></div>
                <div id="cookie-notice-button">
                    <?php if ($show_policy_privacy && !empty($privacy_policy_url)): ?>
                    <a href="<?php echo esc_url($privacy_policy_url); ?>" class="button extra-cookie-privacy-policy" id="cookie-privacy-policy" style="border: 1px solid <?php echo esc_attr($button_background_color); ?>; color: <?php echo esc_attr($button_background_color); ?>">
                        <?php esc_html_e('Privacy Policy'); ?>
                    </a>
                    <?php endif; ?>
                    <a href="#" name="ex-cookie-accept-button" class="button extra-cookie-accept-button" id="cookie-accept-button" style="background-color: <?php echo esc_attr($button_background_color); ?>; color: <?php echo esc_attr($button_text_color); ?>" data-expire="<?php echo esc_attr($cookie_expire_time); ?>">
                        <?php echo esc_html($cookie_info_button); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

}