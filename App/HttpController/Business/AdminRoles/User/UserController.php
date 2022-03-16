<?php

namespace App\HttpController\Business\AdminRoles\User;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\Pay\ali\aliPayService;
use App\HttpController\Service\Pay\wx\wxPayService;
use App\HttpController\Service\User\UserService;
use Carbon\Carbon;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;
use Endroid\QrCode\QrCode;
use wanghanwanghan\someUtils\control;

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

    //用户登录
    function userLogin()
    {
        $appId = $this->request()->getRequestParam('appId') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';
        if (empty($appId) || empty($password) ) return $this->writeJson(201, null, null, '登录信息错误');
        $info = RequestUserInfo::create()->where("appId = '{$appId}' and password = '{$password}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, '账号密码错误');
        }else{
            $newToken = UserService::getInstance()->createAccessToken($info->phone, $info->password);
            $info->update(['token' => $newToken]);
            $data = [
                'token'=>$newToken,
                'username'=>$info->username,
                'money'=>$info->money,
                'roles'=>$info->roles,
                'id'=>$info->id
            ];
            return $this->writeJson(200, $data, null, '登录成功');
        }
    }
}