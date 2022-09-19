<?php

namespace App\Process\ProcessList;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Models\Api\UserApproximateEnterpriseModel;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\Score\qpf;
use App\HttpController\Service\XinDong\XinDongService;
use App\Process\ProcessBase;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;
use wanghanwanghan\someUtils\control;

class MatchSimilarEnterprisesProccess extends ProcessBase
{
    const ProcessNum = 3;
    const QueueKey = 'MatchSimilarEnterprisesQueue';

    public $p_index;

    protected function run($arg)
    {


        parent::run($arg);

        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;

        $redis = Redis::defer('redis');
        $redis->select(15);

        //开始消费
        while (true) {
            $entInsRedis = $redis->rPop(self::QueueKey);


            if (empty($entInRedis)) {
                mt_srand();
                \co::sleep(2);
                continue;
            }

            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'MatchSimilarEnterprisesProccess_pop_from_redis'=>[
                        'list_key'=> self::QueueKey,
                        '$entInsRedis'=> $entInsRedis,
                    ]
                ])
            );

            $info = jsonDecode($entInRedis);

            $score = (new qpf(
                $info['base'][0], $info['base'][1], $info['base'][2], $info['base'][3],
                $info['ys_label'], $info['NIC_ID'], substr($info['ESDATE'], 0, 4), $info['DOMDISTRICT']
            ))->expr();
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'MatchSimilarEnterprisesProccess_Score'=>[
                        '$score'=> $score,
                        '$info'=> $info,
                        'param1' => $info['base'][0],
                        'param2' => $info['base'][1],
                        'param3' => $info['base'][2],
                        'param4' => $info['base'][3],
                        'param5' => $info['ys_label'],
                        'param6' => $info['NIC_ID'],
                        'param7' => substr($info['ESDATE'], 0, 4),
                        'param8' => $info['DOMDISTRICT']
                    ]
                ])
            );
            $esid = control::getUuid();
            $this->toEs($esid, $info);

            $res = (new XinDongService())->getEsBasicInfoV2($info['companyid'],[]);


            $res['ENTTYPE'] && $res['ENTTYPE_CNAME'] =   CodeCa16::findByCode($res['ENTTYPE']);
            $res['ENTSTATUS_CNAME'] =   '';
            $res['ENTSTATUS'] && $res['ENTSTATUS_CNAME'] =   CodeEx02::findByCode($res['ENTSTATUS']);

            try {
                UserApproximateEnterpriseModel::create()->addSuffix($info['user_id'])->data([
                    'userid' => $info['user_id'],
                    'companyid' => $info['companyid'],
                    'esid' => $esid,
                    'score' => $score,
                    'entName' => $res['entName'],
                    'ying_shou_gui_mo' => $res['ying_shou_gui_mo']?:'',
                    'nic_id' => $res['NIC_ID']?:'',
                    'area' => $res['DOMDISTRICT']?:'',
                    'found_years_nums' => $res['OPFROM']>0?date('Y')-date('Y',strtotime($res['OPFROM'])):0,
                    'mvcc' => '',
                ])->save();
            } catch (\Throwable $e) {
                $file = $e->getFile();
                $line = $e->getLine();
                $msg = $e->getMessage();
                $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                CommonService::getInstance()->log4PHP($content);
            }
        }
    }

    static function calScore()
    {


        $redis = Redis::defer('redis');
        $redis->select(15);

        //开始消费
        $nums = 0;
        while (true) {
            $entInsRedis = $redis->rPop(self::QueueKey);
            if ($nums>=100) {
                break;
            }

            if (empty($entInRedis)) {

                continue;
            }

            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'MatchSimilarEnterprisesProccess_pop_from_redis'=>[
                        'list_key'=> self::QueueKey,
                        '$entInsRedis'=> $entInsRedis,
                    ]
                ])
            );

            $info = jsonDecode($entInRedis);

            $score = (new qpf(
                $info['base'][0], $info['base'][1], $info['base'][2], $info['base'][3],
                $info['ys_label'], $info['NIC_ID'], substr($info['ESDATE'], 0, 4), $info['DOMDISTRICT']
            ))->expr();
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'MatchSimilarEnterprisesProccess_Score'=>[
                        '$score'=> $score,
                        '$info'=> $info,
                        'param1' => $info['base'][0],
                        'param2' => $info['base'][1],
                        'param3' => $info['base'][2],
                        'param4' => $info['base'][3],
                        'param5' => $info['ys_label'],
                        'param6' => $info['NIC_ID'],
                        'param7' => substr($info['ESDATE'], 0, 4),
                        'param8' => $info['DOMDISTRICT']
                    ]
                ])
            );


            $res = (new XinDongService())->getEsBasicInfoV2($info['companyid'],[]);


            $res['ENTTYPE'] && $res['ENTTYPE_CNAME'] =   CodeCa16::findByCode($res['ENTTYPE']);
            $res['ENTSTATUS_CNAME'] =   '';
            $res['ENTSTATUS'] && $res['ENTSTATUS_CNAME'] =   CodeEx02::findByCode($res['ENTSTATUS']);

            try {
                UserApproximateEnterpriseModel::create()->addSuffix($info['user_id'])->data([
                    'userid' => $info['user_id'],
                    'companyid' => $info['companyid'],
                    'esid' => 0,
                    'score' => $score,
                    'entName' => $res['entName'],
                    'ying_shou_gui_mo' => $res['ying_shou_gui_mo']?:'',
                    'nic_id' => $res['NIC_ID']?:'',
                    'area' => $res['DOMDISTRICT']?:'',
                    'found_years_nums' => $res['OPFROM']>0?date('Y')-date('Y',strtotime($res['OPFROM'])):0,
                    'mvcc' => '',
                ])->save();
            } catch (\Throwable $e) {
                $file = $e->getFile();
                $line = $e->getLine();
                $msg = $e->getMessage();
                $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                CommonService::getInstance()->log4PHP($content);
            }

            $nums ++;
        }
        return $nums;
    }

    private function toEs(string $esid, array $data)
    {
        //这里可以把搜客中的数据查出来(company_202209)，放到新的es库中
        $bean = new \EasySwoole\ElasticSearch\RequestBean\Get();
        $bean->setIndex('company_202209');
        $bean->setType('_doc');
        $bean->setId($data['companyid']);
        $res = (new ElasticSearchService())->customGetBody($bean);
        CommonService::getInstance()->log4PHP($res, 'info', 'es_ent_check');
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);
        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }
}
