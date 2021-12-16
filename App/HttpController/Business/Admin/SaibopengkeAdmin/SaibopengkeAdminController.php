<?php

namespace App\HttpController\Business\Admin\SaibopengkeAdmin;

use App\HttpController\Index;
use App\HttpController\Models\Admin\SaibopengkeAdmin\Saibopengke_Data_List_Model;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\Zip\ZipService;
use Carbon\Carbon;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\ORM\DbManager;
use wanghanwanghan\someUtils\control;

class SaibopengkeAdminController extends Index
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getDataList(): bool
    {
        $string = $this->request()->getBody()->__toString();
        $raw = jsonDecode($string);

        $start = $raw['start'] ?? '';
        $stop = $raw['stop'] ?? '';
        $radio = $raw['radio'] ?? '';
        $page = $raw['page'] ?? '';

        if (!is_numeric($start) || !is_numeric($stop)) {
            return $this->writeJson(201, null, '日期不能是空');
        }

        if (!is_numeric($radio)) {
            return $this->writeJson(201, null, '状态不能是空');
        }

        if (!is_numeric($page)) {
            return $this->writeJson(201, null, '页码不能是空');
        }

        $model = Saibopengke_Data_List_Model::create();

        if ($radio !== 1) {
            $model->where('status', $radio - 0);
        }

        $start = substr($start, 0, 10) - 0;
        $stop = substr($stop, 0, 10) - 0;

        $model->where('created_at', [$start, $stop], 'BETWEEN')
            ->page($page)->withTotalCount();

        $res = $model->all();
        $total = $model->lastQueryResult()->getTotalCount();

        $result['list'] = $res;
        $result['total'] = $total;

        return $this->writeJson(200, $result);
    }

    function statusChange(): bool
    {
        $string = $this->request()->getBody()->__toString();
        $raw = jsonDecode($string);

        $id = $raw['id'] ?? '';
        $type = $raw['type'] ?? '';

        if (!is_numeric($id) || $id <= 0) {
            return $this->writeJson(201, null, 'id错误');
        }

        if (!in_array($type, ['success', 'close'], true)) {
            return $this->writeJson(201, null, '类型错误');
        }

        $type === 'success' ? $type = 2 : $type = 4;

        try {
            Saibopengke_Data_List_Model::create()->get($id)->update([
                'status' => $type
            ]);
            return $this->writeJson();
        } catch (\Throwable $e) {
            return $this->writeJson(201);
        }
    }

    function getExportZip(): bool
    {
        $string = $this->request()->getBody()->__toString();
        $raw = jsonDecode($string);

        $start = $raw['start'] ?? '';
        $stop = $raw['stop'] ?? '';
        $radio = $raw['radio'] ?? '';
        $page = $raw['page'] ?? '';

        if (!is_numeric($start) || !is_numeric($stop)) {
            return $this->writeJson(201, null, '日期不能是空');
        }

        if (!is_numeric($radio)) {
            return $this->writeJson(201, null, '状态不能是空');
        }

        if (!is_numeric($page)) {
            return $this->writeJson(201, null, '页码不能是空');
        }

        $model = Saibopengke_Data_List_Model::create();

        if ($radio !== 1) {
            $model->where('status', $radio - 0);
        }

        $start = substr($start, 0, 10) - 0;
        $stop = substr($stop, 0, 10) - 0;

        $result = $model->where('created_at', [$start, $stop], 'BETWEEN')
            ->field(['handleDate', 'filename', 'descname'])
            ->group('handleDate,filename,descname')->all();

        $file_arr = [];

        foreach ($result as $one) {
            //拼路径
            $y = substr($one->handleDate, 0, 4);
            $m = substr($one->handleDate, 4, 2);
            $d = substr($one->handleDate, 6, 2);
            $path = ROOT_PATH . "/TempWork/SaiMengHuiZhi/Work/{$y}{$m}/day{$d}/";
            $filename = $path . $one->filename;
            if (!in_array($filename, $file_arr, true)) {
                $file_arr[] = $filename;
            }
            $descname = $path . $one->descname;
            if (!in_array($descname, $file_arr, true)) {
                $file_arr[] = $descname;
            }
        }

        $zip = TEMP_FILE_PATH . control::getUuid() . '.zip';
        empty($file_arr) ?
            $result = [] :
            $result = str_replace(ROOT_PATH, '', ZipService::getInstance()->zip($file_arr, $zip));

        return $this->writeJson(200, $result);
    }

    function uploadEntList(): bool
    {
        $files = $this->request()->getUploadedFiles();

        $y = Carbon::now()->format('Y');
        $m = Carbon::now()->format('m');
        $d = Carbon::now()->format('d');

        $path = ROOT_PATH . "/TempWork/SaiMengHuiZhi/Work/{$y}{$m}/day{$d}/";

        foreach ($files as $key => $oneFile) {
            if ($oneFile instanceof UploadFile) {
                try {
                    $oneFile->moveTo($path . $oneFile->getClientFilename());
                } catch (\Throwable $e) {
                    return $this->writeJson(202);
                }
            }
        }

        return $this->writeJson(200);
    }

}