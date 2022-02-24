<?php

namespace App\HttpController;

use EasySwoole\Component\Singleton;
use FastRoute\RouteCollector;

class AdminNewRouter
{
    use Singleton;

    public $prefix = "/Business/AdminNew/%s/%s/%sController/";

    //加载后台全部api
    function addRouterV1(RouteCollector $routeCollector)
    {
        $this->UserRouterV1($routeCollector, 'Mrxd', 'User', 'User');
        $this->UserRouterV1($routeCollector, 'Mrxd', 'Api', 'Api');
        $this->UserRouterV1($routeCollector, 'Mrxd', 'Menu', 'Menu');
    }

    private function UserRouterV1(RouteCollector $routeCollector, $ent, $module, $name): bool
    {
        $prefix = sprintf($this->prefix, $ent, $module, $name);

        $routeCollector->addGroup("/{$ent}/{$module}", function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/reg', $prefix . 'reg');
        });

        return true;
    }
}
