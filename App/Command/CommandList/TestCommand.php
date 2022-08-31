<?php

namespace App\Command\CommandList;

use App\Command\CommandBase;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\LongXin\LongXinService;
use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\CreateConf;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use  App\HttpController\Service\XinDong\XinDongService;


class TestCommand extends CommandBase
{
    public $queryArr = [];
    function commandName(): string
    {
        return 'test';
    }

    //php easyswoole test
    //只能执行initialize里的
    function exec(array $args): ?string
    {
        $res =  go(function() {
            /* 调用协程API */
            // 用户可以在这里调用上述协程 API
//            $Sql = " select *  from   `wechat_info`  WHERE `code` LIKE  '9144%' limit 2  " ;
//            $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'));
            var_dump([
                $data,
                CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'),
                'xxx'
            ]);
//            foreach ($data as $dataItem){
//
//            }

        });

        return 'exec' . PHP_EOL;
    }

    //php easyswoole  test help
    function help(array $args): ?string
    { 
        
        $res =  go(function() {
            /* 调用协程API */
            // 用户可以在这里调用上述协程 API
           $essentialRes = (new GuoPiaoService())->getEssential('91110105MA01AHQE9C');
           $res = (new GuoPiaoService())->getInvoiceMain(
               '91110105MA01AHQE9C','01', '2020-01', '2020-12', 1
           );

            var_dump($res);
        });

        
 
        return 'help'.$res . PHP_EOL;
    } 
 
}