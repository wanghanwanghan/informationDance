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
        $this->MailRouterV1($routeCollector);
        $this->SouKeRouterV1($routeCollector);
        $this->ToolsRouterV1($routeCollector);
        $this->InvoiceRouterV1($routeCollector);
        $this->ApiUserRouterV1($routeCollector);
        $this->PApiRouterV1($routeCollector);
        $this->DocumentationRouterV1($routeCollector);
        $this->BusinessOpportunityRouterV1($routeCollector);
        $this->PStaticsRouterV1($routeCollector);
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
            $routeCollector->addRoute(['GET', 'POST'], '/decrypt', $prefix . 'decrypt');
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

    private function MailRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/MailController/';

        $routeCollector->addGroup('/mail', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/mailLists', $prefix . 'mailLists');
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
            $routeCollector->addRoute(['GET', 'POST'], '/getAllowedUploadYears', $prefix . 'getAllowedUploadYears');
            $routeCollector->addRoute(['GET', 'POST'], '/chargeAccount', $prefix . 'chargeAccount');
            $routeCollector->addRoute(['GET', 'POST'], '/getAllFinanceFields', $prefix . 'getAllFinanceFields');
        });

        return true;
    }

    private function SouKeRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/SouKe/SouKeController/';

        $routeCollector->addGroup('/souke', function (RouteCollector $routeCollector) use ($prefix) {

            $routeCollector->addRoute(['GET', 'POST'], '/getSearchOption', $prefix . 'getSearchOption');//所有支持的搜索选项
            $routeCollector->addRoute(['GET', 'POST'], '/calMarketShare', $prefix . 'calMarketShare');//所有支持的搜索选项
            $routeCollector->addRoute(['GET', 'POST'], '/advancedSearch', $prefix . 'advancedSearch');//高级搜索
            $routeCollector->addRoute(['GET', 'POST'], '/advancedSearchOption', $prefix . 'advancedSearchOption');//高级搜索
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyInvestor', $prefix . 'getCompanyInvestor'); //获取该企业所有关系图
            $routeCollector->addRoute(['GET', 'POST'], '/exportEntData', $prefix . 'exportEntData');//导出搜客数据
            $routeCollector->addRoute(['GET', 'POST'], '/getConfigs', $prefix . 'getConfigs');//获取搜客配置
            $routeCollector->addRoute(['GET', 'POST'], '/getExportLists', $prefix . 'getExportLists');//我的下载列表
            $routeCollector->addRoute(['GET', 'POST'], '/getDeliverLists', $prefix . 'getDeliverLists');//我的交付记录
            $routeCollector->addRoute(['GET', 'POST'], '/addConfigs', $prefix . 'addConfigs');//添加配置
            $routeCollector->addRoute(['GET', 'POST'], '/updateConfigs', $prefix . 'updateConfigs');//修改配置
            $routeCollector->addRoute(['GET', 'POST'], '/getAllFields', $prefix . 'getAllFields');//获取全部字段
            $routeCollector->addRoute(['GET', 'POST'], '/deliverCustomerRoster', $prefix . 'deliverCustomerRoster');//确认交付客户
            $routeCollector->addRoute(['GET', 'POST'], '/getDeliverDetails', $prefix . 'getDeliverDetails');//确认交付客户 getDeliverDetails
            //===========
            $routeCollector->addRoute(['GET', 'POST'], '/getStaffInfo', $prefix . 'getStaffInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getCompanyBasicInfo', $prefix . 'getCompanyBasicInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsList', $prefix . 'getCpwsList');
            $routeCollector->addRoute(['GET', 'POST'], '/getCpwsDetail', $prefix . 'getCpwsDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggList', $prefix . 'getKtggList');
            $routeCollector->addRoute(['GET', 'POST'], '/getKtggDetail', $prefix . 'getKtggDetail');
            $routeCollector->addRoute(['GET', 'POST'], '/getHighTecQualifications', $prefix . 'getHighTecQualifications');
            $routeCollector->addRoute(['GET', 'POST'], '/getDengLingQualifications', $prefix . 'getDengLingQualifications');
            $routeCollector->addRoute(['GET', 'POST'], '/getIsoQualifications', $prefix . 'getIsoQualifications');
            $routeCollector->addRoute(['GET', 'POST'], '/getEmploymenInfo', $prefix . 'getEmploymenInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getBusinessScaleInfo', $prefix . 'getBusinessScaleInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getMainProducts', $prefix . 'getMainProducts');
            $routeCollector->addRoute(['GET', 'POST'], '/getTagInfo', $prefix . 'getTagInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getInvestorInfo', $prefix . 'getInvestorInfo');
            //$routeCollector->addRoute(['GET', 'POST'], '/getStaffInfo', $prefix . 'getStaffInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getNamesInfo', $prefix . 'getNamesInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getEsBasicInfo', $prefix . 'getEsBasicInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getShangPinInfo', $prefix . 'getShangPinInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getUploadOpportunityLists', $prefix . 'getUploadOpportunityLists');
            $routeCollector->addRoute(['GET', 'POST'], '/getCountInfo', $prefix . 'getCountInfo');
            $routeCollector->addRoute(['GET', 'POST'], '/getEntLianXi', $prefix . 'getEntLianXi');
        });

        return true;
    }

    //工具
    private function ToolsRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Tools/ToolsController/';

        $routeCollector->addGroup('/tools', function (RouteCollector $routeCollector) use ($prefix) {
            //模板文件
            $routeCollector->addRoute(['GET', 'POST'], '/uploadeTemplateLists', $prefix . 'uploadeTemplateLists');
            //上传文件
            $routeCollector->addRoute(['GET', 'POST'], '/uploadeFiles', $prefix . 'uploadeFiles');
            //获取上传列表
            $routeCollector->addRoute(['GET', 'POST'], '/getUploadLists', $prefix . 'getUploadLists'); //
            //获取上传文件类型
            $routeCollector->addRoute(['GET', 'POST'], '/uploadeTypeLists', $prefix . 'uploadeTypeLists'); // uploadeTypeLists
        });

        return true;
    }

    private function InvoiceRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Invoice/InvoiceController/';

        $routeCollector->addGroup('/invoice', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getList', $prefix . 'getList');
            $routeCollector->addRoute(['GET', 'POST'], '/createZip', $prefix . 'createZip');
            $routeCollector->addRoute(['GET', 'POST'], '/createGetDataTime', $prefix . 'createGetDataTime');
        });

        return true;
    }

    private function ApiUserRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/ApiUser/UserController/';

        $routeCollector->addGroup('/apiuser', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getApiListByUser', $prefix . 'getApiListByUser');
            $routeCollector->addRoute(['GET', 'POST'], '/getUserList', $prefix . 'getUserList');
            $routeCollector->addRoute(['GET', 'POST'], '/editApi', $prefix . 'editApi');
            $routeCollector->addRoute(['GET', 'POST'], '/editUserApi', $prefix . 'editUserApi');//
            $routeCollector->addRoute(['GET', 'POST'], '/getUserApi', $prefix . 'getUserApi');//
            $routeCollector->addRoute(['GET', 'POST'], '/editUserApiPrice', $prefix . 'editUserApiPrice');//
            //
        });

        return true;
    }

    private function PApiRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/PApi/PApiController/';

        $routeCollector->addGroup('/papi', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getApiList', $prefix . 'getApiList');
            $routeCollector->addRoute(['GET', 'POST'], '/addApi', $prefix . 'addApi');
            $routeCollector->addRoute(['GET', 'POST'], '/editApi', $prefix . 'editApi');
        });

        return true;
    }

    //
    private function BusinessOpportunityRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/BusinessOpportunityController/';
        $routeCollector->addGroup('/businessopportunity', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/uploadBussinessFile', $prefix . 'uploadBussinessFile');
            $routeCollector->addRoute(['GET', 'POST'], '/redownloadBussinessFile', $prefix . 'redownloadBussinessFile');
            $routeCollector->addRoute(['GET', 'POST'], '/bussinessFilesList', $prefix . 'bussinessFilesList');
            $routeCollector->addRoute(['GET', 'POST'], '/uploadWeiXinFile', $prefix . 'uploadWeiXinFile');
            $routeCollector->addRoute(['GET', 'POST'], '/WeiXinFilesList', $prefix . 'WeiXinFilesList');
        });

        return true;
    }
    private function DocumentationRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/Documentation/DocumentationController/';

        $routeCollector->addGroup('/documentation', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/delDocumention', $prefix . 'delDocumention');
            $routeCollector->addRoute(['GET', 'POST'], '/getOne', $prefix . 'getOne');
            $routeCollector->addRoute(['GET', 'POST'], '/getAll', $prefix . 'getAll');
            $routeCollector->addRoute(['GET', 'POST'], '/addDocumention', $prefix . 'addDocumention');
            $routeCollector->addRoute(['GET', 'POST'], '/editDocumention', $prefix . 'editDocumention');//
            $routeCollector->addRoute(['GET', 'POST'], '/downloadDocumention', $prefix . 'downloadDocumention');//
        });

        return true;
    }

    private function PStaticsRouterV1(RouteCollector $routeCollector): bool
    {
        $prefix = '/Business/AdminV2/Mrxd/PStatics/PStatisticsController/';

        $routeCollector->addGroup('/pstatics', function (RouteCollector $routeCollector) use ($prefix) {
            $routeCollector->addRoute(['GET', 'POST'], '/getStatisticsList', $prefix . 'getStatisticsList');
            $routeCollector->addRoute(['GET', 'POST'], '/exportCsv', $prefix . 'exportCsv');
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
