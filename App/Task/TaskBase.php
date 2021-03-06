<?php

namespace App\Task;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class TaskBase
{
    public $ldUrl;
    public $fyyList;
    public $fyyDetail;

    //pdf字体大小
    public $pdf_BigTitle = 17;
    public $pdf_LittleTitle = 14;
    public $pdf_Text = 11;

    //龙盾 证书资质
    public $zzzs = [
        'C_9' => '中国食品农产品认证',
        'C_997_25' => '国产药品批准文号',
        'C_996_A1' => '节能汽车推广目录',
        'C_997_24' => 'GSP认证证书',
        'C_996_B16' => '互联网电子公告服务专项审批证书',
        'C_224' => '产品检验',
        'C_997_31' => '进口保健食品批准文号',
        'C_996_B2' => '农药产品生产批准证书',
        'C_997_68' => '国产化妆品批准文号',
        'C_996_B24' => '泰尔认证中心产品认证的统一接口手机充电器',
        'C_999' => '工程勘察资质证书',
        'C_262' => 'CCC证书',
        'C_264' => 'CQC自愿性产品认证和中国饲料产品认证证书',
        'C_996_B13' => '电信网码号资源使用审批',
        'C_189' => '卫生行政许可',
        'C_997_69' => '进口化妆品批准文号',
        'C_997_30' => '国产保健食品批准文号',
        'C_996_B30' => '2006年度跨地区增值电信业务经营许可证年检不合格企业',
        'C_996_B23' => '电信网码号资源使用审批',
        'C_996_B28' => '2001年至2006年信息产业重大技术发明获选项目',
        'C_996_B14' => '无线广播电视发射设备生产资质认定',
        'C_996_B6' => '信息系统工程监理资质单位证书',
        'C_997_26' => '国产器械注册号',
        'C_208_A2' => 'IT产品信息安全认证',
        'C_997_91' => '食品生产许可证',
        'C_997_36' => '进口药品注册证号',
        'C_996_B5' => '计算机信息系统集成资质企业',
        'C_266' => '原中标认证中心证书',
        'C_997_23' => 'GMP认证证书',
        'C_997_34' => '药品生产企业',
        'C_198' => '工程设计资质证书',
        'C_996_B22' => '国际通信出入口局的设置和调整审批',
        'C_265' => '防雷产品CQC标志认证证书',
        'C_64' => '产品认证',
        'C_996_B7' => '核发进网许可证书',
        'C_996_B9' => '已批准开采黄金矿产的企业名单',
        'C_22' => '有机产品认证',
        'C_128' => '工程监理资质证书',
        'C_996_B3' => '计算机信息系统集成特一级企业资质证书',
        'C_8' => '玩具婴童用品消费品认证',
        'C_996_B12' => '计算机信息系统集成高级项目经理资质证书',
        'C_996_B8' => '2010年度跨地区电信业务经营许可证年检合格名单和整改名单',
        'C_996_B27' => '全国各省市SP企业违规行为记录情况表',
        'C_990' => '安全生产许可证',
        'C_996_B1' => '农药生产资质证书',
        'C_996_B10' => '电子认证服务机构设立许可证',
        'C_47_1' => '铁路总公司认证采信目录',
        'C_184' => '工业产品生产许可证',
        'C_295' => '证书查询',
        'C_996_B21' => '电信业务经营许可证审批',
        'C_996_B19' => '设置空间电台审批结果',
        'C_205' => '通信建设企业资质',
        'C_197' => '设计施工一体化资质证书',
        'C_47_2' => '原铁道部认证采信目录',
        'C_23' => '环境标志产品认证',
        'C_996_B26' => '全国跨地区SP企业违规行为记录情况表',
        'C_996_B15' => '外商投资电信企业设立审批',
        'C_196' => '建筑施工资质证书',
        'C_21' => '有机认证',
        'C_997_22' => '中药保护品种编号',
        'C_997_89' => '食品添加剂生产许可证',
        'C_997_41' => '药品经营企业',
        'C_997_93' => '化妆品生产许可获证企业',
        'C_996_B17' => '军工电子计量技术机构建立认定',
        'C_996_B25' => '信息产业部电信资费备案',
        'C_263' => 'CCC工厂信息',
        'C_25' => '企业体育用品认证',
        'C_28' => '企业体系认证',
        'C_996_B18' => '域名注册管理机构',
        'C_998' => 'CCC产品认证',
        'C_208_A4' => '服务资质认证证书',
        'C_208_A3' => '非金融机构支付业务设施技术认证',
        'C_201' => '招标代理资质证书',
        'C_996_B11' => '计算机信息系统集成项目经理资质证书',
        'C_996_B4' => '信息系统工程监理资质工程师证书',
        'C_43' => '出口食品生产企业备案查询',
        'C_997_6' => '药包材注册证',
        'C_997_27' => '进口器械注册号',
        'C_996_B29' => '2006年度跨地区增值电信业务经营许可证书',
        'C_200_1' => '工程监理企业资质证书',
        'C_200_2' => '设计施工一体化企业资质证书',
        'C_200_3' => '工程造价咨询企业资质证书',
        'C_200_4' => '外省市建筑业企业进京施工备案',
        'C_200_5' => '北京市专业人员资质',
        'C_200_6' => '建设工程企业资质证书',
        'C_200_7' => '劳务企业资质证书',
        'C_200_8' => '安全生产许可证取证',
        'C_200_9' => '建设工程质量检测单位',
        'C_200_11' => '安全监督编码信息',
        'C_200_12' => '施工许可信息',
        'C_200_13' => '招标代理机构资质证书',
        'C_200_14' => '新建商品房项目查询',
        'C_200_15' => '房地产开发企业资质',
        'C_200_16' => '房产测绘企业',
        'C_200_17' => '物业企业信息',
        'C_200_19' => '新建经济适用住房项目',
        'C_200_20' => '拆迁人员',
        'C_200_21' => '房屋征收评估机构',
        'C_200_22' => '房地产估价机构信用档案',
        'C_200_23' => '北京市轨道交通建设工程专家库信息',
        'C_200_24' => '北京市危险性较大的分部分项工程专家',
        'C_997_42' => '互联网药品信息',
        'C_997_43' => '互联网药品交易服务资格证',
        'C_997_44' => '药品广告批准文号',
        'C_997_45' => '医疗器械广告批准文号',
        'C_997_46' => '保健食品广告批准文号',
        'C_997_95' => '食品质量安全市场准入信息',
        'C_998_2' => 'CCC强制性产品查询',
        'C_170' => '境外矿产资源开发备案',
        'C_170_1' => '对外承包工程经营资格许可',
        'C_168' => '矿业权评估机构',
        'C_169' => '石油经营许可',
        'C_200_18' => '预售证变更注销公告',
        'C_44' => 'SGS',
        'C_33' => '管理体系认证证书',
        'C_211_A' => '进网许可证',
        'cnca001' => '电子信息产品污染控制自愿性认证',
        'cnca002' => '其他自愿性工业产品认证',
        'cnca003' => '测量管理体系',
        'cnca004' => '中国森林认证',
        'cnca005' => '食品安全管理体系认证',
        'cnca006' => '危害分析与关键控制点认证',
        'cnca007' => '森林认证FSC',
        'cnca008' => '医疗器械质量管理体系认证',
        'cnca009' => '环境管理体系认证',
        'cnca010' => '绿色食品认证',
        'cnca011' => '所有未列明的其他管理体系认证',
        'cnca012' => '可再生能源/新能源',
        'cnca013' => '食品质量认证（酒类）',
        'cnca014' => '绿色市场认证',
        'cnca015' => '中国电子招标投标系统认证',
        'cnca016' => '防爆电气产品认证',
        'cnca017' => '有机产品（OGA）',
        'cnca018' => '城市轨道交通产品认证',
        'cnca019' => '光伏产品认证',
        'cnca020' => '信息安全产品认证（未列入强制性产品认证目录内的信息安全产品）',
        'cnca021' => '铁路产品认证',
        'cnca022' => '森林认证PEFC',
        'cnca023' => '乳制品生产企业良好生产规范认证',
        'cnca024' => '风电产品认证',
        'cnca025' => '中国职业健康安全管理体系认证',
        'cnca026' => '良好农业规范（GAP）',
        'cnca027' => '质量管理体系认证（ISO9000）',
        'cnca028' => '企业社会责任管理体系认证',
        'cnca029' => '乳制品生产企业危害分析与关键控制点(HACCP)体系认证',
        'cnca030' => '电气与电子元件和产品有害物质过程控制管理体系认证',
        'cnca031' => '商品售后服务评价认证',
        'cnca032' => '非金融机构支付业务设施认证',
        'cnca033' => '体育场所服务认证',
        'cnca034' => '汽车玻璃零配安装服务认证',
        'cnca035' => '建设施工行业质量管理体系认证',
        'cnca036' => '软件过程能力及成熟度评估认证',
        'cnca037' => '节能产品认证（不含建筑节能）',
        'cnca038' => '信息安全服务资质认证',
        'cnca039' => '低碳产品认证-通用硅酸盐水泥',
        'cnca040' => '防爆电器检修服务认证',
        'cnca041' => '一般服务认证',
        'cnca042' => '环保产品认证',
        'cnca043' => '节水产品认证',
        'cnca044' => '环境标志产品',
        'cnca045' => '低碳产品认证-建筑陶瓷砖（板）',
        'cnca046' => '建筑节能产品认证',
        'cnca047' => '低碳产品认证-纺织面料',
        'cnca048' => '低碳产品认证-平板玻璃',
        'cnca049' => '低碳产品认证-铝合金建筑型材',
        'cnca050' => '低碳产品认证',
        'cnca051' => '信息安全管理体系认证',
        'cnca052' => '饲料产品',
        'cnca053' => '能源管理体系认证',
        'cnca054' => '国际铁路行业质量管理体系认证',
        'cnca055' => '信息技术服务管理体系认证',
        'cnca056' => '汽车行业质量管理体系认证',
        'cnca057' => '航空业质量管理体系认证',
        'cnca058' => '企业知识产权管理体系认证',
        'cnca059' => '电讯业质量管理体系认证',
        'cnca060' => '无公害农产品',
        'cnca061' => '静电防护标准认证',
        'cnca062' => '高等学校知识产权管理体系认证',
        'cnca063' => '航空仓储销售商质量管理体系认证',
        'cnca064' => '中国共产党基层组织建设质量管理体系',
        'cnca065' => '供应链安全管理体系认证',
        'cnca066' => '德国汽车工业协会质量管理体系认证',
        'cnca067' => '运输资产保护协会 运输供应商最低安全要求认证',
        'cnca068' => '航空器维修质量管理体系认证',
        'cnca069' => '验证合格评定程序认证',
        'cnca070' => '温室气体排放和清除的量化和报告的规范及指南认证',
        'cnca071' => '整合管理体系认证',
        'cnca072' => '其它管理体系认证',
        'cnca073' => '商品和服务在生命周期内的温室气体排放评价规范认证',
        'bm001' => '设计资质',
        'bm002' => '设计与施工一体化资质',
        'bm003' => '建筑业企业资质',
        'bm004' => '招标代理资格',
        'bm005' => '监理资质',
        'bm006' => '勘察资质',
        'bm007' => '造价咨询资质',
        'mn001' => '采矿权许可证',
        'mn002' => '探矿权许可证',
        'rd001' => '无线电',
        'em001' => '排污许可证',
        'ht001' => '高新技术企业',
        'bl001' => '商业保理公司经营许可证',
        'jr001' => '金融许可证',
        'fb001' => '食品经营许可证',
        'cf001' => '商业特许经营备案',
        'co001' => '进口化妆品',
        'co002' => '化妆品生产许可获证企业',
        'co003' => '国产特殊用途化妆品',
        'co004' => '国产非特殊用途化妆品备案信息',
        'co005' => '进口非特殊用途化妆品备案信息',
        'sp001' => '软件产品证书',
    ];

