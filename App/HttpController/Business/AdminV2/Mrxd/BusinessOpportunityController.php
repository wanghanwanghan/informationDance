<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserBussinessOpportunityUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\AdminUserWechatInfoUploadRecord;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
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
                $fileName = date('His').'_'.$fileName;
                $path = TEMP_FILE_PATH .$fileName;

                $ext = pathinfo($path);
                if(
                    $ext['extension']!='xlsx'
                ){
                    return $this->writeJson(203, [], [],'不是xlsx文件('.$ext['extension'].')！');;
                }


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
                        'pull_api' => $requestData['pull_api']?1:0,
                        //按手机号拆分成多行
                        'split_mobile' => 1,
                        //删除空号
                        'del_empty' => 1,
                        //匹配微信
                        'match_by_weixin' => 1,
                        //取全字段
                        'get_all_field' => $requestData['get_all_field']?1:0,
                        //填充旧的微信
                        'fill_weixin' => 1,
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

    public function uploadWeiXinFile(){
        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();
        //return $this->writeJson(200, [], [],'导入成功 入库文件数量:');
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

                $addUploadRecordRes = AdminUserWechatInfoUploadRecord::addRecordV2(
                    [
                        'user_id' => $this->loginUserinfo['id'],
                        'file_path' => TEMP_FILE_PATH,
                        'title' => $requestData['title']?:'',
                        'size' => filesize($path),
                        'batch' =>  'BO'.date('YmdHis'),
                        'reamrk' => $requestData['reamrk']?:'',
                        'name' =>  $fileName,
                        'status' => AdminUserWechatInfoUploadRecord::$status_init,
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

    public function WeiXinFilesList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $pageSize = $requestData['pageSize']?:10;

        $conditions = [];
        if($requestData['nickname']){
            $conditions[]  =  [
                'field' =>'nickname',
                'value' =>$requestData['nickname'].'%',
                'operate' =>'like',
            ];

        }
        $datas = WechatInfo::findByConditionV2(
            $conditions,$page,$pageSize
        );

        foreach ($datas['data'] as &$dataItem){
            if($dataItem['code']){
                $companyRes = CompanyBasic::findByCode($dataItem['code']);
                $companyRes = $companyRes?$companyRes->toArray():[];
                $dataItem['ENTNAME'] = $companyRes['ENTNAME'];
            }
            $phone_res = \wanghanwanghan\someUtils\control::aesDecode($dataItem['phone'], $dataItem['created_at']);
            $dataItem['phone_res'] = $phone_res;
        }

        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $datas['total'],
            'totalPage' => ceil($datas['total']/$pageSize) ,
        ],  $datas['data'],'成功');
    }


    //用户-上传客户列表
    public function bussinessFilesList(){
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
        $size = $requestData['pageSize']?:10;

        //bussinessFilesList
        $conditions = [];
        if(
            $requestData['name']
        ){
            $conditions[] = [
                'field' => 'name',
                'value' => '%'.$requestData['name'].'%',
                'operate' => 'like',
            ];
        }
        $createdAtStr = $requestData['created_at'];
        $createdAtArr = explode('|||',$createdAtStr);

        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $conditions[] =  [
                'field' => 'created_at',
                'value' => strtotime($createdAtArr[0].' 00:00:00'),
                'operate' => '>=',
            ];
            $conditions[] = [
                'field' => 'created_at',
                'value' => strtotime($createdAtArr[1]." 23:59:59"),
                'operate' => '<=',
            ];

        }

        $records = AdminUserBussinessOpportunityUploadRecord::findByConditionV2(
            $conditions,
            $page,
            $size
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$records'   => $records
            ])
        );
        foreach ($records['data'] as &$dataitem){
            $dataitem['status_cname'] = AdminUserBussinessOpportunityUploadRecord::getStatusMap()[$dataitem['status']];
            $dataitem['size'] = self::convert($dataitem['size']) ;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'size1'   => $dataitem['size'],
                    'size2'   => self::convert($dataitem['size']),
                ])
            );
        }
        return $this->writeJson(200, [
            'page' => $page,
            'pageSize' => $size,
            'total' => $records['total'],
            'totalPage' => ceil($records['total']/$size) ,
        ],  $records['data'],'成功');
    }

    static  function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    public function redownloadBussinessFile(){
        $requestData =  $this->getRequestData();
        if(
            $requestData['id'] <= 0
        ){
            return $this->writeJson(201, [
               ],  [],'参数缺失');
        }
        return $this->writeJson(200, [],
            AdminUserBussinessOpportunityUploadRecord::updateById(
                $requestData['id'],
                [
                    'status'=>AdminUserBussinessOpportunityUploadRecord::$status_check_mobile_success
                ]
            )
            ,'成功 ');
    }

}