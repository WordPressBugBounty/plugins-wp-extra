<?php
namespace WPEXtra;

class Core {
    
    public function __construct() {
		add_filter('plugin_action_links_' . plugin_basename(WPEX_FILE), [$this, 'add_plugin_action_links']);
		add_filter('plugin_row_meta', [$this, 'add_plugin_meta_links'], 10, 2 );
    }
    
	public function add_plugin_action_links( $links ) {
		$links[]     = '<a href="' . esc_url( admin_url( 'admin.php?page=wp-extra' ) ) . '">' . __( 'Settings' ) . '</a>';
		$upgradeable = apply_filters( 'wpextra_upgradeable', true );
		if ( $upgradeable ) {
			$links[] = '<a href="https://wpvnteam.com/wp-extra/pricing/" style="color: #39b54a; font-weight: bold" target="_blank">' . __( 'Upgrade', 'wp-extra' ) . '</a>';
		}
		return $links;
	}
        
	public function add_plugin_meta_links( $meta, $file ) {
		if ( $file !== plugin_basename( WPEX_FILE ) ) {
			return $meta;
		}
        $upgradeable = apply_filters( 'wpextra_upgradeable', true );
        $meta[] = '<a href="https://wpvnteam.com/wp-extra/doc/" target="_blank">' . __( 'Documentation' ) . '</a>';
		if ( !$upgradeable ) {
            $meta[] = '<a href="https://wpvnteam.com/support/" target="_blank">' . __( 'Support' ) . '</a>';
        } else {
            $meta[] = '<a href="https://wpvnteam.com/donate/" target="_blank">' . __( 'Donate', 'wp-extra' ) . '</a>';
            $meta[] = '<a href="https://wordpress.org/support/plugin/wp-extra/reviews/?filter=5" target="_blank" title="' . esc_html__( 'Rate WP EXtra on WordPress.org', 'wp-extra' ) . '" style="color: #ffb900">'
                . str_repeat( '<span class="dashicons dashicons-star-filled" style="font-size:13px;width:13px;height:13px;line-height:1.8;"></span>', 5 )
                . '</a>';
        }
		return $meta;
	}
    
}