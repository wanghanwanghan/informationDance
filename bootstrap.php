<?php

use App\Command\CommandList\TestCommand;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\Services\Log\Log;
use EasySwoole\EasySwoole\Command\CommandContainer;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;

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

//解test
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

//毫秒时间戳
function microTimeNew(): string
{
    return substr(microtime(true) * 1000, 0, 13);
}

//发票状态 大象值转蚂蚁需要的值
function changeFPZT($FPZT): string
{
    //大象 : 1-正常 2-作废 3-冲红 8-失控 9-异常
    //蚂蚁 : 0-正常 2-作废 3-红字 1-失控 4-异常

    switch (trim($FPZT)) {
        case '1':
            $ret = '0';
            break;
        case '2':
            $ret = '2';
            break;
        case '3':
            $ret = '3';
            break;
        case '8':
            $ret = '1';
            break;
        case '9':
            $ret = '4';
            break;
        default:
            $ret = '';
    }

    return $ret;
}

//购方类型 大象值转蚂蚁需要的值
function changeGMFLX($GMFLX): string
{
    //大象 : 01企业 02机关事业单位 03个人 04其他
    //蚂蚁 : 1企业 2个人 3其他

    switch (trim($GMFLX)) {
        case '02':
            $ret = '4';
            break;
        case '04':
            $ret = '3';
            break;
        case '01':
            $ret = '1';
            break;
        case '03':
            $ret = '2';
            break;
        default:
            $ret = '';
    }

    return $ret;
}

//大象发票专用，数据库字段不能是null，所以null值转换为''
function changeNull($data)
{
    return strlen($data) === 0 ? '' : $data;
}

//执行sql
function sqlRaw(string $sql, string $conn = null): ?array
{
    if (empty($conn)) {
        $conn = CreateConf::getInstance()->getConf('env.mysqlDatabase');
    }

    try {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->raw($sql);
        $res = DbManager::getInstance()
            ->query($queryBuilder, true, $conn)
            ->toArray();
    } catch (\Throwable $e) {
        return null;
    }
    return $res['result'];
}

//随机字符串
function getRandomStr($len = 16): string
{
    mt_srand();
    $char_set = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
    shuffle($char_set);
    return implode('', array_slice($char_set, 0, $len));
}

//保留几位小数
function changeDecimal($num, $precision = 2): string
{
    if (!is_numeric($num)) return '';

    $num = round($num, $precision) . '';

    if (strpos($num, '.') === false) {
        $num .= '.';
        for ($i = $precision; $i--;) {
            $num .= '0';
        }
    }

    $len = strlen(substr($num, strpos($num, '.') + 1));

    if ($precision !== $len) {
        for ($i = ($precision - $len); $i--;) {
            $num .= '0';
        }
    }

    return $num;
}



