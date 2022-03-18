<?php

namespace App\HttpController;

use EasySwoole\Component\Singleton;
use FastRoute\RouteCollector;

class AdminRoles
{
    use Singleton;

    public $prefix = "/Business/AdminRoles/%s/%sController/";

    //加载后台全部api
    function addRouterV1(RouteCollector $routeCollector)
    {
        $this->UserRouterV1($routeCollector, 'User', 'User');
    }

    private function UserRouterV1(RouteCollector $routeCollector, $module, $name): bool
    {
        $prefix = sprintf($this->prefix, $module, $name);

        $routeCollector->addGroup("/{$module}", function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/userLogin', $prefix . 'userLogin');
            $routeCollector->addRoute(['GET', 'POST'], '/getInfoByToken', $prefix . 'getInfoByToken');
            $routeCollector->addRoute(['GET', 'POST'], '/getApiListByUser', $prefix . 'getApiListByUser');
            $routeCollector->addRoute(['GET', 'POST'], '/editApi', $prefix . 'editApi');
            $routeCollector->addRoute(['GET', 'POST'], '/editUserApi', $prefix . 'editUserApi');
            $routeCollector->addRoute(['GET', 'POST'], '/getUserList', $prefix . 'getUserList');
            $routeCollector->addRoute(['GET', 'POST'], '/getUserInfoByAppId', $prefix . 'getUserInfoByAppId');
            $routeCollector->addRoute(['GET', 'POST'], '/editRole', $prefix . 'editRole');
            $routeCollector->addRoute(['GET', 'POST'], '/addUser', $prefix . 'addUser');
            $routeCollector->addRoute(['GET', 'POST'], '/getRoleList', $prefix . 'getRoleList');

        });
        return true;
    }
}
