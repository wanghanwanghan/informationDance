<?php

namespace App\HttpController\Business\AdminV2\Mrxd\SouKe;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class SouKeController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    /*
     * 筛选选型
     * */
    function getSearchOption(): bool
    {
        $searchOptionArr = (new XinDongService())->getSearchOption([]);
        return $this->writeJson(200, null, $searchOptionArr, '成功', false, []);
    }

    //股东关系图
    function getCompanyInvestor(): bool
    {
        //
        $requestData =  $this->getRequestData();
        $res = CompanyInvestor::findByCompanyId(
            $requestData['company_id']
         );
        foreach ($res as &$data){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'getCompanyInvestor_data_item'=>$data
                ])
            );
            $name = CompanyInvestor::getInvestorName( $data['investor_id'], $data['investor_type']);
            $data['name'] = $name;
        }

        return $this->writeJson(200, null, $res, '成功', false, []);

    }


    /*
     * 高级搜索
     * */
    function advancedSearch(): bool
    {
        $requestData =  $this->getRequestData();

        if(substr($requestData['basic_nicid'], -1) == ','){
            $requestData['basic_nicid'] = rtrim($requestData['basic_nicid'], ",");
        }

        if(substr($requestData['basic_regionid'], -1) == ','){
            $requestData['basic_regionid'] = rtrim($requestData['basic_regionid'], ",");
        }

        if(substr($requestData['basic_jlxxcyid'], -1) == ','){
            $requestData['basic_jlxxcyid'] = rtrim($requestData['basic_jlxxcyid'], ",");
        }
 

        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;

        //区域搜索
        $areas_arr  = json_decode($requestData['areas'],true) ;
         if(!empty($areas_arr)){
//             CommonService::getInstance()->log4PHP(
//                 json_encode([
//                     __CLASS__.__FUNCTION__ .__LINE__,
//                     '$areas_arr' => $areas_arr,
//                 ])
//             );

             //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
             $first = $areas_arr[0];;
             $last =  end($areas_arr);
             if(
                 strval($first[0])!= strval($last[0]) ||
                 strval($first[1])!= strval($last[1])
             ){
                 $areas_arr[] = $first;
//                 CommonService::getInstance()->log4PHP(
//                     json_encode([
//                         __CLASS__.__FUNCTION__ .__LINE__,
//                         'add_new_first' => true,
//                         '$areas_arr' => $areas_arr,
//                         'strval($first[0])' => strval($first[0]),
//                         'strval($last[0])'=>strval($last[0]),
//                     ])
//                 );
             }else{
//                 CommonService::getInstance()->log4PHP(
//                     json_encode([
//                         __CLASS__.__FUNCTION__ .__LINE__,
//                         'add_new_first' => false,
//                         '$areas_arr' => $areas_arr,
//                         'strval($first[0])' => strval($first[0]),
//                         'strval($last[0])'=>strval($last[0]),
//                     ])
//                 );
             }
         }
        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('basic_opscope')))
            //数字经济及其核心产业
            ->SetQueryByBasicSzjjid(trim($this->request()->getRequestParam('basic_szjjid')))
            // 搜索文案 智能搜索
            ->SetQueryBySearchText( trim($this->request()->getRequestParam('searchText')))
            // 搜索战略新兴产业
            ->SetQueryByBasicJlxxcyid(trim($this->request()->getRequestParam('basic_jlxxcyid')))
            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
            ->SetQueryByShangPinData( trim($this->request()->getRequestParam('appStr')))
            //必须存在官网
            ->SetQueryByWeb($searchOptionArr)
            ->SetAreaQueryV3($areas_arr,$requestData['areas_type']?:1)
            //必须存在APP
            ->SetQueryByApp($searchOptionArr)
            //必须是物流企业
            ->SetQueryByWuLiuQiYe($searchOptionArr)
            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
            ->SetQueryByCompanyOrgType($searchOptionArr)
            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
            ->SetQueryByEstiblishTime($searchOptionArr)
            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
            ->SetQueryByRegStatus($searchOptionArr)
            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByRegCaptial($searchOptionArr)
            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByTuanDuiRenShu($searchOptionArr)
            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
            ->SetQueryByYingShouGuiMo($searchOptionArr)
            //四级分类 basic_nicid: A0111,A0112,A0113,
            ->SetQueryBySiJiFenLei(trim($this->request()->getRequestParam('basic_nicid')))
            // 地区 basic_regionid: 110101,110102,
            ->SetQueryByBasicRegionid( trim($this->request()->getRequestParam('basic_regionid')))
            ->addSize($size)
            ->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs()
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney()
        ;

        foreach($companyEsModel->return_data['hits']['hits'] as &$dataItem){
            $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmail($dataItem);
            $dataItem['_source']['last_postal_address'] = $addresAndEmailData['last_postal_address'];
            $dataItem['_source']['last_email'] = $addresAndEmailData['last_email'];

            $dataItem['_source']['logo'] =  (new XinDongService())->getLogoByEntId($dataItem['_source']['xd_id']);

            // 添加tag
            $dataItem['_source']['tags'] = array_values(
                (new XinDongService())::getAllTagesByData(
                    $dataItem['_source']
                )
            );

            // 官网
            $webStr = trim($dataItem['_source']['web']);
            if(!$webStr){
                continue;
            }
            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);
        }

        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>$size,
                'total' => intval($companyEsModel->return_data['hits']['total']['value']),
                'totalPage' => (int)floor(intval($companyEsModel->return_data['hits']['total']['value'])/
                    ($size)),

            ]
            , $companyEsModel->return_data['hits']['hits'], '成功', true, []);
    }
    function advancedSearchOption(): bool
    {
        $requestData =  $this->getRequestData();

        if(substr($requestData['basic_nicid'], -1) == ','){
            $requestData['basic_nicid'] = rtrim($requestData['basic_nicid'], ",");
        }

        if(substr($requestData['basic_regionid'], -1) == ','){
            $requestData['basic_regionid'] = rtrim($requestData['basic_regionid'], ",");
        }

        if(substr($requestData['basic_jlxxcyid'], -1) == ','){
            $requestData['basic_jlxxcyid'] = rtrim($requestData['basic_jlxxcyid'], ",");
        }


        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        //区域搜索
        $areas_arr  = json_decode($requestData['areas'],true) ;
        if(!empty($areas_arr)){
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    '$areas_arr' => $areas_arr,
//                ])
//            );

            //区域多边形搜索：要闭合：即最后一个点要和最后一个点重合
            $first = $areas_arr[0];;
            $last =  end($areas_arr);
            if(
                strval($first[0])!= strval($last[0]) ||
                strval($first[1])!= strval($last[1])
            ){
                $areas_arr[] = $first;
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'add_new_first' => true,
//                        '$areas_arr' => $areas_arr,
//                        'strval($first[0])' => strval($first[0]),
//                        'strval($last[0])'=>strval($last[0]),
//                    ])
//                );
            }else{
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'add_new_first' => false,
//                        '$areas_arr' => $areas_arr,
//                        'strval($first[0])' => strval($first[0]),
//                        'strval($last[0])'=>strval($last[0]),
//                    ])
//                );
            }
        }
        $companyEsModel
            //经营范围
            ->SetQueryByBusinessScope(trim($this->request()->getRequestParam('basic_opscope')))
            //数字经济及其核心产业
            ->SetQueryByBasicSzjjid(trim($this->request()->getRequestParam('basic_szjjid')))
            // 搜索文案 智能搜索
            ->SetQueryBySearchText( trim($this->request()->getRequestParam('searchText')))
            // 搜索战略新兴产业
            ->SetQueryByBasicJlxxcyid(trim($this->request()->getRequestParam('basic_jlxxcyid')))
            // 搜索shang_pin_data 商品信息 appStr:五香;农庄
            ->SetQueryByShangPinData( trim($this->request()->getRequestParam('appStr')))
            //必须存在官网
            ->SetQueryByWeb($searchOptionArr)
            ->SetAreaQueryV3($areas_arr,$requestData['areas_type']?:1)
            //必须存在APP
            ->SetQueryByApp($searchOptionArr)
            //必须是物流企业
            ->SetQueryByWuLiuQiYe($searchOptionArr)
            // 企业类型 :传过来的是10 20 转换成对应文案 然后再去搜索
            ->SetQueryByCompanyOrgType($searchOptionArr)
            // 成立年限  ：传过来的是 10  20 30 转换成最小值最大值范围后 再去搜索
            ->SetQueryByEstiblishTime($searchOptionArr)
            // 营业状态   传过来的是 10  20  转换成文案后 去匹配
            ->SetQueryByRegStatus($searchOptionArr)
            // 注册资本 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByRegCaptial($searchOptionArr)
            // 团队人数 传过来的是 10 20 转换成最大最小范围后 再去搜索
            ->SetQueryByTuanDuiRenShu($searchOptionArr)
            // 营收规模  传过来的是 10 20 转换成对应文案后再去匹配
            ->SetQueryByYingShouGuiMo($searchOptionArr)
            //四级分类 basic_nicid: A0111,A0112,A0113,
            ->SetQueryBySiJiFenLei(trim($this->request()->getRequestParam('basic_nicid')))
            // 地区 basic_regionid: 110101,110102,
            ->SetQueryByBasicRegionid( trim($this->request()->getRequestParam('basic_regionid')))
            //->addSize($size)
            //->addFrom($offset)
            //设置默认值 不传任何条件 搜全部
            ->setDefault()
            ->searchFromEs()
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney()
        ;

        $rawOptions = (new XinDongService())->getSearchOption();
        $newOptions = [];
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'search_optionXXX' => $companyEsModel->return_data
//            ])
//        );
        foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
            $has_web = $dataItem['_source']['web']?'有':'无';

            $has_app = $dataItem['_source']['app']?'有':'无';

            $has_wu_liu_xin_xi = $dataItem['_source']['wu_liu_xin_xi']?'是':'否';

            foreach ($rawOptions as $key => $configs){
                $newOptions[$key]['pid'] = $configs['pid']; //
                $newOptions[$key]['desc'] = $configs['desc']; //
                $newOptions[$key]['detail'] = $configs['detail']; //
                $newOptions[$key]['key'] = $configs['key']; //
                $newOptions[$key]['type'] = $configs['type']; //
                // 企业类型
                if($configs['pid'] == 10){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['company_org_type']){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'company_org_type_matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['company_org_type'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'company_org_type_matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['company_org_type'],
//                                ])
//                            );
                        }
                    };
                }
                //营业状态
                if($configs['pid'] == 30){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['reg_status']){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_status_matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['reg_status'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_status_matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'company_org_type'=>$dataItem['_source']['reg_status'],
//                                ])
//                            );
                        }
                    };
                }
                //官网
                if($configs['pid'] == 70){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_web){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'web matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'web'=>$dataItem['_source']['web'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'web matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'web'=>$dataItem['_source']['web'],
//                                ])
//                            );
                        }
                    };
                }

                //有无APP
                if($configs['pid'] == 80){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_app){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'app matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'app'=>$dataItem['_source']['app'],
//                                ])
//                            );
                            //break;
                        }else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'app matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'app'=>$dataItem['_source']['app'],
