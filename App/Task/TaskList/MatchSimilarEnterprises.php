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

        $searchOptions = [];
        //营收规模
        $ys = $this->createYs($this->data[1]);// A10
        if($ys){
            $yingshouMap = XinDongService::getYingShouGuiMoMapV3();
            $yingshouMap = array_flip($yingshouMap);
            $searchOptions[] = [
                'pid'=> 50,
                'value'=>[$yingshouMap[$ys]],
            ];
        }

        //国标行业
        $nic = $this->createNic($this->data[2]);// F5147
        //年限
        $nx = $this->createNx($this->data[3]);// 8
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
        $dy = $this->createDy($this->data[4]);// 110108

        $base = [
            $this->data[1], $this->data[2], $this->data[3], $this->data[4]
        ];

        $redis = Redis::defer('redis');
        $redis->select(15);

        $page = 1;

        $runTimes = 0;

        $companys = Company::SearchAfter(
            10000,
            [
                'searchOption' =>  @json_encode($searchOptions),
                'basic_nicid' =>$nic,
                'basic_regionid' =>$dy,
            ]
        );

        foreach ($companys as $company){
            if($runTimes >= 10000){
                break;
            }
            if (empty($res)) {
                break;
            }

            $company['user_id'] = $uid;
            $company['base'] = $base;//参考系
            $redis->lPush(MatchSimilarEnterprisesProccess::QueueKey, jsonEncode($company, false));

            $page++;
            $runTimes ++;
        }

    }
    static  function pushToRedisList($uid,$ys,$nic,$nx,$dy)
    {
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

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'searchOption' =>  @json_encode($searchOptions),
                    'basic_nicid' =>$nic,
                    'basic_regionid' =>$dy,
                ]
            ])
        );

        $companys = \App\ElasticSearch\Model\Company::SearchAfter(
            10,
            [
                'searchOption' =>  json_encode($searchOptions),
                'basic_nicid' =>$nic,
                'basic_regionid' =>$dy,
            ]
        );
        return $companys;
        foreach ($companys as $company){
            if($runTimes >= 10000){
                break;
            }
            if (empty($res)) {
                break;
            }

            $company['user_id'] = $uid;
            $company['base'] = $base;//参考系
            $redis->lPush(MatchSimilarEnterprisesProccess::QueueKey, jsonEncode($company, false));

            $page++;
            $runTimes ++;
        }

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

    private function createNx(string $nx)
    {
        $year = (Carbon::now()->format('Y') - $nx) . '1231';
        return $year - 0;
    }

    private function createNic(string $nic): string
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
