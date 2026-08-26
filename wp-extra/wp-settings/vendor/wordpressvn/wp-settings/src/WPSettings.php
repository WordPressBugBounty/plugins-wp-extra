<?php

/** v2.8.2 **/

namespace WPVNTeam\WPSettings;

use Adbar\Dot;

class WPSettings
{
    public $title;

    public $menu_title;

    public $slug;

    public $parent_slug;

    public $capability = 'manage_options';

    public $menu_icon;

    public $menu_position;

    public $option_name;

    public $tabs = [];

    public $errors;

    public $flash;

    public $version;

    public $sidebar;

    public $plugin_data;
    
    public $styling_loaded = false;

    public $ajax_save = false;

    public function enable_ajax_save($enabled = true)
    {
        $this->ajax_save = $enabled;

        return $this;
    }

    public function __construct($title, $slug = null)
    {
        $this->title = $title;
        $this->option_name = strtolower(str_replace('-', '_', sanitize_title($this->title)));
        $this->slug = $slug;

        if ($this->slug === null) {
            $this->slug = sanitize_title($title);
        }
    }

    public function set_menu_parent_slug($slug)
    {
        $this->parent_slug = $slug;

        return $this;
    }

    public function set_menu_title($title)
    {
        $this->menu_title = $title;

        return $this;
    }

    public function get_menu_title()
    {
        return $this->menu_title ?? $this->title;
    }

    public function set_capability($capability)
    {
        $this->capability = $capability;

        return $this;
    }

    public function set_option_name($name)
    {
        $this->option_name = $name;

        return $this;
    }

    public function set_menu_icon($icon)
    {
        $this->menu_icon = $icon;

        return $this;
    }

    public function set_menu_position($position)
    {
        $this->menu_position = $position;

        return $this;
    }

    public function set_version($version = null)
    {
        $this->version = $version;

        return $this;
    }
    
    public function set_sidebar($items = []) {
        $this->sidebar = [];

        foreach ($items as $title => $message) {
            $this->sidebar[] = [
                'title' => $title,
                'message' => $message
            ];
        }

        return $this;
    }

    public function get_sidebar() {
        return $this->sidebar;
    }

    public function set_plugin_data($plugin_data = null)
    {
        $this->plugin_data = $plugin_data;

        return $this;
    }

    public function add_to_menu()
    {
        if ($this->parent_slug) {
            \add_submenu_page($this->parent_slug, $this->title, $this->get_menu_title(), $this->capability, $this->slug, [$this, 'render'], $this->menu_position);
        } else {
            \add_menu_page($this->title, $this->get_menu_title(), $this->capability, $this->slug, [$this, 'render'], $this->menu_icon, $this->menu_position);
        }
    }

