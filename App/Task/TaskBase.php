<?php

namespace App\Task;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class TaskBase
{
    public $qccUrl;
    public $fahaiList;
    public $fahaiDetail;

    function __construct()
    {
        $this->qccUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        $this->fahaiList = CreateConf::getInstance()->getConf('fahai.listBaseUrl');
        $this->fahaiDetail = CreateConf::getInstance()->getConf('fahai.detailBaseUrl');

        return true;
    }

    //判断时间用的，时间类的字段，只显示成 Y-m-d
    function formatDate($str)
    {
        $str = str_replace(['\\', '/', '年', '月', '日'], '-', trim($str));

        //10位 unixTime
        if (is_numeric($str) && strlen($str) === 10) {
            try {
                return date('Y-m-d', $str);
            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP($e, __FUNCTION__);
                return '';
            }
        }

        //13位 unixTime + 毫秒
        if (is_numeric($str) && strlen($str) === 13) {
            try {
                return date('Y-m-d', $str / 1000);
            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP($e, __FUNCTION__);
                return '';
            }
        }

        //2020-12-12 12:12:12 字符串中含有-的
        if (preg_match('/-/', $str)) {
            try {
                return mb_substr($str, 0, 10);
            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP($e, __FUNCTION__);
                return '';
            }
        }

        return $str;
    }

    //判断比例用的，只显示成 10%
    function formatPercent($str)
    {
        $str = trim($str);

        if (preg_match('%', $str)) {
            return $str;
        }

        if (is_numeric($str)) {
            return sprintf('%.2f', $str * 100) . '%';
        }

        return $str;
    }
}
