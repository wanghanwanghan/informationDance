<?php

namespace App\HttpController\Business\AdminRoles\User;

use App\HttpController\Models\AdminNew\AdminNewApi;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Models\Provide\RequestUserInfoLog;
use App\HttpController\Models\Provide\RoleInfo;
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
        if(!$this->checkRouter()){
            $appId = $this->getRequestData('username') ?? '';
            $token = $this->getRequestData('token') ?? '';
//            dingAlarmSimple(['$appId'=>$appId,'$token'=>$token]);
            if (empty($token) || empty($appId)) return $this->writeJson(201, null, null, '参数不可以为空');
            $info = RequestUserInfo::create()->where("token = '{$token}' and appId = '{$appId}'")->get();
            if (empty($info)) {
                return $this->writeJson(201, null, null, '用户未登录');
            }
        }
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //check router
    private function checkRouter(): bool
    {
        //直接放行的url，只判断url最后两个在不在数组中
        $pass = CreateConf::getInstance()->getConf('env.passRouter');
        $path = $this->request()->getSwooleRequest()->server['path_info'];
        $path = rtrim($path, '/');
        $path = explode('/', $path);
        if (!empty($path)) {
            //检查url在不在直接放行数组
            $len = count($path);
            //取最后两个
            $path = implode('/', [$path[$len - 2], $path[$len - 1]]);
            //在数组里就放行
            if (in_array($path, $pass)) return true;
        }
        return false;
    }


    /**
     * 用户登录
     */
    function userLogin()
    {
        $appId = $this->getRequestData('username') ?? '';
        $password = $this->getRequestData('password') ?? '';
        if (empty($appId) || empty($password)) return $this->writeJson(201, null, null, '登录信息错误');
        $info = RequestUserInfo::create()->where("appId = '{$appId}' and password = '{$password}'")->get();
        if (empty($info)) {
            return $this->writeJson(201, null, null, '账号密码错误');
        } else {
            $newToken = UserService::getInstance()->createAccessToken($info->appId, $info->password);
            $info->update(['token' => $newToken]);
            $data = [
                'token' => $newToken,
                'username' => $info->username,
                'money' => $info->money,
                'roles' => $info->roles,
                'id' => $info->id
            ];
            return $this->writeJson(200, '', $data, '登录成功');
        }
    }

    /**
     * 根据token 获取用户明细
     */
    function getInfoByToken()
    {
        $token = $this->getRequestData('token') ?? '';
        if (empty($token)) return $this->writeJson(201, null, null, 'token不可以为空');
        $info = RequestUserInfo::create()->where("token = '{$token}'")->get();
        if (empty($info)) {
            return $this->writeJson(201, null, null, 'token不存在');
        }
        $data = [
            'username' => $info->username,
            'money' => $info->money,
            'roles' => $info->roles,
            'id' => $info->id
        ];
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 根据用户获取用户的接口明细
     */
    function getApiListByUser()
    {
        $appId = $this->getRequestData('appId') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
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
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 修改接口详情
     */
    function editApi()
    {
        $aid = $this->getRequestData('aid');
        $path = $this->getRequestData('path');
        $name = $this->getRequestData('name');
        $desc = $this->getRequestData('desc');
        $price = $this->getRequestData('price');
        $status = $this->getRequestData('status');
        $apiDoc = $this->getRequestData('apiDoc');
        $sort_num = $this->getRequestData('sort_num');
        $source = $this->getRequestData('source');

        $info = RequestApiInfo::create()->where('id', $aid)->get();
        $infoAdmin = AdminNewApi::create()->where('path', $path)->get();

        $update = [];
        $updateAdmin = [];
        empty($sort_num) ?: $updateAdmin['sort_num'] = $sort_num;
        empty($path) ?: $update['path'] = $path;
        $updateAdmin['path'] = $path;
        empty($name) ?: $update['name'] = $name;
        $updateAdmin['name'] = $name;
        empty($desc) ?: $update['desc'] = $desc;
        $updateAdmin['desc'] = $desc;
        empty($source) ?: $update['source'] = $source;
        $updateAdmin['source'] = $source;
        empty($price) ?: $update['price'] = sprintf('%3.f', $price);
        $status === '启用' ? $update['status'] = 1 : $update['status'] = 0;
        empty($apiDoc) ?: $update['apiDoc'] = $apiDoc;
        if (empty($infoAdmin)) {
            AdminNewApi::create()->data([
                'path' => $path,
                'api_name' => $name,
                'desc' => $desc,
                'source' => $source,
                'price' => $price,
                'sort_num' => $sort_num,
            ])->save();
        } else {
            $infoAdmin->update($updateAdmin);
        }
        $info->update($update);

        return $this->writeJson();
    }

    /**
     * 修改user和api的关系
     */
    function editUserApi()
    {
        $uid = $this->getRequestData('uid');
        $apiInfo = $this->getRequestData('apiInfo');
        if (empty($uid)) return $this->writeJson(201);
        //先将这个用户的所有接口改为不可用
        RequestUserApiRelationship::create()->where('userId', $uid)->update([
            'status' => 0
        ]);
        //再将可用的接口改为可用
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

    /**
     * 获取用户列表
     */
    public function getUserList()
    {
        $resList = RequestUserInfo::create()->all();
        $data = [];
        foreach ($resList as $item) {
            $data[] = [
                'id'=>$item->getAttr('id'),
                'username'=>$item->getAttr('username'),
                'appId'=>$item->getAttr('appId'),
                'appSecret'=>$item->getAttr('appSecret'),
                'rsaPub'=>$item->getAttr('rsaPub'),
                'rsaPri'=>$item->getAttr('rsaPri'),
                'allowIp'=>$item->getAttr('allowIp'),
                'money'=>$item->getAttr('money'),
                'status'=>$item->getAttr('status'),
                'roles'=>$item->getAttr('roles'),
            ];
        }
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 根据appId获取用户信息
     */
    public function getUserInfoByAppId(){
        $appId = $this->getRequestData('username') ?? '';
        if (empty($appId)) return $this->writeJson(201, null, null, 'username不可以为空');
        $info = RequestUserInfo::create()->where("appId = '{$appId}'")->get();
        if (empty($info)) {
            return $this->writeJson(201, null, null, 'token不存在');
        }
        $data = [
            'username' => $info->username,
            'money' => $info->money,
            'roles' => $info->roles,
            'id' => $info->id,
            'appId' => $info->appId,
            'appSecret' => $info->appSecret,
            'rsaPub' => $info->rsaPub,
            'rsaPri' => $info->rsaPri,
            'allowIp' => $info->allowIp,
            'status' => $info->status,
        ];
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 修改角色
     */
    public function editRole(){
        $id = $this->getRequestData('roleId') ?? '';
        $name = $this->getRequestData('roleName') ?? '';
        $status = $this->getRequestData('status') ?? '';
        $info = RoleInfo::create()->where("id = '{$id}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, $name.'不存在');
        }
        $info->update([
            'status' => $status
        ]);
        return $this->writeJson();
    }

    /**
     * 添加用户,修改用户信息
     */
    function addUser()
    {
        $actionType = $this->getRequestData('actionType');
        $username = $this->getRequestData('username');
        $money = $this->getRequestData('money');
        $roles = $this->getRequestData('roles');

        if (empty($username) || empty($money)) return $this->writeJson(201);

        $check = RequestUserInfo::create()->where('username', $username)->get();

        if ($actionType === 'update') {
            if (empty($check)) return $this->writeJson(201);
            $check->update([
                'username' => $username,
                'money' => $money + $check->getAttr('money'),
                'roles' => $roles,
            ]);
            RequestUserInfoLog::create()->addOne($username,$money);
        } else {
            if (!empty($check)) return $this->writeJson(201);
            $appId = strtoupper(control::getUuid());
            $appSecret = substr(strtoupper(control::getUuid()), 5, 20);
            RequestUserInfo::create()->data([
                'username' => $username,
                'appId' => $appId,
                'appSecret' => $appSecret,
                'money' => $money,
                'roles' => $roles
            ])->save();
            RequestUserInfoLog::create()->addOne($username,$money);
        }

        return $this->writeJson(200);
    }
}