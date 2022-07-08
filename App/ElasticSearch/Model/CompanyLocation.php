<?php

namespace App\ElasticSearch\Model;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;

class CompanyLocation extends ServiceBase
{

    public  $es ;
    public  $return_data ;
    public  $msg ;
    public  $res ;
    public  $indexName = "company_latlng_202207";

    function __construct($type = 1)
    {
        $this->es =  new ElasticSearchService();
        $this->setIndex($type);
        return parent::__construct();
    }

    function  setIndex($type){
        $map = [
            '1' => 'company_latlng_202207',
            '2' => 'geti_latlng_202207',
            '3' => 'company_geti_latlng_202207',
        ];
        $this->indexName = $map[$type];
    }

    function addSize($size)
    {
        $this->es->addSize($size) ;
        return $this;
    }

    function setSource($filedsArr)
    {
        $this->es->setSource($filedsArr) ;
        return $this;
    }

    //
    function addFrom($offset)
    {

        $this->es->addFrom($offset) ;
        return $this;
    }
    function addSearchAfterV1($value)
    {

        $this->es->addSearchAfterV1($value) ;
        return $this;
    }

    function addSort($field,$desc)
    {
        $this->es->addSort($field,$desc) ;
        return $this;
    }

    function setReturnData($data)
    {

        $this->return_data = $data;
        return $this;
    }

    //
    function setMatchedXdIds()
    {
        $this->return_data['hits']['hits'] = (new XinDongService())::formatEsDate(
            $this->return_data['hits']['hits'],
            [
                'estiblish_time',
                'from_time',
                'to_time',
                'approved_time'
            ]);

        $this->setReturnData($this->return_data)   ;
        return $this;
    }

    function searchFromEs()
    {
        $responseJson = (new XinDongService())->advancedSearch($this->es,$this->indexName);
        $responseArr = @json_decode($responseJson,true);
        $this->setReturnData($responseArr);
        CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
                [
                    'es_query' => $this->es->query,
                ]
        ));

        return $this;
    }

    function setDefault()
    {
        $this->es->setDefault() ;
        return $this;
    }

    function SetAreaQuery($areasLocations){
        if(!empty($areasLocations)){
            $this->es->addGeoShapWithin( $areasLocations) ;
        }
        return $this;
    }
}
