<?php

namespace App\HttpController\Business\Admin\CheXianWuliu;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\CarInsuranceInfo;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\Api\DianZiQianAuth;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\Zip\ZipService;
use wanghanwanghan\someUtils\control;

class CheXianWuliuController extends CheXianWuliuBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getList(): bool
    {
        $phone = $this->request()->getRequestParam('phone');
        $page = $this->request()->getRequestParam('page');
        if($page <= 0 ){
           $page = 1; 
        }
        $limit = 10;
        $offset = ($page - 1) * $limit;

        CommonService::getInstance()->log4PHP( 'getList');

        $entname = $this->getRequestData('entname');
        $status = $this->getRequestData('status');
        empty($status) ?: $status = jsonDecode($status);
        $createdAtStr = $this->getRequestData('created_at');
        $createdAtArr = explode('|||',$createdAtStr);
      

        $orm = CompanyCarInsuranceStatusInfo::create();

        if (!empty($entname)) {
            $company = Company::create()
                ->where('name', "$entname%", 'LIKE')
                ->all();
            $companyIds = array_column($company,'id');
            $orm->where('entId', $companyIds, 'IN');
            CommonService::getInstance()->log4PHP(
                'entname '.json_encode($companyIds)
            );

        }
        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr) 
        ) { 
            $orm->where('created_at', $createdAtArr[0], '>=');
            $orm->where('created_at', $createdAtArr[1], '<=');
            CommonService::getInstance()->log4PHP(
                'created_at '.json_encode($createdAtArr)
            );
        }
 

        $model = $orm->where('status',CompanyCarInsuranceStatusInfo::$status_all_auth_done)
                        ->page($page)
                        ->order('id', 'DESC')
                        ->withTotalCount(); 

        $res = $model->all();
        CommonService::getInstance()->log4PHP(
            'res '.json_encode($res)
        );
        $total = $model->lastQueryResult()->getTotalCount();


        foreach($res as &$dataItem){
            $tmpEnt  = Company::create()->where( 
                [
                    'id' => $dataItem['entId']
                ]
            )->get();
            $dataItem['entName'] = $tmpEnt->getAttr('name');
            $dataItem['status_cname'] = CompanyCarInsuranceStatusInfo::getStatusMap()[
                $dataItem['status']
            ];

            $dataItem['created_at'] = date('Y-m-d H:i:s', $dataItem['created_at']);
            $dataItem['updated_at'] = date('Y-m-d H:i:s', $dataItem['updated_at']);

        }
        $totalPages = ceil( $total/ $limit );
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>$limit,
            'total' => $total,
            'totalPage' => $totalPages, 
        ] , $res);
    }

    function setIsOk(): bool
    {  
        $idsStr = $this->getRequestData('ids');
        if(!$idsStr){
            return $this->writeJson(203, null, [], '参数缺失!');
        }
      
        $idsArr = explode(',',$idsStr);
        CommonService::getInstance()->log4PHP( 'setIsOk '.json_encode($idsArr));
        // return $this->writeJson(200, null, [], '操作成功(生效个)');
        $succeedNum = 0 ;
        foreach($idsArr as $id){ 
            if(
                CompanyCarInsuranceStatusInfo::ifHasAuthAll($id) 
            ){
                $res = CompanyCarInsuranceStatusInfo::setIsOk($id); 
                if(!$res){
                    return $this->writeJson(205, null, [], '更新失败');
                }
                $succeedNum ++;
            }
        }
 
        return $this->writeJson(200, null, $res, '操作成功(生效'.$succeedNum.'个)');
    }


    function createZip(): bool
    {
        $idsStr = $this->getRequestData('ids');
        if(!$idsStr){
            return $this->writeJson(203, null, [], '参数缺失');
        }
      
        $pdf = [];
        
        $idsArr = explode(',',$idsStr);
        foreach($idsArr as $id){
            $companyCheXianRes = CompanyCarInsuranceStatusInfo::create()->where([
                'id' => $id, 
            ])->get();
            
            // 该企业所有的授权文件
            $allFiles = CarInsuranceInfo::getAuthedFileUrl(
                $companyCheXianRes->getAttr('entId')
            );
            if (empty($allFiles)) { 
                continue;
            } 
            // 把授权文件下载下来打包
            foreach($allFiles as $num => $FileItem){
                foreach($FileItem as $file){
                    $file_name =  'entDownloadUrl_'.$num.'.pdf';
                    $newFileRes = file_put_contents(
                        TEMP_FILE_PATH.$file_name, 
                        file_get_contents($FileItem['entDownloadUrl'])
                    );
                    if ($newFileRes)
                    {
                        $pdf[] = TEMP_FILE_PATH.$file_name;
                    }
                } 

            } 
        } 

        // 打包
        $filename = control::getUuid();
        ZipService::getInstance()->zip($pdf, TEMP_FILE_PATH . $filename . '.zip'); 
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'zip',  
                   $pdf, 
                   TEMP_FILE_PATH . $filename . '.zip', 
                   $filename
                ]
            )
        );  

        // 删除本地文件
        foreach($pdf as $file){
            if(file_exists($file)){
                @unlink($file);
            }            
        }

        $path = $filename; 

        return $this->writeJson(200, null, $path);
    }

    
    function createGetDataTime(): bool
    {
        $ent_arr = $this->getRequestData('ent_arr');

        foreach ($ent_arr as $one) {
            $info = AntAuthList::create()->where([
                'id' => $one['id'],
                'status' => MaYiService::STATUS_2,
            ])->get();
            if (!empty($info)) {
                $info->update([
                    'canGetDataDate' => time(),
                    'status' => MaYiService::STATUS_3,
                ]);
            }
        }

        return $this->writeJson();
    }

}