<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Business\OnlineGoods\Mrxd\DaiKuanController;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserBussinessOpportunityUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\AdminUserWechatInfoUploadRecord;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\MRXD\OnlineGoodsCommissionGrantDetails;
use App\HttpController\Models\MRXD\OnlineGoodsCommissions;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanBank;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanProducts;
use App\HttpController\Models\MRXD\OnlineGoodsTiXianJiLu;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\MRXD\OnlineGoodsUserBaoXianOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserDaikuanOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserInviteRelation;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class YunCaiRongNewController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getHotWords(): bool
    {
        $requestData =  $this->getRequestData();

        return $this->writeJson(
            200,
            [

            ] ,
            []
            ,
            '成功',
            true,
            []
        );
    }

    function getJiaoYiInfo(): bool
    {
        $requestData =  $this->getRequestData();

        return $this->writeJson(
            200,
            [

            ] ,
            []
            ,
            '成功',
            true,
            []
        );
    }
}