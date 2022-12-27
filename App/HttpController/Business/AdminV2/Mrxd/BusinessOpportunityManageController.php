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
use App\HttpController\Models\MRXD\ShangJiStage;
use App\HttpController\Models\MRXD\ToolsFileLists;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\PinYin\PinYinService;
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

        $allFields = ShangJiFields::findAllByCondition([]);
        $datas = [];
        foreach ($allFields as $fieldItem){
            $datas[$fieldItem['field_name']] = [
                'field_name'=> $fieldItem['field_name'],
                'field_cname'=>   $fieldItem['field_cname'],
            ];
        }

        return $this->writeJson(200, [],  $datas,'成功');
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
        $datas = ShangJi::findByConditionV2($conditions,$page);
        $showfields = ShangJiFields::findAllByCondition([
            'is_show'=>1
        ]);
        foreach ($datas['data'] as &$datum){
            //其他信息
            $showDatas = [];
            foreach ($showfields as $fieldsData){
                $showDatas[$fieldsData['field_cname']] = $datum[$fieldsData['field_name']];
            }

            //备注信息
            $arr = explode("&%&%&%&%&%&",$datum['remark']) ;
            $remarkArr = [];
            foreach ($arr as $key => $reamrkStr){
                if(!trim($reamrkStr)){
                    continue;
                }
                $remarkArr['备注'.$key]= $reamrkStr;
            }


            //商机阶段
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'shang_ji_jie_duan'=>$datum['shang_ji_jie_duan']
                ],JSON_UNESCAPED_UNICODE)
            );
            if(trim($datum['shang_ji_jie_duan'])){
                $jieduan = ShangJiStage::findByFieldName($datum['shang_ji_jie_duan']);

                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '商机阶段'=>$jieduan->field_cname
                    ],JSON_UNESCAPED_UNICODE)
                );
                $datum['show_fields'] = [
                    $showDatas,[
                        '商机阶段'=>$jieduan->field_cname
                    ],$remarkArr
                ];
            }else{
                $datum['show_fields'] = [
                    $showDatas,$remarkArr
                ];
            }


