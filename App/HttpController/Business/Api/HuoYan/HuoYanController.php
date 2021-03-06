<?php

namespace App\HttpController\Business\Api\HuoYan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HuoYan\HuoYanService;
use wanghanwanghan\someUtils\control;

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
        $res['result'] = control::changeArrVal($res['result'], ['', null], '--', true);
        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //仿企名片
    function getData()
    {
        $com = $this->request()->getRequestParam('com') ?? '';//企业
        $keyword = $this->request()->getRequestParam('keyword') ?? '';//全局
        $tag = $this->request()->getRequestParam('tag') ?? '';
        $financing = $this->request()->getRequestParam('financing') ?? '';
        $time = $this->request()->getRequestParam('time') ?? '';
        $province = $this->request()->getRequestParam('province') ?? '';
        $page = $this->request()->getRequestParam('page') ?? '';

        $tag !== '不限' ?: $tag = '';
        $financing !== '不限' ?: $financing = '';
        $time !== '不限' ?: $time = '';
        $province !== '不限' ?: $province = '';

        switch ($time) {
            case '1-3年':
                $time = '1-3';
                break;
            case '3-5年':
                $time = '3-5';
                break;
            case '5-100年':
                $time = '5-100';
                break;
            default:
                $time = '';
        }

        $data = [
            'com' => $com,
            'keyword' => $keyword,
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