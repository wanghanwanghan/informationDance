<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class MailController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }


    public function mailLists(){
        $page = $this->request()->getRequestParam('page')??1;
        $requestData =  $this->getRequestData();
        $createdAtStr = $this->getRequestData('created_at');
        $createdAtArr = explode('|||',$createdAtStr);
        $whereArr = [];
        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $whereArr = [
                [
                    'field' => 'date',
                    'value' => $createdAtArr[0].' 00:00:00',
                    'operate' => '>=',
                ],
                [
                    'field' => 'date',
                    'value' => $createdAtArr[1].' 23:59:59',
                    'operate' => '<=',
                ]
            ];
        }
//        $whereArr[] =  [
//            'field' => 'userId',
//            'value' => $this->loginUserinfo['id'],
//            'operate' => '=',
//        ];
//        if(
//            $requestData['type']
//        ){
//            $whereArr[] =  [
//                'field' => 'type',
//                'value' => $requestData['type'],
//                'operate' => '=',
//            ];
//        }
        $res = MailReceipt::findByConditionV2(
            $whereArr,
            $page
        );
        foreach ($res['data'] as  &$dataItem){
            //$dataItem['type_cname'] = FinanceLog::getTypeCnameMaps()[$dataItem['type']];
        }
        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>10,
                'total' => $res['total'],
                'totalPage' => ceil( $res['total']/ 10 ),
            ] , $res['data'], '成功' );
    }
}