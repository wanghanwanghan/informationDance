<?php

namespace App\HttpController;

use EasySwoole\Component\Singleton;
use FastRoute\RouteCollector;

class ProvideRouter
{
    use Singleton;

    //加载对外全部api
    function addRouterV1(RouteCollector $routeCollector)
    {
        $this->QiChaChaRouterV1($routeCollector);
        $this->QianQiRouterV1($routeCollector);
    }

    private function QiChaChaRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/QiChaCha/QiChaChaController/';

        $routeCollector->addGroup('/qcc', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/getTest', $prefix . 'getTest');
        });

        return true;
    }

    private function QianQiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/QianQi/QianQiController/';

        $routeCollector->addGroup('/qq', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsData', $prefix . 'getThreeYearsData');
        });

        return true;
    }













}
