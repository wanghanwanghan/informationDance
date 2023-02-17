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
    public function downloadDataList(){

        return $this->writeJson(200, [],  [
            "https://api.meirixindong.com/Static/OtherFile/2023_02_10_10_55测试上传支付宝.xlsx"
        ],'成功');
    }

    //对账列表详情-导出
    public function downloadDetailDataList(){

        return $this->writeJson(200, [],  [
            "https://api.meirixindong.com/Static/OtherFile/2023_02_10_10_55测试上传支付宝.xlsx"
        ],'成功');
    }



    public function changeChargeState(){

        return $this->writeJson(200, [],  [],'成功');
    }


    public function getDetailList(){
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
//        $datas = WechatInfo::findByConditionV2(
//            $conditions,$page,$pageSize
//        );


        $total = 2;
        $datas = [
            [
                "id"=>1,
                "year"=> "2022",
                "month"=> "12",
                "day"=> "2022-12-12",
                "request_date"=> "2022-12-12 11:11:11",
                "num"=> "1",
                "if_charge_cname"=> "是",
                "unit_price"=> "10",
                "charge_money"=> "100",
                "charge_state_cname"=> "待结算",
                "real_charge_money"=> "100",
                "charge_time"=> "2022-12-12 12:12:12",
                "operator_cname"=> "隔壁老王",
                "remark"=> "今天是周五！！！！",
            ],
            [
                "id"=>1,
                "year"=> "2022",
                "month"=> "12",
                "day"=> "2022-12-12",
                "request_date"=> "2022-12-12 11:11:11",
                "num"=> "1",
                "if_charge_cname"=> "是",
                "unit_price"=> "10",
                "charge_money"=> "100",
                "charge_state_cname"=> "待结算",
                "real_charge_money"=> "100",
                "charge_time"=> "2022-12-12 12:12:12",
                "operator_cname"=> "隔壁老王",
                "remark"=> "今天是周五！！！！",
            ]
        ];
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'totalPage' => ceil($total/$pageSize) ,
        ],  $datas,'成功');
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
        $whereArr = [];
        $whereArr[] = [
            'field' => 'year',
            'value' => $requestData['year'],
            'operate' => '=',
        ];

        //月份
        $minDate = $requestData['year']."-01";
        $maxDate = $requestData['year']."-12";

        if($requestData["month"]){
            if($requestData["month"]<=9){
                //补个零
                $month = $requestData['year']."-0".$requestData["month"];
            }
            if($requestData["month"]>=10){
                $month = $requestData['year']."-".$requestData["month"];
            }

            $minDate = $month;
            $maxDate = $month;
        }
        $whereArr[] = [
            'field' => 'month',
            'value' => $minDate,
            'operate' => '>=',
        ];

        $whereArr[] = [
            'field' => 'month',
            'value' => $maxDate,
            'operate' => '<=',
        ];

        //客户
        if($requestData['company_name']>0){
            $whereArr[] = [
                'field' => 'userId',
                'value' => $requestData['company_name'],
                'operate' => '=',
            ];
        }


        $res =  InformationDanceRequestRecodeStatics::findByConditionV2(
            $whereArr,$requestData['page']?:1,$requestData['pageSize']?:20
        );
    /***
    "id"=>2,
    "client_name"=> "客户2",
    "project_cname"=> "项目类别1",
    "year"=> "2022",
    "month"=> "12",
    "total_num"=> "200",
    "needs_charge_num"=> "100",
    "total_cost"=> "100",
    "unit_price"=> "1",
    "charge_state_cname"=> "已结算",
    "real_charge_money"=> "100",
    "charge_time"=> "2022-12-12 12:12:12",
    "operator_cname"=> "隔壁老王",
    "remark"=> "今天是周五！！！！！",
     */


        $total = $res['total'];
        foreach ($res['data'] as &$resItem){
            $userInfo = User::findById($resItem["userId"]);
            if($userInfo){
                $resItem["client_name"] =  $userInfo->username;
            }
            $resItem["year"] =  date("Y",$resItem['created_at']);
            $resItem["month"] =  date("m",$resItem['created_at']);
            $resItem["needs_charge_num"] =  $resItem['total_num'] - $resItem['cache_num'];
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