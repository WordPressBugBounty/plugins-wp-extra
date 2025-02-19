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
                'dashboard' => Modules\Dashboards::class,
                'duplicate' => Modules\Duplicate::class,
                'widget' => Modules\Widgets::class,
                'media' => Modules\Media::class,
                'control' => Modules\Control::class,
            ],
            'frontend' => [
                'logins' => Modules\Branding::class,
                'optimize' => Modules\Optimize::class,
                'code' => Modules\Code::class,
                'cookie' => Modules\Cookie::class,
            ],
            'common' => [
                'admins' => Modules\Permission::class,
                'posts' => Modules\Posts::class,
                'comments' => Modules\Comments::class,
                'security' => Modules\Security::class,
                'smtp' => Modules\SMTP::class,
                'permalinks' => Modules\Permalinks::class,
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
