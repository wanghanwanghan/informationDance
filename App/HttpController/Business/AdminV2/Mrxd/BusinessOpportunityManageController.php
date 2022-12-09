<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
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
use App\HttpController\Models\AdminV2\QueueLists;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\MRXD\ToolsFileLists;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class BusinessOpportunityManageController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    public function getFields(){
        $requestData =  $this->getRequestData();

        $datas = [
            'name' => [
                'field_name'=>'name',
                'field_cname'=>'姓名',
            ],
            'ying_shou_gui_mo' => [
                'field_name'=>'ying_shou_gui_mo',
                'field_cname'=>'营收规模',
            ],
        ];
        return $this->writeJson(200, [  ],  $datas,'成功');
    }

    public function getLists(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $pageSize = $requestData['pageSize']?:10;

        $conditions = [];
        if($requestData['nickname']){
            $conditions[]  =  [
                'field' =>'nickname',
                'value' =>$requestData['nickname'].'%',
                'operate' =>'like',
            ];

        }
        $total = $datas['total'];
        $total = 2;
        $datas['data'] = [
            [
                'id' => 1,
                'name' => '北京每日心动有限公司',
                'target_customers' => [
                    '营收规模' => '千万以上',
                    '团队规模' => '五百人',
                ],
                'remark' => '大客户' ,
                'tags' => [
                    '大客户',
                    '重点客户',
                ] ,
                'stage_cname'=>'新建商机',
            ]
        ];
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPage' => ceil($total/$pageSize) ,
        ],  $datas['data'],'成功');
    }

    public function addOne(){
        $requestData =  $this->getRequestData();

        return $this->writeJson(200, [  ], [],'成功');
    }
}