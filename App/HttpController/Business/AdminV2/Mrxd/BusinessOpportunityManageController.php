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
use App\HttpController\Models\MRXD\ShangJi;
use App\HttpController\Models\MRXD\ShangJiFields;
use App\HttpController\Models\MRXD\ToolsFileLists;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;

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

    //获取之前配置的基本信息的维度
    public function getFields(){
        $requestData =  $this->getRequestData();

        $fieldsToAdd = [
            "shang_ji_jie_duan"=>"商机阶段",
            "suo_shu_qv_yu"=>"所属区域",
        ] ;

        $allFields = ShangJiFields::findAllByCondition([]);
        $existsFieldsInfo = array_column($allFields,"field_name");
        foreach ($fieldsToAdd as $Field=>$FieldCname){
            if(
                in_array($Field,$existsFieldsInfo)
            ){
                continue;
            }

            //改表结构
            $dbRes = ShangJi::runBySql("ALTER TABLE shang_ji  add COLUMN `$Field` VARCHAR(200) COMMENT '$FieldCname' DEFAULT ''");
            // 框架暂时没开放 SHOW COLUMNS from tablename ; 只好先存到表里.....
            ShangJiFields::addRecordV2(
                [
                    'field_name' => $Field,
                    'field_cname' => $FieldCname,
                ]
            );
        }


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
        return $this->writeJson(200, $res,  $datas,'成功');
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


    //录入商机
    public function addOne(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [  ], [],'成功');
    }

    public function changeFields(){
        $requestData =  $this->getRequestData();

        return $this->writeJson(200, [  ], [],'成功');
    }

    public function changeStage(){
        $requestData =  $this->getRequestData();

        return $this->writeJson(200, [  ], [],'成功');
    }

    public function getStage(){
        $requestData =  $this->getRequestData();

        return $this->writeJson(200, [  ], [
            "xin_jian_shang_ji"=>"新建商机",
            "shang_ji_ces_hi"=>"商机_测试",
        ],'成功');
    }

    public function getBasicData(){
        $requestData =  $this->getRequestData();

        return $this->writeJson(200, [  ], [
            "name"=>"每日心动公司",
            "ying_shou_gui_mo"=>"营收规模",
        ],'成功');
    }

    public function changeBasicData(){
        $requestData =  $this->getRequestData();

        return $this->writeJson(200, [  ], [],'成功');
    }

    public function getContactData(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [  ],
            [
                'name'=>'联系人名称',
                'contact_type'=>'联系人类型',
                'contact'=>'联系方式',
                'reamrk'=>'备注',
            ]
            ,'成功');
    }

    public function setContactData(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [  ], [],'成功');
    }

    public function getcommunicationrecord(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [  ], [
            [
                'communicate_type' => '沟通方式1',
                'communicate_subject' => '沟通主题1',
                'communicate_remark' => '备注1',
            ],
            [
                'communicate_type' => '沟通方式2',
                'communicate_subject' => '沟通主题2',
                'communicate_remark' => '备注2',
            ],
        ],'成功');
    }

    public function addcommunicationrecord(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [  ], [],'成功');
    }

}