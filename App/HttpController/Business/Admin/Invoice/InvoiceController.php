<?php

namespace App\HttpController\Business\Admin\Invoice;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntAuthSealDetail;
use App\HttpController\Models\Api\DianZiQianAuth;
use App\HttpController\Models\Api\JinCaiRwh;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\Zip\ZipService;
use App\Task\Service\TaskService;
use Carbon\Carbon;
use updc\utils\CommonUtil;
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
        $entname = $this->getRequestData('entname');
        $status = $this->getRequestData('status');
        empty($status) ?: $status = jsonDecode($status);

        $orm = AntAuthList::create();

        if (!empty($entname)) {
            $orm->where('entName', "%{$entname}%", 'LIKE');
        }

        if (!empty($status)) {
            $orm->where('status', $status, 'IN');
        }

        return $this->writeJson(200, null, $orm->all());
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
        CommonService::getInstance()->log4PHP([$target,$zip_arr], 'info', 'createZip');
        if (!empty($target)) {
            $filename = control::getUuid();
            $fp = fopen(TEMP_FILE_PATH . $filename . '.csv', 'w+');
            fwrite($fp, '省份,地区,企业名称,税号,税务机关代码,主管税务机关名称' . PHP_EOL);
            foreach ($target as $one) {
                if (!empty($one->getAttr('filePath')) && file_exists(INV_AUTH_PATH . $one->getAttr('filePath'))) {
                    $pdf[] = INV_AUTH_PATH . $one->getAttr('filePath');
                } else {
                    //从另一张表里找
                    $info = AntAuthSealDetail::create()
                        ->where('antAuthId', $one->getAttr('id'))
                        ->where('type', 2)
                        ->where('fileUrl', '', '<>')
                        ->order('created_at', 'desc')
                        ->all();
                    if (!empty($info)) {
                        foreach ($info as $one_auth) {
                            if (!empty($one_auth->getAttr('fileUrl')) && file_exists(INV_AUTH_PATH . $one_auth->getAttr('fileUrl'))) {
                                $pdf[] = INV_AUTH_PATH . $one_auth->getAttr('fileUrl');
                                break;
                            } else {
                                // 不需要盖章的情况
                                if ($one_auth->getAttr('isSeal') === 'false' && !empty($one_auth->getAttr('fileAddress'))) {
                                    $new_file_name = control::getUuid() . '.pdf';
                                    $path = Carbon::now()->format('Ym');
                                    file_put_contents(INV_AUTH_PATH . $path . DIRECTORY_SEPARATOR . $new_file_name, file_get_contents($one_auth->getAttr('fileAddress')));
                                    $pdf[] = INV_AUTH_PATH . $path . DIRECTORY_SEPARATOR . $new_file_name;
                                    break;
                                }
                            }
                        }
                    }
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