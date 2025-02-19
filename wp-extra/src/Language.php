<?php
namespace WPEXtra;

class Language {
    
    public function __construct() {
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain( 'wp-extra', false, dirname( plugin_basename( WPEX_FILE ) ) . '/languages/' );
    }
    
}