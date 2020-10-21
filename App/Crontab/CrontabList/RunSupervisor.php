<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\SupervisorEntNameInfo;
use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Models\Api\SupervisorPhoneLimit;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateTable\CreateTableService;
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
    //发短信用的
    private $entNameArr = [];

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
        $this->qccUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        $this->fahaiList = CreateConf::getInstance()->getConf('fahai.listBaseUrl');
        $this->fahaiDetail = CreateConf::getInstance()->getConf('fahai.detailBaseUrl');
    }

    static function getRule(): string
    {
        //return '5 4 * * *';
        return '*/1 * * * *';
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

        CommonService::getInstance()->log4PHP('准备开始');

        if (!$this->crontabBase->withoutOverlapping(self::getTaskName()))
        {
            CommonService::getInstance()->log4PHP('不开始');
            return true;
        }

//        //取出本次要监控的企业列表，如果列表是空会跳到onException
//        $target = SupervisorPhoneEntName::create()
//            ->where('status', 1)->where('expireTime', time(), '>')->all();
//
//        $target = obj2Arr($target);
//
//        if (empty($target)) throw new \Exception('target is null');
//
//        foreach ($target as $one) {
//            $this->sf($one['entName']);
//            $this->gs($one['entName']);
//            $this->gl($one['entName']);
//            $this->jy($one['entName']);
//        }

        CommonService::getInstance()->log4PHP('开始');


        $this->crontabBase->removeOverlappingKey(self::getTaskName());

        CommonService::getInstance()->log4PHP('结束');

        $this->sendSMS();

        return true;
    }

    //记录公司名和风险个数
    private function addEntName($entName,$type)
    {
        if (isset($this->entNameArr[$entName][$type]))
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
            unset($one);
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
            unset($one);
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
            unset($one);
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
            unset($one);
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
        }

        //实际控制人变更=================================================================
        $url='/api/xdjc/report/syrctjk';

        $data=[
            'companyName'=>$entName,
        ];

        //level=3

        //$res=curl_post($this->baseUrl.$url,$data,$this->header);

        //最终受益人变更=================================================================
        $url='/api/xdjc/report/syrctjk';

        $data=[
            'companyName'=>$entName,
        ];

        //level=4

        //$res=curl_post($this->baseUrl.$url,$data,$this->header);

        //股东变更=================================================================
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

                $desc=trim($one['ALTITEM']);

                //查找股东变更
                if (control::hasString($desc,'股东'))
                {
                    if (empty($desc)) $desc='-';

                    $content="<p>变更前: {$one['ALTBE']}</p>";
                    $content.="<p>变更后: {$one['ALTAF']}</p>";

                    SupervisorEntNameInfo::create()->data([
                        'entName'=>$entName,
                        'type'=>2,
                        'typeDetail'=>4,
                        'timeRange'=>Carbon::parse($time)->timestamp,
                        'level'=>4,
                        'desc'=>$desc,
                        'content'=>$content,
                        'detailUrl'=>'',
                        'keyNo'=>$id,
                    ])->save();

                    $this->addEntName($entName,'gs');
                }else
                {
                    continue;
                }
            }
        }

        //对外投资=================================================================
        $service = 'getInvestmentAbroadInfo';
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

                strlen($one['CONDATE']) > 9 ? $time=$one['CONDATE'] : $time='';

                $content="<p>被投企业: {$one['ENTNAME']}</p>";
                $content.="<p>出资金额: {$one['SUBCONAM']}万元</p>";
                $content.="<p>出资时间: {$one['CONDATE']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>2,
                    'typeDetail'=>5,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>4,
                    'desc'=>'对外投资',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$id,
                ])->save();

                $this->addEntName($entName,'gs');
            }
        }

        //主要成员变更=================================================================
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

                $desc=trim($one['ALTITEM']);

                //查找 董事（理事）、经理、监事
                if (control::hasStringFront($desc,'董事','监事'))
                {
                    if (empty($desc)) $desc='-';

                    $content="<p>变更前: {$one['ALTBE']}</p>";
                    $content.="<p>变更后: {$one['ALTAF']}</p>";

                    SupervisorEntNameInfo::create()->data([
                        'entName'=>$entName,
                        'type'=>2,
                        'typeDetail'=>6,
                        'timeRange'=>Carbon::parse($time)->timestamp,
                        'level'=>4,
                        'desc'=>$desc,
                        'content'=>$content,
                        'detailUrl'=>'',
                        'keyNo'=>$id,
                    ])->save();

                    $this->addEntName($entName,'gs');
                }else
                {
                    continue;
                }
            }
        }
    }

    //管理相关
    private function gl($entName)
    {
        //严重违法=================================================================
        $postData = [
            'keyWord' => $entName,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qccUrl . 'SeriousViolation/GetSeriousViolationList', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $id=md5(json_encode($one));

                $check=SupervisorEntNameInfo::create()->where('keyNo',$id)->get();

                if ($check) continue;

                strlen($one['AddDate']) > 9 ? $time=$one['AddDate'] : $time='';

                $content="<p>类型: {$one['Type']}</p>";
                $content.="<p>列入原因: {$one['AddReason']}</p>";
                $content.="<p>列入时间: {$one['AddDate']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>1,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>2,
                    'desc'=>'严重违法',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$id,
                ])->save();

                $this->addEntName($entName,'gl');
            }
        }

        //行政处罚=================================================================
        $postData = [
            'searchKey' => $entName,
            'pageNo' => 1,
            'pageSize' => 10,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['Id'])->get();

                if ($check) continue;

                strlen($one['LianDate']) > 9 ? $time=$one['LianDate'] : $time='';

                $content="<p>案号: {$one['CaseNo']}</p>";
                $content.="<p>案由: {$one['CaseReason']}</p>";
                $content.="<p>决定日期: {$one['LianDate']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>2,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>3,
                    'desc'=>'行政处罚',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['Id'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
        }

        //环保处罚=================================================================
        $doc_type='epbparty';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'epb',$postData);

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

                $content="<p>案号: {$detail['caseNo']}</p>";
                $content.="<p>类型: {$detail['eventName']}</p>";
                $content.="<p>结果: {$detail['eventResult']}</p>";
                $content.="<p>日期: {$pTime})}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>3,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'环保处罚',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }

        //税收违法=================================================================
        $doc_type='satparty_chufa';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'sat',$postData);

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

                $content="<p>事件名称: {$one['detail']['eventName']}</p>";
                $content.="<p>事件结果: {$one['detail']['eventResult']}</p>";
                $content.="<p>处罚时间: {$pTime}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>4,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'税收违法',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }

        //欠税公告=================================================================
        $doc_type='satparty_qs';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'sat',$postData);

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

                $content="<p>税种: {$one['detail']['taxCategory']}</p>";
                $content.="<p>金额: {$one['detail']['money']}</p>";
                $content.="<p>欠税时间: {$pTime}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>5,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'欠税公告',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }

        //海关处罚=================================================================
        $doc_type='custom_punish';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'custom',$postData);

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

                $content="<p>处罚决定书文号: {$detail['yjCode']}</p>";
                $content.="<p>案件性质: {$detail['eventType']}</p>";
                $content.="<p>案件名称: {$detail['title']}</p>";
                $content.="<p>处罚日期: {$pTime})}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>6,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'海关处罚',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }

        //央行行政处罚=================================================================
        $doc_type='pbcparty';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'pbc',$postData);

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

                $content="<p>公告编号: {$detail['caseNo']}</p>";
                $content.="<p>名称: {$detail['eventName']}</p>";
                $content.="<p>结果: {$detail['eventResult']}</p>";
                $content.="<p>公告日期: {$pTime})}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>7,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'央行行政处罚',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }

        //银保监会处罚=================================================================
        $doc_type='pbcparty_cbrc';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'pbc',$postData);

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

                $content="<p>公告编号: {$detail['caseNo']}</p>";
                $content.="<p>名称: {$detail['eventName']}</p>";
                $content.="<p>结果: {$detail['eventResult']}</p>";
                $content.="<p>公告日期: {$pTime})}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>7,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'银保监会处罚',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }

        //证监处罚=================================================================
        $doc_type='pbcparty_csrc_chufa';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'pbc',$postData);

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

                $content="<p>公告编号: {$detail['caseNo']}</p>";
                $content.="<p>名称: {$detail['eventName']}</p>";
                $content.="<p>结果: {$detail['eventResult']}</p>";
                $content.="<p>公告日期: {$pTime})}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>7,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'证监处罚',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }

        //外汇局处罚=================================================================
        $doc_type='safe_chufa';
        $postData=[
            'keyword'=>$entName,
            'doc_type'=>$doc_type,
        ];

        $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList.'pbc',$postData);

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

                $content="<p>公告编号: {$detail['caseNo']}</p>";
                $content.="<p>违规行为: {$detail['caseCause']}</p>";
                $content.="<p>处罚结果: {$detail['eventResult']}</p>";
                $content.="<p>公告日期: {$pTime})}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>3,
                    'typeDetail'=>7,
                    'timeRange'=>$time,
                    'level'=>3,
                    'desc'=>'外汇局处罚',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['entryId'],
                ])->save();

                $this->addEntName($entName,'gl');
            }
            unset($one);
        }
    }

    //经营相关
    private function jy($entName)
    {
        //经营异常=================================================================
        $postData = [
            'keyNo' => $entName,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qccUrl . 'ECIException/GetOpException', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $id=md5(json_encode($one));

                $check=SupervisorEntNameInfo::create()->where('keyNo',$id)->get();

                if ($check) continue;

                strlen($one['AddDate']) > 9 ? $time=$one['AddDate'] : $time='';

                $content="<p>列入原因: {$one['AddReason']}</p>";
                $content.="<p>列入日期: {$one['AddDate']}</p>";
                $content.="<p>移除日期: {$one['RemoveDate']}</p>";

                empty($one['RemoveDate']) ? $level=2 : $level=5;

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>4,
                    'typeDetail'=>1,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>$level,
                    'desc'=>'经营异常',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$id,
                ])->save();

                if ($level === 2) $this->addEntName($entName,'jy');
            }
        }

        //动产抵押=================================================================
        $service = 'getChattelMortgageInfo';
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
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['ROWKEY'])->get();

                if ($check) continue;

                strlen($one['DJRQ']) > 9 ? $time=$one['DJRQ'] : $time='';

                $content="<p>登记编号: {$one['DJBH']}</p>";
                $content.="<p>登记日期: {$one['DJRQ']}</p>";
                $content.="<p>被担保债权数额: {$one['BDBZQSE']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>4,
                    'typeDetail'=>2,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>3,
                    'desc'=>'动产抵押',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['ROWKEY'],
                ])->save();

                $this->addEntName($entName,'jy');
            }
        }

        //土地抵押=================================================================
        $postData = [
            'keyWord' => $entName,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qccUrl . 'LandMortgage/GetLandMortgageList', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['Id'])->get();

                if ($check) continue;

                strlen($one['StartDate']) > 9 ? $time=$one['StartDate'] : $time='';

                $content="<p>地址: {$one['Address']}</p>";
                $content.="<p>抵押面积: {$one['MortgageAcreage']}公顷</p>";
                $content.="<p>用途: {$one['MortgagePurpose']}</p>";
                $content.="<p>开始日期: {$one['StartDate']}</p>";
                $content.="<p>结束日期: {$one['EndDate']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>4,
                    'typeDetail'=>3,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>3,
                    'desc'=>'土地抵押',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['Id'],
                ])->save();

                $this->addEntName($entName,'jy');
            }
        }

        //股权出质=================================================================
        $service = 'getEquityPledgedInfo';
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
                $check=SupervisorEntNameInfo::create()->where('keyNo',$one['ROWKEY'])->get();

                if ($check) continue;

                strlen($one['GSRQ']) > 9 ? $time=$one['GSRQ'] : $time='';

                $content="<p>登记编号: {$one['DJBH']}</p>";
                $content.="<p>出质人: {$one['CZR']}</p>";
                $content.="<p>出质股权数额: {$one['CZGQSE']}</p>";
                $content.="<p>公示日期: {$one['GSRQ']}</p>";
                $content.="<p>质权人: {$one['ZQR']}</p>";

                SupervisorEntNameInfo::create()->data([
                    'entName'=>$entName,
                    'type'=>4,
                    'typeDetail'=>4,
                    'timeRange'=>Carbon::parse($time)->timestamp,
                    'level'=>3,
                    'desc'=>'股权出质',
                    'content'=>$content,
                    'detailUrl'=>'',
                    'keyNo'=>$one['ROWKEY'],
                ])->save();

                $this->addEntName($entName,'jy');
            }
        }

        //股权质押=================================================================

        //对外担保=================================================================
        $postData = [
            'keyNo' => $entName,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qccUrl . 'AR/GetAnnualReport', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            foreach ($res['result'] as $one)
            {
                if (empty($one['ProvideAssuranceList'])) continue;

                foreach ($one['ProvideAssuranceList'] as $two)
                {
                    $id=md5(json_encode($two));

                    $check=SupervisorEntNameInfo::create()->where('keyNo',$id)->get();

                    if ($check) continue;

                    $content="<p>债权人: {$two['Creditor']}</p>";
                    $content.="<p>债务人: {$two['Debtor']}</p>";
                    $content.="<p>种类: {$two['CreditorCategory']}</p>";
                    $content.="<p>数额: {$two['CreditorAmount']}</p>";

                    SupervisorEntNameInfo::create()->data([
                        'entName'=>$entName,
                        'type'=>4,
                        'typeDetail'=>6,
                        'timeRange'=>time(),//没什么用了，排序用created_at
                        'level'=>3,
                        'desc'=>'对外担保 年报',
                        'content'=>$content,
                        'detailUrl'=>'',
                        'keyNo'=>$id,
                    ])->save();

                    $this->addEntName($entName,'jy');
                }
            }
        }

        //ipo公司的信息，必须先找出股票代码
        $postData=[
            'keyword'=>$entName,
        ];

        $res = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qccUrl . 'ECIV4/GetBasicDetailsByName', $postData);

        if ($res['code']==200 && !empty($res['result']))
        {
            $StockNumber=trim($res['result']['StockNumber']);

            //有数据才是上市公司
            if (!empty($StockNumber))
            {
                //ipo公司的信息
                $postData=[
                    'stockCode'=>$StockNumber,
                    'pageIndex'=>1,
                    'pageSize'=>10,
                ];

                $res = (new QiChaChaService())->setCheckRespFlag(true)
                    ->get($this->qccUrl . 'IPO/GetIPOGuarantee', $postData);

                if ($res['code']==200 && !empty($res['result']))
                {
                    foreach ($res['result'] as $one)
                    {
                        $id=md5(json_encode($one));

                        $check=SupervisorEntNameInfo::create()->where('keyNo',$id)->get();

                        if ($check) continue;

                        $content="<p>被担保方: {$one['BSecuredParty']}</p>";
                        $content.="<p>担保方式: {$one['SecuredType']}</p>";
                        $content.="<p>担保金额: {$one['SecuredType']}万元</p>";
                        $content.="<p>发布日期: {$one['PublicDate']}</p>";

                        SupervisorEntNameInfo::create()->data([
                            'entName'=>$entName,
                            'type'=>4,
                            'typeDetail'=>6,
                            'timeRange'=>time(),//没什么用了，排序用created_at
                            'level'=>3,
                            'desc'=>'对外担保 IPO',
                            'content'=>$content,
                            'detailUrl'=>'',
                            'keyNo'=>$id,
                        ])->save();

                        $this->addEntName($entName,'jy');
                    }
                }
            }
        }
    }

    //发送短信通知
    private function sendSMS()
    {
        $entNameArr=array_keys($this->entNameArr);

        if (empty($entNameArr)) return true;

        $sendPhoneArr=[];

        //先找出所有关注这些公司，并且没过期的手机号
        $arr = SupervisorPhoneEntName::create()
            ->where('entName',$entNameArr,'IN')
            ->where('status',1)
            ->where('expireTime',time(),'>')->all();

        if (empty($arr)) return true;

        $arr = obj2Arr($arr);

        foreach ($arr as $one)
        {
            //查询每一条记录的阈值超过没超过上限，超过了就加入到发送短信名单
            $info = SupervisorPhoneLimit::create()->where('phone',$one['phone'])
                ->where('entName',$one['entName'])->get();

            if (empty($info)) continue;

            if (isset($this->entNameArr[$one['entName']]['sf']))
            {
                if ($this->entNameArr[$one['entName']]['sf'] > $info->sf) $sendPhoneArr[]=$info->phone;
            }

            if (isset($this->entNameArr[$one['entName']]['gs']))
            {
                if ($this->entNameArr[$one['entName']]['gs'] > $info->gs) $sendPhoneArr[]=$info->phone;
            }

            if (isset($this->entNameArr[$one['entName']]['gl']))
            {
                if ($this->entNameArr[$one['entName']]['gl'] > $info->gl) $sendPhoneArr[]=$info->phone;
            }

            if (isset($this->entNameArr[$one['entName']]['jy']))
            {
                if ($this->entNameArr[$one['entName']]['jy'] > $info->jy) $sendPhoneArr[]=$info->phone;
            }
        }

        $sendPhoneArr = array_unique($sendPhoneArr);

        if (empty($sendPhoneArr)) return true;

        $templateNum='02';

        CommonService::getInstance()->sendSMS($sendPhoneArr,$templateNum);

        return true;
    }

    //全局异常
    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getMessage());
        $this->crontabBase->removeOverlappingKey(self::getTaskName());
        CommonService::getInstance()->log4PHP('报错');
    }


}
