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
        $this->StatisticsRouterV1($routeCollector);
    }

    private function UserRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/User/PUserController/';

        $routeCollector->addGroup('/user', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/getUserList', $prefix . 'getUserList');
            $routeCollector->addRoute(['GET', 'POST'], '/addUser', $prefix . 'addUser');
            $routeCollector->addRoute(['GET', 'POST'], '/editUser', $prefix . 'editUser');
        });

        return true;
    }

    private function StatisticsRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/statistics/PStatisticsController/';

        $routeCollector->addGroup('/statistics', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/getStatisticsList', $prefix . 'getStatisticsList');
        });

        return true;
    }


}
