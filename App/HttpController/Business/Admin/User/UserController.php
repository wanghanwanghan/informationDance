<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\User;

class UserController extends UserBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //用户列表
    function userList()
    {
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('page') ?? 10;

        try
        {
            $list = User::create()->alias('t1')
                ->join('information_dance_wallet as t2','t2.phone = t1.phone')
                ->limit($this->exprOffset($page,$pageSize),$pageSize)
                ->all();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        empty($list) ? $res = null : $res = obj2Arr($list);

        return $this->writeJson(200,null,$res,null);
    }

    //用户位置
    function userLocation()
    {
        try
        {
            $list = LngLat::create()->all();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        empty($list) ? $res = null : $res = obj2Arr($list);

        return $this->writeJson(200,null,$res,null);
    }






}