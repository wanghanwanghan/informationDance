<?php

namespace App\ElasticSearch\Model;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\QueueLists;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;
use Vtiful\Kernel\Format;

class Company extends ServiceBase
{

    public  $es ;
    public  $return_data ;
    public  $msg ;
    public  $res ;

    function __construct()
    {
        $this->es =  new ElasticSearchService();
        return parent::__construct();
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

    function addMustTermQuery($field, $value)
    {
        $this->es->addMustTermQuery($field,$value) ;
        return $this;
    }

    //区域坐标 [[11,112],[11,112],[11,112]]
    function SetAreaQuery($areaArr,$type =1 )
    {
        if(
            empty($areaArr)
        ){
            return $this;
        }

        $companyLocationEsModel = new \App\ElasticSearch\Model\CompanyLocation($type);
        $companyLocationEsModel
            //经营范围
            ->SetAreaQuery($areaArr)
            ->searchFromEs();
        $xdIds = [];
        foreach($companyLocationEsModel->return_data['hits']['hits'] as $dataItem){
            $xdIds[] = $dataItem['_source']['companyid'] ;
        }
        $this->es->addMustTermsQuery('xd_id',$xdIds);
        return $this;
    }

    function SetAreaQueryV3($areaArr,$type =1 )
    {
        $t1 = microtime(true);

        if(
            empty($areaArr)
        ){
            return $this;
        }

        $companyLocationEsModel = new \App\ElasticSearch\Model\CompanyLocation($type);
        $companyLocationEsModel
            //经营范围
            ->SetAreaQuery($areaArr)
            ->addSize(1000)
            //->addSort('_score',"desc")
//            ->addSortV2('companyid',[
//                "order" => "desc",
//                "mode" => "avg"
//            ])
            ->searchFromEs();
        $xdIds = [0];
        foreach($companyLocationEsModel->return_data['hits']['hits'] as $dataItem){
            $xdIds[] = intval($dataItem['_source']['companyid']) ;
        }

        $whereArr = [
            ['field'=>'companyid','value'=>$xdIds,'operate'=>'IN',]
        ];
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    __CLASS__.__LINE__,
                    'whrere' => $whereArr,

                ]
            )
        );

        $res = CompanyBasic::findByConditionV3(
            $whereArr
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    __CLASS__.__LINE__,
                    'count_$res' => count($res),
                    'count_$xdIds' => count($xdIds),
                    'costs_seconds' => round(microtime(true) - $t1, 3) . ' seconds '
                ]
            )
        );
        $cods = ['0'];
        foreach ($res['data'] as $dataItem){
//            CommonService::getInstance()->log4PHP(
//                json_encode(
//                    [
//                        __CLASS__.__LINE__,
//                        'UNISCID' => $dataItem['UNISCID'],
//                    ]
//                )
//            );
            if($dataItem['UNISCID']){
                $cods[] = $dataItem['UNISCID'];
            }
        }
//        CommonService::getInstance()->log4PHP(
//            json_encode(
//                [
//                    __CLASS__.__LINE__,
//                    '$cods' => $cods,
//                ]
//            )
//        );
        //$this->es->addMustTermsQuery('property1',$cods);
        $matchedCnames = [];
        foreach($cods as $code){
            $code && $matchedCnames[] = $code;
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldPhraseQuery( 'property1' , $matchedCnames) ;

        // $this->query['query']['bool']['must'][]
        return $this;
    }

    function SetAreaQueryV5($areasLocations,$type =1 )
    {
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'SetAreaQueryV5' =>  $areasLocations
//            ])
//        );
        (!empty($areasLocations)) &&  $this->es->addGeoShapWithinV2( $areasLocations) ;
        // $this->query['query']['bool']['must'][]
        return $this;
    }