    //龙盾 商标类别
    public $sblb = [
        '1' => '化学原料',
        '2' => '颜料油漆',
        '3' => '日化用品',
        '4' => '燃料油脂',
        '5' => '医药',
        '6' => '金属材料',
        '7' => '机械设备',
        '8' => '手工器械',
        '9' => '科学仪器',
        '10' => '医疗器械',
        '11' => '灯具空调',
        '12' => '运输工具',
        '13' => '军火烟火',
        '14' => '珠宝钟表',
        '15' => '乐器',
        '16' => '办公品',
        '17' => '橡胶制品',
        '18' => '皮革皮具',
        '19' => '建筑材料',
        '20' => '家具',
        '21' => '厨房洁具',
        '22' => '绳网袋篷',
        '23' => '纱线丝',
        '24' => '布料床单',
        '25' => '服装鞋帽',
        '26' => '钮扣拉链',
        '27' => '地毯席垫',
        '28' => '键身器材',
        '29' => '食品',
        '30' => '方便食品',
        '31' => '饲料种籽',
        '32' => '啤酒饮料',
        '33' => '酒',
        '34' => '烟草烟具',
        '35' => '广告销售',
        '36' => '金融物管',
        '37' => '建筑修理',
        '38' => '通讯服务',
        '39' => '运输贮藏',
        '40' => '材料加工',
        '41' => '教育娱乐',
        '42' => '设计研究',
        '43' => '餐饮住宿',
        '44' => '医疗园艺',
        '45' => '社会法律',
    ];

