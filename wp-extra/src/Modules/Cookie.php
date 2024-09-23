<?php

namespace WPEXtra\Modules;

use WPEXtra\Core\ClassEXtra;

class Cookie {

	public function __construct() {
        add_action( 'wp_enqueue_scripts', [$this, 'cookie_enqueue_scripts'] );
        add_action('wp_footer', [$this, 'display_cookie_info']);
        add_action( 'init', [$this, 'display_cookie_notice']);
    }
    
    public function display_cookie_notice() {
        if ( isset( $_POST['ex-cookie-privacy-policy'] ) ) {
            wp_safe_redirect( get_privacy_policy_url() );
            exit;
        }
    }
    
    public function cookie_enqueue_scripts() {
        if ( !isset( $_COOKIE['cookie-accepted'] ) ) {
            wp_enqueue_style( 'cookie', plugins_url( '/assets/css/cookie.css', WPEX_FILE ) );
			wp_enqueue_script('cookie', plugins_url( '/assets/js/cookie.js', WPEX_FILE ), array(), time(), true );
        }
    }
    
    public function display_cookie_info() {
        $cookie_message = wp_extra_get_option( 'cookie_message', __('This site uses cookies to improve your online experience, allow you to share content on social media, measure traffic to this website and display customised ads based on your browsing activity.', 'wp-extra') );
        $cookie_info_button = wp_extra_get_option( 'cookie_button', __('Accept Cookies', 'wp-extra' ));
        $show_policy_privacy = wp_extra_get_option( 'cookie_privacy' );
        $background_color = wp_extra_get_option( 'cookie_bgcolor', '#ffffff' );
        $text_color = wp_extra_get_option( 'cookie_textcolor', '#666666' );
        $button_background_color = wp_extra_get_option( 'cookie_btnbgcolor', '#1e58b1' );
        $button_text_color = wp_extra_get_option( 'cookie_btntextcolor', '#ffffff' );
        $cookie_info_placemet = wp_extra_get_option( 'cookie_placement', 'bottom' );
        $cookie_expire_time = wp_extra_get_option( 'cookie_expire', '30' );
    ?>
    <div class="cookie-box cookie-hidden" style="<?php echo 'background-color: '.esc_attr( $background_color ).'; '.esc_attr( $cookie_info_placemet ).': 0' ?>" id="cookie-box">
        <form method="post" id="cookie-form"> 
            <div id="extra-cookie-info" style="<?php echo 'color: '.esc_attr( $text_color ) ?>"><?php echo esc_html($cookie_message); ?></div>
            <div id="cookie-notice-button">
                <?php if ( $show_policy_privacy ) { ?>
                <a href="#" name="ex-cookie-privacy-policy" class="button extra-cookie-privacy-policy" id="cookie-privacy-policy" style="<?php echo 'border: 1px solid '.esc_attr( $button_background_color ).';color: '.esc_attr( $button_background_color ) ?>">
                <?php esc_html_e( 'Privacy Policy' ) ?>
                </a>
                <?php } ?>
                <a href="#" name="ex-cookie-accept-button" class="button extra-cookie-accept-button" id="cookie-accept-button" style="<?php echo 'background-color: '.esc_attr( $button_background_color ).';color: '.esc_attr( $button_text_color )  ?>" data-expire="<?php echo esc_html( $cookie_expire_time ) ?>">
                <?php echo esc_html($cookie_info_button); ?>
                </a>
            </div>
        </form>
    </div>
    <?php
    }

}