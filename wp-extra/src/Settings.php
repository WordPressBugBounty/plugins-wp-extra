<?php

namespace WPEXtra;

use WPEXtra\Core\ClassEXtra;
use WPVNTeam\WPSettings\WPSettings;
use WPEXtra\WPSettings\Module;
use WPEXtra\WPSettings\SMTP;
use WPEXtra\WPSettings\Import;
use WPEXtra\WPSettings\Widget;
use WPEXtra\WPSettings\Restore;
use WPEXtra\WPSettings\Export;

class Settings {
    public function __construct() {
        add_filter('wp_settings_option_type_map', function($options){
            $options['module'] = Module::class;
            $options['smtp'] = SMTP::class;
            $options['import'] = Import::class;
            $options['widget'] = Widget::class;
            $options['restore'] = Restore::class;
            $options['export'] = Export::class;
            return $options;
        });
        add_action('admin_menu', [$this, 'register'], 10);
    }

    public function register() {
        $settings = new WPSettings(__('WP EXtra'));
        $settings->set_capability('manage_options');
        $settings->set_menu_icon('dashicons-superhero-alt');
        $settings->set_menu_position(80);
        $settings->set_version(WPEX_VERSION);
        $settings->set_plugin_data(WPEX_FILE);
        
        $add_days = (new \DateTime())->diff(new \DateTime('2019-01-18'))->days;
        $notice_message = sprintf(
            /* translators: 1. days; 2. link to donate; 3. link to review */
            __( 'The plugin developer has dedicated <strong>%1$s</strong> days to this project. If you like it, you can support the author with <a href="%2$s" target="_blank">a beer 🍻 / coffee ☕️</a> ! Please <a href="%3$s" target="_blank">rate us on WordPress.org</a>', 'wp-extra' ),
            $add_days,
            'https://wpvnteam.com/donate/',
            'https://wordpress.org/support/plugin/wp-extra/reviews/?filter=5#new-post'
        );
        $plugin_message = "<p>" . esc_html__('Like this plugin? Check out our other WordPress products:', 'wp-extra') . "</p><a class='thickbox open-plugin-details-modal' href='".esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=ux-flat&from=import&TB_iframe=true&width=800&height=550'))."'>UX Flat</a> - ".esc_html__('Create new elements for Flatsome', 'wp-extra');

        $sidebar_items = [
            '📝 ' . __('Write a review for WP EXtra', 'wp-extra') => $notice_message,
            '🔥 ' . __('Our WordPress Products', 'wp-extra') => $plugin_message,
        ];
        if (!ClassEXtra::isPro()) {
            $upgrade_message = "<p>" . esc_html__('Please upgrade to the PRO plan to unlock more awesome features.', 'wp-extra') . "</p><a class='button' target='_blank' href='https://wpvnteam.com/wp-extra/pricing/'>" . esc_html__('Get WP EXtra PRO now', 'wp-extra') . "</a>";
            $sidebar_items['🚀 ' . __('Upgrade to WP EXtra PRO', 'wp-extra')] = $upgrade_message;
        }
        $settings->set_sidebar($sidebar_items);
        
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M10.5 4v4h3V4H15v4h1.5a1 1 0 011 1v4l-3 4v2a1 1 0 01-1 1h-3a1 1 0 01-1-1v-2l-3-4V9a1 1 0 011-1H9V4h1.5zm.5 12.5v2h2v-2l3-4v-3H8v3l3 4z"></path></svg>'.__('Modules'));
        $section = $tab->add_section(__('Modules'), ['description' => __('The module operates independently. Please enable it as needed.', 'wp-extra')]);
        $section->add_option('module', [
            'name' => 'modules',
            'options' => [
                'dashboard'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M18 5.5H6a.5.5 0 00-.5.5v3h13V6a.5.5 0 00-.5-.5zm.5 5H10v8h8a.5.5 0 00.5-.5v-7.5zm-10 0h-3V18a.5.5 0 00.5.5h2.5v-8zM6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>'.__('Dashboard'),
				'posts'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m7.3 9.7 1.4 1.4c.2-.2.3-.3.4-.5 0 0 0-.1.1-.1.3-.5.4-1.1.3-1.6L12 7 9 4 7.2 6.5c-.6-.1-1.1 0-1.6.3 0 0-.1 0-.1.1-.3.1-.4.2-.6.4l1.4 1.4L4 11v1h1l2.3-2.3zM4 20h9v-1.5H4V20zm0-5.5V16h16v-1.5H4z"></path></svg>'.__( 'Posts' ),
				'duplicate'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M5 4.5h11a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5H5a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5ZM3 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm17 3v10.75c0 .69-.56 1.25-1.25 1.25H6v1.5h12.75a2.75 2.75 0 0 0 2.75-2.75V8H20Z"></path></svg>'.__( 'Duplicate', 'wp-extra' ),
				'media'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m7 6.5 4 2.5-4 2.5z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="m5 3c-1.10457 0-2 .89543-2 2v14c0 1.1046.89543 2 2 2h14c1.1046 0 2-.8954 2-2v-14c0-1.10457-.8954-2-2-2zm14 1.5h-14c-.27614 0-.5.22386-.5.5v10.7072l3.62953-2.6465c.25108-.1831.58905-.1924.84981-.0234l2.92666 1.8969 3.5712-3.4719c.2911-.2831.7545-.2831 1.0456 0l2.9772 2.8945v-9.3568c0-.27614-.2239-.5-.5-.5zm-14.5 14.5v-1.4364l4.09643-2.987 2.99567 1.9417c.2936.1903.6798.1523.9307-.0917l3.4772-3.3806 3.4772 3.3806.0228-.0234v2.5968c0 .2761-.2239.5-.5.5h-14c-.27614 0-.5-.2239-.5-.5z"></path></svg>'.__( 'Media' ),
				'admins'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M15.5 9.5a1 1 0 100-2 1 1 0 000 2zm0 1.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm-2.25 6v-2a2.75 2.75 0 00-2.75-2.75h-4A2.75 2.75 0 003.75 15v2h1.5v-2c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v2h1.5zm7-2v2h-1.5v-2c0-.69-.56-1.25-1.25-1.25H15v-1.5h2.5A2.75 2.75 0 0120.25 15zM9.5 8.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" fill-rule="evenodd"></path></svg>'.__('Permission', 'wp-extra'),
				'logins'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4 20h8v-1.5H4V20zM18.9 3.5c-.6-.6-1.5-.6-2.1 0l-7.2 7.2c-.4-.1-.7 0-1.1.1-.5.2-1.5.7-1.9 2.2-.4 1.7-.8 2.2-1.1 2.7-.1.1-.2.3-.3.4l-.6 1.1H6c2 0 3.4-.4 4.7-1.4.8-.6 1.2-1.4 1.3-2.3 0-.3 0-.5-.1-.7L19 5.7c.5-.6.5-1.6-.1-2.2zM9.7 14.7c-.7.5-1.5.8-2.4 1 .2-.5.5-1.2.8-2.3.2-.6.4-1 .8-1.1.5-.1 1 .1 1.3.3.2.2.3.5.2.8 0 .3-.1.9-.7 1.3z"></path></svg>'.__('Branding', 'wp-extra'),
				'comments'	=> '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.68822 16.625L5.5 17.8145L5.5 5.5L18.5 5.5L18.5 16.625L6.68822 16.625ZM7.31 18.125L19 18.125C19.5523 18.125 20 17.6773 20 17.125L20 5C20 4.44772 19.5523 4 19 4H5C4.44772 4 4 4.44772 4 5V19.5247C4 19.8173 4.16123 20.086 4.41935 20.2237C4.72711 20.3878 5.10601 20.3313 5.35252 20.0845L7.31 18.125ZM16 9.99997H8V8.49997H16V9.99997ZM8 14H13V12.5H8V14Z"></path></svg>'.__('Comments'),
				'widget'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6 3H8V5H16V3H18V5C19.1046 5 20 5.89543 20 7V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V7C4 5.89543 4.89543 5 6 5V3ZM18 6.5H6C5.72386 6.5 5.5 6.72386 5.5 7V8H18.5V7C18.5 6.72386 18.2761 6.5 18 6.5ZM18.5 9.5H5.5V19C5.5 19.2761 5.72386 19.5 6 19.5H18C18.2761 19.5 18.5 19.2761 18.5 19V9.5ZM11 11H13V13H11V11ZM7 11V13H9V11H7ZM15 13V11H17V13H15Z"></path></svg>'.__('Widgets'),
				'security'	=> '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M17 10h-1.2V7c0-2.1-1.7-3.8-3.8-3.8-2.1 0-3.8 1.7-3.8 3.8v3H7c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h10c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zM9.8 7c0-1.2 1-2.2 2.2-2.2 1.2 0 2.2 1 2.2 2.2v3H9.8V7zm6.7 11.5h-9v-7h9v7z"></path></svg>'.__('Security'),
				'cookie'	=> '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z"></path></svg>'.__( 'Cookie' ),
				'smtp'	=> '<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M12.5939 21C14.1472 21 16.1269 20.5701 17.0711 20.1975L16.6447 18.879C16.0964 19.051 14.3299 19.6242 12.6548 19.6242C7.4467 19.6242 4.67513 16.8726 4.67513 12C4.67513 7.21338 7.50762 4.34713 12.2893 4.34713C17.132 4.34713 19.4162 7.55732 19.4162 10.7675C19.4162 14.035 19.0508 15.4968 17.4975 15.4968C16.5838 15.4968 16.0964 14.7803 16.0964 13.9777V7.5H14.4822V8.30255H14.3909C14.1777 7.67198 12.9898 7.12739 11.467 7.2707C9.18274 7.5 7.4467 9.27707 7.4467 11.8567C7.4467 14.5796 8.81726 16.672 11.467 16.758C13.203 16.8153 14.1168 16.0127 14.4822 15.1815H14.5736C14.7563 16.414 16.401 16.8439 17.467 16.8439C20.6954 16.8439 21 13.5764 21 10.7962C21 6.86943 18.0761 3 12.3807 3C6.50254 3 3 6.3535 3 11.9427C3 17.7325 6.38071 21 12.5939 21ZM11.7107 15.2962C9.73096 15.2962 9.03046 13.6051 9.03046 11.7707C9.03046 10.1083 10.0355 8.67516 11.7716 8.67516C13.599 8.67516 14.5736 9.36306 14.5736 11.7707C14.5736 14.1497 13.7513 15.2962 11.7107 15.2962Z"></path></svg>'.__('SMTP'),
				'optimize'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M3.445 16.505a.75.75 0 001.06.05l5.005-4.55 4.024 3.521 4.716-4.715V14h1.5V8.25H14v1.5h3.19l-3.724 3.723L9.49 9.995l-5.995 5.45a.75.75 0 00-.05 1.06z"></path></svg>'.__( 'Optimize', 'wp-extra' ),
				'code'	=> '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4.8 11.4H2.1V9H1v6h1.1v-2.6h2.7V15h1.1V9H4.8v2.4zm1.9-1.3h1.7V15h1.1v-4.9h1.7V9H6.7v1.1zM16.2 9l-1.5 2.7L13.3 9h-.9l-.8 6h1.1l.5-4 1.5 2.8 1.5-2.8.5 4h1.1L17 9h-.8zm3.8 5V9h-1.1v6h3.6v-1H20z"></path></svg>'.__( 'Code' ),
				'sslfix'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M17.031 4.703 15.576 4l-1.56 3H14v.03l-2.324 4.47H9.5V13h1.396l-1.502 2.889h-.95a3.694 3.694 0 0 1 0-7.389H10V7H8.444a5.194 5.194 0 1 0 0 10.389h.17L7.5 19.53l1.416.719L15.049 8.5h.507a3.694 3.694 0 0 1 0 7.39H14v1.5h1.556a5.194 5.194 0 0 0 .273-10.383l1.202-2.304Z"></path></svg>'.__( 'SSL Content Fixer' ),
				'tools'	=> '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M7 7.2h8.2L13.5 9l1.1 1.1 3.6-3.6-3.5-4-1.1 1 1.9 2.3H7c-.9 0-1.7.3-2.3.9-1.4 1.5-1.4 4.2-1.4 5.6v.2h1.5v-.3c0-1.1 0-3.5 1-4.5.3-.3.7-.5 1.2-.5zm13.8 4V11h-1.5v.3c0 1.1 0 3.5-1 4.5-.3.3-.7.5-1.3.5H8.8l1.7-1.7-1.1-1.1L5.9 17l3.5 4 1.1-1-1.9-2.3H17c.9 0 1.7-.3 2.3-.9 1.5-1.4 1.5-4.2 1.5-5.6z"></path></svg>'.__( 'Tools' ),
            ],
            'label' => __('List Module', 'wp-extra')
        ]);
        
    if (wp_extra_get_option('modules') && in_array('dashboard',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M18 5.5H6a.5.5 0 00-.5.5v3h13V6a.5.5 0 00-.5-.5zm.5 5H10v8h8a.5.5 0 00.5-.5v-7.5zm-10 0h-3V18a.5.5 0 00.5.5h2.5v-8zM6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>'.__('Dashboard'));
        $section = $tab->add_section('');
        $section->add_option('checkbox', [
            'name' => 'dashboard',
            'label' => __('All Dashboard', 'wp-extra'),
            'description' => __('Remove')
        ]);
        $section->add_option('checkbox', [
            'name' => 'dashboard_welcome',
            'label' => __('Admin Notice', 'wp-extra'),
            'description' => __('Remove').'. '.__('Welcome to your WordPress Dashboard!')
        ]);
        $section->add_option('text', [
            'name' => 'dashboard_title',
            'css' => ['hide_class' => 'dashboard_welcome hidden pro', 'input_class' => 'regular-text' ],
            'label' => __('Add title')
        ]);
        $section->add_option('wp-editor', [
            'name' => 'dashboard_content',
            'css' => ['hide_class' => 'dashboard_welcome hidden'],
            'label' => __('Add to Widget')
        ]);
        $section->add_option('text', [
            'name' => 'dashboard_rss_feed',
            'css' => ['hide_class' => 'dashboard_welcome hidden pro', 'input_class' => 'regular-text' ],
            'label' => __('RSS Feed')
        ]);
        $section->add_option('checkbox', [
            'name' => 'tab_help',
            'label' => __('Help'),
            'description' => __('Remove')
        ]);
        $section->add_option('checkbox', [
            'name' => 'tab_screen',
            'label' => __('Screen Options'),
            'description' => __('Remove')
        ]);
    }

    if (wp_extra_get_option('modules') && in_array('posts',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m7.3 9.7 1.4 1.4c.2-.2.3-.3.4-.5 0 0 0-.1.1-.1.3-.5.4-1.1.3-1.6L12 7 9 4 7.2 6.5c-.6-.1-1.1 0-1.6.3 0 0-.1 0-.1.1-.3.1-.4.2-.6.4l1.4 1.4L4 11v1h1l2.3-2.3zM4 20h9v-1.5H4V20zm0-5.5V16h16v-1.5H4z"></path></svg>'.__('Posts'));
        $section = $tab->add_section(__('Editor toolbar'));
        $section->add_option('checkbox', [
            'name' => 'mce_classic',
            'label' => __('Classic Editor'),
            'description' => __('Use the classic WordPress editor.', 'wp-extra')
        ]);
        $section->add_option('checkbox-multiple', [
            'name' => 'mce_plugin',
            'select' => true,
            'label' => __('TinyMCE Plugins'),
            'options' => [
                'justify'	=> '<span class="dashicons dashicons-editor-justify"></span> '.__('Justify'),
				'unlink'	=> '<span class="dashicons dashicons-editor-unlink"></span> '.__( 'Unlinks' ),
				'letterspacing'	=> '<strong>[VA]</strong> '.__( 'Letter Spacing' ),
				'changecase'	=> '<strong>[Aa]</strong> '.__('Change Case'),
				'table'	=> '<span class="dashicons dashicons-editor-table"></span> '.__('Table'),
				'visualblocks'	=> '<span class="dashicons dashicons-editor-paragraph"></span> '.__('Visual Blocks'),
				'searchreplace'	=> '<span class="dashicons dashicons-code-standards"></span> '.__('Search Replace'),
				'nofollow'	=> '<span class="dashicons dashicons-admin-links"></span> '.__('Add rel=nofollow & sponsored', 'wp-extra'),
				'cleanhtml'	=> '<span class="dashicons dashicons-editor-spellcheck"></span> '.__('Clean HTML', 'wp-extra').' <pro>PRO</pro>',
				'toc'	=> '<span class="dashicons dashicons-list-view"></span> '.__('Table of Contents').' <pro>PRO</pro>',
            ]
        ]);
        $section->add_option('checkbox', [
            'name' => 'signature',
            'label' => __('Signature', 'wp-extra'),
            'description' => sprintf(__('Used %1$s or %2$s','wp-extra'),
                '<code>[signature]</code>',
                '<span class="dashicons dashicons-heart"></span>'
            )
        ]);
        $section->add_option('wp-editor', [
            'name' => 'signature_content',
            'teeny' => true,
            'css' => ['hide_class' => 'signature hidden'],
            'label' => __('Content')
        ]);
        $section->add_option('choices', [
            'name' => 'signature_pos',
            'options' => [
                '' => __( 'No' ),
                'top'	=> __( 'Top' ),
				'bottom'	=> __( 'Bottom' )
            ],
            'label' => __('Display Options').' '.__('Single Post'),
            'css' => ['hide_class' => 'signature hidden']
        ]);
        if ( wp_get_theme()->template !== 'flatsome' ) {
            $section->add_option('checkbox', [
                'name' => 'classic_widget',
                'label' => __('Classic Widgets','wp-extra'),
                'description' => __('Display a legacy widget.', 'wp-extra')
            ]);
        }
        if ( !class_exists( 'RankMath' ) ) {
            $section->add_option('checkbox', [
                'name' => 'mce_category',
                'label' => __('Category Description', 'wp-extra'),
                'description' => __('Adds a tinymce editor to the category description box', 'wp-extra')
            ]);
        }
        $section->add_option('checkbox-multiple', [
            'name' => 'publish_btn',
            'options'     => fn() => array_combine(
                $ids = array_diff(get_post_types(['public' => true]), ['attachment', 'revision']),
                array_map(fn($id) => get_post_type_object($id)->label . " <code>$id</code>", $ids)
            ),
            'label' => __('Publish Button', 'wp-extra'),
            'description' => __('Making it stick to the bottom of the page when scrolling down the page', 'wp-extra')
        ]);
        
        $section = $tab->add_section(__('Posts Page'));
        $section->add_option('select', [
            'name' => 'limit_post_revisions',
            'options' => [
                ''      => __('No limit','wp-extra') . ' (' . __('Default') . ')',
                'false' => __('Disabled'),
                '1'     => '1',
                '3'     => '3',
                '5'     => '5',
                '10'    => '10'
            ],
            'label' => __('Revisions'),
            'description' => __('Required to be true, as revisions do not support trashing.')
        ]);
        $section->add_option('select', [
            'name' => 'autosave_interval',
            'options' => [
                ''    => sprintf(__('%s minute'), '1') . ' (' . __('Default') . ')',
                '180' => sprintf(__('%s minute'), '3'),
                '300' => sprintf(__('%s minute'), '5'),
                '600' => sprintf(__('%s minute'), '10')
            ],
            'label' => __('Auto Draft'),
            'description' => __('Maximum number of items to be returned in result set.')
        ]);
        $section->add_option('checkbox', [
            'name' => 'img_column',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Show Images'),
            'description' => __('Posts list')
        ]);
        $section->add_option('checkbox', [
            'name' => 'redirect_single_post',
            'label' => __('Redirect Single Post', 'wp-extra'),
            'description' => __('Redirect to the post if the search results return only one post.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'tag_links',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Tag links', 'wp-extra'),
            'description' => __('Remove link in the tags from all post', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'page_extension',
            'label' => __('Add Any Extension to Pages', 'wp-extra'),
            'description' => __('Allows you to specify an extension for pages', 'wp-extra')
        ]);
        $section->add_option('text', [
            'name' => 'page_slash',
            'css' => ['hide_class' => 'page_extension pro'],
            'label' => __('Extension', 'wp-extra'),
            'description' => __('Type in the extension you would like to use e.g. .html, .htm, .jsp, .cop, or any other. (Default: .html)', 'wp-extra')
        ]);
    }
    
    if (wp_extra_get_option('modules') && in_array('duplicate',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M5 4.5h11a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5H5a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5ZM3 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm17 3v10.75c0 .69-.56 1.25-1.25 1.25H6v1.5h12.75a2.75 2.75 0 0 0 2.75-2.75V8H20Z"></path></svg>'.__('Duplicate', 'wp-extra'));
        $section = $tab->add_section(__('Duplicate', 'wp-extra'));
        $section->add_option('checkbox-multiple', [
            'name' => 'duplicate',
            'options'     => fn() => array_combine(
                $ids = array_diff(get_post_types(['public' => true]), ['product', 'attachment']),
                array_map(fn($id) => get_post_type_object($id)->label . " <code>$id</code>", $ids)
            ),
            'label' => __('Duplicate Post Type', 'wp-extra'),
            'description' => __('Duplicate Posts, Pages and Custom Post Type', 'wp-extra')
        ]);
        $section->add_option('checkbox-multiple', [
            'name' => 'duplicate_tax',
            'options'     => fn() => array_combine(
                $ids = array_diff(get_taxonomies(['public' => true], 'names'), ['post_format']),
                array_map(fn($id) => get_taxonomy($id)->label . " <code>$id</code>", $ids)
            ),
            'label' => __('Duplicate Taxonomy', 'wp-extra'),
            'description' => __('Duplicate Category, Tags and Custom Taxonomy', 'wp-extra')
        ]);
    }

    if (wp_extra_get_option('modules') && in_array('media',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m7 6.5 4 2.5-4 2.5z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="m5 3c-1.10457 0-2 .89543-2 2v14c0 1.1046.89543 2 2 2h14c1.1046 0 2-.8954 2-2v-14c0-1.10457-.8954-2-2-2zm14 1.5h-14c-.27614 0-.5.22386-.5.5v10.7072l3.62953-2.6465c.25108-.1831.58905-.1924.84981-.0234l2.92666 1.8969 3.5712-3.4719c.2911-.2831.7545-.2831 1.0456 0l2.9772 2.8945v-9.3568c0-.27614-.2239-.5-.5-.5zm-14.5 14.5v-1.4364l4.09643-2.987 2.99567 1.9417c.2936.1903.6798.1523.9307-.0917l3.4772-3.3806 3.4772 3.3806.0228-.0234v2.5968c0 .2761-.2239.5-.5.5h-14c-.27614 0-.5-.2239-.5-.5z"></path></svg>'.__('Media'));
        $section = $tab->add_section(__('Auto-update'));
        $section->add_option('checkbox', [
            'name' => 'autoset',
            'label' => __('Auto Set Featured Image', 'wp-extra'),
            'description' => __('Automatically find and set post images as featured images', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'save_images',
            'label' => __('Auto Save Images', 'wp-extra'),
            'description' => __('Downloading automatically image from a post to gallery', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'autocrop',
            'css' => ['hide_class' => 'save_images hidden'],
            'label' => '➡️ ' . __('Auto Crop', 'wp-extra')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'crop_width',
            'css' => ['input_class' => 'small-text', 'hide_class' => 'autocrop hidden'],
            'label' => '➡️ ' . __('Crop Width'),
            'description' => __('px (E.g: 800px). ')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'crop_height',
            'css' => ['input_class' => 'small-text', 'hide_class' => 'autocrop hidden'],
            'label' => '➡️ ' . __('Crop Height'),
            'description' => __('px (E.g: 600px). ')
        ]);
        $section->add_option('checkbox', [
            'name' => 'autoflip',
            'css' => ['hide_class' => 'save_images hidden pro'],
            'label' => '➡️ ' . __('Flip horizontal', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'image_convert',
            'css' => ['hide_class' => 'save_images hidden pro'],
            'label' => '➡️ ' . __('Convert PNG to JPG', 'wp-extra')
        ]);
        
        $section->add_option('checkbox', [
            'name' => 'resize_images',
            'label' => __('Resize Images', 'wp-extra'),
            'description' => __('Automatically resizes uploaded images (JPEG, GIF, and PNG)', 'wp-extra')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'image_max_width',
            'css' => ['input_class' => 'small-text', 'hide_class' => 'resize_images hidden'],
            'label' => '➡️ ' . __('Max Width'),
            'description' => __('px (E.g: 1000px). ').__('Max size of an uploaded file')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'image_max_height',
            'css' => ['input_class' => 'small-text', 'hide_class' => 'resize_images hidden'],
            'label' => '➡️ ' . __('Max Height'),
            'description' => __('px (E.g: 1000px). ').__('Max size of an uploaded file')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'image_limit',
            'css' => ['input_class' => 'small-text'],
            'label' => __('Image Size in kilobytes', 'wp-extra'),
            'description' => __('kb (E.g: 2000 = 2MB). ').__('Max size of an uploaded file')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'image_quality',
            'css' => ['input_class' => 'small-text'],
            'default' => '90',
            'options' => [
                'step' => '5',
                'min' => '70',
                'max' => '100'
            ],
            'label' => __('JPEG compression level', 'wp-extra'),
            'description' => __('% (Default: 90%)', 'wp-extra')
        ]);
        $section->add_option('select', [
            'name' => 'rename_images',
            'css' => ['hide_class' => 'pro'],
            'label' => __('File Renamer', 'wp-extra'),
            'options' => [
				''	=> __( 'No' ),
				'slug'	=> __('post-slug.jpg'),
				'filename'	=>  __('post-slug-{file-name}.jpg'),
				'date'=> __('post-slug-{2023-06-11}.jpg')
            ],
            'description' => __('Rename uploaded files available in wordpress media and change the postname or slug name.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'meta_images',
            'label' => __('Optimize image SEO', 'wp-extra'),
            'description' => __('Auto Set The Image Title, Alt-Text, Caption & Description. Default to using the post title.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'meta_images_filename',
            'css' => ['hide_class' => 'meta_images hidden'],
            'label' => __('From Image Filename', 'wp-extra')
        ]);
        
        $section = $tab->add_section(__('Optional'));
        $section->add_option('checkbox', [
            'name' => 'delete_attached',
            'label' => __('Delete Attached Media', 'wp-extra')
        ]);
        $section->add_option('image', [
            'name' => 'media_default',
            'label' => __('Default featured image', 'wp-extra'),
            'description' => __('This featured image will show up if no featured image is set', 'wp-extra')
        ]);
        $section->add_option('checkbox-multiple', [
            'name' => 'media_thumbnails',
            'options' => fn() => array_combine(
                $sizes = get_intermediate_image_sizes(),
                array_map(fn($size) => ucfirst(str_replace('_', ' ', $size)), $sizes)
            ),
            'label' => __('Disable Thumbnails', 'wp-extra'),
            'description' => __('Disable thumbnail sizes, default WordPress sizes and theme/plugins image size', 'wp-extra')
        ]);
        $section->add_option('checkbox-multiple', [
            'name' => 'media_functions',
            'del' => true,
            'label' => __('Disable Threshold & EXIF', 'wp-extra'),
            'options' => [
				'threshold'	=> __('Large image threshold', 'wp-extra'),
				'exif'=> __('Exif automatic rotation', 'wp-extra')
            ]
        ]);
    }

    if (wp_extra_get_option('modules') && in_array('comments',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.68822 16.625L5.5 17.8145L5.5 5.5L18.5 5.5L18.5 16.625L6.68822 16.625ZM7.31 18.125L19 18.125C19.5523 18.125 20 17.6773 20 17.125L20 5C20 4.44772 19.5523 4 19 4H5C4.44772 4 4 4.44772 4 5V19.5247C4 19.8173 4.16123 20.086 4.41935 20.2237C4.72711 20.3878 5.10601 20.3313 5.35252 20.0845L7.31 18.125ZM16 9.99997H8V8.49997H16V9.99997ZM8 14H13V12.5H8V14Z"></path></svg>'.__('Comments'));
        $section = $tab->add_section(__('Comments'));
        $section->add_option('checkbox', [
            'name' => 'disable_comments',
            'label' => __('All Comments'),
            'description' => __('Hide')
        ]);
        $section->add_option('checkbox', [
            'name' => 'cm_antispam',
            'label' => __('Anti-Spam Comments', 'wp-extra'),
            'description' => __('Automatically checks all comments and filters out the ones that look like spam.', 'wp-extra')
        ]);
        $section->add_option('text', [
            'name' => 'cm_traffic',
            'css' => ['hide_class' => 'cm_antispam hidden pro', 'input_class' => 'regular-text'],
            'label' => __('Traffic Spam'),
            'description' => __('Redirect to link', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'cm_media',
            'label' => __('Comment Media'),
            'description' => __('Remove')
        ]);
    }

    if (wp_extra_get_option('modules') && in_array('widget',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M6 3H8V5H16V3H18V5C19.1046 5 20 5.89543 20 7V19C20 20.1046 19.1046 21 18 21H6C4.89543 21 4 20.1046 4 19V7C4 5.89543 4.89543 5 6 5V3ZM18 6.5H6C5.72386 6.5 5.5 6.72386 5.5 7V8H18.5V7C18.5 6.72386 18.2761 6.5 18 6.5ZM18.5 9.5H5.5V19C5.5 19.2761 5.72386 19.5 6 19.5H18C18.2761 19.5 18.5 19.2761 18.5 19V9.5ZM11 11H13V13H11V11ZM7 11V13H9V11H7ZM15 13V11H17V13H15Z"></path></svg>'.__('Widgets'));
        $section = $tab->add_section('');
        $section->add_option('widget', [
            'name' => 'disable_widget',
            'label' => __('Available Widgets'),
            'description' => __('Choose the sidebar widgets you would like to disable. Note that developers can still display widgets using PHP.', 'wp-extra')
        ]);
    }
        
    if (wp_extra_get_option('modules') && in_array('admins',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M15.5 9.5a1 1 0 100-2 1 1 0 000 2zm0 1.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm-2.25 6v-2a2.75 2.75 0 00-2.75-2.75h-4A2.75 2.75 0 003.75 15v2h1.5v-2c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v2h1.5zm7-2v2h-1.5v-2c0-.69-.56-1.25-1.25-1.25H15v-1.5h2.5A2.75 2.75 0 0120.25 15zM9.5 8.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" fill-rule="evenodd"></path></svg>'.__('Permission', 'wp-extra'));
        $section = $tab->add_section(__('Admin Bars'));
        $section->add_option('checkbox', [
            'name' => 'wp_adminbar',
            'label' => __('Admin Bar'),
            'description' => __('Remove')
        ]);
        $section->add_option('select2', [
            'name' => 'wp_adminbar_roles',
            'multiple' => true,
            'options'     => fn() => array_combine(
                $ids = array_diff(array_keys(get_editable_roles()), []),
                array_map(fn($id) => get_editable_roles()[$id]['name'], $ids)
            ),
            'css' => ['hide_class' => 'wp_adminbar hidden' ],
            'label' => __('Role'),
            'description' => __('Roles assigned to the user.')
        ]);
        $section->add_option('checkbox', [
            'name' => 'wp_adminbar_auto',
            'css' => ['hide_class' => 'wp_adminbar hidden pro' ],
            'label' => __('Auto-hide', 'wp-extra'),
            'description' => __('Enabled')
        ]);
        $section->add_option('checkbox-multiple', [
            'name' => 'wp_adminbar_visible',
            'css' => ['hide_class' => 'wp_adminbar hidden' ],
            'label' => __('Show'),
            'options' => [
				'site-admin'=> __('Site Admin'),
				'homepage'	=> __( 'Homepage' )
            ]
        ]);
        $section->add_option('checkbox-multiple', [
            'select' => true,
            'css' => ['hide_class' => 'wp_adminbar visible' ],
            'name' => 'wp_toolbar',
            'options' => [
				'wp-logo'	=> __( 'Logo' ),
				'site-name'	=> __( 'Site Title' ),
				'new-content'	=> __( 'New Menu' ),
				'comments'	=> __( 'Comments' ),
				'updates'	=> __( 'Update' ),
				'flatsome_panel'	=> __( 'Flatsome' ),
				'wp-extra'	=> __( 'WP EXtra' ),
				'wpseo-menu'	=> __( 'Yoast SEO' ),
				'rank-math'	=> __( 'Rank Math' ),
				'wp-rocket'	=> __( 'WP Rocket' ),
				'my-account'	=> __( 'Profile' )
            ],
            'label' => __('Hide Toolbar', 'wp-extra')
        ]);

        $section = $tab->add_section(__('Admin Menus'));
        $section->add_option('checkbox-multiple', [
            'name' => 'adminmenu_list',
            'select' => true,
            'options' => function () {
                global $menu;
                $menu_items = [];
                
                foreach ($menu as $item) {
                    if (preg_match('/wp-menu-separator/', $item[4])) {
                        $label = '<sub style="color:#616A74;">― Separator</sub>';
                    } else {
                        $label = $item[0];
                    }
                    
                    $key = esc_attr($item[2]);
                    $menu_items[$key] = $label;
                }
                
                return $menu_items;
            },
            'label' => __('Hide Menu', 'wp-extra')
        ]);
        
        $section = $tab->add_section(__('Admin Plugins'));
        $section->add_option('checkbox-multiple', [
            'name' => 'adminplugin_list',
            'select' => true,
            'options' => function () {
                $all_plugins = get_plugins();
                $plugin_items = [];

                foreach ($all_plugins as $value => $item) {
                    $key = esc_attr($value);
                    $label = wp_strip_all_tags($item['Name']);
                    $plugin_items[$key] = $label;
                }

                return $plugin_items;
            },
            'label' => __('Hide Plugin', 'wp-extra')
        ]);
        
        $section = $tab->add_section(__('Permission', 'wp-extra'));
        $section->add_option('checkbox', [
            'name' => 'restricted_backend',
            'label' => __('Restricted backend access for non-admins.', 'wp-extra'),
            'description' => __('Enabled')
        ]);
        $section->add_option('checkbox-multiple', [
            'css' => ['hide_class' => 'pro'],
            'name' => 'adminmenu_extra',
            'options' => wp_list_pluck(get_users(['role' => 'administrator']), 'display_name', 'ID'),
            'label' => __('WP EXtra Permission', 'wp-extra'),
            'description' => __('This user has super admin privileges.')
        ]);
        $section->add_option('checkbox-multiple', [
            'css' => ['hide_class' => 'pro'],
            'name' => 'hide_users',
            'options' => wp_list_pluck(get_users(['role' => 'administrator']), 'display_name', 'ID'),
            'label' => __('Hide Users', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'themeplugin_edits',
            'label' => __('Theme & Plugin Editors', 'wp-extra'),
            'description' => __('Hide')
        ]);
        $section->add_option('checkbox', [
            'name' => 'core_updates',
            'label' => __('All Core Updates', 'wp-extra'),
            'description' => __('Hide')
        ]);
        $section->add_option('checkbox', [
            'name' => 'misc_client_nags',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Nags & Notices', 'wp-extra'),
            'description' => __('Hide')
        ]);

        $section = $tab->add_section(__('Users'));
        $section->add_option('checkbox', [
            'name' => 'profile_email',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Field Email', 'wp-extra'),
            'description' => __('Remove')
        ]);
        $section->add_option('checkbox', [
            'name' => 'profile_pw',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Field Password', 'wp-extra'),
            'description' => __('Remove')
        ]);
        $section->add_option('checkbox', [
            'name' => 'application_passwords',
            'label' => __('Application Passwords'),
            'description' => __('Remove')
        ]);
        $section = $tab->add_section(__('Optional'));
        $section->add_option('checkbox', [
            'name' => 'scrolltotop',
            'label' => __('Scroll To Top'),
            'description' => __('Back To Top In WP Admin Area', 'wp-extra')
        ]);
    }
        
    if (wp_extra_get_option('modules') && in_array('logins',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4 20h8v-1.5H4V20zM18.9 3.5c-.6-.6-1.5-.6-2.1 0l-7.2 7.2c-.4-.1-.7 0-1.1.1-.5.2-1.5.7-1.9 2.2-.4 1.7-.8 2.2-1.1 2.7-.1.1-.2.3-.3.4l-.6 1.1H6c2 0 3.4-.4 4.7-1.4.8-.6 1.2-1.4 1.3-2.3 0-.3 0-.5-.1-.7L19 5.7c.5-.6.5-1.6-.1-2.2zM9.7 14.7c-.7.5-1.5.8-2.4 1 .2-.5.5-1.2.8-2.3.2-.6.4-1 .8-1.1.5-.1 1 .1 1.3.3.2.2.3.5.2.8 0 .3-.1.9-.7 1.3z"></path></svg>'.__('Branding', 'wp-extra'));
        $section = $tab->add_section(__('Log in'));
        $section->add_option('text', [
            'name' => 'login_title',
            'css' => ['input_class' => 'regular-text'],
            'label' => __('Login Title', 'wp-extra'),
            'description' => __('Change the title tag content used on the login page.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'login_logo_hide',
            'default' => 1,
            'label' => __('Logo'),
            'description' => __('Remove')
        ]);
        $section->add_option('image', [
            'name' => 'login_logo',
            'css' => ['hide_class' => 'login_logo_hide visible'],
            'label' => __('Custom Logo'),
            'description' => __('Replace the default WordPress logo. Max width: 320px.', 'wp-extra')
        ]);
        $section->add_option('text', [
            'name' => 'login_logo_url',
            'css' => ['hide_class' => 'login_logo_hide visible pro', 'input_class' => 'regular-text'],
            'label' => __('Logo URL', 'wp-extra'),
            'description' => __('When login logo is clicked, the user will be redirected to this url.', 'wp-extra')
        ]);
        $section->add_option('color', [
            'name' => 'login_color',
            'label' => __('Custom Colors')
        ]);
        $section->add_option('image', [
            'name' => 'login_bg_image',
            'type' => 'text',
            'label' => __('Set as background')
        ]);
        $section->add_option('color', [
            'name' => 'login_bg_color',
            'label' => __('Custom Background')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'login_form_radius',
            'css' => ['input_class' => 'small-text'],
            'label' => __('Form Border Radius'),
            'description' => 'px'
        ]);
        $section->add_option('checkbox', [
            'name' => 'login_placeholder',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Placeholder'),
            'description' => __('Username') .' & '.__('Password'),
        ]);
        $section->add_option('checkbox', [
            'name' => 'login_remember',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Remember Me'),
            'description' => __('Auto-check')
        ]);
        $section->add_option('checkbox-multiple', [
            'name' => 'login_link_form',
            'css' => ['hide_class' => 'pro'],
            'options' => [
				'remember' => __('Remember Me'),
                'lost' => __('Register') .' | '.__('Lost your password?'),
                'backto' => __('&laquo; Back'),
                'language' => __('Language'),
                'privacy' => __('Privacy Policy')
            ],
            'label' => __('Hide Controls','wp-extra')
        ]);
        $section->add_option('text', [
            'name' => 'login_url',
            'label' => __('Login Address (URL)'),
            'description' => __('When configured, this feature modifies your WordPress login URL (slug) to the specified string and prevents direct access to the wp-admin and wp-login endpoints.', 'wp-extra').'<br>🔐 <a href="'.esc_url(wp_login_url()).'" tooltip="'.__('New private window', 'wp-extra').'" target="_blank">'.__('Preview').'</a>'
        ]);

        $section = $tab->add_section(__('Site Admin'));
        $section->add_option('image', [
            'name' => 'admin_logo',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Admin Logo'),
            'description' => __('Replace image')
        ]);
        $section->add_option('text', [
            'name' => 'admin_logo_link',
            'css' => ['hide_class' => 'pro', 'input_class' => 'regular-text'],
            'label' => __('Link URL'),
            'description' => __('Add New Link')
        ]);
        $section->add_option('choices', [
            'name' => 'admincolor_scheme',
            'css' => ['hide_class' => 'pro' ],
            'options' => [
                '' => __('Default'),
                'blue' => __('Blue'),
                'light' => __('Light')
            ],
            'label' => __('Admin Color Scheme'),
            'description' => __('Custom Colors')
        ]);
        $section->add_option('checkbox', [
            'name' => 'adminfooter_version',
            'label' => sprintf(__('%s WordPress'),__('Version')),
            'description' => __('Hide')
        ]);
        $section->add_option('wp-editor', [
            'name' => 'adminfooter_custom',
            'css' => ['hide_class' => 'pro'],
            'teeny' => true,
            'label' => __('Footer Text'),
        ]);
        
        $section = $tab->add_section(__('Copyright'));
        $section->add_option('checkbox', [
            'name' => 'donot_copy',
            'label' => __('Do Not Copy', 'wp-extra'),
            'description' => __('Restrict user to copy content & disable mouse right click.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'donot_content',
            'css' => ['hide_class' => 'donot_copy pro'],
            'label' => __('Copying Content', 'wp-extra'),
            'description' => __('Allow')
        ]);
        $section->add_option('text', [
            'name' => 'donot_copyright',
            'css' => ['hide_class' => 'donot_copy pro', 'input_class' => 'regular-text'],
            'label' => __('Copyright WP EXtra', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'donot_back',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Button Back', 'wp-extra'),
            'description' => __('Restrict user from clicking the back button.', 'wp-extra')
        ]);
    }
        
    if (wp_extra_get_option('modules') && in_array('security',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M17 10h-1.2V7c0-2.1-1.7-3.8-3.8-3.8-2.1 0-3.8 1.7-3.8 3.8v3H7c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h10c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zM9.8 7c0-1.2 1-2.2 2.2-2.2 1.2 0 2.2 1 2.2 2.2v3H9.8V7zm6.7 11.5h-9v-7h9v7z"></path></svg>'.__('Security'));
        $section = $tab->add_section(__('Htaccess'), ['description' => __('Please backup before making any changes.', 'wp-extra')]);
        $section->add_option('import', [
            'name' => 'htaccess_root',
            'label' => __('Root'),
            'description' => __('.htaccess'),
            'default' => '# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress'
        ]);
        $section->add_option('import', [
            'name' => 'htaccess_includes',
            'label' => __('WP-Includes'),
            'description' => __('wp-includes/.htaccess')
        ]);
        $section->add_option('import', [
            'name' => 'htaccess_content',
            'label' => __('WP-Content'),
            'description' => __('wp-content/.htaccess')
        ]);
        
        $section = $tab->add_section(__('Security'));
        $section->add_option('checkbox', [
            'name' => 'disable_embeds',
            'label' => __('Embeds'),
            'description' => __('Removes WordPress Embed JavaScript file (wp-embed.min.js). ', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'disable_xmlrpc',
            'label' => __('XML-RPC'),
            'description' => __('XML-RPC services are disabled on this site.')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_jquery_migrate',
            'label' => __('jQuery Migrate'),
            'description' => __('Removes jQuery Migrate JavaScript file (jquery-migrate.min.js).', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_wp_version',
            'label' => __('Version'),
            'description' => __('Removes WordPress version meta tag.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_wlwmanifest_link',
            'label' => __('wlwmanifest'),
            'description' => __('Remove wlwmanifest (Windows Live Writer) link tag.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_rsd_link',
            'label' => __('RSD Link'),
            'description' => __('Remove RSD (Real Simple Discovery) link tag.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_shortlink',
            'label' => __('Shortlink'),
            'description' => __('Remove Shortlink link tag.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'disable_rss_feeds',
            'label' => __('RSS Feeds'),
            'description' => __('Disable WordPress generated RSS feeds and 301 redirect URL to parent.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_feed_links',
            'label' => __('RSS Feed Links'),
            'description' => __('Disable WordPress generated RSS feed link tags.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'disable_self_pingbacks',
            'label' => __('Self Pingbacks'),
            'description' => __('Disable Self Pingbacks (generated when linking to an article on your own blog).', 'wp-extra')
        ]);
        $section->add_option('choices', [
            'name' => 'disable_rest_api',
            'options' => [
                ''           => __('Default (Enabled)', 'wp-extra'),
    			'non_admins' => __('Disable for Non-Admins', 'wp-extra'),
    			'logged_out' => __('Disable When Logged Out', 'wp-extra')
            ],
            'label' => __('REST API'),
            'description' => __('Disables REST API requests and displays an error message if the requester does not have permission.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_rest_api_links',
            'label' => __('REST API Links'),
            'description' => __('Removes REST API link tag from the front end and the REST API header link from page requests.', 'wp-extra')
        ]);
        $section->add_option('choices', [
            'name' => 'disable_heartbeat',
            'options' => [
                '' => __('Default'),
                'everywhere' => __('Disable Everywhere', 'wp-extra'),
                'allow_posts' => __('Only Allow When Editing Posts/Pages', 'wp-extra')
            ],
            'label' => __('Heartbeat'),
            'description' => __('Disable WordPress Heartbeat everywhere or in certain areas (used for auto saving and revision tracking).', 'wp-extra')
        ]);
        $section->add_option('select', [
            'name' => 'heartbeat_frequency',
            'options' => [
                ''   => sprintf(__('%s second'), '15') . ' (' . __('Default') . ')',
                '30' => sprintf(__('%s second'), '30'),
                '45' => sprintf(__('%s second'), '45'),
                '60' => sprintf(__('%s second'), '60')
            ],
            'label' => __('Heartbeat Frequency'),
            'description' => __('Controls how often the WordPress Heartbeat API is allowed to run.', 'wp-extra')
        ]);
    }
        
    if (wp_extra_get_option('modules') && in_array('cookie',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm.5 16c0 .3-.2.5-.5.5H5c-.3 0-.5-.2-.5-.5V7h15v12zM9 10H7v2h2v-2zm0 4H7v2h2v-2zm4-4h-2v2h2v-2zm4 0h-2v2h2v-2zm-4 4h-2v2h2v-2zm4 0h-2v2h2v-2z"></path></svg>'.__('Cookie'));
        $section = $tab->add_section(__('Cookie'));
        $section->add_option('textarea', [
            'name' => 'cookie_message',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Message', 'wp-extra')
        ]);
        $section->add_option('text', [
            'name' => 'cookie_button',
            'css' => ['hide_class' => 'pro', 'input_class' => 'regular-text'],
            'label' => __('Button Text')
        ]);
        $section->add_option('checkbox', [
            'name' => 'cookie_privacy',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Display Privacy Policy', 'wp-extra'),
            'description' => '<a href="'.get_privacy_policy_url().'">'.__('Privacy Policy Page').'</a>'
        ]);
        $section->add_option('choices', [
            'name' => 'cookie_placement',
            'options' => [
                ''    => __('Bottom'),
                'top' => __('Top')
            ],
            'label' => __('Cookie info placement')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'css' => ['input_class' => 'small-text'],
            'min' => 1,
            'max' => 365,
            'name' => 'cookie_expire',
            'label' => __('Cookie expire time', 'wp-extra'),
            'description' => __('in days')
        ]);
        $section->add_option('color', [
            'name' => 'cookie_bgcolor',
            'label' => __('Background color')
        ]);
        $section->add_option('color', [
            'name' => 'cookie_textcolor',
            'label' => __('Text color')
        ]);
        $section->add_option('color', [
            'name' => 'cookie_btnbgcolor',
            'label' => __('Button background color')
        ]);
        $section->add_option('color', [
            'name' => 'cookie_btntextcolor',
            'label' => __('Button text color')
        ]);
    }
        
    if (wp_extra_get_option('modules') && in_array('smtp',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M12.5939 21C14.1472 21 16.1269 20.5701 17.0711 20.1975L16.6447 18.879C16.0964 19.051 14.3299 19.6242 12.6548 19.6242C7.4467 19.6242 4.67513 16.8726 4.67513 12C4.67513 7.21338 7.50762 4.34713 12.2893 4.34713C17.132 4.34713 19.4162 7.55732 19.4162 10.7675C19.4162 14.035 19.0508 15.4968 17.4975 15.4968C16.5838 15.4968 16.0964 14.7803 16.0964 13.9777V7.5H14.4822V8.30255H14.3909C14.1777 7.67198 12.9898 7.12739 11.467 7.2707C9.18274 7.5 7.4467 9.27707 7.4467 11.8567C7.4467 14.5796 8.81726 16.672 11.467 16.758C13.203 16.8153 14.1168 16.0127 14.4822 15.1815H14.5736C14.7563 16.414 16.401 16.8439 17.467 16.8439C20.6954 16.8439 21 13.5764 21 10.7962C21 6.86943 18.0761 3 12.3807 3C6.50254 3 3 6.3535 3 11.9427C3 17.7325 6.38071 21 12.5939 21ZM11.7107 15.2962C9.73096 15.2962 9.03046 13.6051 9.03046 11.7707C9.03046 10.1083 10.0355 8.67516 11.7716 8.67516C13.599 8.67516 14.5736 9.36306 14.5736 11.7707C14.5736 14.1497 13.7513 15.2962 11.7107 15.2962Z"></path></svg>'.__('SMTP'));
        $section = $tab->add_section(__('Configure'));
        $section->add_option('checkbox', [
            'name' => 'smtp',
            'label' => __('Server settings'),
            'description' => __('Manual configuration. By default, automatically uses Gmail SMTP', 'wp-extra')
        ]);
        $section->add_option('text', [
            'name' => 'smtp_host',
            'css' => ['hide_class' => 'smtp hidden', 'input_class' => 'regular-text' ],
            'label' => __('Mail Server'),
            'description' => sprintf(__('The SMTP server which will be used to send email. %s', 'wp-extra'),'E.g: smtp.mail.com')
        ]);
        $section->add_option('text', [
            'type' => 'number',
            'name' => 'smtp_port',
            'css' => ['hide_class' => 'smtp hidden', 'input_class' => 'small-text' ],
            'label' => __('Port'),
            'description' => __('The port which will be used when sending an email (587/465/25). If you choose TLS it should be set to 587. For SSL use port 465 instead.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'smtp_auth',
            'css' => ['hide_class' => 'smtp hidden', 'input_class' => 'regular-text' ],
            'label' => __('Authentication'),
            'description' => __('Authenticate connection with username and password', 'wp-extra')
        ]);
        $section->add_option('choices', [
            'name' => 'smtp_encryption',
            'css' => ['hide_class' => 'smtp hidden', 'input_class' => 'regular-text' ],
            'options' => [
                ''    => __('Default'),
                'tls' => __('TLS'),
                'ssl' => __('SSL')
            ],
            'label' => __('Type of Encryption')
        ]);
        $section->add_option('text', [
            'name' => 'smtp_username',
            'css' => ['input_class' => 'regular-text' ],
            'label' => __('Username')
        ]);
        $section->add_option('password', [
            'name' => 'smtp_password',
            'css' => ['input_class' => 'regular-text' ],
            'label' => __('Password')
        ]);
        $section->add_option('text', [
            'name' => 'from_email',
            'text' => 'email',
            'css' => ['input_class' => 'regular-text' ],
            'label' => __('Force from e-mail address', 'wp-extra')
        ]);
        $section->add_option('text', [
            'name' => 'from_name',
            'css' => ['input_class' => 'regular-text' ],
            'label' => __('Force from e-mail sender name', 'wp-extra')
        ]);
        $section->add_option('checkbox-multiple', [
            'name' => 'smtp_options',
            'options' => [
                'noverifyssl' => __('Disable SSL Verification', 'wp-extra'),
                'antispam' => __('Anti-spam forms', 'wp-extra')
            ],
            'label' => __('Advanced')
        ]);
        $section->add_option('smtp', [
            'name' => 'test_email',
            'label' => __('Test Email', 'wp-extra'),
            'description' => __('Sends a simple test email to check your settings.', 'wp-extra')
        ]);
        
        $section = $tab->add_section(__('Email'));
        $section->add_option('checkbox-multiple', [
            'name' => 'no_emails',
            'options' => [
                'remove_admin' => __('Remove admin email confirmation', 'wp-extra'),
                'auto_update' => __('Disable auto update email notification', 'wp-extra'),
                'new_user' => __('Disable admin email notification when a new user is registered', 'wp-extra'),
                'password_reset' => __('Disable email notification when users reset passwords', 'wp-extra')
            ],
            'label' => __('Disable email notifications', 'wp-extra')
        ]);
        $section->add_option('textarea', [
            'name' => 'email_domain',
            'label' => __('Valid Email Domain', 'wp-extra'),
            'description' => __('Validates that the email domain of users registering is either gmail.com or yahoo.com', 'wp-extra')
        ]);
    }

    if (wp_extra_get_option('modules') && in_array('optimize',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M3.445 16.505a.75.75 0 001.06.05l5.005-4.55 4.024 3.521 4.716-4.715V14h1.5V8.25H14v1.5h3.19l-3.724 3.723L9.49 9.995l-5.995 5.45a.75.75 0 00-.05 1.06z"></path></svg>'.__('Optimize', 'wp-extra'));
        $section = $tab->add_section('');
        $section->add_option('choices', [
            'name' => 'to_home',
            'options' => [
                ''    => __('None'),
                'home' => __('Homepage'),
                'random' => __('Random Post')
            ],
            'label' => __('404 to Home/Post'),
            'description' => __('Redirect 404 Error Page to Homepage/Single Post', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'query_strings',
            'label' => __('Query Strings'),
            'description' => __('Remove query strings from static resources (CSS, JS).', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'gutenberg',
            'label' => __('Gutenberg'),
            'description' => __('Prevent Gutenberg Block Library CSS from Loading on the Frontend.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'defer_css',
            'label' => __('Defer CSS'),
            'description' => __('Enabled')
        ]);
        $section->add_option('checkbox', [
            'name' => 'defer_js',
            'label' => __('Defer JS'),
            'description' => __('Enabled')
        ]);
        $section->add_option('choices', [
            'name' => 'defer_js_type',
            'options' => [
                ''    => __('Javascript'),
                'php' => __('PHP')
            ],
            'css' => ['hide_class' => 'defer_js hidden'],
            'label' => __('Type')
        ]);
        $section->add_option('textarea', [
            'name' => 'defer_js_list',
            'rows' => 20,
            'css' => ['hide_class' => 'defer_js hidden'],
            'label' => __('Items list'),
            'description' => __('List of JavaScript file IDs. Example: id="wp-extra-js" should only use wp-extra. One data field per line.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'minify_html',
            'label' => __('Minify HTML'),
            'description' => __('Minify HTML output for clean looking markup and faster downloading.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'tuborlinks',
            'css' => ['hide_class' => 'pro'],
            'label' => __('Tuborlinks'),
            'description' => __('Enabled')
        ]);
        $section->add_option('checkbox', [
            'name' => 'disable_emojis',
            'label' => __('Emojis'),
            'description' => __('Removes WordPress Emojis JavaScript file (wp-emoji-release.min.js).', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'disable_dashicons',
            'label' => __('Dashicons'),
            'description' => __('Disables dashicons on the front end when not logged in.', 'wp-extra')
        ]);
        $section->add_option('checkbox', [
            'name' => 'remove_global_styles',
            'label' => __('Global Styles & SVG Filters'),
            'description' => __('Remove global-styles-inline-css & SVG Duotone Filter', 'wp-extra')
        ]);
    }

    if (wp_extra_get_option('modules') && in_array('code',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4.8 11.4H2.1V9H1v6h1.1v-2.6h2.7V15h1.1V9H4.8v2.4zm1.9-1.3h1.7V15h1.1v-4.9h1.7V9H6.7v1.1zM16.2 9l-1.5 2.7L13.3 9h-.9l-.8 6h1.1l.5-4 1.5 2.8 1.5-2.8.5 4h1.1L17 9h-.8zm3.8 5V9h-1.1v6h3.6v-1H20z"></path></svg>'.__('Code'));
        $section = $tab->add_section(__('Custom Scripts', 'wp-extra'));
        $section->add_option('code-editor', [
            'name' => 'code_header',
            'label' => __('Header Scripts'),
            'description' => __('Add custom scripts inside HEAD tag. You need to have a SCRIPT tag around scripts.', 'wp-extra')
        ]);
        if(function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2' , '>=')) {
            $section->add_option('code-editor', [
                'name' => 'code_body',
                'label' => __('Body Scripts'),
                'description' => __('Add custom scripts just after the BODY tag opened. You need to have a SCRIPT tag around scripts.', 'wp-extra')
            ]);
        }
        $section->add_option('code-editor', [
            'name' => 'code_footer',
            'label' => __('Footer Scripts'),
            'description' => __('Add custom scripts you might want to be loaded in the footer of your website. You need to have a SCRIPT tag around scripts.', 'wp-extra')
        ]);

        $section = $tab->add_section(__('Custom CSS'));
        $section->add_option('textarea', [
            'name' => 'css_all',
            'css' => ['input_class' => 'large-text code'],
            'rows' => 10,
            'label' => __('All screens', 'wp-extra'),
            'description' => __('Add custom CSS here', 'wp-extra')
        ]);
        $section->add_option('textarea', [
            'name' => 'css_tablet',
            'css' => ['input_class' => 'large-text code'],
            'rows' => 10,
            'label' => __('Tablets and down', 'wp-extra'),
            'description' => __('Add custom CSS here for tablets and mobile', 'wp-extra')
        ]);
        $section->add_option('textarea', [
            'name' => 'css_mobile',
            'css' => ['input_class' => 'large-text code'],
            'rows' => 10,
            'label' => __('Mobile only', 'wp-extra'),
            'description' => __('Add custom CSS here for mobile view', 'wp-extra')
        ]);
    }
        
    if (wp_extra_get_option('modules') && in_array('tools',  wp_extra_get_option('modules'))) {
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M7 7.2h8.2L13.5 9l1.1 1.1 3.6-3.6-3.5-4-1.1 1 1.9 2.3H7c-.9 0-1.7.3-2.3.9-1.4 1.5-1.4 4.2-1.4 5.6v.2h1.5v-.3c0-1.1 0-3.5 1-4.5.3-.3.7-.5 1.2-.5zm13.8 4V11h-1.5v.3c0 1.1 0 3.5-1 4.5-.3.3-.7.5-1.3.5H8.8l1.7-1.7-1.1-1.1L5.9 17l3.5 4 1.1-1-1.9-2.3H17c.9 0 1.7-.3 2.3-.9 1.5-1.4 1.5-4.2 1.5-5.6z"></path></svg>'.__('Tools'));
        $section = $tab->add_section(__('Tools'), ['slug' => true, 'description' => __('You can transfer the saved options data between different installs by copying the text inside the text box. To import data from another install, replace the data in the text box with the one from another install and click "Import".', 'wp-extra')]);
        $section->add_option('export', [
            'name' => 'wpdb_download',
            'label' => __('Export to SQL', 'wp-extra'),
            'description' => __('Administrator role user can take dump of the mysql database by single click.', 'wp-extra')
        ]);
        $section->add_option('restore', [
            'name' => 'restore',
            'label' => __('Transfer Plugin', 'wp-extra'),
            'css' => ['hide_class' => 'pro']
        ]);
    }
        
        if (ClassEXtra::isPro()) {
            $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M9 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM9 16a4.002 4.002 0 003.8-2.75H15V16h2.5v-2.75H19v-2.5h-6.2A4.002 4.002 0 005 12a4 4 0 004 4z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>'.__('License'));
            $section = $tab->add_section(__('License'), ['slug' => true, 'description' => __('The plugin activation status.')]);
            $section->add_option('license', [
                'name' => 'license_key',
                'label' => __('Activation Key:'),
                'description' => 'https://account.wpvnteam.com/checkout/?edd_action=add_to_cart&download_id=' . WPEXPRO_ITEM_ID,
                'store_url' => 'https://account.wpvnteam.com',
                'item_id'   => WPEXPRO_ITEM_ID,
                'item_name' => WPEXPRO_ITEM_NAME,
                'version' 	=> WPEXPRO_VERSION,
                'file' => WPEXPRO_FILE,
            ]);
        }
        $settings->make();
        
    }

}