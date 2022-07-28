<?php

namespace App\ElasticSearch\Model;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
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
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'SetAreaQueryV5' =>  $areasLocations
            ])
        );
        (!empty($areasLocations)) &&  $this->es->addGeoShapWithinV2( $areasLocations) ;
        // $this->query['query']['bool']['must'][]
        return $this;
    }


    function getYieldDataForSouKe($areaArr,$type =1){

        $startMemory = memory_get_usage();
        $start = microtime(true);
        $datas = [];

        $size = 5000;
        $offset = 0;
        $nums =1;
        $lastId = 0;

        while ($totalNums > 0) {
            if($totalNums<$size){
                $size = $totalNums;
            }

            $companyLocationEsModel = new \App\ElasticSearch\Model\CompanyLocation($type);
            $companyLocationEsModel
                //经营范围
                ->SetAreaQuery($areaArr)
                ->searchFromEs();

            if($lastId>0){
                $companyLocationEsModel->addSearchAfterV1($lastId);
            }

            foreach($companyLocationEsModel->return_data['hits']['hits'] as $dataItem){
                $lastId = $dataItem['_id'];

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


    function searchFromEs($index = 'company_202207')
    {

        $responseJson = (new XinDongService())->advancedSearch($this->es,$index);
        $responseArr = @json_decode($responseJson,true);
        $this->setReturnData($responseArr);
        CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
                [
                    // 'hits' => $responseArr['hits']['hits'],
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
                $this->es->addMustShouldPhrasePrefixQuery( 'si_ji_fen_lei_code' , $nicIds) ;
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
        if($searchText){
            $matchedCnames = [
                [ 'field'=>'ENTNAME' ,'value'=> $searchText],
                [ 'field'=>'shang_pin_data.name' ,'value'=> $searchText],
                [ 'field'=>'OPSCOPE' ,'value'=> $searchText]
            ];
            $this->es->addMustShouldPhraseQueryV2($matchedCnames) ;
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

            $this->es->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $matchedCnames) ;

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

    function SetQueryBySiJiFenLei($siJiFenLeiStrs){
        $siJiFenLeiStrs && $siJiFenLeiArr = explode(',', $siJiFenLeiStrs);
        if(!empty($siJiFenLeiArr)){
            $this->es->addMustShouldPhraseQuery( 'si_ji_fen_lei_code' , $siJiFenLeiArr) ;
        }

        return $this;
    }
    function SetQueryByBasicRegionid($basiRegionidStr){
        $basiRegionidStr && $basiRegionidArr = explode(',',$basiRegionidStr);
        if(!empty($basiRegionidArr)){
            $this->es->addMustShouldPrefixQuery( 'reg_number' , $basiRegionidArr) ;
        }

        return $this;
    }
}
