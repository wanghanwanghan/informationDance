<?php

namespace App\Process\ProcessList;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Models\Api\FrontEndUserApproximateEnterpriseModel;
use App\HttpController\Models\Api\UserApproximateEnterpriseModel;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\Score\qpf;
use App\HttpController\Service\XinDong\XinDongKeDongService;
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
//        CommonService::getInstance()->log4PHP('MatchSimilarEnterprisesProccess_run');
        parent::run($arg);

        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;

        $redis = Redis::defer('redis');
        $redis->select(15);

        //开始消费
        $runTimes = 0;
        while (true) {
            $runTimes ++;
            $entInRedis = $redis->rPop(self::QueueKey);
            if (empty($entInRedis)) {
                mt_srand();
                \co::sleep(2);
                continue;
            }
            $info = jsonDecode($entInRedis);

            $score = (new qpf(
                $info['base'][0], $info['base'][1], $info['base'][2], $info['base'][3],
                $info['ys_label'], $info['NIC_ID'], substr($info['ESDATE'], 0, 4), $info['DOMDISTRICT']
            ))->expr(); 
            
            if($runTimes%100==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        'MatchSimilarEnterprisesProccess_Score'=>[
                            '$runTimes'=> $runTimes,
                            '$score'=> $score,
                            'ENTNAME' => $info['ENTNAME']
                        ]
                    ])
                );
            }

             //小于70的 不计算
             if($score <= 70 ){
                continue ;
            }

            if(
                $info['ENTSTATUS'] &&
                in_array($info['ENTSTATUS'],array_keys(CodeEx02::invalidCodeMap()))
            ){
                continue;
            }; 
            

            $esid = control::getUuid();
            $this->toEs($esid, $info);
            try {
                if($info['data_type_front_or_back'] == XinDongKeDongService::$type_frontkend){
                    FrontEndUserApproximateEnterpriseModel::create()->addSuffix($info['user_id'])->data([
                        'userid' => $info['user_id'],
                        'companyid' => $info['companyid'],
                        'esid' => $esid,
                        'score' => $score,
                        'entName' => $info['ENTNAME'],
                        'ying_shou_gui_mo' => $info['ying_shou_gui_mo']?:'',
                        'nic_id' => $info['NIC_ID']?:'',
                        'area' => $info['DOMDISTRICT']?:'',
                        'found_years_nums' => $info['OPFROM']>0?date('Y')-date('Y',strtotime($info['OPFROM'])):0,
                        'mvcc' => '',
                    ])->save();
                }else{
                    UserApproximateEnterpriseModel::create()->addSuffix($info['user_id'])->data([
                        'userid' => $info['user_id'],
                        'companyid' => $info['companyid'],
                        'esid' => $esid,
                        'score' => $score,
                        'entName' => $info['ENTNAME'],
                        'ying_shou_gui_mo' => $info['ying_shou_gui_mo']?:'',
                        'nic_id' => $info['NIC_ID']?:'',
                        'area' => $info['DOMDISTRICT']?:'',
                        'found_years_nums' => $info['OPFROM']>0?date('Y')-date('Y',strtotime($info['OPFROM'])):0,
                        'mvcc' => '',
                    ])->save();
                }
                
            } catch (\Throwable $e) {
                $file = $e->getFile();
                $line = $e->getLine();
                $msg = $e->getMessage();
                $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                CommonService::getInstance()->log4PHP($content);
            }
        }
    } 

    private function toEs(string $esid, array $data)
    {
        //这里可以把搜客中的数据查出来(company_202209)，放到新的es库中
        $bean = new \EasySwoole\ElasticSearch\RequestBean\Get();
        $bean->setIndex('company_202211');
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
