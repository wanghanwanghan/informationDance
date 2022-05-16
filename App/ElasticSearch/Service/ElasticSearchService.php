<?php

namespace App\ElasticSearch\Service;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;

class ElasticSearchService extends ServiceBase
{
    private $config;
    private $searchBean;

    public $query; //默认都放数组里 如果啥条件都没有 取全部的时候 用json （默认用数组有点问题）

    function __construct()
    {
        $this->config = new Config([
            'host' => CreateConf::getInstance()->getConf('es.host'),
            'port' => CreateConf::getInstance()->getConf('es.port') - 0,
            'username' => CreateConf::getInstance()->getConf('es.username'),
            'password' => CreateConf::getInstance()->getConf('es.password'),
        ]);

        return parent::__construct();
    }

    function createSearchBean(string $setIndex = '', string $setType = '', $setBody = ''): ElasticSearchService
    {
        $bean = new Search();
        empty($setIndex) ?: $bean->setIndex($setIndex);
        empty($setType) ?: $bean->setType($setType);
        empty($setBody) ?: $bean->setBody($setBody);//array or string
        $this->searchBean = $bean;
        return $this;
    }

    function getBody()
    {
        return (new ElasticSearch($this->config))
            ->client()
            ->search($this->searchBean)
            ->getBody();
    }

    function addMustMatchQuery($field, $value){
        $this->query['query']['bool']['must'][] = ['match' => [$field => $value]];
    }

    function addMustNotTermQuery($field, $value){
        $this->query['query']['bool']['must_not'][] = ['term' => [$field => $value]];
    }

    function addMustMatchPhraseQuery($field, $value){
        $this->query['query']['bool']['must'][] = ['match_phrase' => [$field => $value]];
    }

    function addMustRegexpQuery($field, $value){
        $this->query['query']['bool']['must'][] = ['regexp' => [$field => $value]];
    }

    function addMustShouldRegexpQuery($field, $valueArr){
        $boolQuery = []; 
        foreach($valueArr as $value){
            $boolQuery['bool']['should'][] = 
                ['regexp' => [$field => $value]]; 
        } 
         
        $this->query['query']['bool']['must'][] = $boolQuery; 
    }

    function addMustExistsQuery($field){
        $this->query['query']['bool']['must'][] = ['exists' => ['field' => $field]];
    }
    function addMustNotExistsQuery($field){
        $this->query['query']['bool']['must_not'][] = ['exists' => ['field' => $field]];
    }
    function addMustShouldPrefixQuery($field, $valueArr){
        $boolQuery = []; 
        foreach($valueArr as $value){
            $boolQuery['bool']['should'][] = 
                ['prefix' => [$field => $value]]; 
        } 
         
        $this->query['query']['bool']['must'][] = $boolQuery; 
    }
    function addMustShouldPhrasePrefixQuery($field, $valueArr){
        $boolQuery = []; 
        foreach($valueArr as $value){
            $boolQuery['bool']['should'][] = 
                ['match_phrase_prefix' => [$field => $value]]; 
        } 
         
        $this->query['query']['bool']['must'][] = $boolQuery; 
    }
    function addMustShouldPhraseQuery($field, $valueArr){
        $boolQuery = []; 
        foreach($valueArr as $value){
            $boolQuery['bool']['should'][] = 
                ['match_phrase' => [$field => $value]]; 
        } 
         
        $this->query['query']['bool']['must'][] = $boolQuery;
    }

     

    function addMustShouldPhraseQueryV2($valueArr){
        $boolQuery = []; 
        foreach($valueArr as $valueItem){
            $boolQuery['bool']['should'][] = 
                ['match_phrase' => [$valueItem['field'] => $valueItem['value']]]; 
        } 
         
        $this->query['query']['bool']['must'][] = $boolQuery;
    }

    function addMustTermQuery($field, $value){
        $this->query['query']['bool']['must'][] =  ['term' => [$field => $value]]; 
    }

    function addMustRangeQuery($field, $minValue, $maxValue){
        $rangeArr = [];
        if($minValue>0){
            $rangeArr['gte'] = $minValue; 
        }
        if($maxValue>0){
            $rangeArr['lte'] = $maxValue; 
        }
        $this->query['query']['bool']['must'][] =  ['range' => [$field => [$rangeArr]]]; 
    }

    function addMustShouldRangeQuery($field, $map){
        $boolQuery = [];  
        foreach($map  as $subItem){
            $boolQuery['bool']['should'][] = 
                    ['range' => [$field => ['lte' => $subItem['max'] ,'gte' => $subItem['min']]]];
            // $boolQuery['bool']['should'][] = 
            //     ['range' => [$field => ['gte' => $subItem['min'] ]]];
        } 
        
        $this->query['query']['bool']['must'][] = $boolQuery; 
    }

    function setByPage($page, $size = 20){
        $offset = ($page-1)*$size;
        $this->addSize( $size); 
        $this->addFrom( $offset);  
    }

    function addSize($size = 5){
        $this->query['size'] =  $size; 
    }

    function addFrom($from = 0){
        $this->query['from'] =  $from; 
    }
    function addSort($field,$desc){
        $this->query['sort'][] = [$field => ['order' => $desc]];
    }
    function setDefault(){
        if(empty($this->query['query']['bool']['must'])){
            $this->query =   
            '{"size":"'.$this->query['size'].'","from":'.$this->query['from'].',"sort":[{"xd_id":{"order":"desc"}}],"query":{"bool":{"must":[{"match_all":{}}]}}}';
        }
    }
}
