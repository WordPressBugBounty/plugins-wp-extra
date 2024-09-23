<?php

namespace WPEXtra;

use WPEXtra\Core\ClassEXtra;

class WPEXtra {
    private static $instance;

    public static function instance() {
        if (! isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        $this->boot();
        add_action('plugins_loaded', [$this, 'load_textdomain']);
		add_filter('plugin_action_links_' . plugin_basename(WPEX_FILE), [$this, 'add_plugin_action_links']);
        add_filter('plugin_row_meta', [$this, 'add_plugin_meta_links'], 10, 2 );
    }

    public function load_textdomain() {
        load_plugin_textdomain(WPEX_BASE, false, WPEX_BASE . '/languages/');
    }

    public function add_plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wp-extra' ) . '">' .__( 'Settings' ) . '</a>',
		);
		return array_merge( $action_links, $links );
	}

    public function add_plugin_meta_links($plugin_meta, $plugin_file) {
        if ((plugin_basename(WPEX_FILE) === $plugin_file) && !ClassEXtra::isPro()) {
            $row_meta = array(
                '<a href="https://wpvnteam.com/donate/" target="_blank">' .__('Donate') . '</a>',
            );
            $row_meta[] = '<a href="https://wpvnteam.com/wp-extra/pricing/" target="_blank"><strong style="color:#d54e21;font-weight:bold">' .__('Go Pro') . '</strong></a>';
            return array_merge($plugin_meta, $row_meta);
        }

        return $plugin_meta;
    }
     
    public function boot() {
        new Settings();
        $ismodules = wp_extra_get_option('modules');
        $modules = [
            'dashboard' => Modules\Dashboards::class,
            'posts' => Modules\Posts::class,
            'sslfix' => Modules\ContentFixer::class,
            'media' => Modules\Media::class,
            'code' => Modules\Code::class,
            'admins' => Modules\Permission::class,
            'logins' => Modules\Branding::class,
            'comments' => Modules\Comments::class,
            'widget' => Modules\Widgets::class,
            'security' => Modules\Security::class,
            'cookie' => Modules\Cookie::class,
            'optimize' => Modules\Optimize::class,
            'duplicate' => Modules\Duplicate::class,
            'smtp' => Modules\SMTP::class,
        ];
        foreach ($modules as $key => $class) {
            if (is_string($key) && $ismodules && in_array($key, $ismodules)) {
                new $class;
            }
        }
    }

}