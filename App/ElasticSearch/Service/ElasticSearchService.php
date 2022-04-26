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

    private $queryArr;

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
        $this->queryArr['query']['bool']['must'][] = ['match' => [$field => $value]];
    }

    function addMustTermQuery($field, $value){
        $this->queryArr['query']['bool']['must'][] =  ['term' => [$field => $value]]; 
    }

    function addMustRangeQuery($field, $minValue, $maxValue){
        $rangeArr = [];
        if($minValue>0){
            $rangeArr['gte'] = $minValue; 
        }
        if($maxValue>0){
            $rangeArr['lte'] = $maxValue; 
        }
        $this->queryArr['query']['bool']['must'][] =  ['range' => [$field => [$rangeArr]]]; 
    }

    function setByPage($page, $size = 20){
        $offset = ($page-1)*$size;
        $this->addSize( $size); 
        $this->addFrom( $offset);  
    }

    function addSize($size){
        $this->queryArr['size'] =  $size; 
    }

    function addFrom($from){
        $this->queryArr['from'] =  $from; 
    }
}
