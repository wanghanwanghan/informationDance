<?php

namespace App\HttpController\Service\OneSaid;

use App\HttpController\Models\Api\OneSaid;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class OneSaidService extends ServiceBase
{
    use Singleton;

    //最高255
    private $moduleInfo = [
        0 => ['name' => '财务资产', 'desc' => '详情', 'source' => '乾启'],
        1 => ['name' => '开庭公告', 'desc' => '详情', 'source' => '法海'],
        2 => ['name' => '裁判文书', 'desc' => '详情', 'source' => '法海'],
        3 => ['name' => '法院公告', 'desc' => '详情', 'source' => '法海'],
        4 => ['name' => '执行公告', 'desc' => '详情', 'source' => '法海'],
        5 => ['name' => '失信公告', 'desc' => '详情', 'source' => '法海'],
        6 => ['name' => '司法查封冻结扣押', 'desc' => '详情', 'source' => '法海'],
        7 => ['name' => '司法拍卖', 'desc' => '详情', 'source' => '企查查'],
        8 => ['name' => '欠税公告', 'desc' => '详情', 'source' => '法海'],
        9 => ['name' => '涉税处罚公示', 'desc' => '详情', 'source' => '法海'],
        10 => ['name' => '税务非正常户公示', 'desc' => '详情', 'source' => '法海'],
        11 => ['name' => '纳税信用等级', 'desc' => '详情', 'source' => '法海'],
        12 => ['name' => '税务登记', 'desc' => '详情', 'source' => '法海'],
        13 => ['name' => '税务许可', 'desc' => '详情', 'source' => '法海'],
    ];

    //发表一句话
    function createOneSaid($phone, $oneSaid, $moduleId)
    {
        try
        {
            $info = OneSaid::create()->where('phone',$phone)->where('moduleId',$moduleId)->get();

            if (empty($info))
            {
                OneSaid::create()->data([
                    'phone'=>$phone,
                    'moduleId'=>$moduleId,
                    'oneSaid'=>$oneSaid,
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


}