    function __construct()
    {
        $this->ldUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');
        $this->fyyList = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $this->fyyDetail = CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl');

        return true;
    }

    //判断时间用的，时间类的字段，只显示成 Y-m-d
    function formatDate($str)
    {
        $str = trim($str);

        return empty($str) ? '--' : formatDate($str);
    }

    //判断比例用的，只显示成 10%
    function formatPercent($str)
    {
        $str = trim($str);

        return empty($str) ? '--' : formatPercent($str);
    }

    //显示--
    function formatTo($data, $to = '--')
    {
        return empty($data) ? $to : $data;
    }

    //pdf目录
    function pdf_Catalog($index = ''): array
    {
        $catalog = [
            '基本信息' => [
                '基本信息',
                '实际控制人',
                '历史沿革及重大事项',
                '法人对外投资',
                '法人对外任职',
                '企业对外投资',
                '主要分支机构',
                '银行信息',
            ],
            '公司概况' => [
                '融资信息',
                '招投标信息',
                '购地信息',
                '土地公示',
                '土地转让',
                '建筑资质证书',
                '建筑工程项目',
                '债券信息',
                '网站信息',
                '微博',
                '新闻舆情',
            ],
            '团队招聘' => [
                '近三年团队人数',
                '专业注册人员',
                '招聘信息',
            ],
            '财务总揽' => [
                '财务总揽'
            ],
            '业务概况' => [
                '业务概况',
                '备案主营产品',//deep
                '主营商品分析',//deep
                '主要成本分析',//deep
                '水费支出',//deep
                '电费支出',//deep
                '燃气支出',//deep
                '热力支出',//deep
                '运输与仓储支出',//deep
                '物业支出',//deep
            ],
            '创新能力' => [
                '专利',
                '软件著作权',
                '商标',
                '作品著作权',
                '证书资质',
            ],
            '税务信息' => [
                '纳税信用等级',
                '税务许可信息',
                '认证登记信息',
                '非正常用户信息',
                '欠税信息',
                '重大税收违法',
            ],
            '行政管理信息' => [
                '行政许可信息',
                '行政处罚信息',
            ],
            '环保信息' => [
                '环保处罚',
                '重点监控企业名单',
                '环保企业自行监测结果',
                '环评公示数据',
            ],
            '海关信息' => [
                '海关基本信息',
                '海关许可',
                '海关信用',
                '海关处罚',
            ],
            '一行两会信息' => [
                '央行行政处罚',
                '银保监会处罚公示',
                '证监会处罚公示',
                '证监会许可信息',
                '外汇局处罚',
                '外汇局许可',
            ],
            '司法涉诉与抵质押信息' => [
                '法院公告',
                '开庭公告',
                '裁判文书',
                '执行公告',
                '失信公告',
                '被执行人信息',
                '查封冻结扣押',
                '动产抵押',
                '股权出质',
                '对外担保',
                '土地抵押',
            ],
            '债权信息' => [
                '应收账款',
                '所有权保留',
            ],
            '债务信息' => [
                '租赁登记',
                '保证金质押登记',
                '仓单质押',
                '其他动产融资',
            ],
            '经营交易分析' => [
                '企业开票情况汇总',//deep
                '企业销项发票分析',//deep
                '年度销项发票情况汇总',//deep
                '月度销项正常发票分析',//deep
                '月度销项红充发票分析',//deep
                '月度销项作废发票分析',//deep
                '单张开票金额TOP10记录',//deep
                '累计开票金额TOP10企业汇总',//deep
                '下游企业汇总分析',//deep
                '下游客户稳定性分析',//deep
                '下游客户集中度分析',//deep
                '企业销售情况分布',//deep
                '企业进项发票分析',//deep
                '年度进项发票情况汇总',//deep
                '月度进项发票分析',//deep
                '累计开票金额TOP10企业汇总',//deep
                '单张开票金额TOP10企业汇总',//deep
                '上游企业汇总分析',//deep
                '游供应商稳定性分析',//deep
                '企业采购情况分布',//deep
            ],
        ];

        $catalogCspKey = [
            [
                'getRegisterInfo',//0-0
                'Beneficiary',//0-1
                'getHistoricalEvolution',//0-2
                'lawPersonInvestmentInfo',//0-3
                'getLawPersontoOtherInfo',//0-4
                'getInvestmentAbroadInfo',//0-5
                'getBranchInfo',//0-6
                'GetCreditCodeNew',//0-7
            ],
            [
                'SearchCompanyFinancings',//1-0
                'TenderSearch',//1-1
                'LandPurchaseList',//1-2
                'LandPublishList',//1-3
                'LandTransferList',//1-4
                'Qualification',//1-5
                'BuildingProject',//1-6
                'BondList',//1-7
                'GetCompanyWebSite',//1-8
                'Microblog',//1-9
                'CompanyNews',//1-10
            ],
            [
                'itemInfo',//2-0
                'BuildingRegistrar',//2-1
                'Recruitment',//2-2
            ],
            [
                'FinanceData'//3-0
            ],
            [
                'SearchCompanyCompanyProducts',//4-0
            ],
            [
                'PatentV4Search',//5-0
                'SearchSoftwareCr',//5-1
                'tmSearch',//5-2
                'SearchCopyRight',//5-3
                'SearchCertification',//5-4
            ],
            [
                'satparty_xin',//6-0
                'satparty_xuke',//6-1
                'satparty_reg',//6-2
                'satparty_fzc',//6-3
                'satparty_qs',//6-4
                'satparty_chufa',//6-5
            ],
            [
                'GetAdministrativeLicenseList',//7-0
                'GetAdministrativePenaltyList',//7-1
            ],
            [
                'epbparty',//8-0
                'epbparty_jkqy',//8-1
                'epbparty_zxjc',//8-2
                'epbparty_huanping',//8-3
            ],
            [
                'custom_qy',//9-0
                'custom_xuke',//9-1
                'custom_credit',//9-2
                'custom_punish',//9-3
            ],
            [
                'pbcparty',//10-0
                'pbcparty_cbrc',//10-1
                'pbcparty_csrc_chufa',//10-2
                'pbcparty_csrc_xkpf',//10-3
                'safe_chufa',//10-4
                'safe_xuke',//10-5
            ],
            [
                'fygg',//11-0
                'ktgg',//11-1
                'cpws',//11-2
                'zxgg',//11-3
                'shixin',//11-4
                'SearchZhiXing',//11-5
                'sifacdk',//11-6
                'getChattelMortgageInfo',//11-7
                'getEquityPledgedInfo',//11-8
                'GetAnnualReport',//11-9
                'GetLandMortgageList',//11-10
            ],
            [
                'company_zdw_yszkdsr',//12-0
                'company_zdw_syqbldsr',//12-1
            ],
            [
                'company_zdw_zldjdsr',//13-0
                'company_zdw_bzjzydsr',//13-1
                'company_zdw_cdzydsr',//13-2
                'company_zdw_qtdcdsr',//13-3
            ],
        ];

        $temp = [];

        $index = trim($index);

        $index = explode(',', $index);

        $index = array_filter($index);

        if (empty($index)) return $index;

        foreach ($index as $oneCatalog) {
            $catalog = explode('-', $oneCatalog);
            if (empty($catalog)) continue;
            $catalog[0] = (int)$catalog[0];
            $catalog[1] = (int)$catalog[1];
            $temp[] = $catalogCspKey[$catalog[0]][$catalog[1]];
        }

        if (empty($temp)) return $temp;

        return $temp;
    }


}
