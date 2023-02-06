<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Invoice;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\Zip\ZipService;
use wanghanwanghan\someUtils\control;

class InvoiceController extends ControllerBase
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
        $entname = $this->getRequestData('entname');
        $status = $this->getRequestData('status');
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                '$status'  => $status
//            ])
//        );
        empty($status) ?: $status = jsonDecode($status);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                '$status'  => $status
//            ])
//        );
        $orm = AntAuthList::create();

        if (!empty($entname)) {
            $orm->where('entName', "%{$entname}%", 'LIKE');
        }

        if (!empty($status)) {
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$status'  => $status
//                ])
//            );
            $orm->where('status', $status, 'IN');
        }

        $res = $orm->all();

//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'count_$res'  => count($res)
//            ])
//        );
        return $this->writeJson(200, null,$res );
    }

    function createZip(): bool
    {
        $zip_arr = $this->getRequestData('zip_arr');

        $target = [];

        foreach ($zip_arr as $one) {
            $info = AntAuthList::create()->where([
                'id' => $one['id'],
                'status' => MaYiService::STATUS_1,
            ])->get();
            if (empty($info)) {
                continue;
            }
            $target[] = $info;
        }

        $path = '';
        $pdf = [];
//        CommonService::getInstance()->log4PHP([$target,$zip_arr], 'info', 'createZip');
        if (!empty($target)) {
            $filename = control::getUuid();
            $fp = fopen(TEMP_FILE_PATH . $filename . '.csv', 'w+');
            fwrite($fp, '省份,地区,企业名称,税号,税务机关代码,主管税务机关名称' . PHP_EOL);
            foreach ($target as $one) {
                if (!empty($one->getAttr('filePath')) && file_exists(INV_AUTH_PATH . $one->getAttr('filePath'))) {
                    $pdf[] = INV_AUTH_PATH . $one->getAttr('filePath');
                }
                $insert = [];
                $insert[] = $one->getAttr('province');
                $insert[] = $one->getAttr('city');
                $insert[] = $one->getAttr('entName');
                $insert[] = $one->getAttr('socialCredit');
                $tmp = (new MaYiService())->taxAuthoritiesCode(obj2Arr($one));
                $insert[] = $tmp[0];
                $insert[] = $tmp[1];
                $one->update([
                    'regionId' => $tmp[0],
                    'sendDate' => time(),
                    'status' => MaYiService::STATUS_2,
                ]);
                fwrite($fp, implode(',', $insert) . PHP_EOL);
            }
            fclose($fp);
            $pdf[] = TEMP_FILE_PATH . $filename . '.csv';
            ZipService::getInstance()->zip($pdf, TEMP_FILE_PATH . $filename . '.zip');
            $path = $filename;
        }

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