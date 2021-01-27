<?php

namespace App\Task;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use function Qiniu\explodeUpToken;

class TaskBase
{
    public $qccUrl;
    public $fahaiList;
    public $fahaiDetail;

    //pdf字体大小
    public $pdf_BigTitle = 17;
    public $pdf_LittleTitle = 14;
    public $pdf_Text = 11;

    function __construct()
    {
        $this->qccUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        $this->fahaiList = CreateConf::getInstance()->getConf('fahai.listBaseUrl');
        $this->fahaiDetail = CreateConf::getInstance()->getConf('fahai.detailBaseUrl');

        return true;
    }

    //判断时间用的，时间类的字段，只显示成 Y-m-d
    function formatDate($str)
    {
        return formatDate($str);
    }

    //判断比例用的，只显示成 10%
    function formatPercent($str)
    {
        return formatPercent($str);
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
            ],
            '团队招聘' => [
                '近三年团队人数',
                '专业注册人员',
            ],
            '财务总揽' => [
                '财务总揽'
            ],
            '业务概况' => [
                '业务概况'
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
                '证监处罚公示',
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
                '土地抵押',
                '对外担保',
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
                'GetCompanyWebSite',
            ],
            [
                'itemInfo',
                'BuildingRegistrar',
            ],
            [
                'FinanceData'
            ],
            [
                'SearchCompanyCompanyProducts'
            ],
            [
                'PatentV4Search',
                'SearchSoftwareCr',
                'tmSearch',
                'SearchCopyRight',
                'SearchCertification',
            ],
            [
                'satparty_xin',
                'satparty_xuke',
                'satparty_reg',
                'satparty_fzc',
                'satparty_qs',
                'satparty_chufa',
            ],
            [
                'GetAdministrativeLicenseList',
                'GetAdministrativePenaltyList',
            ],
            [
                'epbparty',
                'epbparty_jkqy',
                'epbparty_zxjc',
                'epbparty_huanping',
            ],
            [
                'custom_qy',
                'custom_xuke',
                'custom_credit',
                'custom_punish',
            ],
            [
                'pbcparty',
                'pbcparty_cbrc',
                'pbcparty_csrc_chufa',
                'pbcparty_csrc_xkpf',
                'safe_chufa',
                'safe_xuke',
            ],
            [
                'fygg',
                'ktgg',
                'cpws',
                'zxgg',
                'shixin',
                'SearchZhiXing',
                'sifacdk',
                'getChattelMortgageInfo',
                'getEquityPledgedInfo',
                'GetLandMortgageList',
                'GetAnnualReport',
            ],
            [
                'company_zdw_yszkdsr',
                'company_zdw_syqbldsr',
            ],
            [
                'company_zdw_zldjdsr',
                'company_zdw_bzjzydsr',
                'company_zdw_cdzydsr',
                'company_zdw_qtdcdsr',
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
