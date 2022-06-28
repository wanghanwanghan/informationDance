<?php

namespace App\HttpController\Business\AdminV2\Mrxd\SouKe;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\XinDongService;
use Vtiful\Kernel\Format;

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

    /*
     * 高级搜索
     * */
    function advancedSearch(): bool
    {
        $companyEsModel = new \App\ElasticSearch\Model\Company();

        //传过来的searchOption 例子 [{"type":20,"value":["5","10","2"]},{"type":30,"value":["15","5"]}]
        $searchOptionStr =  trim($this->request()->getRequestParam('searchOption'));
        $searchOptionArr = json_decode($searchOptionStr, true);

        $size = $this->request()->getRequestParam('size')??10;
        $page = $this->request()->getRequestParam('page')??1;
        $offset  =  ($page-1)*$size;

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
        $res = DownloadSoukeHistory::findByConditionV2(

            [
                [
                    'field' => 'admin_id',
                    'value' => $this->loginUserinfo['id'],
                    'operate' => '=',
                ],
            ],
            $page
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
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 20 ),
        ], $res['data'],'成功');
    }

    public function getConfigs(){
        $requestData =  $this->getRequestData();
        $res = AdminUserSoukeConfig::findByConditionV3(
            [
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
            'totalPage' =>  ceil( $res['total']/ 20 ),
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
            'totalPage' =>  ceil( $res['total']/ 20 ),
        ], $res['data'],'成功');
    }

    public function getAllFields(){
        /*
          {
                    "xd_id": 71088418,
                    "base": "js",
                    "name": "南京大华影视文化传媒有限责任公司",
                    "legal_person_id": 4619238,
                    "legal_person_name": "李冬昱",
                    "legal_person_type": 1,
                    "reg_number": "320100000170484",
                    "company_org_type": "有限责任公司(非自然人投资或控股的法人独资)",
                    "reg_location": "南京市建邺区白龙江东街22号(艺树家工场)15层1502室",
                    "estiblish_time": "2014-04-23 00:00:00",
                    "from_time": "2014-04-23 00:00:00",
                    "to_time": "0000-00-00 00:00:00",
                    "business_scope": "影视,演艺,音乐文化项目的投资与开发;影视策划咨询;企业管理咨询服务;会务服务;文化交流活动策划,咨询,服务;工艺品设计,销售;影视器材,灯光音响租赁,销售;设计,制作,代理,发布国内各类广告;演出经纪;票务代理.(依法须经批准的项目,经相关部门批准后方可开展经营活动)",
                    "reg_institute": "南京市建邺区市场监督管理局",
                    "approved_time": "2020-11-06 00:00:00",
                    "reg_status": "存续(在营,开业,在册)",
                    "reg_capital": "800.000000万人民币",
                    "actual_capital": "",
                    "org_number": "302305280",
                    "org_approved_institute": "",
                    "parent_id": 0,
                    "list_code": "",
                    "property1": "913201003023052803",
                    "property2": "",
                    "property3": "",
                    "property4": "",
                    "up_state": 2,
                    "ying_shou_gui_mo": "A9",
                    "si_ji_fen_lei_code": "R8890",
                    "si_ji_fen_lei_full_name": "文化,体育和娱乐业-体育-其他体育",
                    "gong_si_jian_jie": "公司简介大华影视文化传媒有限责任公司是南京市文投集团控股的新兴影视公司,公司宗旨是以构建影视文化为主导的内容企业为目标,整合优势影视产业资源,吸引社会资本投向影视产业,在有效提升国有资产运营效率,保证国有资产保值增值的同时,推动南京影视产业的转型发展,创新发展和跨越发展.公司与与集团旗下南京最具知名度的百年电影品牌--大华大戏院一起形成电影制作,营销,院线,影院放映等贯穿影视上下游的,相对完整的产业链.公司主营影视,演艺,音乐等文化项目的投资与开发;电影,电视剧,电视节目的拍摄,制作和发行;承办国内,国外文化交流活动和大型文艺演出;文化交流活动策划,咨询,服务,企业形象策划,企业管理及咨询服务,商务,会展服务;广告设计,制作,代理,发布;文化创意产品,多媒体产品,数字影视节目的开发和应用;为演艺人员提供培训服务及经纪业务;影视,舞美器材的租赁和销售;进出口影视版权贸易等.&&&",
                    "gao_xin_ji_shu": "",
                    "deng_ling_qi_ye": "",
                    "tuan_dui_ren_shu": "25",
                    "tong_xun_di_zhi": "南京市建邺区白龙江东街22号(艺树家工场)1502)",
                    "web": "shop322262928.taobao.com&&&a230r.7195193.1997079397.2.5778b479833bhl",
                    "yi_ban_ren": "1",
                    "shang_shi_xin_xi": "",
                    "app": "",
                    "manager": "李冬昱(董事,总经理)&&&吴海青(董事长)&&&陶晶晶(监事)&&&卜正繁(董事)&&&宋伟利(董事长)&&&",
                    "inv_type": "3",
                    "inv": "李冬昱&&&南京市文化投资控股集团有限责任公司(91320100552095183M)&&&南京锦城佳业营销策划有限公司(91320106MA1ME91654)&&&",
                    "en_name": "",
                    "email": "njdhys2014@163.com&&&njdhys2014@163.com&&&",
                    "app_data": [],
                    "shang_pin_data": [],
                    "zlxxcy": "",
                    "szjjcy": "",
                    "report_year": [
                        {
                            "report_year": "2014",
                            "phone_number": "18013842339",
                            "postcode": "210000",
                            "postal_address": "南京市建邺区奥体大街69号01栋四层",
                            "email": " "
                        },
                        {
                            "report_year": "2015",
                            "phone_number": "02552001329",
                            "postcode": "210000",
                            "postal_address": "建邺区奥体大街69号01栋四层",
                            "email": "无"
                        },
                        {
                            "report_year": "2016",
                            "phone_number": "025-52001329",
                            "postcode": "210000",
                            "postal_address": "建邺区奥体大街69号01栋四层",
                            "email": "无"
                        },
                        {
                            "report_year": "2017",
                            "phone_number": "025-86299993",
                            "postcode": "210000",
                            "postal_address": "南京市奥体大街69号01栋七层",
                            "email": "无"
                        },
                        {
                            "report_year": "2018",
                            "phone_number": "025-86299993",
                            "postcode": "210000",
                            "postal_address": "南京市建邺区白龙江东街22号艺树家工场1502室",
                            "email": "无"
                        },
                        {
                            "report_year": "2019",
                            "phone_number": "025-86299993",
                            "postcode": "210000",
                            "postal_address": "南京市建邺区白龙江东街22号艺树家工场1502室",
                            "email": "njdhys2014@163.com"
                        },
                        {
                            "report_year": "2020",
                            "phone_number": "025-86299993",
                            "postcode": "211100",
                            "postal_address": "南京市建邺区白龙江东街22号（艺树家工场）1502）",
                            "email": "njdhys2014@163.com"
                        }
                    ],
                    "jin_chu_kou": "",
                    "iso": ""
                }
            },
            {
                "_index": "company_20220516",
                "_type": "_doc",
                "_id": "2572315",
                "_score": 34.757763,
                "_source": {
                    "xd_id": 183004578,
                    "base": "hun",
                    "name": "湖南大歌影视文化传媒有限公司",
                    "legal_person_id": 509732,
                    "legal_person_name": "杨武成",
                    "legal_person_type": 1,
                    "reg_number": "430194000085459",
                    "company_org_type": "有限责任公司(自然人投资或控股)",
                    "reg_location": "长沙经济技术开发区漓湘东路1号01栋201",
                    "estiblish_time": "2019-01-09 00:00:00",
                    "from_time": "2019-01-09 00:00:00",
                    "to_time": "2069-01-08 00:00:00",
                    "business_scope": "影视节目制作;广播电视节目制作;影视策划;电影放映;广播电视视频点播服务;影视节目发行;录音制作;营业性文艺表演;会议服务;展览服务;日用品,文化用品,办公用品,字画,电子产品,农产品,家居用品销售;广告制作服务;商业信息咨询;文化艺术咨询服务;文化艺术交流活动的组织;文化创意设计;文艺表演,体育,娱乐活动的策划和组织;物业管理;场地租赁;自有厂房租赁;舞台表演美工服务,道具服务,化妆服务;道具出租服务;服装和鞋帽出租服务;舞台灯光,音响设备安装服务;电子显示屏及舞台设备的设计与安装;电子商务平台的开发建设;货物仓储(不含危化品和监控品);数字动漫制作;游戏软件设计制作;广告发布服务;广告国内代理服务;广告设计;灯光设备租赁;音频和视频设备租赁;物流园运营服务.(依法须经批准的项目,经相关部门批准后方可开展经营活动)",
                    "reg_institute": "长沙经济技术开发区管理委员会",
                    "approved_time": "2019-08-12 00:00:00",
                    "reg_status": "存续(在营,开业,在册)",
                    "reg_capital": "5000.000000万人民币",
                    "actual_capital": "",
                    "org_number": "MA4Q7X2T6",
                    "org_approved_institute": "",
                    "parent_id": 0,
                    "list_code": "",
                    "property1": "91430100MA4Q7X2T6A",
                    "property2": "",
                    "property3": "",
                    "property4": "",
                    "up_state": 2,
                    "ying_shou_gui_mo": "A6",
                    "si_ji_fen_lei_code": "R9051",
                    "si_ji_fen_lei_full_name": "文化,体育和娱乐业-娱乐业-文化体育娱乐活动与经纪代理服务-文化活动服务",
                    "gong_si_jian_jie": "",
                    "gao_xin_ji_shu": "",
                    "deng_ling_qi_ye": "",
                    "tuan_dui_ren_shu": "",
                    "tong_xun_di_zhi": "湖南省长沙经济技术开发区漓湘东路1号",
                    "web": "",
                    "yi_ban_ren": "1",
                    "shang_shi_xin_xi": "",
                    "app": "",
                    "manager": "卢喜兰(监事)&&&杨武成(经理,执行董事)&&&",
                    "inv_type": "1",
                    "inv": "卢喜兰&&&杨武成&&&",
                    "en_name": "",
                    "email": "291736727@qq.com&&&291736727@qq.com&&&",
                    "app_data": [],
                    "shang_pin_data": [],
                    "zlxxcy": "",
                    "szjjcy": "",
                    "report_year": [
                        {
                            "report_year": "2019",
                            "phone_number": "19118977189",
                            "postcode": "410100",
                            "postal_address": "湖南省长沙经济技术开发区漓湘东路1号",
                            "email": "291736727@qq.com"
                        },
                        {
                            "report_year": "2020",
                            "phone_number": "19118977189",
                            "postcode": "410100",
                            "postal_address": "湖南省长沙经济技术开发区漓湘东路1号",
                            "email": "291736727@qq.com"
                        }
                    ],
                    "jin_chu_kou": "",
                    "iso": ""
                }
         * */
        return $this->writeJson(200,  [
            'page' => $requestData['page'],
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 20 ),
        ], $res['data'],'成功');
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
            'totalPage' =>  ceil( $res['total']/ 20 ),
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
//                'total_nums' => [
//                    'bigger_than' => 0,
//                    'less_than' => 1000000,
//                    'field_name' => 'total_nums',
//                    'err_msg' => '总数不对！必须大于0且小于100万',
//                ]
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
        $res = DeliverHistory::findByConditionV2(

            [
                [
                    'field' => 'admin_id',
                    'value' => $this->loginUserinfo['id'],
                    'operate' => '=',
                ],
            ],
            $page
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
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>  ceil( $res['total']/ 20 ),
        ], $res['data'],'成功');
    }

}