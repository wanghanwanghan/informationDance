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
}