//                                ])
//                            );
                        }

                    };
                }
                //是否物流企业
                if($configs['pid'] == 90){
                    foreach ($configs['data'] as $subKey => $item){
                        if($item['cname'] == $has_wu_liu_xin_xi){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'wu_liu_xin_xi matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'wu_liu_xin_xi'=>$dataItem['_source']['wu_liu_xin_xi'],
//                                ])
//                            );
                            // break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'wu_liu_xin_xi matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'cname'=>$item['cname'],
//                                    'wu_liu_xin_xi'=>$dataItem['_source']['wu_liu_xin_xi'],
//                                ])
//                            );
                        }
                    }
                }

                //成立年限
                if($configs['pid'] == 20){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['estiblish_year_nums'] >= $item['min'] &&
                            $dataItem['_source']['estiblish_year_nums'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'estiblish_year_nums matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'estiblish_year_nums'=>$dataItem['_source']['estiblish_year_nums'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'estiblish_year_nums matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'estiblish_year_nums'=>$dataItem['_source']['estiblish_year_nums'],
//                                ])
//                            );
                        }
                    };
                }

                //注册资本
                if($configs['pid'] == 40){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['reg_capital'] >= $item['min'] &&
                            $dataItem['_source']['reg_capital'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_capital matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['reg_capital'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'reg_capital matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['reg_capital'],
//                                ])
//                            );
                        }
                    };
                }
                //营收规模
                if($configs['pid'] == 50){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['ying_shou_gui_mo'] >= $item['min'] &&
                            $dataItem['_source']['ying_shou_gui_mo'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'ying_shou_gui_mo matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['ying_shou_gui_mo'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'ying_shou_gui_mo matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'reg_capital'=>$dataItem['_source']['ying_shou_gui_mo'],
//                                ])
//                            );
                        }
                    };
                }
                //企业规模
                if($configs['pid'] == 60){
                    foreach ($configs['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['tuan_dui_ren_shu'] >= $item['min'] &&
                            $dataItem['_source']['tuan_dui_ren_shu'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'tuan_dui_ren_shu matched' => true,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'tuan_dui_ren_shu'=>$dataItem['_source']['tuan_dui_ren_shu'],
//                                ])
//                            );
                            //break;
                        }
                        else{
//                            CommonService::getInstance()->log4PHP(
//                                json_encode([
//                                    __CLASS__.__FUNCTION__ .__LINE__,
//                                    'tuan_dui_ren_shu matched' => false,
//                                    '$subKey' => $subKey,
//                                    '$item' => $item,
//                                    'tuan_dui_ren_shu'=>$dataItem['_source']['tuan_dui_ren_shu'],
//                                ])
//                            );
                        }
                    };
                }
            }
        }

        $newOptionsV2 = [];
        foreach ($newOptions as $option){
            if(empty($option['data'])){
                continue;
            }
            $newOptionsV2[] = $option;
        }
        return $this->writeJson(200,
            [  ]
            , $newOptionsV2, '成功', true, []);
    }

    function getStaffInfo(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $model = \App\HttpController\Models\RDS3\CompanyStaff::create()
            ->where('company_id', $companyId)->page($page)->withTotalCount();
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount();

        foreach($retData as &$dataItem){
            $humanModel = \App\HttpController\Models\RDS3\Human::create()
                ->where('id', $dataItem['staff_id'])->get();
            $dataItem['name'] = $humanModel->name;
        }
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);

    }

    function getCompanyBasicInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业ID)');
        }

        $retData  = Company::create()->where('id', $companyId)->get();
        $retData = (new XinDongService())::formatObjDate(
            $retData,
            [
                'estiblish_time',
                'from_time',
                'to_time',
                'approved_time',
            ]
        );
        $retData = (new XinDongService())::formatObjMoney(
            $retData,
            [
                'reg_capital',
                'actual_capital',
            ]
        );

        $retData['logo'] =  (new XinDongService())->getLogoByEntId($retData['id']);
        $res = (new XinDongService())->getEsBasicInfo($companyId);
        $retData['last_postal_address'] = $res['last_postal_address'];
        $retData['last_email'] = $res['last_email'];
        return $this->writeJson(200, ['total' => 1], $retData, '成功', true, []);
    }

    function getCpwsList(): bool
    {
        $page = $this->getRequestData('page');
        $page = $page > 0? $page :1;
        $pageSize = $this->getRequestData('size');
        $pageSize = $pageSize > 0? $pageSize :10;
        $postData = [
            'entName' => trim($this->getRequestData('entName')),
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        if (!$postData['entName']) {
            return $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $res = (new LongXinService())->setCheckRespFlag(true)->getCpwsList($postData);
        return   $this->writeJson(200,  $res['paging'],  $res['result'], '成功', true, []);
    }

    function getCpwsDetail(): bool
    {
        $postData = [
            'mid' => $this->getRequestData('mid'),
        ];

        $res = (new LongXinService())->setCheckRespFlag(true)->getCpwsDetail($postData);

        return   $this->writeJson(200,  ['total' => 1],  $res['result'], '成功', true, []);
        // return $this->checkResponse($res);
    }


    function getKtggList(): bool
    {
        $page = $this->getRequestData('page');
        $page = $page > 0? $page :1;
        $pageSize = $this->getRequestData('size');
        $pageSize = $pageSize > 0? $pageSize :10;

        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $page,
            'pageSize' => $pageSize,
        ];

        $res = (new LongXinService())->setCheckRespFlag(true)->getKtggList($postData);

        return   $this->writeJson(200,  $res['paging'],  $res['result'], '成功', true, []);
        // return $this->checkResponse($res);
    }


    function getKtggDetail(): ?bool
    {
        $postData = [
            'mid' => $this->getRequestData('mid'),
        ];

        $res = (new LongXinService())->setCheckRespFlag(true)->getKtggDetail($postData);

        return   $this->writeJson(200,   ['total' => 1], $res['result'], '成功', true, []);
        // return $this->checkResponse($res);
    }

    function getHighTecQualifications(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $model = \App\HttpController\Models\RDS3\XdHighTec::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount();
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    }

    function getDengLingQualifications(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $model = \App\HttpController\Models\RDS3\XdDl::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount();
        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    }

    function getIsoQualifications(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $model = \App\HttpController\Models\RDS3\XdDlRzGlTx::create()
            ->where('xd_id', $companyId)->page($page)->withTotalCount();
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount();

        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);

    }

    function getEmploymenInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $retData  =\App\HttpController\Models\RDS3\TuanDuiGuiMo::create()->where('xd_id', $companyId)->get();

        return $this->writeJson(200, ['total' => 1], $retData, '成功', true, []);
    }

    function getBusinessScaleInfo(): bool
    {
        $entname = trim($this->request()->getRequestParam('entname'));
        if (!$entname) {
            return  $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $retData  =\App\HttpController\Models\RDS3\ArLable::create()->where('entname', $entname)->get();

        return $this->writeJson(200, ['total' => 1], $retData, '成功', true, []);
    }


    function getMainProducts(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $type = trim($this->request()->getRequestParam('type'));
        if (!in_array($type,['ios', 'andoriod'])) {
            return  $this->writeJson(201, null, null, '参数缺失(类型)');
        }

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        if($type == 'ios'){
            $model = \App\HttpController\Models\RDS3\XdAppIos::create()
                ->where('xd_id', $companyId)->page($page)->withTotalCount();
            $retData = $model->all();
            $total = $model->lastQueryResult()->getTotalCount();
        }

        if($type == 'andoriod'){
            $model = \App\HttpController\Models\RDS3\XdAppAndroid::create()
                ->where('xd_id', $companyId)->page($page)->withTotalCount();
            $retData = $model->all();
            $total = $model->lastQueryResult()->getTotalCount();
        }

        return $this->writeJson(200,  ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);
    }

    function getCountInfo(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(类型)');
        }


        $highTecCount = \App\HttpController\Models\RDS3\XdHighTec::create()
            ->where('xd_id', $companyId)->count();

        $isoCount = \App\HttpController\Models\RDS3\XdDlRzGlTx::create()
            ->where('xd_id', $companyId)->count();


        $iosCount = \App\HttpController\Models\RDS3\XdAppIos::create()
            ->where('xd_id', $companyId)->count();
        $andoriodCount = \App\HttpController\Models\RDS3\XdAppAndroid::create()
            ->where('xd_id', $companyId)->count();

        $guDongCount = \App\HttpController\Models\RDS3\CompanyInvestor::create()
            ->where('company_id', $companyId)->count();
        // 没有工商股东信息 从企业自发查
        if(!$guDongCount){
            $guDongCount = \App\HttpController\Models\RDS3\CompanyInvestorEntPub::create()
                ->where('company_id', $companyId)->count();
        }

        $employeeCount = \App\HttpController\Models\RDS3\CompanyStaff::create()
            ->where('company_id', $companyId)->count();


        // 商品信息
        $ElasticSearchService = new ElasticSearchService();
        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;
        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ;

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true);
        // 格式化下日期和时间
        $hits = $responseArr['hits']['hits'];
        $hits = (new XinDongService())::formatEsMoney($hits, [
            'reg_capital',
        ]);

        foreach($hits as $dataItem){
            $retData = $dataItem['_source']['shang_pin_data'];
            break;
        }
        $shangPinTotal =  count($retData); //total items in array

        $retData = [
            // 股东+人员
            'gong_shang' => intval($employeeCount + $guDongCount),
            // 商品
            'shang_pin' => $shangPinTotal,
            //专业资质 iso+高新
            'rong_yu' =>  intval($highTecCount + $isoCount),
            //ios +andoriod
            'app' => intval($iosCount+$andoriodCount),
        ];

        return $this->writeJson(200,  [  ], $retData, '成功', true, []);
    }

    function getTagInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $companyData  =\App\HttpController\Models\RDS3\Company::create()->where('id', $companyId)->get();
        if(!$companyData){
            return $this->writeJson(201, null, null, '没有该企业');
        }

        $ElasticSearchService = new ElasticSearchService();

        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;

        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ;

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true);

        return $this->writeJson(200, ['total' => 1],
            XinDongService::getAllTagesByData($responseArr['hits']['hits'][0]['_source']),
            '成功', true, []);
    }

    function getInvestorInfo(): bool
    {
        $page = intval($this->request()->getRequestParam('page'));
        $page = $page>0 ?$page:1;
        $size = intval($this->request()->getRequestParam('size'));
        $size = $size>0 ?$size:10;
        $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        //优先从工商股东信息取
        $model = \App\HttpController\Models\RDS3\CompanyInvestor::create()
            ->where('company_id', $companyId)->page($page)->withTotalCount();
        // 没有工商股东信息 从企业自发查
        if(!$model){
            $model = \App\HttpController\Models\RDS3\CompanyInvestorEntPub::create()
                ->where('company_id', $companyId)->page($page)->withTotalCount();
        }
        $retData = $model->all();
        $total = $model->lastQueryResult()->getTotalCount();

        foreach($retData as &$dataItem){
            if(
                $dataItem['investor_type'] == 2
            ){
                $companyModel = \App\HttpController\Models\RDS3\Company::create()
                    ->where('id', $dataItem['investor_id'])->get();
                $dataItem['name'] = $companyModel->name;
                if(XinDongService::isJson($dataItem['capital'])){
                    $dataItem['capitalData'] = @json_decode($dataItem['capital'],true);
                }else{
                    $dataItem['capitalData'] = [['amomon'=>$dataItem['capital'],'time'=>'','paymet'=>'']];
                }
                if(XinDongService::isJson($dataItem['capitalActl'])){
                    $dataItem['capitalActlData'] = @json_decode($dataItem['capitalActl'],true);
                }else{
                    $dataItem['capitalActlData'] = [['amomon'=>$dataItem['capitalActl'],'time'=>'','paymet'=>'']];
                }

            }

            if(
                $dataItem['investor_type'] == 1
            ){
                $humanModel = \App\HttpController\Models\RDS3\Human::create()
                    ->where('id', $dataItem['investor_id'])->get();
                $dataItem['name'] = $humanModel->name;
                if(XinDongService::isJson($dataItem['capital'])){
                    $dataItem['capitalData'] = @json_decode($dataItem['capital'],true);
                }else{
                    $dataItem['capitalData'] = [['amomon'=>$dataItem['capital'],'time'=>'','paymet'=>'']];
                }
                if(XinDongService::isJson($dataItem['capitalActl'])){
                    $dataItem['capitalActlData'] = @json_decode($dataItem['capitalActl'],true);
                }else{
                    $dataItem['capitalActlData'] = [['amomon'=>$dataItem['capitalActl'],'time'=>'','paymet'=>'']];
                }
            }
        }

        return $this->writeJson(200, ['total' => $total,'page' => $page, 'pageSize' => $size, 'totalPage'=> floor($total/$size)], $retData, '成功', true, []);

    }

    function getNamesInfo(): bool
    {
        // $page = intval($this->request()->getRequestParam('page'));
        // $page = $page>0 ?$page:1;
        // $size = intval($this->request()->getRequestParam('size'));
        // $size = $size>0 ?$size:10;
        // $offset = ($page-1)*$size;

        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $model = Company::create()
            // ->field(['id','name','property2'])
            ->where('id', $companyId)
            ->get();
        if(!$model){
            return  $this->writeJson(201, null, null, '数据缺失(企业id)');
        }

        $names = (new XinDongService())::getAllUsedNames(
            [
                'id' => $model->id,
                'name' => $model->name,
                'property2' => $model->property2,
            ]
        );

        return $this->writeJson(200, [], $names, '成功', true, []);

    }

    function getEsBasicInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }

        $res = (new XinDongService())->getEsBasicInfo($companyId);

        return $this->writeJson(200,
            [ ]
            , $res, '成功', true, []);
    }

    //
    function getShangPinInfo(): bool
    {
        $companyId = intval($this->request()->getRequestParam('xd_id'));
        if (!$companyId) {
            return  $this->writeJson(201, null, null, '参数缺失(企业id)');
        }


        $ElasticSearchService = new ElasticSearchService();

        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize(1) ;
        $ElasticSearchService->addFrom(0) ;

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true);
        CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
                [
                    'es_query' => $ElasticSearchService->query,
                    'post_data' => $this->request()->getRequestParam(),
                ]
            ));

        // 格式化下日期和时间
        $hits = (new XinDongService())::formatEsDate($responseArr['hits']['hits'], [
            'estiblish_time',
            'from_time',
            'to_time',
            'approved_time'
        ]);
        $hits = (new XinDongService())::formatEsMoney($hits, [
            'reg_capital',
        ]);


        foreach($hits as $dataItem){
            $retData = $dataItem['_source']['shang_pin_data'];
            break;
        }


        $total =  count($retData); //total items in array
        $totalPages = ceil( $total/ $size ); //calculate total pages
        $page = max($page, 1); //get 1 page when $_GET['page'] <= 0
        // $page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
        $offset = ($page - 1) * $size;
        if( $offset < 0 ) $offset = 0;

        $retData = array_slice( $retData, $offset, $size );


        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>$size,
                'total' => $total,
                'totalPage' => $totalPages,
            ]
            , $retData, '成功', true, []);
    }

    function getEntLianXi(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
        ];

        $retData =  (new LongXinService())
            ->setCheckRespFlag(true)
            ->getEntLianXi($postData);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;

        $retData = $retData['result'];
        $total =  count($retData); //total items in array
        $totalPages = ceil( $total/ $size ); //calculate total pages
        $page = max($page, 1); //get 1 page when $_GET['page'] <= 0
        // $page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
        $offset = ($page - 1) * $size;
        if( $offset < 0 ) $offset = 0;

        $retData = array_slice( $retData, $offset, $size );
        // CommonService::getInstance()->log4PHP(
        //     'getEntLianXi '.json_encode(
        //         $retData
        //     )
        // );
        $retData = LongXinService::complementEntLianXiMobileState($retData);
        $retData = LongXinService::complementEntLianXiPosition($retData, $postData['entName']);

        return $this->writeJson(200,
            [
                'page' => $page,
                'pageSize' =>$size,
                'total' => $total,
                'totalPage' => $totalPages,
            ]
            , $retData, '成功', true, []);
    }

    /*
     * 导出客户数据
     * */
    function exportEntData(): bool
    {
        if(
            !ConfigInfo::setRedisNx('exportEntData',5)
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
        }

        $requestData =  $this->getRequestData();
        if(substr($requestData['basic_nicid'], -1) == ','){
            $requestData['basic_nicid'] = rtrim($requestData['basic_nicid'], ",");
        }

        if(substr($requestData['basic_regionid'], -1) == ','){
            $requestData['basic_regionid'] = rtrim($requestData['basic_regionid'], ",");
        }

        if(substr($requestData['basic_jlxxcyid'], -1) == ','){
            $requestData['basic_jlxxcyid'] = rtrim($requestData['basic_jlxxcyid'], ",");
        }


        $checkRes = DataModelExample::checkField(
            [

                'total_nums' => [
                    'bigger_than' => 0,
                    'less_than' => 1000000,
                    'field_name' => 'total_nums',
                    'err_msg' => '总数不对！必须大于0且小于100万',
                ]
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        //下载
        DownloadSoukeHistory::addRecord(
            [
                'admin_id' => $this->loginUserinfo['id'],
                'entName' => $requestData['entName'],
                //选择的哪些条件
                'feature' => json_encode($requestData),
                //标题
                'title' => $requestData['title'],
                'remark' => $requestData['remark'],
                'total_nums' => $requestData['total_nums'],
                'status' => DeliverHistory::$state_init,
                'type' => $requestData['type']?:1,
            ]
        );

        ConfigInfo::removeRedisNx('exportEntData');
        return $this->writeJson(200,[ ] , [], '已发起下载，请去我的下载中查看', true, []);
    }

    /*
     * 获取导出列表
     * */
    public function getExportLists(){
        $page = $this->request()->getRequestParam('page')??1;
        $createdAtStr = $this->getRequestData('created_at');
        $createdAtArr = explode('|||',$createdAtStr);
        $whereArr = [];
        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $whereArr = [
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[0]),
                    'operate' => '>=',
                ],
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[1]),
                    'operate' => '<=',
                ]
            ];
        }
        $whereArr[] =   [
            'field' => 'admin_id',
            'value' => $this->loginUserinfo['id'],
            'operate' => '=',
        ];
        $res = DownloadSoukeHistory::findByConditionV2(
            $whereArr,
            $page
        );

        foreach ($res['data'] as &$value){
            $value['status_cname'] = DownloadSoukeHistory::getStatusMap()[$value['status']];
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }

    public function getConfigs(){
        $requestData =  $this->getRequestData();
        $res = AdminUserSoukeConfig::findByConditionV3(
            [
                [
                    'field' => 'status',
                    'value' => AdminUserSoukeConfig::$state_init,
                    'operate' => '='
                ]
            ],
            $requestData['page']
        );

        foreach ($res['data'] as &$value){
//            $value['upload_details'] = [];
//            if(
//                $value['upload_record_id']
//            ){
//                $value['upload_details'] = AdminUserFinanceUploadRecord::findById($value['upload_record_id'])->toArray();
//            }
        }
        return $this->writeJson(200,  [
            'page' => $requestData['page'],
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }


    public function addConfigs(){
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'user_id',
                    'err_msg' => '请指定用户',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $res = AdminUserSoukeConfig::addRecordV2(
            [
                'user_id' => $requestData['user_id'],
                'allowed_fields' => $requestData['allowed_fields'],
                'price' => $requestData['price'],
                'max_daily_nums' => $requestData['max_daily_nums'],
                'remark' => $requestData['remark']?:'',
                'status' => $requestData['status']?:1,
                'type' => $requestData['type']?:1,
            ]
        );

        return $this->writeJson(200,  [
            'page' => $requestData['page'],
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }

    public function getAllFields(){
        return $this->writeJson(200,  [], AdminUserSoukeConfig::getAllFields(),'成功');
    }

    public function updateConfigs(){
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => '请指定记录',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        AdminUserSoukeConfig::setStatus($requestData['id'],AdminUserSoukeConfig::$state_del);

        $res = AdminUserSoukeConfig::addRecordV2(
            [
                'user_id' => $requestData['user_id'],
                'allowed_fields' => $requestData['allowed_fields'],
                'price' => $requestData['price'],
                'max_daily_nums' => $requestData['max_daily_nums'],
                'remark' => $requestData['remark']?:'',
                'status' => $requestData['status']?:1,
                'type' => $requestData['type']?:1,
            ]
        );

        return $this->writeJson(200,  [
            'page' => $requestData['page'],
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }

    /*
     * 确认使用该文件
     * */
    public function deliverCustomerRoster(){
        $requestData =  $this->getRequestData();

        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => '请指定记录',
                ],
                'entName' => [
                    'not_empty' => 1,
                    'field_name' => 'entName',
                    'err_msg' => '请输入要交付的企业名',
                ],
                'title' => [
                    'not_empty' => 1,
                    'field_name' => 'title',
                    'err_msg' => '标题必填',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        //下载历史
        $downloadHistoryRes  = DownloadSoukeHistory::findById($requestData['id'])->toArray();
        $checkRes = DataModelExample::checkField(
            [
                'status' => [
                    'in_array' => [DownloadSoukeHistory::$state_file_succeed],
                    'field_name' => 'status',
                    'err_msg' => '该状态不允许确认',
                ],
            ],
            $downloadHistoryRes
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        //交付历史
        DeliverHistory::addRecord(
            [
                'admin_id' => $downloadHistoryRes['admin_id'],
                'entName' => $requestData['entName'],
                'feature' => $downloadHistoryRes['feature'],
                'title' => $requestData['title'],//
                'file_name' => '',
                'file_path' => '',
                'remark' => $requestData['remark']?:'',
                'total_nums' => $downloadHistoryRes['total_nums'],
                'status' => DeliverHistory::$state_init,
                'type' => 1,
                'is_destroy' => 0,
            ]
        );

        return $this->writeJson(200,  [], [],'成功');
    }

    /*
     * 获取交付记录  deliver_history
     * */
    public function getDeliverLists(){
        $page = $this->request()->getRequestParam('page')??1;
        $createdAtStr = $this->getRequestData('created_at');
        $createdAtArr = explode('|||',$createdAtStr);
        $whereArr = [];
        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $whereArr = [
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[0]),
                    'operate' => '>=',
                ],
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[1]),
                    'operate' => '<=',
                ]
            ];
        }
        $whereArr[] =   [
            'field' => 'admin_id',
            'value' => $this->loginUserinfo['id'],
            'operate' => '=',
        ];

        $res = DeliverHistory::findByConditionV2(

            $whereArr,
            $page
        );

        //补全数据
         foreach ($res['data'] as &$value){

             $featureArr = json_decode($value['feature'],true) ;
             $map = [
                 'searchText' => '企业名称/经营范围/业务商品等关键词',
                 'basic_nicid' => '四级分类',
                 'basic_opscope' => '经营范围',
                 'basic_regionid' => '地区',
                 'basic_szjjid'=>'数字经济及其核心产业',
                 'basic_jlxxcyid' => '搜索战略新兴产业',
                 'total_nums' => '数量',
             ];

             $options =  (new  XinDongService())->getSearchOption();
             $cname ="";
             foreach ($featureArr as $key => $featureItem){
                    if($key == 'searchOption'){
                        $searchOption = json_decode($featureItem,true);
                        foreach ($searchOption as $searchOptionItem){
                            foreach ($options as $config){
                                if($config['pid'] == $searchOptionItem['pid']){
                                    $tmpSTr = $config['desc'].":";
                                    foreach ($searchOptionItem['value'] as $subvalue){
                                        $cname.= $tmpSTr.$config['data'][$subvalue]['cname'].',';
                                    }
                                    $cname.= "<br/>";
                                };
                            }
                        }
                    }
                    else{
                        if($map[$key]){
                            $cname .= $map[$key].":".$featureItem."<br/>";
                        }
                    }
             }
             $value['feature_cname'] = $cname;
         }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }


    public function getDeliverDetails(){
        $page = $this->request()->getRequestParam('page')??1;

        $requestData =  $this->getRequestData();

        $checkRes = DataModelExample::checkField(
            [
                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => '请指定记录',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $createdAtStr = $this->getRequestData('created_at');
        $createdAtArr = explode('|||',$createdAtStr);
        $whereArr = [];

        $whereArr[] =   [
            'field' => 'admin_id',
            'value' => $this->loginUserinfo['id'],
            'operate' => '=',
        ];
        $whereArr[] =   [
            'field' => 'deliver_id',
            'value' => $requestData['id'],
            'operate' => '=',
        ];
        $res = DeliverDetailsHistory::findByConditionV2(

            $whereArr,
            $page
        );

        //补全数据
        foreach ($res['data'] as &$value){
        }
        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 10 ),
        ], $res['data'],'成功');
    }

    public function calMarketShare(){
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [

                'xd_id' => [
                    'bigger_than' => 0,
                    'field_name' => 'xd_id',
                    'err_msg' => '参数错误',
                ]
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        XinDongService::getMarjetShare($requestData['xd_id']);
        return $this->writeJson(200, [ ] ,XinDongService::getMarjetShare($requestData['xd_id']), '成功', true, []);
    }

}