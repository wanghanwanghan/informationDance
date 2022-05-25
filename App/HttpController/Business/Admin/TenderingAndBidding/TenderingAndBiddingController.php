<?php

namespace App\HttpController\Business\Admin\TenderingAndBidding;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\CreateConf;
use Carbon\Carbon;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config;

class TenderingAndBiddingController extends TenderingAndBiddingBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function mysqlCli(): Client
    {
        $conf = new Config([
            'host' => CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'),
            'port' => 3306,
            'user' => CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'),
            'password' => CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'),
            'database' => 'zhao_tou_biao',
            'timeout' => 5,
            'charset' => 'utf8mb4',
        ]);

        return new Client($conf);
    }

    function getList(): bool
    {
        $date = $this->getRequestData('date') ?? Carbon::now()->format('Y-m-d');

        $cli = $this->mysqlCli();

        $cli->queryBuilder()
            ->where('updated_at', "{$date}%", 'LIKE')
            ->get('zhao_tou_biao');

        try {
            $res = $cli->execBuilder();
        } catch (\Throwable $e) {
            $res = null;
        }

        return $this->writeJson(200, null, $res);
    }

}