//            $datum['show_fields'] = [
//                [
//                    '商机名称'=>'测试公司',
//                    '商机阶段'=>'测试结算',
//                ],
//                [
//                    '商机名称'=>'测试公司',
//                    '商机阶段'=>'测试结算',
//                ],
//                [
//                    '商机名称'=>'测试公司',
//                    '商机阶段'=>'测试结算',
//                ],
//            ];
        }
        $total = $datas['total'];
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
        unset($requestData['phone']);
        ShangJi::addRecordV2($requestData);
        return $this->writeJson(200, [],$requestData,'成功');
    }

    public function changeFields(){
        $requestData =  $this->getRequestData();

        //前端传过来的是
        //{"text":"姓名"}
        //data[]:
        //{"text":"营收规模"}

        //取到所有配置的字段
        $allSubmitFields = [];
        foreach ($requestData['data'] as $datum){
            $tmpArr = json_decode($datum,true);
            $allSubmitFields[] = $tmpArr['text'];
        }

        //把字段转换为横杠分隔的英文字符
        $fieldsToAdd = [];
        foreach ($allSubmitFields as $field){
            $length = strlen($field);
            $wordNums = $length/3;
            $newstr = "";
            for ($i=0; $i<$wordNums; $i++){
                $tmpStr  = mb_substr($field, $i, 1, 'utf-8');
                $newstr  .= PinYinService::getPinyin($tmpStr)."_";
            }
            $newstr = substr($newstr, 0, -1);
            $fieldsToAdd[$newstr] =  $field;
        }

        $allFields = ShangJiFields::findAllByCondition([]);
        $existsFieldsInfo = array_column($allFields,"field_name");
        foreach ($fieldsToAdd as $Field=>$FieldCname){
            //存在的就不加了
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

        return $this->writeJson(200, [  ], [$allFields],'成功');
    }

    public function changeStage(){
        $requestData =  $this->getRequestData();

        //前端传过来的是
        //{"text":"阶段1"}
        //data[]:
        //{"text":"阶段2"}

        //取到所有配置的字段
        $allSubmitFields = [];
        foreach ($requestData['data'] as $datum){
            $tmpArr = json_decode($datum,true);
            $allSubmitFields[] = $tmpArr['text'];
        }

        //把字段转换为横杠分隔的英文字符
        $fieldsToAdd = [];
        foreach ($allSubmitFields as $field){
            $length = strlen($field);
            $wordNums = $length/3;
            $newstr = "";
            for ($i=0; $i<$wordNums; $i++){
                $tmpStr  = mb_substr($field, $i, 1, 'utf-8');
                $newstr  .= PinYinService::getPinyin($tmpStr)."_";
            }
            $newstr = substr($newstr, 0, -1);
            $fieldsToAdd[$newstr] =  $field;
        }

        $allFields = ShangJiStage::findAllByCondition([]);
        $existsFieldsInfo = array_column($allFields,"field_name");
        foreach ($fieldsToAdd as $Field=>$FieldCname){
            //存在的就不加了
            if(
                in_array($Field,$existsFieldsInfo)
            ){
                continue;
            }

            ShangJiStage::addRecordV2(
                [
                    'field_name' => $Field,
                    'field_cname' => $FieldCname,
                ]
            );
        }

        return $this->writeJson(200, [  ], [$allFields],'成功');
    }

    public function setStage(){
        $requestData =  $this->getRequestData();

        ShangJi::updateById(
            $requestData['id'],
            [
                'shang_ji_jie_duan'=>$requestData['stage']
            ]
        );
        return $this->writeJson(200, [  ], [],'成功');
    }

    public function setReamrk(){
        $requestData =  $this->getRequestData();
        $dataObj = ShangJi::findById($requestData['id']);
        $reamrk = $dataObj->remark;
        $reamrk .=  $requestData['remark']."&%&%&%&%&%&";

        ShangJi::updateById(
            $requestData['id'],
            [
                'remark'=>$reamrk
            ]
        );
        return $this->writeJson(200, [  ], [],'成功');
    }

    public function getStage(){
        $requestData =  $this->getRequestData();

        $allFields = ShangJiStage::findAllByCondition([]);
        $datas = [];
        foreach ($allFields as $fieldItem){
            $datas[$fieldItem['field_name']] = [
                'field_name'=> $fieldItem['field_name'],
                'field_cname'=>   $fieldItem['field_cname'],
            ];
        }

        return $this->writeJson(200, [],  $datas,'成功');
    }

    public function getBasicData(){
        $requestData =  $this->getRequestData();
        $res = ShangJi::findById(2);
        $res = $res->toArray();
        unset($res['id']);
        unset($res['created_at']);
        unset($res['updated_at']);
        return $this->writeJson(200, [  ], $res,'成功');
    }

    public function changeBasicData(){
        $requestData =  $this->getRequestData();

        return $this->writeJson(200, [  ], [],'成功');
    }

    public function getContactData(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [  ],
            [
                [
                    'name'=>'联系人名称1',
                    'contact_type'=>'联系人类型1',
                    'contact'=>'联系方式1',
                    'reamrk'=>'备注1',
                ],
                [
                    'name'=>'联系人名称',
                    'contact_type'=>'联系人类型',
                    'contact'=>'联系方式',
                    'reamrk'=>'备注',
                ]
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
                'time' => '2022-12-12 23:22:22',
                'subject' => '沟通主题1',
                'details' => '备注1',
            ],
            [
                'time' => '2022-12-12 23:22:22',
                'subject' => '沟通主题1',
                'details' => '备注1',
            ],
        ],'成功');
    }

    public function addcommunicationrecord(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [  ], [],'成功');
    }

}