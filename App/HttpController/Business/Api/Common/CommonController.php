<?php

namespace App\HttpController\Business\Api\Common;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\Charge;
use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateTable\CreateTableService;
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
        $iCode = $this->request()->getRequestParam('iCode') ?? control::getUuid(4);
        $type = $this->request()->getRequestParam('type') ?? 'image';

        $redis = Redis::defer('redis');
        $redis->select(14);
        $redis->sAdd('imageVerifyCode', strtolower($iCode));
        $redis->expire('imageVerifyCode', 60);

        return CommonService::getInstance()->createVerifyCode($this->response(), $iCode, $type);
    }

    //创建手机短信验证码
    function smsVerifyCode()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $iCode = $this->request()->getRequestParam('iCode') ?? '';

        if (empty($phone) || empty($type)) return $this->writeJson(201, null, null, '手机号或类别不能是空');

        //验证图片验证码
        $redis = Redis::defer('redis');
        $redis->select(14);
        $check = $redis->sIsMember('imageVerifyCode', strtolower($iCode));

        // if ($check)

        return $this->writeJson(200, null, null, CommonService::getInstance()->sendCode((string)$phone, $type));
    }

    //上传用户地理位置
    function userLngLatUpload()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $lng = $this->request()->getRequestParam('lng') ?? '';//经度
        $lat = $this->request()->getRequestParam('lat') ?? '';//纬度

        $lng = sprintf('%.8f',trim($lng));
        $lat = sprintf('%.8f',trim($lat));

        try
        {
            $info = LngLat::create()->where('target',$phone)->get();

            if (empty($info))
            {
                LngLat::create()->data(['target'=>$phone,'lng'=>$lng,'lat'=>$lat])->save();
            }else
            {
                $info->update(['lng'=>$lng,'lat'=>$lat]);
            }

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        return $this->writeJson(200,null,null,'上传成功');
    }

    //退钱到钱包
    function refundToWallet()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $moduleNum = $this->request()->getRequestParam('moduleNum') ?? '';
        $msg = '退款成功';

        if (empty($phone) || !is_numeric($phone)) return $this->writeJson(201,null,null,'手机号错误');
        if (empty($entName)) return $this->writeJson(201,null,null,'企业名称错误');
        if (empty($moduleNum) || !is_numeric($moduleNum)) return $this->writeJson(201,null,null,'扣费模块错误');

        try
        {
            $info = Charge::create()
                ->where('phone',$phone)
                ->where('entName',$entName)
                ->where('moduleId',$moduleNum)
                ->where('created_at',time() - 30,'>')//首先要看这个人在30秒之前有没有真的消费并扣钱
                ->where('price',0,'>')//是否有被扣费
                ->get();

            if (empty($info)) return $this->writeJson(201,null,null,'未找到订单');

            $addPrice = $info->price;

            //修改订单金额
            $info->update(['price'=>0]);

            //把扣的钱返回
            $userWalletInfo = Wallet::create()->where('phone',$phone)->get();

            $userWalletInfo->money += $addPrice;

            $userWalletInfo->update();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        switch ($moduleNum)
        {
            case 14:
                $msg = '因穿透股东中有政府部门或国资单位等特殊机构，故不予显示，退款成功';
                break;
        }

        return $this->writeJson(200,null,null,$msg);
    }









}