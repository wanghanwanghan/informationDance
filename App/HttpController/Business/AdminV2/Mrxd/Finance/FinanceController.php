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
use App\HttpController\Models\AdminV2\FinanceLog;
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
use Vtiful\Kernel\Format;

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
            'totalPage' => (int)($count/$pageSize)+1,
        ];
        foreach ($list as &$dataItem){
            $userRes = AdminNewUser::findById($dataItem['user_id'])->toArray();
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
            'needs_confirm' => $requestData['needs_confirm'] ?
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
        $page = $this->request()->getRequestParam('page')??1;
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
                    'value' => strtotime($createdAtArr[0]),
                    'operate' => '>=',
                ],
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[1]),
                    'operate' => '<=',
                ]
            ];
        }
        $whereArr[] =  [
            'field' => 'user_id',
            'value' => $this->loginUserinfo['id'],
            'operate' => '=',
        ];
        $res = AdminUserFinanceUploadRecord::findByConditionV3(
            $whereArr,
            $page
        );
        foreach ($res['data'] as &$dataItem){
            $dataItem['status_cname'] = AdminUserFinanceUploadRecord::getStatusMaps()[$dataItem['status']];
            $dataItem['if_can_download'] = AdminUserFinanceUploadRecord::ifCanDownload($dataItem['id'])?1:0;
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>20,
            'total' => $res['total'],
            'totalPage' =>   $totalPages = ceil( $res['total']/ 20 ),
        ],  $res['data'],'成功');
    }

    //获取导出列表|财务对账列表
    public function getExportLists(){
        $page = $this->request()->getRequestParam('page')??1;
        $res = AdminUserFinanceExportRecord::findByConditionV3(

            [
                [
                    'field' => 'user_id',
                    'value' => $this->loginUserinfo['id'],
                    'operate' => '=',
                ],
            ],
            $page
        );

        foreach ($res['data'] as &$value){
            $value['upload_details'] = [];
            if(
                $value['upload_record_id']
            ){
                $value['upload_details'] = AdminUserFinanceUploadRecord::findById($value['upload_record_id'])->toArray();
            }
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 20 ),
        ], $res['data'],'成功');
    }

    //导出之前的导出记录  财务对账
    public function exportExportLists(){
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
        $page = $requestData['page']?:1;
        $config = AdminUserFinanceConfig::getConfigByUserId($this->loginUserinfo['id']);

        $res = AdminUserFinanceExportDataQueue::findByConditionV3(
            [
                [
                    'field' => 'user_id',
                    'value' => $this->loginUserinfo['id'],
                    'operate' => '=',
                ],
                [
                    'field' => 'created_at',
                    'value' => strtotime(date("Y-m-d H:i:s", strtotime("-".$config['cache']." hours"))),
                    'operate' => '>=',
                ]
            ],
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

        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>20,
            'total' => $res['total'],
            'totalPage' => ceil( $res['total']/ 20 ),
        ], $res['data'],'成功');
    }

    //获取待确认的列表
    public function getNeedsConfirmExportLists(){
        $requestData =  $this->getRequestData();
        $condition = [
            'user_id' => $this->loginUserinfo['id'],
            'needs_confirm' => 1,
        ];

        $page = $requestData['page']?:1;
        $res = AdminUserFinanceData::findByConditionV2(
            $condition,
            $page
        );
        foreach ($res['data'] as &$itme ){
            $itme['status_cname'] =AdminUserFinanceData::getStatusCname()[$itme['status']];
        }
        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>20,
                'total' => $res['total'],
                'totalPage' => ceil( $res['total']/ 20 ),
            ] , $res['data'], '成功' );
    }

    //账户流水
    public function getFinanceLogLists(){
        $requestData =  $this->getRequestData();
        $condition = [
            'userId' => $this->loginUserinfo['id'],
        ];

        $page = $requestData['page']?:1;
        $res = FinanceLog::findByConditionV2(
            $condition,
            $page
        );
        foreach ($res['data'] as  &$dataItem){
            $dataItem['type_cname'] = FinanceLog::getTypeCnameMaps()[$dataItem['type']];
        }
        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>20,
                'total' => $res['total'],
                'totalPage' => ceil( $res['total']/ 20 ),
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

        if(
            $requestData['type'] == \App\HttpController\Models\AdminV2\AdminNewUser::$chargeTypeAdd
        ){
            $newBalance = \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                    $requestData['user_id']
                ) + $requestData['money'];
            $title= '充值';
            $type = FinanceLog::$chargeTytpeAdd ;
        }

        if(
            $requestData['type'] == \App\HttpController\Models\AdminV2\AdminNewUser::$chargeTypeDele
        ){
            $newBalance = \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                    $requestData['user_id']
                ) - $requestData['money'];
            $title= '扣费';
            $type = FinanceLog::$chargeTytpeDele ;
        }
        if(
            ! \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
                $requestData['user_id'],
                $newBalance
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
        $realData = NewFinanceData::findByIdV2($realFinanceDatId,($allowedFields));
        return $this->writeJson(200, [ ] , $realData , '成功' );
    }
    //确认是否需要
    public function ConfirmFinanceData(){
        $requestData =  $this->getRequestData();
        $res = AdminUserFinanceData::updateStatus(
            $requestData['id'],
            $requestData['status']
        );
        if(!$res){
            return $this->writeJson(206, [] ,   [], '确认失败', true, []);
        }
        return $this->writeJson(200, [ ], $res, '成功');
    }

    //导出记录对应的详情
    public function exportDetails(){
        $requestData =  $this->getRequestData();
        $res = AdminUserFinanceExportDataRecord::findByUserAndExportId(
            $this->loginUserinfo['id'],
            $requestData['id']
        );
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

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$size,
            'total' => count($res),
            'totalPage' => ceil(count($res)/$size),
        ], $res, '');
    }

    //导出某次导出的详情记录
    public function exportExportDetails(){
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

    function  parseDataToXls($config,$filename,$header,$exportData,$sheetName){

        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, $sheetName);
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
            ->header($header)
            ->defaultFormat($alignStyle)
            ->data($exportData)
            // ->setColumn('B:B', 50)
        ;

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource();

        return $fileObject->output();
    }
    // 导出客户名单 异步 只是加入队列
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
                $uploadRes['id'],AdminUserFinanceUploadRecord::$stateConfirmed
            )
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [],  '当前状态不允许导出 请稍等');
        }
        $batchNum = 'CWDC'.date('YmdHis');
        //先扣费
        //本名单之前是否扣费过
        $chargeBefore = AdminUserFinanceUploadRecord::ifHasChargeBefore($uploadRes['id']);
        // ===============
        // 实际扣费了
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
                    \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                        $uploadRes['user_id']
                    ) - $uploadRes['money']
                )
            ){
                return $this->writeJson(201, null, [],  '扣余额失败，联系管理员');
            }
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
                return $this->writeJson(201, null, [],  '添加扣费记录失败，联系管理员');
            }

            //设置收费时间|本名单的
            $res = AdminUserFinanceUploadRecord::updateLastChargeDate($uploadRes['id'],date('Y-m-d H:i:s'));
            if(!$res  ){
                return $this->writeJson(201, null, [],  '设置收费时间失败，联系管理员');
            }

            $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV2(
                $uploadRes['user_id'],$uploadRes['id']
            );
            $finance_config = AdminUserFinanceUploadRecord::getFinanceConfigArray($uploadRes['id']);
            foreach($financeDatas['details'] as $financeData){
                $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::findById($financeData['UploadDataRecordId'])->toArray();

                // 收费了
                if(
                    $AdminUserFinanceUploadDataRecord['real_price'] &&
                    !$chargeBefore
                ){
                    //设置收费记录
                    $AdminUserFinanceChargeInfoId = AdminUserFinanceChargeInfo::addRecordV2(
                        [
                            'user_id' => $AdminUserFinanceUploadDataRecord['user_id'],
                            'batch' => $batchNum.'_'.$AdminUserFinanceUploadDataRecord['id'],
                            'entName' => $financeData['entName'],
                            'start_year' => $AdminUserFinanceUploadDataRecord['charge_year_start'],
                            'end_year' => $AdminUserFinanceUploadDataRecord['charge_year_end'],
                            'year' => $AdminUserFinanceUploadDataRecord['charge_year'],
                            'price' => $AdminUserFinanceUploadDataRecord['real_price'],
                            'price_type' => $AdminUserFinanceUploadDataRecord['price_type'],
                            'status' => AdminUserFinanceChargeInfo::$state_init,
                        ]
                    );
                    if(!$AdminUserFinanceChargeInfoId  ){
                        return $this->writeJson(201, null, [],  '设置收费时间失败，联系管理员');
                    }
                    //设置上次计费时间 |具体用户对应的企业数据
                    $res = AdminUserFinanceData::updateLastChargeDate(
                        $AdminUserFinanceUploadDataRecord['user_finance_data_id'],
                        date('Y-m-d H:i:s')
                    );
                    if(!$res  ){
                        return $this->writeJson(201, null, [],  '设置具体收费时间失败，联系管理员');
                    }

                    //设置缓存过期时间
                    $res = AdminUserFinanceData::updateCacheEndDate(
                        $AdminUserFinanceUploadDataRecord['user_finance_data_id'],
                        date('Y-m-d H:i:s'),
                        $finance_config['cache']
                    );
                    if(!$res  ){
                        return $this->writeJson(201, null, [],  '设置缓存过期时间失败，联系管理员');
                    }
                }
            }

        }
        // =================

        //添加到下载队列
        if(
            !AdminUserFinanceExportDataQueue::addRecordV2(
                [
                    'batch' => $batchNum,
                    'user_id' => $this->loginUserinfo['id'],
                    'upload_record_id' => $requestData['id'],
                    'real_charge' =>$price >0 ? 1 : 0,
                    'status' => AdminUserFinanceExportDataQueue::$state_init
                ]
            )
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return  $this->writeJson(201,[],[],'添加失败，联系管理员');
        }

        ConfigInfo::removeRedisNx('exportFinanceData2');
        return $this->writeJson(200,[],[],'已发起下载，请稍后去【内容记录】中查看');
    }
}