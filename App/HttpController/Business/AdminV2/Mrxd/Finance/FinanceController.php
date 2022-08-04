<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Finance;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecordV3;
use App\HttpController\Models\AdminV2\AdminUserRole;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\XinDongService;
use Vtiful\Kernel\Format;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;

class FinanceController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //XX
    public function getConfigLists(){
        $user_id = $this->getRequestData('user_id','') ;
        $type = $this->getRequestData('type','') ;
        $pageNo = $this->getRequestData('pageNo',1) ;
        $pageSize = $this->getRequestData('pageSize',10) ;
        $limit = ($pageNo-1)*$pageSize;
        $sql = "status = 1";
        if(!empty($user_id)){
            $sql .= " and user_id = '{$user_id}'";
        }
        if(!empty($type)){
            $sql .= " and type = '{$type}'";
        }
        $count = AdminUserFinanceConfig::create()->where($sql)->count();
        $list = AdminUserFinanceConfig::create()->where($sql." order by id desc limit {$limit},$pageSize ")->all();
        $paging = [
            'page' => $pageNo,
            'pageSize' => $pageSize,
            'total' => $count,
            'totalPage' => ceil($count/$pageSize) ,
        ];
        foreach ($list as &$dataItem){
            $userRes = AdminNewUser::findById($dataItem['user_id']);
            if($userRes){
                $userRes = $userRes->toArray();
            }
            $dataItem['user_name'] = $userRes['name'];
        }
        return $this->writeJson(
            200,
            $paging,
            $list
        );
    }

    public function getAllowedUploadYears(){
       // $user_id = $this->getRequestData('user_id','') ;
        $user_id = $this->loginUserinfo['id'] ;

        $configs = AdminUserFinanceConfig::getConfigByUserId($user_id);
        $years = json_decode($configs['annually_years'],true);
        foreach (json_decode($configs['normal_years_price_json'],true) as $normal_years_item){
            $years[] = $normal_years_item['year'];
        }
        return $this->writeJson(
            200,
            [],

            array_values($years),
            '成功'
        );
    }

    public function getAllRoles(){ 
        return $this->writeJson(
            200,
            [],
           AdminRoles::create()->where("status = 1")->all()
        );
    }

    public function getAllMenu(){  
        return $this->writeJson(
            200,
            [],
            AdminPrivilegedUser::getMenus(false,$this->loginUserinfo['id'])
        );
    }

    //根据开始年和结束年 列出所有组合
    public function getAllYearsRangeList(){
        $requestData = $this->getRequestData();

        return $this->writeJson(
            200,
            [],
            AdminPrivilegedUser::getMenus(false,$this->loginUserinfo['id'])
        );
    }

    /**
     *  增加财务配置
     */
    public function addConfig(){
        $requestData = $this->getRequestData(); 
        if (
            !$requestData['user_id'] ||
            !$requestData['allowed_fields'] || //允许导出的字段
            !$requestData['type'] || //财务类型
            !$requestData['allowed_total_years_num'] //最多允许导出年限
        ) {
            return $this->writeJson(201);
        }

        $dataItem = [
            'user_id' => $requestData['user_id'],
            //包年价格
            'annually_price' => $requestData['annually_price'],
            //包年年度
            'annually_years' => $requestData['annually_years'],
            'normal_years_price_json' => $requestData['normal_years_price_json']?:'',
            //缓存期
            'cache' => intval($requestData['cache']),
            //财务数据类型
            'type' => $requestData['type'],
            //允许导出的字段
            'allowed_fields' => $requestData['allowed_fields'],
            //导出单年度是否按照包年年度算钱
            'single_year_charge_as_annual' => $requestData['single_year_charge_as_annual'],
            //最大允许导出年份数
            'allowed_total_years_num' => $requestData['allowed_total_years_num'],
            //是否需要确认
            'needs_confirm' => $requestData['needs_confirm'],
            'max_daily_nums' => $requestData['max_daily_nums']?:0,
            'sms_notice_percent' => $requestData['sms_notice_percent']?:0,
            'status' => 1,
        ];
        $res = AdminUserFinanceConfig::addRecordV2(
            $dataItem
        );
        if(!$res){
            return $this->writeJson(203);
        }
        OperatorLog::addRecord(
            [
                'user_id' => $requestData['user_id'],
                'msg' =>  '操作人:'.$this->loginUserinfo['user_name'].' '.json_encode($dataItem)  ,
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '财务配置-新加',
            ]
        );
        return $this->writeJson(200);
    }

     /**
     *  修改财务配置
     */
    public function updateConfig(){
        $requestData = $this->getRequestData(); 
        $info = AdminUserFinanceConfig::create()->where('id',$requestData['id'])->get(); 
        $data = [
            'id' => $requestData['id'],
            'user_id' => $requestData['user_id'] ?   $requestData['user_id']: $info['user_id'],
            'annually_price' => $requestData['annually_price'] ?   $requestData['annually_price']: $info['annually_price'],
            'annually_years' => $requestData['annually_years'] ? $requestData['annually_years']: $info['annually_years'],
            'normal_years_price_json' => $requestData['normal_years_price_json'] ? $requestData['normal_years_price_json']: $info['normal_years_price_json'],
            'cache' => $requestData['cache'] ? $requestData['cache']: $info['cache'],
            'type' => $requestData['type'] ? $requestData['type']: $info['type'],
            'single_year_charge_as_annual' => $requestData['single_year_charge_as_annual'] ? $requestData['single_year_charge_as_annual']: $info['single_year_charge_as_annual'],
            'allowed_total_years_num' => $requestData['allowed_total_years_num'] ? $requestData['allowed_total_years_num']: $info['allowed_total_years_num'],
            'needs_confirm' => isset($requestData['needs_confirm']) ?
                $requestData['needs_confirm']: $info['needs_confirm'],
            'allowed_fields' => $requestData['allowed_fields'] ? $requestData['allowed_fields']: $info['allowed_fields'],
            'max_daily_nums' => $requestData['max_daily_nums'] ? $requestData['max_daily_nums']: $info['max_daily_nums'],
            'sms_notice_value' => $requestData['sms_notice_value'] ? $requestData['sms_notice_value']: $info['sms_notice_value'],
            'status' => AdminUserFinanceConfig::$state_ok,
        ];
        AdminUserFinanceConfig::setStatus(
            $requestData['id'],AdminUserFinanceConfig::$state_del
        );

        $res = AdminUserFinanceConfig::addRecordV2($data);
        if (!$res){
            return $this->writeJson(205,[],[],'修改失败');
        }

        OperatorLog::addRecord(
            [
                'user_id' => $data['user_id'],
                'msg' =>  '操作人:'.$this->loginUserinfo['user_name'].' '.json_encode($data)  ,
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '财务配置-新加',
            ]
        );

        return $this->writeJson(200,[],[],'成功');
    }

    public function queryPower(){
        return AdminNewMenu::create()->all();
    }

    /*
     * 冻结
     */
    public function updateConfigStatus(){
        $id = $this->getRequestData('id');
        $status = $this->getRequestData('status');
        // if (empty($phone)) return $this->writeJson(201, null, null, '参数 不能是空');
        if (empty($status)) return $this->writeJson(201, null, null, 'status 不能是空');
        $info = AdminUserFinanceConfig::create()->where("id = '{$id}' ")->get();
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'id' => $id,
            'status' => $status,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

    // 用户-上传客户名单
    public function uploadeCompanyLists(){
        $years = trim($this->getRequestData('years'));
        if(empty($years) ){
            return $this->writeJson(206, [] ,   [], '缺少年度参数('.$years.')', true, []);
        }

        //最多导出年限
        if(
            !AdminUserFinanceConfig::checkExportYearsNums(
                $this->loginUserinfo['id'],
                count(json_decode($years,true))
            )
        ){
            return $this->writeJson(206, [] ,   [], '超出年限！', true, []);
        }

        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();

        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $path = TEMP_FILE_PATH . $fileName;
                if(file_exists($path)){
                   return $this->writeJson(203, [], [],'文件已存在！');;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    CommonService::getInstance()->log4PHP( json_encode(['uploadeCompanyLists   file_not_exists moveTo false ', 'params $path '=> $path,  ]) );
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }

                $UploadRecordRes =  AdminUserFinanceUploadRecord::findByIdAndFileName(
                    $this->loginUserinfo['id'],   
                    $fileName
                );
                if($UploadRecordRes){
                    return $this->writeJson(203, [], [],'文件已存在！');
                }

                $addUploadRecordRes = AdminUserFinanceUploadRecord::addUploadRecord(
                    [
                        'user_id' => $this->loginUserinfo['id'],
                        'file_path' => $path,
                        'years' => $requestData['years'],
                        'file_name' => $fileName,
                        'title' => $requestData['title']?:'',
                        'reamrk' => $requestData['reamrk']?:'',
                        'batch' => 'CWMD'.date('YmdHis'),
                        'finance_config' => json_encode(
                            AdminUserFinanceConfig::getConfigDataByUserId(
                                $this->loginUserinfo['id']
                            )
                        ),
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

    //用户上传的列表 所有上传的客户名单
    public function getUploadLists(){
        $page = $this->request()->getRequestParam('pageNo')??1;
        $createdAtStr = $this->getRequestData('created_at');
        $createdAtArr = explode('|||',$createdAtStr);
        $whereArr = [];
        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $whereArr = [
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[0].' 00:00:00'),
                    'operate' => '>=',
                ],
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[1]." 23:59:59"),
                    'operate' => '<=',
                ]
            ];
        }
        if(
            AdminUserRole::checkIfIsAdmin(
                $this->loginUserinfo['id']
            )
        ){

        }else{
            $whereArr[] =  [
                'field' => 'user_id',
                'value' => $this->loginUserinfo['id'],
                'operate' => '=',
            ];
        }


        $res = AdminUserFinanceUploadRecord::findByConditionV3(
            $whereArr,
            $page
        );
        foreach ($res['data'] as &$dataItem){
            $dataItem['status_cname'] = AdminUserFinanceUploadRecordV3::getStatusMaps()[$dataItem['status']];
            $dataItem['if_can_download'] = AdminUserFinanceUploadRecord::ifCanDownload($dataItem['id'])?1:0;
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>   $totalPages = ceil( $res['total']/ 10 ),
        ],  $res['data'],'成功');
    }

    //获取导出列表|财务对账列表
    public function getExportLists(){
        $page = $this->request()->getRequestParam('pageNo')??1;
        $whereArr = [];
        if(
            AdminUserRole::checkIfIsAdmin(
                $this->loginUserinfo['id']
            )
        ){

        }
        else{
            $whereArr[] =  [
                'field' => 'user_id',
                'value' => $this->loginUserinfo['id'],
                'operate' => '=',
            ];
        }

        $res = AdminUserFinanceExportRecord::findByConditionV3(
            $whereArr,
            $page
        );

        foreach ($res['data'] as &$value){
            $value['upload_details'] = [];
            if(
                $value['upload_record_id']
            ){
                $obj  =AdminUserFinanceUploadRecord::findById($value['upload_record_id']);
                $obj && $value['upload_details'] = $obj->toArray();
            }
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }

    //导出财务扣费记录  财务对账
    public function exportExportLists(){
        $startMemory = memory_get_usage();
        $requestData =  $this->getRequestData();
        $where = [
             [
                'field'=>'user_id',
                'value'=> $this->loginUserinfo['id'],
                'operate' => '=',
             ]
        ];
        if(
            $requestData['ids']
        ){
//            $where[] = [
//                'field'=>'id',
//                'value'=> explode(',',$requestData['ids']),
//                'operate' => 'IN',
//            ];
        }
        $res = AdminUserFinanceExportRecord::getYieldDataToExport(
            $where
        );

        //===================================
        $config=  [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis').'.xlsx';
        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, 'sheet');
        $fileHandle = $fileObject->getHandle();

        $format = new Format($fileHandle);
        $colorStyle = $format
            ->fontColor(Format::COLOR_ORANGE)
            ->border(Format::BORDER_DASH_DOT)
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $format = new Format($fileHandle);

        $alignStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $fileObject
            ->defaultFormat($colorStyle)
            ->header(
                ['订单号','日期','文件名','费用']
            )
            ->defaultFormat($alignStyle)
        ;

        foreach ($res as $dataItem){
            $fileObject ->data([$dataItem]);
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
            ])
        );

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource();

        $fileObject->output();

        //===================================


        return $this->writeJson(200,  [],  [
            'path' => '/Static/Temp/'.$filename,
            'filename' => $filename
        ],'成功');

    }

    public function exportExportListsbak(){
        $requestData =  $this->getRequestData();
        $where = [
            [
                'field'=>'user_id',
                'value'=> $this->loginUserinfo['id'],
                'operate' => '=',
            ]
        ];
        if(
            $requestData['ids']
        ){
//            $where[] = [
//                'field'=>'id',
//                'value'=> explode(',',$requestData['ids']),
//                'operate' => 'IN',
//            ];
        }
        $res = AdminUserFinanceExportRecord::findByConditionV4(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                'exportExportLists empty data',
                '$where' => $where,
                '$requestData'=>$requestData,
                '$res' =>$res,
            ])
        );

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis').'.xlsx';
//        NewFinanceData::parseDataToXls(
//            $config,$filename,['测试1','测试2','测试3'],array_values($res),'sheet1'
//        );
        $exportData = [];
        foreach ($res as $dataItem){
            $exportData[] = [
                $dataItem['id'],
                date('Y-m-d H:i:s',$dataItem['created_at']),
                $dataItem['file_name'],
                $dataItem['price'],
            ];
        }
        NewFinanceData::parseDataToXls(
            $config,$filename,['订单号','日期','文件名','费用'],$exportData,'sheet1'
        );

        return $this->writeJson(200,  [],  [
            'path' => '/Static/Temp/'.$filename,
            'filename' => $filename
        ],'成功');

    }

    //我的下载记录
    public function getExportQueueLists(){
        $requestData =  $this->getRequestData();
        $page = $requestData['pageNo']?:1;
        $config = AdminUserFinanceConfig::getConfigByUserId($this->loginUserinfo['id']);
        $whereArr = [
            [
                'field' => 'created_at',
                'value' => strtotime(date("Y-m-d H:i:s", strtotime("-".($config['cache']?:12)." hours"))),
                'operate' => '>=',
            ]
        ];
        if(
            AdminUserRole::checkIfIsAdmin(
                $this->loginUserinfo['id']
            )
        ){

        }else{
//            $whereArr[] =  [
//                'field' => 'user_id',
//                'value' => $this->loginUserinfo['id'],
//                'operate' => '=',
//            ];
        }

        $res = AdminUserFinanceExportDataQueue::findByConditionV3(
            $whereArr,
            $page
        );

        foreach ($res['data'] as &$value){
            $value['upload_details'] = [];
            if(
                $value['upload_record_id']
            ){
                $AdminUserFinanceUploadRecordRes =  AdminUserFinanceUploadRecord::findById($value['upload_record_id']);
                $AdminUserFinanceUploadRecordRes && $value['upload_details'] = $AdminUserFinanceUploadRecordRes->toArray();
            }
            $value['status_cname'] = AdminUserFinanceExportDataQueue::getStatusMap()[$value['status']];
            //user_id
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' => ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }

    //获取待确认的列表
    public function getNeedsConfirmExportLists(){
        $requestData =  $this->getRequestData();

        //--------------------
        $page = $requestData['pageNo']?:1;
        $pageSize = $requestData['pageSize']?:10;

        $status = $this->getRequestData('status');
        $createdAtStr = $this->getRequestData('updated_at');
        $createdAtArr = explode('|||',$createdAtStr);
        $whereArr = [];
        if (
            $status > 0
        ) {
            $whereArr = [
                [
                    'field' => 'status',
                    'value' => $status,
                    'operate' => '=',
                ]
            ];
        }

        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {

            $whereArr[] = [
                'field' => 'updated_at',
                'value' => strtotime($createdAtArr[0].' 00:00:00'),
                'operate' => '>=',
            ];

            $whereArr[] = [
                'field' => 'updated_at',
                'value' => strtotime($createdAtArr[1].' 23:59:59'),
                'operate' => '<=',
            ];
        }

        if(
            AdminUserRole::checkIfIsAdmin(
                $this->loginUserinfo['id']
            )
        ){
            $uid = $this->getRequestData('user_id');
            if($uid){
                $whereArr[] =  [
                    'field' => 'user_id',
                    'value' => $uid,
                    'operate' => '=',
                ];
            }
        }
        else{
            $whereArr[] =  [
                'field' => 'user_id',
                'value' => $this->loginUserinfo['id'],
                'operate' => '=',
            ];
        }

        $whereArr[] =  [
            'field' => 'needs_confirm',
            'value' => 1,
            'operate' => '=',
        ];

        $dataRes = AdminUserFinanceData::findByConditionV3(
            $whereArr,
            $page,
            $pageSize
        );
        //---------------------
        $titls = [
            'id' => 'ID',
            'username'=>'用户名',
            'entName'=>'企业名',
            'period'=>'年度',
            'updated_at'=>'更新时间',
            //'资产总额',
            //'营业总收入'
        ];

        foreach ($dataRes['data'] as &$itme ){
            $res = AdminUserFinanceData::findById($itme['id']);
            $data = $res->toArray();
            $allowedFields = NewFinanceData::getFieldCname(false);
            $configs = AdminUserFinanceConfig::getConfigByUserId($data['user_id']);
            $newFields = [];
            foreach (json_decode($configs['allowed_fields']) as $field){
                $newFields[$field] = $allowedFields[$field];
            }
            foreach ($newFields as $field){
                $titls[$field] = $field;
            }
            $titls['status_cname'] = '状态';
            break;
        }

        $returnDatas = [];
        foreach ($dataRes['data'] as &$itme ){
            $AdminNewUser = AdminNewUser::findById($itme['user_id'])->toArray();
            $tmp = [
                'id'=>$itme['id'],
                'username'=>$AdminNewUser['user_name'],
                'entName'=>$itme['entName'],
                'period'=>$itme['year'],
                'updated_at'=>date('Y-m-d H:i:s',$itme['updated_at']),
            ];
            //---
            $res = AdminUserFinanceData::findById($itme['id']);
            $data = $res->toArray();
            $realFinanceDatId = $data['finance_data_id'];
            $allowedFields = NewFinanceData::getFieldCname(false);
            $configs = AdminUserFinanceConfig::getConfigByUserId($data['user_id']);
            $newFields = [];
            foreach (json_decode($configs['allowed_fields']) as $field){
                $newFields[$field] = $allowedFields[$field];
            }
            $realData = NewFinanceData::findByIdV2($realFinanceDatId,$newFields);
            foreach ($realData as $key=>$datItem){
                $tmp[$key] = $datItem;
            }

            $tmp['status_cname']  =AdminUserFinanceData::getStatusCname()[$itme['status']];
            $returnDatas[] = $tmp;
        }


        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $dataRes['total'],
                'totalPage' => ceil( $dataRes['total']/ $pageSize ),
            ] , [
                'field'=>$titls,
                'data'=>$returnDatas
            ], '成功' );
    }

    //账户流水
    public function getFinanceLogLists(){
        $page = $this->request()->getRequestParam('pageNo')??1;
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
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[0].' 00:00:00'),
                    'operate' => '>=',
                ],
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[1].' 23:59:59'),
                    'operate' => '<=',
                ]
            ];
        }

        if(
            AdminUserRole::checkIfIsAdmin(
                $this->loginUserinfo['id']
            )
        ){

        }else{
            $whereArr[] =  [
                'field' => 'userId',
                'value' => $this->loginUserinfo['id'],
                'operate' => '=',
            ];
        }


        if(
            $requestData['type']
        ){
            $whereArr[] =  [
                'field' => 'type',
                'value' => $requestData['type'],
                'operate' => '=',
            ];
        }
        $res = FinanceLog::findByConditionV3(
            $whereArr,
            $page
        );
        foreach ($res['data'] as  &$dataItem){
            $dataItem['type_cname'] = FinanceLog::getTypeCnameMaps()[$dataItem['type']];
            $userModel = \App\HttpController\Models\AdminV2\AdminNewUser::findById($dataItem['userId']);;
            $dataItem['user_name'] =  $userModel?$userModel->getAttr('user_name'):'';
        }
        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>10,
                'total' => $res['total'],
                'totalPage' => ceil( $res['total']/ 10 ),
            ] , $res['data'], '成功' );
    }

    //充值
    public function chargeAccount(){
        if(
            !ConfigInfo::setRedisNx('chargeAccount',5)
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
        }

        $requestData =  $this->getRequestData();
        //批次号码
        $batchNum = 'CWDC'.date('YmdHis');
        $oldBalance = \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
            $requestData['user_id']
        );
        if(
            $requestData['type'] == \App\HttpController\Models\AdminV2\AdminNewUser::$chargeTypeAdd
        ){
            $newBalance = $oldBalance+ $requestData['money'];
            $title= '充值';
            $type = FinanceLog::$chargeTytpeAdd ;
        }

        if(
            $requestData['type'] == \App\HttpController\Models\AdminV2\AdminNewUser::$chargeTypeDele
        ){
            $newBalance = $oldBalance - $requestData['money'];
            $title= '扣费';
            $type = FinanceLog::$chargeTytpeDele ;
        }
        if(
            ! \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
                $requestData['user_id'],
                \App\HttpController\Models\AdminV2\AdminNewUser::aesEncode($newBalance)
            )
        ){
            return  $this->writeJson(201,[],[],'充值失败，联系管理员');
        }

        if(
            !FinanceLog::addRecordV2(
                [
                    'detailId' => 0,
                    'detail_table' => '',
                    'price' => $requestData['money'],
                    'userId' => $requestData['user_id'],
                    'type' =>  $type,
                    'batch' => $batchNum,
                    'title' => $title,
                    'detail' => json_encode(['operatoer'=>$this->loginUserinfo['name'],'remark' => $requestData['remark']]),
                    'reamrk' => $requestData['remark'].'(操作人'.$this->loginUserinfo['user_name'].')',
                    'status' => $requestData['status']?:1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            )
        ){
            return  $this->writeJson(201,[],[],'入库失败，联系管理员');
        }

        $userInfo = \App\HttpController\Models\AdminV2\AdminNewUser::findById($requestData['user_id']);
        OperatorLog::addRecord(
            [
                'user_id' => $this->loginUserinfo['id'],
                'msg' => $this->loginUserinfo['user_name'].'给用户'.$userInfo['user_name'].'充值'.$requestData['money'].'元('.$title.')【之前余额'.$oldBalance.'，充值好余额'.$newBalance.'】',
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '用户充值',
            ]
        );
        ConfigInfo::removeRedisNx('exportFinanceData2');

        return $this->writeJson(200, [ ] , [], '成功' );
    }
    //待确认详情
    public function getNeedsConfirmDetails(){
        $requestData =  $this->getRequestData();
        if($requestData['id'] <= 0){
            return $this->writeJson(203, [   ] , [], '参数缺失' );
        }
        $res = AdminUserFinanceData::findById($requestData['id']);
        $data = $res->toArray();
        $realFinanceDatId = $data['finance_data_id'];
        $allowedFields = NewFinanceData::getFieldCname(false);
        $configs = AdminUserFinanceConfig::getConfigByUserId($data['user_id']);
        $newFields = [];
        foreach (json_decode($configs['allowed_fields']) as $field){
            $newFields[$field] = $allowedFields[$field];
        }
        $realData = NewFinanceData::findByIdV2($realFinanceDatId,$newFields);
        return $this->writeJson(200, [ ] , $realData , '成功' );
    }
    //确认是否需要
    public function ConfirmFinanceData(){
        $requestData =  $this->getRequestData();
        $ids = explode(',',$requestData['ids']);
        if($requestData['ids']<=0){
            return $this->writeJson(206, [] ,   [], '参数缺失', true, []);
        }

        if(empty($ids)){
            return $this->writeJson(206, [] ,   [], '参数缺失', true, []);
        }
        foreach ($ids as $id){
            $records =AdminUserFinanceData::findById($id)->toArray();
            $oldStatus = $records['status'] ;
            if(
                $records['status'] == $requestData['status']
            ){
                continue;
            }

            $res = AdminUserFinanceData::updateStatus(
                $id,
                $requestData['status']
            );
            if(!$res){
                return $this->writeJson(206, [] ,   [], '确认失败', true, []);
            }

            OperatorLog::addRecord(
                [
                    'user_id' => $this->loginUserinfo['id'],
                    'msg' =>  json_encode([
                        //'entName'=>$records['entName'],
                        'year'=>$records['year'],
                        'old_status'=>$oldStatus,
                        'new_status'=>$requestData['status'],
                    ]),
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '财务确认_'.$records['entName'],
                ]
            );

            if(
                $records['status'] ==  AdminUserFinanceData::$statusNeedsConfirm
            ){

            }else{
                //变更为用户确认中
               $AdminUserFinanceUploadDataRecord =  AdminUserFinanceUploadDataRecord::findByUserFinanceDataId($id);
               $AdminUserFinanceUploadRecord = AdminUserFinanceUploadRecord::findById($AdminUserFinanceUploadDataRecord->getAttr('record_id'));
                AdminUserFinanceUploadRecord::changeStatus(
                    $AdminUserFinanceUploadRecord->getAttr('id'),
                    AdminUserFinanceUploadRecord::$stateNeedsConfirm
                );
            }

        }

        return $this->writeJson(200, [ ], $res, '成功');
    }

    //导出记录对应的详情
    public function exportDetails(){
        $size = $this->request()->getRequestParam('size')??50;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;

        $requestData =  $this->getRequestData();

        if(
            AdminUserRole::checkIfIsAdmin(
                $this->loginUserinfo['id']
            )
        ){
            $res = AdminUserFinanceExportDataRecord::findByExportId(
                $requestData['id']
            );
        }else{
            $res = AdminUserFinanceExportDataRecord::findByUserAndExportId(
                $this->loginUserinfo['id'],
                $requestData['id']
            );
        }
        //

        foreach ($res as &$dataItem){
            $dataItem['details'] = [];
            if($dataItem['upload_data_id']){
                $dataItem['upload_details'] = [];
                $dataItem['data_details'] = [];
                $uploadRes = AdminUserFinanceUploadDataRecord::findById($dataItem['upload_data_id']);
                if($uploadRes){
                    $dataItem['upload_details'] = $uploadRes->toArray();
                }

                $dataRes = AdminUserFinanceData::findById($uploadRes['user_finance_data_id']);
                if($dataRes){
                    $dataItem['data_details'] = $dataRes->toArray();
                }
            }
        }
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$size,
            'total' => count($res),
            'totalPage' => ceil(count($res)/$size),
        ], $res, '');
    }

    //导出某次导出的详情记录
    public function exportExportDetails(){
        $startMemory = memory_get_usage();
        $requestData =  $this->getRequestData();
        $where = [
            [
                'field'=>'user_id',
                'value'=> $this->loginUserinfo['id'],
                'operate' => '=',
            ],
            [
                'field'=>'export_record_id',
                'value'=> $requestData['id'],
                'operate' => '=',
            ],
        ];
        $res = AdminUserFinanceExportDataRecord::getYieldDataToExport($where);
        //===================================
        $config=  [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis').'.xlsx';
        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, 'sheet');
        $fileHandle = $fileObject->getHandle();

        $format = new Format($fileHandle);
        $colorStyle = $format
            ->fontColor(Format::COLOR_ORANGE)
            ->border(Format::BORDER_DASH_DOT)
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $format = new Format($fileHandle);

        $alignStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $fileObject
            ->defaultFormat($colorStyle)
            ->header(
                ['订单号','企业名称','上次收费时间','年度','按年收费/年度','包年收费/开始年','包年收费/结束年','实际收费','收费类型','实际收费备注']
            )
            ->defaultFormat($alignStyle)
        ;

        foreach ($res as $dataItem){
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$dataItem' => $dataItem
//                ])
//            );
            $fileObject ->data([$dataItem]);
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
            ])
        );

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource();

        $fileObject->output();

        //===================================

        return $this->writeJson(200,  [ ],  [
            'path' => '/Static/Temp/'.$filename,
            'filename' => $filename
        ],'成功');
    }
    public function exportExportDetailsbak(){
        $requestData =  $this->getRequestData();

        $res = AdminUserFinanceExportDataRecord::findByUserAndExportId(
            $this->loginUserinfo['id'],
            $requestData['id']
        );

        $exportHeader = ['订单号','企业名称','上次收费时间','年度','按年收费/年度','包年收费/开始年','包年收费/结束年','实际收费','收费类型','实际收费备注'];
        $exportData = [];
        foreach ($res as &$dataItem){
            $dataItem['details'] = [];

            if($dataItem['upload_data_id']){
                $dataItem['upload_details'] = [];
                $dataItem['data_details'] = [];
                $uploadRes = AdminUserFinanceUploadDataRecord::findById($dataItem['upload_data_id']);
                if($uploadRes){
                    $dataItem['upload_details'] = $uploadRes->toArray();
                }

                $dataRes = AdminUserFinanceData::findById($uploadRes['user_finance_data_id']);
                if($dataRes){
                    $dataItem['data_details'] = $dataRes->toArray();
                }
            }

            $exportData[] = [
                'id' => $dataItem['id'],
                'entName' => $dataItem['data_details']['entName'],
                'last_charge_date' => $dataItem['data_details']['last_charge_date'],
                'year' => $dataItem['data_details']['year'],
                'charge_year' => $dataItem['upload_details']['charge_year'],
                'charge_year_start' => $dataItem['upload_details']['charge_year_start'],
                'charge_year_end' => $dataItem['upload_details']['charge_year_end'],
                'real_price' => $dataItem['upload_details']['real_price'],
                'real_price_remark' => $dataItem['upload_details']['real_price_remark'],
                'price_type_remark' => $dataItem['upload_details']['price_type_remark'],
            ];
        }

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis').'.xlsx';
        $exportDataToXlsRes = NewFinanceData::parseDataToXls(
            $config,$filename,$exportHeader,$exportData,'sheet1'
        );

        return $this->writeJson(200,  [ ],  [
            'path' => '/Static/Temp/'.$filename,
            'filename' => $filename
        ],'成功');
    }

    //
    public function getAllFinanceFields(){
        $requestData =  $this->getRequestData();
        $allFields = NewFinanceData::getFieldCname(false);
        return $this->writeJson(200,  [ ],  $allFields,'成功');
    }

    /**
    导出客户名单 异步 只是加入队列
     * TODO： 要加事务
     */
    function exportFinanceData()
    {
        if(
            !ConfigInfo::setRedisNx('exportFinanceData2',5)
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
        }

        $requestData =  $this->getRequestData();
        if(
            $requestData['id'] <= 0
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [ ],'参数缺失');
        }

        //上传记录
        $uploadRes = AdminUserFinanceUploadRecord::findById($requestData['id'])->toArray();

        //只有缓存期内才可以下载
        if(
            !AdminUserFinanceUploadRecord::ifCanDownload($uploadRes['id'])
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [],'时间过长，请重新上传');
        }

        //检查是否是可以下载的状态状态
        if(
            !AdminUserFinanceUploadRecord::checkByStatus(
                $uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet
            )
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [],  '当前状态不允许导出 请稍等');
        }
        $batchNum = 'CWDC'.date('YmdHis');

         //本名单之前是否扣费过
        $chargeBefore = AdminUserFinanceUploadRecord::ifHasChargeBefore($uploadRes['id']);

        //检查余额是否充足
        if(!$chargeBefore){
            //检查余额
            $checkAccountBalanceRes = \App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                $uploadRes['user_id'],
                $uploadRes['money']
            );
            if(!$checkAccountBalanceRes){
                return $this->writeJson(201, null, [],  '余额不足 请充值');
            }
        }

        try {

            DbManager::getInstance()->startTransaction('mrxd');

            //========================
            // 需要付费
            $price = 0;
            if(
                $uploadRes['money'] > 0 &&
                !$chargeBefore
            ){
                $price = $uploadRes['money'];
                //扣余额
                $updateMoneyRes = \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
                    $uploadRes['user_id'],
                    \App\HttpController\Models\AdminV2\AdminNewUser::aesEncode(
                        \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                            $uploadRes['user_id']
                        ) - $uploadRes['money']
                    )
                );

                $addFinanceLogRes = FinanceLog::addRecordV2(
                    [
                        'detailId' => $uploadRes['id'],
                        'detail_table' => 'admin_user_finance_upload_record',
                        'price' => $uploadRes['money'],
                        'userId' => $uploadRes['user_id'],
                        'type' =>  FinanceLog::$chargeTytpeFinance,
                        'batch' => $batchNum,
                        'title' => '导出内容数据扣费',
                        'detail' => '',
                        'reamrk' => '',
                        'status' => 1,
                    ]
                );

                //设置收费时间|本名单的
                $setChargeDateRes = AdminUserFinanceUploadRecord::updateLastChargeDate($uploadRes['id'],date('Y-m-d H:i:s'));


                //设置具体的收费记录
                $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV3(
                    $uploadRes['user_id'],$uploadRes['id']
                );
                foreach($financeDatas as $financeData){
                    if(empty($financeData['AdminUserFinanceData'])){
                        continue;
                    }
                    $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::findById(
                        $financeData['AdminUserFinanceUploadDataRecord']['id']
                    )->toArray();

                    if(  $AdminUserFinanceUploadDataRecord['real_price'] <= 0){
                        continue;
                    }
                    if(  $chargeBefore){
                        continue;
                    }

                    //设置收费记录
                    $addChargeInfoRes  = AdminUserFinanceChargeInfo::addRecordV2(
                        [
                            'user_id' => $AdminUserFinanceUploadDataRecord['user_id'],
                            'batch' => $batchNum.'_'.$AdminUserFinanceUploadDataRecord['id'],
                            'entName' => $financeData['AdminUserFinanceData']['entName'],
                            'start_year' => $AdminUserFinanceUploadDataRecord['charge_year_start'],
                            'end_year' => $AdminUserFinanceUploadDataRecord['charge_year_end'],
                            'year' => $AdminUserFinanceUploadDataRecord['charge_year'],
                            'price' => $AdminUserFinanceUploadDataRecord['real_price'],
                            'price_type' => $AdminUserFinanceUploadDataRecord['price_type'],
                            'status' => AdminUserFinanceChargeInfo::$state_init,
                        ]
                    );
                    if(
                        !$updateMoneyRes ||
                        !$addFinanceLogRes ||
                        !$setChargeDateRes ||
                        !$addChargeInfoRes
                    ){
                        DbManager::getInstance()->rollback('mrxd');
                        OperatorLog::addRecord(
                            [
                                'user_id' => $uploadRes['user_id'],
                                'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].') 具体结果：$updateMoneyRes:'.$updateMoneyRes.' $addFinanceLogRes:'.$addFinanceLogRes.' $setChargeDateRes:'.$setChargeDateRes. ' $addChargeInfoRes:'.$addChargeInfoRes.'('.$AdminUserFinanceUploadDataRecord['id'].')',
                                'details' =>json_encode( XinDongService::trace()),
                                'type_cname' => '【失败】导出财务名单扣费',
                            ]
                        );
                        return $this->writeJson(201,[],[],'收费失败，请联系管理员');
                    }
                }

               OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].")，实际扣费".$uploadRes['money'],
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【成功】导出财务名单扣费成功',
                    ]
                );
            }

            //添加到下载队列
            $addExportQueueRes = AdminUserFinanceExportDataQueue::addRecordV2(
                [
                    'batch' => $batchNum,
                    'user_id' => $this->loginUserinfo['id'],
                    'upload_record_id' => $requestData['id'],
                    'real_charge' =>$price ,
                    'status' => AdminUserFinanceExportDataQueue::$state_init
                ]
            );
            if(
                !$addExportQueueRes
            ){
                DbManager::getInstance()->rollback('mrxd');
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].') 具体结果：$addExportQueueRes:'.$addExportQueueRes,
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【失败】导出财务名单扣费',
                    ]
                );
                return $this->writeJson(201,[],[],'添加到下载队列，请联系管理员');
            }


            //=========================

            DbManager::getInstance()->commit('mrxd');

        }catch (\Throwable $e) {
            DbManager::getInstance()->rollback('mrxd');
            OperatorLog::addRecord(
                [
                    'user_id' => $uploadRes['user_id'],
                    'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].") 错误信息：".json_encode($e->getMessage()),
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '【失败】导出财务名单',
                ]
            );
        }

        ConfigInfo::removeRedisNx('exportFinanceData2');
        return $this->writeJson(200,[],[],'已发起下载，请稍后去【内容记录】中查看');
    }
    function exportFinanceDatabak()
    {
        if(
            !ConfigInfo::setRedisNx('exportFinanceData2',5)
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
        }

        $requestData =  $this->getRequestData();
        if(
            $requestData['id'] <= 0
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [ ],'参数缺失');
        }

        //上传记录
        $uploadRes = AdminUserFinanceUploadRecord::findById($requestData['id'])->toArray();

        //只有缓存期内才可以下载
        if(
            !AdminUserFinanceUploadRecord::ifCanDownload($uploadRes['id'])
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [],'时间过长，请重新上传');
        }

        //检查是否是可以下载的状态状态
        if(
            !AdminUserFinanceUploadRecord::checkByStatus(
                $uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet
            )
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [],  '当前状态不允许导出 请稍等');
        }
        $batchNum = 'CWDC'.date('YmdHis');

        //本名单之前是否扣费过
        $chargeBefore = AdminUserFinanceUploadRecord::ifHasChargeBefore($uploadRes['id']);

        //检查余额是否充足
        if(!$chargeBefore){
            //检查余额
            $checkAccountBalanceRes = \App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                $uploadRes['user_id'],
                $uploadRes['money']
            );
            if(!$checkAccountBalanceRes){
                return $this->writeJson(201, null, [],  '余额不足 请充值');
            }
        }

        // 需要付费
        $price = 0;
        if(
            $uploadRes['money'] > 0 &&
            !$chargeBefore
        ){
            $price = $uploadRes['money'];
            //扣余额
            if(
                ! \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
                    $uploadRes['user_id'],
                    \App\HttpController\Models\AdminV2\AdminNewUser::aesEncode(
                        \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                            $uploadRes['user_id']
                        ) - $uploadRes['money']
                    )
                )
            ){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].")，实际扣费".$uploadRes['money']."失败",
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【失败】导出财务名单扣费',
                    ]
                );
                return $this->writeJson(201, null, [],  '扣余额失败，联系管理员');
            }

            //添加导出费用流水
            if(
                !FinanceLog::addRecordV2(
                    [
                        'detailId' => $uploadRes['id'],
                        'detail_table' => 'admin_user_finance_upload_record',
                        'price' => $uploadRes['money'],
                        'userId' => $uploadRes['user_id'],
                        'type' =>  FinanceLog::$chargeTytpeFinance,
                        'batch' => $batchNum,
                        'title' => '导出内容数据扣费',
                        'detail' => '',
                        'reamrk' => '',
                        'status' => 1,
                    ]
                )
            ){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$requestData['id'].")，实际扣费".$uploadRes['money']."成功，添加导出费用流水失败",
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【失败】导出财务名单扣费成功，添加导出费用流水失败',
                    ]
                );
                return $this->writeJson(201, null, [],  '添加扣费记录失败，联系管理员');
            }

            //设置收费时间|本名单的
            $res = AdminUserFinanceUploadRecord::updateLastChargeDate($uploadRes['id'],date('Y-m-d H:i:s'));
            if(!$res  ){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].")，实际扣费".$uploadRes['money']."成功，设置收费时间失败",
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【失败】导出财务名单扣费成功，设置收费时间失败',
                    ]
                );
                return $this->writeJson(201, null, [],  '设置收费时间失败，联系管理员');
            }

            //设置具体的收费记录
            $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV3(
                $uploadRes['user_id'],$uploadRes['id']
            );
            foreach($financeDatas as $financeData){
                $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::findById(
                    $financeData['AdminUserFinanceUploadDataRecord']['id']
                )->toArray();

                if(  $AdminUserFinanceUploadDataRecord['real_price'] <= 0){
                    continue;
                }
                if(  $chargeBefore){
                    continue;
                }

                //设置收费记录
                $AdminUserFinanceChargeInfoId = AdminUserFinanceChargeInfo::addRecordV2(
                    [
                        'user_id' => $AdminUserFinanceUploadDataRecord['user_id'],
                        'batch' => $batchNum.'_'.$AdminUserFinanceUploadDataRecord['id'],
                        'entName' => $financeData['AdminUserFinanceData']['entName'],
                        'start_year' => $AdminUserFinanceUploadDataRecord['charge_year_start'],
                        'end_year' => $AdminUserFinanceUploadDataRecord['charge_year_end'],
                        'year' => $AdminUserFinanceUploadDataRecord['charge_year'],
                        'price' => $AdminUserFinanceUploadDataRecord['real_price'],
                        'price_type' => $AdminUserFinanceUploadDataRecord['price_type'],
                        'status' => AdminUserFinanceChargeInfo::$state_init,
                    ]
                );
                if(!$AdminUserFinanceChargeInfoId  ){
                    OperatorLog::addRecord(
                        [
                            'user_id' => $uploadRes['user_id'],
                            'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].")，实际扣费".$uploadRes['money']."成功，设置详细收费记录失败(".$AdminUserFinanceUploadDataRecord['id'].")",
                            'details' =>json_encode( XinDongService::trace()),
                            'type_cname' => '【失败】导出财务名单扣费成功，设置详细收费记录失败',
                        ]
                    );
                    return $this->writeJson(201, null, [],  '设置收费时间失败，联系管理员');
                }
            }
            OperatorLog::addRecord(
                [
                    'user_id' => $uploadRes['user_id'],
                    'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].")，实际扣费".$uploadRes['money'],
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '【成功】导出财务名单扣费成功',
                ]
            );
        }

        //添加到下载队列
        if(
            !AdminUserFinanceExportDataQueue::addRecordV2(
                [
                    'batch' => $batchNum,
                    'user_id' => $this->loginUserinfo['id'],
                    'upload_record_id' => $requestData['id'],
                    'real_charge' =>$price ,
                    'status' => AdminUserFinanceExportDataQueue::$state_init
                ]
            )
        ){
            //ConfigInfo::removeRedisNx('exportFinanceData2');
            OperatorLog::addRecord(
                [
                    'user_id' => $uploadRes['user_id'],
                    'msg' => "用户".$uploadRes['user_id']."操作导出客户名单(".$uploadRes['id'].")，实际扣费".$uploadRes['money']."成功，添加到下载队列失败",
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '【失败】导出财务名单扣费成功，添加到下载队列失败',
                ]
            );
            return  $this->writeJson(201,[],[],'添加失败，联系管理员');
        }

        ConfigInfo::removeRedisNx('exportFinanceData2');
        return $this->writeJson(200,[],[],'已发起下载，请稍后去【内容记录】中查看');
    }
}