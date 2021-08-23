<?php

namespace App\HttpController\Business\Admin\Invoice;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\Zip\ZipService;
use wanghanwanghan\someUtils\control;

class InvoiceController extends InvoiceBase
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
        $res = AntAuthList::create()->all();

        return $this->writeJson(200, null, $res);
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
                fwrite($fp, implode(',', $insert) . PHP_EOL);
            }
            fclose($fp);
            $pdf[] = TEMP_FILE_PATH . $filename . '.csv';
            ZipService::getInstance()->zip($pdf, TEMP_FILE_PATH . $filename . '.zip');
            $path = $filename;
        }

        return $this->writeJson(200, null, $path);
    }


}