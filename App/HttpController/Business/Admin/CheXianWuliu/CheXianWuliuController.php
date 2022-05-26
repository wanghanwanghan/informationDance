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

        if (!empty($status)) {
            $orm->where('status', $status, 'IN');
        }

        $res = $orm->limit(3)->all();
        foreach($res as &$dataItem){
            $dataItem['entName'] = Company::create()->where(
                [
                    'id' => $dataItem['entId']
                ]
            );
            $dataItem['status_cname'] = $dataItem['status'];
        }
        return $this->writeJson(200, null, $res);
    }

    function createZip(): bool
    {
        $zip_arr = $this->getRequestData('zip_arr');
        if(empty($zip_arr)){
            return $this->writeJson(205, null, null);
        }
        $pdf = [];
        $filename = control::getUuid();
        foreach ($zip_arr as $one) {
            $info = DianZiQianAuth::create()->where([
                'id' => $one['id'],
                'status' => MaYiService::STATUS_1,
            ])->get();
            if (empty($info)) {
                continue;
            }
            if (
                !empty($info->getAttr('entDownloadUrl')) && 
                file_exists(INV_AUTH_PATH . $info->getAttr('entDownloadUrl'))
            ) {
                $pdf[] =$info->getAttr('entDownloadUrl');
            }

            if (
                !empty($info->getAttr('entViewPdfUrl')) && 
                file_exists(INV_AUTH_PATH . $info->getAttr('entViewPdfUrl'))
            ) {
                $pdf[] =$info->getAttr('entViewPdfUrl');
            } 
        }
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