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

            $key1 = $key;

            $response = LongXinService::getLianXiByNameV2($key1);
        }

        //通过信用代码查询非公开联系人
        if($requestData['type'] == 10 ){
            $key1 = $key;
            $response = CompanyClue::getAllContactByCode($key1);
            $response = [
                '非公开联系人来源1（pub）'=>$response['pub'],
                '非公开联系人来源2（pri）'=>$response['pri'],
                '非公开联系人来源3（qcc）'=>$response['qcc'],
            ];
        }
        //通过手机号检测号码状态（多个手机号英文逗号分隔）
        if($requestData['type'] == 15 ){
            $key1 = $key;
            $response = (new ChuangLanService())->getCheckPhoneStatus([
                'mobiles' => $key1,
            ]);
        }

        // 根据微信名匹配企业对应的联系人（入参格式:企业名&&&微信名）
        if($requestData['type'] == 15 ){

            $key1 = $arr[0];
            $key2 = $arr[1];

            $response = (new XinDongService())->matchContactNameByWeiXinNameV3($key1, $key2);
        }


        return $this->writeJson(200, [], [
            [
                'params'=> json_encode([
                    '$key1'=>$key1,
                    '$key2'=>$key2,
                    '$key3'=>$key3,
                    '$arr'=>$arr,
                ],JSON_UNESCAPED_UNICODE),
                'return_datas_json'=>is_array($response)?json_encode($response,JSON_UNESCAPED_UNICODE):$response,
            ]
        ],'成功 ');
    }

    public function commonToosOptions(){

        return $this->writeJson(200, [], [
            5 => '通过企业名称查询我们库里的企业管理人(company_manager)',
            10 => '通过信用代码查询非公开联系人',
            15 => '通过手机号检测号码状态（多个手机号英文逗号分隔）',
            20 => '根据微信名匹配企业对应的联系人（入参格式:企业名&&&微信名）',
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
                                    'static_func'=> 'buQuanZiDuan',
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