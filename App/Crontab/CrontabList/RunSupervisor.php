<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\SupervisorEntNameInfo;
use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use wanghanwanghan\someUtils\control;

class RunSupervisor extends AbstractCronTask
{
    private $crontabBase;
    private $qccUrl;
    private $fahaiList;
    private $fahaiDetail;
    private $tsUrl;
    //发短信用的
    private $entNameArr = [];

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
        $this->qccUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        $this->fahaiList = CreateConf::getInstance()->getConf('fahai.listBaseUrl');
        $this->fahaiDetail = CreateConf::getInstance()->getConf('fahai.detailBaseUrl');
        $this->tsUrl = CreateConf::getInstance()->getConf('taoshu.baseUrl');
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
            ->all();

        $target = obj2Arr($target);

        if (empty($target)) throw new \Exception('target is null');

        foreach ($target as $one) {
            $this->sf($one['entName']);
            $this->gs($one['entName']);
            //$this->gl($one['entName']);
            //$this->jy($one['entName']);
        }

        $this->crontabBase->removeOverlappingKey(self::getTaskName());

        return true;
    }

    //记录公司名和风险个数
    private function addEntName($entName,$type)
    {
        $name=array_keys($this->entNameArr);

        if (in_array($entName,$name))
        {
            $this->entNameArr[$entName][$type]++;

        }else
        {
            $this->entNameArr[$entName][$type]=1;
        }

        return true;
    }

    //司法相关
    private function sf($entName)
    {
        //失信信息=================================================================
        $postData = [
            'searchKey' => $entName,
            'isExactlySame' => true,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CourtV4/SearchShiXin', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['Id'])->get();

                if ($check) continue;

                strlen($one['Liandate']) > 9 ? $time=$one['Liandate'] : $time='';

                $content="<p>名称: {$one['Executestatus']}</p>";
                $content.="<p>组织类型: {$one['OrgTypeName']}</p>";
                $content.="<p>案号: {$one['Anno']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>1,
                    'typeDetail'=>1,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>1,
                    'desc'=>$one['Executestatus'],
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['Id'],
                ])->save();

                $this->addEntName($entName,'sf');
            }
        }

        //被执行人=================================================================
        $postData = [
            'searchKey' => $entName,
            'isExactlySame' => true,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CourtV4/SearchZhiXing', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['Id'])->get();

                if ($check) continue;

                strlen($one['Liandate']) > 9 ? $time=$one['Liandate'] : $time='';

                $content="<p>名称: {$one['Name']}</p>";
                $content.="<p>标的: {$one['Biaodi']}</p>";
                $content.="<p>案号: {$one['Anno']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>1,
                    'typeDetail'=>2,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>2,
                    'desc'=>'被执行人信息',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['Id'],
                ])->save();

                $this->addEntName($entName,'sf');
            }
        }

        //股权冻结=================================================================
        $postData=[
            'keyWord'=>$entName,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'JudicialAssistance/GetJudicialAssistance', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $id=md5(json_encode($one));

                $check=SupervisorEntNameInfo::create()->where('keyNo',$id)->get();

                if ($check) continue;

                //是冻结还是解除冻结
                if (!empty($one['EquityFreezeDetail']))
                {
                    //是冻结
                    strlen($one['EquityFreezeDetail']['FreezeStartDate']) > 9 ?
                        $time=$one['EquityFreezeDetail']['FreezeStartDate'] :
                        $time='';

                    $content="<p>被执行人: {$one['ExecutedBy']}</p>";
                    $content.="<p>股权数额: {$one['EquityAmount']}</p>";
                    $content.="<p>执行通知书文号: {$one['ExecutionNoticeNum']}</p>";
                    $level=2;
                }else
                {
                    //是解除冻结
                    strlen($one['EquityUnFreezeDetail']['UnFreezeDate']) > 9 ?
                        $time=$one['EquityUnFreezeDetail']['UnFreezeDate'] :
                        $time='';

                    $content="<p>相关企业名称: {$one['ExecutedBy']}</p>";
                    $content.="<p>股权数额: {$one['EquityAmount']}</p>";
                    $content.="<p>执行通知书文号: {$one['ExecutionNoticeNum']}</p>";
                    $level=5;
                }

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>1,
                    'typeDetail'=>3,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>$level,
                    'desc'=>$one['Status'],
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$id,
                ])->save();

                if ($level===2) $this->addEntName($entName,'sf');
            }
        }

        //裁判文书=================================================================
        $doc_type='cpws';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'sifa',$postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as &$one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['entryId'])->get();

                if ($check) continue;

                $detail = (new FaHaiService())->setCheckRespFlag(true)
                    ->getDetail($this->fahaiDetail.$doc_type,[
                        'id'=>$one['entryId'],
                        'doc_type'=>$doc_type
                    ]);

                ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = current($detail['result']) : $detail=null;

                $one['detail']=$detail;

                strlen($one['sortTime']) > 9 ? $time=substr($one['sortTime'],0,10) : $time=time();

                $pTime=date('Y-m-d',$time);

                $content="<p>案号: {$one['detail']['caseNo']}</p>";

                if (empty($one['detail']['partys']))
                {
                    $content.="<p>案由: -</p>";
                    $content.="<p>诉讼身份: -</p>";

                }else
                {
                    foreach ($one['detail']['partys'] as $two)
                    {
                        if ($two['pname']==$entName)
                        {
                            $content.="<p>案由: {$two['caseCauseT']}</p>";
                            $content.="<p>诉讼身份: {$two['partyTitleT']}</p>";
                        }
                    }
                }

                $content.="<p>发布日期: {$pTime}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>1,
                    'typeDetail'=>4,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'裁判文书',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'sf');
            }
        }

        //开庭公告=================================================================
        $doc_type='ktgg';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'sifa',$postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as &$one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['entryId'])->get();

                if ($check) continue;

                $detail = (new FaHaiService())->setCheckRespFlag(true)
                    ->getDetail($this->fahaiDetail.$doc_type,[
                        'id'=>$one['entryId'],
                        'doc_type'=>$doc_type
                    ]);

                ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = current($detail['result']) : $detail=null;

                $one['detail']=$detail;

                strlen($one['sortTime']) > 9 ? $time=substr($one['sortTime'],0,10) : $time=time();

                $pTime=date('Y-m-d',$time);

                $content="<p>案号: {$one['detail']['caseNo']}</p>";

                if (empty($one['detail']['partys']))
                {
                    $content.="<p>案由: -</p>";

                }else
                {
                    foreach ($one['detail']['partys'] as $two)
                    {
                        $tmp=$two['caseCauseT'];
                    }

                    $content.="<p>案由: {$tmp}</p>";
                }

                $content.="<p>开庭时间: {$pTime}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>1,
                    'typeDetail'=>5,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'开庭公告',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'sf');
            }
        }

        //法院公告=================================================================
        $doc_type='fygg';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'sifa',$postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as &$one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['entryId'])->get();

                if ($check) continue;

                $detail = (new FaHaiService())->setCheckRespFlag(true)
                    ->getDetail($this->fahaiDetail.$doc_type,[
                        'id'=>$one['entryId'],
                        'doc_type'=>$doc_type
                    ]);

                ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = current($detail['result']) : $detail=null;

                $one['detail']=$detail;

                strlen($one['sortTime']) > 9 ? $time=substr($one['sortTime'],0,10) : $time=time();

                $pTime=date('Y-m-d',$time);

                $content="<p>案号: {$one['detail']['caseNo']}</p>";

                if (empty($one['detail']['partys']))
                {
                    $content.="<p>案由: -</p>";

                }else
                {
                    foreach ($one['detail']['partys'] as $two)
                    {
                        $tmp=$two['caseCauseT'];

                        $content.="<p>{$two['partyTitleT']}: {$two['pname']}</p>";
                    }

                    $content.="<p>案由: {$tmp}</p>";
                }

                $content.="<p>刊登日期: {$pTime}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>1,
                    'typeDetail'=>6,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'法院公告',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'sf');
            }
        }

        //查封冻结扣押=================================================================
        $doc_type='sifacdk';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'sifa',$postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as &$one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['entryId'])->get();

                if ($check) continue;

                $detail = (new FaHaiService())->setCheckRespFlag(true)
                    ->getDetail($this->fahaiDetail.$doc_type,[
                        'id'=>$one['entryId'],
                        'doc_type'=>$doc_type
                    ]);

                ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = current($detail['result']) : $detail=null;

                $one['detail']=$detail;

                strlen($one['sortTime']) > 9 ? $time=substr($one['sortTime'],0,10) : $time=time();

                $pTime=date('Y-m-d',$time);

                $content="<p>案号: {$one['detail']['caseNo']}</p>";
                $content.="<p>类别: {$one['detail']['action']}</p>";
                $content.="<p>标的类型: {$one['detail']['objectType']}</p>";
                $content.="<p>标的名称: {$one['detail']['objectName']}</p>";
                $content.="<p>审结时间: {$pTime}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>1,
                    'typeDetail'=>7,
                    'timeRange'=>$time,
                    'level'=>2,
                    'desc'=>'查封冻结扣押',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'sf');
            }
        }
    }

    //工商相关
    private function gs($entName)
    {
        //工商变更=================================================================
        $service = 'getRegisterChangeInfo';
        $postData=[
            'entName' => $entName,
            'pageNo' => 1,
            'pageSize' => 10,
        ];

        $res = (new TaoShuService())->setCheckRespFlag(true)->post($postData,$service);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $id=md5(json_encode($one));

                $check=SupervisorEntNameInfo::create()->where('keyNo',$id)->get();

                if ($check) continue;

                strlen($one['ALTDATE']) > 9 ? $time=$one['ALTDATE'] : $time='';

                //什么变更了
                $desc=trim($one['ALTITEM']);

                if (control::hasStringFront($desc,'董事','监事')) continue;
                if (control::hasString($desc,'股东')) continue;

                if (empty($desc)) $desc='-';

                mb_strlen($one['ALTBE']) > 100 ? $one['ALTBE']=mb_substr($one['ALTBE'],0,100).'...' : null;
                mb_strlen($one['ALTAF']) > 100 ? $one['ALTAF']=mb_substr($one['ALTAF'],0,100).'...' : null;

                $content="<p>变更前: {$one['ALTBE']}</p>";
                $content.="<p>变更后: {$one['ALTAF']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>2,
                    'typeDetail'=>1,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>3,
                    'desc'=>$desc,
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$id,
                ])->save();

                $this->addEntName($entName,'gs');
            }

            CommonService::getInstance()->log4PHP($res['result']);
        }




















    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getMessage());
        $this->crontabBase->removeOverlappingKey(self::getTaskName());
    }


}
