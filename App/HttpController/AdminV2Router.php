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
        $this->PermissionsRouterV1($routeCollector);
        $this->FinanceRouterV1($routeCollector);
    }

    private function UserRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/User/UserController/';

        $routeCollector->addGroup('/user', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/login', $prefix . 'userLogin');
            $routeCollector->addRoute(['GET', 'POST'], '/signOut', $prefix . 'signOut');
            $routeCollector->addRoute(['GET', 'POST'], '/addUser', $prefix . 'addUser');
            $routeCollector->addRoute(['GET', 'POST'], '/updateUserInfo', $prefix . 'updateUserInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/updateUserStatus', $prefix . 'updateUserStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/updatePassword', $prefix . 'updatePassword');
            $routeCollector->addRoute(['GET', 'POST'], '/list', $prefix . 'userList');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllUser', $prefix . 'getAllUser');
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
            $routeCollector->addRoute(['GET', 'POST'], '/getRawMenus', $prefix . 'getRawMenus');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllowedMenus', $prefix . 'getAllowedMenu');
            $routeCollector->addRoute(['GET', 'POST'], '/updateMenuStatus', $prefix . 'updateMenuStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/addMenu', $prefix . 'addMenu');
            $routeCollector->addRoute(['GET', 'POST'], '/updateMenu', $prefix . 'updateMenu');
            $routeCollector->addRoute(['GET', 'POST'], '/getMenuById', $prefix . 'getMenuById');
            // $routeCollector->addRoute(['GET', 'POST'], '/getAllMenu', $prefix . 'getAllMenu'); 
        });

        return true;
    }

    private function FinanceRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Finance/FinanceController/';

        $routeCollector->addGroup('/finance', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getConfigLists', $prefix . 'getConfigLists');
            $routeCollector->addRoute(['GET', 'POST'], '/addConfig', $prefix . 'addConfig');
            $routeCollector->addRoute(['GET', 'POST'], '/updateConfig', $prefix . 'updateConfig');
            $routeCollector->addRoute(['GET', 'POST'], '/updateConfigStatus', $prefix . 'updateConfigStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/uploadeCompanyLists', $prefix . 'uploadeCompanyLists');
            $routeCollector->addRoute(['GET', 'POST'], '/getUploadLists', $prefix . 'getUploadLists'); 
            $routeCollector->addRoute(['GET', 'POST'], '/getNeedsConfirmExportLists', $prefix . 'getNeedsConfirmExportLists'); 
            $routeCollector->addRoute(['GET', 'POST'], '/exportFinanceData', $prefix . 'exportFinanceData'); 
            $routeCollector->addRoute(['GET', 'POST'], '/ConfirmFinanceData', $prefix . 'ConfirmFinanceData');
            $routeCollector->addRoute(['GET', 'POST'], '/getExportLists', $prefix . 'getExportLists');
            $routeCollector->addRoute(['GET', 'POST'], '/exportDetails', $prefix . 'exportDetails');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllYearsRangeList', $prefix . 'getAllYearsRangeList');
            $routeCollector->addRoute(['GET', 'POST'], '/exportExportLists', $prefix . 'exportExportLists');
            $routeCollector->addRoute(['GET', 'POST'], '/exportExportDetails', $prefix . 'exportExportDetails');
            $routeCollector->addRoute(['GET', 'POST'], '/getExportQueueLists', $prefix . 'getExportQueueLists');
             $routeCollector->addRoute(['GET', 'POST'], '/getNeedsConfirmDetails', $prefix . 'getNeedsConfirmDetails');
             $routeCollector->addRoute(['GET', 'POST'], '/getFinanceLogLists', $prefix . 'getFinanceLogLists');
        });

        return true;
    }


    private function RoleRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Role/RoleController/';
        $routeCollector->addGroup('/role', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getAllRoles', $prefix . 'getAllRoles');
            $routeCollector->addRoute(['GET', 'POST'], '/addRole', $prefix . 'addRole');
            $routeCollector->addRoute(['GET', 'POST'], '/updateRole', $prefix . 'updateRole');
            $routeCollector->addRoute(['GET', 'POST'], '/updateRolePermissions', $prefix . 'updateRolePermissions');
            $routeCollector->addRoute(['GET', 'POST'], '/updateUserRoles', $prefix . 'updateUserRoles');
            $routeCollector->addRoute(['GET', 'POST'], '/updateRoleStatus', $prefix . 'updateRoleStatus');
            $routeCollector->addRoute(['GET', 'POST'], '/getRolesPermission', $prefix . 'getRolesPermission');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllowedMenus', $prefix . 'getAllowedMenu');
            // $routeCollector->addRoute(['GET', 'POST'], '/getAllMenu', $prefix . 'getAllMenu'); 
        });

        return true;
    }

    private function PermissionsRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Permission/PermissionController/';
        $routeCollector->addGroup('/permission', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getAllPermissions', $prefix . 'getAllPermissions');
            $routeCollector->addRoute(['GET', 'POST'], '/addRole', $prefix . 'addRole');
            $routeCollector->addRoute(['GET', 'POST'], '/updateRole', $prefix . 'updateRole');
            $routeCollector->addRoute(['GET', 'POST'], '/updateRoleStatus', $prefix . 'updateRoleStatus');
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
