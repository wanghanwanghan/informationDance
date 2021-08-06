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
        $this->ApiRouterV1($routeCollector);
        $this->StatisticsRouterV1($routeCollector);
        $this->FinanceRouterV1($routeCollector);
        $this->FileTransmissionRouterV1($routeCollector);
    }

    private function UserRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/User/PUserController/';

        $routeCollector->addGroup('/user', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getUserList', $prefix . 'getUserList');
            $routeCollector->addRoute(['GET', 'POST'], '/addUser', $prefix . 'addUser');
            $routeCollector->addRoute(['GET', 'POST'], '/editUser', $prefix . 'editUser');
            $routeCollector->addRoute(['GET', 'POST'], '/getUserApi', $prefix . 'getUserApi');
            $routeCollector->addRoute(['GET', 'POST'], '/editUserApi', $prefix . 'editUserApi');
            $routeCollector->addRoute(['GET', 'POST'], '/editUserApiPrice', $prefix . 'editUserApiPrice');
            $routeCollector->addRoute(['GET', 'POST'], '/addRsaKey', $prefix . 'addRsaKey');
        });

        return true;
    }

    private function ApiRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/Api/PApiController/';

        $routeCollector->addGroup('/api', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getApiList', $prefix . 'getApiList');
            $routeCollector->addRoute(['GET', 'POST'], '/addApi', $prefix . 'addApi');
            $routeCollector->addRoute(['GET', 'POST'], '/editApi', $prefix . 'editApi');
        });

        return true;
    }

    private function StatisticsRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/Statistics/PStatisticsController/';

        $routeCollector->addGroup('/statistics', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getStatisticsList', $prefix . 'getStatisticsList');
        });

        return true;
    }

    private function FinanceRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/Finance/FinanceController/';

        $routeCollector->addGroup('/finance', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getIndex', $prefix . 'getIndex');
            $routeCollector->addRoute(['GET', 'POST'], '/uploadEntList', $prefix . 'uploadEntList');
            $routeCollector->addRoute(['GET', 'POST'], '/getFinanceData', $prefix . 'getFinanceData');
        });

        return true;
    }

    private function FileTransmissionRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/FileTransmission/FileTransmissionController/';

        $routeCollector->addGroup('/transmission', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getFileList', $prefix . 'getFileList');
            $routeCollector->addRoute(['GET', 'POST'], '/uploadFileToDir', $prefix . 'uploadFileToDir');
        });

        return true;
    }

}
