<?php

namespace App\HttpController;

use EasySwoole\Component\Singleton;
use FastRoute\RouteCollector;

class AdminRoles
{
    use Singleton;

    public $prefix = "/Business/AdminRoles/%s/%s/%sController/";

    //加载后台全部api
    function addRouterV1(RouteCollector $routeCollector)
    {
        $this->UserRouterV1($routeCollector, 'Mrxd', 'User', 'User');
    }

    private function UserRouterV1(RouteCollector $routeCollector, $ent, $module, $name): bool
    {
        $prefix = sprintf($this->prefix, $ent, $module, $name);

        $routeCollector->addGroup("/{$ent}/{$module}", function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/userLogin', $prefix . 'userLogin');
        });

        return true;
    }
}
