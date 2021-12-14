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
        $start = $this->request()->getRequestParam('start');
        $stop = $this->request()->getRequestParam('stop');
        $radio = $this->request()->getRequestParam('radio');
        $page = $this->request()->getRequestParam('page');

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
            ->where('status', $radio - 0)
            ->page($page)->withTotalCount();

        $res = $model->all();
        $total = $model->lastQueryResult()->getTotalCount();

        $result['list'] = $res;
        $result['total'] = $total;

        return $this->writeJson(200, $result);
    }


}