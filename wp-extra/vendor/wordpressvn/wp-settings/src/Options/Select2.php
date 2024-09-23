<?php

namespace WPVNTeam\WPSettings\Options;

use WPVNTeam\WPSettings\Enqueuer;

class Select2 extends OptionAbstract
{
    public $view = 'select2';
    
    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);
        
        parent::__construct($section, $args);
    }

    public function get_name_attribute()
    {
        $name = parent::get_name_attribute();

        return "{$name}[]";
    }

    public function sanitize($value)
    {
        return (array) $value;
    }

    public function enqueue()
    {
        Enqueuer::add('wps-select2', function () {
            $select2_assets = apply_filters('wps_select2_assets', [
                'js' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js',
                'css' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css'
            ]);

            wp_enqueue_style('wp-select2', $select2_assets['css']);
            wp_enqueue_script('wp-select2', $select2_assets['js'], ['jquery']);

            wp_add_inline_script('wp-select2', 'jQuery(function($){$(\'.select2\').select2();})'); 
            ?>
            <style>
            .select2.select2-container {
              width: 100% !important;
            }

            .select2.select2-container .select2-selection {
              border: 1px solid #ccc;
              -webkit-border-radius: 3px;
              -moz-border-radius: 3px;
              border-radius: 3px;
              height: 34px;
              margin-bottom: 15px;
              outline: none !important;
              transition: all .15s ease-in-out;
            }

            .select2.select2-container .select2-selection .select2-selection__rendered {
              color: #333;
              line-height: 32px;
              padding-right: 33px;
            }

            .select2.select2-container .select2-selection .select2-selection__arrow {
              background: #f8f8f8;
              border-left: 1px solid #ccc;
              -webkit-border-radius: 0 3px 3px 0;
              -moz-border-radius: 0 3px 3px 0;
              border-radius: 0 3px 3px 0;
              height: 32px;
              width: 33px;
            }

            .select2.select2-container.select2-container--open .select2-selection.select2-selection--single {
              background: #f8f8f8;
            }

            .select2.select2-container.select2-container--open .select2-selection.select2-selection--single .select2-selection__arrow {
              -webkit-border-radius: 0 3px 0 0;
              -moz-border-radius: 0 3px 0 0;
              border-radius: 0 3px 0 0;
            }

            .select2.select2-container.select2-container--open .select2-selection.select2-selection--multiple {
              border: 1px solid #34495e;
            }

            .select2.select2-container .select2-selection--multiple {
              height: auto;
              min-height: 34px;
            }

            .select2.select2-container .select2-selection--multiple .select2-search--inline .select2-search__field {
              margin-top: 0;
              height: 32px;
            }

            .select2.select2-container .select2-selection--multiple .select2-selection__rendered {
              padding: 0 4px;
              line-height: 29px;
            }

            .select2.select2-container .select2-selection--multiple .select2-selection__choice {
              background-color: #f8f8f8;
              border: 1px solid #ccc;
              -webkit-border-radius: 3px;
              -moz-border-radius: 3px;
              border-radius: 3px;
              margin: 4px 4px 0 0;
              padding: 0 6px 0 22px;
              height: 24px;
              line-height: 24px;
              font-size: 12px;
              position: relative;
            }

            .select2.select2-container .select2-selection--multiple .select2-selection__choice .select2-selection__choice__remove {
              color: #e74c3c;
              font-size: 18px;
            }

            .select2-container .select2-dropdown {
              background: transparent;
              border: none;
              margin-top: -5px;
            }

            .select2-container .select2-dropdown .select2-search {
              padding: 0;
            }

            .select2-container .select2-dropdown .select2-search input {
              outline: none !important;
              border: 1px solid #34495e !important;
              border-bottom: none !important;
              padding: 4px 6px !important;
            }

            .select2-container .select2-dropdown .select2-results {
              padding: 0;
            }

            .select2-container .select2-dropdown .select2-results ul {
              background: #fff;
              border: 1px solid #34495e;
            }

            .select2-container .select2-dropdown .select2-results ul .select2-results__option--highlighted[aria-selected] {
              background-color: #3498db;
            }
            </style><?php 
        });
    }
}
