<?php

namespace App\HttpController\Business\Api\XinDong;

use App\HttpController\Models\Api\User;

class XinDongKeDongController extends XinDongBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    private function checkResponse($res): bool
    {
        return $this->writeJson($res['code'] - 0, $res['paging'], $res['result'], $res['msg'] ?? null);
    }

    //上传用户的客户列表
    function uploadEntList(): bool
    {
        $phone = $this->request()->getRequestParam('phone');//拿uid用
        $entName = $this->request()->getRequestParam('entName_json');//企业名称字符串
        $file = $this->request()->getRequestParam('file');//上传的文件路径

        //在这里处理出4个搜索条件 营收 行业 年限 地域

        $uid = (User::create()->where('phone', $phone)->get())->getAttr('id');


        return $this->checkResponse($res);
    }


}
