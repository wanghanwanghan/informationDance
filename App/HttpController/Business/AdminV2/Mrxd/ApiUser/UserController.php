<?php

namespace App\HttpController\Business\AdminV2\Mrxd\ApiUser;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewApi;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Models\Provide\RoleInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\User\UserService;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;
use App\HttpController\Models\AdminV2\AdminUserRole;

class UserController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {   
        // $this->setChckToken(true);
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
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
                'price' => $item->price,
                'status' => $apiInfo->status,
                'apiDoc' => $apiInfo->apiDoc,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'billing_plan' => $item->billing_plan,
                'cache_day' => $item->cache_day,
            ];
        }
        return $this->writeJson(200, '', array_values($data), '成功');
    }

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

    /**
     * 获取用户列表
     */
    public function getUserList()
    {
        $resList = RequestUserInfo::create()->all();
        $data = [];
        foreach ($resList as $item) {
            $data[] = [
                'id' => $item->getAttr('id'),
                'username' => $item->getAttr('username'),
                'appId' => $item->getAttr('appId'),
                'appSecret' => $item->getAttr('appSecret'),
                'rsaPub' => $item->getAttr('rsaPub'),
                'rsaPri' => $item->getAttr('rsaPri'),
                'allowIp' => $item->getAttr('allowIp'),
                'money' => $item->getAttr('money'),
                'status' => $item->getAttr('status'),
                'roles' => $item->getAttr('roles'),
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

        return  $this->writeJson(200, null, []);
    }

    /**
     * 修改user和api的关系
     */
    function editUserApi()
    {
        //return $this->writeJson(200, null, []);

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
                    'billing_plan' => $one['billing_plan'],
                    'cache_day' => $one['cache_day'],
                    'kidTypes' => $one['kidTypes'],
                    'year_price_detail' => $one['year_price_detail']
                ])->save();
            } else {
                $check->update([
                    'status' => 1
                ]);
            }
        }
        return $this->writeJson(200, null, []);
    }

}