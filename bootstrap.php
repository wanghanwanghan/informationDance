<?php

use App\Command\CommandList\TestCommand;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\EasySwoole\Command\CommandContainer;

//bootstrap 允许在框架未初始化之前，初始化其他业务

//自定义命令
CommandContainer::getInstance()->set(new TestCommand());

//******************注册常用全局函数******************

//加
function jsonEncode($target, $urlEncode = true)
{
    return $urlEncode === false ?
        json_encode($target, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) :
        json_encode($target);
}

//解
function jsonDecode($target, $type = true)
{
    return json_decode($target, $type);
}

//对象转数组
function obj2Arr($obj)
{
    return json_decode(json_encode($obj), true);
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
            CommonService::getInstance()->log4PHP($e->getMessage());
            return '';
        }
    }

    //13位 unixTime + 毫秒
    if (is_numeric($str) && strlen($str) === 13) {
        try {
            return date('Y-m-d', $str / 1000);
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return '';
        }
    }

    //2020-12-12 12:12:12 字符串中含有-的
    if (preg_match('/-/', $str)) {
        try {
            return mb_substr($str, 0, 10);
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return '';
        }
    }

    return $str;
}

//判断比例用的，只显示成 10%
function formatPercent($str)
{
    $str = trim($str);

    if (preg_match('/%/', $str)) {
        return $str;
    }

    if (is_numeric($str)) {
        return sprintf('%.2f', $str * 100) . '%';
    }

    return $str;
}

function sRound($num, $m = 10)
{
    $num = round(trim($num));
    $moto = current(explode('.', $num));
    $saki = $moto / $m;

    if (abs($saki) > 1) {
        $saki = round($saki);
        $saki = $saki * $m;
    } else {
        $saki = $moto;
    }

    return round($saki);
}

function desensitization($num)
{
    if ($num > 0) {
        $len = strlen($num);
        if ($num > 9) {
            $num = substr($num, 0, 1);
            $num = str_pad($num, $len, 0, STR_PAD_RIGHT);
        }
    } elseif ($num < 0) {
        $num = abs($num);
        $len = strlen($num);
        if ($num > 9) {
            $num = substr($num, 0, 1);
            $num = str_pad($num, $len, 0, STR_PAD_RIGHT);
        }
        $num = '-' . $num;
    } else {
        $num = 0;
    }

    return $num - 0;
}






