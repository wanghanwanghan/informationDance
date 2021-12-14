<?php

namespace App\HttpController\Business\Admin\SaibopengkeAdmin;

use App\HttpController\Index;
use App\HttpController\Models\Admin\SaibopengkeAdmin\Saibopengke_Data_List_Model;

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

        $model = Saibopengke_Data_List_Model::create()
            ->where('handleDate', [$start - 0, $stop - 0], 'BETWEEN')
            ->page($page)->withTotalCount();

        if ($radio !== 1) {
            $model->where('status', $radio - 0);
        }

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

        $type === 'success' ? $type = 1 : $type = 3;

        try {
            Saibopengke_Data_List_Model::create()->get($id)->update([
                'status' => $type
            ]);
            return $this->writeJson();
        } catch (\Throwable $e) {
            return $this->writeJson(201);
        }
    }


}