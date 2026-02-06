<?php
namespace WPEXtra;

class WPEXtra {
    
	public function __construct() {
        $this->boot();
    }

    public function boot() {
        $ismodules = Settings::get_option('modules') ?: [];

        if (empty($ismodules)) {
            return;
        }

        $moduleMap = [
            'backend' => [
                'dashboard' => Modules\Backend\Dashboards::class,
                'duplicate' => Modules\Backend\Duplicate::class,
                'widget' => Modules\Backend\Widgets::class,
                'media' => Modules\Backend\Media::class,
                'control' => Modules\Backend\Control::class,
            ],
            'frontend' => [
                'logins' => Modules\Frontend\Branding::class,
                'optimize' => Modules\Frontend\Optimize::class,
                'code' => Modules\Frontend\Code::class,
                'cookie' => Modules\Frontend\Cookie::class,
            ],
            'common' => [
                'admins' => Modules\Common\Permission::class,
                'posts' => Modules\Common\Posts::class,
                'comments' => Modules\Common\Comments::class,
                'security' => Modules\Common\Security::class,
                'smtp' => Modules\Common\SMTP::class,
                'permalinks' => Modules\Common\Permalinks::class,
            ],
        ];

        $userType = is_admin() ? 'backend' : 'frontend';
        $modulesToLoad = array_merge($moduleMap[$userType], $moduleMap['common']);

        foreach ($modulesToLoad as $key => $class) {
            if (in_array($key, $ismodules, true)) {
                new $class();
            }
        }
    }

}
