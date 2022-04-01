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
            $routeCollector->addRoute(['GET', 'POST'], '/getAllApiList', $prefix . 'getAllApiList');
            $routeCollector->addRoute(['GET', 'POST'], '/getUserApiList', $prefix . 'getUserApiList');
            $routeCollector->addRoute(['GET', 'POST'], '/importData', $prefix . 'importData');
            $routeCollector->addRoute(['GET', 'POST'], '/exportBaseInformation', $prefix . 'exportBaseInformation');
            $routeCollector->addRoute(['GET', 'POST'], '/getBatchNumDetail', $prefix . 'getBatchNumDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getBatchNumList', $prefix . 'getBatchNumList');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllTypeMap', $prefix . 'getAllTypeMap');
            $routeCollector->addRoute(['GET', 'POST'], '/getTypeMapByAPPId', $prefix . 'getTypeMapByAPPId');
            $routeCollector->addRoute(['GET', 'POST'], '/addBatchType', $prefix . 'addBatchType');
            $routeCollector->addRoute(['GET', 'POST'], '/editApiUserRelation', $prefix . 'editApiUserRelation');
            $routeCollector->addRoute(['GET', 'POST'], '/getFBatchNumList', $prefix . 'getFBatchNumList');
            $routeCollector->addRoute(['GET', 'POST'], '/getFAbnormalData', $prefix . 'getFAbnormalData');

        });
        return true;
    }
}
