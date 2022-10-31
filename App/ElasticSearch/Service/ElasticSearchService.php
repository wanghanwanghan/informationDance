<?php

namespace App\ElasticSearch\Service;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Get;
use EasySwoole\ElasticSearch\RequestBean\Search;


class ElasticSearchService extends ServiceBase
{
    private $config;
    private $searchBean;
    private $getBean;

    public $query; //默认都放数组里 如果啥条件都没有 取全部的时候 用json （默认用数组有点问题）
    public $return_data;

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

    function setReturnData($data)
    {

        $this->return_data = $data;
        return $this;
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

    function createGet(string $setIndex = '', string $setType = '', $id = ''): ElasticSearchService
    {
        $bean = new Get();
        $bean->setIndex($setIndex);
        $bean->setType($setType);
        $bean->setId($id);
        empty($setIndex) ?: $bean->setIndex($setIndex);
        empty($setType) ?: $bean->setType($setType);
        empty($id) ?: $bean->setId($id);
        $this->getBean = $bean;
        return $this;
    }

    function customGetBody($bean)
    {
        $cli = (new ElasticSearch($this->config))->client();
        if ($bean instanceof Get) {
            $res = $cli->get($bean)->getBody();
        } else {
            $res = $cli->search($bean)->getBody();
        }
        return $res;
    }

    function getBody()
    {
        return (new ElasticSearch($this->config))
            ->client()
            ->search($this->searchBean)
            ->getBody();
    }

    function addMustMatchQuery($field, $value)
    {
        $this->query['query']['bool']['must'][] = ['match' => [$field => $value]];
    }

    function addMustNotTermQuery($field, $value)
    {
        $this->query['query']['bool']['must_not'][] = ['term' => [$field => $value]];
    }
    function addMustNotMatchQuery($field, $value)
    {
        $this->query['query']['bool']['must_not'][] = ['match' => [$field => $value]];
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'SetQueryBySearchTextV5'  =>  [
                    'query'=>$this->query,
                ],
            ])
        );
    }

    function addMustMatchPhraseQuery($field, $value)
    {
        $this->query['query']['bool']['must'][] = ['match_phrase' => [$field => $value]];
    }

    function addMustRegexpQuery($field, $value)
    {
        $this->query['query']['bool']['must'][] = ['regexp' => [$field => $value]];
    }

    function addMustShouldRegexpQuery($field, $valueArr)
    {
        $boolQuery = [];
        foreach ($valueArr as $value) {
            $boolQuery['bool']['should'][] =
                ['regexp' => [$field => $value]];
        }

        $this->query['query']['bool']['must'][] = $boolQuery;
    }

    function addMustExistsQuery($field)
    {
        $this->query['query']['bool']['must'][] = ['exists' => ['field' => $field]];
    }

    function addMustNotExistsQuery($field)
    {
        $this->query['query']['bool']['must_not'][] = ['exists' => ['field' => $field]];
    }

    function addMustShouldPrefixQuery($field, $valueArr)
    {
        $boolQuery = [];
        foreach ($valueArr as $value) {
            $boolQuery['bool']['should'][] =
                ['prefix' => [$field => $value]];
        }

        $this->query['query']['bool']['must'][] = $boolQuery;
    }

    function addMustShouldPhrasePrefixQuery($field, $valueArr)
    {
        $boolQuery = [];
        foreach ($valueArr as $value) {
            $boolQuery['bool']['should'][] =
                ['match_phrase_prefix' => [$field => $value]];
        }

        $this->query['query']['bool']['must'][] = $boolQuery;
    }

    function addMustShouldPhraseQuery($field, $valueArr)
    {
        $boolQuery = [];
        foreach ($valueArr as $value) {
            if(empty(trim($value))){
                continue;
            }
            $boolQuery['bool']['should'][] =
                ['match_phrase' => [$field => $value]];
        }

        $this->query['query']['bool']['must'][] = $boolQuery;
    }


    function addMustShouldPhraseQueryV2($valueArr)
    {
        $boolQuery = [];
        foreach ($valueArr as $valueItem) {
            $boolQuery['bool']['should'][] =
                ['match_phrase' => [$valueItem['field'] => $valueItem['value']]];
        }

        $this->query['query']['bool']['must'][] = $boolQuery;
    }


    function addMustTermQuery($field, $value)
    {
        $this->query['query']['bool']['must'][] = ['term' => [$field => $value]];
    }


    /**
     * 新加terms
     * {
     * "query": {
     * "bool": {
     * "must": [{
     * "terms": {
     * "xd_id": [
     * "159074",
     * "161792"
     * ]
     * }
     * }
     * ]
     * }
     * }
     * }
     */

    function addMustTermsQuery($field, $array)
    {
        $this->query['query']['bool']['must'][] = ['terms' => [$field => $array]];
    }

    function addMustRangeQuery($field, $minValue, $maxValue)
    {
        $rangeArr = [];
        if ($minValue > 0) {
            $rangeArr['gte'] = $minValue;
        }
        if ($maxValue > 0) {
            $rangeArr['lte'] = $maxValue;
        }
        $this->query['query']['bool']['must'][] = ['range' => [$field => [$rangeArr]]];
    }

    function addMustShouldRangeQuery($field, $map)
    {
        $boolQuery = [];
        foreach ($map as $subItem) {
            $boolQuery['bool']['should'][] =
                ['range' => [$field => ['lte' => $subItem['max'], 'gte' => $subItem['min']]]];
            // $boolQuery['bool']['should'][] = 
            //     ['range' => [$field => ['gte' => $subItem['min'] ]]];
        }

        $this->query['query']['bool']['must'][] = $boolQuery;
    }

    function setByPage($page, $size = 20)
    {
        $offset = ($page - 1) * $size;
        $this->addSize($size);
        $this->addFrom($offset);
    }

    function addSize($size = 5)
    {
        $this->query['size'] = $size;
    }

    function addFrom($from = 0)
    {
        $this->query['from'] = $from;
    }

    function addSort($field, $desc)
    {

        $this->query['sort'][] = [$field => ['order' => $desc]];
    }

    function addSortV2($field, $value)
    {

        $this->query['sort'][] = [$field => $value];
    }

    function addSearchAfterV1($value)
    {
        $this->query['search_after'] = [$value];
    }

    //设置 _source
    function setSource($filedsArr)
    {
        $this->query['_source'] = $filedsArr;
    }

    function setDefault()
    {
        $size = $this->query['size'] ?: 10;
        $from = $this->query['from'] ?: 0;
        if (
            empty($this->query['query']['bool']['must']) &&
            empty($this->query['query']['bool']['must_not'])
        ) {
            $this->query =
                //'{"size":"'.$this->query['size'].'","from":'.$this->query['from'].',"sort":[{"_id":{"order":"desc"}}],"query":{"bool":{"must":[{"match_all":{}}]}}}';
                '{"size":'.$size.',"from":'.$from.',"sort":[{"_id":{"order":"desc"}}],"query":{"bool":{"must":[{"match_all":{}}]}}}';
                //'{"size":' . $size . ',"from":' . $from . ',"query":{"bool":{"must":[{"match_all":{}}]}}}';
        }
    }

    /*
     多边形查询
      $arrays = [
            [116.443452, 39.872222],
            [116.421369, 39.872791],
    ];

    * */
    function addGeoShapWithin($arrays, $filed = 'location')
    {

        $this->query['query']['geo_shape'][$filed] = [
            //$this->query['query']['geo_shape'][$filed] = [
            'relation' => 'within',
            'shape' => [
                'type' => 'polygon',
                'coordinates' => [
                    $arrays
                ]
            ]
        ];
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__ . __FUNCTION__ . __LINE__,
//                '$this->query' => $this->query
//            ])
//        );
    }

    function addGeoShapWithinV2($arrays, $filed = 'location')
    {

        $this->query['query']['bool']['must'][] =
            [
                'geo_shape' => [
                    $filed => [

                        'relation' => 'within',
                        'shape' => [
                            'type' => 'polygon',
                            'coordinates' => [
                                $arrays
                            ]
                        ]
                    ]
                ]
            ];
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__ . __FUNCTION__ . __LINE__,
                '$this->query' => $this->query
            ])
        );
    }

    function Search($index = 'company_202207')
    {
        $elasticsearch = new ElasticSearch(
            new  Config([
                'host' => "es-cn-7mz2m3tqe000cxkfn.public.elasticsearch.aliyuncs.com",
                'port' => 9200,
                'username' => 'elastic',
                'password' => 'zbxlbj@2018*()',
            ])
        );
        $bean = new  Search();
        $bean->setIndex($index);
        //不加的话，不能保证一致性 即同样的搜索条件，会返回不一致的结果
        $bean->setPreference("_primary");
        $bean->setType('_doc');
        $bean->setBody($this->query);
        $response = $elasticsearch->client()->search($bean)->getBody();
        CommonService::getInstance()->log4PHP(json_encode(['es_query' => $this->query]));
        $this->setReturnData($response);
        return $this->return_data;
    }

    function setPageIdCache()
    {
        $this->query;
        foreach ($this->return_data['hits']['hits'] as $dataItem) {
            $dataItem['_source']['xd_id'];
        }
        return $this;
    }

}
