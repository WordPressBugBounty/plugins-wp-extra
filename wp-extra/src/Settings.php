<?php
namespace WPEXtra;

if (!defined('ABSPATH')) {
    exit;
}

use WPEXtra\WPSettings\SMTP;
use WPEXtra\WPSettings\EmailLogsPage;
use WPEXtra\WPSettings\ProtectHtaccess;

class Settings
{
    public function __construct()
    {
        add_filter('wp_settings_option_type_map', function ($options) {
            $options['smtp'] = SMTP::class;
            $options['protect'] = ProtectHtaccess::class;
            return $options;
        });
        new EmailLogsPage();
        add_action('admin_menu', [$this, 'register'], 10);
        add_action('admin_init', [$this, 'register'], 10);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function register()
    {
        $settings = new \WPVNTeam\WPSettings\WPSettings('WP EXtra');
        $settings->set_capability('manage_options');
        $settings->set_menu_icon('dashicons-superhero-alt');
        $settings->set_menu_position(80);
        $settings->set_version(WPEX_VERSION);
        $settings->set_plugin_data(WPEX_FILE);

        $add_days = (new \DateTime())->diff(new \DateTime('2019-01-18'))->days;
        $notice_message = sprintf(
            /* translators: 1. days; 2. link to donate; 3. link to review */
            __('The plugin developer has dedicated <strong>%1$s</strong> days to this project. If you like it, you can support the author with <a href="%2$s" target="_blank">a beer 🍻 / coffee ☕️</a> ! Please <a href="%3$s" target="_blank">rate us on WordPress.org</a>', 'wp-extra'),
            number_format_i18n($add_days),
            'https://wpvnteam.com/donate/',
            'https://wordpress.org/support/plugin/wp-extra/reviews/?filter=5#new-post'
        );
        $plugin_message = "<p>" . esc_html__('Like this plugin? Check out our other WordPress products:', 'wp-extra') . "</p><a class='thickbox open-plugin-details-modal' href='" . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=ux-flat&from=import&TB_iframe=true&width=800&height=550')) . "'>UX Flat</a> - " . esc_html__('Create new elements for Flatsome', 'wp-extra');

        $sidebar_items = [
            __('Write a review for WP EXtra', 'wp-extra') . ' 📝' => $notice_message,
            __('Our WordPress Products', 'wp-extra') . ' ⭐' => $plugin_message,
        ];
        $settings->set_sidebar($sidebar_items);

        // Tab 0: Modules Overview
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M10.5 4v4h3V4H15v4h1.5a1 1 0 011 1v4l-3 4v2a1 1 0 01-1 1h-3a1 1 0 01-1-1v-2l-3-4V9a1 1 0 011-1H9V4h1.5zm.5 12.5v2h2v-2l3-4v-3H8v3l3 4z"></path></svg>' . __('Modules'));
        $section = $tab->add_section(__('Modules'), ['description' => __('The module operates independently. Please enable it as needed.', 'wp-extra')]);
        $module_options = [
            'dashboard' => [
                'title' => __('Dashboard', 'wp-extra'),
                'desc' => __('Widgets cleanup, system info cards & custom notice', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M18 5.5H6a.5.5 0 00-.5.5v3h13V6a.5.5 0 00-.5-.5zm.5 5H10v8h8a.5.5 0 00.5-.5v-7.5zm-10 0h-3V18a.5.5 0 00.5.5h2.5v-8zM6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>'
            ],
            'posts' => [
                'title' => __('Posts & Writing', 'wp-extra'),
                'desc' => __('Classic Editor, featured image column & cleanup', 'wp-extra'),
                'icon' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true" focusable="false"><path d="M18 5.5H6a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h12a.5.5 0 0 0 .5-.5V6a.5.5 0 0 0-.5-.5ZM6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm1 5h1.5v1.5H7V9Zm1.5 4.5H7V15h1.5v-1.5ZM10 9h7v1.5h-7V9Zm7 4.5h-7V15h7v-1.5Z"></path></svg>'
            ],
            'toc' => [
                'title' => __('Table of Contents', 'wp-extra'),
                'desc' => __('Auto Table of Contents, headings numbering & sticky badge', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"></path></svg>'
            ],
            'duplicate' => [
                'title' => __('Clone Content', 'wp-extra'),
                'desc' => __('1-click duplicate posts, pages & taxonomies', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M5 4.5h11a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5H5a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5ZM3 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm17 3v10.75c0 .69-.56 1.25-1.25 1.25H6v1.5h12.75a2.75 2.75 0 0 0 2.75-2.75V8H20Z"></path></svg>'
            ],
            'media' => [
                'title' => __('Media & SVG', 'wp-extra'),
                'desc' => __('SVG uploads, auto WebP/JPG conversion & SEO rename', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="m7 6.5 4 2.5-4 2.5z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="m5 3c-1.10457 0-2 .89543-2 2v14c0 1.1046.89543 2 2 2h14c1.1046 0 2-.8954 2-2v-14c0-1.10457-.8954-2-2-2zm14 1.5h-14c-.27614 0-.5.22386-.5.5v10.7072l3.62953-2.6465c.25108-.1831.58905-.1924.84981-.0234l2.92666 1.8969 3.5712-3.4719c.2911-.2831.7545-.2831 1.0456 0l2.9772 2.8945v-9.3568c0-.27614-.2239-.5-.5-.5zm-14.5 14.5v-1.4364l4.09643-2.987 2.99567 1.9417c.2936.1903.6798.1523.9307-.0917l3.4772-3.3806 3.4772 3.3806.0228-.0234v2.5968c0 .2761-.2239.5-.5.5h-14c-.27614 0-.5-.2239-.5-.5z"></path></svg>'
            ],
            'comments' => [
                'title' => __('Comments & Spam', 'wp-extra'),
                'desc' => __('Disable comments globally & anti-spam link filter', 'wp-extra'),
                'icon' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.68822 16.625L5.5 17.8145L5.5 5.5L18.5 5.5L18.5 16.625L6.68822 16.625ZM7.31 18.125L19 18.125C19.5523 18.125 20 17.6773 20 17.125L20 5C20 4.44772 19.5523 4 19 4H5C4.44772 4 4 4.44772 4 5V19.5247C4 19.8173 4.16123 20.086 4.41935 20.2237C4.72711 20.3878 5.10601 20.3313 5.35252 20.0845L7.31 18.125ZM16 9.99997H8V8.49997H16V9.99997ZM8 14H13V12.5H8V14Z"></path></svg>'
            ],
            'logins' => [
                'title' => __('Branding & Login', 'wp-extra'),
                'desc' => __('Custom login URL, themes, Turnstile captcha & security', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M4 20h8v-1.5H4V20zM18.9 3.5c-.6-.6-1.5-.6-2.1 0l-7.2 7.2c-.4-.1-.7 0-1.1.1-.5.2-1.5.7-1.9 2.2-.4 1.7-.8 2.2-1.1 2.7-.1.1-.2.3-.3.4l-.6 1.1H6c2 0 3.4-.4 4.7-1.4.8-.6 1.2-1.4 1.3-2.3 0-.3 0-.5-.1-.7L19 5.7c.5-.6.5-1.6-.1-2.2zM9.7 14.7c-.7.5-1.5.8-2.4 1 .2-.5.5-1.2.8-2.3.2-.6.4-1 .8-1.1.5-.1 1 .1 1.3.3.2.2.3.5.2.8 0 .3-.1.9-.7 1.3z"></path></svg>'
            ],
            'admins' => [
                'title' => __('Permission & Roles', 'wp-extra'),
                'desc' => __('Hide admin bar, menu restrictions & hide plugins', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M15.5 9.5a1 1 0 100-2 1 1 0 000 2zm0 1.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm-2.25 6v-2a2.75 2.75 0 00-2.75-2.75h-4A2.75 2.75 0 003.75 15v2h1.5v-2c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v2h1.5zm7-2v2h-1.5v-2c0-.69-.56-1.25-1.25-1.25H15v-1.5h2.5A2.75 2.75 0 0120.25 15zM9.5 8.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" fill-rule="evenodd"></path></svg>'
            ],
            'security' => [
                'title' => __('Security & Hardening', 'wp-extra'),
                'desc' => __('Disable XML-RPC, REST API, file editors & update locks', 'wp-extra'),
                'icon' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true" focusable="false"><path d="M17 10h-1.2V7c0-2.1-1.7-3.8-3.8-3.8-2.1 0-3.8 1.7-3.8 3.8v3H7c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h10c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zM9.8 7c0-1.2 1-2.2 2.2-2.2 1.2 0 2.2 1 2.2 2.2v3H9.8V7zm6.7 11.5h-9v-7h9v7z"></path></svg>'
            ],
            'optimize' => [
                'title' => __('Speed & Optimize', 'wp-extra'),
                'desc' => __('Disable emojis, block styles & strip query strings', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M3.445 16.505a.75.75 0 001.06.05l5.005-4.55 4.024 3.521 4.716-4.715V14h1.5V8.25H14v1.5h3.19l-3.724 3.723L9.49 9.995l-5.995 5.45a.75.75 0 00-.05 1.06z"></path></svg>'
            ],
            'permalinks' => [
                'title' => __('Permalinks & SEO', 'wp-extra'),
                'desc' => __('Remove category slugs, external links SEO & robots.txt', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M12.5 14.5h-1V16h1c2.2 0 4-1.8 4-4s-1.8-4-4-4h-1v1.5h1c1.4 0 2.5 1.1 2.5 2.5s-1.1 2.5-2.5 2.5zm-4 1.5v-1.5h-1C6.1 14.5 5 13.4 5 12s1.1-2.5 2.5-2.5h1V8h-1c-2.2 0-4 1.8-4 4s1.8 4 4 4h1zm-1-3.2h5v-1.5h-5v1.5zM18 4H9c-1.1 0-2 .9-2 2v.5h1.5V6c0-.3.2-.5.5-.5h9c.3 0 .5.2.5.5v12c0 .3-.2.5-.5.5H9c-.3 0-.5-.2-.5-.5v-.5H7v.5c0 1.1.9 2 2 2h9c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"></path></svg>'
            ],
            'code' => [
                'title' => __('Custom Code & CSS', 'wp-extra'),
                'desc' => __('Header/body/footer scripts & responsive custom CSS', 'wp-extra'),
                'icon' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="20" height="20" aria-hidden="true" focusable="false"><path d="M4.8 11.4H2.1V9H1v6h1.1v-2.6h2.7V15h1.1V9H4.8v2.4zm1.9-1.3h1.7V15h1.1v-4.9h1.7V9H6.7v1.1zM16.2 9l-1.5 2.7L13.3 9h-.9l-.8 6h1.1l.5-4 1.5 2.8 1.5-2.8.5 4h1.1L17 9h-.8zm3.8 5V9h-1.1v6h3.6v-1H20z"></path></svg>'
            ],
            'cookie' => [
                'title' => __('Cookie Consent', 'wp-extra'),
                'desc' => __('GDPR/CCPA cookie banner with modern presets', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20" aria-hidden="true" focusable="false"><path d="M497.1 286.7c-3.4-4.6-8.5-7.6-14.1-8.3-73.7-9.1-129.2-71.4-129.2-145.1 0-24.7 6.4-49.2 18.5-70.8 2.8-4.9 3.3-10.8 1.6-16.2s-5.7-9.8-10.8-12.2C330 18.8 294.7 11 258.1 11c-136.2 0-247 109.9-247 245s110.8 245 247.1 245c118.2 0 220.2-83.5 242.6-198.5 1-5.5-.3-11.2-3.7-15.8m-239 173.5c-113.5 0-205.9-91.6-205.9-204.2S144.6 51.8 258.1 51.8c23.5 0 46.4 3.9 68.2 11.5-9 22.2-13.7 46-13.7 70 0 86.5 59.9 160.8 142.7 181.4-25.7 85.4-105.6 145.5-197.2 145.5"/><ellipse cx="194.5" cy="150.8" rx="20.4" ry="20.3"/><ellipse cx="264.4" cy="230.7" rx="20.4" ry="20.3"/><ellipse cx="293.8" cy="340.2" rx="20.4" ry="20.3"/><ellipse cx="146.7" cy="304.3" rx="20.4" ry="20.3"/></svg>'
            ],
            'smtp' => [
                'title' => __('SMTP Mailer', 'wp-extra'),
                'desc' => __('Multi-account rotation, provider presets & email logs', 'wp-extra'),
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4.5 4.5" width="20" height="20" aria-hidden="true" focusable="false"><path d="M4 0.75H0.5a0.25 0.25 0 0 0 -0.25 0.25v2.5a0.25 0.25 0 0 0 0.25 0.25h3.5a0.25 0.25 0 0 0 0.25 -0.25V1a0.25 0.25 0 0 0 -0.25 -0.25m-0.193 2.75H0.708l0.875 -0.905 -0.18 -0.174L0.5 3.355V1.19l1.554 1.546a0.25 0.25 0 0 0 0.352 0L4 1.151v2.188l-0.92 -0.92 -0.176 0.176ZM0.664 1h3.134L2.23 2.559Z"/></svg>'
            ],
        ];

        $section->add_option('module', [
            'name' => 'modules',
            'options' => $module_options,
            'label' => __('List Module', 'wp-extra')
        ]);

        // 1. Dashboard Tab
        if (self::get_option('modules') && in_array('dashboard', (array) self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M18 5.5H6a.5.5 0 00-.5.5v3h13V6a.5.5 0 00-.5-.5zm.5 5H10v8h8a.5.5 0 00.5-.5v-7.5zm-10 0h-3V18a.5.5 0 00.5.5h2.5v-8zM6 4h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2z"></path></svg>' . __('Dashboard', 'wp-extra'));

            // Roles helper
            $all_roles = wp_roles()->get_names();

            // Section 1: Dashboard Widgets & Cleanup
            $section = $tab->add_section(__('Dashboard Widgets & Cleanup', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'dashboard',
                'label' => __('Clean All Default Widgets & Welcome Panel', 'wp-extra'),
                'description' => __('Hide all standard WordPress and plugin dashboard widgets including the Welcome Panel at once.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'dashboard_sysinfo',
                'label' => __('Enable System Info Widget', 'wp-extra'),
                'description' => __('Display a dashboard widget with live PHP, Memory, Upload Size, and Server stats (Visible to Administrators only).', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'tab_help',
                'label' => __('Hide Help Tabs', 'wp-extra'),
                'description' => __('Remove the Help dropdown tab from the top right of admin screens.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'tab_screen',
                'label' => __('Hide Screen Options', 'wp-extra'),
                'description' => __('Remove the Screen Options dropdown tab from the top right of admin screens.', 'wp-extra')
            ]);

            // Section 2: Custom Notice Widgets (Repeater)
            $role_options = array_merge(['all' => __('All User Roles', 'wp-extra')], $all_roles);

            $section = $tab->add_section(__('Custom Notice Widgets', 'wp-extra'));
            $section->add_option('repeater', [
                'name' => 'dashboard_custom_widgets',
                'label' => __('Custom Widgets List', 'wp-extra'),
                'description' => __('Add custom notice widgets to the dashboard screen.', 'wp-extra'),
                'button_text' => __('Add New Widget', 'wp-extra'),
                'fields' => [
                    [
                        'name' => 'title',
                        'label' => __('Widget Title', 'wp-extra'),
                        'type' => 'text',
                        'placeholder' => __('e.g. Admin Guidelines / System Notice', 'wp-extra'),
                    ],
                    [
                        'name' => 'role',
                        'label' => __('Display For', 'wp-extra'),
                        'type' => 'select',
                        'options' => $role_options,
                        'default' => 'all',
                    ],
                    [
                        'name' => 'content',
                        'label' => __('Content', 'wp-extra'),
                        'type' => 'editor',
                        'rows' => 6,
                        'full_width' => true,
                        'placeholder' => __('Enter text, HTML, or shortcodes...', 'wp-extra'),
                    ],
                ]
            ]);
        }

        // 2. Posts Tab
        if (self::get_option('modules') && in_array('posts', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M18 5.5H6a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h12a.5.5 0 0 0 .5-.5V6a.5.5 0 0 0-.5-.5ZM6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm1 5h1.5v1.5H7V9Zm1.5 4.5H7V15h1.5v-1.5ZM10 9h7v1.5h-7V9Zm7 4.5h-7V15h7v-1.5Z"></path></svg>' . __('Posts'));

            $section = $tab->add_section(__('Editor & Writing', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'mce_classic',
                'label' => __('Classic Editor'),
                'description' => __('Use the classic WordPress editor instead of block editor.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'mce_plugins',
                'label' => __('TinyMCE Power Tools', 'wp-extra'),
                'description' => __('Enable full toolbar tools: justify, unlinks, letter spacing, tables, visual blocks, search & replace, rel=nofollow & sponsored.', 'wp-extra'),
                'show_if' => ['mce_classic' => true]
            ]);
            $section->add_option('checkbox', [
                'name' => 'signature',
                'label' => __('Post Signature', 'wp-extra'),
                'description' => sprintf(__('Insert signature into single posts using shortcode %1$s or automatically.', 'wp-extra'), '<code>[signature]</code>')
            ]);
            $section->add_option('wp-editor', [
                'name' => 'signature_content',
                'teeny' => true,
                'show_if' => ['signature' => true],
                'label' => __('Signature Content', 'wp-extra')
            ]);
            $section->add_option('choices', [
                'name' => 'signature_pos',
                'options' => [
                    '' => __('No'),
                    'top' => __('Top'),
                    'bottom' => __('Bottom')
                ],
                'label' => __('Auto Insert Position', 'wp-extra'),
                'show_if' => ['signature' => true]
            ]);
            if (wp_get_theme()->template !== 'flatsome') {
                $section->add_option('checkbox', [
                    'name' => 'classic_widget',
                    'label' => __('Classic Widgets', 'wp-extra'),
                    'description' => __('Restore classic widget management screens.', 'wp-extra')
                ]);
                $section->add_option('widget', [
                    'name' => 'disable_widget',
                    'label' => __('Disable Sidebar Widgets', 'wp-extra'),
                ]);
            }

            $section = $tab->add_section(__('Publishing & List Views', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'publish_btn',
                'label' => __('Sticky Publish Button', 'wp-extra'),
                'default' => 1,
                'description' => __('Stick the Publish/Save button to the bottom of the screen when scrolling long posts.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'delete_attached',
                'label' => __('Delete Attached Media', 'wp-extra'),
                'description' => __('Automatically remove images attached to a post when the post is permanently deleted.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'post_revisions',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Post Revisions Limit', 'wp-extra'),
                'description' => __('Maximum revisions to keep per post. Enter 0 to disable revisions completely, or leave empty to use WordPress default.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'img_column',
                'label' => __('Featured Image Column', 'wp-extra'),
                'description' => __('Show a thumbnail preview column in post admin list tables.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'show_modified',
                'label' => __('Show Modified Date', 'wp-extra'),
                'description' => __('Display a sortable last modified date column in admin post lists.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'lock_modified',
                'label' => __('Lock Modified Date', 'wp-extra'),
                'description' => __('Prevent updating the modified date when editing existing posts.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'disable_tags',
                'label' => __('Disable Tags Globally', 'wp-extra'),
                'description' => __('Completely disable post tags, remove tag admin menus & metaboxes, and block tag archives.', 'wp-extra')
            ]);
        }

        // Tab Table of Contents
        if (self::get_option('modules') && in_array('toc', (array) self::get_option('modules'), true)) {
            $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"></path></svg>' . __('Table of Contents', 'wp-extra'));

            // SECTION 1: Auto Insert & Positioning
            $section = $tab->add_section(__('Auto Insert & Positioning', 'wp-extra'));

            $post_types = get_post_types(['public' => true], 'objects');
            $post_type_options = [];
            foreach ($post_types as $pt) {
                if ($pt->name !== 'attachment') {
                    $post_type_options[$pt->name] = esc_html($pt->labels->name);
                }
            }

            $section->add_option('checkbox-multiple', [
                'name' => 'enabled_post_types',
                'label' => __('Enable Support', 'wp-extra'),
                'description' => __('Enable table of contents support for these post types.', 'wp-extra'),
                'options' => $post_type_options,
                'default' => ['post']
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'auto_insert_post_types',
                'label' => __('Auto Insert', 'wp-extra'),
                'description' => __('Automatically insert table of contents without manual shortcode.', 'wp-extra'),
                'options' => $post_type_options,
                'default' => ['post']
            ]);
            $section->add_option('select', [
                'name' => 'position',
                'label' => __('Position', 'wp-extra'),
                'options' => [
                    'before' => __('Before first heading (Recommended)', 'wp-extra'),
                    'after' => __('After first heading', 'wp-extra'),
                    'top' => __('Top of content', 'wp-extra'),
                    'bottom' => __('Bottom of content', 'wp-extra'),
                    'afterpara' => __('After first paragraph', 'wp-extra'),
                    'afterimg' => __('After first image', 'wp-extra'),
                ],
                'default' => 'before'
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'start',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Show When', 'wp-extra'),
                'default' => 4,
                'description' => __('headings or more are present.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'heading_text',
                'label' => __('Header Label', 'wp-extra'),
                'default' => __('Table of Contents', 'wp-extra'),
                'description' => __('Header text displayed at the top of the table of contents.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'visibility',
                'label' => __('Toggle Visibility', 'wp-extra'),
                'description' => __('Allow readers to toggle table of contents visibility.', 'wp-extra'),
                'default' => true
            ]);
            $section->add_option('checkbox', [
                'name' => 'visibility_hide_by_default',
                'label' => __('Initially Hide', 'wp-extra'),
                'description' => __('Initially collapse the table of contents on page load.', 'wp-extra'),
                'default' => false,
                'show_if' => ['visibility' => true]
            ]);
            $section->add_option('choices', [
                'name' => 'counter',
                'label' => __('Counter Style', 'wp-extra'),
                'options' => [
                    'none' => [
                        'label' => __('None', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><rect x="18" y="20" width="60" height="8" rx="3" fill="#334155"/><rect x="26" y="34" width="50" height="7" rx="3" fill="#64748B"/><rect x="26" y="47" width="44" height="7" rx="3" fill="#64748B"/><rect x="18" y="60" width="56" height="8" rx="3" fill="#334155"/></svg>',
                        'description' => __('Minimal pure text (Default)', 'wp-extra'),
                    ],
                    'decimal' => [
                        'label' => __('Decimal', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><rect x="14" y="18" width="3.5" height="12" rx="1.75" fill="#2271B1"/><rect x="23" y="20" width="55" height="8" rx="3" fill="#334155"/><rect x="23" y="37" width="2.5" height="9" rx="1.25" fill="#64748B"/><rect x="30" y="38" width="45" height="7" rx="3" fill="#64748B"/><rect x="23" y="51" width="2.5" height="9" rx="1.25" fill="#64748B"/><rect x="30" y="52" width="50" height="7" rx="3" fill="#64748B"/></svg>',
                        'description' => __('Numbered 1, 1.1, 1.2...', 'wp-extra'),
                    ],
                    'decimal-circle' => [
                        'label' => __('Decimal Circle', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><circle cx="20" cy="24" r="6.5" fill="#0284C7"/><text x="20" y="27" fill="#FFFFFF" font-size="8" font-weight="bold" font-family="sans-serif" text-anchor="middle">1</text><rect x="31" y="20" width="55" height="8" rx="3" fill="#334155"/><rect x="17" y="37" width="17" height="10" rx="5" fill="#F1F5F9" stroke="#E2E8F0" stroke-width="1"/><text x="25.5" y="44.5" fill="#475569" font-size="7" font-weight="bold" font-family="sans-serif" text-anchor="middle">1.1</text><rect x="38" y="38" width="42" height="7" rx="3" fill="#64748B"/><rect x="17" y="51" width="17" height="10" rx="5" fill="#F1F5F9" stroke="#E2E8F0" stroke-width="1"/><text x="25.5" y="58.5" fill="#475569" font-size="7" font-weight="bold" font-family="sans-serif" text-anchor="middle">1.2</text><rect x="38" y="52" width="48" height="7" rx="3" fill="#64748B"/></svg>',
                        'description' => __('Numbers in rounded badge', 'wp-extra'),
                    ],
                    'disc' => [
                        'label' => __('Disc', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><line x1="20" y1="26" x2="20" y2="55" stroke="#CBD5E1" stroke-width="1.5"/><line x1="20" y1="41" x2="27" y2="41" stroke="#CBD5E1" stroke-width="1.5"/><line x1="20" y1="55" x2="27" y2="55" stroke="#CBD5E1" stroke-width="1.5"/><circle cx="20" cy="24" r="3.5" fill="#2271B1"/><rect x="30" y="20" width="55" height="8" rx="3" fill="#334155"/><circle cx="30" cy="41" r="2.5" fill="#94A3B8"/><rect x="37" y="38" width="42" height="7" rx="3" fill="#64748B"/><circle cx="30" cy="55" r="2.5" fill="#94A3B8"/><rect x="37" y="52" width="48" height="7" rx="3" fill="#64748B"/></svg>',
                        'description' => __('Bullet dot • with guide line', 'wp-extra'),
                    ],
                    'checkmark' => [
                        'label' => __('Checkmark', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><circle cx="20" cy="24" r="5.5" fill="#D1FAE5"/><path d="M17.5 24L19.5 26L22.5 22" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><rect x="31" y="20" width="55" height="8" rx="3" fill="#334155"/><circle cx="25" cy="41" r="4.5" fill="#ECFDF5"/><path d="M23 41L24.5 42.5L27 39.5" stroke="#10B981" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/><rect x="35" y="38" width="45" height="7" rx="3" fill="#64748B"/><circle cx="25" cy="55" r="4.5" fill="#ECFDF5"/><path d="M23 55L24.5 56.5L27 53.5" stroke="#10B981" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/><rect x="35" y="52" width="48" height="7" rx="3" fill="#64748B"/></svg>',
                        'description' => __('Circle checkmark ✔ badge', 'wp-extra'),
                    ],
                ],
                'default' => 'none',
            ]);

            // SECTION 2: Appearance & Customization
            $section = $tab->add_section(__('Appearance & Customization', 'wp-extra'));
            $section->add_option('choices', [
                'name' => 'title_icon_style',
                'label' => __('Title Icon Type', 'wp-extra'),
                'options' => [
                    'none' => [
                        'label' => __('None', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><circle cx="60" cy="40" r="14" stroke="#94A3B8" stroke-width="2"/><line x1="50" y1="50" x2="70" y2="30" stroke="#94A3B8" stroke-width="2"/></svg>',
                        'description' => __('No icon before title (Default)', 'wp-extra'),
                    ],
                    'toc_tree' => [
                        'label' => __('TOC Tree', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><circle cx="36" cy="26" r="3.5" fill="#2271B1"/><rect x="45" y="23" width="40" height="6" rx="3" fill="#334155"/><circle cx="44" cy="38" r="3" fill="#64748B"/><rect x="52" y="35.5" width="30" height="5" rx="2.5" fill="#64748B"/><circle cx="36" cy="50" r="3.5" fill="#2271B1"/><rect x="45" y="47" width="40" height="6" rx="3" fill="#334155"/><circle cx="44" cy="61" r="3" fill="#64748B"/><rect x="52" y="58.5" width="28" height="5" rx="2.5" fill="#64748B"/></svg>',
                        'description' => __('Hierarchical nested list tree', 'wp-extra'),
                    ],
                    'list' => [
                        'label' => __('Index List', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><circle cx="36" cy="27" r="3" fill="#2271B1"/><line x1="46" y1="27" x2="84" y2="27" stroke="#2271B1" stroke-width="3" stroke-linecap="round"/><circle cx="36" cy="40" r="3" fill="#334155"/><line x1="46" y1="40" x2="84" y2="40" stroke="#334155" stroke-width="3" stroke-linecap="round"/><circle cx="36" cy="53" r="3" fill="#334155"/><line x1="46" y1="53" x2="84" y2="53" stroke="#334155" stroke-width="3" stroke-linecap="round"/></svg>',
                        'description' => __('Classic index ☰ list icon', 'wp-extra'),
                    ],
                    'bookmark' => [
                        'label' => __('Bookmark', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><path d="M68 56L60 50L52 56V24H68V56Z" fill="#2271B1" stroke="#2271B1" stroke-width="2" stroke-linejoin="round"/></svg>',
                        'description' => __('Reading bookmark 🔖 ribbon', 'wp-extra'),
                    ],
                    'book' => [
                        'label' => __('Open Book', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><path d="M42 53C47 50 55 50 60 54C65 50 73 50 78 53V29C73 26 65 26 60 30C55 26 47 26 42 29V53Z" stroke="#2271B1" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><line x1="60" y1="30" x2="60" y2="54" stroke="#2271B1" stroke-width="3"/></svg>',
                        'description' => __('Open book 📖 pages', 'wp-extra'),
                    ],
                    'layers' => [
                        'label' => __('Layers', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><path d="M60 22L36 33L60 44L84 33L60 22Z" fill="#2271B1"/><path d="M36 43L60 54L84 43" stroke="#334155" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M36 53L60 64L84 53" stroke="#64748B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        'description' => __('Layer stack ☷', 'wp-extra'),
                    ],
                    'sparkle' => [
                        'label' => __('Sparkle', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><path d="M60 20L63.5 35L78 38.5L63.5 42L60 57L56.5 42L42 38.5L56.5 35L60 20Z" fill="#0284C7"/><circle cx="76" cy="24" r="3" fill="#38BDF8"/><circle cx="44" cy="54" r="2" fill="#38BDF8"/></svg>',
                        'description' => __('Modern star sparkle ✦', 'wp-extra'),
                    ],
                    'settings' => [
                        'label' => __('Settings', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><circle cx="60" cy="40" r="9" stroke="#2271B1" stroke-width="4"/><circle cx="60" cy="40" r="4" fill="#334155"/></svg>',
                        'description' => __('Gear cog ⚙', 'wp-extra'),
                    ],
                    'bullet' => [
                        'label' => __('Bullet', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><circle cx="60" cy="40" r="10" fill="#2271B1"/></svg>',
                        'description' => __('Solid circle dot ●', 'wp-extra'),
                    ],
                ],
                'default' => 'none'
            ]);
            $section->add_option('choices', [
                'name' => 'toggle_icon_style',
                'label' => __('Toggle Button Icon', 'wp-extra'),
                'options' => [
                    'chevron' => [
                        'label' => __('Chevron (⌄ / ⌃)', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><rect x="24" y="36" width="48" height="8" rx="3" fill="#334155"/><polyline points="84 36 90 42 96 36" stroke="#2271B1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        'description' => __('Modern folding chevron (Default)', 'wp-extra'),
                    ],
                    'list' => [
                        'label' => __('List Menu (☰)', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><rect x="24" y="36" width="48" height="8" rx="3" fill="#334155"/><line x1="84" y1="34" x2="96" y2="34" stroke="#2271B1" stroke-width="2" stroke-linecap="round"/><line x1="84" y1="40" x2="96" y2="40" stroke="#2271B1" stroke-width="2" stroke-linecap="round"/><line x1="84" y1="46" x2="96" y2="46" stroke="#2271B1" stroke-width="2" stroke-linecap="round"/></svg>',
                        'description' => __('Classic hamburger menu icon', 'wp-extra'),
                    ],
                    'caret' => [
                        'label' => __('Caret (▼ / ▲)', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><rect x="24" y="36" width="48" height="8" rx="3" fill="#334155"/><path d="M85 36L90 44L95 36Z" fill="#2271B1"/></svg>',
                        'description' => __('Solid arrow caret', 'wp-extra'),
                    ],
                    'plus_minus' => [
                        'label' => __('Plus / Minus (+ / −)', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><rect x="24" y="36" width="48" height="8" rx="3" fill="#334155"/><line x1="84" y1="40" x2="96" y2="40" stroke="#2271B1" stroke-width="2.5" stroke-linecap="round"/><line x1="90" y1="34" x2="90" y2="46" stroke="#2271B1" stroke-width="2.5" stroke-linecap="round"/></svg>',
                        'description' => __('Accordion expandable + / −', 'wp-extra'),
                    ],
                    'grid' => [
                        'label' => __('Grid Dots (☷)', 'wp-extra'),
                        'svg' => '<svg viewBox="0 0 120 80" width="120" height="80" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="120" height="80" rx="8" fill="#F8FAFC" stroke="#E2E8F0" stroke-width="1.5"/><rect x="24" y="36" width="48" height="8" rx="3" fill="#334155"/><circle cx="86" cy="36" r="2" fill="#2271B1"/><circle cx="94" cy="36" r="2" fill="#2271B1"/><circle cx="86" cy="44" r="2" fill="#2271B1"/><circle cx="94" cy="44" r="2" fill="#2271B1"/></svg>',
                        'description' => __('Four dot matrix pattern', 'wp-extra'),
                    ],
                ],
                'default' => 'chevron',
                'show_if' => ['visibility' => true]
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'font_size',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Font Size', 'wp-extra'),
                'default' => 14,
                'description' => 'px'
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'title_font_size',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Title Font Size', 'wp-extra'),
                'default' => 16,
                'description' => 'px'
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'child_font_size',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Sub-heading Font Size', 'wp-extra'),
                'default' => 13,
                'description' => 'px'
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'child_indent',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Sub-heading Indent', 'wp-extra'),
                'default' => 40,
                'placeholder' => '40',
                'description' => __('px (indentation distance for nested headings H3, H4, H5... Set 0 for flat list).', 'wp-extra')
            ]);
            $section->add_option('select', [
                'name' => 'subheading_collapse_mode',
                'label' => __('Default Heading State', 'wp-extra'),
                'options' => [
                    'open_first' => __('1. Open First Item Only (Default)', 'wp-extra'),
                    'open_all' => __('2. Open All', 'wp-extra'),
                    'collapse_all' => __('3. Collapse All', 'wp-extra'),
                ],
                'default' => 'open_first',
                'description' => __('Initial display state of nested sub-headings on page load.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'width_custom',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Custom Width', 'wp-extra'),
                'placeholder' => '320',
                'description' => __('px (leave blank for auto 100%)', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'container_padding',
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Container Padding', 'wp-extra'),
                'placeholder' => '16px',
                'description' => __('Padding for outer container (#wptoc-container). Enter CSS values like 16px or 14px 18px.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'nav_padding',
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Nav Padding', 'wp-extra'),
                'placeholder' => '12px 16px',
                'description' => __('Padding for list wrapper (#wptoc-container nav). Enter CSS values like 12px 16px.', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_background_colour',
                'label' => __('Container Background', 'wp-extra'),
                'default' => '',
                'description' => __('Background color of the entire container (leave blank for default).', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_header_background_colour',
                'label' => __('Header Background', 'wp-extra'),
                'default' => '',
                'description' => __('Background color of the header bar (leave blank for default).', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_border_colour',
                'label' => __('Border Color', 'wp-extra'),
                'default' => '',
                'description' => __('Border color of the container (leave blank for default).', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'custom_border_width',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Border Width', 'wp-extra'),
                'placeholder' => '1',
                'description' => __('px (border thickness, leave blank for default)', 'wp-extra')
            ]);
            $section->add_option('select', [
                'name' => 'custom_border_style',
                'label' => __('Border Style', 'wp-extra'),
                'options' => [
                    'solid' => __('Solid', 'wp-extra'),
                    'dashed' => __('Dashed', 'wp-extra'),
                    'dotted' => __('Dotted', 'wp-extra'),
                    'none' => __('None', 'wp-extra'),
                ],
                'default' => 'solid'
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'custom_border_radius',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Border Radius', 'wp-extra'),
                'placeholder' => '12',
                'description' => __('px (rounded corners radius, leave blank for default)', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_title_colour',
                'label' => __('Title Color', 'wp-extra'),
                'default' => '',
                'description' => __('Leave blank to use layout default.', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_title_icon_colour',
                'label' => __('Title Prefix Icon Color', 'wp-extra'),
                'default' => '',
                'description' => __('Color of the icon before table of contents title.', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_link_colour',
                'label' => __('Link Color', 'wp-extra'),
                'default' => '',
                'description' => __('Leave blank to use layout default.', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_link_hover_colour',
                'label' => __('Hover & Active Accent Color', 'wp-extra'),
                'default' => '',
                'description' => __('Accent color when hovering or active scrollspy heading.', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_counter_colour',
                'label' => __('Counter & Bullet Color', 'wp-extra'),
                'default' => '',
                'description' => __('Default color of counter numbers, bullet dots or checkmarks.', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_button_background_colour',
                'label' => __('Button Background', 'wp-extra'),
                'default' => '',
                'description' => __('Background color for toggle buttons (transparent by default).', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_button_hover_background_colour',
                'label' => __('Button Hover Background', 'wp-extra'),
                'default' => '',
                'description' => __('Background color when hovering over buttons.', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'custom_button_colour',
                'label' => __('Button Icon Color', 'wp-extra'),
                'default' => '',
                'description' => __('Icon color of toggle buttons.', 'wp-extra')
            ]);

            // SECTION 3: Headings & Rules
            $section = $tab->add_section(__('Headings & Rules', 'wp-extra'));
            $section->add_option('checkbox-multiple', [
                'name' => 'heading_levels',
                'label' => __('Heading Levels', 'wp-extra'),
                'options' => [
                    '1' => __('Heading 1 (h1)', 'wp-extra'),
                    '2' => __('Heading 2 (h2)', 'wp-extra'),
                    '3' => __('Heading 3 (h3)', 'wp-extra'),
                    '4' => __('Heading 4 (h4)', 'wp-extra'),
                    '5' => __('Heading 5 (h5)', 'wp-extra'),
                    '6' => __('Heading 6 (h6)', 'wp-extra'),
                ],
                'default' => ['1', '2', '3', '4', '5', '6'],
                'description' => __('Select heading levels to include in the table of contents.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'exclude',
                'label' => __('Exclude Headings', 'wp-extra'),
                'placeholder' => 'Introduction*|Comments|References',
                'description' => __('Specify headings to exclude separated by pipe | (supports wildcard *).', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'exclude_by_class',
                'label' => __('Exclude by CSS Class', 'wp-extra'),
                'placeholder' => 'no-toc, ignore-heading',
                'description' => __('Enter CSS classes of headings to exclude, separated by comma.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'exclude_post_ids',
                'label' => __('Exclude Posts by ID', 'wp-extra'),
                'placeholder' => '12, 45, 108',
                'description' => __('Enter post/page IDs to exclude from table of contents, separated by comma (,).', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'exclude_category_ids',
                'label' => __('Exclude Categories by ID', 'wp-extra'),
                'placeholder' => '3, 8, 21',
                'description' => __('Enter category IDs to exclude from table of contents, separated by comma (,).', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'include_homepage',
                'label' => __('Show on Homepage', 'wp-extra'),
                'description' => __('Show table of contents on homepage if eligible headings exist.', 'wp-extra'),
                'default' => false
            ]);
            $section->add_option('checkbox', [
                'name' => 'include_category',
                'label' => __('Show on Category Pages', 'wp-extra'),
                'default' => false
            ]);
            $section->add_option('checkbox', [
                'name' => 'include_product_category',
                'label' => __('Show in WooCommerce Product Categories', 'wp-extra'),
                'default' => false
            ]);

            // SECTION 4: Sticky TOC
            $section = $tab->add_section(__('Sticky TOC', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'sticky-toggle',
                'label' => __('Enable Sticky Floating Button (FAB)', 'wp-extra'),
                'description' => __('Display floating circular button when scrolling down, clicking opens table of contents drawer panel.', 'wp-extra'),
                'default' => true
            ]);
            $section->add_option('select', [
                'name' => 'sticky-toggle-position',
                'label' => __('Floating Button Position', 'wp-extra'),
                'options' => [
                    'bottom-left' => __('Bottom Left (Default)', 'wp-extra'),
                    'bottom-right' => __('Bottom Right', 'wp-extra'),
                    'bottom-center' => __('Bottom Center Badge (Edge attached)', 'wp-extra'),
                    'middle-left' => __('Vertical Left Badge (Edge attached)', 'wp-extra'),
                    'middle-right' => __('Vertical Right Badge (Edge attached)', 'wp-extra'),
                ],
                'default' => 'bottom-left',
                'description' => __('Floating button position (horizontal pill badge or vertical edge badge).', 'wp-extra'),
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('text', [
                'name' => 'sticky_button_text',
                'label' => __('Floating Button Text', 'wp-extra'),
                'default' => 'Contents',
                'placeholder' => 'Contents',
                'description' => __('Text label displayed on the floating sticky badge (e.g. "Contents"). Leave empty to display icon only.', 'wp-extra'),
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('color', [
                'name' => 'sticky_highlight_bg_colour',
                'label' => __('Active Background Color', 'wp-extra'),
                'default' => '#f0f6fc',
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('color', [
                'name' => 'sticky_highlight_title_colour',
                'label' => __('Active Text Color', 'wp-extra'),
                'default' => '#2271b1',
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'sticky_width',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Sticky Width', 'wp-extra'),
                'placeholder' => '270',
                'description' => __('px (leave blank for default 270px)', 'wp-extra'),
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'sticky_height',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Sticky Max Height', 'wp-extra'),
                'placeholder' => '500',
                'description' => __('px (leave blank for default auto)', 'wp-extra'),
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'sticky_scale',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Sticky Scale', 'wp-extra'),
                'default' => '90',
                'placeholder' => '90',
                'description' => __('% (e.g. 90 for 90%, leave blank for 100%)', 'wp-extra'),
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'sticky_mobile_width',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Sticky Mobile Width', 'wp-extra'),
                'placeholder' => '280',
                'description' => __('px (leave blank for default auto responsive)', 'wp-extra'),
                'show_if' => ['sticky-toggle' => true]
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'sticky_mobile_height',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Sticky Mobile Max Height', 'wp-extra'),
                'placeholder' => '400',
                'description' => __('px (leave blank for default auto responsive)', 'wp-extra'),
                'show_if' => ['sticky-toggle' => true]
            ]);

            // SECTION 5: Advanced & Performance
            $section = $tab->add_section(__('Advanced & Performance', 'wp-extra'));
            $section->add_option('select', [
                'name' => 'toc_loading',
                'label' => __('Loading Method', 'wp-extra'),
                'options' => [
                    'js' => __('JavaScript (Default - Full animation & smooth scroll)', 'wp-extra'),
                    'css' => __('Pure CSS (Super lightweight, no JS)', 'wp-extra'),
                ],
                'default' => 'js'
            ]);
            $section->add_option('checkbox', [
                'name' => 'inline_css',
                'label' => __('Inline CSS', 'wp-extra'),
                'description' => __('Inject CSS inline to reduce HTTP requests.', 'wp-extra'),
                'default' => false
            ]);
            $section->add_option('checkbox', [
                'name' => 'smooth_scroll',
                'label' => __('Smooth Scroll', 'wp-extra'),
                'description' => __('Scroll smoothly to anchor targets when clicking TOC links.', 'wp-extra'),
                'default' => true
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'smooth_scroll_offset',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Smooth Scroll Offset', 'wp-extra'),
                'default' => 30,
                'description' => 'px',
                'show_if' => ['smooth_scroll' => true]
            ]);
        }

        // 3. Clone / Duplicate Tab
        if (self::get_option('modules') && in_array('duplicate', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M5 4.5h11a.5.5 0 0 1 .5.5v11a.5.5 0 0 1-.5.5H5a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5ZM3 5a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm17 3v10.75c0 .69-.56 1.25-1.25 1.25H6v1.5h12.75a2.75 2.75 0 0 0 2.75-2.75V8H20Z"></path></svg>' . __('Clone', 'wp-extra'));

            $section = $tab->add_section(__('Duplication', 'wp-extra'));
            $section->add_option('checkbox-multiple', [
                'name' => 'duplicate',
                'options' => fn() => array_combine(
                    $ids = array_diff(get_post_types(['public' => true]), ['product', 'attachment']),
                    array_map(fn($id) => get_post_type_object($id)->label . " <code>$id</code>", $ids)
                ),
                'label' => __('Duplicate Post Types', 'wp-extra'),
                'description' => __('Add a 1-click Clone action to Posts, Pages, and Custom Post Types.', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'duplicate_tax',
                'options' => fn() => array_combine(
                    $ids = array_diff(get_taxonomies(['public' => true], 'names'), ['post_format']),
                    array_map(fn($id) => get_taxonomy($id)->label . " <code>$id</code>", $ids)
                ),
                'label' => __('Duplicate Taxonomies', 'wp-extra'),
                'description' => __('Add a Clone action to Categories, Tags, and Custom Taxonomies.', 'wp-extra')
            ]);
        }

        // 4. Media Tab
        if (self::get_option('modules') && in_array('media', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m7 6.5 4 2.5-4 2.5z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="m5 3c-1.10457 0-2 .89543-2 2v14c0 1.1046.89543 2 2 2h14c1.1046 0 2-.8954 2-2v-14c0-1.10457-.8954-2-2-2zm14 1.5h-14c-.27614 0-.5.22386-.5.5v10.7072l3.62953-2.6465c.25108-.1831.58905-.1924.84981-.0234l2.92666 1.8969 3.5712-3.4719c.2911-.2831.7545-.2831 1.0456 0l2.9772 2.8945v-9.3568c0-.27614-.2239-.5-.5-.5zm-14.5 14.5v-1.4364l4.09643-2.987 2.99567 1.9417c.2936.1903.6798.1523.9307-.0917l3.4772-3.3806 3.4772 3.3806.0228-.0234v2.5968c0 .2761-.2239.5-.5.5h-14c-.27614 0-.5-.2239-.5-.5z"></path></svg>' . __('Media'));

            $section = $tab->add_section(__('Image Optimization & Uploads', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'allow_filetype',
                'label' => __('Allow SVG Uploads', 'wp-extra'),
                'description' => __('Enable secure SVG file uploads in WordPress Media Library.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'autoupload',
                'label' => __('Auto Optimize Uploads', 'wp-extra'),
                'description' => __('Automatically resize and convert uploaded images to modern formats (WebP/JPG).', 'wp-extra')
            ]);
            $section->add_option('select', [
                'name' => 'autoconverter',
                'show_if' => ['autoupload' => true],
                'label' => __('Format Converter', 'wp-extra'),
                'options' => [
                    '' => __('No'),
                    'webp' => __('WebP (Recommended)', 'wp-extra'),
                    'jpg' => __('JPG')
                ]
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'image_max_width',
                'css' => ['input_class' => 'small-text'],
                'show_if' => ['autoupload' => true],
                'label' => __('Max Image Width', 'wp-extra'),
                'description' => __('px (E.g: 1920). Larger images are scaled down automatically.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'image_max_height',
                'css' => ['input_class' => 'small-text'],
                'show_if' => ['autoupload' => true],
                'label' => __('Max Image Height', 'wp-extra'),
                'description' => __('px (E.g: 1080). Larger images are scaled down automatically.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'image_limit',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Max File Size Limit', 'wp-extra'),
                'description' => __('KB (E.g: 2048 = 2MB). Prevent uploading overly large files.', 'wp-extra')
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
                'label' => __('Compression Quality', 'wp-extra'),
                'description' => '% ' . __('Default') . ': 90%'
            ]);

            $section = $tab->add_section(__('Remote Images & Featured Thumbnails', 'wp-extra'));
            $section->add_option('image', [
                'name' => 'media_default',
                'label' => __('Default Featured Image', 'wp-extra'),
                'description' => __('Fallback featured image used when a post has no thumbnail set.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'save_images',
                'label' => __('Auto Save External Images', 'wp-extra'),
                'description' => __('Automatically download remote images in post content to local Media Library upon publishing.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'autocrop',
                'show_if' => ['save_images' => true],
                'label' => __('Auto Crop Downloaded Images', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'crop_width',
                'css' => ['input_class' => 'small-text'],
                'show_if' => ['autocrop' => true],
                'label' => __('Crop Width', 'wp-extra'),
                'description' => 'px (E.g: 800)'
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'crop_height',
                'css' => ['input_class' => 'small-text'],
                'show_if' => ['autocrop' => true],
                'label' => __('Crop Height', 'wp-extra'),
                'description' => 'px (E.g: 600)'
            ]);
            $section->add_option('checkbox', [
                'name' => 'autoflip',
                'show_if' => ['save_images' => true],
                'label' => __('Flip Horizontal', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'autoset',
                'label' => __('Auto Set Featured Image', 'wp-extra'),
                'description' => __('Automatically set the first post image as the featured image if none is selected.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Media SEO & Filenames', 'wp-extra'));
            $section->add_option('select', [
                'name' => 'rename_images',
                'label' => __('SEO File Renamer', 'wp-extra'),
                'options' => [
                    '' => __('No'),
                    'slug' => __('post-slug.jpg'),
                    'filename' => __('post-slug-{file-name}.jpg'),
                    'date' => __('post-slug-{2026-08-19}.jpg')
                ],
                'description' => __('Automatically rename uploaded media files based on post slug for better Google Image SEO.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'meta_images',
                'label' => __('Auto Image Alt & Titles', 'wp-extra'),
                'description' => __('Automatically populate Title, Alt-Text, and Caption for images from post title.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'meta_images_filename',
                'show_if' => ['meta_images' => true],
                'label' => __('Use Original Filename', 'wp-extra'),
                'description' => __('Generate Alt/Title from cleaned filename instead of post title.', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'media_thumbnails',
                'del' => true,
                'options' => fn() => array_combine(
                    $sizes = get_intermediate_image_sizes(),
                    array_map(fn($size) => ucfirst(str_replace('_', ' ', $size)), $sizes)
                ),
                'label' => __('Disable Unnecessary Thumbnails', 'wp-extra'),
                'description' => __('Prevent generating unused image sizes to save disk storage and CDN bandwidth.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'big_image_threshold',
                'label' => __('Disable Large Image Threshold (2560px)', 'wp-extra'),
                'description' => __('Disable WordPress default 2560px image threshold auto-scaling.', 'wp-extra')
            ]);
        }

        // 5. Comments Tab
        if (self::get_option('modules') && in_array('comments', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.68822 16.625L5.5 17.8145L5.5 5.5L18.5 5.5L18.5 16.625L6.68822 16.625ZM7.31 18.125L19 18.125C19.5523 18.125 20 17.6773 20 17.125L20 5C20 4.44772 19.5523 4 19 4H5C4.44772 4 4 4.44772 4 5V19.5247C4 19.8173 4.16123 20.086 4.41935 20.2237C4.72711 20.3878 5.10601 20.3313 5.35252 20.0845L7.31 18.125ZM16 9.99997H8V8.49997H16V9.99997ZM8 14H13V12.5H8V14Z"></path></svg>' . __('Comments'));

            $section = $tab->add_section(__('Comments & Anti-Spam Control', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'disable_comments',
                'label' => __('Disable All Comments', 'wp-extra'),
                'description' => __('Completely close comments across all posts, pages, and post types.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'cm_media',
                'label' => __('Disable Media Comments', 'wp-extra'),
                'description' => __('Close comments on media attachment pages.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'cm_antispam',
                'label' => __('Smart Anti-Spam Filter', 'wp-extra'),
                'description' => __('Automatically filter out spam comments containing malicious keywords or links.', 'wp-extra')
            ]);
        }

        // 6. Branding & Logins Tab
        if (self::get_option('modules') && in_array('logins', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4 20h8v-1.5H4V20zM18.9 3.5c-.6-.6-1.5-.6-2.1 0l-7.2 7.2c-.4-.1-.7 0-1.1.1-.5.2-1.5.7-1.9 2.2-.4 1.7-.8 2.2-1.1 2.7-.1.1-.2.3-.3.4l-.6 1.1H6c2 0 3.4-.4 4.7-1.4.8-.6 1.2-1.4 1.3-2.3 0-.3 0-.5-.1-.7L19 5.7c.5-.6.5-1.6-.1-2.2zM9.7 14.7c-.7.5-1.5.8-2.4 1 .2-.5.5-1.2.8-2.3.2-.6.4-1 .8-1.1.5-.1 1 .1 1.3.3.2.2.3.5.2.8 0 .3-.1.9-.7 1.3z"></path></svg>' . __('Branding', 'wp-extra'));

            $section = $tab->add_section(__('Login Screen Customization', 'wp-extra'));
            $section->add_option('choices', [
                'name' => 'login_preset',
                'label' => __('Login Design Preset', 'wp-extra'),
                'default' => 'default',
                'options' => [
                    'default' => [
                        'label' => __('Default', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><rect width="120" height="70" rx="4" fill="#f0f0f1"/><circle cx="60" cy="14" r="5" fill="#2271b1"/><path d="M58 13.5l1.5 4 1-2.5 1 2.5 1.5-4" stroke="#fff" stroke-width="0.8" fill="none" stroke-linecap="round"/><rect x="36" y="22" width="48" height="42" rx="3" fill="#ffffff" stroke="#dcdcde" stroke-width="0.8"/><rect x="42" y="27" width="36" height="4.5" rx="1.5" fill="#f6f7f7" stroke="#c3c4c7" stroke-width="0.6"/><rect x="42" y="34.5" width="36" height="4.5" rx="1.5" fill="#f6f7f7" stroke="#c3c4c7" stroke-width="0.6"/><rect x="42" y="42" width="14" height="2" rx="1" fill="#8c8f94"/><rect x="62" y="41" width="16" height="5" rx="1.5" fill="#2271b1"/><rect x="42" y="51" width="18" height="2" rx="1" fill="#a7aaad"/><rect x="64" y="51" width="14" height="2" rx="1" fill="#a7aaad"/></svg>',
                    ],
                    'minimal_white' => [
                        'label' => __('Minimalist Clean', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><rect width="120" height="70" rx="4" fill="#ffffff" stroke="#e5e7eb" stroke-width="0.8"/><circle cx="60" cy="13" r="4" fill="#18181b"/><rect x="38" y="20" width="44" height="44" rx="5" fill="#ffffff" stroke="#e2e8f0" stroke-width="1"/><rect x="44" y="26" width="32" height="5" rx="2.5" fill="#f8fafc" stroke="#cbd5e1" stroke-width="0.6"/><rect x="44" y="34" width="32" height="5" rx="2.5" fill="#f8fafc" stroke="#cbd5e1" stroke-width="0.6"/><rect x="44" y="43" width="32" height="6" rx="3" fill="#0f172a"/><rect x="48" y="54" width="24" height="2" rx="1" fill="#94a3b8"/></svg>',
                    ],
                    'dark_modern' => [
                        'label' => __('Dark Glass', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><defs><linearGradient id="dgGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#18181b"/><stop offset="100%" stop-color="#09090b"/></linearGradient></defs><rect width="120" height="70" rx="4" fill="url(#dgGrad)"/><circle cx="60" cy="13" r="4.5" fill="#38bdf8"/><rect x="36" y="21" width="48" height="43" rx="4" fill="#27272a" fill-opacity="0.8" stroke="#3f3f46" stroke-width="0.8"/><rect x="42" y="27" width="36" height="5" rx="2" fill="#18181b" stroke="#52525b" stroke-width="0.6"/><rect x="42" y="35" width="36" height="5" rx="2" fill="#18181b" stroke="#52525b" stroke-width="0.6"/><rect x="42" y="44" width="36" height="6" rx="3" fill="#38bdf8"/><rect x="48" y="55" width="24" height="2" rx="1" fill="#71717a"/></svg>',
                    ],
                    'gradient_vibrant' => [
                        'label' => __('Vibrant Gradient', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><defs><linearGradient id="gvBg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#6366f1"/><stop offset="50%" stop-color="#a855f7"/><stop offset="100%" stop-color="#ec4899"/></linearGradient><linearGradient id="gvBtn" x1="0" y1="0" x2="1" y2="0"><stop offset="0%" stop-color="#8b5cf6"/><stop offset="100%" stop-color="#ec4899"/></linearGradient></defs><rect width="120" height="70" rx="4" fill="url(#gvBg)"/><circle cx="60" cy="13" r="4.5" fill="#ffffff"/><rect x="36" y="21" width="48" height="43" rx="5" fill="#ffffff" fill-opacity="0.95" stroke="rgba(255,255,255,0.6)" stroke-width="0.8"/><rect x="42" y="27" width="36" height="5" rx="2" fill="#fdf4ff" stroke="#e879f9" stroke-width="0.6"/><rect x="42" y="35" width="36" height="5" rx="2" fill="#fdf4ff" stroke="#e879f9" stroke-width="0.6"/><rect x="42" y="44" width="36" height="6" rx="3" fill="url(#gvBtn)"/><rect x="48" y="55" width="24" height="2" rx="1" fill="#c084fc"/></svg>',
                    ],
                    'custom' => [
                        'label' => __('Custom', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><rect width="120" height="70" rx="4" fill="#f8fafc" stroke="#e2e8f0" stroke-width="0.8"/><circle cx="42" cy="35" r="16" fill="#e0e7ff"/><path d="M42 22a13 13 0 0 1 13 13c0 2.5-1.5 4.5-3.5 4.5-1 0-1.8-.5-2.2-1.2-.4-.7-.9-1.3-1.8-1.3H44c-4.4 0-8-3.6-8-8 0-3.9 2.7-7 6-7z" fill="#6366f1"/><circle cx="38" cy="29" r="2" fill="#f43f5e"/><circle cx="44" cy="27" r="2" fill="#3b82f6"/><circle cx="48" cy="32" r="2" fill="#10b981"/><circle cx="36" cy="36" r="2" fill="#f59e0b"/><path d="M66 26h28M66 35h28M66 44h28" stroke="#cbd5e1" stroke-width="2.5" stroke-linecap="round"/><circle cx="74" cy="26" r="4" fill="#6366f1"/><circle cx="84" cy="35" r="4" fill="#f43f5e"/><circle cx="71" cy="44" r="4" fill="#10b981"/></svg>',
                    ],
                ],
                'description' => __('Choose a pre-built login style or select "Custom" to reveal custom colors, background, and logo options.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'login_title',
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Login Page Title', 'wp-extra'),
                'description' => __('Custom title tag for the login page browser tab.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'login_logo_hide',
                'label' => __('Hide WordPress Logo', 'wp-extra')
            ]);
            $section->add_option('image', [
                'name' => 'login_logo',
                'show_if' => ['login_logo_hide' => false],
                'label' => __('Custom Logo'),
                'description' => __('Replace default WordPress logo. Max recommended width: 320px.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'login_logo_url',
                'show_if' => ['login_logo_hide' => false],
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Logo Link URL', 'wp-extra'),
                'description' => __('Redirect destination when clicking the login logo (default: homepage).', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'login_color',
                'show_if' => ['login_preset' => 'custom'],
                'label' => __('Accent Color', 'wp-extra')
            ]);
            $section->add_option('image', [
                'name' => 'login_bg_image',
                'show_if' => ['login_preset' => 'custom'],
                'label' => __('Background Image', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'login_bg_color',
                'show_if' => ['login_preset' => 'custom'],
                'label' => __('Background Color', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'login_form_radius',
                'show_if' => ['login_preset' => 'custom'],
                'css' => ['input_class' => 'small-text'],
                'label' => __('Form Border Radius', 'wp-extra'),
                'description' => 'px'
            ]);
            $section->add_option('checkbox', [
                'name' => 'login_placeholder',
                'label' => __('Add Input Placeholders', 'wp-extra'),
                'description' => __('Show Username and Password inside input fields.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'login_remember',
                'label' => __('Auto-Check Remember Me', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'login_link_form',
                'del' => true,
                'options' => [
                    'remember' => __('Remember Me'),
                    'lost' => __('Register') . ' | ' . __('Lost your password?'),
                    'backto' => __('&laquo; Back'),
                    'language' => __('Language'),
                    'privacy' => __('Privacy Policy')
                ],
                'label' => __('Hide Login Footer Links', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Login Security & Protection', 'wp-extra'));
            $section->add_option('text', [
                'name' => 'login_url',
                'label' => __('Custom Login URL', 'wp-extra'),
                'description' => __('Change your login URL slug to block brute-force bots from finding wp-login.php.', 'wp-extra') . '<br>🔐 <a href="' . esc_url(wp_login_url()) . '" target="_blank">' . __('Preview Login URL', 'wp-extra') . '</a>'
            ]);
            $section->add_option('checkbox', [
                'name' => 'turnstile',
                'label' => __('Cloudflare Turnstile Captcha', 'wp-extra'),
                'description' => 'Protect login and registration forms with smart zero-friction captcha. <a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank">' . __('Get Turnstile Keys', 'wp-extra') . '</a>'
            ]);
            $section->add_option('text', [
                'name' => 'turnstile_site_key',
                'show_if' => ['turnstile' => true],
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Turnstile Site Key', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'turnstile_secret_key',
                'show_if' => ['turnstile' => true],
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Turnstile Secret Key', 'wp-extra')
            ]);
            if (Helper::is_woo_active()) {
                $section->add_option('checkbox', [
                    'name' => 'turnstile_my_account',
                    'show_if' => ['turnstile' => true],
                    'label' => __('WooCommerce My-Account & Flatsome Popup Turnstile', 'wp-extra'),
                    'description' => __('Enable Turnstile captcha on WooCommerce customer login, register forms, and Flatsome theme account popup/modal.', 'wp-extra')
                ]);
            }
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'limit_login',
                'css' => ['input_class' => 'small-text'],
                'label' => __('Limit Login Attempts', 'wp-extra'),
                'description' => __('Max allowed failed login attempts before IP temporary lockout.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Admin Panel Branding', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'adminfooter_version',
                'label' => __('Hide WordPress Version', 'wp-extra'),
                'description' => __('Remove WordPress version number from admin footer.', 'wp-extra')
            ]);
            $section->add_option('wp-editor', [
                'name' => 'adminfooter_custom',
                'teeny' => true,
                'label' => __('Custom Admin Footer Text', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Content Protection', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'donot_copy',
                'label' => __('Disable Content Copy', 'wp-extra'),
                'description' => __('Disable right-click and text selection to protect your articles from copying.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'donot_content',
                'show_if' => ['donot_copy' => true],
                'label' => __('Allow Text Selection', 'wp-extra'),
                'description' => __('Allow text selection but append copyright notice on copy.', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'donot_copyright',
                'css' => ['input_class' => 'regular-text'],
                'show_if' => ['donot_copy' => true],
                'label' => __('Copyright Notice Text', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'donot_back',
                'label' => __('Disable Back Button', 'wp-extra'),
                'description' => __('Prevent visitors from navigating back in history.', 'wp-extra')
            ]);
        }

        // 7. Admin Menu & Permissions Tab
        if (self::get_option('modules') && in_array('admins', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M15.5 9.5a1 1 0 100-2 1 1 0 000 2zm0 1.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm-2.25 6v-2a2.75 2.75 0 00-2.75-2.75h-4A2.75 2.75 0 003.75 15v2h1.5v-2c0-.69.56-1.25 1.25-1.25h4c.69 0 1.25.56 1.25 1.25v2h1.5zm7-2v2h-1.5v-2c0-.69-.56-1.25-1.25-1.25H15v-1.5h2.5A2.75 2.75 0 0120.25 15zM9.5 8.5a1 1 0 11-2 0 1 1 0 012 0zm1.5 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" fill-rule="evenodd"></path></svg>' . __('Permission', 'wp-extra'));

            $section = $tab->add_section(__('Top Admin Bar', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'wp_adminbar',
                'label' => __('Hide Admin Bar', 'wp-extra'),
                'description' => __('Hide the top admin bar on the frontend.', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'select' => true,
                'show_if' => ['wp_adminbar' => false],
                'name' => 'wp_toolbar',
                'options' => function () {
                    $options = [
                        'wp-logo' => __('WordPress Logo', 'wp-extra'),
                        'site-name' => __('Site Title', 'wp-extra'),
                        'customize' => __('Customize', 'wp-extra'),
                        'updates' => __('Updates', 'wp-extra'),
                        'comments' => __('Comments', 'wp-extra'),
                        'new-content' => __('New Content Menu (+ New)', 'wp-extra'),
                        'edit' => __('Edit Post/Page', 'wp-extra'),
                        'search' => __('Search', 'wp-extra'),
                        'my-account' => __('Profile / Account', 'wp-extra'),
                        'wp-extra' => __('WP Extra', 'wp-extra'),
                    ];

                    // Detect Active Plugins & Themes
                    if (defined('FLATSOME_VERSION') || get_template() === 'flatsome' || get_stylesheet() === 'flatsome') {
                        $options['flatsome_panel'] = __('Flatsome Theme', 'wp-extra');
                    }
                    if (defined('WPSEO_VERSION')) {
                        $options['wpseo-menu'] = __('Yoast SEO', 'wp-extra');
                    }
                    if (defined('RANK_MATH_VERSION')) {
                        $options['rank-math'] = __('Rank Math', 'wp-extra');
                    }
                    if (defined('WP_ROCKET_VERSION')) {
                        $options['wp-rocket'] = __('WP Rocket', 'wp-extra');
                    }
                    if (defined('LSCWP_V') || defined('LSCWP_BASENAME')) {
                        $options['litespeed-menu'] = __('LiteSpeed Cache', 'wp-extra');
                    }
                    if (defined('ELEMENTOR_VERSION')) {
                        $options['elementor_edit_page'] = __('Elementor', 'wp-extra');
                    }
                    if (class_exists('WooCommerce')) {
                        $options['woocommerce'] = __('WooCommerce', 'wp-extra');
                    }
                    if (defined('WPFORMS_VERSION')) {
                        $options['wpforms-menu'] = __('WPForms', 'wp-extra');
                    }
                    if (defined('AUTOPTIMIZE_PLUGIN_VERSION')) {
                        $options['autoptimize'] = __('Autoptimize', 'wp-extra');
                    }
                    if (class_exists('QueryMonitor') || class_exists('QM_Backtrace')) {
                        $options['query-monitor'] = __('Query Monitor', 'wp-extra');
                    }

                    return $options;
                },
                'label' => __('Hide Specific Admin Bar Items', 'wp-extra'),
                'description' => __('Select specific items to remove from the top Admin Bar.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'admin_site_link',
                'label' => __('Redirect Site Title to Admin Dashboard', 'wp-extra'),
                'description' => __('When enabled, clicking the Site Title on the top Admin Bar opens the Admin Dashboard instead of viewing the website.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'wp_adminbar_auto',
                'show_if' => ['wp_adminbar' => false],
                'label' => __('Auto-Hide Admin Bar', 'wp-extra'),
                'description' => __('Automatically slide up admin bar until hovered.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Admin Menu & Plugin Management', 'wp-extra'));
            $section->add_option('checkbox-multiple', [
                'name' => 'adminmenu_list',
                'select' => true,
                'options' => function () {
                    global $menu;
                    $menu_items = [];
                    if (!empty($menu) && is_array($menu)) {
                        foreach ($menu as $item) {
                            if (!isset($item[0], $item[2]))
                                continue;
                            if (isset($item[4]) && preg_match('/wp-menu-separator/', $item[4])) {
                                $label = '<sub style="color:#616A74;">― Separator</sub>';
                            } else {
                                $label = $item[0];
                            }
                            $key = esc_attr($item[2]);
                            $menu_items[$key] = $label;
                        }
                    }
                    return $menu_items;
                },
                'label' => __('Hide Admin Menu Items', 'wp-extra'),
                'description' => __('Hide selected sidebar navigation items for non-super admins.', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'adminplugin_list',
                'select' => true,
                'options' => function () {
                    if (!function_exists('get_plugins')) {
                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                    }
                    $plugin_items = wp_cache_get('wpex_all_plugins_list');
                    if (false === $plugin_items) {
                        $all_plugins = get_plugins();
                        $plugin_items = [];

                        foreach ($all_plugins as $value => $item) {
                            $key = esc_attr($value);
                            $label = wp_strip_all_tags($item['Name']);
                            $plugin_items[$key] = $label;
                        }
                        wp_cache_set('wpex_all_plugins_list', $plugin_items, '', 1800);
                    }

                    return $plugin_items;
                },
                'label' => __('Hide Installed Plugins', 'wp-extra'),
                'description' => __('Hide specific plugins from appearing in the Plugins list.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('User Account Enhancements', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'scrolltotop',
                'label' => __('Scroll To Top', 'wp-extra'),
                'description' => __('Floating squircle button to quickly scroll back to the top of any admin page.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'registration_date',
                'label' => __('Show User Registration Date', 'wp-extra'),
                'description' => __('Display registration date column in the Users list table.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'last_login',
                'label' => __('Show User Last Login', 'wp-extra'),
                'description' => __('Display last login time column in the Users list table.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'application_passwords',
                'label' => __('Disable Application Passwords', 'wp-extra'),
                'description' => __('Turn off WordPress Application Passwords feature for security.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'profile_pw',
                'label' => __('Disable Password Change in Profile', 'wp-extra'),
                'description' => __('Hide password fields in user profile to prevent users from changing passwords.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'profile_email',
                'label' => __('Disable Email Change in Profile', 'wp-extra'),
                'description' => __('Lock and prevent users from changing their email address on the profile screen.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Access & Security Restrictions', 'wp-extra'));
            $section->add_option('checkbox-multiple', [
                'name' => 'no_backend',
                'select' => true,
                'options' => function () {
                    $roles = get_editable_roles();
                    unset($roles['administrator']);
                    $options = [];
                    foreach ($roles as $key => $role) {
                        $options[$key] = translate_user_role($role['name']);
                    }
                    return $options;
                },
                'label' => __('Block Backend Access by Role', 'wp-extra'),
                'description' => __('Prevent non-admin user roles from accessing /wp-admin and redirect them to homepage.', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'adminmenu_extra',
                'select' => true,
                'options' => fn() => wp_list_pluck(get_users(['role' => 'administrator']), 'display_name', 'ID'),
                'label' => __('WP EXtra Super Admins', 'wp-extra'),
                'description' => __('Only selected administrator accounts can view and edit WP EXtra settings.', 'wp-extra')
            ]);
        }

        // 8. Security Tab
        if (self::get_option('modules') && in_array('security', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M17 10h-1.2V7c0-2.1-1.7-3.8-3.8-3.8-2.1 0-3.8 1.7-3.8 3.8v3H7c-.6 0-1 .4-1 1v8c0 .6.4 1 1 1h10c.6 0 1-.4 1-1v-8c0-.6-.4-1-1-1zM9.8 7c0-1.2 1-2.2 2.2-2.2 1.2 0 2.2 1 2.2 2.2v3H9.8V7zm6.7 11.5h-9v-7h9v7z"></path></svg>' . __('Security', 'wp-extra'));

            $section = $tab->add_section(__('Core Hardening', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'disable_xmlrpc',
                'label' => __('Disable XML-RPC', 'wp-extra'),
                'description' => __('Block XML-RPC pingbacks and brute-force attacks.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'disable_embeds',
                'label' => __('Disable WordPress Embeds', 'wp-extra'),
                'description' => __('Remove WordPress Embed script (wp-embed.min.js) and oEmbed discovery links.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'remove_jquery_migrate',
                'label' => __('Remove jQuery Migrate', 'wp-extra'),
                'description' => __('Unload legacy jQuery Migrate script on frontend to save HTTP request.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'remove_wp_version',
                'label' => __('Hide WordPress Generator Version', 'wp-extra'),
                'description' => __('Remove generator meta tag containing WordPress version from HTML source.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'clean_head_links',
                'label' => __('Clean Head & Pingbacks', 'wp-extra'),
                'description' => __('Remove legacy RSD, WLWmanifest, Shortlink tags and self-pingbacks.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'disable_rss_feeds',
                'label' => __('Disable RSS Feeds & Links', 'wp-extra'),
                'description' => __('Disable generated RSS feeds, 301 redirect visitors to parent URL, and remove RSS feed link tags from HTML head.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'block_user_enumeration',
                'label' => __('Block User / Author Enumeration', 'wp-extra'),
                'description' => __('Block URL scanner (?author=N) and REST API user queries to protect admin usernames from bots.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'security_headers',
                'label' => __('Enable HTTP Security Headers', 'wp-extra'),
                'description' => __('Send modern security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy).', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'themeplugin_edits',
                'label' => __('Disable File Editors', 'wp-extra'),
                'description' => __('Disable the built-in Theme and Plugin code editors for security.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'core_updates',
                'label' => __('Disable Automatic Core Updates', 'wp-extra'),
                'description' => __('Prevent WordPress core from automatically applying updates.', 'wp-extra')
            ]);
            $section->add_option('textarea', [
                'name' => 'http_request',
                'label' => __('Block External HTTP API Calls', 'wp-extra'),
                'description' => __('Block outgoing remote HTTP requests to specified domains (one per line).', 'wp-extra')
            ]);

            $section = $tab->add_section(__('REST API & Heartbeat', 'wp-extra'));
            $section->add_option('choices', [
                'name' => 'disable_rest_api',
                'options' => [
                    '' => __('Default (Enabled)', 'wp-extra'),
                    'non_admins' => __('Disable for Non-Admins', 'wp-extra'),
                    'logged_out' => __('Disable When Logged Out', 'wp-extra'),
                    'all' => __('All')
                ],
                'label' => __('REST API Access', 'wp-extra'),
                'description' => __('Restrict REST API access to authenticated users or administrators.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'remove_rest_api_links',
                'label' => __('Remove REST API Header Links', 'wp-extra'),
                'description' => __('Remove REST API link tags from HTML head and HTTP response headers.', 'wp-extra')
            ]);
            $section->add_option('choices', [
                'name' => 'disable_heartbeat',
                'options' => [
                    '' => __('Default'),
                    'everywhere' => __('Disable Everywhere', 'wp-extra'),
                    'allow_posts' => __('Only Allow When Editing Posts/Pages', 'wp-extra')
                ],
                'label' => __('Heartbeat API Control', 'wp-extra'),
                'description' => __('Control WordPress Heartbeat API execution to reduce server CPU load.', 'wp-extra')
            ]);
            $section->add_option('select', [
                'name' => 'heartbeat_frequency',
                'options' => [
                    '' => sprintf(__('%s second'), '15') . ' (' . __('Default') . ')',
                    '30' => sprintf(__('%s second'), '30'),
                    '45' => sprintf(__('%s second'), '45'),
                    '60' => sprintf(__('%s second'), '60')
                ],
                'label' => __('Heartbeat Frequency', 'wp-extra'),
                'description' => __('Set interval frequency for Heartbeat requests.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'remove_blocks',
                'label' => __('Disable Core Blocks Library', 'wp-extra'),
                'description' => __('Unregister all standard Gutenberg core block types.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Htaccess Rules', 'wp-extra'), ['description' => __('Applies to Apache, LiteSpeed, and OpenLiteSpeed web servers. Please backup your server configuration before editing.', 'wp-extra')]);
            $section->add_option('import', [
                'name' => 'htaccess_root',
                'label' => __('Root .htaccess', 'wp-extra'),
                'description' => __('.htaccess')
            ]);
            $section->add_option('protect', [
                'name' => 'htaccess_includes',
                'target' => 'includes',
                'label' => __('Protect WP-Includes Folder', 'wp-extra'),
                'description' => __('Creates .htaccess inside wp-includes to block direct execution of PHP scripts.', 'wp-extra')
            ]);
            $section->add_option('protect', [
                'name' => 'htaccess_content',
                'target' => 'content',
                'label' => __('Protect WP-Content Folder', 'wp-extra'),
                'description' => __('Creates .htaccess inside wp-content to block direct execution of PHP files.', 'wp-extra')
            ]);
        }

        // 9. Optimize Tab
        if (self::get_option('modules') && in_array('optimize', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M3.445 16.505a.75.75 0 001.06.05l5.005-4.55 4.024 3.521 4.716-4.715V14h1.5V8.25H14v1.5h3.19l-3.724 3.723L9.49 9.995l-5.995 5.45a.75.75 0 00-.05 1.06z"></path></svg>' . __('Optimize', 'wp-extra'));

            $section = $tab->add_section(__('Frontend Asset Cleanup', 'wp-extra'), ['description' => __('Note: Combine with a caching plugin for best performance.', 'wp-extra')]);
            $section->add_option('checkbox', [
                'name' => 'disable_emojis',
                'label' => __('Disable WordPress Emojis', 'wp-extra'),
                'description' => __('Remove WordPress Emoji JavaScript and styles from frontend.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'disable_dashicons',
                'label' => __('Disable Dashicons for Guests', 'wp-extra'),
                'description' => __('Unload Dashicons CSS for visitors who are not logged in.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'gutenberg',
                'label' => __('Disable Gutenberg Block CSS', 'wp-extra'),
                'description' => __('Prevent Gutenberg Block Library CSS from loading on the frontend.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'remove_global_styles',
                'label' => __('Remove Global Styles & SVG Filters', 'wp-extra'),
                'description' => __('Strip global-styles-inline-css and SVG Duotone Filters.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'query_strings',
                'label' => __('Remove Query Strings from Static Resources', 'wp-extra'),
                'description' => __('Remove version query strings from CSS/JS files to improve CDN cacheability.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'font_preconnect',
                'label' => __('Preconnect Google Fonts & CDNs', 'wp-extra'),
                'description' => __('Add preconnect link hints for Google Fonts and common CDNs to speed up font loading.', 'wp-extra')
            ]);
        }

        // 10. Permalinks Tab
        if (self::get_option('modules') && in_array('permalinks', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12.5 14.5h-1V16h1c2.2 0 4-1.8 4-4s-1.8-4-4-4h-1v1.5h1c1.4 0 2.5 1.1 2.5 2.5s-1.1 2.5-2.5 2.5zm-4 1.5v-1.5h-1C6.1 14.5 5 13.4 5 12s1.1-2.5 2.5-2.5h1V8h-1c-2.2 0-4 1.8-4 4s1.8 4 4 4h1zm-1-3.2h5v-1.5h-5v1.5zM18 4H9c-1.1 0-2 .9-2 2v.5h1.5V6c0-.3.2-.5.5-.5h9c.3 0 .5.2.5.5v12c0 .3-.2.5-.5.5H9c-.3 0-.5-.2-.5-.5v-.5H7v.5c0 1.1.9 2 2 2h9c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"></path></svg>' . __('Permalinks'));

            $section = $tab->add_section(__('URL Structure & Slugs', 'wp-extra'));
            $section->add_option('checkbox-multiple', [
                'name' => 'slug_post_type',
                'select' => true,
                'options' => fn() => array_combine(
                    $ids = array_diff(get_post_types(['public' => true]), ['post', 'page', 'attachment', 'blocks', 'product']),
                    array_map(fn($id) => get_post_type_object($id)->label . " <code>$id</code>", $ids)
                ),
                'label' => __('Remove Post Type Slugs', 'wp-extra'),
                'description' => __('Remove post type base prefix from URLs for clean single post links.', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'slug_taxonomy',
                'select' => true,
                'options' => fn() => array_combine(
                    $ids = array_diff(get_taxonomies(['public' => true], 'names'), ['post_tag', 'post_format', 'product_shipping_class', 'product_brand', 'product_cat', 'product_tag']),
                    array_map(fn($id) => get_taxonomy($id)->label . " <code>$id</code>", $ids)
                ),
                'label' => __('Remove Taxonomy Slugs', 'wp-extra'),
                'description' => __('Remove category base prefix from archive URLs.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('SEO & Link Redirection', 'wp-extra'));
            $section->add_option('checkbox', [
                'name' => 'external_links',
                'label' => __('Auto Nofollow & Open External Links in New Tab', 'wp-extra'),
                'description' => __('Automatically add rel="nofollow noopener noreferrer" and target="_blank" to all external links.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'redirect_attachment',
                'label' => __('Redirect Attachment Pages', 'wp-extra'),
                'description' => __('Redirect media attachment URLs to the parent post or media file.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'redirect_single_post',
                'label' => __('Auto Redirect Single Search Result', 'wp-extra'),
                'description' => __('Redirect directly to post if a search query returns exactly one match.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Robots.txt Editor', 'wp-extra'), ['description' => __('Manage your search engine crawler directives file.', 'wp-extra')]);
            $section->add_option('import', [
                'name' => 'robots_txt',
                'label' => __('Robots.txt', 'wp-extra'),
                'description' => __('robots.txt')
            ]);
        }

        // 11. Custom Code Tab
        if (self::get_option('modules') && in_array('code', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4.8 11.4H2.1V9H1v6h1.1v-2.6h2.7V15h1.1V9H4.8v2.4zm1.9-1.3h1.7V15h1.1v-4.9h1.7V9H6.7v1.1zM16.2 9l-1.5 2.7L13.3 9h-.9l-.8 6h1.1l.5-4 1.5 2.8 1.5-2.8.5 4h1.1L17 9h-.8zm3.8 5V9h-1.1v6h3.6v-1H20z"></path></svg>' . __('Code'));

            $section = $tab->add_section(__('Tracking & Custom Scripts', 'wp-extra'));
            $section->add_option('code-editor', [
                'name' => 'code_header',
                'editor_type' => 'text/html',
                'label' => __('Header Scripts (<head>)', 'wp-extra'),
                'description' => __('Add Google Analytics, Tag Manager, or tracking scripts inside the HEAD tag.', 'wp-extra')
            ]);
            if (function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2', '>=')) {
                $section->add_option('code-editor', [
                    'name' => 'code_body',
                    'editor_type' => 'text/html',
                    'label' => __('Body Scripts (<body>)', 'wp-extra'),
                    'description' => __('Add scripts immediately following opening BODY tag.', 'wp-extra')
                ]);
            }
            $section->add_option('code-editor', [
                'name' => 'code_footer',
                'editor_type' => 'text/html',
                'label' => __('Footer Scripts (</body>)', 'wp-extra'),
                'description' => __('Add tracking pixels or chat widgets loaded at page footer.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Custom Responsive CSS', 'wp-extra'));
            $section->add_option('code-editor', [
                'name' => 'css_all',
                'editor_type' => 'text/css',
                'label' => __('Global CSS (All Screens)', 'wp-extra')
            ]);
            $section->add_option('code-editor', [
                'name' => 'css_tablet',
                'editor_type' => 'text/css',
                'label' => __('Tablet CSS (<= 1024px)', 'wp-extra')
            ]);
            $section->add_option('code-editor', [
                'name' => 'css_mobile',
                'editor_type' => 'text/css',
                'label' => __('Mobile CSS (<= 767px)', 'wp-extra')
            ]);
        }

        // 12. Cookie Tab
        if (self::get_option('modules') && in_array('cookie', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24" aria-hidden="true" focusable="false"><path d="M497.1 286.7c-3.4-4.6-8.5-7.6-14.1-8.3-73.7-9.1-129.2-71.4-129.2-145.1 0-24.7 6.4-49.2 18.5-70.8 2.8-4.9 3.3-10.8 1.6-16.2s-5.7-9.8-10.8-12.2C330 18.8 294.7 11 258.1 11c-136.2 0-247 109.9-247 245s110.8 245 247.1 245c118.2 0 220.2-83.5 242.6-198.5 1-5.5-.3-11.2-3.7-15.8m-239 173.5c-113.5 0-205.9-91.6-205.9-204.2S144.6 51.8 258.1 51.8c23.5 0 46.4 3.9 68.2 11.5-9 22.2-13.7 46-13.7 70 0 86.5 59.9 160.8 142.7 181.4-25.7 85.4-105.6 145.5-197.2 145.5"/><ellipse cx="194.5" cy="150.8" rx="20.4" ry="20.3"/><ellipse cx="264.4" cy="230.7" rx="20.4" ry="20.3"/><ellipse cx="293.8" cy="340.2" rx="20.4" ry="20.3"/><ellipse cx="146.7" cy="304.3" rx="20.4" ry="20.3"/></svg>' . __('Cookie', 'wp-extra'));

            $section = $tab->add_section(__('Cookie Banner', 'wp-extra'));
            $section->add_option('choices', [
                'name' => 'cookie_preset',
                'label' => __('Design Preset', 'wp-extra'),
                'default' => 'default',
                'options' => [
                    'default' => [
                        'label' => __('Classic Light', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><rect width="120" height="70" rx="4" fill="#f8fafc" stroke="#e2e8f0" stroke-width="0.8"/><rect x="8" y="8" width="104" height="34" rx="2" fill="#ffffff" stroke="#e2e8f0" stroke-width="0.6"/><circle cx="16" cy="15" r="3" fill="#f1f5f9"/><rect x="22" y="14" width="40" height="2.5" rx="1" fill="#94a3b8"/><rect x="8" y="46" width="104" height="18" rx="2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.8"/><circle cx="16" cy="55" r="4" fill="#f59e0b"/><circle cx="15" cy="54" r="0.8" fill="#78350f"/><circle cx="17.5" cy="55.5" r="0.8" fill="#78350f"/><rect x="24" y="53" width="50" height="3" rx="1" fill="#475569"/><rect x="24" y="57.5" width="30" height="2" rx="0.8" fill="#94a3b8"/><rect x="84" y="52" width="22" height="7" rx="2" fill="#1e58b1"/></svg>',
                    ],
                    'dark_modern' => [
                        'label' => __('Dark Modern', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><rect width="120" height="70" rx="4" fill="#0f172a"/><rect x="8" y="8" width="104" height="34" rx="2" fill="#1e293b" stroke="#334155" stroke-width="0.6"/><rect x="14" y="14" width="45" height="3" rx="1" fill="#64748b"/><rect x="8" y="46" width="104" height="18" rx="2" fill="#18181b" stroke="#27272a" stroke-width="0.8"/><circle cx="16" cy="55" r="4" fill="#f59e0b"/><circle cx="15" cy="54" r="0.8" fill="#78350f"/><circle cx="17.5" cy="55.5" r="0.8" fill="#78350f"/><rect x="24" y="53" width="50" height="3" rx="1" fill="#f8fafc"/><rect x="24" y="57.5" width="28" height="2" rx="0.8" fill="#94a3b8"/><rect x="84" y="52" width="22" height="7" rx="3.5" fill="#38bdf8"/></svg>',
                    ],
                    'soft_warm' => [
                        'label' => __('Soft Warm', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><rect width="120" height="70" rx="4" fill="#fefce8" stroke="#fef08a" stroke-width="0.8"/><rect x="8" y="8" width="104" height="30" rx="2" fill="#ffffff" stroke="#fef08a" stroke-width="0.6"/><rect x="14" y="14" width="40" height="2.5" rx="1" fill="#ca8a04"/><rect x="16" y="42" width="88" height="22" rx="6" fill="#ffffff" stroke="#fde047" stroke-width="0.8"/><circle cx="27" cy="53" r="4.5" fill="#ea580c"/><circle cx="26" cy="52" r="0.8" fill="#ffffff"/><circle cx="28.5" cy="53.5" r="0.8" fill="#ffffff"/><rect x="36" y="50" width="34" height="3" rx="1" fill="#78350f"/><rect x="36" y="54.5" width="22" height="2" rx="0.8" fill="#a16207"/><rect x="76" y="48" width="22" height="9" rx="4.5" fill="#ea580c"/></svg>',
                    ],
                    'custom' => [
                        'label' => __('Custom', 'wp-extra'),
                        'image' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 70" width="100%" height="100%"><rect width="120" height="70" rx="4" fill="#f8fafc" stroke="#e2e8f0" stroke-width="0.8"/><circle cx="36" cy="35" r="15" fill="#fef3c7"/><path d="M36 24a11 11 0 0 1 11 11c0 2-1.2 3.5-2.8 3.5-.8 0-1.4-.4-1.8-1-.3-.5-.7-1-1.4-1h-1.5c-3.5 0-6.5-3-6.5-6.5 0-3.3 2.5-6 6-6z" fill="#f59e0b"/><circle cx="33" cy="30" r="1.5" fill="#ef4444"/><circle cx="38" cy="28" r="1.5" fill="#3b82f6"/><circle cx="41" cy="32" r="1.5" fill="#10b981"/><circle cx="31" cy="35" r="1.5" fill="#8b5cf6"/><path d="M60 26h34M60 35h34M60 44h34" stroke="#cbd5e1" stroke-width="2.5" stroke-linecap="round"/><circle cx="68" cy="26" r="4" fill="#f59e0b"/><circle cx="82" cy="35" r="4" fill="#1e58b1"/><circle cx="65" cy="44" r="4" fill="#ef4444"/></svg>',
                    ],
                ],
                'description' => __('Choose a pre-styled color scheme or select "Custom" to customize colors.', 'wp-extra')
            ]);
            $section->add_option('wp-editor', [
                'name' => 'cookie_message',
                'label' => __('Notice Message', 'wp-extra'),
                'default' => __('This site uses cookies to improve your online experience, allow you to share content on social media, measure traffic to this website and display customised ads based on your browsing activity.', 'wp-extra'),
                'description' => __('Notice message displayed to website visitors.', 'wp-extra'),
                'editor_settings' => [
                    'media_buttons' => false,
                    'textarea_rows' => 4,
                    'teeny' => true,
                    'quicktags' => true
                ]
            ]);
            $section->add_option('text', [
                'name' => 'cookie_button',
                'css' => ['input_class' => 'regular-text'],
                'default' => __('Accept Cookies', 'wp-extra'),
                'label' => __('Accept Button Text', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'cookie_privacy',
                'label' => __('Display Privacy Policy Link', 'wp-extra'),
                'description' => '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">' . __('Privacy Policy Page') . '</a>'
            ]);
            $section->add_option('choices', [
                'name' => 'cookie_placement',
                'options' => [
                    '' => __('Bottom'),
                    'top' => __('Top')
                ],
                'label' => __('Banner Position', 'wp-extra')
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'css' => ['input_class' => 'small-text'],
                'min' => 1,
                'max' => 365,
                'default' => 30,
                'name' => 'cookie_expire',
                'label' => __('Cookie Expiry (Days)', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'cookie_bgcolor',
                'show_if' => ['cookie_preset' => 'custom'],
                'label' => __('Banner Background Color', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'cookie_textcolor',
                'show_if' => ['cookie_preset' => 'custom'],
                'label' => __('Banner Text Color', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'cookie_btnbgcolor',
                'show_if' => ['cookie_preset' => 'custom'],
                'label' => __('Button Background Color', 'wp-extra')
            ]);
            $section->add_option('color', [
                'name' => 'cookie_btntextcolor',
                'show_if' => ['cookie_preset' => 'custom'],
                'label' => __('Button Text Color', 'wp-extra')
            ]);
        }

        // 13. SMTP Mail Tab
        if (self::get_option('modules') && in_array('smtp', self::get_option('modules'))) {
            $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 4.5 4.5" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4 0.75H0.5a0.25 0.25 0 0 0 -0.25 0.25v2.5a0.25 0.25 0 0 0 0.25 0.25h3.5a0.25 0.25 0 0 0 0.25 -0.25V1a0.25 0.25 0 0 0 -0.25 -0.25m-0.193 2.75H0.708l0.875 -0.905 -0.18 -0.174L0.5 3.355V1.19l1.554 1.546a0.25 0.25 0 0 0 0.352 0L4 1.151v2.188l-0.92 -0.92 -0.176 0.176ZM0.664 1h3.134L2.23 2.559Z"/></svg>' . __('SMTP', 'wp-extra'));

            $section = $tab->add_section(__('SMTP Mailer Configuration', 'wp-extra'), [
                'description' => __('Configure one or multiple SMTP accounts with daily send limits and automatic rotation.', 'wp-extra')
            ]);
            $section->add_option('repeater', [
                'name' => 'smtp_accounts',
                'label' => __('SMTP Accounts', 'wp-extra'),
                'description' => __('Configure one or multiple SMTP accounts with daily send limits and automatic rotation.', 'wp-extra'),
                'button_text' => __('+ Add SMTP Account', 'wp-extra'),
                'fields' => [
                    [
                        'name' => 'provider',
                        'label' => __('SMTP Mail Service', 'wp-extra'),
                        'type' => 'select',
                        'options' => [
                            'gmail'      => 'Gmail SMTP [10,000 free emails/mo]',
                            'mailgun'    => 'Mailgun SMTP [5,000 free emails/mo for 3 months]',
                            'outlook'    => 'Outlook / Hotmail / Office 365',
                            'yahoo'      => 'Yahoo Mail',
                            'aws_ses'    => 'Amazon SES (AWS SES)',
                            'zoho'       => 'Zoho Mail',
                            'sendgrid'   => 'SendGrid',
                            'sendinblue' => 'Sendinblue (Brevo)',
                            'other'      => __('Other Custom SMTP Server', 'wp-extra')
                        ],
                        'default' => 'gmail',
                        'full_width' => true
                    ],
                    [
                        'name' => 'title',
                        'label' => __('Account Label', 'wp-extra'),
                        'type' => 'text',
                        'default' => 'Gmail SMTP'
                    ],
                    [
                        'name' => 'username',
                        'label' => __('Username / Email', 'wp-extra'),
                        'type' => 'text',
                        'placeholder' => 'your-email@example.com or api key'
                    ],
                    [
                        'name' => 'password',
                        'label' => __('Password / App Key', 'wp-extra'),
                        'type' => 'password',
                        'placeholder' => 'App Password or SMTP API Key'
                    ],
                    [
                        'name' => 'host',
                        'label' => __('SMTP Host', 'wp-extra'),
                        'type' => 'text',
                        'default' => 'smtp.gmail.com'
                    ],
                    [
                        'name' => 'port',
                        'label' => __('Port', 'wp-extra'),
                        'type' => 'number',
                        'default' => 465
                    ],
                    [
                        'name' => 'encryption',
                        'label' => __('Encryption', 'wp-extra'),
                        'type' => 'select',
                        'options' => [
                            'ssl' => 'SSL (Port 465)',
                            'tls' => 'TLS (Port 587)',
                            'none' => __('None', 'wp-extra')
                        ],
                        'default' => 'ssl'
                    ],
                    [
                        'name' => 'daily_limit',
                        'label' => __('Daily Quota (emails/day, 0 = unlimited)', 'wp-extra'),
                        'type' => 'number',
                        'default' => 100
                    ]
                ]
            ]);
            $section->add_option('text', [
                'name' => 'from_email',
                'text' => 'email',
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Global From Email Address', 'wp-extra')
            ]);
            $section->add_option('text', [
                'name' => 'from_name',
                'css' => ['input_class' => 'regular-text'],
                'label' => __('Global From Sender Name', 'wp-extra')
            ]);
            $section->add_option('checkbox-multiple', [
                'name' => 'smtp_options',
                'options' => [
                    'noverifyssl' => __('Disable SSL Certificate Verification', 'wp-extra'),
                    'antispam' => __('Anti-spam contact form filtering', 'wp-extra')
                ],
                'label' => __('SMTP Options', 'wp-extra')
            ]);
            $section->add_option('smtp', [
                'name' => 'test_email',
                'label' => __('Send Test Email', 'wp-extra'),
                'description' => __('Verify your SMTP mail configuration by sending a test message.', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Email Delivery Logs', 'wp-extra'), [
                'description' => __('Track outgoing emails, check delivery status, inspect email content, and resend failed messages.', 'wp-extra')
            ]);
            $section->add_option('checkbox', [
                'name' => 'email_log',
                'label' => __('Enable Email Logging', 'wp-extra'),
                'description' => sprintf(
                    __('Record all outgoing emails sent through WordPress. View your logs in the <a href="%s"><strong>Email Logs</strong></a> menu.', 'wp-extra'),
                    esc_url(admin_url('admin.php?page=wpex-email-logs'))
                )
            ]);
            $section->add_option('text', [
                'type' => 'number',
                'name' => 'email_log_retention',
                'show_if' => ['email_log' => true],
                'css' => ['input_class' => 'small-text'],
                'min' => 1,
                'default' => 30,
                'label' => __('Log Retention (Days)', 'wp-extra'),
                'description' => __('Automatically delete logs older than this number of days (e.g. 30).', 'wp-extra')
            ]);

            $section = $tab->add_section(__('Email Notifications & Domain Filter', 'wp-extra'));
            $section->add_option('checkbox-multiple', [
                'name' => 'no_emails',
                'select' => true,
                'options' => [
                    'remove_admin' => __('Disable admin email address change confirmation', 'wp-extra'),
                    'auto_update' => __('Disable core auto-update notification emails', 'wp-extra'),
                    'new_user' => __('Disable new user registration notification emails to admin', 'wp-extra'),
                    'password_reset' => __('Disable password reset notification emails to admin', 'wp-extra')
                ],
                'label' => __('Mute System Emails', 'wp-extra')
            ]);
            $section->add_option('textarea', [
                'name' => 'email_domain',
                'label' => __('Allowed Registration Email Domains', 'wp-extra'),
                'description' => __('Only allow user registration from specified email domains (e.g. gmail.com, yahoo.com). One per line.', 'wp-extra')
            ]);
        }

        // Transfer & Backup Tab (Tools)
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M17.3 10.1C17.3 7.60001 15.2 5.70001 12.5 5.70001C10.3 5.70001 8.4 7.10001 7.9 9.00001H7.7C5.7 9.00001 4 10.7 4 12.8C4 14.9 5.7 16.6 7.7 16.6H9.5V15.2H7.7C6.5 15.2 5.5 14.1 5.5 12.9C5.5 11.7 6.5 10.5 7.7 10.5H9L9.3 9.40001C9.7 8.10001 11 7.20001 12.5 7.20001C14.3 7.20001 15.8 8.50001 15.8 10.1V11.4L17.1 11.6C17.9 11.7 18.5 12.5 18.5 13.4C18.5 14.4 17.7 15.2 16.8 15.2H14.5V16.6H16.7C18.5 16.6 19.9 15.1 19.9 13.3C20 11.7 18.8 10.4 17.3 10.1Z M14.1245 14.2426L15.1852 13.182L12.0032 10L8.82007 13.1831L9.88072 14.2438L11.25 12.8745V18H12.75V12.8681L14.1245 14.2426Z"></path></svg>' . __('Tools'));
        $section = $tab->add_section(__('Backup & Transfer Settings', 'wp-extra'), [
            'slug' => true,
        ]);
        $section->add_option('restore', [
            'name' => 'restore',
            'label' => __('Transfer Options (.json)', 'wp-extra')
        ]);

        // Donate Tab
        $tab = $settings->add_tab('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>' . __('Donate', 'wp-extra'), 'donate');

        $donate_html = '
        <div style="max-width: 860px; margin-top: 6px;">
            <p style="font-size: 14px; line-height: 1.7; color: #3c434a; margin-bottom: 22px;">
                ' . sprintf(
            esc_html__('WP EXtra is an open-source project dedicated to delivering lightweight, clean, and reliable WordPress optimizations for everyone. Over %s days of continuous development and maintenance, our mission has always been keeping your website fast, secure, and hassle-free.', 'wp-extra'),
            '<strong>' . number_format_i18n($add_days) . '</strong>'
        ) . '
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 22px;">
                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 15px; font-weight: 600; color: #1d2327; display: flex; align-items: center; gap: 8px;">
                            <span>☕</span> ' . esc_html__('Buy Us a Coffee / Beer', 'wp-extra') . '
                        </h3>
                        <p style="font-size: 13px; color: #646970; line-height: 1.6; margin-bottom: 18px;">' . esc_html__('Your financial support directly covers server costs, testing devices, and research into new optimization features to keep the plugin 100% free and actively maintained.', 'wp-extra') . '</p>
                    </div>
                    <div>
                        <a href="https://wpvnteam.com/donate/" target="_blank" class="button button-primary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 500;">
                            ' . esc_html__('Donate via WPVNTeam Portal →', 'wp-extra') . '
                        </a>
                    </div>
                </div>

                <div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 15px; font-weight: 600; color: #1d2327; display: flex; align-items: center; gap: 8px;">
                            <span>⭐</span> ' . esc_html__('Spread the Word', 'wp-extra') . '
                        </h3>
                        <p style="font-size: 13px; color: #646970; line-height: 1.6; margin-bottom: 18px;">' . esc_html__('Take 1 minute to leave an honest 5-star rating on WordPress.org. It helps more webmasters discover WP EXtra and gives us tremendous motivation to keep improving.', 'wp-extra') . '</p>
                    </div>
                    <div>
                        <a href="https://wordpress.org/support/plugin/wp-extra/reviews/?filter=5#new-post" target="_blank" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 500;">
                            ' . esc_html__('Write a 5-Star Review ★★★★★', 'wp-extra') . '
                        </a>
                    </div>
                </div>
            </div>

            <p style="font-size: 13px; color: #646970; line-height: 1.6; margin: 0;">
                ' . esc_html__('Every single contribution — whether a donation, a positive review, or constructive feedback — is deeply appreciated and keeps WP EXtra moving forward. Thank you for your trust and partnership!', 'wp-extra') . '
            </p>
        </div>';

        $section = $tab->add_section(__('Support WP EXtra', 'wp-extra'), [
            'slug' => true,
            'description' => $donate_html
        ]);

        $settings->enable_ajax_save();
        $settings->make();
    }

    public static function get_option($key, $fallback = null)
    {
        return Helper::get_option($key, $fallback);
    }

    public static function is_feature_active($key)
    {
        return Helper::is_feature_active($key);
    }

    public static function minifyCSS($css)
    {
        return Helper::minifyCSS($css);
    }

    public function enqueue_admin_assets($hook)
    {
        $page = $_GET['page'] ?? '';
        if ($page !== 'wp-extra' && $page !== 'wpex-email-logs') {
            return;
        }

        wp_enqueue_style(
            'wpex-settings',
            WPEX_URL . 'assets/css/settings.css',
            [],
            WPEX_VERSION
        );

        wp_enqueue_script(
            'wpex-settings',
            WPEX_URL . 'assets/js/settings.js',
            ['jquery'],
            WPEX_VERSION,
            true
        );

        $today = current_time('Y-m-d');
        $accounts = (array) Helper::get_option('smtp_accounts', []);
        $usage_data = [];
        foreach ($accounts as $idx => $acc) {
            $limit = intval($acc['daily_limit'] ?? 0);
            $sent = intval(get_option('wpex_smtp_sent_' . $idx . '_' . $today, 0));
            $usage_data[$idx] = [
                'sent'    => $sent,
                'limit'   => $limit,
                'percent' => ($limit > 0) ? min(100, round(($sent / $limit) * 100)) : 0,
            ];
        }

        $config = [
            'nonces' => [
                'htaccess_protect' => wp_create_nonce('wpex_htaccess_protect_nonce'),
                'transfer_backup'  => wp_create_nonce('wpex_transfer_backup_nonce'),
                'smtp_test'        => wp_create_nonce('wpex_smtp_test_nonce'),
                'reload_disk_file' => wp_create_nonce('wpex_reload_disk_file'),
                'email_log'        => wp_create_nonce('wpex_email_log_action'),
            ],
            'smtp_providers' => [
                'gmail'      => ['name' => 'Gmail SMTP', 'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls', 'hint' => '10,000 free emails/mo. Use your full Gmail address and a 16-character Google App Password.'],
                'mailgun'    => ['name' => 'Mailgun SMTP', 'host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls', 'hint' => '5,000 free emails/mo for 3 months. Username format: postmaster@yourdomain.com.'],
                'outlook'    => ['name' => 'Outlook / Hotmail / Office 365', 'host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Use your Microsoft/Outlook email address and Microsoft App Password.'],
                'yahoo'      => ['name' => 'Yahoo Mail', 'host' => 'smtp.mail.yahoo.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Requires a dedicated Yahoo App Password from Account Security.'],
                'aws_ses'    => ['name' => 'Amazon SES (AWS SES)', 'host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Enter your AWS SES SMTP Username (AKIA...) and SMTP Password.'],
                'zoho'       => ['name' => 'Zoho Mail', 'host' => 'smtp.zoho.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Use your Zoho email address and Zoho generated App Password.'],
                'sendgrid'   => ['name' => 'SendGrid', 'host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Username is strictly "apikey" and Password is your SendGrid API Key.'],
                'sendinblue' => ['name' => 'Sendinblue (Brevo)', 'host' => 'smtp-relay.brevo.com', 'port' => 587, 'encryption' => 'tls', 'hint' => 'Use your Brevo login email and the SMTP master key from your Brevo dashboard.'],
                'other'      => ['name' => 'Other Custom SMTP Server', 'host' => '', 'port' => 587, 'encryption' => 'tls', 'hint' => ''],
            ],
            'smtp_usage' => $usage_data,
            'i18n' => [
                'processing'                => __('Processing...', 'wp-extra'),
                'protected'                 => __('Protected', 'wp-extra'),
                'not_protected'             => __('Not Protected', 'wp-extra'),
                'remove_protection'         => __('Remove Protection', 'wp-extra'),
                'create_protection'         => __('Create Protection File', 'wp-extra'),
                'confirm_remove_htaccess'   => __('Are you sure you want to remove the .htaccess protection file?', 'wp-extra'),
                'error_occurred'            => __('An error occurred.', 'wp-extra'),
                'request_failed'            => __('Request failed.', 'wp-extra'),
                'enter_recipient_email'     => __('Please enter a recipient email address.', 'wp-extra'),
                'connecting_sending'        => __('Connecting & Sending...', 'wp-extra'),
                'send_test_email'           => __('Send Test Email', 'wp-extra'),
                'delivery_successful'       => __('Delivery Successful!', 'wp-extra'),
                'delivery_failed'           => __('Delivery Failed', 'wp-extra'),
                'server_error'              => __('Server Error', 'wp-extra'),
                'loading_details'           => __('Loading details...', 'wp-extra'),
                'to'                        => __('To:', 'wp-extra'),
                'subject'                   => __('Subject:', 'wp-extra'),
                'date_time'                 => __('Date / Time:', 'wp-extra'),
                'mailer_account'            => __('Mailer Account:', 'wp-extra'),
                'error_details'             => __('Error Details:', 'wp-extra'),
                'delivered_successfully'    => __('Delivered Successfully', 'wp-extra'),
                'confirm_resend_email'      => __('Are you sure you want to resend this email?', 'wp-extra'),
                'email_resent_successfully' => __('Email resent successfully!', 'wp-extra'),
                'failed_resend_email'       => __('Failed to resend email.', 'wp-extra'),
                'confirm_delete_log'        => __('Delete this email log record?', 'wp-extra'),
                'confirm_clear_all_logs'    => __('Are you sure you want to delete ALL email log records? This cannot be undone.', 'wp-extra'),
            ],
        ];

        wp_localize_script('wpex-settings', 'wpex_settings', $config);
    }
}
