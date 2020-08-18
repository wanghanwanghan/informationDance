<?php

namespace App\HttpController\Business\Api\Common;

use App\HttpController\Service\Common\CommonService;

class CommonController extends CommonBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function imageUpload()
    {
        $type=$this->request()->getRequestParam('type') ?? 'Avatar';
        $phone=$this->request()->getRequestParam('phone');
        $imageFile=$this->request()->getUploadedFile('image');

        //返回文件路径
        return $this->writeJson(200,CommonService::getInstance()->storeImage($imageFile,$type));
    }

}