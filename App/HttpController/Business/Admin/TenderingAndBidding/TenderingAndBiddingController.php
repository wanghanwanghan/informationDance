<?php

namespace App\HttpController\Business\Admin\TenderingAndBidding;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\CreateConf;
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
        $cli = $this->mysqlCli();

        $cli->queryBuilder()->limit(5)->get('zhao_tou_biao');

        $res = $cli->execBuilder();

        return $this->writeJson(200, null, $res);
    }

}