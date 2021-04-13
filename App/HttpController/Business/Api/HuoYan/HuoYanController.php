<?php

namespace App\HttpController\Business\Api\HuoYan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HuoYan\HuoYanService;

class HuoYanController extends HuoYanBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    private function checkResponse($res)
    {
        return $this->writeJson($res);
    }

    //仿企名片
    function getData()
    {
        $tag = $this->request()->getRequestParam('tag') ?? '';
        $financing = $this->request()->getRequestParam('financing') ?? '';
        $time = $this->request()->getRequestParam('time') ?? '';
        $province = $this->request()->getRequestParam('province') ?? '';
        $page = $this->request()->getRequestParam('page') ?? '';

        CommonService::getInstance()->log4PHP([
            $tag, $financing, $time, $province, $page
        ]);

        $tag !== '不限' ?: $tag = '';
        $financing !== '不限' ?: $financing = '';
        $time !== '不限' ?: $time = '';
        $province !== '不限' ?: $province = '';

        if (!empty($time)) {
            $time = date('Y') - $time;
        }

        $data = [
            'tag' => $tag,
            'province' => $province,
            'financing' => $financing,
            'time' => $time . '',
            'page' => $page . '',
        ];

        $res = (new HuoYanService())->setCheckRespFlag(true)->getData($data);

        return $this->checkResponse($res);
    }


}