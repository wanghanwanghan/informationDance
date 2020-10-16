<?php

namespace App\HttpController\Service\OneSaid;

use App\HttpController\Models\Api\OneSaid;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class OneSaidService extends ServiceBase
{
    use Singleton;

    //最高255
    private $moduleInfo = [
        0 => ['name' => '财务资产', 'desc' => '详情', 'source' => ''],
        1 => ['name' => '开庭公告', 'desc' => '详情', 'source' => ''],
        2 => ['name' => '裁判文书', 'desc' => '详情', 'source' => ''],
        3 => ['name' => '法院公告', 'desc' => '详情', 'source' => ''],
        4 => ['name' => '执行公告', 'desc' => '详情', 'source' => ''],
        5 => ['name' => '失信公告', 'desc' => '详情', 'source' => ''],
        6 => ['name' => '司法查封冻结扣押', 'desc' => '详情', 'source' => ''],
        7 => ['name' => '司法拍卖', 'desc' => '详情', 'source' => ''],
        8 => ['name' => '欠税公告', 'desc' => '详情', 'source' => ''],
        9 => ['name' => '涉税处罚公示', 'desc' => '详情', 'source' => ''],
        10 => ['name' => '税务非正常户公示', 'desc' => '详情', 'source' => ''],
        11 => ['name' => '纳税信用等级', 'desc' => '详情', 'source' => ''],
        12 => ['name' => '税务登记', 'desc' => '详情', 'source' => ''],
        13 => ['name' => '税务许可', 'desc' => '详情', 'source' => ''],

        14 => ['name' => '基本信息', 'desc' => '', 'source' => ''],
        15 => ['name' => '股东', 'desc' => '', 'source' => ''],
        16 => ['name' => '实际控制人和控制路径', 'desc' => '', 'source' => ''],
        17 => ['name' => '企业主要管理人', 'desc' => '', 'source' => ''],
        18 => ['name' => '分支机构', 'desc' => '', 'source' => ''],
        19 => ['name' => '变更信息', 'desc' => '', 'source' => ''],
        20 => ['name' => '法人变更', 'desc' => '', 'source' => ''],
        21 => ['name' => '经营异常', 'desc' => '', 'source' => ''],
        22 => ['name' => '融资历史', 'desc' => '', 'source' => ''],
        23 => ['name' => '对外投资', 'desc' => '', 'source' => ''],
        24 => ['name' => '招投标', 'desc' => '', 'source' => ''],
        25 => ['name' => '购地信息', 'desc' => '', 'source' => ''],
        26 => ['name' => '土地公示', 'desc' => '', 'source' => ''],
        27 => ['name' => '土地转让', 'desc' => '', 'source' => ''],
        28 => ['name' => '招聘信息', 'desc' => '', 'source' => ''],
        29 => ['name' => '建筑资质证书', 'desc' => '', 'source' => ''],
        30 => ['name' => '建筑工程项目', 'desc' => '', 'source' => ''],
        31 => ['name' => '债券', 'desc' => '', 'source' => ''],
        32 => ['name' => '行政许可', 'desc' => '', 'source' => ''],
        33 => ['name' => '行政处罚', 'desc' => '', 'source' => ''],
        34 => ['name' => '环保处罚', 'desc' => '', 'source' => ''],
        35 => ['name' => '重点监控企业名单', 'desc' => '', 'source' => ''],
        36 => ['name' => '环保企业自行监测结果', 'desc' => '', 'source' => ''],
        37 => ['name' => '环评公示数据', 'desc' => '', 'source' => ''],
        38 => ['name' => '海关企业', 'desc' => '', 'source' => ''],
        39 => ['name' => '海关许可', 'desc' => '', 'source' => ''],
        40 => ['name' => '海关信用', 'desc' => '', 'source' => ''],
        41 => ['name' => '海关处罚', 'desc' => '', 'source' => ''],
        42 => ['name' => '股权出质', 'desc' => '', 'source' => ''],
        43 => ['name' => '动产抵押', 'desc' => '', 'source' => ''],
        44 => ['name' => '土地抵押', 'desc' => '', 'source' => ''],
        45 => ['name' => '对外担保', 'desc' => '', 'source' => ''],
        46 => ['name' => '央行行政处罚', 'desc' => '', 'source' => ''],
        47 => ['name' => '银保监会处罚公示', 'desc' => '', 'source' => ''],
        48 => ['name' => '证监处罚公示', 'desc' => '', 'source' => ''],
        49 => ['name' => '证监会许可批复等级', 'desc' => '', 'source' => ''],
        50 => ['name' => '外汇局处罚', 'desc' => '', 'source' => ''],
        51 => ['name' => '外汇局许可', 'desc' => '', 'source' => ''],
        52 => ['name' => '应收账款登记', 'desc' => '', 'source' => ''],
        53 => ['name' => '所有权保留', 'desc' => '', 'source' => ''],
        54 => ['name' => '保证金质押登记', 'desc' => '', 'source' => ''],
        55 => ['name' => '仓单质押登记', 'desc' => '', 'source' => ''],
        56 => ['name' => '融资租赁', 'desc' => '', 'source' => ''],
        57 => ['name' => '其他动产融资', 'desc' => '', 'source' => ''],
    ];

    //发布（修改）一句话
    function createOneSaid($phone, $oneSaid, $moduleId,$entName)
    {
        $oneSaid = trim($oneSaid);

        !empty($oneSaid) ?: $oneSaid='';

        //敏感内容检测
        $oneSaid = CommonService::getInstance()->checkContentByAI($oneSaid);

        try
        {
            $info = OneSaid::create()->where('phone',$phone)->where('entName',$entName)->where('moduleId',$moduleId)->get();

            if (empty($info))
            {
                $info = OneSaid::create()->data([
                    'phone'=>$phone,
                    'moduleId'=>$moduleId,
                    'moduleName'=>$this->moduleInfo[$moduleId]['name'],
                    'oneSaid'=>$oneSaid,
                    'entName'=>$entName,
                ])->save();

            }else
            {
                $info->update(['oneSaid'=>$oneSaid]);
            }

        }catch (\Throwable $e)
        {
            $info = [];
        }

        return $info;
    }

    //获取一句话
    function getOneSaid($phone,$moduleId,$entName,$onlyOneSaid=true)
    {
        try
        {
            $oneSaidInfo = OneSaid::create()
                ->where('phone',$phone)
                ->where('entName',$entName)
                ->where('moduleId',$moduleId)->get();

        }catch (\Throwable $e)
        {
            $oneSaidInfo = [];
        }

        if ($onlyOneSaid)
        {
            return empty($oneSaidInfo) ? null : $oneSaidInfo->oneSaid;
        }else
        {
            return empty($oneSaidInfo) ? null : $oneSaidInfo->toArray();
        }
    }


}
