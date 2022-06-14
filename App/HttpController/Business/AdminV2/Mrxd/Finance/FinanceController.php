<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Finance;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadeRecord;
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

    /**
     *  增加菜单
     */
    public function addConfig(){
        $requestData = $this->getRequestData(); 
        if (
            !$requestData['user_id'] ||
            //包的年限
            !$requestData['annually_years'] || 
            !$requestData['annually_price']  ||
            !$requestData['normal_years_price_json'] ||
            !$requestData['allowed_fields'] ||
            !$requestData['type'] ||
            !$requestData['cache']   
        ) {
            return $this->writeJson(201);
        }
        
        if(
            AdminUserFinanceConfig::getConfigByUserId(
                $requestData['user_id']
            )
        ){
            return $this->writeJson(203,[],[],'一个用户只能有一个有效配置');
        }
        AdminUserFinanceConfig::addRecord(
            [
                'user_id' => $requestData['user_id'], 
                'annually_price' => $requestData['annually_price'],  
                'annually_years' => $requestData['annually_years'],  
                'normal_years_price_json' => $requestData['normal_years_price_json'],  
                'cache' => $requestData['cache'],  
                'type' => $requestData['type'],  
                'allowed_fields' => $requestData['allowed_fields'],  
                'status' => 1,  
            ]
        );
        return $this->writeJson(200);
    }

     /**
     *  修改菜单
     */
    public function updateConfig(){
        $requestData = $this->getRequestData(); 
        $info = AdminUserFinanceConfig::create()->where('id',$requestData['id'])->get(); 
        $info->update([
            'id' => $requestData['id'],
            'annually_price' => $requestData['annually_price'] ?   $requestData['annually_price']: $info['annually_price'],
            'annually_years' => $requestData['annually_years'] ? $requestData['annually_years']: $info['annually_years'],
            'normal_years_price_json' => $requestData['normal_years_price_json'] ? $requestData['normal_years_price_json']: $info['normal_years_price_json'],
            'cache' => $requestData['cache'] ? $requestData['cache']: $info['cache'],
            'type' => $requestData['type'] ? $requestData['type']: $info['type'],
            'allowed_fields' => $requestData['allowed_fields'] ? $requestData['allowed_fields']: $info['allowed_fields'],
        ]);
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
            return $this->writeJson(206, [] ,   [], '缺少必要参数('.$years.')', true, []); 
        }

        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();
        CommonService::getInstance()->log4PHP(
            'uploadeCompanyLists files'.json_encode($files).''

        );
        CommonService::getInstance()->log4PHP(
            'uploadeCompanyLists files2'.json_encode($_FILES).''

        );
        $path = $fileName = '';

        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            CommonService::getInstance()->log4PHP(
                'file  . '.json_encode($oneFile)
            );
            if (!$oneFile instanceof UploadFile) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'not instanceof UploadFile '.$oneFile->getClientFilename(),
                    ])
                ); 
//                    continue;
            }

            try {
                $fileName = $oneFile->getClientFilename();
                $path = TEMP_FILE_PATH . $fileName;
                if(file(file_exists($path))){
                    CommonService::getInstance()->log4PHP(
                        'file  already exists. '.$path
                    );  
                    continue;
                }

                $res = $oneFile->moveTo($path);  
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        'move file   failed . '.$path
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
                        'finance_config' => AdminUserFinanceConfig::getConfigByUserId(
                            $this->loginUserinfo['id']
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
                        'uploadeCompanyLists getMessage ',
                        $e->getMessage(),
                    ])
                );  
            } 
        }

        return $this->writeJson(200, [], [
            $this->request()->getUploadedFiles(),
            $_FILES
        ],'导入成功 入库数量:'.$succeedNums);
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
        // $userId = $this->getRequestData('user_id');
        // if($userId <= 0){
        //     return $this->writeJson(206, [] ,   [], '缺少必要参数', true, []); 
        // } 

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
        // $userId = $this->getRequestData('user_id');
        // if($userId <= 0){
        //     return $this->writeJson(206, [] ,   [], '缺少必要参数', true, []); 
        // } 

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
        $requestData =  $this->getRequestData();
        if(
            $requestData['id'] <= 0 
        ){
            return $this->writeJson(201, null, [

            ], [], '参数缺失');
        }

        // 找到对应的财务信息
        $financeData = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordId(
            $this->loginUserinfo['id'],
            $requestData['id'],
            1
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                '找到对应的财务信息' ,
                'totalNums' => $financeData['totalNums'],
                'totalPrice' => $financeData['totalPrice'],
            ])
        );
        return $this->writeJson(200);

        // 检查余额
        if(
            !\App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                $this->loginUserinfo['id'],
                $financeData['totalPrice']
            )
        ){
            return $this->writeJson(201, null, [], [], '余额不足 需要至少'.$financeData['totalPrice'].'元');
        }

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = '客户名单_'.$requestData['id'].'_'.date('YmdHis'). '.xlsx';
        $header = [
            '序号',
            '企业名称',
            '监控类别',
        ];
        $exportDataToXlsRes = $this->parseDataToXls(
            $config,$filename,$header,$financeData['finance_data'],'财务数据'
        );
        if(!$exportDataToXlsRes){
            return $this->writeJson(203, null, [], [], '下载失败');
        }

        // 设置导出记录
        $AdminUserFinanceExportRecordId = AdminUserFinanceExportRecord::addExportRecord(
            [
                'user_id' => $this->loginUserinfo['id'], 
                'price' => $financeData['totalPrice'],  
                'total_company_nums' => $financeData['totalNums'],  
                'config_json' => '',  
                'upload_record_id' => $requestData['id'],  
                'reamrk' => '',  
                'status' => '',   
            ]
        );
        // 设置导出详情
        foreach($financeData['finance_data'] as $financeItem){
            AdminUserFinanceExportDataRecord::addExportRecord(
                [
                    'user_id' => $requestData['user_id'], 
                    'export_record_id' => $AdminUserFinanceExportRecordId,   
                    'user_finance_data_id' => $financeItem['user_finance_data_id'],   
                    'price' => $financeItem['price'],   
                    'detail' => $financeItem['price_detail'],   
                    'status' => 1,
                ]
                );
        }

        // 实际扣费 
        \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
            $this->loginUserinfo['id'],
            (
                \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
                    $this->loginUserinfo['id']
                ) - $financeData['totalPrice']
            )
        );


        foreach($financeData['chargeDetails'] as $financeItem){
            if($financeItem['price'] <=0 ){
                continue;
            }

            //设置上次计费时间
            AdminUserFinanceData::updateLastChargeDate(
                $financeItem['user_finance_data_id'],
                date('Y-m-d H:i:s')
            );
            //设置缓存过期时间
            AdminUserFinanceData::updateCacheEndDate(
                $financeItem['user_finance_data_id'],
                date('Y-m-d H:i:s'),
                $financeData['config_arr']['cache']
            );
        }

        //添加计费日志
        FinanceLog::addRecord(
            [
                'detailId' => $AdminUserFinanceExportRecordId,
                'detail_table' => 'admin_user_finance_export_record',
                'price' => $financeData['totalPrice'],
                'userId' => $this->loginUserinfo['id'],
                'type' =>  FinanceLog::$chargeTytpeFinance,
                'title' => '导出财务数据',
                'detail' => '导出财务数据',
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,
            ]
        );

        return $this->writeJson(200, null, 'Static/Temp/' . $filename, null, true, [$res]);
    }


}