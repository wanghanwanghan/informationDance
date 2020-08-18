<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\AbstractRouter;
use FastRoute\RouteCollector;

class Router extends AbstractRouter
{
    public function initialize(RouteCollector $routeCollector)
    {
        //全局模式拦截下,路由将只匹配Router.php中的控制器方法响应,将不会执行框架的默认解析
        $this->setGlobalMode(true);

        $routeCollector->addGroup('/api/v1',function (RouteCollector $routeCollector)
        {
            $this->CommonRouteV1($routeCollector);//公共功能
            $this->UserRouteV1($routeCollector);//用户相关
            $this->QiChaChaRouteV1($routeCollector);//企查查路由
            $this->FaHaiRouteV1($routeCollector);//法海路由
        });
    }

    private function CommonRouteV1(RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/comm',function (RouteCollector $routeCollector)
        {
            $routeCollector->addRoute(['GET','POST'],'/image/upload','/Business/Api/Common/CommonController/imageUpload');
        });

        return true;
    }

    private function UserRouteV1(RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/user',function (RouteCollector $routeCollector)
        {
            $routeCollector->addRoute(['GET','POST'],'/reg','/Business/Api/User/UserController/reg');
            $routeCollector->addRoute(['GET','POST'],'/login','/Business/Api/User/UserController/login');
        });

        return true;
    }

    private function QiChaChaRouteV1(RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/qcc',function (RouteCollector $routeCollector)
        {
            $routeCollector->addRoute(['GET','POST'],'/getEntList','/Business/Api/QiChaCha/QiChaChaController/getEntList');//模糊搜索企业
            $routeCollector->addRoute(['GET','POST'],'/getSpecialEntDetails','/Business/Api/QiChaCha/QiChaChaController/getSpecialEntDetails');//律所及其他特殊基本信息
            $routeCollector->addRoute(['GET','POST'],'/getEntType','/Business/Api/QiChaCha/QiChaChaController/getEntType');//企业类型查询

        });

        return true;
    }

    private function FaHaiRouteV1(RouteCollector $routeCollector)
    {
        $routeCollector->addGroup('/fh',function (RouteCollector $routeCollector)
        {
            $routeCollector->addRoute(['GET','POST'],'/test','/Business/Api/FaHai/FaHaiController/index');
        });

        return true;
    }











}
