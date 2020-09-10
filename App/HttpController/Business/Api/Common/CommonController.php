<?php

namespace App\HttpController\Business\Api\Common;

use App\HttpController\Service\Common\CommonService;
use EasySwoole\Pay\AliPay\Config as aliConfig;
use EasySwoole\Pay\WeChat\Config as wxConfig;

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

    //图片上传
    function imageUpload()
    {
        $type = $this->request()->getRequestParam('type') ?? 'Avatar';
        $phone = $this->request()->getRequestParam('phone');
        $imageFile = $this->request()->getUploadedFile('image');

        //返回文件路径
        return $this->writeJson(200, null, CommonService::getInstance()->storeImage($imageFile, $type));
    }

    //创建验证码
    function createVerifyCode()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 'img';

        return CommonService::getInstance()->createVerifyCode($this->response(), $code, $type);
    }

    //微信支付
    function wxPay()
    {

    }

    //支付宝支付
    function aliPay()
    {

    }

}