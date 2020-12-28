<?php

namespace App\HttpController;

use EasySwoole\Component\Singleton;
use FastRoute\RouteCollector;

class AdminProvideRouter
{
    use Singleton;

    //加载后台全部api
    function addRouterV1(RouteCollector $routeCollector)
    {
        $this->UserRouterV1($routeCollector);
    }

    private function UserRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/User/PUserController/';

        $routeCollector->addGroup('/user', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/login', $prefix . 'userLogin');
        });

        return true;
    }


}
