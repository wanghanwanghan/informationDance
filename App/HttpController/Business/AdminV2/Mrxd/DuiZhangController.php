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
use App\HttpController\Models\Api\User;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\BusinessBase\ZhifubaoInfo;
use App\HttpController\Models\MRXD\InformationDanceRequestRecode;
use App\HttpController\Models\MRXD\InformationDanceRequestRecodeStatics;
use App\HttpController\Models\MRXD\ToolsFileLists;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class DuiZhangController  extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    // 用户-上传客户名单
    public function uploadBussinessFile(){
        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();

        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $fileName = date('His').'_'.$fileName;
                $path = TEMP_FILE_PATH .$fileName;

                $ext = pathinfo($path);
                if(
                    $ext['extension']!='xlsx'
                ){
                    return $this->writeJson(203, [], [],'不是xlsx文件('.$ext['extension'].')！');;
                }


                if(file_exists($path)){
                    return $this->writeJson(203, [], [],'文件已存在！');;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }

                $addUploadRecordRes = AdminUserBussinessOpportunityUploadRecord::addRecordV2(
                    [
                        'user_id' => $this->loginUserinfo['id'],
                        'file_path' => TEMP_FILE_PATH,
                        'title' => $requestData['title']?:'',
                        'size' => filesize($path),
                        //是否拉取url联系人
                        'pull_api' => $requestData['pull_api']?1:0,
                        //按手机号拆分成多行
                        'split_mobile' => 1,
                        //删除空号
                        'del_empty' => 1,
                        //匹配微信
                        'match_by_weixin' => 1,
                        //取全字段
                        'get_all_field' => $requestData['get_all_field']?1:0,
                        //填充旧的微信
                        'fill_weixin' => 1,
                        'batch' =>  'BO'.date('YmdHis'),
                        'reamrk' => $requestData['reamrk']?:'',
                        'name' =>  $fileName,
                        'status' => AdminUserFinanceUploadRecord::$stateInit,
                    ]
                );

                if(!$addUploadRecordRes){
                    return $this->writeJson(203, [], [],'入库失败，请联系管理员');
                }
                $succeedNums ++;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], [],'导入失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], [],'导入成功 入库文件数量:'.$succeedNums);
    }

    public function uploadWeiXinFile(){

        $requestData =  $this->getRequestData();
        $succeedFiels = [];
        $files = $this->request()->getUploadedFiles();
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $fileInfo = pathinfo($fileName);
                if($fileInfo['extension']!='xlsx'){
                    return $this->writeJson(203, [], [],'暂时只支持xlsx文件！');
                }
                $fileName = date('Y_m_d_H_i',time()).$fileName;
                $path = OTHER_FILE_PATH . $fileName;
                if(file_exists($path)){
                    return $this->writeJson(203, [], [],'文件已存在！');
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }

                $UploadRecordRes =  ToolsFileLists::addRecordV2(
                    [
                        'admin_id' => $this->loginUserinfo['id'],
                        'file_name' => $fileName,
                        'new_file_name' => '',
                        'remark' => $requestData['remark']?:'',
                        'type' => ToolsFileLists::$type_upload_weixin,
                        'state' => $requestData['state']?:'',
                        'touch_time' => $requestData['touch_time']?:'',
                    ]
                );
                if(!$UploadRecordRes){
                    return $this->writeJson(203, [], [],'文件上传失败');
                }

                $res = QueueLists::addRecord(
                    [
                        'name' => '',
                        'desc' => '',
                        'func_info_json' => json_encode(
                            [
                                'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                                'static_func'=> 'shangChuanWeiXinHao',
                            ]
                        ),
                        'params_json' => json_encode([

                        ]),
                        'type' => ToolsFileLists::$type_upload_weixin,
                        'remark' => '',
                        'begin_date' => NULL,
                        'msg' => '',
                        'status' => QueueLists::$status_init,
                    ]
                );

                $succeedFiels[] = $fileName;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], [],'导入失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], [],'成功 入库文件:'.join(',',$succeedFiels));
    }
    public function uploadZhiFuBaoFile(){

        $requestData =  $this->getRequestData();
        $succeedFiels = [];
        $files = $this->request()->getUploadedFiles();
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $fileInfo = pathinfo($fileName);
                if($fileInfo['extension']!='xlsx'){
                    return $this->writeJson(203, [], [],'暂时只支持xlsx文件！');
                }
                $fileName = date('Y_m_d_H_i',time()).$fileName;
                $path = OTHER_FILE_PATH . $fileName;
                if(file_exists($path)){
                    return $this->writeJson(203, [], [],'文件已存在！');
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }
                $db_data1 = [
                    'admin_id' => $this->loginUserinfo['id'],
                    'file_name' => $fileName,
                    'new_file_name' => '',
                    'remark' => $requestData['remark']?:'',
                    'type' => ToolsFileLists::$type_upload_weixin,
                    'state' => $requestData['state']?:'',
                    'touch_time' => $requestData['touch_time']?:'',
                ];
                $UploadRecordRes =  ToolsFileLists::addRecordV2(
                    $db_data1
                );
                if(!$UploadRecordRes){
                    return $this->writeJson(203, [], [],'文件上传失败');
                }
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        // __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            '上传支付宝文件'=>[
                                '表名' => "tools_file_lists" ,
                                '数据' => $db_data1 ,
                            ]
                        ]
                    ], JSON_UNESCAPED_UNICODE)
                );

                $db_data2 = [
                    'name' => '',
                    'desc' => '',
                    'func_info_json' => json_encode(
                        [
                            'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                            'static_func'=> 'shangChuanZhiFubao',
                        ]
                    ),
                    'params_json' => json_encode([

                    ]),
                    'type' => ToolsFileLists::$type_upload_weixin,
                    'remark' => '',
                    'begin_date' => NULL,
                    'msg' => '',
                    'status' => QueueLists::$status_init,
                ];
                $res = QueueLists::addRecord(
                    $db_data2
                );
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        // __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            '上传支付宝文件——队列'=>[
                                '表名' => "queue_lists" ,
                                '数据' =>  $db_data2,
                            ]
                        ]
                    ], JSON_UNESCAPED_UNICODE)
                );

                $succeedFiels[] = $fileName;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], [],'导入失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], [],'成功 入库文件:'.join(',',$succeedFiels));
    }

    //对账列表-导出
    /***
    https://api.meirixindong.com/admin/v2/duizhang/downloadDataList?
     * phone=13269706193&
     * company_name=&
     * project_type=&
     * charge_state=5&year=2020&
     * month=
     ***/
    public function downloadDataList(){
        $requestData =  $this->getRequestData();
        $requestData['page'] = 1;
        $requestData['pageSize'] = 100;

        $res =  InformationDanceRequestRecodeStatics::getFullDatas(
            $requestData
        );
        $new_res = [];
        foreach ($res["data"] as $resItem){
            $new_res[] = [
                "client_name" => $resItem["client_name"],
                "year" => $resItem["year"],
                "month" => $resItem["month"],
                "day" => $resItem["day"],
                "total_num" => $resItem["total_num"],
                "total_cache_num" => $resItem["total_cache_num"],
                "needs_charge_num" => $resItem["needs_charge_num"],
                "charge_state_cname" => $resItem["charge_state_cname"],
            ];
        }
        $fileName = "对账单_".date("Ymd").".xlsx";
        $url = 'https://api.meirixindong.com/Static/Temp/'.$fileName;

        InformationDanceRequestRecodeStatics::exportData(
            $new_res , $fileName,[
                "用户",
                "年度",
                "月份",
                "日",
                "总次数",
                "缓存总次数",
                "需要计费次数",
                "结算状态",
            ]
        );

        return $this->writeJson(200, [],  [
            $url
        ],'成功');
    }

    //对账列表详情-导出
    public function downloadDetailDataList(){

        return $this->writeJson(200, [],  [
            "https://api.meirixindong.com/Static/OtherFile/2023_02_10_10_55测试上传支付宝.xlsx"
        ],'成功');
    }


    /***
    请求 URL:
     * https://api.meirixindong.com/admin/v2/duizhang/changeChargeState?
     * phone=13269706193&
     * real_charge_money=NaN&
     * cahrge_time=2023-02-06&
     * ids=188,187,186,185,184,183,188,187,186,185,184,183,188,187,186,185,184,183&
     * operation=sss&
     * remark=sssss
     ***/
    public function changeChargeState(){
        $requestData =  $this->getRequestData();
        $ids = $requestData['ids'];
        //$id = "13,14";
        $idsArr = explode(",",$ids);
        CommonService::getInstance()->log4PHP(
            json_encode([
                '对账-结算' => [
                    '$ids'=>$ids,
                    '$idsArr'=>$idsArr,
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        foreach ($idsArr as $id){
            InformationDanceRequestRecodeStatics::updateById(
                $id,[
                    "charge_stage" => InformationDanceRequestRecodeStatics::$charge_stage_done,
                    "charge_time" => date("Y-m-d H:i:s"),
                    "operator_cname" => $requestData['operation'],
                    "real_charge_money" =>  number_format($requestData['real_charge_money']/count($idsArr),3) ,
                    "remark" => $requestData['remark'],
                ]
            );
        }
        return $this->writeJson(200, [],  [],'成功');
    }

    /****
     **/
    public function getDetailList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $pageSize = $requestData['pageSize']?:10;

        //created
        CommonService::getInstance()->log4PHP(
            json_encode([
                '对账-详情' => [
                    'created'=>$requestData["created"],
                ]
            ],JSON_UNESCAPED_UNICODE)
        );


        $staticInfo = InformationDanceRequestRecodeStatics::findById($requestData['id']);

        //本月第一天
        $beginDate = date('Y-m-01', strtotime($staticInfo->month));
        //本月最后一天
        $endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
        $res = InformationDanceRequestRecode::getFullDatas([
            "page" => $page,
            "pageSize" => $pageSize,
            "year" => $staticInfo->year,
            "userId" => $staticInfo->userId,
            "minDate" => $beginDate." 00:00:00",
            "maxDate" => $endDate." 23:59:59",
        ]);

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $res['total'],
            'totalPage' => ceil($res['total']/$pageSize) ,
        ],  $res['data'],'成功');
    }

    /****
    请求 URL: https://api.meirixindong.com/admin/v2/duizhang/getList?
     phone=13269706193&company_name=&project_type=&charge_state=&year=2020&month=

     */
    public function getList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $pageSize = $requestData['pageSize']?:10;

        //年度
        if( $requestData['year'] <= 0 ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    "客户对账模块" => "没指定年限，返回空",
                ],JSON_UNESCAPED_UNICODE)
            );
            $total = 0;
            return $this->writeJson(201, [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPage' => ceil($total/$pageSize) ,
            ],  [],'请指定年限');
        }

        $res =  InformationDanceRequestRecodeStatics::getFullDatas(
            $requestData
        );

        $total = $res['total'];
        foreach ($res['data'] as &$resItem){
            $userInfo = User::findById($resItem["userId"]);
            if($userInfo){
                $resItem["client_name"] =  $userInfo->username;
            }
            $resItem["needs_charge_num"] =  $resItem['total_num'] - $resItem['cache_num'];
            $resItem["charge_state_cname"] =  InformationDanceRequestRecodeStatics::chargeStageMaps()[$resItem['charge_stage']];
        }

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPage' => ceil($total/$pageSize) ,
        ],  $res['data'],'成功');
    }

    public function getUserList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $pageSize = $requestData['pageSize']?:10;

        $res = InformationDanceRequestRecode::getAllUsers();
        //information_dance_user
        $allUsers =   User::findByConditionWithCountInfo(
          [],1,500
        );
        $newUsersInfo = [];
        foreach ($allUsers["data"] as $UserInfo){
            $newUsersInfo[$UserInfo['id']] = $UserInfo;
        }

        $newRes = [];
        foreach ($res as $resItem){
            $newRes[$resItem["userId"]] = $newUsersInfo[$resItem["userId"]]['username'];
        }
        $total = count($res);
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPage' => ceil($total/$pageSize) ,
        ],  $newRes,'成功');
    }

    public function WeiXinFilesList(){
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
        $datas = WechatInfo::findByConditionV2(
            $conditions,$page,$pageSize
        );

        foreach ($datas['data'] as &$dataItem){
            if($dataItem['code']){
                $companyRes = CompanyBasic::findByCode($dataItem['code']);
                $companyRes = $companyRes?$companyRes->toArray():[];
                $dataItem['ENTNAME'] = $companyRes['ENTNAME'];
            }
            $phone_res = \wanghanwanghan\someUtils\control::aesDecode($dataItem['phone'], $dataItem['created_at']);
            $dataItem['phone_res'] = $phone_res;
        }

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $datas['total'],
            'totalPage' => ceil($datas['total']/$pageSize) ,
        ],  $datas['data'],'成功');
    }
    public function ZhiFuBaoFilesList(){
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
        $datas = ZhifubaoInfo::findByConditionV2(
            $conditions,$page,$pageSize
        );

        foreach ($datas['data'] as &$dataItem){
            if($dataItem['code']){
                $companyRes = CompanyBasic::findByCode($dataItem['code']);
                $companyRes = $companyRes?$companyRes->toArray():[];
                $dataItem['ENTNAME'] = $companyRes['ENTNAME'];
            }
            $phone_res = \wanghanwanghan\someUtils\control::aesDecode($dataItem['phone'], $dataItem['created_at']);
            $dataItem['phone_res'] = $phone_res;
        }

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $datas['total'],
            'totalPage' => ceil($datas['total']/$pageSize) ,
        ],  $datas['data'],'成功');
    }


    /**
       用户-上传客户列表
     */
    public function bussinessFilesList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $size = $requestData['pageSize']?:10;

        //bussinessFilesList
        $conditions = [];
        if(
            $requestData['name']
        ){
            $conditions[] = [
                'field' => 'name',
                'value' => '%'.$requestData['name'].'%',
                'operate' => 'like',
            ];
        }
        $createdAtStr = $requestData['created_at'];
        $createdAtArr = explode('|||',$createdAtStr);

        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $conditions[] =  [
                'field' => 'created_at',
                'value' => strtotime($createdAtArr[0].' 00:00:00'),
                'operate' => '>=',
            ];
            $conditions[] = [
                'field' => 'created_at',
                'value' => strtotime($createdAtArr[1]." 23:59:59"),
                'operate' => '<=',
            ];

        }

        $records = AdminUserBussinessOpportunityUploadRecord::findByConditionV2(
            $conditions,
            $page,
            $size
        );
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                '$records'   => $records
//            ])
//        );
        foreach ($records['data'] as &$dataitem){
            $dataitem['status_cname'] = AdminUserBussinessOpportunityUploadRecord::getStatusMap()[$dataitem['status']];
            $dataitem['size'] = self::convert($dataitem['size']) ;
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    'size1'   => $dataitem['size'],
//                    'size2'   => self::convert($dataitem['size']),
//                ])
//            );
        }
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $size,
            'total' => $records['total'],
            'totalPage' => ceil($records['total']/$size) ,
        ],  $records['data'],'成功');
    }

    static  function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    public function redownloadBussinessFile(){
        $requestData =  $this->getRequestData();
        if(
            $requestData['id'] <= 0
        ){
            return $this->writeJson(201, [
               ],  [],'参数缺失');
        }
        return $this->writeJson(200, [],
            AdminUserBussinessOpportunityUploadRecord::updateById(
                $requestData['id'],
                [
                    'status'=>AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success
                ]
            )
            ,'成功 ');
    }

}