<?php

namespace App\Task\TaskList;

use App\HttpController\Models\BusinessBase\ApproximateEnterpriseModel;
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

        while (true) {

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
