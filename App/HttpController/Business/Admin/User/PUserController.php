<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateSessionHandler;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\Session\Session;
use wanghanwanghan\someUtils\control;

class PUserController extends UserBase
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        CreateSessionHandler::getInstance()->check($this->request(), $this->response());

        $isLogin = Session::getInstance()->get('isLogin');

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getUserList()
    {
        $userInfo = RequestUserInfo::create()->order('created_at', 'desc')->all();

        return $this->writeJson(200, null, $userInfo);
    }

    //添加用户
    function addUser()
    {
        $actionType = $this->getRequestData('actionType');
        $user_id = $this->getRequestData('user_id');
        $username = $this->getRequestData('username');
        $money = $this->getRequestData('money');

        if (empty($username) || empty($money)) return $this->writeJson(201);

        $check = RequestUserInfo::create()->where('username', $username)->get();

        if ($actionType === 'update') {
            if (empty($check)) return $this->writeJson(201);
            $check->update([
                'username' => $username,
                'money' => $money,
            ]);
        } else {
            if (!empty($check)) return $this->writeJson(201);
            $appId = strtoupper(control::getUuid());
            $appSecret = substr(strtoupper(control::getUuid()), 5, 20);
            RequestUserInfo::create()->data([
                'username' => $username,
                'appId' => $appId,
                'appSecret' => $appSecret,
                'money' => $money,
            ])->save();
        }

        return $this->writeJson(200);
    }

    //编辑用户
    function editUser()
    {
        $id = $this->getRequestData('id');
        $money = $this->getRequestData('money');

        if (empty($id) || empty($money)) return $this->writeJson(201);

        $userInfo = RequestUserInfo::create()->where('id', $id)->get();

        if (empty($userInfo)) return $this->writeJson(201);

        $userInfo->update([
            'money' => QueryBuilder::inc($money)
        ]);

        return $this->writeJson(200);
    }

    //用户都有哪些api
    function getUserApi()
    {
        $id = $this->getRequestData('id');

        $res = RequestUserApiRelationship::create()->alias('t1')
            ->join('information_dance_request_api_info as t2', 't1.apiId = t2.id', 'left')
            ->field([
                't1.apiId',
                't1.price AS custPrice',
                't2.*',
            ])->where('t1.userId', $id)->where('t1.status', 1)->all();

        return $this->writeJson(200, null, $res);
    }

    //修改user和api的关系
    function editUserApi()
    {
        $uid = $this->getRequestData('uid');
        $apiInfo = $this->getRequestData('apiInfo');

        if (empty($uid)) return $this->writeJson(201);

        RequestUserApiRelationship::create()->where('userId', $uid)->update([
            'status' => 0
        ]);

        foreach ($apiInfo as $one) {
            $check = RequestUserApiRelationship::create()->where('userId', $uid)->where('apiId', $one['id'])->get();

            if (empty($check)) {
                RequestUserApiRelationship::create()->data([
                    'userId' => $uid,
                    'apiId' => $one['id'],
                    'price' => $one['price'] + 0.2,
                ])->save();

            } else {
                $check->update([
                    'status' => 1
                ]);
            }
        }

        return $this->writeJson();
    }

    //修改用户使用接口价格
    function editUserApiPrice()
    {
        $uid = $this->getRequestData('uid');
        $aid = $this->getRequestData('aid');
        $price = $this->getRequestData('price');

        RequestUserApiRelationship::create()->where([
            'userId' => $uid,
            'apiId' => $aid,
        ])->update([
            'price' => sprintf('%3.f', $price)
        ]);

        return $this->writeJson();
    }

    //创建用户rsa密钥
    function addRsaKey()
    {
        $uid = $this->getRequestData('user_id');

        $userInfo = RequestUserInfo::create()->get($uid);

        if (!empty($userInfo->getAttr('rsaPub'))) {
            return $this->writeJson(201);
        }

        //createRsa
        $rsaInfo = control::createRsa(RSA_KEY_PATH, [
            'config' => '/etc/pki/tls/openssl.cnf',
        ]);

        if (is_array($rsaInfo)) {
            $userInfo->update([
                'rsaPub' => $rsaInfo['pub'],
                'rsaPri' => $rsaInfo['pri'],
            ]);
        }

        return is_array($rsaInfo) ? $this->writeJson(200) : $this->writeJson(201);
    }


}