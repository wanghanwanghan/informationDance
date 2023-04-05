<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Tools;

use App\Crontab\CrontabList\RunDealZhaoTouBiao;
use App\Csp\Service\CspService;
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
use App\HttpController\Models\AdminV2\MobileCheckInfo;
use App\HttpController\Models\AdminV2\QueueLists;
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\BusinessBase\CompanyClue;
use App\HttpController\Models\BusinessBase\CompanyClueMd5;
use App\HttpController\Models\EntDb\EntDbEnt;
use App\HttpController\Models\EntDb\EntDbFinance;
use App\HttpController\Models\MRXD\TmpInfo;
use App\HttpController\Models\MRXD\TmpInfo2;
use App\HttpController\Models\MRXD\ToolsFileLists;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\HdSaic\CompanyHistoryManager;
use App\HttpController\Models\RDS3\HdSaic\CompanyHistoryName;
use App\HttpController\Models\RDS3\HdSaic\CompanyManager;
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
use App\HttpController\Service\Common\XlsWriter;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\LongXin\FinanceRange;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\Zip\ZipService;
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
                'name' => '根据微信名补全联系人姓名职位等信息',
                'path' => '/Static/Template/补全联系人姓名职位等信息[主要基于微信名和联系人库].xlsx',
            ],
            [
                'name' => '根据支付宝名补全联系人姓名职位等信息',
                'path' => '/Static/Template/根据支付宝名匹配联系人名称.xlsx',
            ],
            [
                'name' => '将表格根据手机号拆分成多行',
                'path' => '/Static/Template/将表格根据手机号拆分成多行.xlsx',
            ],
            [
                'name' => '剔除代理记账并去空号',
                'path' => '/Static/Template/剔除代理记账并去空号.xlsx',
            ],
        ],'');
    }

    // 用户-上传类型
    public function uploadeTypeLists(){

        return $this->writeJson(200, [], [
                5   =>  '补全企业联系人信息(并检测手机状态)',
                6  =>  '根据企业微信补全联系人姓名职位等信息',
                10  =>  '根据微信名补全联系人姓名职位等信息',
                12  =>  '根据支付宝名补全联系人姓名职位等信息',
                13  =>  '根据支付宝名补全桃树对应得联系人姓名职位等信息',
                15  =>  '模糊匹配企业名称',
                20  =>  '将表格根据手机号拆分成多行',
                //25  =>  '补全企业字段',
                30  =>  '剔除代理记账并去空号',
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

        $res = QueueLists::addRecord(
            [
                'name' => '重新拉取非公开联系人',
                'desc' => '',
                'func_info_json' => json_encode(
                    [
                        'class' => '\App\HttpController\Models\MRXD\ToolsFileLists',
                        'static_func'=> 'pullFeiGongKaiContacts',
                    ]
                ),
                'params_json' => json_encode([
                    "date" => date("Y-m-d"),
                    "user_info" => $this->loginUserinfo['user_name'],
                ],JSON_UNESCAPED_UNICODE),
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
            'total' => 0,
            'totalPage' =>  1,
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
                    "date" => date("Y-m-d"),
                    "user_info" => $this->loginUserinfo['user_name'],
                ],JSON_UNESCAPED_UNICODE),
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
            'total' => 0,
            'totalPage' =>  1,
        ],  $res['data'],'');
    }

    //上传补全字段
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
                                "date" => date("Y-m-d"),
                                "user_info" => $this->loginUserinfo['user_name'],
                            ],JSON_UNESCAPED_UNICODE),
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
                            'fill_position_by_name' => $requestData['get_zhiwei']?1:0,
                            'fill_weixin_by_phone' => $requestData['get_wxname']?1:0,
                            'fill_name_and_position_by_weixin' => $requestData['get_namezhiwei']?1:0,
                            'filter_qcc_phone' => $requestData['get_filterQccPhone']?1:0,
                            "date" => date("Y-m-d"),
                            "user_info" => $this->loginUserinfo['user_name'],
                        ],JSON_UNESCAPED_UNICODE),
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
                                "date" => date("Y-m-d"),
                                "user_info" => $this->loginUserinfo['user_name'],
                            ],JSON_UNESCAPED_UNICODE),
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
                            'fill_position_by_name' => $requestData['get_zhiwei']?1:0,
                            'fill_weixin_by_phone' => $requestData['get_wxname']?1:0,
                            'fill_name_and_position_by_weixin' => $requestData['get_namezhiwei']?1:0,
                            'filter_qcc_phone' => $requestData['get_filterQccPhone']?1:0,
                            'pull_gong_shang_shu_ju' => $requestData['pull_gong_shang_shu_ju']?1:0,
                            "date" => date("Y-m-d"),
                            "user_info" => $this->loginUserinfo['user_name'],
                        ],JSON_UNESCAPED_UNICODE),
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
                                "date" => date("Y-m-d"),
                                "user_info" => $this->loginUserinfo['user_name'],
                            ],JSON_UNESCAPED_UNICODE),
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

        //根据企业名称查询库里全部的联系人名称和职位(老梗)
        if($requestData['type'] == 2 ){
            $response = LongXinService::getLianXiByName($key);
        }

        //通过企业名称查询我们库里的有职务信息的企业管理人(company_manager)（入参格式:企业名）
        if($requestData['type'] == 5 ){
            $response = LongXinService::getLianXiByNameV2($key);
        }


        //通过企业名称查询我们库里的所有企业管理人(company_manager)（入参格式:企业名）
        if($requestData['type'] == 6 ){
            $companyRes = CompanyBasic::findByName($key);
            //管理人
            $response = CompanyManager::findByCompanyId($companyRes->companyid);
        }

        //通过企业名称查询我们库里的所有历史企业管理人(company_history_manager)（入参格式:企业名）
        if($requestData['type'] == 7 ){
            $companyRes = CompanyBasic::findByName($key);
            //管理人
            $response = CompanyHistoryManager::findByCompanyId($companyRes->companyid);
        }

        //通过企业名称查询公开联系人(company_manager)
        if($requestData['type'] == 8 ){
            $postData = [
                'entName' => $key,
            ];

            $response =  (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntLianXi($postData);
        }

        //通过信用代码查询非公开联系人
        if($requestData['type'] == 10 ){
            $response = CompanyClue::getAllContactByCode($key);
            $response = [
                '非公开联系人来源1（pub）'=>$response['pub'],
                '非公开联系人来源2（pri）'=>$response['pri'],
                '非公开联系人来源3（qcc）'=>$response['qcc'],
                '非公开联系人来源3（prd）'=>$response['prd'],
                '非公开联系人（xn）'=>$response['xn'],
                '非公开联系人（xn_with_pxd）'=>$response['xn_with_pxd'],
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
                "申报日期",
                "本期数-货物及劳务",
                "所属时期止",
                "征收项目",
                "项目类型",
                "本年累计-服务、不动产和无形资产",
                "所属时期起",
                "顺序",
                "栏次",
                "即征即退项目-本年累计",
                "纳税人类型(0：一般纳税人 1：小规模纳税人)",
                "本年累计-货物及劳务",
                "一般项目-本月数",
                "项目代码",
                "授权批次号",
                "项目名称",
                "本期数-服务、不动产和无形资产",
                "deadline",
                "企业信用代码",
                "一般项目-本年累计",
                "即征即退项目-本月数"
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            $allInvoiceDatas = (new GuoPiaoService())->getVatReturn(
                $key
            );
            $allInvoiceDatas = jsonDecode($allInvoiceDatas['data']); 
            foreach ($allInvoiceDatas as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        //根据信用代码查询所得税（入参格式:信用代码）
        if($requestData['type'] == 80 ){
            $response =  (new GuoPiaoService())->getIncometaxMonthlyDeclaration(
                $key
            );
        }

        //根据信用代码导出所得税（入参格式:信用代码）
        if($requestData['type'] == 85 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_所得税.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
                "申报日期",//
                "所属时期止",//
                "征收项目",//
                "累计金额",//
                "项目类型",//
                "项目父类型",//
                "本期金额(2015版专有)",//
                "所属时期起",//
                "顺序",//
                "tableType",
                "栏次",//
                "项目代码",//
                "所属税务局",//
                "授权批次号",//
                "项目名称",//
                "deadline",
                "纳税识别号" //
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            $allInvoiceDatas = (new GuoPiaoService())->getIncometaxMonthlyDeclaration(
                $key
            );
            $allInvoiceDatas = jsonDecode($allInvoiceDatas['data']);
            foreach ($allInvoiceDatas as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        //根据信用代码查询所得税（入参格式:信用代码）
        if($requestData['type'] == 90 ){
            $response = (new GuoPiaoService())->getEssential(
                $key
            );
        }

        //根据信用代码导出企业税务基本信息（入参格式:信用代码）
        if($requestData['type'] == 95 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_企业税务基本信息.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
               '是否欠税（是/否）',//
                '纳税状态 = 正常/异常',//
                '违章稽查记录（条）',
                '纳税人性质',
                '基本信息-纳税信用等级-年份',
                '基本信息-纳税信用等级-税务征信等级，枚举值[A、B、C、D、M、不参评、暂无、该纳税人还未终审完成]',
                '基本信息-纳税信用等级-纳税人识别号',
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            $allInvoiceDatas = (new GuoPiaoService())->getEssential(
                $key
            );

            fputcsv($f, [
                $allInvoiceDatas['data']['owingType'],//是否欠税（是/否）
                $allInvoiceDatas['data']['payTaxes'],//纳税状态 = 正常/异常
                $allInvoiceDatas['data']['regulations'],//违章稽查记录（条）
                $allInvoiceDatas['data']['nature'],//纳税人性质
                $allInvoiceDatas['data']['essential'][0]['year'],//基本信息-纳税信用等级-年份
                $allInvoiceDatas['data']['essential'][0]['creditLevel'],//基本信息-纳税信用等级-税务征信等级，枚举值[A、B、C、D、M、不参评、暂无、该纳税人还未终审完成]
                $allInvoiceDatas['data']['essential'][0]['taxpayerId']//基本信息-纳税信用等级-纳税人识别号
            ]);

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        //根据信用代码查询企业利润（入参格式:信用代码）
        if($requestData['type'] == 100 ){
            $response =  (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getFinanceIncomeStatement(
                $key
            );
        }

        //根据信用代码导出企业利润（入参格式:信用代码）
        if($requestData['type'] == 105 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_企业利润.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
                    "本月累计金额",//
                    "申报日期",//
                    "所属时期止",//
                    "征收项目",//
                    "reportType",
                    "所属时期起",//
                    "顺序",//
                    "栏次",//
                    "本年累计金额",//
                    "项目代码",//
                    "授权批次号",//
                    "项目名称",//
                    "纳税识别号",//
                    "SQJE"
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            $allInvoiceDatas = (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getFinanceIncomeStatement(
                    $key
                );
            //$allInvoiceDatas = jsonDecode($allInvoiceDatas['data']);
            foreach ($allInvoiceDatas['result'] as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;

        }

        //根据信用代码查询资产负债（入参格式:信用代码）
        if($requestData['type'] == 110 ){
            $response =  (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getFinanceBalanceSheet(
                    $key
                );
        }

        //根据信用代码导出资产负债（入参格式:信用代码）
        if($requestData['type'] == 115 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."_资产负债.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
                "申报日期",//
                "所属时期止",//
                "征收项目",//
                "projectType",
                "期末数",//
                "资产负债类型，1:资产，2:负债及所有者权益",//
                "reportType",
                "所属时期起",//
                "顺序",//
                "栏次",//
                "年初数",//
                "项目代码",//
                "授权批次号",//
                "项目名称",//
                "纳税识别号"//
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);


            $allInvoiceDatas = (new GuoPiaoService())
                ->setCheckRespFlag(true)
                ->getFinanceBalanceSheet(
                    $key
                );
            //$allInvoiceDatas = jsonDecode($allInvoiceDatas['data']);
            foreach ($allInvoiceDatas['result'] as $InvoiceData){
                fputcsv($f, $InvoiceData);
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;

        }

        //125 根据日期查询新的招投标邮件对应的文件（入参格式:日期|如2022-11-11）
        if($requestData['type'] == 125 ){
            $res = RunDealZhaoTouBiao::exportDataV8($key);
            $filename = 'zhao_tou_biao_new_'.date('Y_m_d_H_i');
            $zipRes = ZipService::getInstance()->zip( $res['filesArr'], TEMP_FILE_PATH . $filename . '.zip');
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '发送招投标邮件（2）-压缩csv文件' => [
                        //'csv文件集合'=> $res['filesArr'],
                        '压缩结果'=> $zipRes,
                        '新的文件名'=> $filename,
                    ]
                ])
            );
            $response[] = 'http://api.test.meirixindong.com/Static/Temp/'.$filename. '.zip';
        }

        //126 根据日期发送新的招投标邮件对应的文件（入参格式:日期|如2022-11-11）
        if($requestData['type'] == 126 ){
            $response[] = RunDealZhaoTouBiao::sendEmailV4($key,[
                'tianyongshan@meirixindong.com',
                'minglongoc@me.com',
                'zhengmeng@meirixindong.com',
                'luoyuting@huoyan.cn',
                'liqingfeng@huoyan.cn',
                //'luoyuting@huoyan.cn',
            ]);

        }

        //空号验证的时候：有多少其他错误
        if($requestData['type'] == 127 ){

            $res2 = MobileCheckInfo::findAllByCondition([
                'status' => 999,
            ]);

            $response[] = count($res2);
        }

        //空号验证里的其他错误，重新拉取（入参格式：重拉的数量）
        if($requestData['type'] == 128 ){

            $res2 = MobileCheckInfo::reCheckV2(intval($key));

            $response[] = $res2 ;
        }

        //
        if($requestData['type'] == 129 ){

            $res = RunDealZhaoTouBiao::exportDataV5($key);
            $response[] = $res['filename_url'];
        }

        //查询代理记账信息（ 入参格式:手机号）
        if($requestData['type'] == 130 ){
            //代理记账
            $daiLiJiZhang = CompanyClueMd5::daiLiJiZhang($key);

            // 调用接口查询手机号状态
            $postData = [
                'mobiles' => $key,
            ];

            $res = (new ChuangLanService())->getCheckPhoneStatus($postData);
            $response['代理记账'] = $daiLiJiZhang;
            $response['空号验证'] = $res;

        }

        //查询本周招投标信息
        if($requestData['type'] == 131 ){
            $the_date = $key;
            $the_day_of_week = date("w",strtotime($the_date)); //sunday is 0

            $first_day_of_week = date("Y-m-d",strtotime( $the_date )-60*60*24*($the_day_of_week)+60*60*24*1 );
            $last_day_of_week = date("Y-m-d",strtotime($first_day_of_week)+60*60*24*4 );

            $dateStart = $first_day_of_week.' 00:00:00';
            $dateEnd = $last_day_of_week.' 23:59:59';

            $response['查询日期'] = $key;
            $response['查询日期-周1'] = $first_day_of_week;
            $response['查询日期-周5'] = $last_day_of_week;

            $tables = [
                'zhao_tou_biao_key01',
                'zhao_tou_biao_key02',
                'zhao_tou_biao_key03',
                'zhao_tou_biao_key04',
                'zhao_tou_biao_key05',
                'zhao_tou_biao_key06',
                'zhao_tou_biao_key07',
                'zhao_tou_biao_key08',
                'zhao_tou_biao_key09',
                'zhao_tou_biao_key10',
                'zhao_tou_biao_key11',
                'zhao_tou_biao_key12',
                'zhao_tou_biao_key13',
                'zhao_tou_biao_all',
            ];
            foreach ($tables as $table){
                //===========
                $datas =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
                    " SELECT * FROM  $table WHERE updated_at >= '$dateStart' AND  updated_at <= '$dateEnd'  "
                );
                $response['来源'.$table]['本周总数'] = count($datas);
                $response['来源'.$table]['sql'] = " SELECT * FROM  $table WHERE updated_at >= '$dateStart' AND  updated_at <= '$dateEnd'  ";

                $datas =  \App\HttpController\Models\RDS3\ZhaoTouBiao\ZhaoTouBiaoAll::findBySqlV2(
                    " SELECT `标题`,`updated_at` FROM  $table      ORDER BY  updated_at desc  LIMIT 1 
                    "
                );
                $response['来源'.$table]['最新一条数据'] = $datas;
            }

            //===========
        }

        //
        if($requestData['type'] == 132 ){
            $filename = scandir(
                '/home/wwwroot/informationDance_test/Static/shan_xi'
            );

            $i = 1;
            foreach($filename as $k=>$v){
                if($key <= $i){
                    break;
                }

                if($v=="." || $v==".."){continue;}

                $ext = pathinfo($v);
                if($ext['extension']=='json'){
                    $name = substr($v,0,strpos($v,"."));
                    //sleep(1);
                    usleep(100);
                    $ctx = stream_context_create(array('http'=>
                        array(
                            'timeout' => 3,  //3 Seconds
                        )
                    ));
                    $str = file_get_contents('/home/wwwroot/informationDance_test/Static/shan_xi/'.$v, false, $ctx);
                    //$str = file_get_contents('/home/wwwroot/informationDance_test/Static/shan_xi/'.$v);
                    $arr = json_decode($str,true);
                    if(empty($arr)){
                        CommonService::getInstance()->log4PHP(
                            json_encode([
                                '抓取山西数据'=>[
                                    'json异常'=>[
                                        '$v'=>$v,
                                        '$arr'=>$arr,
                                        '$name'=>$name,
                                        '$str'=>$str,
                                    ]
                                ]
                            ],JSON_UNESCAPED_UNICODE)
                        );
                        continue;
                    }

                    if(
                        is_array($arr['hits']['hits']) &&
                        !empty($arr['hits']['hits'])
                    ){
                        foreach ($arr['hits']['hits'] as $tmp){

                            $data_tmp = [
                                'page' =>$name,
                                'pathName' =>$tmp['_source']['pathName'],
                                'districtName' =>$tmp['_source']['districtName'],
                                'gpCatalogName' =>$tmp['_source']['gpCatalogName'],
                                'publishDate' =>$tmp['_source']['publishDate'],
                                'procurementMethod' =>$tmp['_source']['procurementMethod'],
                                'articleId' =>$tmp['_source']['articleId'],
                                'siteId' =>$tmp['_source']['siteId'],
                                'gpCatalogType' =>$tmp['_source']['gpCatalogType'],
                                'title' =>$tmp['_source']['title'],
                                'url' =>$tmp['_source']['url'],
                                'real_url' => 'http://www.ccgp-shanxi.gov.cn'.$tmp['_source']['url'],
                            ];

                            $detail_str = file_get_contents('http://www.ccgp-shanxi.gov.cn'.$tmp['_source']['url']);

                            $regex='/采购人（甲方）：(.*)&lt;\/samp&gt;&lt;/U';
                            preg_match_all($regex,$detail_str,$result1);
                            $data_tmp['jia_fang'] = str_replace(
                                '&lt;samp style&#x3D;\&quot;font-family: inherit\&quot; class&#x3D;\&quot;bookmark-item uuid-1653374723811 code-00014 editDisable interval-text-box-cls readonly\&quot;&gt;',
                                '',
                                $result1[1][0]
                            );

                            $regex='/联系方式：(.*)&lt;\/samp&gt;&amp;nbsp;/U';
                            preg_match_all($regex,$detail_str,$result2);
                            $data_tmp['jia_fang_contacts'] = str_replace(
                                '&lt;samp style&#x3D;\&quot;font-family: inherit\&quot; class&#x3D;\&quot;bookmark-item uuid-1653374744359 code-00016 editDisable single-line-text-input-box-cls readonly\&quot;&gt;',
                                '',
                                $result2[1][0]
                            );

                            // 供应商（乙方）：
                            $regex='/供应商（乙方）：(.*)&lt;\/samp&gt;&lt/U';
                            preg_match_all($regex,$detail_str,$result3);
                            $data_tmp['yi_fang'] = str_replace(
                                '&lt;samp style&#x3D;\&quot;font-family: inherit\&quot; class&#x3D;\&quot;bookmark-item uuid-1653374757031 code-81201 addWord single-line-text-input-box-cls\&quot;&gt;',
                                '',
                                $result3[1][0]
                            );

                            $regex='/联系方式：(.*)&lt;\/samp&gt;&amp;nbsp;/U';
                            preg_match_all($regex,$detail_str,$result2);
                            $data_tmp['yi_fang_contacts'] = str_replace(
                                '&lt;samp style&#x3D;\&quot;font-family: inherit\&quot; class&#x3D;\&quot;bookmark-item uuid-1653374785720 code-AM014supplierContact addContent single-line-text-input-box-cls\&quot;&gt;',
                                '',
                                $result2[1][1]
                            );

                            //
                            $regex='/合同金额（元）：(.*)&lt;\/samp&gt;&lt;/U';
                            preg_match_all($regex,$detail_str,$result5);
                            $data_tmp['contact_money'] = str_replace(
                                '&lt;samp style&#x3D;\&quot;font-family: inherit\&quot; class&#x3D;\&quot;bookmark-item uuid-1653374884933 code-AM014totalContractAmount addWord single-line-text-input-box-cls\&quot;&gt;',
                                '',
                                $result5[1][0]
                            );

                            TmpInfo::addRecord(
                                $data_tmp
                            );
                        }
                    }
                }
                $i++;
                @unlink('/home/wwwroot/informationDance_test/Static/shan_xi/'.$v);
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '抓取山西数据'=>[
                            '已完成$v'=>$v,
                            '已完成$i'=>$i,
                        ]
                    ],JSON_UNESCAPED_UNICODE));
            }

            $response['$i'] = $i;
            //===========
        }

        if($requestData['type'] == 133 ){
            $response  = [];

            //写到csv里
            $fileName = date('YmdHis')."山西政府采购网_合同公告信息.csv";
            $f = fopen(OTHER_FILE_PATH.$fileName, "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            $allFields = [
                "序号",
                "标题",
                "采购人（甲方）",
                "供应商（乙方）",
                "乙方联系人",
                "采购方式",
                "地区",
            ];
            foreach ($allFields as $field=>$cname){

                $title[] = $cname ;
            }
            fputcsv($f, $title);

            $allInvoiceDatas = TmpInfo::findBySql("SELECT page,title,jia_fang,yi_fang,yi_fang_contacts,procurementMethod,districtName FROM tmp_info   
            ");
            //$allInvoiceDatas = jsonDecode($allInvoiceDatas['data']);
            $i =1;
            foreach ($allInvoiceDatas as $InvoiceData){
                if($i%100==0){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            '导出山西政府采购网_已生成' => $i,
                            '导出山西政府采购网_文件名' => $fileName,
                        ],JSON_UNESCAPED_UNICODE)
                    );
                }
                fputcsv($f, $InvoiceData);
                $i++;
            }

            $response[] = "http://api.test.meirixindong.com/Static/OtherFile/".$fileName;
        }

        if($requestData['type'] == 134 ){
            $files = glob("/home/wwwroot/tianyongshan/top500/*list.json");
            $i =1;
            foreach($files as $file) {
                $content = file_get_contents($file);
                $pathinfo  = pathinfo($file);
                $content = json_decode($content,true);
                foreach ($content['blocklist']['searchlist']['data']['resultlist']['data'] as $dataItem){
                    TmpInfo::addRecordV2([
                        'ID'=>$dataItem['ID'],
                        'CompanyName'=>$dataItem['CompanyName'],
                        'content'=> json_encode($dataItem,JSON_UNESCAPED_UNICODE),
                        'remark'=>$pathinfo['filename'],
                    ]);
                };
                @unlink('/home/wwwroot/tianyongshan/top500/'.$file);
                if($i%100==0){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            '解析top500_已生成' => $i,
                            '解析top500_文件名' => $file,
                        ],JSON_UNESCAPED_UNICODE)
                    );
                }
                $i++;
            }
        }

        if($requestData['type'] == 135 ){
            $files = glob("/home/wwwroot/tianyongshan/top500/*details.json");
            $i = 1;
            foreach($files as $file) {
                $content = file_get_contents($file);
                $pathinfo  = pathinfo($file);
                $content = json_decode($content,true);
                foreach ($content as $dataItem){
                    TmpInfo2::addRecordV2([
                        'brandid'=>$dataItem['brandid'],
                        'content'=> json_encode($dataItem,JSON_UNESCAPED_UNICODE),
                        'remark'=>$pathinfo['filename'],
                    ]);
                };
                @unlink('/home/wwwroot/tianyongshan/top500/'.$file);
                if($i%100==0){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            '解析top500详情_已生成' => $i,
                            '解析top500详情_文件名' => $file,
                        ],JSON_UNESCAPED_UNICODE)
                    );
                }
                $i ++;
            }

        }

        if($requestData['type'] == 136 ){
            $response  = [];

            $file = "/home/wwwroot/informationDance_test/Static/Temp/桃树.txt";
            $handle = fopen($file, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $entName = trim($line);
                    $postData = [
                        'entName' => $entName,
                        'pageNo' => 1,
                        'pageSize' => 100,
                    ];

                    $res = (new TaoShuService())->post($postData, __FUNCTION__);
                    if ($res['code'] == 200 && !empty($res['result'])) {
                        foreach ($res['result'] as &$one) {
                            $one['CONRATIO'] = formatPercent($one['CONRATIO']);
                        }
                        unset($one);
                    }
                    $response[]  =  $entName;
                    $response[]  =  $res;
                    break;
                }

                fclose($handle);
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

    /**
    通用工具类
     */
    public function commonToosV2(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [], [
            "datas" => [
                [
                    "show_1"=>"内容1",
                    "show_2"=>"内容2",
                ]
            ],
            "fields" => [
                "show_1" => "字段1",
                "show_2" => "字段2",
            ]
        ],'成功 ');
    }

    public function commonToosOptions(){
        return $this->writeJson(200, [], [
            2 => '根据企业名称查询库里全部的联系人名称和职位(老梗)（入参格式:企业名）',
            5 => '通过企业名称查询我们库里的有职务信息的企业管理人(company_manager)（入参格式:企业名）',
            6 => '通过企业名称查询我们库里的所有企业管理人(company_manager)（入参格式:企业名）',
            7 => '通过企业名称查询我们库里的所有历史企业管理人(company_history_manager)（入参格式:企业名）',
            8 => '通过企业名称查询公开联系人（入参格式:企业名称）',
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
            80 => '根据信用代码查询所得税（入参格式:信用代码）',
            85 => '根据信用代码导出所得税（入参格式:信用代码）',
            90 => '根据信用代码查询企业税务基本信息（入参格式:信用代码）',
            95 => '根据信用代码导出企业税务基本信息（入参格式:信用代码）',
            100 => '根据信用代码查询企业利润（入参格式:信用代码）',
            105 => '根据信用代码导出企业利润（入参格式:信用代码）',
            110 => '根据信用代码查询资产负债（入参格式:信用代码）',
            115 => '根据信用代码导出资产负债（入参格式:信用代码）',
            //120 => '根据json查询导出资产负债（入参格式:信用代码）',
            125 => '根据日期查询新的招投标邮件对应的csv文件（入参格式:日期|如2022-11-11）',
            126 => '根据日期发送新的招投标邮件对应的文件（入参格式:日期|如2022-11-11）',
            127 => '空号验证的时候：有多少其他错误',
            128 => '空号验证里的其他错误，重新拉取（入参格式：重拉的数量）',
            129 => '根据日期查询新的招投标邮件对应的xlsx文件（入参格式:日期|如2022-11-11）',
            130 => '查询代理记账信息（入参格式:手机号）',
            131 => '查询本周招投标信息（入参格式:日期|如2022-11-11）',
            132 => '根据json抓取山西官网数据（入参格式:数量）',
            133 => '导出山西官网数据',
            134 => '解析top500_列表',
            135 => '解析top500_详情',
            136 => '根据企业名字取套数接口',
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