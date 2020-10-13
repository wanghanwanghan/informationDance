<?php

namespace App\HttpController\Business\Api\Common;

use App\HttpController\Service\Common\CommonService;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

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

    //创建图片验证码
    function imageVerifyCode()
    {
        //随机生成code后，存到redis，等着验证，还没做存到redis
        $code = $this->request()->getRequestParam('code') ?? control::getUuid(4);
        $type = $this->request()->getRequestParam('type') ?? 'image';

        $redis = Redis::defer('redis');

        $redis->select(14);

        $redis->sAdd('imageVerifyCode', strtolower($code));

        $redis->expire('imageVerifyCode', 30);

        return CommonService::getInstance()->createVerifyCode($this->response(), $code, $type);
    }

    //创建手机短信验证码
    function smsVerifyCode()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';

        if (empty($phone) || empty($type)) return $this->writeJson(201, null, null, '手机号或类别不能是空');

        return $this->writeJson(200, null, null, CommonService::getInstance()->sendCode((string)$phone, $type));
    }


}