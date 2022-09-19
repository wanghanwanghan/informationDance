<?php

namespace App\Task\TaskList;

use App\ElasticSearch\Model\Company;
use App\HttpController\Models\BusinessBase\ApproximateEnterpriseModel;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\XinDongService;
use App\Process\ProcessList\MatchSimilarEnterprisesProccess;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class MatchSimilarEnterprises extends TaskBase implements TaskInterface
{
    private $data;

    //匹配近似企业

    function __construct($data)
    {
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'MatchSimilarEnterprises_init'=>[
                    '$data'=> $data
                ]
            ])
        );
        $this->data = array_map(function ($row) {
            return trim($row);
        }, $data);

        return parent::__construct();
    }

    // 第一步根据条件取主表里的近似企业    | TODO 这一步 改为直接从es里查询
    // 第二步放到redis队列
    function run(int $taskId, int $workerIndex)
    {
        $uid = $this->data[0] - 0;
        //$ys = $this->createYs($this->data[1]);// A10
        $ys = ($this->data[1]);// A10
        //$nic = $this->createNic($this->data[2]);// F5147
        $nic = ($this->data[2]);// F5147
        //$nx = $this->createNx($this->data[3]);// 8
        $nx = ($this->data[3]);// 8
        //$dy = $this->createDy($this->data[4]);// 110108
        $dy = ($this->data[4]);// 110108
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'MatchSimilarEnterprises_start_run'=>[
                    '$uid'=> $uid,
                    '$ys'=> $ys,
                    '$nic'=> $nic,
                    '$nx'=> $nx,
                    '$dy'=> $dy,
                ]
            ])
        );

        self::pushToRedisList($uid,$ys,$nic,$nx,$dy);
    }

    static  function pushToRedisList($uid,$ys,$nic,$nx,$dy)
    {
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'pushToRedisList_start'=>[
                    '$uid'=> $uid,
                    '$ys'=> $ys,
                    '$nic'=> $nic,
                    '$nx'=> $nx,
                    '$dy'=> $dy,
                ]
            ])
        );
        $nic = self::createNicV2($nic);
        $dy = self::createDyV2($dy);
        $searchOptions = [];
        //营收规模
        if($ys){
            $yingshouMapRaw = XinDongService::getYingShouGuiMoMapV3();
            foreach ($yingshouMapRaw as $key=>$arr){
                if(
                    in_array($ys,$arr)
                ){
                    $searchOptions[] = [
                        'pid'=> 50,
                        'value'=>[$key],
                    ];
                    break;
                }
            }
        }
        //年限
        if($nx){
            $tmpValue = 2;
            if($nx == '0-2'){
                $tmpValue = 2;
            }

            if($nx == '2-5'){
                $tmpValue = 5;
            }

            if($nx == '5-10'){
                $tmpValue = 10;
            }

            if($nx == '10-15'){
                $tmpValue = 15;
            }

            if($nx == '15-20'){
                $tmpValue = 20;
            }

            if($nx == '20年以上'){
                $tmpValue = 25;
            }

            $searchOptions[] = [
                'pid'=> 20,
                'value'=>[$tmpValue],
            ];
        }

        //地域
        $base = [
            $ys,$nic,$nx,$dy
        ];

        $redis = Redis::defer('redis');
        $redis->select(15);

        $page = 1;
        $runTimes = 0;
        $maxTimes = 100;
        $esRequestData =  [
            'searchOption' =>  json_encode($searchOptions),
            'basic_nicid' =>$nic,
            'basic_regionid' =>$dy,
        ];
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'pushToRedisList_about_to_search_from_es'=>[
                    '$esRequestData'=> $esRequestData,
                ]
            ])
        );

        $companys = \App\ElasticSearch\Model\Company::SearchAfterV2(
            $maxTimes,
            $esRequestData
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'pushToRedisList_search_from_es'=>[
                    'es_return_nums'=> count($companys),
                ]
            ])
        );
        foreach ($companys as $company){
            if($runTimes >= $maxTimes){
                break;
            }
            if (empty($company)) {
                break;
            }

            $company['user_id'] = $uid;
            $company['ys_label'] = $company['ying_shou_gui_mo'];
            $company['base'] = $base;//参考系

            $redis->lPush(MatchSimilarEnterprisesProccess::QueueKey, jsonEncode($company, false));
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'pushToRedisList_lPush_to_Redis'=>[
                        'list_key'=> MatchSimilarEnterprisesProccess::QueueKey,
                        'value'=> jsonEncode($company, false),
                    ]
                ])
            );
            $page++;
            $runTimes ++;
        }

        return true;
    }

    function runOld(int $taskId, int $workerIndex)
    {
        $uid = $this->data[0] - 0;
        $ys = $this->createYs($this->data[1]);// A10
        $nic = $this->createNic($this->data[2]);// F5147
        $nx = $this->createNx($this->data[3]);// 8
        $dy = $this->createDy($this->data[4]);// 110108

        $base = [
            $this->data[1], $this->data[2], $this->data[3], $this->data[4]
        ];

        $redis = Redis::defer('redis');
        $redis->select(15);

        $page = 1;

        $runTimes = 0;
        while (true) {
            if($runTimes >= 10000){
                break;
            }

            // 只查这些字段，有索引覆盖
            $res = ApproximateEnterpriseModel::create()
                ->where('ys_label', $ys, 'IN')
                ->where('NIC_ID', "{$nic}%", 'LIKE')
                ->where('ESDATE', $nx, '>=')
                ->where('DOMDISTRICT', "{$dy}%", 'LIKE')
                ->page($page, 500)
                ->field(['ys_label', 'NIC_ID', 'ESDATE', 'DOMDISTRICT', 'companyid'])
                ->all();

            if (empty($res)) {
                break;
            }

            foreach ($res as $one) {
                $one = obj2Arr($one);
                $one['user_id'] = $uid;
                $one['base'] = $base;//参考系
                $redis->lPush(MatchSimilarEnterprisesProccess::QueueKey, jsonEncode($one, false));
            }

            $page++;
            $runTimes ++;
        }
    }

    private function createDy(string $dy)
    {
        return substr($dy, 0, 2);
    }

    static function createDyV2(string $dy)
    {
        return substr($dy, 0, 2);
    }

    private function createNx(string $nx)
    {
        $year = (Carbon::now()->format('Y') - $nx) . '1231';
        return $year - 0;
    }

    private function createNic(string $nic): string
    {
        return strlen($nic) >= 4 ? substr($nic, 0, -2) : $nic;
    }

    static function createNicV2(string $nic): string
    {
        return strlen($nic) >= 4 ? substr($nic, 0, -2) : $nic;
    }

    private function createYs(string $ys): array
    {
        $ys_tmp = substr($ys, 1);
        $arr = [];
        for ($i = -1; $i <= 1; $i++) {
            $arr[] = 'A' . ($ys_tmp + $i);
        }
        return $arr;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
