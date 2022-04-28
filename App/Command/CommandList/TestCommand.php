<?php

namespace App\Command\CommandList;

use App\Command\CommandBase;
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
        parent::commendInit();

        return 'this is exec' . PHP_EOL;
    }

    //php easyswoole help test
    function help(array $args): ?string
    { 
        // return 'dddd'.json_encode(CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1')).PHP_EOL;
         
        go(function() {
            /* 调用协程API */
            // 用户可以在这里调用上述协程 API
            // $res = (new XinDongService())->getCompanyBasicInfo();
            $res = Company::create()->where('id', 1)->get();
            CommonService::getInstance()->log4PHP(json_encode($res), 'info', 'souke.log');
        
        });

        
 
        return 'this is exec' . PHP_EOL;
    } 
 
}