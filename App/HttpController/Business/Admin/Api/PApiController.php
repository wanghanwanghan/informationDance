<?php

namespace App\HttpController\Business\Admin\Api;

use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\CreateSessionHandler;
use EasySwoole\Session\Session;

class PApiController extends ApiBase
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

    function getApiList()
    {
        $res = RequestApiInfo::create()->all();

        return $this->writeJson(200,null,$res);
    }

    function addApi()
    {
        $path = $this->getRequestData('path');
        $name = $this->getRequestData('name');
        $desc = $this->getRequestData('desc');
        $source = $this->getRequestData('source');
        $price = $this->getRequestData('price');//成本价

        if (empty($path) || empty($name)) return $this->writeJson(201);
        if (empty($source) || empty($price)) return $this->writeJson(201);

        $check = RequestApiInfo::create()->where('path',$path)->get();

        if (!empty($check)) return $this->writeJson(201);

        RequestApiInfo::create()->data([
            'path' => $path,
            'name' => $name,
            'desc' => $desc,
            'source' => $source,
            'price' => $price,
        ])->save();

        return $this->writeJson(200);
    }

    function editApi()
    {
        $aid = $this->getRequestData('aid');
        $path = $this->getRequestData('path');
        $name = $this->getRequestData('name');
        $desc = $this->getRequestData('desc');
        $price = $this->getRequestData('price');
        $status = $this->getRequestData('status');
        $apiDoc = $this->getRequestData('apiDoc');

        $info = RequestApiInfo::create()->where('id',$aid)->get();

        $update = [];

        empty($path) ?: $update['path'] = $path;
        empty($name) ?: $update['name'] = $name;
        empty($desc) ?: $update['desc'] = $desc;
        empty($price) ?: $update['price'] = sprintf('%3.f',$price);
        $status === '启用' ? $update['status'] = 1 : $update['status'] = 0;
        empty($apiDoc) ?: $update['apiDoc'] = $apiDoc;

        $info->update($update);

        return $this->writeJson();
    }




}