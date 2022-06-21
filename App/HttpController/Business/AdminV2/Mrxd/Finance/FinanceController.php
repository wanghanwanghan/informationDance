<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Finance;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
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
            !$requestData['allowed_fields'] ||
            !$requestData['type'] ||
            //!$requestData['single_year_charge_as_annual'] ||
            !$requestData['allowed_total_years_num']
        ) {
            return $this->writeJson(201);
        }
        
        if(
            AdminUserFinanceConfig::getConfigDataByUserId(
                $requestData['user_id']
            )
        ){
            return $this->writeJson(203,[],[],'一个用户只能有一个有效配置');
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
            'status' => 1,
        ];
        $res = AdminUserFinanceConfig::addRecordV2(
            $dataItem
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceConfig addRecord',
                $dataItem,
                $res
            ])
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
            'max_daily_nums' => $requestData['max_daily_nums'] ? $requestData['allowed_fields']: $info['max_daily_nums'],
        ];
        AdminUserFinanceConfig::setStatus(
            $requestData['id'],AdminUserFinanceConfig::$state_del
        );

        $res = AdminUserFinanceConfig::addRecordV2($data);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceConfig updateConfig',
                $data,
                $res
            ])
        );
        if (!$res){
            return $this->writeJson(205);
        }

        return $this->writeJson();
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
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'uploadeCompanyLists   file_exists continue',
                            'params $path '=> $path,
                        ])
                    );
                    continue;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'uploadeCompanyLists   file_not_exists moveTo false ',
                            'params $path '=> $path,
                        ])
                    );
                    continue;
                }

                $UploadRecordRes =  AdminUserFinanceUploadRecord::findByIdAndFileName(
                    $this->loginUserinfo['id'],   
                    $fileName
                );
                if($UploadRecordRes){
                    continue;
                }

                $addUploadRecordRes = AdminUserFinanceUploadRecord::addUploadRecord(
                    [
                        'user_id' => $this->loginUserinfo['id'],
                        'file_path' => $path,
                        'years' => $requestData['years'],
                        'file_name' => $fileName,
                        'title' => $requestData['title']?:'',
                        'reamrk' => $requestData['reamrk']?:'',
                        'finance_config' => json_encode(
                            AdminUserFinanceConfig::getConfigDataByUserId(
                                $this->loginUserinfo['id']
                            )
                        ),
                        'status' => AdminUserFinanceUploadRecord::$stateInit,
                    ]
                 );

                if(!$addUploadRecordRes){
                    continue;
                }

                $succeedNums ++;
            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'uploadeCompanyLists false',
                        ' getMessage '=> $e->getMessage()
                    ])
                );
            } 
        }

        return $this->writeJson(200, [], [],'导入成功 入库文件数量:'.$succeedNums);
    }

    public function getUploadLists(){

        $requestData =  $this->getRequestData();
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

    public function getExportLists(){
        $requestData =  $this->getRequestData();

        $res = AdminUserFinanceExportRecord::findByCondition(
            [
                // 'user_id' => $userId
                'user_id' => $this->loginUserinfo['id']
            ],
            0, 20
        );
        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;

        foreach ($res as &$value){
            $value['upload_details'] = [];
            if(
                $value['upload_record_id']
            ){
                $value['upload_details'] = AdminUserFinanceUploadRecord::findById($value['upload_record_id'])->toArray();
            }
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>$size,
            'total' => 1,
            'totalPage' => 1,
        ], $res,'成功');
    }

    public function exportExportLists(){
        $requestData =  $this->getRequestData();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceExportRecord  exportExportLists    ',
                '$requestData' => $requestData,
            ])
        );
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
            $where[] = [
                'field'=>'id',
                'value'=> json_decode($requestData['ids'],true),
                'operate' => 'IN',
            ];
        }
        $res = AdminUserFinanceExportRecord::findByConditionV4(
            $where
        );

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis').'xlsx';
        NewFinanceData::parseDataToXls(
            $config,$filename,[],$res,'sheet1'
        );

        return $this->writeJson(200,  [],  [
            'path' => TEMP_FILE_PATH,
            'filename' => $filename
        ],'成功');

    }

    //我的下载
    public function getExportQueueLists(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $res = AdminUserFinanceExportDataQueue::findByConditionV2(
            [
                // 'user_id' => $userId
                'user_id' => $this->loginUserinfo['id']
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
            $value['status_cname'] = AdminUserFinanceExportDataQueue::getStatusMap()[$value['status']];
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>20,
            'total' => $res['total'],
           // 'totalPage' => 1,
        ], $res['data'],'成功');
    }

    //获取待确认的列表
    public function getNeedsConfirmExportLists(){
        $requestData =  $this->getRequestData();
        $condition = [
            // 'user_id' => $userId
            'user_id' => $this->loginUserinfo['id'],
            'needs_confirm' => 1,
            //'status' => AdminUserFinanceData::$statusNeedsConfirm
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
                //'totalPage' => 1,
            ] , $res['data'], '成功' );
    }

    public function getFinanceLogLists(){
        $requestData =  $this->getRequestData();
        $condition = [
            // 'user_id' => $userId
            'user_id' => $this->loginUserinfo['id'],
            //'status' => FinanceLog::$statusNeedsConfirm
        ];

        $page = $requestData['page']?:1;
        $res = FinanceLog::findByConditionV2(
            $condition,
            $page
        );

        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>20,
                'total' => $res['total'],
                //'totalPage' => 1,
            ] , $res['data'], '成功' );
    }


    public function chargeAccount(){
        return $this->writeJson(200,
            [

            ] , [], '成功' );
        $requestData =  $this->getRequestData();
        $res = \App\HttpController\Models\AdminV2\AdminNewUser::charge(
            $requestData['user_id'],
            $requestData['money'],
            date('YmdHis'),
            [
                'detailId' => 0,
                'detail_table' => 'admin_user_finance_export_data_queue',
                'price' => $requestData['money'],
                'userId' => $requestData['user_id'],
                'type' => FinanceLog::$chargeTytpeFinance,
                'batch' => $queueData['id'],
                'title' => '',
                'detail' => '',
                'reamrk' => '',
                'status' => 1,
            ],
            $requestData['money']
        );
        if(!$res){

        }



        $condition = [
            // 'user_id' => $userId
            'user_id' => $this->loginUserinfo['id'],
            //'status' => FinanceLog::$statusNeedsConfirm
        ];


        $page = $requestData['page']?:1;
        $res = FinanceLog::findByConditionV2(
            $condition,
            $page
        );

        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>20,
                'total' => $res['total'],
                //'totalPage' => 1,
            ] , $res['data'], '成功' );
    }

    public function getNeedsConfirmDetails(){
        $requestData =  $this->getRequestData();
        if($requestData['id'] <= 0){
            return $this->writeJson(203,
                [
                ] , [], '参数缺失' );
        }
        $res = AdminUserFinanceData::findById($requestData['id']);
        $data = $res->toArray();
        $realFinanceDatId = $data['finance_data_id'];
        $allowedFields = NewFinanceData::getFieldCname(false);
        $realData = NewFinanceData::findByIdV2($realFinanceDatId,($allowedFields));
        return $this->writeJson(200,
            [

            ] , $realData , '成功' );
    }
    //确认的列表
    public function ConfirmFinanceData(){

        $requestData =  $this->getRequestData();
         
        $res = AdminUserFinanceData::updateStatus(
            $requestData['id'],
            $requestData['status']
        );
        if(!$res){
            return $this->writeJson(206, [] ,   [], '更新失败', true, []);
        }
        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$size,
            'total' => 1,
            'totalPage' => 1,
        ], $res, '');
    }

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

    public function exportExportDetails(){
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

//        $requestData =  $this->getRequestData();

//        $res = AdminUserFinanceExportRecord::findByCondition(
//            [
//                // 'user_id' => $userId
//                'user_id' => $this->loginUserinfo['id']
//            ],
//            0, 20
//        );
        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis').'xlsx';
        $exportDataToXlsRes = NewFinanceData::parseDataToXls(
            $config,$filename,[],$res,'sheet1'
        );

        return $this->writeJson(200,  [

        ],  [
            'path' => TEMP_FILE_PATH,
            'filename' => $filename
        ],'成功');
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
    // 导出客户名单
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
            return $this->writeJson(201, null, [

            ],'参数缺失');
        }

        $uploadRes = AdminUserFinanceUploadRecord::findById($requestData['id'])->toArray();

        //检查是否可以下载
        if(
           !AdminUserFinanceUploadRecord::ifCanDownload($uploadRes['id'])
        ){
            return $this->writeJson(201, null, [],'时间过长，请重新上传');
        }

        //检查状态
        if(
            !AdminUserFinanceUploadRecord::checkByStatus(
                $requestData['id'],AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            )
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [],  '当前状态不允许导出 请稍等');

        }
        // 检查余额
        if(
            !\App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                $this->loginUserinfo['id'],
                $uploadRes['money']
            )
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return $this->writeJson(201, null, [],  '余额不足 需要至少'. $uploadRes['money'].'元');
        }

        //检查账户是否可以拉取 如果到达今日次数等限制的话 就跳过去      把优先级调的低一些 updatePriorityById
        $totalNeedExportNums = count(
            AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
                $uploadRes['user_id'],
                $uploadRes['id'],
                ['id']
            )
        );

        //每日最大次数限制
        if(
            !AdminUserChargeConfig::checkIfCanRun($uploadRes['user_id'],$totalNeedExportNums)
        ){
            return  $this->writeJson(201,[],'超出每日最大次数，联系管理员');
        }

        if(
            !AdminUserFinanceExportDataQueue::addRecordV2(
                [
                    'batch' => date('YmdHis'),
                    'user_id' => $this->loginUserinfo['id'],
                    'upload_record_id' => $requestData['id']
                ]
            )
        ){
            ConfigInfo::removeRedisNx('exportFinanceData2');
            return  $this->writeJson(201,[],'添加失败，联系管理员');
        }

        ConfigInfo::removeRedisNx('exportFinanceData2');
        return $this->writeJson(200,[],'已发起下载，请稍后去我的下载中查看');

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = '客户名单_'.$requestData['id'].'_'.date('YmdHis'). '.xlsx';
        $header = [
            '序号',
            '企业名称',
            '年度',
            '资产总额',
            '负债总额',
            '营业总收入',
            '主营业务收入',
            '利润总额',
            '净利润',
            '纳税总额',
            '所有者权益',
            '社保人数'
        ];
        $exportDataToXlsRes = $this->parseDataToXls(
            $config,$filename,$header,$financeData['finance_data'],'财务数据'
        );
        if(!$exportDataToXlsRes){
            return $this->writeJson(203, null, [], [], '下载失败');
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                '生成文件' ,
                $config,$filename
            ])
        );

        // 实际扣费 
        $res = \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
            $this->loginUserinfo['id'],
            (
                \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                    $this->loginUserinfo['id']
                ) - $financeData['total_charge']
            )
        );
        if(
            !$res
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '实际扣费 失败' ,
                ])
            );
        }

        AdminUserFinanceExportDataQueue::addRecord(
            [
                'batch' => date('YmdHis'),
                'upload_record_id' => $requestData['id']
            ]
        );
        ConfigInfo::removeRedisNx('exportFinanceData');
        return $this->writeJson(200, null, 'Static/Temp/' . $filename, null, true, [$res]);
    }


}