    public function make()
    {
        $this->errors = new Error($this);
        $this->flash = new Flash($this);
        add_action('admin_init', [$this, 'save'], 20);
        add_action('wp_ajax_wps_save_' . $this->option_name, [$this, 'ajax_save']);
        add_action('admin_menu', [$this, 'add_to_menu'], 20);
        add_action('admin_head', [$this, 'styling'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styling']);
        add_action('admin_footer', [$this, 'scripting'], 20);
        add_filter('admin_footer_text', [$this, 'admin_rate_us']);
        add_action('admin_notices', [$this, 'admin_notice']);
    }

    public function is_on_settings_page()
    {
        $screen = get_current_screen();

        if ($screen && $screen->base === 'settings_page_'.$this->slug) {
            return true;
        }

        return false;
    }

    public function is_on_toplevel_page()
    {
        $screen = get_current_screen();

        if ($screen && $screen->base === 'toplevel_page_'.$this->slug) {
            return true;
        }

        return false;
    }
    
    public function is_on_parent_page()
    {
        $screen = get_current_screen();

        if ($screen && strpos($screen->base, $this->slug) !== false) {
            return true;
        }
        
        return false;
    }


    public function styling()
    {
        if (! $this->is_on_settings_page()) {
            return;
        }
        ?>
        <style>.wps-error-feedback {color: #d63638;margin: 5px 0;}</style>
        <?php
    }
    
    public function enqueue_styling()
    {
        if ($this->styling_loaded) {
            return;
        }

        if ($this->is_on_toplevel_page() || $this->is_on_settings_page() || $this->is_on_parent_page()) {
            wp_enqueue_script('clipboard');
            wp_enqueue_style('wp-components');
            $css_ver = file_exists(plugin_dir_path(__FILE__) . '../resources/css/wp-settings.css') ? filemtime(plugin_dir_path(__FILE__) . '../resources/css/wp-settings.css') : '1.0';
            wp_enqueue_style('wp-settings', plugin_dir_url(__FILE__) . '../resources/css/wp-settings.css', [], $css_ver);
            wp_enqueue_script('wp-settings', plugin_dir_url(__FILE__) . '../resources/js/wp-settings.js', [], $css_ver, true);
        }

        $this->styling_loaded = true;
    }
    
    public function scripting()
    {
        if ($this->is_on_toplevel_page() || $this->is_on_settings_page() || $this->is_on_parent_page()) {
            add_thickbox();
        }
    }
    
    public function admin_rate_us( $footer_text ) {
        if ( isset($_GET['page']) && $_GET['page'] === $this->slug && $this->plugin_data ) {
            if ( ! function_exists('get_plugin_data') ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $plugin_data = get_plugin_data( $this->plugin_data );
            $thank_text = sprintf(
                /* translators: 1. link to plugin uri; 2. link to plugin name; 3. link to author name */
                __( 'Thank you for using <a href="%1$s" target="_blank">%2$s</a>. Made with <span class="wps-heart" style="color:#e11d48;">♥</span> by <strong>%3$s</strong>', 'wp-settings' ),
                esc_url($plugin_data['PluginURI']),
                esc_html($plugin_data['Name']),
                esc_html($plugin_data['AuthorName'])
            );
            return '<span id="footer-thankyou">' . $thank_text . '</span>';
        }
        return $footer_text;
    }
    
    public function admin_notice() {
        // No license notices
    }

    public function get_tab_by_slug($slug)
    {
        foreach ($this->tabs as $tab) {
            if ($tab->slug === $slug) {
                return $tab;
            }
        }

        return false;
    }

    public function get_active_tab()
    {
        $default = $this->tabs[0] ?? false;
        $tab_slug = $_REQUEST['tab'] ?? ($_POST['tab'] ?? ($_GET['tab'] ?? null));

        if ($tab_slug) {
            return in_array($tab_slug, array_map(function ($tab) {
                return $tab->slug;
            }, $this->tabs)) ? $this->get_tab_by_slug($tab_slug) : $default;
        }

        return $default;
    }

    public function add_tab($title, $slug = null)
    {
        $tab = new Tab($this, $title, $slug);

        $this->tabs[] = $tab;

        return $tab;
    }

    public function add_section($title, $args = [])
    {
        if (empty($this->tabs)) {
            $tab = $this->add_tab('Unnamed tab');
        } else {
            $tab = end($this->tabs);
        }

        return $tab->add_section($title, $args);
    }

    public function add_option($type, $args = [])
    {
        $tab = end($this->tabs);

        if (! $tab instanceof Tab) {
            return false;
        }

        $section = end($tab->sections);

        if (! $section instanceof Section) {
            return false;
        }

        return $section->add_option($type, $args);
    }

    public function should_make_tabs()
    {
        return count($this->tabs) > 1;
    }

    public function get_url()
    {
        if ($this->parent_slug && strpos($this->parent_slug, '.php') !== false) {
            return \add_query_arg('page', $this->slug, \admin_url($this->parent_slug));
        }

        return \admin_url("admin.php?page=$this->slug");
    }
    
    public function get_full_url()
    {
        $params = [];

        if ($active_tab = $this->get_active_tab()) {
            $params['tab'] = $active_tab->slug;

            if ($active_section = $active_tab->get_active_section()) {
                $params['section'] = $active_section->slug;
            }
        }
        
        return add_query_arg($params, $this->get_url());
    }

    public function render_tab_menu()
    {
        if (! $this->should_make_tabs()) {
            return;
        }

        view('tab-menu', ['settings' => $this]);
    }

    public function render_active_sections()
    {
        view('sections', ['settings' => $this]);
    }

    public function render()
    {
        Enqueuer::setEnqueueManager(new EnqueueManager);

        do_action('wp_settings_before_render_settings_page');

        Enqueuer::enqueue();

        view('settings-page', ['settings' => $this]);

        do_action('wp_settings_after_render_settings_page');
    }

    public function get_options()
    {
        return get_option($this->option_name, []);
    }

    public function find_option($search_option)
    {
        foreach ($this->tabs as $tab) {
            foreach ($tab->sections as $section) {
                foreach ($section->options as $option) {
                    if ($option->args['name'] == $search_option) {
                        return $option;
                    }
                }
            }
        }

        return false;
    }

    public function save()
    {
        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'wp_settings_save_'.$this->option_name)) {
            return;
        }

        if (! current_user_can($this->capability)) {
            wp_die(__('You need a higher level of permission.'));
        }
        
        if (isset($_POST['do_reset'])) {
            return $this->set_reset();
        }

        $current_options = $this->get_options();
        $submitted_options = apply_filters('wp_settings_new_options', $_POST[$this->option_name] ?? [], $current_options);
        $new_options = new Dot($current_options);

        foreach ($this->get_active_tab()->get_active_sections() as $section) {
            foreach ($section->options as $option) {
                $value = (new Dot($submitted_options))
                    ->get($option->implementation->get_option_key_path());

                $valid = $option->validate($value);

                if (! $valid) {
                    continue;
                }

                $value = apply_filters('wp_settings_new_options_'.$option->implementation->get_name(), $option->implementation->sanitize($value), $option->implementation);

                $new_options->set($option->implementation->get_option_key_path(), $value);
            }
        }

        update_option($this->option_name, $new_options->pull());

        $this->flash->set('success', __('Settings saved.'));
    }

    public function ajax_save()
    {
        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'wp_settings_save_' . $this->option_name)) {
            wp_send_json_error(['message' => __('Something went wrong.')]);
        }

        if (! current_user_can($this->capability)) {
            wp_send_json_error(['message' => __('You need a higher level of permission.')]);
        }

        $current_options = $this->get_options();
        $submitted_options = apply_filters('wp_settings_new_options', $_POST[$this->option_name] ?? [], $current_options);
        $new_options = new Dot($current_options);

        $active_tab = $this->get_active_tab();
        if ($active_tab) {
            foreach ($active_tab->get_active_sections() as $section) {
                foreach ($section->options as $option) {
                    $value = (new Dot($submitted_options))
                        ->get($option->implementation->get_option_key_path());

                    $valid = $option->validate($value);
                    if (! $valid) {
                        continue;
                    }

                    $value = apply_filters('wp_settings_new_options_' . $option->implementation->get_name(), $option->implementation->sanitize($value), $option->implementation);
                    $new_options->set($option->implementation->get_option_key_path(), $value);
                }
            }
        }

        update_option($this->option_name, $new_options->pull());

        ob_start();
        $this->render_tab_menu();
        $tab_menu_html = ob_get_clean();

        $is_module_tab = ($active_tab && isset($this->tabs[0]) && $active_tab->slug === $this->tabs[0]->slug);

        wp_send_json_success([
            'message' => __('Settings saved.'),
            'tab_menu_html' => $tab_menu_html,
            'is_module_tab' => $is_module_tab,
        ]);
    }
    
    public function set_reset()
    {
        if (! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'wp_settings_save_' . $this->option_name)) {
            return;
        }

        if (! current_user_can($this->capability)) {
            wp_die(__('You need a higher level of permission.'));
        }

        $default_options = $this->get_default_options();
        
        update_option($this->option_name, $default_options);

        $this->flash->set('success', __('Changes saved.'));
    }
    
    public function get_default_options()
    {
        $default_options = [];
        foreach ($this->get_active_tab()->get_active_sections() as $section) {
            foreach ($section->options as $option) {
                $default_options[$option->implementation->get_option_key_path()] = $option->implementation->get_default_value();
            }
        }
        return $default_options;
    }

    /**
     * @deprecated
     */
    public function maybe_unset_options($current_options, $new_options)
    {
        if (! isset($_REQUEST['wp_settings_submitted'])) {
            return $current_options;
        }

        foreach ($_REQUEST['wp_settings_submitted'] as $submitted) {
            if (empty($new_options[$submitted])) {
                unset($current_options[$submitted]);
            }
        }

        return $current_options;
    }

}
