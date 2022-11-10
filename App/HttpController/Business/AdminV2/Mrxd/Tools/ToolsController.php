<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Tools;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\CarInsuranceInstallment;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\QueueLists;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\BusinessBase\CompanyClue;
use App\HttpController\Models\MRXD\ToolsFileLists;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use Vtiful\Kernel\Format;

class ToolsController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }


    // 用户-上传模板
    public function uploadeTemplateLists(){

        return $this->writeJson(200, [], [
            [
                'name' => '根据企业名补全联系人模板[检测手机号]',
                'path' => '/Static/Template/根据企业名补全联系人模板[检测手机号].xlsx',
            ],
            [
                'name' => '模糊匹配企业名称模板',
                'path' => '/Static/Template/模糊匹配企业名称模板.xlsx',
            ],
            [
                'name' => '补全联系人姓名职位等信息[主要基于微信名和联系人库]',
                'path' => '/Static/Template/补全联系人姓名职位等信息[主要基于微信名和联系人库].xlsx',
            ],
            [
                'name' => '将表格根据手机号拆分成多行',
                'path' => '/Static/Template/将表格根据手机号拆分成多行.xlsx',
            ],
        ],'');
    }

    // 用户-上传类型
    public function uploadeTypeLists(){

        return $this->writeJson(200, [], [
                5   =>  '补全企业联系人信息(并检测手机状态)',
                10  =>  '补全联系人姓名职位等信息(主要基于微信名和联系人库)',
                15  =>  '模糊匹配企业名称',
                20  =>  '将表格根据手机号拆分成多行',
                25  =>  '补全企业字段',

        ],'');
    }

    public function buQuanZiDuanList(){
        $requestData =  $this->getRequestData();
        $page =$requestData['page']?:1;
        $pageSize =$requestData['pageSize']?:20;

        $res = ToolsFileLists::findByConditionWithCountInfo(
            [
                'type' =>ToolsFileLists::$type_bu_quan_zi_duan,
            ],$page
        );
        foreach ($res['data'] as &$dataItem ){
            $adminInfo = \App\HttpController\Models\AdminV2\AdminNewUser::findById($dataItem['admin_id']);
            $dataItem['admin_id_cname'] = $adminInfo->user_name;
            $dataItem['new_file_path'] = '/Static/OtherFile/'.$dataItem['new_file_name'];
            $dataItem['state_cname'] = ToolsFileLists::stateMaps()[$dataItem['state']];
        }
        $total = $res['total'];
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],  $res['data'],'');
    }

    public function pullGongKaiContact(){
        $requestData =  $this->getRequestData();
        $page =$requestData['page']?:1;
        $pageSize =$requestData['pageSize']?:20;

        $res = ToolsFileLists::findByConditionWithCountInfo(
            [
                'type' =>ToolsFileLists::$type_upload_pull_gong_kai_contact,
            ],$page
        );
        foreach ($res['data'] as &$dataItem ){
            $adminInfo = \App\HttpController\Models\AdminV2\AdminNewUser::findById($dataItem['admin_id']);
            $dataItem['admin_id_cname'] = $adminInfo->user_name;
            $dataItem['new_file_path'] = '/Static/OtherFile/'.$dataItem['new_file_name'];
            $dataItem['state_cname'] = ToolsFileLists::stateMaps()[$dataItem['state']];
        }
        $total = $res['total'];
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],  $res['data'],'');
    }

    public function pullFeiGongKaiContact(){
        $requestData =  $this->getRequestData();
        $page =$requestData['page']?:1;
        $pageSize =$requestData['pageSize']?:20;

        $res = ToolsFileLists::findByConditionWithCountInfo(
            [
                'type' =>ToolsFileLists::$type_upload_pull_fei_gong_kai_contact,
            ],$page
        );
        foreach ($res['data'] as &$dataItem ){
            $adminInfo = \App\HttpController\Models\AdminV2\AdminNewUser::findById($dataItem['admin_id']);
            $dataItem['admin_id_cname'] = $adminInfo->user_name;
            $dataItem['new_file_path'] = '/Static/OtherFile/'.$dataItem['new_file_name'];
            $dataItem['state_cname'] = ToolsFileLists::stateMaps()[$dataItem['state']];
        }
        $total = $res['total'];
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],  $res['data'],'');
    }


    public function rePullFeiGongKaiContact(){
        $requestData =  $this->getRequestData();
        $page =$requestData['page']?:1;
        $pageSize =$requestData['pageSize']?:20;

        $dataRes = ToolsFileLists::findById($requestData['id']);
        ToolsFileLists::updateById(
            $dataRes->id,
            [
                'touch_time'=>'',
                'new_file_name'=>'',
                'state'=>ToolsFileLists::$state_init,
            ]
        );
        $config_arr = @json_decode($dataRes->remark,true);

        $res = QueueLists::addRecord(
            [
                'name' => '拉取非公开联系人',
                'desc' => '',
                'func_info_json' => json_encode(
                    [
                        'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                        'static_func'=> 'pullFeiGongKaiContacts',
                    ]
                ),
                'params_json' => json_encode([
                    'fill_position_by_name' => intval($config_arr['fill_position_by_name']),
                    'fill_weixin_by_phone' => intval($config_arr['fill_weixin_by_phone']),
                    'fill_name_and_position_by_weixin' => intval($config_arr['fill_name_and_position_by_weixin']),
                    'filter_qcc_phone' => intval($config_arr['filter_qcc_phone']),
                ]),
                'type' => ToolsFileLists::$type_upload_pull_fei_gong_kai_contact,
                'remark' => '',
                'begin_date' => NULL,
                'msg' => '',
                'status' => QueueLists::$status_init,
            ]
        );

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],  $res['data'],'');
    }

    public function rePullGongKaiContact(){
        $requestData =  $this->getRequestData();
        $page =$requestData['page']?:1;
        $pageSize =$requestData['pageSize']?:20;

        $dataRes = ToolsFileLists::findById($requestData['id']);
        $config_arr = @json_decode($dataRes->remark,true);
        ToolsFileLists::updateById(
            $dataRes->id,
            [
                'touch_time'=>'',
                'new_file_name'=>'',
                'state'=>ToolsFileLists::$state_init,
            ]
        );

        $res = QueueLists::addRecord(
            [
                'name' => '拉取公开联系人',
                'desc' => '',
                'func_info_json' => json_encode(
                    [
                        'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                        'static_func'=> 'pullGongKaiContacts',
                    ]
                ),
                'params_json' => json_encode([
                    'fill_position_by_name' => intval($config_arr['fill_position_by_name']),
                    'fill_weixin_by_phone' => intval($config_arr['fill_weixin_by_phone']),
                    'fill_name_and_position_by_weixin' => intval($config_arr['fill_name_and_position_by_weixin']),
                ]),
                'type' => ToolsFileLists::$type_upload_pull_gong_kai_contact,
                'remark' => '',
                'begin_date' => NULL,
                'msg' => '',
                'status' => QueueLists::$status_init,
            ]
        );


        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],  $res['data'],'');
    }

    public function uploadeBuQuanZiDuanFiles(){
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
                        'type' => ToolsFileLists::$type_bu_quan_zi_duan,
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
                                    'static_func'=> 'buQuanZiDuan',
                                ]
                            ),
                            'params_json' => json_encode([

                            ]),
                            'type' => QueueLists::$typle_finance,
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

    /**
        上传公开联系人文件
    http://api.test.meirixindong.com/admin/v2/tools/uploadeGongKaiContactFiles
    phone:
    18618457910
    get_zhiwei:true
    get_wxname:true
    get_namezhiwei:true
    file:
    (二进制)
     */

    public function uploadeGongKaiContactFiles(){
        $requestData =  $this->getRequestData();
        $succeedFiels = [];
        $files = $this->request()->getUploadedFiles();
        //return $this->writeJson(200, [], [],'成功 入库文件:'.join(',',$succeedFiels));
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
                        'remark' => json_encode([
                            'fill_position_by_name' => intval($requestData['get_zhiwei']),
                            'fill_weixin_by_phone' => intval($requestData['get_wxname']),
                            'fill_name_and_position_by_weixin' => intval($requestData['get_namezhiwei']),
                        ]),
                        'type' => ToolsFileLists::$type_upload_pull_gong_kai_contact,
                        'state' => $requestData['state']?:'',
                        'touch_time' => $requestData['touch_time']?:'',
                    ]
                );
                if(!$UploadRecordRes){
                    return $this->writeJson(203, [], [],'文件上传失败');
                }

                    $res = QueueLists::addRecord(
                        [
                            'name' => '拉取公开联系人',
                            'desc' => '',
                            'func_info_json' => json_encode(
                                [
                                    'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                                    'static_func'=> 'pullGongKaiContacts',
                                ]
                            ),
                            'params_json' => json_encode([
                                'fill_position_by_name' => intval($requestData['get_zhiwei']),
                                'fill_weixin_by_phone' => intval($requestData['get_wxname']),
                                'fill_name_and_position_by_weixin' => intval($requestData['get_namezhiwei']),
                            ]),
                            'type' => ToolsFileLists::$type_upload_pull_gong_kai_contact,
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
    public function uploadeFeiGongKaiContactFiles(){
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
                        'remark' => json_encode([
                            'fill_position_by_name' => intval($requestData['get_zhiwei']),
                            'fill_weixin_by_phone' => intval($requestData['get_wxname']),
                            'fill_name_and_position_by_weixin' => intval($requestData['get_namezhiwei']),
                            'filter_qcc_phone' => intval($requestData['get_filterQccPhone']),
                        ]),
                        'type' => ToolsFileLists::$type_upload_pull_fei_gong_kai_contact,
                        'state' => $requestData['state']?:'',
                        'touch_time' => $requestData['touch_time']?:'',
                    ]
                );
                if(!$UploadRecordRes){
                    return $this->writeJson(203, [], [],'文件上传失败');
                }

                    $res = QueueLists::addRecord(
                        [
                            'name' => '拉取非公开联系人',
                            'desc' => '',
                            'func_info_json' => json_encode(
                                [
                                    'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                                    'static_func'=> 'pullFeiGongKaiContacts',
                                ]
                            ),
                            'params_json' => json_encode([
                                'fill_position_by_name' => intval($requestData['get_zhiwei']),
                                'fill_weixin_by_phone' => intval($requestData['get_wxname']),
                                'fill_name_and_position_by_weixin' => intval($requestData['get_namezhiwei']),
                            ]),
                            'type' => ToolsFileLists::$type_upload_pull_fei_gong_kai_contact,
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


    public function commonToos(){
        $requestData =  $this->getRequestData();
        $key = trim($requestData['key']);
        $arr = explode('&&&',$key);
        //通过企业名称查询我们库里的企业管理人(company_manager)
        if($requestData['type'] == 5 ){
            $response = LongXinService::getLianXiByNameV2($key);
        }

        //通过信用代码查询非公开联系人
        if($requestData['type'] == 10 ){
            $response = CompanyClue::getAllContactByCode($key);
            $response = [
                '非公开联系人来源1（pub）'=>$response['pub'],
                '非公开联系人来源2（pri）'=>$response['pri'],
                '非公开联系人来源3（qcc）'=>$response['qcc'],
            ];
        }
        //通过手机号检测号码状态（多个手机号英文逗号分隔）
        if($requestData['type'] == 15 ){
            $response = (new ChuangLanService())->getCheckPhoneStatus([
                'mobiles' => $key,
            ]);
        }

        // 根据微信名匹配企业对应的联系人（入参格式:企业名&&&微信名）
        if($requestData['type'] == 20 ){

            $response = (new XinDongService())->matchContactNameByWeiXinNameV3($arr[0], $arr[1]);
        }

        // 根据微信名匹配中文姓名（入参格式:中文姓名&&&微信名）
        if($requestData['type'] == 25 ){

            $response = (new XinDongService())->matchNamesV2($arr[0], $arr[1]);
        }

        //根据信用代码取最近两年进项发票（入参格式:信用代码）
        if($requestData['type'] == 30 ){
            $response  = [];
            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $key,
                $last2YearStart,
                $lastMonth,
                1,
                true
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                $response[] = $InvoiceData;
            }
        }

        /***/
        //根据信用代码导出最近两年进项发票（入参格式:信用代码）
        if($requestData['type'] == 35 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_最近两年进项发票.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));
            $allFields = [
                "发票代码",
                "发票号码",
                "开票日期",
                "总金额",
                "总税额",
                "发票类型",
                "发票状态",
                "卖方税号",
                "卖方名称",
                "买方税号",
                "买方名称",
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $key,
                $last2YearStart,
                $lastMonth,
                1,
                true
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        //根据信用代码取最近两年销项发票（入参格式:信用代码）
        if($requestData['type'] == 40 ){
            $response  = [];
            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $key,
                $last2YearStart,
                $lastMonth,
                2,
                true
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                $response[] = $InvoiceData;
            }
        }

        //根据信用代码导出最近两年销项发票（入参格式:信用代码）
        if($requestData['type'] == 45 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_最近两年销项发票.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));
            $allFields = [
                "发票代码",
                "发票号码",
                "开票日期",
                "总金额",
                "总税额",
                "发票类型",
                "发票状态",
                "卖方税号",
                "卖方名称",
                "买方税号",
                "买方名称",
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
                $key,
                $last2YearStart,
                $lastMonth,
                2,
                true
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        //根据信用代码查询最近两年进项发票明细（入参格式:信用代码）
        if($requestData['type'] == 50 ){
            $response  = [];
            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceGoodsData(
                $key,
                $last2YearStart,
                $lastMonth,
                1,
                true
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                $response[] = $InvoiceData;
            }
        }

        //根据信用代码导出最近两年进项发票明细（入参格式:信用代码）
        if($requestData['type'] == 55 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_最近两年进项发票明细.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
                "发票代码",
                "发票号码",
                "开票日期",
                "开票金额",
                "税额",
                "规格型号",
                "商品名",
                "税率",
                "单价",
                "数量",
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceGoodsData(
                $key,
                $last2YearStart,
                $lastMonth,
                1,
                true
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        //根据信用代码查询最近两年销项发票明细（入参格式:信用代码）
        if($requestData['type'] == 60 ){
            $response  = [];
            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceGoodsData(
                $key,
                $last2YearStart,
                $lastMonth,
                2
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                $response[] = $InvoiceData;
            }
        }

        //根据信用代码导出最近两年销项发票明细（入参格式:信用代码）
        if($requestData['type'] == 65 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_最近两年销项发票明细.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
                "发票代码",
                "发票号码",
                "开票日期",
                "开票金额",
                "税额",
                "规格型号",
                "商品名",
                "税率",
                "单价",
                "数量",
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            // $startDate 往前推一个月  推两年
            //纳税数据取得是两年的数据 取下开始结束时间
            $lastMonth = date("Y-m-01",strtotime("-1 month"));
            //两年前的开始月
            $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
            $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceGoodsData(
                $key,
                $last2YearStart,
                $lastMonth,
                2
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        //根据信用代码查询增值税（入参格式:信用代码）
        if($requestData['type'] == 70 ){
            $response = (new GuoPiaoService())->getVatReturn(
                $key
            );
        }

        //根据信用代码导出增值税（入参格式:信用代码）
        if($requestData['type'] == 75 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_增值税.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
                "declarationDate",
                "currentGoods",
                "endDate",
                "levyProjectName",
                "projectType",
                "currentYearAccumulativeService",
                "beginDate",
                "sequence",
                "columnSequence",
                "immediateRetreatYearAccumulativeAmount",
                "taxpayerType",
                "currentYearAccumulativeGoods",
                "generalMonthAmount",
                "projectNameCode",
                "taxNo",
                "projectName",
                "currentService",
                "deadline",
                "taxpayerId",
                "generalYearAccumulativeAmount",
                "immediateRetreatMonthAmount"
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            $allInvoiceDatas = (new GuoPiaoService())->getVatReturn(
                $key
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$allInvoiceDatas' => $allInvoiceDatas,
                    'is_array' => is_array($allInvoiceDatas),
                ])
            );
            foreach ($allInvoiceDatas as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        return $this->writeJson(200, [], [
            [
                'params'=> json_encode([
                    '$key'=>$key,
                    '$arr'=>$arr,
                    '$arr0'=>$arr[0],
                    '$arr1'=>$arr[1],
                    '$arr2'=>$arr[2],
                ],JSON_UNESCAPED_UNICODE),
                'return_datas_json'=>is_array($response)?json_encode($response,JSON_UNESCAPED_UNICODE):$response,
            ]
        ],'成功 ');
    }

    public function commonToosOptions(){

        return $this->writeJson(200, [], [
            5 => '通过企业名称查询我们库里的企业管理人(company_manager)（入参格式:企业名）',
            10 => '通过信用代码查询非公开联系人（入参格式:信用代码）',
            15 => '通过手机号检测号码状态（入参格式:英文逗号分隔的手机号）',
            20 => '根据微信名匹配企业对应的联系人（入参格式:企业名&&&微信名）',
            25 => '根据微信名匹配中文姓名（入参格式:中文姓名&&&微信名）',
            30 => '根据信用代码查询最近两年进项发票（入参格式:信用代码）',
            35 => '根据信用代码导出最近两年进项发票（入参格式:信用代码）',
            40 => '根据信用代码查询最近两年销项发票（入参格式:信用代码）',
            45 => '根据信用代码导出最近两年销项发票（入参格式:信用代码）',
            50 => '根据信用代码查询最近两年进项发票明细（入参格式:信用代码）',
            55 => '根据信用代码导出最近两年进项发票明细（入参格式:信用代码）',
            60 => '根据信用代码查询最近两年销项发票明细（入参格式:信用代码）',
            65 => '根据信用代码导出最近两年销项发票明细（入参格式:信用代码）',
            70 => '根据信用代码查询增值税（入参格式:信用代码）',
            75 => '根据信用代码导出增值税（入参格式:信用代码）',
        ],'成功');
    }


    public function uploadeGongKaiContacts(){
        $requestData =  $this->getRequestData();
        $succeedFiels = [];
        $files = $this->request()->getUploadedFiles();
       // return $this->writeJson(200, [], [],'成功 入库文件:'.join(',',$succeedFiels));
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
                        'type' => ToolsFileLists::$type_upload_gong_kai_contact,
                        'state' => $requestData['state']?:'',
                        'touch_time' => $requestData['touch_time']?:'',
                    ]
                );
                if(!$UploadRecordRes){
                    return $this->writeJson(203, [], [],'文件上传失败');
                }

                    $res = QueueLists::addRecord(
                        [
                            'name' => '上传非公开联系人',
                            'desc' => '',
                            'func_info_json' => json_encode(
                                [
                                    'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                                    'static_func'=> 'shangChuanGongKaiContact',
                                ]
                            ),
                            'params_json' => json_encode([

                            ]),
                            'type' => ToolsFileLists::$type_upload_gong_kai_contact,
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
    public function getAllContactsFromDb(){
        $requestData =  $this->getRequestData();
        $page =$requestData['page']?:1;
        $pageSize =$requestData['pageSize']?:20;
//        return $this->writeJson(200, [
//            'page' => $page,
//            'pageSize' =>$pageSize,
//            'total' => $total,
//            'totalPage' =>  ceil( $total/ $pageSize ),
//        ],  [
//            [
//                'id'=>1,
//                'entname'=>'北京公司',
//                'code'=>'XXXX',
//                'pub_contacts'=>'13269706293,13269706293,13269706293,13269706293,13269706293,13269706293,13269706293,13269706293',
//                'pri_contacts'=>'13269706293,13269706293,13269706293,13269706293,13269706293,13269706293,13269706293,13269706293',
//                'qcc_contacts'=>'13269706293,13269706293,13269706293,13269706293,13269706293,13269706293,13269706293,13269706293',
//
//            ]
//        ],'');


        if($requestData['code']){
            $all =   CompanyClue::getAllContactByCode($requestData['code']);

            $companyRes = CompanyBasic::findByCode($requestData['code']);
        }
        else{
            $all = [];
            $companyRes = [];
        }


        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],   [
            [

                'entname'=>$companyRes->ENTNAME,
                'code'=>$requestData['code'],
                'pub_contacts'=>$all['pub'],
                'pri_contacts'=>$all['pri'],
                'qcc_contacts'=>$all['qcc'],

            ]
        ],'');
    }

    /*
      type: 5 url补全
      type: 10 微信匹配
      type: 15 模糊匹配企业名称
      type: 20 检测手机号状态

     * */
    public function uploadeFiles(){
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'is_array' =>  [5,10,15,20],
                    'field_name' => 'type',
                    'err_msg' => '参数错误',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $files = $this->request()->getUploadedFiles();

        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $fileName = date('Y_m_d_H_i',time()).$fileName;
                $path = TEMP_FILE_PATH . $fileName;
                if(file_exists($path)){
                    return $this->writeJson(203, [], [],'文件已存在！');;
                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    CommonService::getInstance()->log4PHP( json_encode(['uploadeFiles   file_not_exists moveTo false ', 'params $path '=> $path,  ]) );
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }

                $UploadRecordRes =  ToolsUploadQueue::addRecordV2(
                    [
                        'admin_id' => $this->loginUserinfo['id'], //
                        'upload_file_name' => $fileName, //
                        'upload_file_path' => $path, //
                        'download_file_name' => '', //
                        'download_file_path' => '', //
                        'title' => $requestData['title']?:'', //
                        'params' => $requestData['params']?:'', //
                        'type' => $requestData['type'], //
                        'status' => ToolsUploadQueue::$state_init, //
                        'remark' => $requestData['remark']?:'', //
                    ]
                );
                if(!$UploadRecordRes){
                    return $this->writeJson(203, [], [],'文件上传成功！');
                }
                $succeedNums ++;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], [],'导入失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], [],'导入成功 入库文件数量:'.$succeedNums);
    }

    /*
     * 获取上传的文件列表
     * */
    public function getUploadLists(){
        $page = $this->request()->getRequestParam('page')??1;
        $res = ToolsUploadQueue::findByConditionV2(
            [
//                [
//                    'field' => 'admin_id',
//                    'value' => $this->loginUserinfo['id'],
//                    'operate' => '=',
//                ],
            ],
            $page
        );

        foreach ($res['data'] as &$value){
            $value['download_file_path'] = $value['download_file_name']?'/Static/Temp/'.$value['download_file_name'] : '';
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 20 ),
        ], $res['data'],'成功');
    }


}