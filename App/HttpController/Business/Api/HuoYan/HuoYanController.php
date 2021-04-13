<?php

namespace App\HttpController\Business\Api\HuoYan;

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
        $tag = $this->request()->getRequestParam('tag') ?? '物联网 硬件';
        $province = $this->request()->getRequestParam('province') ?? '北京';
        $financing = $this->request()->getRequestParam('financing') ?? 'A轮';
        $time = $this->request()->getRequestParam('time') ?? '3-4';
        $page = $this->request()->getRequestParam('page') ?? '1';

        $data = [
            'tag' => $tag,
            'province' => $province,
            'financing' => $financing,
            'time' => $time,
            'page' => $page,
        ];

        $res = (new HuoYanService())->setCheckRespFlag(true)->getData($data);

        return $this->checkResponse($res);
    }


}