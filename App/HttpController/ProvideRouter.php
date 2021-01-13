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
        $this->TaoShuRouterV1($routeCollector);
        $this->QianQiRouterV1($routeCollector);
        $this->ZhongWangRouterV1($routeCollector);
    }

    private function QiChaChaRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/QiChaCha/QiChaChaController/';

        $routeCollector->addGroup('/qcc', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/getIPOGuarantee', $prefix . 'getIPOGuarantee');
        });

        return true;
    }

    private function TaoShuRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/TaoShu/TaoShuController/';

        $routeCollector->addGroup('/ts', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/lawPersonInvestmentInfo', $prefix . 'lawPersonInvestmentInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getRegisterInfo', $prefix . 'getRegisterInfo');
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

    private function ZhongWangRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/ZhongWang/ZhongWangController/';

        $routeCollector->addGroup('/zw', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/getInvoiceOcr', $prefix . 'getInvoiceOcr');
        });

        return true;
    }











}
