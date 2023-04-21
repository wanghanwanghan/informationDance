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
            $resultArr['data']
            ,
            '成功',
            true,
            []
        );
    }

    function dealInfoLists(): bool
    {
        $requestData =  $this->getRequestData();
        $requestData =  $this->getRequestData();
        $page = $requestData['pageNum']?:1;
        $pageSize = $requestData['pageSize']?:10;
        $keyWord = $requestData['keyWord']?:'';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://qlycpro.techopen.org.cn/api/transaction/info/list');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"pageNum\":$page,\"pageSize\":$pageSize,\"query\":{\"dataSetType\":0,\"infoType\":0,\"publishTimeType\":0,\"keyWord\":\"\",\"regionCode\":\"\"}}");
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
        $resultArr = json_decode($result,true);
        return $this->writeJson(
            200,
            [
                'pageNum' => $page,
                'pageSize' => $pageSize,
                'total' => $resultArr['data']['total'],
            ] ,
            $resultArr['data']['list']
            ,
            '成功',
            true,
            []
        );
    }

    function biaoXunInfoLists(): bool
    {
        $requestData =  $this->getRequestData();
        $requestData =  $this->getRequestData();
        $page = $requestData['pageNum']?:1;
        $pageSize = $requestData['pageSize']?:10;
        $keyWord = $requestData['keyWord']?:'';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.qibiaoduo.com/api/bid/projects/search/projects?page=3&perPage=20');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"regionCodes\":[],\"ownerNicCodes\":[],\"nicCodes\":[],\"infoTypes\":[],\"keywordsExclude\":[],\"keywordsExtend\":[],\"keywords\":[],\"pageOwnerCodes\":\"18\"}");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Host: api.qibiaoduo.com';
        $headers[] = 'Sec-Ch-Ua: \"Chromium\";v=\"112\", \"Google Chrome\";v=\"112\", \"Not:A-Brand\";v=\"99\"';
        $headers[] = 'Sec-Ch-Ua-Mobile: ?0';
        $headers[] = 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJlbmhhbmNlcyI6IlBDIiwidXNlcl9uYW1lIjoidWlkXzcxNTE2MzQ4Mzg5OTYzNzc2MCIsInNjb3BlIjpbInJlYWQiLCJ3cml0ZSJdLCJ1c2VyOmp3dHY6dWlkXzcxNTE2MzQ4Mzg5OTYzNzc2MCI6ImI0NzUyZDE0LWI0NTEtNDg1Ny04ZmE4LWYwNmE2MDZlNmYxMyIsImV4cCI6MTY4NDY1ODIzNywiYXV0aG9yaXRpZXMiOlsidXNlciJdLCJqdGkiOiJiMTBKbTVNQ1VjeV9qRjBINl8wYlAxdkNVbzAiLCJjbGllbnRfaWQiOiJxaWtlLWJpZC1yZW1lbWJlci1tZSJ9.SXpK9oL158fHB0w9zQROV1zLSkfRlfwEBt5bjXvgsj6wJcLGjSTA4-cR16oEaZMvT5VuyOjjSqBa3Hdct4YuSYZMxgWv_icYN5cvIS6QGKDpdBSys4vVO1zJ7Fe53ltBwfm-MO064Aah7XXfomE3Thy0TLIdZnMQwuILiStlm2HyrHf7UHakp2T4daU7fAcr0RQmXn_6Ib-MB_2JPcKFMBQRCyv88vpcyNepOwHKOEU1R6LaFStUvF9I5F2lmCsePSbmvCVZhG7mD7VBUwzw0uRC9FAYAO2Ak6lviz0Vv1czQNNsXc1aKv-bHRr23HgFp-l-kfqXIKLtJBAF1BfJng';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36';
        $headers[] = 'Content-Type: application/json;charset=UTF-8';
        $headers[] = 'Accept: application/json';
        $headers[] = 'Productid: 20';
        $headers[] = 'Sec-Ch-Ua-Platform: \"Windows\"';
        $headers[] = 'Origin: https://www.qibiaoduo.com';
        $headers[] = 'Sec-Fetch-Site: same-site';
        $headers[] = 'Sec-Fetch-Mode: cors';
        $headers[] = 'Sec-Fetch-Dest: empty';
        $headers[] = 'Referer: https://www.qibiaoduo.com/';
        $headers[] = 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,zh-TW;q=0.7';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $resultArr = json_decode($result,true);
        return $this->writeJson(
            200,
            [
                'pageNum' => $page,
                'pageSize' => $pageSize,
                'total' => $resultArr['data']['count'],
            ] ,
            $resultArr['data']['rows']
            ,
            '成功',
            true,
            []
        );
    }
}