<?php

namespace App\HttpController\Business\AdminNew\Mrxd\Api;

use App\HttpController\Business\AdminNew\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewApi;
use App\HttpController\Models\Provide\RequestApiInfo;

class ApiController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //可以开通的接口列表
    //列表的增删改
    function addInterface(){
        $form = $this->request()->getRequestParam();
        $path = $form['path'];
        $name = $form['name'];
        $desc = $form['desc'];
        $source = $form['source'];
        $price = $form['price'];
        $apiDoc = $form['apiDoc'];
        $sort_num = $form['sort_num'];

        if (empty($path) || empty($name)) return $this->writeJson(201);
        if (empty($source) || empty($price)) return $this->writeJson(201);

        $checkAdmin = AdminNewApi::create()->where('path',$path)->get();
        $check = RequestApiInfo::create()->where('path',$path)->get();
        if (!empty($check) && !empty($checkAdmin)) return $this->writeJson(201);

        if(empty($check)){
            RequestApiInfo::create()->data([
                'path' => $path,
                'name' => $name,
                'desc' => $desc,
                'source' => $source,
                'price' => $price,
                'apiDoc' => $apiDoc,
            ])->save();
        }
        if(empty($checkAdmin)){
            AdminNewApi::create()->data([
                'path' => $path,
                'api_name' => $name,
                'desc' => $desc,
                'source' => $source,
                'price' => $price,
                'sort_num' => $sort_num,
            ])->save();
        }
        return $this->writeJson(200);
    }
    function getInterfaceList(){
        $res = AdminNewApi::create()->all();

        return $this->writeJson(200,null,$res);
    }



}