//    function getYieldDataForSouKe($areaArr,$type =1){
//
//        $startMemory = memory_get_usage();
//        $start = microtime(true);
//        $datas = [];
//
//        $size = 5000;
//        $offset = 0;
//        $nums =1;
//        $lastId = 0;
//
//        while ($totalNums > 0) {
//            if($totalNums<$size){
//                $size = $totalNums;
//            }
//
//            $companyLocationEsModel = new \App\ElasticSearch\Model\CompanyLocation($type);
//            $companyLocationEsModel
//                //经营范围
//                ->SetAreaQuery($areaArr)
//                ->searchFromEs();
//
//            if($lastId>0){
//                $companyLocationEsModel->addSearchAfterV1($lastId);
//            }
//
//            foreach($companyLocationEsModel->return_data['hits']['hits'] as $dataItem){
//                $lastId = $dataItem['_id'];
//
//                $nums ++;
//
//                yield $datas[] = $dataItem['_source'];
//            }
//
//            $totalNums -= $size;
//            $offset +=$size;
//        }
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
//                'generate data  done . costs seconds '=>microtime(true) - $start
//            ])
//        );
//    }

    function setReturnData($data)
    {

        $this->return_data = $data;
        return $this;
    }

    function formatEsDate()
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


    function formatEsMoney($field = 'reg_capital')
    {

        $this->return_data['hits']['hits'] = (new XinDongService())::formatEsMoney(
            $this->return_data['hits']['hits'],
            [
                $field,
            ]
        );

        $this->setReturnData($this->return_data)   ;
        return $this;
    }


    function searchFromEs($index = 'company_202207',$showLog = false)
    {

        $responseJson = (new XinDongService())->advancedSearch($this->es,$index);
        $responseArr = @json_decode($responseJson,true);
        $this->setReturnData($responseArr);
        if($showLog){
            CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
                    [
                        'hits' => count($responseArr['hits']['hits']),
                        'es_query' => $this->es->query,
                    ]
            ));
        }
        return $this;
    }

    function setDefault()
    {
        $this->es->setDefault() ;
        return $this;
    }

    function SetQueryByBasicSzjjid($szjjidsStr){
        // 数字经济及其核心产业 050101,050102 需要转换为四级分类 然后再搜索

        $szjjidsStr && $szjjidsArr = explode(',', $szjjidsStr);
        if($szjjidsArr){
            $szjjidsStr = implode("','", $szjjidsArr);
            $sql = "SELECT
                        nic_id 
                    FROM
                        nic_code
                    WHERE
                    nssc IN (
                        SELECT
                            nssc_id 
                        FROM
                            `szjj_nic_code` 
                        WHERE
                        szjj_id IN ( '$szjjidsStr' ) 
                    )
            ";

            $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_nic_code'));
            $nicIds = array_column($list, 'nic_id');

            if(!empty($nicIds)){
                foreach($nicIds as &$nicId){
                    if(
                        strlen($nicId) == 5 &&
                        substr($nicId, -1) == '0'
                    ){
                        $nicId = substr($nicId, 0, -1);
                    }
                }
                CommonService::getInstance()->log4PHP($nicIds);
                //$this->es->addMustShouldPhrasePrefixQuery( 'si_ji_fen_lei_code' , $nicIds) ;
                $this->es->addMustShouldPhrasePrefixQuery( 'NIC_ID' , $nicIds) ;
            }
        }

        return $this;
    }

    function SetQueryBySearchText($searchText){
        if($searchText){
            $matchedCnames = [
                [ 'field'=>'name' ,'value'=> $searchText],
                [ 'field'=>'shang_pin_data.name' ,'value'=> $searchText],
                [ 'field'=>'basic_opscope' ,'value'=> $searchText]
            ];
            $this->es->addMustShouldPhraseQueryV2($matchedCnames) ;
        }
        return $this;
    }

    function SetQueryBySearchTextV2($searchText){
        /**
        ->SetQueryBySearchTextV5( trim($this->request()->getRequestParam('un_name')),'ENTNAME')
        //不包含经营范围
        ->SetQueryBySearchTextV5( trim($this->request()->getRequestParam('un_basic_opscope')),'OPSCOPE')
        //不包含简介
        ->SetQueryBySearchTextV5( trim($this->request()->getRequestParam('un_jiejian')),'gong_si_jian_jie')
        //不包含简介
        ->SetQueryBySearchTextV5( trim($this->request()->getRequestParam('un_app')),'app')
         */
        if($searchText){
            $matchedCnames = [
                [ 'field'=>'ENTNAME' ,'value'=> $searchText],
                //[ 'field'=>'shang_pin_data.name' ,'value'=> $searchText],
                [ 'field'=>'OPSCOPE' ,'value'=> $searchText],
                [ 'field'=>'gong_si_jian_jie' ,'value'=> $searchText],
                [ 'field'=>'app' ,'value'=> $searchText],
            ];
            $this->es->addMustShouldPhraseQueryV2($matchedCnames) ;
        }
        return $this;
    }

    function SetQueryBySearchTextV3($searchText,$fileds = [
        'ENTNAME',
        'shang_pin_data.name',
        'OPSCOPE.name',
    ]){
        if($searchText){
            $matchedCnames = [
//                [ 'field'=>'ENTNAME' ,'value'=> $searchText],
//                [ 'field'=>'shang_pin_data.name' ,'value'=> $searchText],
//                [ 'field'=>'OPSCOPE' ,'value'=> $searchText]
            ];

            foreach ($fileds as $filed){
                $matchedCnames[] =  [ 'field'=>$filed ,'value'=> $searchText];
            }
            $this->es->addMustShouldPhraseQueryV2($matchedCnames) ;
        }
        return $this;
    }

    function SetQueryBySearchTextV4($searchText,$fileds = [
        'ENTNAME',
        'shang_pin_data.name',
        'OPSCOPE.name',
    ]){
        if($searchText){
            $matchedCnames = [
//                [ 'field'=>'ENTNAME' ,'value'=> $searchText],
//                [ 'field'=>'shang_pin_data.name' ,'value'=> $searchText],
//                [ 'field'=>'OPSCOPE' ,'value'=> $searchText]
            ];

            foreach ($fileds as $filed){
                $this->es->addMustNotMatchQuery($filed,$searchText) ;
            }

        }
        return $this;
    }

    function SetQueryBySearchTextV5($searchText,$filed){
        if($searchText){
            $this->es->addMustNotMatchQuery($filed,$searchText) ;
        }
        return $this;
    }


    function SetQueryBySearchCompanyIds($xdIds){
        if(!empty($xdIds)){
            $this->es->addMustTermsQuery('companyid',$xdIds);
        }
        return $this;
    }

    function SetQueryBySearchCompanyNames($entNames){
        if(!empty($entNames)){
            $this->es->addMustTermsQuery('ENTNAME',$entNames);
        }
        return $this;
    }



    function SetQueryByBusinessScope($basic_opscope,$business_scope_field_name = "business_scope"){
        // 需要按文本搜索的
        $addMustMatchPhraseQueryMap = [
            // basic_opscope: 经营范围
            $business_scope_field_name => $basic_opscope,
        ];
        if(!empty($addMustMatchPhraseQueryMap)){
            foreach($addMustMatchPhraseQueryMap as $field=>$value){
                $value && $this->es->addMustMatchPhraseQuery( $field , $value) ;
            }
        }

        return $this;
    }

    function SetQueryByBasicJlxxcyid($basicJlxxcyidStr){
        // 需要按文本搜索的
        $basicJlxxcyidStr && $basicJlxxcyidArr = explode(',',  $basicJlxxcyidStr);
        // CommonService::getInstance()->log4PHP('basicJlxxcyidArr '.json_encode($basicJlxxcyidArr));
        if(
            !empty($basicJlxxcyidArr)
        ){
            $siJiFenLeiDatas = \App\HttpController\Models\RDS3\ZlxxcyNicCode::create()
                ->where('zlxxcy_id', $basicJlxxcyidArr, 'IN')
                ->all();
            $matchedCnames = array_column($siJiFenLeiDatas, 'nic_id');
            CommonService::getInstance()->log4PHP('matchedCnames '.json_encode($matchedCnames));

            //$this->es->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $matchedCnames) ;
            $this->es->addMustShouldPhraseQuery( 'NIC_ID' , $matchedCnames) ;

        }
        return $this;
    }

    function SetQueryByShangPinData($appStr){
        // 搜索shang_pin_data 商品信息 appStr:五香;农庄
        $appStr && $appStrDatas = explode(';', $appStr);
        !empty($appStrDatas) && $this->es->addMustShouldPhraseQuery( 'shang_pin_data.name' , $appStrDatas) ;

        return $this;
    }

    function SetQueryByWeb($searchOptionArr){

        $web_values = []; //官网
        foreach($searchOptionArr as $item){
            if($item['pid'] == 70){
                $web_values = $item['value'];
            }
        }

        //必须存在官网
        foreach($web_values as $value){
            if($value){
                // $ElasticSearchService->addMustExistsQuery( 'web') ;
                $this->es->addMustRegexpQuery( 'web', ".+") ;

                break;
            }
        }
        return $this;
    }

    function SetQueryByApp($searchOptionArr){
        $app_values = []; //
        foreach($searchOptionArr as $item){
            if($item['pid'] == 80){
                $app_values = $item['value'];
            }
        }

        //必须存在APP
        foreach($app_values as $value){
            if($value){
                $this->es->addMustRegexpQuery( 'app', ".+") ;
                break;
            }
        }
        return $this;
    }

    function SetQueryByWuLiuQiYe($searchOptionArr){
        $app_values = []; //
        foreach($searchOptionArr as $item){
            if($item['pid'] == 90){
                $app_values = $item['value'];
            }
        }

        //必须存在APP
        foreach($app_values as $value){
            if($value){
                $this->es->addMustRegexpQuery( 'wu_liu_xin_xi', ".+") ;
                break;
            }
        }
        return $this;
    }

    function SetQueryByCompanyOrgType($searchOptionArr){
        $org_type_values = [];  // 企业类型
        foreach($searchOptionArr as $item){
            if($item['pid'] == 10){
                $org_type_values = $item['value'];
            }
        }

        $matchedCnames = [];
        foreach($org_type_values as $orgType){
            $orgType && $matchedCnames[] = (new XinDongService())->getCompanyOrgType()[$orgType];
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldPhraseQuery( 'company_org_type' , $matchedCnames) ;

        return $this;
    }

    //XXXXXXX
    function SetQueryByCompanyOrgTypeV2($searchOptionArr){
        $org_type_values = [];  // 企业类型
        foreach($searchOptionArr as $item){
            if($item['pid'] == 10){
                $org_type_values = $item['value'];
            }
        }

        $Sql = " select *  
                            from  
                        `admin_new_user` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));


        $matchedCnames = [];
        foreach($org_type_values as $orgType){
            $orgType && $matchedCnames[] = (new XinDongService())->getCompanyOrgType()[$orgType];
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldPhraseQuery( 'company_org_type' , $matchedCnames) ;

        return $this;
    }

    //
    function SetQueryByEstiblishTime($searchOptionArr){
        $estiblish_time_values = [];  // 成立年限
        foreach($searchOptionArr as $item){
            if($item['pid'] == 20){
                $estiblish_time_values = $item['value'];
            }

        }
        $matchedCnames = [];
        $map = [
            // 2年以内
            2 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year')), 'max' => date('Y-m-d')  ],
            // 2-5年
            5 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year'))  ],
            // 5-10年
            10 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year'))  ],
            // 10-15年
            15 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year'))  ],
            // 15-20年
            20 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year'))  ],
            // 20年以上
            25 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -100 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year'))  ],
        ];
        foreach($estiblish_time_values as $item){
            $item && $matchedCnames[] = $map[$item];
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldRangeQuery( 'estiblish_time' , $matchedCnames) ;

        return $this;
    }

    function SetQueryByEstiblishTimeV2($searchOptionArr){
        $estiblish_time_values = [];  // 成立年限
        foreach($searchOptionArr as $item){
            if($item['pid'] == 20){
                $estiblish_time_values = $item['value'];
            }

        }
        $matchedCnames = [];
        $map = [
            // 2年以内
            2 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year')), 'max' => date('Y-m-d')  ],
            // 2-5年
            5 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -2 year'))  ],
            // 5-10年
            10 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -5 year'))  ],
            // 10-15年
            15 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -10 year'))  ],
            // 15-20年
            20 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -15 year'))  ],
            // 20年以上
            25 => ['min'=>date('Y-m-d', strtotime(date('Y-m-01') . ' -100 year')), 'max' => date('Y-m-d', strtotime(date('Y-m-01') . ' -20 year'))  ],
        ];
        foreach($estiblish_time_values as $item){
            $item && $matchedCnames[] = $map[$item];
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldRangeQuery( 'ESDATE' , $matchedCnames) ;

        return $this;
    }

    function SetQueryByRegStatus($searchOptionArr){
        $reg_status_values = [];// 营业状态
        foreach($searchOptionArr as $item){
            if($item['pid'] == 30){
                $reg_status_values = $item['value'];
            }
        }

        $matchedCnames = [];
        foreach($reg_status_values as $item){
            $item && $matchedCnames[] = (new XinDongService())->getRegStatus()[$item];
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldPhraseQuery( 'reg_status' , $matchedCnames) ;


        return $this;
    }

    function SetQueryByRegStatusV2($searchOptionArr){
        $reg_status_values = [];// 营业状态
        foreach($searchOptionArr as $item){
            if($item['pid'] == 30){
                $reg_status_values = $item['value'];
            }
        }

        $matchedCnames = [];
        foreach($reg_status_values as $item){
            //$item && $matchedCnames[] = (new XinDongService())->getRegStatus()[$item];
            $item && $matchedCnames[] = $item;
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldPhraseQuery( 'ENTSTATUS' , $matchedCnames) ;


        return $this;
    }

    function SetQueryByRegCaptial($searchOptionArr){

        $reg_capital_values = [];  // 注册资本

        foreach($searchOptionArr as $item){

            if($item['pid'] == 40){
                $reg_capital_values = $item['value'];
            }

        }
        $map = XinDongService::getZhuCeZiBenMap();
        foreach($reg_capital_values as $item){
            $tmp = $map[$item]['epreg'];
            foreach($tmp as $tmp_item){
                $matchedCnames[] = $tmp_item;
            }
        }
        $map = XinDongService::getZhuCeZiBenMapV2();
        foreach($reg_capital_values as $item){
            $tmp = $map[$item]['epreg'];
            foreach($tmp as $tmp_item){
                $matchedCnames[] = $tmp_item;
            }
        }

        (!empty($matchedCnames)) && $this->es->addMustShouldRegexpQuery(
            'reg_capital' , $matchedCnames
        ) ;

        return $this;
    }

    function SetQueryByRegCaptialV2($searchOptionArr){

        $reg_capital_values = [];  // 注册资本

        foreach($searchOptionArr as $item){

            if($item['pid'] == 40){
                $reg_capital_values = $item['value'];
            }

        }
        $map = XinDongService::getZhuCeZiBenMap();
        foreach($reg_capital_values as $item){
            $tmp = $map[$item]['epreg'];
            foreach($tmp as $tmp_item){
                $matchedCnames[] = $tmp_item;
            }
        }
        $map = XinDongService::getZhuCeZiBenMapV2();
        foreach($reg_capital_values as $item){
            $tmp = $map[$item]['epreg'];
            foreach($tmp as $tmp_item){
                $matchedCnames[] = $tmp_item;
            }
        }

        (!empty($matchedCnames)) && $this->es->addMustShouldRegexpQuery(
            'REGCAP' , $matchedCnames
        ) ;

        return $this;
    }

    function SetQueryByTuanDuiRenShu($searchOptionArr){

        $tuan_dui_ren_shu_values = [];  // 团队人数

        foreach($searchOptionArr as $item){

            if($item['pid'] == 60){
                $tuan_dui_ren_shu_values = $item['value'];
            }
        }
        $map =  (new XinDongService())::getTuanDuiGuiMoMap();
        $matchedCnames = [];

        foreach($tuan_dui_ren_shu_values as $item){
            $tmp = $map[$item]['epreg'];
            foreach($tmp as $tmp_item){
                $matchedCnames[] = $tmp_item;
            }
        }
        (!empty($matchedCnames)) && $this->es->addMustShouldRegexpQuery(
            'tuan_dui_ren_shu' , $matchedCnames
        ) ;

        return $this;
    }
    function SetQueryByYingShouGuiMo($searchOptionArr){

        $ying_shou_gui_mo_values = [];  // 营收规模

        foreach($searchOptionArr as $item){
            if($item['pid'] == 50){
                $ying_shou_gui_mo_values = $item['value'];
            }
        }

        $map = XinDongService::getYingShouGuiMoMapV3();

        $matchedCnamesRaw = [];
        foreach($ying_shou_gui_mo_values as $item){
            $item && $matchedCnamesRaw[] = $map[$item];
        }
        $matchedCnames = [];
        foreach($matchedCnamesRaw as $items){
            foreach($items as $item){
                $matchedCnames[] = $item;
            }
        }

        (!empty($matchedCnames)) && $this->es->addMustShouldPhraseQuery( 'ying_shou_gui_mo' , $matchedCnames) ;

        return $this;
    }
    function SetQueryByYingShouGuiMoV2($searchOptionArr){

        $ying_shou_gui_mo_values = [];  // 营收规模

        foreach($searchOptionArr as $item){
            if($item['pid'] == 50){
                $ying_shou_gui_mo_values = $item['value'];
            }
        }

        $map = [
            5 => ['A1'], //微型
            10 => ['A2'], //小型C类
            15 => ['A3'],// 小型B类
            20 => ['A4'],// 小型A类
            25 => ['A5'],// 中型C类
            30 => ['A6'],// 中型B类
            40 => ['A7'],// 中型A类
            45 => ['A8'],// 大型C类
            50 => ['A9'],//大型B类
            60 => ['A10'],//大型A类，一般指规模在10亿以上，50亿以下
            65 => ['A11'],//'特大型C类，一般指规模在50亿以上，100亿以下'
            70 => ['A12'],//'特大型C类，一般指规模在50亿以上，100亿以下'
            80 => ['A13'],//'特大型C类，一般指规模在50亿以上，100亿以下'
        ];

        $matchedCnamesRaw = [];
        foreach($ying_shou_gui_mo_values as $item){
            $item && $matchedCnamesRaw[] = $map[$item];
        }
        $matchedCnames = [];
        foreach($matchedCnamesRaw as $items){
            foreach($items as $item){
                $matchedCnames[] = $item;
            }
        }

        (!empty($matchedCnames)) && $this->es->addMustShouldPhraseQuery( 'ying_shou_gui_mo' , $matchedCnames) ;

        return $this;
    }

    // addMustShouldPhrasePrefixQuery
    function SetQueryBySiJiFenLei($siJiFenLeiStrs,$field = 'si_ji_fen_lei_code'){
        if(substr($siJiFenLeiStrs, -1) == ','){
            $siJiFenLeiStrs = rtrim($siJiFenLeiStrs, ",");
        }
        $siJiFenLeiStrs && $siJiFenLeiArr = explode(',', $siJiFenLeiStrs);
        if(!empty($siJiFenLeiArr)){
            $this->es->addMustShouldPhraseQuery( $field , $siJiFenLeiArr) ;
        }
        return $this;
    }

    function SetQueryBySiJiFenLeiV2($nicIdStr,$field = 'NIC_ID'){
        if(substr($nicIdStr, -1) == ','){
            $nicIdStr = rtrim($nicIdStr, ",");
        }
        $nicIdStr && $nicIdArr = explode(',', $nicIdStr);
        if(!empty($nicIdArr)){
            $this->es->addMustShouldPhrasePrefixQuery( $field , $nicIdArr) ;
        }
        return $this;
    }

    function SetQueryByQiYeLeiXing($Str,$field = 'ENTTYPE'){
        if(substr($Str, -1) == ','){
            $Str = rtrim($Str, ",");
        }
        $Str && $Arr = explode(',', $Str);
        if(!empty($Arr)){
            $this->es->addMustShouldPhrasePrefixQuery( $field , $Arr) ;
        }
        return $this;
    }


    function SetQueryByCompanyStatus($companyStatus){
        if(substr($companyStatus, -1) == ','){
            $companyStatus = rtrim($companyStatus, ",");
        }
        $companyStatus && $siJiFenLeiArr = explode(',', $companyStatus);
        if(!empty($siJiFenLeiArr)){
            $this->es->addMustShouldPhraseQuery( 'ENTTYPE' , $siJiFenLeiArr) ;
        }

        return $this;
    }

    function SetQueryByCompanyType($companyType){
        if(substr($companyType, -1) == ','){
            $companyType = rtrim($companyType, ",");
        }
        $companyType && $siJiFenLeiArr = explode(',', $companyType);


        if(!empty($siJiFenLeiArr)){
            $this->es->addMustShouldPhraseQuery( 'ENTTYPE' , $siJiFenLeiArr) ;
        }
        return $this;
    }
    //function SetQueryByBasicRegionid($basiRegionidStr,$fieldName = 'reg_number'){
    function SetQueryByBasicRegionid($basiRegionidStr,$fieldName = 'DOMDISTRICT'){
        if(substr($basiRegionidStr, -1) == ','){
            $basiRegionidStr = rtrim($basiRegionidStr, ",");
        }
        $basiRegionidStr && $basiRegionidArr = explode(',',$basiRegionidStr);
        if(!empty($basiRegionidArr)){
            $this->es->addMustShouldPrefixQuery( $fieldName, $basiRegionidArr) ;
        }

        return $this;
    }


    /**
    事件里执行的
     */
    static function exportCompanyData($paramsData){
        $startMemory = memory_get_usage();
        $InitData =  DownloadSoukeHistory::findById( $paramsData['data_id'] );
        if(empty($InitData)){
            return [
                'msg' => 'wrong id',
                'data_id' => $paramsData['data_id'],
                '$paramsData' => $paramsData,
            ];
        }
        $InitData =  DownloadSoukeHistory::findById( $paramsData['data_id'] );

        $filename = '搜客导出_'.date('YmdHis').'.xlsx';
        $config=  [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];


        $fieldsArr = AdminUserSoukeConfig::getAllowedFieldsArrayV2($InitData['admin_id']);
        array_unshift($fieldsArr, 'companyid');  //在数组开头插入

        $filedCname = ['companyid'];
        $allFields = AdminUserSoukeConfig::getAllFieldsV2();
        foreach ($fieldsArr as $field){
            if($allFields[$field]){
                $filedCname[] = $allFields[$field];
            }
        }

        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, 'sheet');
        $fileHandle = $fileObject->getHandle();

        $format = new Format($fileHandle);
        $colorStyle = $format
            ->fontColor(Format::COLOR_ORANGE)
            ->border(Format::BORDER_DASH_DOT)
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $format = new Format($fileHandle);

        $alignStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $fileObject
            ->defaultFormat($colorStyle)
            ->header($filedCname)
            ->defaultFormat($alignStyle)
        ;

        $featureArr = json_decode($InitData['feature'],true);
        // get SouKe Config

        $tmpXlsxDatas = self::getYieldDataForSouKe($featureArr['total_nums'],$featureArr,$fieldsArr);
        foreach ($tmpXlsxDatas as $dataItem){
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$dataItem' => $dataItem
//                    ])
//                );
            $tmp = [
                //'xd_id'=>$dataItem['xd_id'],
            ];
            foreach ($fieldsArr as $field){
                $tmp[$field] = $dataItem[$field];
            }
            //$tmp['xd_id'] = $dataItem['xd_id'];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$dataItem' => $dataItem,
//                        '$featureArr'=>$featureArr,
//                        '$tmp'=>$tmp,
//                    ])
//                );
            $fileObject ->data([$tmp]);
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
            ])
        );

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource();

        $fileObject->output();

        //更新文件地址
        DownloadSoukeHistory::setFilePath($InitData['id'],'/Static/Temp/',$filename);

        //设置状态
        DownloadSoukeHistory::setStatus(
            $InitData['id'],DownloadSoukeHistory::$state_file_succeed
        );
    }
    /**
    事件里执行的
     */
    static function exportCompanyDataToCsv($paramsData){
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sou_ke_exportCompanyDataToCsv' =>[
                    'data_id'=> $paramsData['data_id'],
                    'start'=> 'start ',
                ]
            ])
        );
        $startMemory = memory_get_usage();
        $InitData =  DownloadSoukeHistory::findById( $paramsData['data_id'] );
        if(empty($InitData)){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'sou_ke_exportCompanyDataToCsv' => [
                        'data_id'=> $paramsData['data_id'],
                        'return_false'=> 'empty data ',
                    ]
                ])
            );

            return [
                'msg' => 'wrong id',
                'data_id' => $paramsData['data_id'],
                '$paramsData' => $paramsData,
            ];
        }

        $InitData =  DownloadSoukeHistory::findById( $paramsData['data_id'] );
        $filename = '搜客导出_'.date('YmdHis').'.csv';
        $f = fopen(OTHER_FILE_PATH.$filename, "w");
        //fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

        $fieldsArr = AdminUserSoukeConfig::getAllowedFieldsArrayV2($InitData['admin_id']);
        array_unshift($fieldsArr, 'companyid');  //在数组开头插入

        $filedCname = ['companyid'];
        $allFields = AdminUserSoukeConfig::getAllFieldsV2();
        foreach ($fieldsArr as $field){
            if($allFields[$field]){
                $filedCname[] = $allFields[$field];
            }
        }
        fputcsv($f, $filedCname);

        $featureArr = json_decode($InitData['feature'],true);

        $tmpXlsxDatas = self::getYieldDataForSouKe($featureArr['total_nums'],$featureArr,$fieldsArr);

        $i = 1;
        foreach ($tmpXlsxDatas as $dataItem){
            $i++;
            if($i%300==0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        'sou_ke_exportCompanyDataToCsv' =>[
                            'data_id'=> $paramsData['data_id'],
                            'generate_data_$i'=> $i,
                        ]
                    ])
                );
            }

            $tmp = [ ];
            foreach ($fieldsArr as $field){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '$field' =>[
                            '1'=> $dataItem[$field],
                            '2'=> $dataItem[$field]. "\t"
                        ]
                    ])
                );
                $tmp[$field] = iconv("UTF-8", "GB2312//IGNORE", $dataItem[$field]). "\t";;
                //$tmp[$field] = $dataItem[$field]. "\t";;
            }
            fputcsv($f, $tmp);
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sou_ke_exportCompanyDataToCsv' =>[
                    'data_id'=> $paramsData['data_id'],
                    'generate_data_$i'=> $i,
                    'memory use'=> round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
                ]
            ])
        );

        //更新文件地址
        DownloadSoukeHistory::setFilePath($InitData['id'],'/Static/OtherFile/',$filename);

        //设置状态
        DownloadSoukeHistory::setStatus(
            $InitData['id'],DownloadSoukeHistory::$state_file_succeed
        );
    }

    static function getYieldDataForSouKe($totalNums,$requestDataArr,$fieldsArr){
        $startMemory = memory_get_usage();
        $start = microtime(true);
        $searchOption = json_decode($requestDataArr['searchOption'],true);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'getYieldDataForSouKe' =>[
                    '$searchOption'=> $searchOption,
                    '$totalNums'=> $totalNums,
                    'start'=> 'start',
                ]
            ])
        );

        $datas = [];

        $size = 3500;
        $offset = 0;
        $nums =1;
        $lastId = 0;
        while ($totalNums > 0) {
            if($totalNums<$size){
                $size = $totalNums;
            }

            //区域搜索
            $areas_arr  = json_decode($requestDataArr['areas'],true) ;
            if(!empty($areas_arr)){

                //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
                $first = $areas_arr[0];;
                $last =  end($areas_arr);
                if(
                    strval($first[0])!= strval($last[0]) ||
                    strval($first[1])!= strval($last[1])
                ){
                    $areas_arr[] = $first;
                }
            }

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                //经营范围
                ->SetQueryByBusinessScope(trim($requestDataArr['basic_opscope']),"OPSCOPE")
                //数字经济及其核心产业
                ->SetQueryByBasicSzjjid(trim($requestDataArr['basic_szjjid']))
                // 搜索文案 智能搜索
                ->SetQueryBySearchTextV2( trim($requestDataArr['searchText']))
                // 搜索战略新兴产业
                ->SetQueryByBasicJlxxcyid(trim($requestDataArr['basic_jlxxcyid']))
                // 搜索shang_pin_data 商品信息 appStr:五香;农庄
                ->SetQueryByShangPinData( trim($requestDataArr['appStr']))
                //必须存在官网
                ->SetQueryByWeb($searchOption)
                ->SetAreaQueryV5($areas_arr,$requestDataArr['areas_type']?:1)
                //必须存在APP
                ->SetQueryByApp($searchOption)
                //必须是物流企业
                ->SetQueryByWuLiuQiYe($searchOption)
                // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
                ->SetQueryByCompanyOrgType($searchOption)
                // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
                ->SetQueryByEstiblishTimeV2($searchOption)
                // 营业状态   传过来的是 10  20  转换成文案后 去匹配
                ->SetQueryByRegStatusV2($searchOption)
                // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByRegCaptialV2($searchOption)
                // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByTuanDuiRenShu($searchOption)
                // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
                ->SetQueryByYingShouGuiMo($searchOption)
                //四级分类 basic_nicid: A0111,A0112,A0113,
                //->SetQueryBySiJiFenLei(trim($requestDataArr['basic_nicid']),'NIC_ID')
                ->SetQueryBySiJiFenLeiV2(trim($requestDataArr['basic_nicid']),'NIC_ID')
                //公司类型
                ->SetQueryByCompanyType(trim($requestDataArr['ENTTYPE']))
                //公司状态
                ->SetQueryByCompanyStatus(trim($requestDataArr['ENTSTATUS']))
                // 地区 basic_regionid: 110101,110102,
                ->SetQueryByBasicRegionid(trim($requestDataArr['basic_regionid']) ,'DOMDISTRICT')
                //不包含名称
                ->SetQueryBySearchTextV5( trim($requestDataArr['un_name']),'ENTNAME')
                //不包含经营范围
                ->SetQueryBySearchTextV5( trim($requestDataArr['un_basic_opscope']),'OPSCOPE')
                //不包含简介
                ->SetQueryBySearchTextV5( trim($requestDataArr['un_jiejian']),'gong_si_jian_jie')
                //不包含简介
                ->SetQueryBySearchTextV5( trim($requestDataArr['un_app']),'app')
                ->addSort("_id","desc")
                ->addSize($size)
                ->setSource($fieldsArr)
            ;


            if($lastId>0){
                $companyEsModel->addSearchAfterV1($lastId);
            }

            $showLog = true;
            $companyEsModel
                ->setDefault()
                ->searchFromEs('company_202209',$showLog)
                ->formatEsDate()
                // 格式化下金额
                ->formatEsMoney();

            foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
                if($nums%500==0){
//                    CommonService::getInstance()->log4PHP(json_encode(
//                            [
//                                'getYieldDataForSouKe' => [
//                                    'read from es $nums'=>$nums,
//                                    '$searchOption'=> $searchOption,
//                                    '$totalNums'=> $totalNums,
//                                ]
//                            ]
//                    ));
                }
                $lastId = $dataItem['_id'];
                $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmailV2($dataItem);
                $dataItem['_source']['LAST_DOM'] = $addresAndEmailData['LAST_DOM'];
                $dataItem['_source']['LAST_EMAIL'] = $addresAndEmailData['LAST_EMAIL'];

                $nums ++;
                // 官网
                $webStr = trim($dataItem['_source']['web']);
                if(!$webStr){
                    yield $datas[] = $dataItem['_source'];
                    continue;
                }
                $webArr = explode('&&&', $webStr);
                !empty($webArr) && $dataItem['_source']['web'] = end($webArr);

                yield $datas[] = $dataItem['_source'];
            }

            $totalNums -= $size;
            $offset +=$size;
        }

        CommonService::getInstance()->log4PHP(json_encode(
            [
                'getYieldDataForSouKe' => [
                    'read from es $nums'=>$nums,
                    '$searchOption'=> $searchOption,
                    '$totalNums'=> $totalNums,
                    'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                    'generate data  done . costs seconds '=>microtime(true) - $start
                ]
            ]
        ));
    }

    static  function  getNamesByText($page,$size,$searchText,$returnFullField = false){

        $companyEsModel = new \App\ElasticSearch\Model\Company();

        $offset  =  ($page-1)*$size;

        $companyEsModel
            // 搜索文案 智能搜索
            ->SetQueryBySearchTextV3( trim($searchText),[
                'ENTNAME',
            ])
            ->addSize($size)
            ->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs('company_202209')
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney('REGCAP')
        ;
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'es_hits_count' =>  count($companyEsModel->return_data['hits']['hits'])
            ])
        );
        if($returnFullField){
            return $companyEsModel->return_data;
        }
        $names = [];
        foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
            $names[] = $dataItem['_source']['ENTNAME'];
        }
        return $names;
    }

    static  function  serachFromEs(
        $requestData,
        $dataConfig = [ 'show_log' => true,]
    )
    {
        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($requestData['searchOption']);
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $requestData['size']??20;
        $page = $requestData['page']??1;
        $offset  =  ($page-1)*$size;

        //区域搜索
        $areas_arr  = json_decode($requestData['areas'],true) ;
        if(!empty($areas_arr)){

            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = $areas_arr[0];;
            $last =  end($areas_arr);
            if(
                strval($first[0])!= strval($last[0]) ||
                strval($first[1])!= strval($last[1])
            ){
                $areas_arr[] = $first;
            }
        }

        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($requestData['OPSCOPE']),"OPSCOPE")
            //数字经济及其核心产业
            ->SetQueryByBasicSzjjid(trim($requestData['basic_szjjid']))
            // 搜索文案 智能搜索
            ->SetQueryBySearchTextV2( trim($requestData['searchText']))
            ->SetQueryBySearchCompanyIds(
                !empty($requestData['companyids'])?
                explode(',',$requestData['companyids']):[]
            )
            ->SetQueryBySearchCompanyNames(
                !empty($requestData['entNames'])?
                    explode(',',$requestData['entNames']):[]
            )
            // 搜索战略新兴产业
            ->SetQueryByBasicJlxxcyid(trim($requestData['basic_jlxxcyid']))
            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
            ->SetQueryByShangPinData( trim($requestData['appStr']))
            //必须存在官网
            ->SetQueryByWeb($searchOptionArr)
            ->SetAreaQueryV5($areas_arr,$requestData['areas_type']?:1)
            //必须存在APP
            ->SetQueryByApp($searchOptionArr)
            //必须是物流企业
            ->SetQueryByWuLiuQiYe($searchOptionArr)
            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
            ->SetQueryByCompanyOrgType($searchOptionArr)
            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
            ->SetQueryByEstiblishTimeV2($searchOptionArr)
            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
            ->SetQueryByRegStatusV2($searchOptionArr)
            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByRegCaptialV2($searchOptionArr)
            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByTuanDuiRenShu($searchOptionArr)
            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
            ->SetQueryByYingShouGuiMo($searchOptionArr)
            //四级分类 basic_nicid: A0111,A0112,A0113,
            //->SetQueryBySiJiFenLei(trim($requestData['basic_nicid']),'NIC_ID')
            ->SetQueryBySiJiFenLeiV2(trim($requestData['basic_nicid']),'NIC_ID')
            //公司类型
            ->SetQueryByCompanyType(trim($requestData['ENTTYPE']))
            //公司状态
            ->SetQueryByCompanyStatus(trim($requestData['ENTSTATUS']))
            // 地区 basic_regionid: 110101,110102,
            ->SetQueryByBasicRegionid(trim($requestData['basic_regionid']) ,'DOMDISTRICT')
            ->addSize($size)
            ->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs('company_202209')
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney('REGCAP')
        ;

        if($dataConfig['show_log']){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'hits_count' =>  count($companyEsModel->return_data['hits']['hits'])
                ])
            );
        }

        foreach($companyEsModel->return_data['hits']['hits'] as &$dataItem){
            if($dataConfig['fill_short_name']){
                $dataItem['_source']['short_name'] =  CompanyBasic::findBriefName($dataItem['_source']['ENTNAME']);
            }
            if($dataConfig['fill_LAST_EMAIL']){
                $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmailV2($dataItem);
                $dataItem['_source']['LAST_DOM'] = $addresAndEmailData['LAST_DOM'];
                $dataItem['_source']['LAST_EMAIL'] = $addresAndEmailData['LAST_EMAIL'];
                $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntIdV2($dataItem['_source']['companyid']);
            }

            if($dataConfig['fill_tags']){
                // 添加tag
                $dataItem['_source']['tags'] = array_values(
                    (new XinDongService())::getAllTagesByData(
                        $dataItem['_source']
                    )
                );
            }


            $dataItem['_source']['ENTTYPE_CNAME'] =   '';
            $dataItem['_source']['ENTSTATUS_CNAME'] =  '';
            if(
                $dataItem['_source']['ENTTYPE'] &&
                ($dataConfig['fill_ENTTYPE_CNAME'])
            ){
                $dataItem['_source']['ENTTYPE_CNAME'] =   CodeCa16::findByCode($dataItem['_source']['ENTTYPE']);
            }
            if(
                $dataItem['_source']['ENTSTATUS'] &&
                ($dataConfig['fill_ENTSTATUS_CNAME'])
            ){
                $dataItem['_source']['ENTSTATUS_CNAME'] =   CodeEx02::findByCode($dataItem['_source']['ENTSTATUS']);
            }

            // 公司简介
            $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
            array_pop($tmpArr);
            $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
            foreach($tmpArr as $tmpItem_){
                // $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
                $dataItem['_source']['gong_si_jian_jie_data_arr'][] = $tmpItem_;
            }

            // 官网
            $webStr = trim($dataItem['_source']['web']);
            if(!$webStr){
                continue;
            }

            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);
        }

        return  [
            'total' => intval($companyEsModel->return_data['hits']['total']['value']),
            'data'=>$companyEsModel->return_data['hits']['hits'],
        ];
    }

    static  function SearchAfter($totalNums,$requestDataArr, $dataConfig = [ 'show_log' => true,]){

        $startMemory = memory_get_usage();
        $start = microtime(true);
        $searchOption = json_decode($requestDataArr['searchOption'],true);
        $datas = [];

//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                '$datas' => $datas
//            ])
//        );

        $size = 3500;
        $offset = 0;
        $nums =1;
        $lastId = 0;
        while ($totalNums > 0) {
            if($totalNums<$size){
                $size = $totalNums;
            }

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                //经营范围
                ->SetQueryByBusinessScope(trim($requestDataArr['OPSCOPE']),"OPSCOPE")
                //数字经济及其核心产业
                ->SetQueryByBasicSzjjid(trim($requestDataArr['basic_szjjid']))
                // 搜索文案 智能搜索
                ->SetQueryBySearchText( $requestDataArr['searchText'])
                ->SetQueryBySearchCompanyIds(
                    !empty($requestDataArr['companyids'])?
                        explode(',',$requestDataArr['companyids']):[]
                )
                ->SetQueryBySearchCompanyNames(
                    !empty($requestDataArr['entNames'])?
                        explode(',',$requestDataArr['entNames']):[]
                )
                // 搜索战略新兴产业
                ->SetQueryByBasicJlxxcyid( $requestDataArr['basic_jlxxcyid']   )
                // 搜索shang_pin_data 商品信息 appStr:五香;农庄
                ->SetQueryByShangPinData( $requestDataArr['appStr']  )
                //必须存在官网
                ->SetQueryByWeb($searchOption)
                //必须存在APP
                ->SetQueryByApp($searchOption)
                ->addSort('_id',"asc")
                //必须是物流企业
                ->SetQueryByWuLiuQiYe($searchOption)
                // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
                ->SetQueryByCompanyOrgType($searchOption)
                // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
                ->SetQueryByEstiblishTimeV2($searchOption)
                // 营业状态   传过来的是 10  20  转换成文案后 去匹配
                ->SetQueryByRegStatusV2($searchOption)
                // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByRegCaptial($searchOption)
                // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByTuanDuiRenShu($searchOption)
                // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
                ->SetQueryByYingShouGuiMo($searchOption)
                //四级分类 basic_nicid: A0111,A0112,A0113,
                //->SetQueryBySiJiFenLei($requestDataArr['basic_nicid'],'NIC_ID')
                ->SetQueryBySiJiFenLeiV2($requestDataArr['basic_nicid'],'NIC_ID')
                //公司类型
                ->SetQueryByCompanyType(trim($requestDataArr['ENTTYPE']))
                //公司状态
                ->SetQueryByCompanyStatus(trim($requestDataArr['ENTSTATUS']))
                // 地区 basic_regionid: 110101,110102,
                ->SetQueryByBasicRegionid($requestDataArr['basic_regionid']  ,'DOMDISTRICT' )
                ->addSize($size)
                //->setSource($fieldsArr)
                //设置默认值 不传任何条件 搜全部
            ;
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$lastId' => $lastId,
//                    '$totalNums' => $totalNums,
//                    '$fieldsArr' => $fieldsArr,
//                    'generate data  . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
//                    ' costs seconds '=>microtime(true) - $start
//                ])
//            );

            if($lastId>0){
                $companyEsModel->addSearchAfterV1($lastId);
            }
            // 格式化下日期和时间
            $companyEsModel
                ->setDefault()
                ->searchFromEs('company_202209')
                ->formatEsDate()
                // 格式化下金额
                ->formatEsMoney();

            foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
                $lastId = $dataItem['_id'];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$lastId' => $lastId
//                    ])
//                );

                if($dataConfig['fill_short_name']){
                    $dataItem['_source']['short_name'] =  CompanyBasic::findBriefName($dataItem['_source']['ENTNAME']);
                }
                if($dataConfig['fill_LAST_EMAIL']){
                    $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmailV2($dataItem);
                    $dataItem['_source']['LAST_DOM'] = $addresAndEmailData['LAST_DOM'];
                    $dataItem['_source']['LAST_EMAIL'] = $addresAndEmailData['LAST_EMAIL'];
                    $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntIdV2($dataItem['_source']['companyid']);
                }

                if($dataConfig['fill_tags']){
                    // 添加tag
                    $dataItem['_source']['tags'] = array_values(
                        (new XinDongService())::getAllTagesByData(
                            $dataItem['_source']
                        )
                    );
                }

                $dataItem['_source']['ENTTYPE_CNAME'] =   '';
                $dataItem['_source']['ENTSTATUS_CNAME'] =  '';
                if(
                    $dataItem['_source']['ENTTYPE'] &&
                    ($dataConfig['fill_ENTTYPE_CNAME'])
                ){
                    $dataItem['_source']['ENTTYPE_CNAME'] =   CodeCa16::findByCode($dataItem['_source']['ENTTYPE']);
                }
                if(
                    $dataItem['_source']['ENTSTATUS'] &&
                    ($dataConfig['fill_ENTSTATUS_CNAME'])
                ){
                    $dataItem['_source']['ENTSTATUS_CNAME'] =   CodeEx02::findByCode($dataItem['_source']['ENTSTATUS']);
                }

                // 公司简介
                $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
                array_pop($tmpArr);
                $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
                foreach($tmpArr as $tmpItem_){
                    // $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
                    $dataItem['_source']['gong_si_jian_jie_data_arr'][] = $tmpItem_;
                }

                // 官网
                $webStr = trim($dataItem['_source']['web']);
                if(!$webStr){
                    yield $datas[] = $dataItem['_source'];
                    continue;
                }

                $webArr = explode('&&&', $webStr);
                !empty($webArr) && $dataItem['_source']['web'] = end($webArr);


                $nums ++;
                yield $datas[] = $dataItem['_source'];
            }

            $totalNums -= $size;
            $offset +=$size;
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                'generate data  done . costs seconds '=>microtime(true) - $start
            ])
        );
    }
    static  function SearchAfterV3($totalNums,$requestDataArr, $dataConfig = [ 'show_log' => true,]){
        $startMemory = memory_get_usage();
        $start = microtime(true);
        $searchOption = json_decode($requestDataArr['searchOption'],true);
        $datas = [];

//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                '$datas' => $datas
//            ])
//        );

        $size = 3500;
        $offset = 0;
        $nums =1;
        $lastId = 0;
        while ($totalNums > 0) {
            if($totalNums<$size){
                $size = $totalNums;
            }

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                //经营范围
                ->SetQueryByBusinessScope(trim($requestDataArr['OPSCOPE']),"OPSCOPE")
                //数字经济及其核心产业
                ->SetQueryByBasicSzjjid(trim($requestDataArr['basic_szjjid']))
                // 搜索文案 智能搜索
                ->SetQueryBySearchText( $requestDataArr['searchText'])
                ->SetQueryBySearchCompanyIds(
                    !empty($requestDataArr['companyids'])?
                        explode(',',$requestDataArr['companyids']):[]
                )
                ->SetQueryBySearchCompanyNames(
                    !empty($requestDataArr['entNames'])?
                        explode(',',$requestDataArr['entNames']):[]
                )
                // 搜索战略新兴产业
                ->SetQueryByBasicJlxxcyid( $requestDataArr['basic_jlxxcyid']   )
                // 搜索shang_pin_data 商品信息 appStr:五香;农庄
                ->SetQueryByShangPinData( $requestDataArr['appStr']  )
                //必须存在官网
                ->SetQueryByWeb($searchOption)
                //必须存在APP
                ->SetQueryByApp($searchOption)
                ->addSort('_id',"asc")
                //必须是物流企业
                ->SetQueryByWuLiuQiYe($searchOption)
                // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
                ->SetQueryByCompanyOrgType($searchOption)
                // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
                ->SetQueryByEstiblishTimeV2($searchOption)
                // 营业状态   传过来的是 10  20  转换成文案后 去匹配
                ->SetQueryByRegStatusV2($searchOption)
                // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByRegCaptial($searchOption)
                // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByTuanDuiRenShu($searchOption)
                // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
                ->SetQueryByYingShouGuiMo($searchOption)
                //四级分类 basic_nicid: A0111,A0112,A0113,
                //->SetQueryBySiJiFenLei($requestDataArr['basic_nicid'],'NIC_ID')
                ->SetQueryBySiJiFenLeiV2($requestDataArr['basic_nicid'],'NIC_ID')
                //公司类型
                ->SetQueryByCompanyType(trim($requestDataArr['ENTTYPE']))
                //公司状态
                ->SetQueryByCompanyStatus(trim($requestDataArr['ENTSTATUS']))
                // 地区 basic_regionid: 110101,110102,
                ->SetQueryByBasicRegionid($requestDataArr['basic_regionid']  ,'DOMDISTRICT' )
                ->addSize($size)
                //->setSource($fieldsArr)
                //设置默认值 不传任何条件 搜全部
            ;
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$lastId' => $lastId,
//                    '$totalNums' => $totalNums,
//                    '$fieldsArr' => $fieldsArr,
//                    'generate data  . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
//                    ' costs seconds '=>microtime(true) - $start
//                ])
//            );

            if($lastId>0){
                $companyEsModel->addSearchAfterV1($lastId);
            }
            // 格式化下日期和时间
            $companyEsModel
                ->setDefault()
                ->searchFromEs('company_202209')
                ->formatEsDate()
                // 格式化下金额
                ->formatEsMoney();
            yield $datas[] = $companyEsModel->return_data['hits']['hits'];

            $lastItem = end($companyEsModel->return_data['hits']['hits']);
            $lastId = $lastItem['_id'];

            $totalNums -= $size;
            $offset +=$size;
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                'generate data  done . costs seconds '=>microtime(true) - $start
            ])
        );
    }
    static  function SearchAfterV2($totalNums,$requestDataArr, $dataConfig = [ 'show_log' => true,]){

        $startMemory = memory_get_usage();
        $start = microtime(true);
        $searchOption = json_decode($requestDataArr['searchOption'],true);
        $datas = [];

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'es_company_SearchAfterV2' =>
                [
                    '$searchOption'=>$searchOption,
                ]
            ])
        );

        $size = 3500;
        $offset = 0;
        $nums =1;
        $lastId = 0;
        while ($totalNums > 0) {
            if($totalNums<$size){
                $size = $totalNums;
            }

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                //经营范围
                ->SetQueryByBusinessScope(trim($requestDataArr['OPSCOPE']),"OPSCOPE")
                //数字经济及其核心产业
                ->SetQueryByBasicSzjjid(trim($requestDataArr['basic_szjjid']))
                // 搜索文案 智能搜索
                ->SetQueryBySearchText( $requestDataArr['searchText'])
                ->SetQueryBySearchCompanyIds(
                    !empty($requestDataArr['companyids'])?
                        explode(',',$requestDataArr['companyids']):[]
                )
                ->SetQueryBySearchCompanyNames(
                    !empty($requestDataArr['entNames'])?
                        explode(',',$requestDataArr['entNames']):[]
                )
                // 搜索战略新兴产业
                ->SetQueryByBasicJlxxcyid( $requestDataArr['basic_jlxxcyid']   )
                // 搜索shang_pin_data 商品信息 appStr:五香;农庄
                ->SetQueryByShangPinData( $requestDataArr['appStr']  )
                //必须存在官网
                ->SetQueryByWeb($searchOption)
                //必须存在APP
                ->SetQueryByApp($searchOption)
                ->addSort('_id',"asc")
                //必须是物流企业
                ->SetQueryByWuLiuQiYe($searchOption)
                // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
                ->SetQueryByCompanyOrgType($searchOption)
                // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
                ->SetQueryByEstiblishTimeV2($searchOption)
                // 营业状态   传过来的是 10  20  转换成文案后 去匹配
                ->SetQueryByRegStatusV2($searchOption)
                // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByRegCaptial($searchOption)
                // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
                ->SetQueryByTuanDuiRenShu($searchOption)
                // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
                ->SetQueryByYingShouGuiMo($searchOption)
                //四级分类 basic_nicid: A0111,A0112,A0113,
                //->SetQueryBySiJiFenLei($requestDataArr['basic_nicid'],'NIC_ID')
                ->SetQueryBySiJiFenLeiV2($requestDataArr['basic_nicid'],'NIC_ID')
                //公司类型
                ->SetQueryByCompanyType(trim($requestDataArr['ENTTYPE']))
                //公司状态
                ->SetQueryByCompanyStatus(trim($requestDataArr['ENTSTATUS']))
                // 地区 basic_regionid: 110101,110102,
                ->SetQueryByBasicRegionid($requestDataArr['basic_regionid'] ,'DOMDISTRICT' )
                ->addSize($size)
                //->setSource($fieldsArr)
                //设置默认值 不传任何条件 搜全部
            ;
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$lastId' => $lastId,
//                    '$totalNums' => $totalNums,
//                    '$fieldsArr' => $fieldsArr,
//                    'generate data  . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
//                    ' costs seconds '=>microtime(true) - $start
//                ])
//            );

            if($lastId>0){
                $companyEsModel->addSearchAfterV1($lastId);
            }
            // 格式化下日期和时间
            $companyEsModel
                ->setDefault()
                ->searchFromEs('company_202209')
                ->formatEsDate()
                // 格式化下金额
                ->formatEsMoney();

            foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
                $lastId = $dataItem['_id'];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$lastId' => $lastId
//                    ])
//                );

                if($dataConfig['fill_short_name']){
                    $dataItem['_source']['short_name'] =  CompanyBasic::findBriefName($dataItem['_source']['ENTNAME']);
                }
                if($dataConfig['fill_LAST_EMAIL']){
                    $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmailV2($dataItem);
                    $dataItem['_source']['LAST_DOM'] = $addresAndEmailData['LAST_DOM'];
                    $dataItem['_source']['LAST_EMAIL'] = $addresAndEmailData['LAST_EMAIL'];
                    $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntIdV2($dataItem['_source']['companyid']);
                }

                if($dataConfig['fill_tags']){
                    // 添加tag
                    $dataItem['_source']['tags'] = array_values(
                        (new XinDongService())::getAllTagesByData(
                            $dataItem['_source']
                        )
                    );
                }

                $dataItem['_source']['ENTTYPE_CNAME'] =   '';
                $dataItem['_source']['ENTSTATUS_CNAME'] =  '';
                if(
                    $dataItem['_source']['ENTTYPE'] &&
                    ($dataConfig['fill_ENTTYPE_CNAME'])
                ){
                    $dataItem['_source']['ENTTYPE_CNAME'] =   CodeCa16::findByCode($dataItem['_source']['ENTTYPE']);
                }
                if(
                    $dataItem['_source']['ENTSTATUS'] &&
                    ($dataConfig['fill_ENTSTATUS_CNAME'])
                ){
                    $dataItem['_source']['ENTSTATUS_CNAME'] =   CodeEx02::findByCode($dataItem['_source']['ENTSTATUS']);
                }

                // 公司简介
                $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
                array_pop($tmpArr);
                $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
                foreach($tmpArr as $tmpItem_){
                    // $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
                    $dataItem['_source']['gong_si_jian_jie_data_arr'][] = $tmpItem_;
                }

                // 官网
                $webStr = trim($dataItem['_source']['web']);
                if(!$webStr){
                     $datas[] = $dataItem['_source'];
                    continue;
                }

                $webArr = explode('&&&', $webStr);
                !empty($webArr) && $dataItem['_source']['web'] = end($webArr);


                $nums ++;
                $datas[] = $dataItem['_source'];
            }

            $totalNums -= $size;
            $offset +=$size;
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'es_company_SearchAfterV2_end' =>
                    [
                        'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                        'generate data  done . costs seconds '=>microtime(true) - $start
                    ]
            ])
        );
        return $datas;
    }

}
