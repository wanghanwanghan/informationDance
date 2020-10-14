<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\SupervisorEntNameInfo;
use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaHai\FaHaiService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class RunSupervisor extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '* * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        //$workerIndex是task进程编号
        //taskId是进程周期内第几个task任务
        //可以用task，也可以用process

        if (!$this->crontabBase->withoutOverlapping(self::getTaskName())) return true;

        //取出本次要监控的企业列表，如果列表是空会跳到onException
        $target = SupervisorPhoneEntName::create()
            ->where('status', 1)->where('expireTime', time(), '>')
            ->get()->toArray();

        CommonService::getInstance()->log4PHP($target);


        foreach ($target as $one)
        {
            //$this->sf($one['entName']);
            //$this->gs($one['entName']);
            //$this->gl($one['entName']);
            //$this->jy($one['entName']);
        }

        $this->crontabBase->removeOverlappingKey(self::getTaskName());

        return true;
    }

    //司法相关
    private function sf($entName)
    {
        //失信信息=================================================================
        $postData = [
            'doc_type' => 'shixin',
            'keyword' => $entName,
            'pageno' => 1,
            'range' => 10,
        ];

        $res = (new FaHaiService())->getList(CreateConf::getInstance()->getConf('fahai.listBaseUrl') . 'sifa', $postData);

        CommonService::getInstance()->log4PHP($res);

//        if ($res['Status']==200 && !empty($res['Result']))
//        {
//            foreach ($res['Result'] as $one)
//            {
//                $check=SupervisorEntNameInfo::where('keyNo',$one['Id'])->first();
//
//                if ($check) continue;
//
//                strlen($one['Liandate']) > 9 ? $time=$one['Liandate'] : $time='';
//
//                $content="<p>名称: {$one['Executestatus']}</p>";
//                $content.="<p>组织类型: {$one['OrgTypeName']}</p>";
//                $content.="<p>案号: {$one['Anno']}</p>";
//
//                SupervisorEntNameInfo::create([
//                    'entName'=>$entName,
//                    'type'=>1,
//                    'typeDetail'=>1,
//                    'timeRange'=>Carbon::parse($time)->timestamp,
//                    'level'=>1,
//                    'desc'=>$one['Executestatus'],
//                    'content'=>$content,
//                    'detailUrl'=>'',
//                    'keyNo'=>$one['Id'],
//                ]);
//
//                $this->addEntName($entName,'sf');
//            }
//        }





    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        $this->crontabBase->removeOverlappingKey(self::getTaskName());
    }


}
