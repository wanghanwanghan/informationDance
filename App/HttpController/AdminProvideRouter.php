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
        $this->invoiceRouterV1($routeCollector);
        $this->cheXianWuLiuRouterV1($routeCollector);
        $this->TenderingAndBiddingRouterV1($routeCollector);
        $this->ErrorLogRouterV1($routeCollector);
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
            $routeCollector->addRoute(['GET', 'POST'], '/exportCsv', $prefix . 'exportCsv');
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

    private function invoiceRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/Invoice/InvoiceController/';

        $routeCollector->addGroup('/invoice', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getList', $prefix . 'getList'); 
            $routeCollector->addRoute(['GET', 'POST'], '/createZip', $prefix . 'createZip');
            $routeCollector->addRoute(['GET', 'POST'], '/createGetDataTime', $prefix . 'createGetDataTime');
        });

        return true;
    }

    private function cheXianWuLiuRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/CheXianWuliu/CheXianWuliuController/';

        $routeCollector->addGroup('/chexian', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getList', $prefix . 'getList');
            $routeCollector->addRoute(['GET', 'POST'], '/createZip', $prefix . 'createZip');
            $routeCollector->addRoute(['GET', 'POST'], '/createGetDataTime', $prefix . 'createGetDataTime');
            $routeCollector->addRoute(['GET', 'POST'], '/setIsOk', $prefix . 'setIsOk');
        });

        return true;
    }


    private function TenderingAndBiddingRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/TenderingAndBidding/TenderingAndBiddingController/';

        $routeCollector->addGroup('/TenderingAndBidding', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getList', $prefix . 'getList');
            $routeCollector->addRoute(['GET', 'POST'], '/createZip', $prefix . 'createZip');
        });

        return true;
    }

    /**
     * 错误日志
     * @param RouteCollector $routeCollector
     * @return bool
     */
    private function ErrorLogRouterV1(RouteCollector $routeCollector)
    {
        $prefix = '/Business/Admin/Log/LogController/';

        $routeCollector->addGroup('/log', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getErrList', $prefix . 'getErrList');
        });

        return true;
    }

}
