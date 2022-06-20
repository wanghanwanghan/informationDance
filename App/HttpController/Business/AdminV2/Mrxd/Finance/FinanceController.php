<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Finance;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\Provide\RequestApiInfo;
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
            !$requestData['single_year_charge_as_annual'] ||
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
            'price_config' => $requestData['price_config']?:'',
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
            'status' => 1,
        ];
        $res = AdminUserFinanceConfig::addRecord(
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
            'price_config' => $requestData['price_config'] ? $requestData['price_config']: $info['normal_years_price_json'],
            'cache' => $requestData['cache'] ? $requestData['cache']: $info['cache'],
            'type' => $requestData['type'] ? $requestData['type']: $info['type'],
            'single_year_charge_as_annual' => $requestData['single_year_charge_as_annual'] ? $requestData['single_year_charge_as_annual']: $info['single_year_charge_as_annual'],
            'allowed_total_years_num' => $requestData['allowed_total_years_num'] ? $requestData['allowed_total_years_num']: $info['allowed_total_years_num'],
            'needs_confirm' => $requestData['needs_confirm'] ?
                $requestData['needs_confirm']: $info['needs_confirm'],
            'allowed_fields' => $requestData['allowed_fields'] ? $requestData['allowed_fields']: $info['allowed_fields'],
        ];
        $res = $info->update($data);
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

    // 上传客户名单
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
                        'uploadeCompanyLists file  already exists. '.$path
                    );
                    continue;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    CommonService::getInstance()->log4PHP(
                        'uploadeCompanyLists file  not  exists. '.$path
                    );
                    continue;
                }

                $UploadRecordRes =  AdminUserFinanceUploadRecord::findByIdAndFileName(
                    $this->loginUserinfo['id'],   
                    $fileName
                );
                if($UploadRecordRes){
                    CommonService::getInstance()->log4PHP(
                        json_encode(
                            [
                                'uploadeCompanyLists  AdminUserFinanceUploadRecord    exists',
                                '$path' => $path,
                            ]
                        )
                    );
                    continue;
                } 
                $tmpData = [
                    'user_id' => $this->loginUserinfo['id'],
                    'file_path' => $path,
                    'years' => $requestData['years'],
                    'file_name' => $fileName,
                    'title' => $requestData['title']?:'',
                    'reamrk' => $requestData['reamrk']?:'',
                    'finance_config' => AdminUserFinanceConfig::getConfigDataByUserId(
                        $this->loginUserinfo['id']
                    ),
                    'status' => AdminUserFinanceUploadRecord::$stateInit,
                ];
                $addUploadRecordRes = AdminUserFinanceUploadRecord::addUploadRecord(
                    $tmpData
                 );
                if(!$addUploadRecordRes){
                    continue;
                }

                $succeedNums ++;
            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'uploadeCompanyLists failed  ',
                        $e->getMessage(),
                    ])
                );  
            } 
        }

        return $this->writeJson(200, [], [],'导入成功 入库文件数量:'.$succeedNums);
    }

    public function getUploadLists(){
        // $userId = $this->getRequestData('user_id');
        // if($userId <= 0){
        //     return $this->writeJson(206, [] ,   [], '缺少必要参数', true, []); 
        // } 

        $requestData =  $this->getRequestData();
         
        $res = AdminUserFinanceUploadRecord::findByCondition(
            [
                // 'user_id' => $userId
                'user_id' => $this->loginUserinfo['id']
            ],
            0, 20
        );
        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>$size,
            'total' => 1,
            'totalPage' => 1,
        ],  $res,'成功');
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
        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis').'xlsx';
        $exportDataToXlsRes = NewFinanceData::parseDataToXls(
            $config,$filename,[],$res,'sheet1'
        );

        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>$size,
            'total' => 1,
            'totalPage' => 1,
        ],  [
            'path' => TEMP_FILE_PATH,
            'filename' => $filename
        ],'成功');

    }


    public function getExportQueueLists(){
        $requestData =  $this->getRequestData();

        $res = AdminUserFinanceExportDataQueue::findByCondition(
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



    //获取待确认的列表
    public function getNeedsConfirmExportLists(){
        // $userId = $this->getRequestData('user_id');
        // if($userId <= 0){
        //     return $this->writeJson(206, [] ,   [], '缺少必要参数', true, []); 
        // } 

        $requestData =  $this->getRequestData();
        $condition = [
            // 'user_id' => $userId
            'user_id' => $this->loginUserinfo['id']
        ];
        if($requestData['status']){
            $condition['status'] = $requestData['status'];
        }
//        if($requestData['began_time']){
//            $condition['status'] = $requestData['status'];
//        }
        $res = AdminUserFinanceData::findByCondition(
            $condition,
            0, 20
        );

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>$size,
                'total' => 1,
                'totalPage' => 1,
            ] , $res, '成功' );
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
            'total' => 1,
            'totalPage' => 1,
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

        $requestData =  $this->getRequestData();

        $res = AdminUserFinanceExportRecord::findByCondition(
            [
                // 'user_id' => $userId
                'user_id' => $this->loginUserinfo['id']
            ],
            0, 20
        );
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
            ConfigInfo::setRedisNx('exportFinanceData')
        ){
            return $this->writeJson(201, null, [

            ], [], '请勿重复提交');
        }

        $requestData =  $this->getRequestData();
        if(
            $requestData['id'] <= 0 
        ){
            return $this->writeJson(201, null, [

            ], [], '参数缺失');
        }
        $uploadRes = AdminUserFinanceUploadRecord::findById($requestData['id'])->toArray();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceUploadRecord findById ' ,
                $requestData['id'],$uploadRes
            ])
        );
        // 检查余额
        if(
            !\App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                $this->loginUserinfo['id'],
                $uploadRes['money']
            )
        ){
            return $this->writeJson(201, null, [], [], '余额不足 需要至少'. $uploadRes['money'].'元');
        }


        CommonService::getInstance()->log4PHP(
            json_encode([
                'exportFinanceData AdminUserFinanceExportDataQueue  addRecordV2' ,
                'batch' => date('YmdHis'),
                'upload_record_id' => $requestData['id']
            ])
        );
        if(
            AdminUserFinanceExportDataQueue::addRecordV2(
                [
                    'batch' => date('YmdHis'),
                    'user_id' => $this->loginUserinfo['id'],
                    'upload_record_id' => $requestData['id']
                ]
            )
        ){
            return  $this->writeJson(200);
        }



        //添加到导出队列
//        AdminUserFinanceExportDataQueue::addRecord(
//            [
//                'batch' => date('YmdHis'),
//                'upload_record_id' => $requestData['id']
//            ]
//        );
        return $this->writeJson(200);

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