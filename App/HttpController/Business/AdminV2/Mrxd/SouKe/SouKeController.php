<?php

namespace App\HttpController\Business\AdminV2\Mrxd\SouKe;

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
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$offset' => $offset,
                '$page'=>$page,
                '$size'=>$size,
            ])
        );
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
            ->SetAreaQuery($requestData['areas'],$requestData['areas_type']?:1)
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
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$offset' => $offset,
                '$page'=>$page,
                '$size'=>$size,
            ])
        );
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
            ->SetAreaQuery($requestData['areas'],$requestData['areas_type']?:1)
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
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['company_org_type']){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }
                //营业状态
                if($configs['pid'] == 30){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $dataItem['_source']['reg_status']){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }
                //官网
                if($configs['pid'] == 70){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $has_web){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }

                //有无APP
                if($configs['pid'] == 80){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $has_app){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }
                //是否物流企业
                if($configs['pid'] == 90){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if($item['cname'] == $has_wu_liu_xin_xi){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        };
                    };
                }

                //成立年限
                if($configs['pid'] == 20){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['estiblish_year_nums'] >= $item['min'] &&
                            $dataItem['_source']['estiblish_year_nums'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }

                //注册资本
                if($configs['pid'] == 40){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['reg_capital'] >= $item['min'] &&
                            $dataItem['_source']['reg_capital'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }
                //营收规模
                if($configs['pid'] == 50){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['ying_shou_gui_mo'] >= $item['min'] &&
                            $dataItem['_source']['ying_shou_gui_mo'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }
                //企业规模
                if($configs['pid'] == 60){
                    foreach ($configs['pid']['data'] as $subKey => $item){
                        if(
                            $dataItem['_source']['tuan_dui_ren_shu'] >= $item['min'] &&
                            $dataItem['_source']['tuan_dui_ren_shu'] <  $item['max']
                        ){
                            $newOptions[$key]['data'][$subKey] = $item;
                            break;
                        }
                    };
                }
            }
        }

        return $this->writeJson(200,
            [  ]
            , $newOptions, '成功', true, []);
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