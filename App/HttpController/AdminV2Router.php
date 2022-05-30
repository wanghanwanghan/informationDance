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
        $this->MenuRouterV1($routeCollector);
        $this->RoleRouterV1($routeCollector);
    }

    private function UserRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/User/UserController/';

        $routeCollector->addGroup('/user', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/login', $prefix . 'userLogin');
            $routeCollector->addRoute(['GET', 'POST'], '/signOut', $prefix . 'signOut');
            $routeCollector->addRoute(['GET', 'POST'], '/addUser', $prefix . 'addUser');
            $routeCollector->addRoute(['GET', 'POST'], '/list', $prefix . 'userList');
            $routeCollector->addRoute(['GET', 'POST'], '/getUserInfo', $prefix . 'getUserInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/location', $prefix . 'userLocation');
            $routeCollector->addRoute(['GET', 'POST'], '/purchase/list', $prefix . 'userPurchaseList');
            $routeCollector->addRoute(['GET', 'POST'], '/purchase/do', $prefix . 'userPurchaseDo');
            $routeCollector->addRoute(['GET', 'POST'], '/getUserAuthBook', $prefix . 'getUserAuthBook');
            $routeCollector->addRoute(['GET', 'POST'], '/handleUserAuthBook', $prefix . 'handleUserAuthBook');
            $routeCollector->addRoute(['GET', 'POST'], '/getAbnormalFinance', $prefix . 'getAbnormalFinance');

        });

        return true;
    }

    private function MenuRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Menu/MenuController/';

        $routeCollector->addGroup('/menu', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getAllMenus', $prefix . 'getAllMenu');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllowedMenus', $prefix . 'getAllowedMenu');
            // $routeCollector->addRoute(['GET', 'POST'], '/getAllMenu', $prefix . 'getAllMenu'); 
        });

        return true;
    }
    private function RoleRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Role/RoleController/';
        $routeCollector->addGroup('/role', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getAllRoles', $prefix . 'getAllRoles');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllowedMenus', $prefix . 'getAllowedMenu');
            // $routeCollector->addRoute(['GET', 'POST'], '/getAllMenu', $prefix . 'getAllMenu'); 
        });

        return true;
    }
    private function GroceryStore(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/Admin/GroceryStore/GroceryStoreController/';

        $routeCollector->addGroup('/grocerystore', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/wuliuNode', $prefix . 'wuliuNode');
        });

        return true;
    }

    private function SaibopengkeAdmin(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/Admin/SaibopengkeAdmin/SaibopengkeAdminController/';
        $routeCollector->addGroup('/sbpk', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getDataList', $prefix . 'getDataList');
            $routeCollector->addRoute(['GET', 'POST'], '/statusChange', $prefix . 'statusChange');
            $routeCollector->addRoute(['GET', 'POST'], '/getExportZip', $prefix . 'getExportZip');
            $routeCollector->addRoute(['GET', 'POST'], '/uploadEntList', $prefix . 'uploadEntList');
        });
        return true;
    }
}
