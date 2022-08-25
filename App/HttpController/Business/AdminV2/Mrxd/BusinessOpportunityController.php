<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserBussinessOpportunityUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class BusinessOpportunityController extends ControllerBase
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
                $path = TEMP_FILE_PATH . $fileName;
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
                        'pull_api' => intval($requestData['pull_api']),
                        //按手机号拆分成多行
                        'split_mobile' => intval($requestData['split_mobile']),
                        //删除空号
                        'del_empty' => intval($requestData['del_empty']),
                        //匹配微信
                        'match_by_weixin' => intval($requestData['match_by_weixin']),
                        //取全字段
                        'get_all_field' => intval($requestData['get_all_field']),
                        //填充旧的微信
                        'fill_weixin' => intval($requestData['fill_weixin']),
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

    //用户-上传客户列表
    public function bussinessFilesList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $size = $requestData['pageSize']?:10;
        $records = AdminUserBussinessOpportunityUploadRecord::findByConditionV2(
            [ ],
            $page
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$records'   => $records
            ])
        );
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $size,
            'total' => $records['total'],
            'totalPage' => ceil($records['total']/$size) ,
        ],  $records['data'],'成功'); 
    }

    public function downloadBussinessFile(){
        $requestData =  $this->getRequestData();
        return $this->writeJson(200, [], '/Static/Temp/dianziqian_ent.png','成功 ');
    }

}