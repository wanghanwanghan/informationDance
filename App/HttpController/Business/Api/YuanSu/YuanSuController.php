<?php

namespace App\HttpController\Business\Api\YuanSu;

use App\HttpController\Service\YuanSu\YuanSuService;

class YuanSuController extends YuanSuBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验元素返回值，并给客户计费
    private function checkResponse($res)
    {
        //这里还没改
        if (isset($res['PAGEINFO']) && isset($res['PAGEINFO']['TOTAL_COUNT']) && isset($res['PAGEINFO']['TOTAL_PAGE']) && isset($res['PAGEINFO']['CURRENT_PAGE'])) {
            $res['Paging'] = [
                'page' => $res['PAGEINFO']['CURRENT_PAGE'],
                'pageSize' => null,
                'total' => $res['PAGEINFO']['TOTAL_COUNT'],
                'totalPage' => $res['PAGEINFO']['TOTAL_PAGE'],
            ];

        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        $res['code'] == '000' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        isset($res['data']) ? $res['Result'] = $res['data'] : $res['Result'] = [];

        return $this->writeJson($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    //三要素
    function personCheck()
    {
        $mobile = $this->request()->getRequestParam('mobile') ?? '';
        $idCard = $this->request()->getRequestParam('idCard') ?? '';
        $name = $this->request()->getRequestParam('name') ?? '';

        if (empty($mobile) || empty($idCard) || empty($name)) return $this->writeJson(201, null, null, '参数不能是空');

        $params = ['mobile' => $mobile, 'idNo' => $idCard, 'realname' => $name];

        $res = (new YuanSuService())->getList('https://api.elecredit.com/mobile/mobileValidate', $params);

        return $this->checkResponse($res);
    }


}