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
use App\HttpController\Models\AdminV2\ToolsUploadQueue;
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
        $total = 1;
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],
            [
                [
                    'id' => 1,
                    'admin_id_cname' => '操作人',
                    'new_file_name' => '文件名称',
                    'new_file_path' => '文件下载地址/Static/Temp/XXX.csv',
                    'remark' => '备注',
                    'state_cname' => '状态',
                    'created_at' => '上传时间',
                ]

            ],'');
    }

    public function uploadeBuQuanZiDuanFiles(){
        $requestData =  $this->getRequestData();
        $page =$requestData['page']?:1;
        $pageSize =$requestData['pageSize']?:20;
        $total = 1;
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' =>$pageSize,
            'total' => $total,
            'totalPage' =>  ceil( $total/ $pageSize ),
        ],
            [

            ],'成功');
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