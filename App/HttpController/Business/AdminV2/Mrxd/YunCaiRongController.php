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

class YunCaiRongController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getProductNameHot(): bool
    {
        $requestData =  $this->getRequestData();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://qlycpro.techopen.org.cn/api/drugs/getProductNameHot');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Host: qlycpro.techopen.org.cn';
        $headers[] = 'Access-Token: eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiI5MmFlMWY2Ny1jMDFiLTRjNTgtYjZiNS00YzlkOWJiNTRkYTIiLCJzZXNzaW9uSWQiOiI5MmFlMWY2Ny1jMDFiLTRjNTgtYjZiNS00YzlkOWJiNTRkYTIiLCJleHAiOjQ4Mzc0NzQxNDksImlhdCI6MTY4MTgwMDU0OX0.GFW6ZWUX_P6Ar4PaEsCa3kWw1HxwDP22uA6OpfQjvqWDC0QQ2gv84vynf0Ls5_PaUE8OZwfG_IChnoTGZpqfng';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.143 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat';
        $headers[] = 'Content-Type: application/json;charset=UTF-8';
        $headers[] = 'User_token: eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiI5MmFlMWY2Ny1jMDFiLTRjNTgtYjZiNS00YzlkOWJiNTRkYTIiLCJzZXNzaW9uSWQiOiI5MmFlMWY2Ny1jMDFiLTRjNTgtYjZiNS00YzlkOWJiNTRkYTIiLCJleHAiOjQ4Mzc0NzQxNDksImlhdCI6MTY4MTgwMDU0OX0.GFW6ZWUX_P6Ar4PaEsCa3kWw1HxwDP22uA6OpfQjvqWDC0QQ2gv84vynf0Ls5_PaUE8OZwfG_IChnoTGZpqfng';
        $headers[] = 'Referer: https://servicewechat.com/wxed1127fa8a0fc503/74/page-frame.html';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $resultArr =  json_decode($result,true);
        curl_close($ch);


        return $this->writeJson(
            200,
            [

            ] ,
            $resultArr
            ,
            '成功',
            true,
            []
        );
    }
}