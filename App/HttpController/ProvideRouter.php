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
        $this->XinDongRouterV1($routeCollector);
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
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForASSGRO_REL', $prefix . 'getThreeYearsDataForASSGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForLIAGRO_REL', $prefix . 'getThreeYearsDataForLIAGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForVENDINC_REL', $prefix . 'getThreeYearsDataForVENDINC_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForMAIBUSINC_REL', $prefix . 'getThreeYearsDataForMAIBUSINC_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForPROGRO_REL', $prefix . 'getThreeYearsDataForPROGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForNETINC_REL', $prefix . 'getThreeYearsDataForNETINC_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForRATGRO_REL', $prefix . 'getThreeYearsDataForRATGRO_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForTOTEQU_REL', $prefix . 'getThreeYearsDataForTOTEQU_REL');
            $routeCollector->addRoute(['GET', 'POST'], '/getThreeYearsDataForSOCNUM', $prefix . 'getThreeYearsDataForSOCNUM');
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

    private function XinDongRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Provide/XinDong/XinDongController/';

        $routeCollector->addGroup('/xd', function (RouteCollector $routeCollector) use ($prefix)
        {
            $routeCollector->addRoute(['GET', 'POST'], '/getProductStandard', $prefix . 'getProductStandard');
        });

        return true;
    }










}
