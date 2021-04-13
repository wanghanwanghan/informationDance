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
        $tag = $this->request()->getRequestParam('tag') ?? '';
        $province = $this->request()->getRequestParam('province') ?? '';
        $financing = $this->request()->getRequestParam('financing') ?? '';
        $time = $this->request()->getRequestParam('time') ?? '';
        $page = $this->request()->getRequestParam('page') ?? '';

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