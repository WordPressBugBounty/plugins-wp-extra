<?php

namespace WPEXtra\Core;

class ClassEXtra {

    private static $instance;

    public static function instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }
    
    public static function minifyCSS($css){
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        return $css;
    }
    
    public static function isPro() {
        return defined('WPEXPRO');
    }
    
    public static function isProClass() {
        return isPro() ? 'active' : 'inactive';
    }
    
}