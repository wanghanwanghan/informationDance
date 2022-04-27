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
         
        $res = Company::create()->where('id', 1)->get();
        return json_encode($res).PHP_EOL ;
         

        $elasticsearch = new ElasticSearch(
            new  Config([
                'host' => "es-cn-7mz2m3tqe000cxkfn.public.elasticsearch.aliyuncs.com",
                'port' => 9200,
                'username'=>'elastic',
                'password'=>'zbxlbj@2018*()',
    
            ])
        ); 
        
        go(function () use ($elasticsearch) {
            $this->setDefault();
            $bean = new  Search();
            $bean->setIndex('company_287_all');
            $bean->setType('_doc');
            // $bean->setBody(($this->queryArr));
            $bean->setBody(('{
                "size": "1",
                "from": 0,
                "query": {
                    "bool": {
                        "must": [{
                            "match_all": {}
                        }]
                    }
                }
            }'));
            $response = $elasticsearch->client()->search($bean)->getBody(); 
            CommonService::getInstance()->log4PHP(json_encode($response), 'info', 'zhangjiang.log');
            CommonService::getInstance()->log4PHP(json_encode($this->queryArr), 'info', 'zhangjiang.log');
          
        });

        return    json_encode( $response ) . PHP_EOL; 

    }

    function setDefault(){
        if(empty($this->queryArr['query']['bool']['must'])){
            // $this->queryArr['query']['bool']['must'][] =  ['match_all' =>null ]; 
            $this->queryArr['query'] =  json_decode('{
                "bool": {
                    "must": [{
                        "match_all": {}
                    }]
                }
            }',true);
        }
    }
 
}