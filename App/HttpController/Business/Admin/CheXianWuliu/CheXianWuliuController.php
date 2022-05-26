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
        CommonService::getInstance()->log4PHP( 'getList');

        $entname = $this->getRequestData('entname');
        $status = $this->getRequestData('status');
        empty($status) ?: $status = jsonDecode($status);

        $orm = CompanyCarInsuranceStatusInfo::create();

        if (!empty($entname)) {
            $orm->where('entName', "%{$entname}%", 'LIKE');
        }

        // if (!empty($status)) {
        //     $orm->where('status', $status, 'IN');
        // }

        $res = $orm->where('status',CompanyCarInsuranceStatusInfo::$status_all_auth_done)
        ->all();
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
        }
        return $this->writeJson(200, null, $res);
    }

    function setOk(): bool
    {
        CommonService::getInstance()->log4PHP( 'getList');

        $idsStr = $this->getRequestData('ids');
        
        CompanyCarInsuranceStatusInfo::create();

        empty($status) ?: $status = jsonDecode($status);

        $orm = CompanyCarInsuranceStatusInfo::create()->where( 
                [
                    'id' => $dataItem['entId']
                ]
            )->get();;

        if (!empty($entname)) {
            $orm->where('entName', "%{$entname}%", 'LIKE');
        }

        // if (!empty($status)) {
        //     $orm->where('status', $status, 'IN');
        // }

        $res = $orm->where('status',CompanyCarInsuranceStatusInfo::$status_all_auth_done)
        ->all();
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
        }
        return $this->writeJson(200, null, $res);
    }

    function createZip(): bool
    {
        $zip_arr = $this->getRequestData('zip_arr');
        $zip_arr = [['entId' => 194490069]];
        if(empty($zip_arr)){
            return $this->writeJson(205, null, null);
        }
        $pdf = [];
        $filename = control::getUuid();
        foreach ($zip_arr as $one) {
            $info = CarInsuranceInfo::create()->where([
                'entId' => $one['entId'],
                'status' => 5,
            ])->get();
            if (empty($info)) {
                
                continue;
            } 
            $res = DianZiQianAuth::create()->where([
                'id' =>  $info->getAttr('auth_res_id')
            ])->get();
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'entDownloadUrl', 
                        'entDownloadUrl' => $res->getAttr('entDownloadUrl'), 
                        'personalDownloadUrl' => $res->getAttr('personalDownloadUrl'), 
                    ]
                )
            ); 
            if (
                !empty($res->getAttr('entDownloadUrl')) && 
                file_exists($res->getAttr('entDownloadUrl'))
            ) {
                $pdf[] =$res->getAttr('entDownloadUrl');
            }else{
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'entDownloadUrl  ', 
                            'entDownloadUrl ' => $res->getAttr('entDownloadUrl'),  
                            'entDownloadUrl empty' => empty($res->getAttr('entDownloadUrl')),  
                            'entDownloadUrl file_exists' => file_exists($res->getAttr('entDownloadUrl')),  
                        ]
                    )
                ); 
            }

            if (
                !empty($res->getAttr('personalDownloadUrl')) && 
                file_exists($res->getAttr('personalDownloadUrl'))
            ) {
                $pdf[] =$res->getAttr('personalDownloadUrl');
            } else{
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'personalDownloadUrl  ', 
                            'personalDownloadUrl ' => $res->getAttr('personalDownloadUrl'),  
                            'personalDownloadUrl empty' => empty($res->getAttr('personalDownloadUrl')),  
                            'personalDownloadUrl file_exists' => file_exists($res->getAttr('personalDownloadUrl')),  
                        ]
                    )
                ); 
            }
        }

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
        ZipService::getInstance()->zip($pdf, TEMP_FILE_PATH . $filename . '.zip');
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