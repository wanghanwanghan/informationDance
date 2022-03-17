<?php

namespace App\HttpController\Business\AdminRoles\User;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
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

    /**
     * 用户登录
    */
    function userLogin()
    {
        $appId = $this->getRequestData('username') ?? '';
        $password = $this->getRequestData('password') ?? '';
        if (empty($appId) || empty($password) ) return $this->writeJson(201, null, null, '登录信息错误');
        $info = RequestUserInfo::create()->where("appId = '{$appId}' and password = '{$password}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, '账号密码错误');
        }else{
            $newToken = UserService::getInstance()->createAccessToken($info->appId, $info->password);
            $info->update(['token' => $newToken]);
            $data = [
                'token'=>$newToken,
                'username'=>$info->username,
                'money'=>$info->money,
                'roles'=>$info->roles,
                'id'=>$info->id
            ];
            return $this->writeJson(200, '',$data, '登录成功');
        }
    }

    /**
     * 根据token 获取用户明细
     */
    function getInfoByToken(){
        $token = $this->getRequestData('token') ?? '';
        if (empty($token)) return $this->writeJson(201, null, null, 'token不可以为空');
        $info = RequestUserInfo::create()->where("token = '{$token}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, 'token不存在');
        }
        $data = [
            'username'=>$info->username,
            'money'=>$info->money,
            'roles'=>$info->roles,
            'id'=>$info->id
        ];
        return $this->writeJson(200, '',$data, '成功');
    }

    /**
     * 根据用户获取用户的接口明细
     */
    function getApiListByUser(){
        $appId = $this->getRequestData('username') ?? '';
        $token = $this->getRequestData('token') ?? '';
        dingAlarmSimple(['$appId'=>$appId,'$token'=>$token]);
        if (empty($token) || empty($appId)) return $this->writeJson(201, null, null, '参数不可以为空');
        $info = RequestUserInfo::create()->where("token = '{$token}' and appId = '{$appId}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, '用户未登录');
        }
        $shipList = RequestUserApiRelationship::create()->where(" userId = {$info->id}")->all();
        $data = [];
        foreach ($shipList as $item) {
            $apiId = $item->getAttr('apiId');
            $apiInfo = RequestApiInfo::create()->where("id={$apiId}")->get();
            $data[$apiId] = [
                'path' => $apiInfo->path,
                'name' => $apiInfo->name,
                'desc' => $apiInfo->desc,
                'source' => $apiInfo->source,
                'price' => $apiInfo->price,
                'status' => $apiInfo->status,
                'apiDoc' => $apiInfo->apiDoc,
                'created_at' => $apiInfo->created_at,
                'updated_at' => $apiInfo->updated_at,
            ];
        }
        return $this->writeJson(200, '',$data, '成功');
    }

    private function checkUserIsLogin(){
        if (empty($token) || empty($appId)) return $this->writeJson(201, null, null, '参数不可以为空');
        $info = RequestUserInfo::create()->where("token = '{$token}' and appId = '{$appId}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, '用户未登录');
        }
    }
}