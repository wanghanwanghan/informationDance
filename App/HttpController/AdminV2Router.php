<?php

namespace App\HttpController;

use EasySwoole\Component\Singleton;
use FastRoute\RouteCollector;

class AdminV2Router
{
    use Singleton;

    //加载后台全部api
    function addRouterV1(RouteCollector $routeCollector)
    {
        // $this->SaibopengkeAdmin($routeCollector);
        // $this->GroceryStore($routeCollector);
        $this->UserRouterV1($routeCollector);
    }

    private function UserRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/User/UserController/';

        $routeCollector->addGroup('/user', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/login', $prefix . 'userLogin');
            $routeCollector->addRoute(['GET', 'POST'], '/addUser', $prefix . 'addUser'); 
        });

        return true;
    } 


}
