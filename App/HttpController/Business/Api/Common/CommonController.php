<?php

namespace App\HttpController\Business\Api\Common;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\Charge;
use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\OcrQueue;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\BaiDu\BaiDuService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\HeHe\HeHeService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Pay\ChargeService;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\Mysqli\QueryBuilder;
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
        $imageFile = $this->request()->getUploadedFiles();

        //返回文件路径
        return $this->writeJson(200, null, CommonService::getInstance()->storeImage($imageFile, $type));
    }

    //文件上传
    function fileUpload()
    {
        $files = $this->request()->getUploadedFiles();

        //返回文件路径
        return $this->writeJson(200, null, CommonService::getInstance()->storeFile($files));
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

        if (strlen($phone) !== 11 || !is_numeric($phone)) return $this->writeJson(201, null, null, '手机号错误');

        //验证图片验证码
        $redis = Redis::defer('redis');
        $redis->select(14);
        $check = $redis->sIsMember('imageVerifyCode', strtolower($iCode));

        // if ($check)

        return $this->writeJson(200, null, null, CommonService::getInstance()->sendCode($phone, $type));
    }

    //上传用户地理位置
    function userLngLatUpload()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $lng = $this->request()->getRequestParam('lng') ?? '';//经度
        $lat = $this->request()->getRequestParam('lat') ?? '';//纬度

        $lng = sprintf('%.8f', trim($lng));
        $lat = sprintf('%.8f', trim($lat));

        try {
            $info = LngLat::create()->where('target', $phone)->get();

            if (empty($info)) {
                LngLat::create()->data(['target' => $phone, 'lng' => $lng, 'lat' => $lat])->save();
            } else {
                $info->update(['lng' => $lng, 'lat' => $lat]);
            }

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, null, '上传成功');
    }

    //退钱到钱包
    function refundToWallet()
    {
        $moduleNum = $this->request()->getRequestParam('moduleNum') ?? '';

        $res = ChargeService::getInstance()->refundToWallet($this->request(), $moduleNum);

        return $this->writeJson($res['code'], null, null, $res['msg']);
    }

    //百度ocr
    function ocrForBaiDu()
    {
        $image = $this->request()->getUploadedFile('image');

        $res = BaiDuService::getInstance()->ocr($image);

        (isset($res['words_result']) && !empty($res['words_result'])) ?
            $res = $res['words_result'] :
            $res = null;

        return $this->writeJson(200, null, $res, '扫描成功');
    }

    //合合ocr
    function ocrForHeHe()
    {
        $image = $this->request()->getUploadedFile('image');

        $res = HeHeService::getInstance()->ocrImageToWord($image);

        (isset($res['result']['whole_text']) && !empty($res['result']['whole_text'])) ?
            $res = $res['result']['whole_text'] :
            $res = null;

        return $this->writeJson(200, null, $res, '扫描成功');
    }

    //ocr识别
    function ocrQueue()
    {
        $reportNum = $this->request()->getRequestParam('reportNum') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $catalogueNum = $this->request()->getRequestParam('catalogueNum') ?? '';
        $catalogueName = $this->request()->getRequestParam('catalogueName') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 'word';//定版的word，还是不确定的pdf
        $filename = $this->request()->getRequestParam('filename');

        if (empty($reportNum)) return $this->writeJson(201, null, null, '报告编号不能是空');
        if (empty($filename)) return $this->writeJson(201, null, null, '未发现上传文件');
        if (!in_array($type, ['word', 'pdf'])) return $this->writeJson(201, null, null, 'type错误');

        $filename = explode(',', $filename);
        $filename = array_filter($filename);
        $tmp = [];
        foreach ($filename as $oneName) {
            $name = explode(DIRECTORY_SEPARATOR, $oneName);
            $tmp[] = end($name);
        }

        try {
            OcrQueue::create()->destroy(function (QueryBuilder $builder) use ($reportNum, $phone, $catalogueNum) {
                $builder->where('reportNum', $reportNum)->where('phone', $phone)->where('catalogueNum', $catalogueNum);
            });

            $insert = [
                'reportNum' => $reportNum,
                'phone' => $phone,
                'catalogueNum' => $catalogueNum,
                'catalogueName' => $catalogueName,
                'type' => $type,
                'filename' => implode(',', $tmp),
            ];

            OcrQueue::create()->data($insert)->save();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, $insert, '成功');
    }

    //地址转经纬度
    function addressToLatLng()
    {
        $address = $this->request()->getRequestParam('address') ?? '';

        if (strlen($address) > 84) return null;

        $res = BaiDuService::getInstance()->addressToLatLng($address);

        return $this->writeJson(200, null, $res, '成功');
    }

}