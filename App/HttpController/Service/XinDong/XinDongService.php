<?php

namespace App\HttpController\Service\XinDong;

use App\Csp\Service\CspService;
use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Models\AdminV2\InvoiceTask;
use App\HttpController\Models\AdminV2\InvoiceTaskDetails;
use App\HttpController\Models\Api\CarInsuranceInfo;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\Api\CompanyName;
use App\HttpController\Models\Api\UserSearchHistory;
use App\HttpController\Models\BusinessBase\VendincScale2020Model;
use App\HttpController\Models\EntDb\EntDbNacao;
use App\HttpController\Models\EntDb\EntDbNacaoBasic;
use App\HttpController\Models\EntDb\EntDbNacaoClass;
use App\HttpController\Models\RDS3\HdSaic\CaseAll;
use App\HttpController\Models\RDS3\HdSaic\CaseCheck;
use App\HttpController\Models\RDS3\HdSaic\CaseYzwfsx;
use App\HttpController\Models\RDS3\HdSaic\CompanyAbnormity;
use App\HttpController\Models\RDS3\HdSaic\CompanyAr;
use App\HttpController\Models\RDS3\HdSaic\CompanyArAlterstockinfo;
use App\HttpController\Models\RDS3\HdSaic\CompanyArCapital;
use App\HttpController\Models\RDS3\HdSaic\CompanyArForguaranteeinfo;
use App\HttpController\Models\RDS3\HdSaic\CompanyArForinvestment;
use App\HttpController\Models\RDS3\HdSaic\CompanyArModify;
use App\HttpController\Models\RDS3\HdSaic\CompanyArSocialfee;
use App\HttpController\Models\RDS3\HdSaic\CompanyArWebsiteinfo;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\HdSaic\CompanyCancelInfo;
use App\HttpController\Models\RDS3\HdSaic\CompanyCertificate;
use App\HttpController\Models\RDS3\HdSaic\CompanyFiliation;
use App\HttpController\Models\RDS3\HdSaic\CompanyHistoryInv;
use App\HttpController\Models\RDS3\HdSaic\CompanyHistoryManager;
use App\HttpController\Models\RDS3\HdSaic\CompanyHistoryName;
use App\HttpController\Models\RDS3\HdSaic\CompanyInv;
use App\HttpController\Models\RDS3\HdSaic\CompanyInvestment;
use App\HttpController\Models\RDS3\HdSaic\CompanyIpr;
use App\HttpController\Models\RDS3\HdSaic\CompanyIprChange;
use App\HttpController\Models\RDS3\HdSaic\CompanyLiquidation;
use App\HttpController\Models\RDS3\HdSaic\CompanyModify;
use App\HttpController\Models\RDS3\HdSaic\CompanyMort;
use App\HttpController\Models\RDS3\HdSaic\CompanyMortChange;
use App\HttpController\Models\RDS3\HdSaic\CompanyMortPawn;
use App\HttpController\Models\RDS3\HdSaic\CompanyMortPeople;
use App\HttpController\Models\RDS3\HdSaic\CompanyStockImpawn;
use App\HttpController\Models\RDS3\HdSaicExtension\AggreListedH;
use App\HttpController\Models\RDS3\HdSaic\CompanyManager;
use App\HttpController\Models\RDS3\HdSaicExtension\AggrePicsH;
use App\HttpController\Models\RDS3\HdSaicExtension\CncaRzGltxH;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\Score\xds;
use EasySwoole\Pool\Manager;
use wanghanwanghan\someUtils\control;
use wanghanwanghan\someUtils\traits\Singleton;
use EasySwoole\ElasticSearch\Config;
use EasySwoole\ElasticSearch\ElasticSearch;
use EasySwoole\ElasticSearch\RequestBean\Search;
use App\HttpController\Models\Api\UserBusinessOpportunity;
use App\HttpController\Models\Api\UserCarsRelation;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\XsyA24Logo;
use App\HttpController\Service\PinYin\PinYinService;

class XinDongService extends ServiceBase
{
    use Singleton;

    private $fyyList;
    private $ldUrl;

    // 企业类型
    public $company_org_type_youxian = 10;
    public $company_org_type_youxian_des = '有限责任公司';
    public $company_org_type_youxian2 = 15;
    public $company_org_type_youxian2_des = '有限公司';

    public $company_org_type_gufen = 20;
    public $company_org_type_gufen_des = '股份有限公司';

    public $company_org_type_fengongsi = 25;
    public $company_org_type_fengongsi_des = '分公司';
    public $company_org_type_zongsongsi = 30;
    public $company_org_type_zongsongsi_des = '总公司';

    public $company_org_type_youxianhehuo = 35;
    public $company_org_type_youxianhehuo_des = '有限合伙企业';

    // 成立年限
    public $estiblish_year_under_2 = 2;
    public $estiblish_year_under_2_des = '2年以内';

    public $estiblish_year_2to5 = 5;
    public $estiblish_year_2to5_des = '2-5年';

    public $estiblish_year_5to10 = 10;
    public $estiblish_year_5to10_des = '5-10年';

    public $estiblish_year_10to15 = 15;
    public $estiblish_year_10to15_des = '10-15年';

    public $estiblish_year_15to20 = 20;
    public $estiblish_year_15to20_des = '15-20年';
    public $estiblish_year_more_than_20 = 25;
    public $estiblish_year_more_than_20_des = '20年以上';

    //营业状态  迁入、迁出、、清算 
    public $reg_status_cunxu = 5;
    public $reg_status_cunxu_des = '存续';
    public $reg_status_zaiye = 10;
    public $reg_status_zaiye_des = '在业';
    public $reg_status_diaoxiao = 15;
    public $reg_status_diaoxiao_des = '吊销';
    public $reg_status_zhuxiao = 20;
    public $reg_status_zhuxiao_des = '注销';
    public $reg_status_tingye = 25;
    public $reg_status_tingye_des = '停业';

    //注册资本
    public $reg_capital_50 = 5;
    // public $reg_capital_50_des = '50万元以下' 
    public $reg_capital_50_des = '微型';
    public $reg_capital_50to100 = 10;
    // public $reg_capital_50to100_des = '50-100万' 
    public $reg_capital_50to100_des = '小型C类';
    public $reg_capital_100to200 = 15;
    // public $reg_capital_100to200_des = '100-200万';
    public $reg_capital_100to200_des = '小型B类';
    public $reg_capital_200to500 = 20;
    // public $reg_capital_200to500_des = '200-500万';
    public $reg_capital_200to500_des = '小型A类';
    public $reg_capital_500to1000 = 25;
    // public $reg_capital_500to1000_des = '500-1000万';
    public $reg_capital_500to1000_des = '中型C类';
    public $reg_capital_1000to10000 = 30;
    // public $reg_capital_1000to10000_des = '1000万-1亿';
    public $reg_capital_1000to10000_des = '中型B类';
    // public $reg_capital_10000to100000 = 35;
    // public $reg_capital_10000to100000_des = '1亿-10亿'; 
    // public $reg_capital_10000to100000_des = '中型A类'; 

    public $reg_capital_minddle_a = 40;
    public $reg_capital_minddle_a_des = '中型A类';

    public $reg_capital_big_c = 45;
    public $reg_capital_big_c_des = '大型C类';

    public $reg_capital_big_b = 50;
    public $reg_capital_big_b_des = '大型B类';
    public $reg_capital_big_A = 60;
    public $reg_capital_big_A_des = '大型A类';

    public $reg_capital_super_big_C = 65;
    public $reg_capital_super_big_C_des = '特大型C类';
    public $reg_capital_super_big_B = 70;
    public $reg_capital_super_big_B_des = '特大型B类';
    public $reg_capital_super_big_A = 80;
    public $reg_capital_super_big_A_des = '特大型A类';

    function __construct()
    {
        $this->fyyList = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $this->ldUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');

        return parent::__construct();
    }

    // 获取注册资本
    function getRegCapital($getAll = false)
    {
        $map = [
            $this->reg_capital_50 => $this->reg_capital_50_des,
            $this->reg_capital_50to100 => $this->reg_capital_50to100_des,
            $this->reg_capital_100to200 => $this->reg_capital_100to200_des,
            $this->reg_capital_200to500 => $this->reg_capital_200to500_des,
            $this->reg_capital_500to1000 => $this->reg_capital_500to1000_des,
            $this->reg_capital_1000to10000 => $this->reg_capital_1000to10000_des,
            //    $this->reg_capital_10000to100000  =>  $this->reg_capital_10000to100000_des,
            $this->reg_capital_minddle_a => $this->reg_capital_minddle_a_des,
            $this->reg_capital_big_c => $this->reg_capital_big_c_des,
            $this->reg_capital_big_b => $this->reg_capital_big_b_des,
            $this->reg_capital_big_A => $this->reg_capital_big_A_des,
            $this->reg_capital_super_big_C => $this->reg_capital_super_big_C_des,
            $this->reg_capital_super_big_B => $this->reg_capital_super_big_B_des,
            $this->reg_capital_super_big_A => $this->reg_capital_super_big_A_des,
        ];

        if ($getAll) {
            return array_merge($map, [0 => '全部']);
        }
        return $map;
    }

    // 获取营业状态
    function getRegStatus($getAll = false)
    {
        $map = [
            $this->reg_status_cunxu => $this->reg_status_cunxu_des,
            $this->reg_status_zaiye => $this->reg_status_zaiye_des,
            $this->reg_status_diaoxiao => $this->reg_status_diaoxiao_des,
            $this->reg_status_zhuxiao => $this->reg_status_zhuxiao_des,
            $this->reg_status_tingye => $this->reg_status_tingye_des,
        ];

        if ($getAll) {
            return array_merge($map, [0 => '全部']);
        }
        return $map;

    }

    /**
     * "1": {
     * "cname": "在营（开业）",
     * "detail": ""
     * },
     * "2": {
     * "cname": "吊销",
     * "detail": ""
     * },
     * "3": {
     * "cname": "注销",
     * "detail": ""
     * },
     * "4": {
     * "cname": "迁出",
     * "detail": ""
     * },
     * "8": {
     * "cname": "停业",
     * "detail": ""
     * },
     * "9": {
     * "cname": "其他",
     * "detail": ""
     * }
     * 1    在营（开业）
     * 2    吊销
     * 21    吊销，未注销
     * 22    吊销，已注销
     * 3    注销
     * 4    迁出
     * 5    撤销
     * 6    临时(个体工商户使用)
     * 8    停业
     * 9    其他
     * 9_01    撤销
     * 9_02    待迁入
     * 9_03    经营期限届满
     * 9_04    清算中
     * 9_05    停业
     * 9_06    拟注销
     * 9_07    非正常户
     * 30    正在注销
     * !    -
     */
    function getRegStatusV2($getAll = false)
    {
        $map = [
            1 => $this->reg_status_cunxu_des,
            $this->reg_status_zaiye => $this->reg_status_zaiye_des,
            $this->reg_status_diaoxiao => $this->reg_status_diaoxiao_des,
            $this->reg_status_zhuxiao => $this->reg_status_zhuxiao_des,
            $this->reg_status_tingye => $this->reg_status_tingye_des,
        ];

        if ($getAll) {
            return array_merge($map, [0 => '全部']);
        }
        return $map;

    }

    // 获取企业成立年限
    function getEstiblishYear($getAll = false)
    {
        $map = [
            $this->estiblish_year_under_2 => $this->estiblish_year_under_2_des,
            $this->estiblish_year_2to5 => $this->estiblish_year_2to5_des,
            $this->estiblish_year_5to10 => $this->estiblish_year_5to10_des,
            $this->estiblish_year_10to15 => $this->estiblish_year_10to15_des,
            $this->estiblish_year_15to20 => $this->estiblish_year_15to20_des,
            $this->estiblish_year_more_than_20 => $this->estiblish_year_more_than_20_des,
        ];

        if ($getAll) {
            return array_merge($map, [0 => '全部']);
        }
        return $map;

    }

    // 获取企业类型
    function getCompanyOrgType($getAll = false)
    {
        $map = [
            $this->company_org_type_youxian => $this->company_org_type_youxian_des,
            $this->company_org_type_youxian2 => $this->company_org_type_youxian2_des,
            $this->company_org_type_gufen => $this->company_org_type_gufen_des,
            $this->company_org_type_fengongsi => $this->company_org_type_fengongsi_des,
            $this->company_org_type_zongsongsi => $this->company_org_type_zongsongsi_des,
            $this->company_org_type_youxianhehuo => $this->company_org_type_youxianhehuo_des,
            40 => '外商独资公司',
            50 => '个人独资企业',
            60 => '国有独资公司',
        ];

        if ($getAll) {
            return array_merge($map, [0 => '全部']);
        }
        return $map;

    }

    //处理结果给信息controller
    private function checkResp($code, $paging, $result, $msg)
    {
        return $this->createReturn((int)$code, $paging, $result, $msg);
    }

    //控股法人股东的司法风险
    function getCorporateShareholderRisk(string $entName): array
    {
        $csp = CspService::getInstance()->create();

        //====================债务-顶====================
        //融资租赁
        $docType = 'company_zdw_zldjdsr';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'zdw', $postData);
        });
        //其他动产融资
        $docType = 'company_zdw_qtdcdsr';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'zdw', $postData);
        });
        //====================债务-底====================

        //====================债权-顶====================
        //保证金质押登记
        $docType = 'company_zdw_bzjzydsr';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'zdw', $postData);
        });
        //应收账款登记
        $docType = 'company_zdw_yszkdsr';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'zdw', $postData);
        });
        //仓单质押登记
        $docType = 'company_zdw_cdzydsr';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'zdw', $postData);
        });
        //所有权保留
        $docType = 'company_zdw_syqbldsr';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'zdw', $postData);
        });
        //====================债权-底====================

        //欠税公告
        $docType = 'satparty_qs';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'sat', $postData);
        });

        //涉税处罚公示
        $docType = 'satparty_chufa';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'sat', $postData);
        });

        //税务非正常户
        $docType = 'satparty_fzc';
        $csp->add($docType, function () use ($entName, $docType) {
            $postData = [
                'doc_type' => $docType,
                'keyword' => $entName,
                'pageno' => 1,
                'range' => 10,
            ];
            return (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'sat', $postData);
        });

        //执行
        $res = CspService::getInstance()->exec($csp);

        //整理返回数组
        foreach ($res as $key => $arr) {
            if ($arr['code'] === 200 && !empty($arr['paging'])) {
                $num = $arr['paging']['total'];
            } else {
                $num = 0;
            }

            $result[$key] = $num;
        }

        return $this->checkResp(200, null, $result ?? [], '查询成功');
    }

    //历史沿革
    function getHistoricalEvolution(string $entName): array
    {
        $csp = CspService::getInstance()->create();

        //淘数 变更信息
        $csp->add('getRegisterChangeInfo', function () use ($entName) {

            $data = [];

            $page = 1;

            do {

                $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                    'entName' => $entName,
                    'pageNo' => $page,
                    'pageSize' => 100,
                ], 'getRegisterChangeInfo');

                if ($res['code'] != 200 || empty($res['result'])) break;

                //如果本次取到了，就循环找
                foreach ($res['result'] as $one) {

                    if ($one['ALTITEM'] == '法定代表人') {

                        $data[] = $one['ALTDATE'] . "，法人变更前：{$one['ALTBE']}，法人变更后：{$one['ALTAF']}";
                    }

                    if ($one['ALTITEM'] == '董事' || $one['ALTITEM'] == '监事' || $one['ALTITEM'] == '高管') {

                        $job = $one['ALTITEM'];

                        $beStr = $afStr = [];

                        //找出变更 前 的董监高
                        foreach (array_filter(explode(';', $one['ALTBE'])) as $two) {

                            if (!preg_match("/职务:{$job}/", $two)) continue;

                            //如果查到了，取出姓名
                            preg_match_all('/姓名:(.*)\,/U', $two, $nameArray);

                            if (count($nameArray) != 2 || empty($nameArray[1])) continue;

                            //取出姓名
                            $name = current($nameArray[1]);

                            //拼接字符串
                            $beStr[] = $name;
                        }

                        //找出变更 后 的董监高
                        foreach (array_filter(explode(';', $one['ALTAF'])) as $two) {

                            if (!preg_match("/职务:{$job}/", $two)) continue;

                            //如果查到了，取出姓名
                            preg_match_all('/姓名:(.*)\,/U', $two, $nameArray);

                            if (count($nameArray) != 2 || empty($nameArray[1])) continue;

                            //取出姓名
                            $name = current($nameArray[1]);

                            //拼接字符串
                            $afStr[] = $name;
                        }

                        //历史大变革就这里有用，别的$beStr和$afStr没用
                        $beStr = implode('，', $beStr);
                        $afStr = implode('，', $afStr);
                        $data[] = $one['ALTDATE'] . "，{$job}变更前：{$beStr}，{$job}变更后：{$afStr}";
                    }
                }

                $page++;

            } while ($page <= 5);

            return empty($data) ? null : $data;
        });

        //龙盾 融资
        $csp->add('SearchCompanyFinancings', function () use ($entName) {

            $data = [];

            $postData = ['searchKey' => $entName];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'CompanyFinancingSearch/GetList', $postData);//BusinessStateV4/SearchCompanyFinancings
            CommonService::getInstance()->log4PHP($res, 'info', 'CompanyFinancingSearch_GetList');

            if (empty($res['result'])) return null;

            foreach ($res['result']['Data'] as $one) {
                $data[] = $one['Date'] . "，拿到了来自{$one['Investment']}的{$one['Round']}融资，{$one['Amount']}";
            }

            return empty($data) ? null : $data;
        });

        //龙盾 行政许可 只要数字
        $csp->add('GetAdministrativeLicenseList', function () use ($entName) {

            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'AdminLicenseCheck/GetList', $postData);//ADSTLicense/GetAdministrativeLicenseList

            ($res['code'] === 200 && !empty($res['paging'])) ? $total = (int)$res['paging']['total'] : $total = 0;

            return $total;
        });

        //龙盾 专利 只要数字
        $csp->add('PatentSearch', function () use ($entName) {

            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'PatentV4/Search', $postData);

            ($res['code'] === 200 && !empty($res['paging'])) ? $total = (int)$res['paging']['total'] : $total = 0;

            return $total;
        });

        //淘数 分支机构
        $csp->add('getBranchInfo', function () use ($entName) {

            $data = [];

            $page = 1;

            do {

                $postData = [
                    'entName' => $entName,
                    'pageNo' => $page,
                    'pageSize' => 20,
                ];

                $res = (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getBranchInfo');

                if ($res['code'] != 200 || empty($res['result'])) break;

                foreach ($res['result'] as $one) {

                    $data[] = $one['ESDATE'] . "，{$one['ENTNAME']}成立了，当前状态是{$one['ENTSTATUS']}";
                }

                $page++;

            } while ($page <= 5);

            return empty($data) ? null : $data;
        });

        //龙盾 土地资源
        $csp->add('landResources', function () use ($entName) {

            $csp = CspService::getInstance()->create();

            //土地抵押
            $csp->add('GetLandMortgageList', function () use ($entName) {

                $data = [];

                $page = 1;

                do {

                    $postData = [
                        'keyWord' => $entName,
                        'pageIndex' => $page,
                        'pageSize' => 50,
                    ];

                    $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandMortgage/GetLandMortgageList', $postData);

                    if ($res['code'] != 200 || empty($res['result'])) break;

                    foreach ($res['result'] as $one) {
                        $data[] = "在{$one['StartDate']}到{$one['EndDate']}期间，抵押了位于{$one['Address']}的{$one['MortgageAcreage']}公顷{$one['MortgagePurpose']}";
                    }

                    $page++;

                } while ($page <= 5);

                return empty($data) ? null : $data;
            });

            //土地公示
            $csp->add('LandPublishList', function () use ($entName) {

                $data = [];

                $page = 1;

                do {

                    $postData = [
                        'searchKey' => $entName,
                        'pageIndex' => $page,
                        'pageSize' => 50,
                    ];

                    $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandMergeCheck/GetList', $postData);//LandPublish/LandPublishList

                    if ($res['code'] != 200 || empty($res['result'])) break;

                    foreach ($res['result']['Data'] as $one) {
                        $data[] = "{$one['PublishDate']}，由{$one['PublishGov']}公示了位于{$one['AdminArea']}{$one['Address']}的土地";
                    }

                    $page++;

                } while ($page <= 5);

                return empty($data) ? null : $data;
            });

            //购地信息
            $csp->add('LandPurchaseList', function () use ($entName) {

                $data = [];

                $page = 1;

                do {

                    $postData = [
                        'searchKey' => $entName,
                        'pageIndex' => $page,
                        'pageSize' => 50,
                    ];

                    $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandPurchase/LandPurchaseList', $postData);

                    if ($res['code'] != 200 || empty($res['result'])) break;

                    foreach ($res['result']['Data'] as $one) {
                        $data[] = "{$one['SignTime']}，通过{$one['SupplyWay']}购得位于{$one['AdminArea']}{$one['Address']}{$one['Area']}公顷的{$one['LandUse']}";
                    }

                    $page++;

                } while ($page <= 5);

                return empty($data) ? null : $data;
            });

            //土地转让
            $csp->add('LandTransferList', function () use ($entName) {

                $data = [];

                $page = 1;

                do {

                    $postData = [
                        'searchKey' => $entName,
                        'pageIndex' => $page,
                        'pageSize' => 50,
                    ];

                    $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandMarketDealCheck/GetList', $postData);//LandTransfer/LandTransferList

                    if ($res['code'] != 200 || empty($res['result'])) break;

                    foreach ($res['result']['Data'] as $one) {
                        $data[] = "位于{$one['Address']}的土地转让给{$one['NewOwner']['Name']}";
                    }

                    $page++;

                } while ($page <= 5);

                return empty($data) ? null : $data;
            });

            $res = CspService::getInstance()->exec($csp);

            return $res;
        });

        //执行
        $res = CspService::getInstance()->exec($csp);

        //先整理文字的
        $tmp = [];
        $tmp[] = control::array_flatten($res['SearchCompanyFinancings']);
        $tmp[] = control::array_flatten($res['landResources']);
        $tmp[] = control::array_flatten($res['getBranchInfo']);
        $tmp[] = control::array_flatten($res['getRegisterChangeInfo']);
        $tmp = control::array_flatten($tmp);
        $tmp = array_filter($tmp);
        sort($tmp);
        //再整理数字
        $res['PatentSearch'] > 0 ? $said = "共有{$res['PatentSearch']}个专利，具体登录 信动客动 查看" : $said = "共有{$res['PatentSearch']}个专利";
        array_push($tmp, $said);
        $res['GetAdministrativeLicenseList'] > 0 ? $said = "共有{$res['GetAdministrativeLicenseList']}个行政许可，具体登录 信动客动 查看" : $said = "共有{$res['GetAdministrativeLicenseList']}个行政许可";
        array_push($tmp, $said);

        return $this->checkResp(200, null, $tmp, '查询成功');
    }

    //产品标准
    function getProductStandard($entName, $page, $pageSize)
    {
        try {
            $mysqlObj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))->getObj();

            $mysqlObj->queryBuilder()->where('ORG_NAME', $entName)
                ->limit($this->exprOffset($page, $pageSize), $pageSize)
                ->get('qyxx');

            $list = $mysqlObj->execBuilder();

            $mysqlObj->queryBuilder()->where('ORG_NAME', $entName)->get('qyxx');

            $total = $mysqlObj->execBuilder();

            empty($total) ? $total = 0 : $total = count($total);

        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);

            return ['code' => 201, 'paging' => null, 'result' => null, 'msg' => '获取mysql错误'];

        } finally {
            Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'))->recycleObj($mysqlObj);
        }

        return $this->checkResp(200, ['page' => $page, 'pageSize' => $pageSize, 'total' => $total], $list, '查询成功');
    }

    //资产线索
    function getAssetLeads($entName)
    {
        $csp = CspService::getInstance()->create();

        //龙盾 购地信息
        $csp->add('LandPurchaseList', function () use ($entName) {
            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 5,
            ];
            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandPurchase/LandPurchaseList', $postData);
            return empty($res['paging']) ? 0 : $res['paging']['total'];
        });

        //龙盾 土地公示
        $csp->add('LandPublishList', function () use ($entName) {
            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 5,
            ];
            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandMergeCheck/GetList', $postData);//LandPublish/LandPublishList
            return empty($res['paging']) ? 0 : $res['paging']['total'];
        });

        //龙盾 土地转让
        $csp->add('LandTransferList', function () use ($entName) {
            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 5,
            ];
            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandMarketDealCheck/GetList', $postData);//LandTransfer/LandTransferList
            return empty($res['paging']) ? 0 : $res['paging']['total'];
        });

        //产品标准
        $csp->add('ProductStandard', function () use ($entName) {
            $res = $this->getProductStandard($entName, 1, 10);
            return empty($res['paging']) ? 0 : $res['paging']['total'];
        });

        //执行
        $res = CspService::getInstance()->exec($csp);
        $tmp = [];
        $tmp['LandPurchaseList'] = $res['LandPurchaseList'];
        $tmp['LandPublishList'] = $res['LandPublishList'];
        $tmp['LandTransferList'] = $res['LandTransferList'];
        $tmp['ProductStandard'] = $res['ProductStandard'];

        return $this->checkResp(200, null, $tmp, '查询成功');
    }

    //非企信息
    function getNaCaoRegisterInfo($post_data): array
    {
        $entName = $post_data['entName'];

        if (empty($entName)) return $this->checkResp(200, null, null, '查询条件是空');

        $check = mb_substr($entName, 0, 5);

        $basic_model = EntDbNacaoBasic::create();

        is_numeric($check) ?
            $basic_model->where('UNISCID', $entName) :
            $basic_model->where('ENTNAME', $entName);

        $ent_info = $basic_model->get();

        if (!empty($ent_info)) {
            //补充信息
            $ent_info->ishistory = EntDbNacao::create()->where([
                'UNISCID' => $ent_info->getAttr('UNISCID'),
                'ishistory' => 1,
            ])->all();
            $ent_info->latlng = EntDbNacaoClass::create()
                ->where('entid', $ent_info->getAttr('entid'))
                ->field(['lat', 'lng'])
                ->get();
        }

        return $this->checkResp(200, null, $ent_info, '查询成功');
    }

    //二次特征分数
    function getFeatures($entName): array
    {
        //查看经营范围
        $postData = ['entName' => $entName];
        $OPSCOPE = (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getRegisterInfo');
        $OPSCOPE = current($OPSCOPE['result']);
        $OPSCOPE = $OPSCOPE['OPSCOPE'];

        if (mb_strpos($OPSCOPE, '教育') !== false) {
            $topList = [
                '新东方教育科技集团有限公司',
                '北京世纪好未来教育科技有限公司',
                '北京高途云集教育科技有限公司',
                '中公教育科技股份有限公司',
                '北京新东方迅程网络科技股份有限公司',
                '神州天立控股集团有限公司',
                '深圳中教控股集团有限公司',
                '四川希望教育产业集团有限公司',
                '作业帮教育科技（北京）有限公司',
                '北京猿力教育科技有限公司',
            ];
        } elseif (mb_strpos($OPSCOPE, '新能源汽车') !== false) {
            $topList = [
                '上汽通用五菱汽车股份有限公司',
                '比亚迪股份有限公司',
                '特斯拉（上海）有限公司',
                '长城汽车股份有限公司',
                '上海汽车集团股份有限公司',
                '奇瑞汽车股份有限公司',
                '一汽—大众汽车有限公司',
                '上汽大众汽车有限公司',
                '广州汽车集团股份有限公司',
                '蔚来控股有限公司',
                '北京汽车股份有限公司',
                '东风汽车股份有限公司',
                '广东小鹏汽车科技有限公司',
                '北京车和家信息技术有限公司',
                '浙江吉利控股集团有限公司',
                '重庆长安汽车股份有限公司',
            ];
        } elseif (mb_strpos($OPSCOPE, '计算机软件') !== false || mb_strpos($OPSCOPE, '计算机硬件') !== false) {
            $topList = [
                '阿里云计算有限公司',
                '腾讯云计算（北京）有限责任公司',
                '华为云计算技术有限公司',
                '百度云计算技术（北京）有限公司',
                '珠海金山云科技有限公司',
                '优刻得科技股份有限公司',
                '京东云计算有限公司',
                '新华三云计算技术有限公司',
                '杭州朗和科技有限公司',
                '浪潮云信息技术股份公司',
            ];
        } else {
            $topList = [];
        }

        $res = (new xds())->cwScore($entName);

        if (!empty($topList)) {
            $csp = CspService::getInstance()->create();
            foreach ($topList as $key => $ent) {
                $csp->add($key . '_', function () use ($ent) {
                    return (new xds())->cwScore($ent);
                });
            }
            $top = CspService::getInstance()->exec($csp);
            $field = [
                'MAIBUSINC_yoy' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'ASSGRO_yoy' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'PROGRO' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'PROGRO_yoy' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'RATGRO' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'TBR' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'ASSGROPROFIT_REL' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'ASSETS' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'TOTEQU' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'DEBTL_H' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'DEBTL' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'ATOL' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'PERCAPITA_C' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'PERCAPITA_Y' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'RepaymentAbility' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'GuaranteeAbility' => ['score' => 0, 'num' => 0, 'pic' => ''],
                'TBR_new' => ['score' => 0, 'num' => 0, 'pic' => ''],
            ];
            foreach ($top as $one) {
                $keys = array_keys($field);
                foreach ($one as $key => $arr) {
                    if (in_array($arr['field'], $keys, true) && is_numeric($arr['score'])) {
                        $field[$arr['field']]['score'] += $arr['score'];
                        $field[$arr['field']]['num']++;
                    }
                }
            }
            foreach ($field as $key => $one) {
                $avgScore = round($one['score'] / $one['num']);
                $field[$key]['score'] = $avgScore;
                if (is_numeric($avgScore)) {
                    if ($avgScore < 20) {
                        $angle = 4.9;
                        $word = '弱';
                    } elseif ($avgScore < 40) {
                        $angle = 5.6;
                        $word = '较弱';
                    } elseif ($avgScore < 60) {
                        $angle = 0;
                        $word = '中等';
                    } elseif ($avgScore < 80) {
                        $angle = 0.7;
                        $word = '较强';
                    } else {
                        $angle = 1.4;
                        $word = '强';
                    }
                } else {
                    $angle = 0;
                    $word = '无';
                }
                $field[$key]['pic'] = CommonService::getInstance()->createDashboardPic($angle, $word);
            }
        }

        foreach ($res as $key => $one) {
            if (is_numeric($one['score'])) {
                if ($one['score'] < 20) {
                    $angle = 4.9;
                    $word = '弱';
                } elseif ($one['score'] < 40) {
                    $angle = 5.6;
                    $word = '较弱';
                } elseif ($one['score'] < 60) {
                    $angle = 0;
                    $word = '中等';
                } elseif ($one['score'] < 80) {
                    $angle = 0.7;
                    $word = '较强';
                } else {
                    $angle = 1.4;
                    $word = '强';
                }
            } else {
                $angle = 0;
                $word = '无';
            }
            if (isset($field)) {
                $res[$key]['topPic'] = $field[$key]['pic'];
                $res[$key]['topScore'] = $field[$key]['score'];
            }
            $res[$key]['pic'] = CommonService::getInstance()->createDashboardPic($angle, $word);
        }

        return $this->checkResp(200, null, $res, isset($field) ? '有top' : '无top');
    }

    //二次特征分数
    function getFeaturesTwo($entName): array
    {
        list($res, $data) = (new xds())->cwScoreTwo($entName);
        return $this->checkResp(200, null, ['res' => $res, 'data' => $data], isset($field) ? '有top' : '无top');
    }

    //二次特征分数
    function getFeaturesForApi($entName): array
    {
        $res = (new xds())->cwScore($entName);
        return $this->checkResp(200, null, $res, 'success');
    }

    function industryTop($fz_list, $fm_list): array
    {
        foreach ($fz_list as $key => $oneEnt) {
            $postData = [
                'entName' => $oneEnt,
                'code' => '',
                'beginYear' => date('Y') - 1,
                'dataCount' => 4,
            ];
            $info = (new LongXinService())->setCheckRespFlag(true)->getFinanceData($postData, false);
            $fz_list[$key] = [
                'entName' => $oneEnt,
                'info' => $info,
            ];
        }

        foreach ($fm_list as $key => $oneEnt) {
            $postData = [
                'entName' => $oneEnt,
                'code' => '',
                'beginYear' => date('Y') - 1,
                'dataCount' => 4,
            ];
            $info = (new LongXinService())->setCheckRespFlag(true)->getFinanceData($postData, false);
            $fm_list[$key] = [
                'entName' => $oneEnt,
                'info' => $info,
            ];
        }

        return [
            'fz_list' => $fz_list,
            'fm_list' => $fm_list,
        ];
    }

    //2020年营收规模
    function getVendincScale(?string $code, int $year = 2020): ?string
    {
        if (empty($code)) return '';

        if (substr($code, 0, 1) === '9') {
            $where = ['code' => $code];
        } else {
            $where = ['entname' => $code];
        }

        $scale = VendincScale2020Model::create()->where($where)->get();

        return empty($scale) ? '' : $scale->getAttr('label');
    }

    //2020年营收规模标签转换
    function vendincScaleLabelChange(string $label): array
    {
        if (empty($label)) return ['未找到', '未找到'];
        if ($label === 'F') return ['F', '负数'];

        $label_num = substr($label, 1) - 0;

        switch ($label_num) {
            case $label_num <= 2:
                $after_change_num = 1;
                $desc = '微型，一般指规模在100万以下';
                break;
            case $label_num <= 4:
                $after_change_num = 2;
                $desc = '小型C类，一般指规模在100万以上，500万以下';
                break;
            case $label_num <= 5:
                $after_change_num = 3;
                $desc = '小型B类，一般指规模在500万以上，1000万以下';
                break;
            case $label_num <= 7:
                $after_change_num = 4;
                $desc = '小型A类，一般指规模在1000万以上，3000万以下';
                break;
            case $label_num <= 9:
                $after_change_num = 5;
                $desc = '中型C类，一般指规模在3000万以上，5000万以下';
                break;
            case $label_num <= 12:
                $after_change_num = 6;
                $desc = '中型B类，一般指规模在5000万以上，8000万以下';
                break;
            case $label_num <= 14:
                $after_change_num = 7;
                $desc = '中型A类，一般指规模在8000万以上，1亿以下';
                break;
            case $label_num <= 18:
                $after_change_num = 8;
                $desc = '大型C类，一般指规模在1亿以上，5亿以下';
                break;
            case $label_num <= 23:
                $after_change_num = 9;
                $desc = '大型B类，一般指规模在5亿以上，10亿以下';
                break;
            case $label_num <= 27:
                $after_change_num = 10;
                $desc = '大型A类，一般指规模在10亿以上，50亿以下';
                break;
            case $label_num <= 32:
                $after_change_num = 11;
                $desc = '特大型C类，一般指规模在50亿以上，100亿以下';
                break;
            case $label_num <= 36:
                $after_change_num = 12;
                $desc = '特大型B类，一般指规模在100亿以上，500亿以下';
                break;
            default:
                $after_change_num = 13;
                $desc = '特大型A类，一般指规模在500亿以上';
        }

        return ['A' . $after_change_num, $desc];
    }

    function getNicCode($postData): ?array
    {
        if (empty($postData['entName']) && empty($postData['code'])) {
            return $this->checkResp(500, null, [], '请传entName或者code');
        }
        //先查询四级分类标签
        $sql = 'select * from si_ji_fen_lei  where 1=1';
        if (!empty($postData['entName'])) {
            $sql .= " and entName = '{$postData['entName']}' ";
        } elseif (empty($postData['entName']) && !empty($postData['code'])) {
            $sql .= " and code1 = '{$postData['code']}'";
        }

        $sql .= ' limit 1';
        $res = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_si_ji_fen_lei'));
        if (empty($res)) {
            return $this->checkResp(200, null, [], '查询成功');
        }
        //然后用code5去nic_code表中查询full_name
        $retData = [];
        foreach ($res as $v) {
            $retData [] = sqlRaw("select full_name from nic_code where nic_id = '{$v['code5']}'", CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_nic_code'));
        }
        CommonService::getInstance()->log4PHP($retData, 'info', 'getNicCode_nic_code');

        return $this->checkResp(200, null, $retData, '查询成功');
    }

    function searchClue()
    {
        $es = (new ElasticSearchService())->createSearchBean()->getBody();


    }

    /**
     * 高级搜索支持的搜索条件
     * 返回示例
     * 注意：type：radio 单选  select 多选
     *
     */
    function getSearchOption($postData = [])
    {

        return [
//           [
//                'pid' => 10,
//                'desc' => '企业类型',
//                'detail' => '',
//                'key' => 'company_org_type',
//                'type' => 'select',
//                'data' =>  [
//                    $this->company_org_type_youxian => [
//                        'cname' =>$this->company_org_type_youxian_des,
//                        'detail' => '',
//                    ],
//                    $this->company_org_type_youxian2 => [
//                        'cname' => $this->company_org_type_youxian2_des,
//                        'detail' => '',
//                    ],
//                    $this->company_org_type_gufen => [
//                        'cname' =>  $this->company_org_type_gufen_des,
//                        'detail' => '',
//                    ],
//                    $this->company_org_type_fengongsi => [
//                        'cname' => $this->company_org_type_fengongsi_des,
//                        'detail' => '',
//                    ],
//                    $this->company_org_type_zongsongsi => [
//                        'cname' => $this->company_org_type_zongsongsi_des,
//                        'detail' => '',
//                    ],
//                    $this->company_org_type_youxianhehuo => [
//                        'cname' => $this->company_org_type_youxianhehuo_des,
//                        'detail' => '',
//                    ],
//                    40 => [
//                        'cname' =>  '外商独资公司',
//                        'detail' => '',
//                    ],
//                    50 =>  [
//                        'cname' =>  '个人独资企业',
//                        'detail' => '',
//                    ],
//                    60 =>  [
//                        'cname' =>  '国有独资公司',
//                        'detail' => '',
//                    ],
//                ],
//            ],


            [
                'pid' => 20,
                'desc' => '成立年限',
                'detail' => '',
                'key' => 'estiblish_year_nums',
                'type' => 'select',
                'data' => [
                    $this->estiblish_year_under_2 => [
                        'cname' => $this->estiblish_year_under_2_des,
                        'detail' => '',
                        'min' => 0,
                        'max' => 2,
                    ],
                    $this->estiblish_year_2to5 => [
                        'cname' => $this->estiblish_year_2to5_des,
                        'detail' => '',
                        'min' => 2,
                        'max' => 5,
                    ],
                    $this->estiblish_year_5to10 => [
                        'cname' => $this->estiblish_year_5to10_des,
                        'detail' => '',
                        'min' => 5,
                        'max' => 10,
                    ],
                    $this->estiblish_year_10to15 => [
                        'cname' => $this->estiblish_year_10to15_des,
                        'detail' => '',
                        'min' => 10,
                        'max' => 15,
                    ],
                    $this->estiblish_year_15to20 => [
                        'cname' => $this->estiblish_year_15to20_des,
                        'detail' => '',
                        'min' => 15,
                        'max' => 20,
                    ],
                    $this->estiblish_year_more_than_20 => [
                        'cname' => $this->estiblish_year_more_than_20_des,
                        'detail' => '',
                        'min' => 20,
                        'max' => 2000,
                    ],
                ],
            ],
//             [
//                'pid' => 30,
//                'desc' => '营业状态',
//                'detail' => '',
//                'key' => 'reg_status',
//                'type' => 'select',
//                'data' => [
//                    $this->reg_status_cunxu  =>  [
//                        'cname' => $this->reg_status_cunxu_des,
//                        'detail' => '',
//                    ],
//                    $this->reg_status_zaiye  =>  [
//                        'cname' => $this->reg_status_zaiye_des,
//                        'detail' => '',
//                    ],
//                    $this->reg_status_diaoxiao  =>  [
//                        'cname' => $this->reg_status_diaoxiao_des,
//                        'detail' => '',
//                    ],
//                    $this->reg_status_zhuxiao  =>  [
//                        'cname' => $this->reg_status_zhuxiao_des,
//                        'detail' => '',
//                    ],
//                    $this->reg_status_tingye  => [
//                        'cname' => $this->reg_status_tingye_des,
//                        'detail' => '',
//                    ],
//                ],
//            ],
            [
                'pid' => 30,
                'desc' => '营业状态',
                'detail' => '',
                'key' => 'reg_status',
                'type' => 'select',
                'data' => [
                    1 => [
                        'cname' => '在营（开业）',
                        'detail' => '',
                    ],
                    2 => [
                        'cname' => '吊销',
                        'detail' => '',
                    ],
                    3 => [
                        'cname' => '注销',
                        'detail' => '',
                    ],
                    4 => [
                        'cname' => '迁出',
                        'detail' => '',
                    ],
                    8 => [
                        'cname' => '停业',
                        'detail' => '',
                    ],
                    9 => [
                        'cname' => '其他',
                        'detail' => '',
                    ],
                ],
            ],
            [
                'pid' => 40,
                'desc' => '注册资本',
                'detail' => '',
                'key' => 'reg_capital',
                'type' => 'select',
                // 'data' => $this->getRegCapital(),
                'data' => [
                    10 => [
                        'cname' => '100万以内',
                        'detail' => '',
                        'min' => 0,
                        'max' => 100,
                    ],
                    15 => [
                        'cname' => '100-500万',
                        'detail' => '',
                        'min' => 100,
                        'max' => 500,
                    ],
                    20 => [
                        'cname' => '500-1000万',
                        'detail' => '',
                        'min' => 500,
                        'max' => 1000,
                    ],
                    25 => [
                        'cname' => '1000万-5000万',
                        'detail' => '',
                        'min' => 1000,
                        'max' => 5000,
                    ],
                    30 => [
                        'cname' => '5000万-1亿',
                        'detail' => '',
                        'min' => 5000,
                        'max' => 10000,
                    ],
                    35 => [
                        'cname' => '1亿-10亿',
                        'detail' => '',
                        'min' => 10000,
                        'max' => 100000,
                    ],
                    40 => [
                        'cname' => '10亿以上',
                        'detail' => '',
                        'min' => 100000,
                        'max' => 10000000,
                    ],
                ],
            ],
//            [
//                'pid' => 50,
//                'desc' => '营收规模',
//                'key' => 'ying_shou_gui_mo',
//                'detail' => '',
//                'type' => 'select',
//                'data' => [
//                    $this->reg_capital_50 => [
//                        'cname' => $this->reg_capital_50_des,
//                        'detail' => '100万以下',
//                        'min' => 0,
//                        'max' => 1000000,
//                    ],
//                    $this->reg_capital_50to100 => [
//                        'cname' => $this->reg_capital_50to100_des,
//                        'detail' => '100万以上，500万以下',
//                        'min' => 1000000,
//                        'max' => 5000000,
//                    ],
//                    $this->reg_capital_100to200 => [
//                        'cname' => $this->reg_capital_100to200_des,
//                        'detail' => '500万以上，1000万以下',
//                        'min' => 5000000,
//                        'max' => 10000000,
//                    ],
//                    $this->reg_capital_200to500 => [
//                        'cname' => $this->reg_capital_200to500_des,
//                        'detail' => '1000万以上，3000万以下',
//                        'min' => 10000000,
//                        'max' => 30000000,
//                    ],
//                    $this->reg_capital_500to1000 => [
//                        'cname' => $this->reg_capital_500to1000_des,
//                        'detail' => '3000万以上，5000万以下',
//                        'min' => 30000000,
//                        'max' => 50000000,
//                    ],
//                    $this->reg_capital_1000to10000 => [
//                        'cname' => $this->reg_capital_1000to10000_des,
//                        'detail' => '5000万以上，8000万以下',
//                        'min' => 50000000,
//                        'max' => 80000000,
//                    ],
//                    //    $this->reg_capital_10000to100000  =>  $this->reg_capital_10000to100000_des,
//                    $this->reg_capital_minddle_a => [
//                        'cname' => $this->reg_capital_minddle_a_des,
//                        'detail' => '8000万以上，1亿以下',
//                        'min' => 80000000,
//                        'max' => 100000000,
//                    ],
//                    $this->reg_capital_big_c => [
//                        'cname' => $this->reg_capital_big_c_des,
//                        'detail' => '1亿以上，5亿以下',
//                        'min' => 100000000,
//                        'max' => 500000000,
//                    ],
//                    $this->reg_capital_big_b => [
//                        'cname' => $this->reg_capital_big_b_des,
//                        'detail' => '5亿以上，10亿以下',
//                        'min' => 500000000,
//                        'max' => 1000000000,
//                    ],
//                    $this->reg_capital_big_A => [
//                        'cname' => $this->reg_capital_big_A_des,
//                        'detail' => '10亿以上，50亿以下',
//                        'min' => 1000000000,
//                        'max' => 5000000000,
//                    ],
//                    $this->reg_capital_super_big_C => [
//                        'cname' => $this->reg_capital_super_big_C_des,
//                        'detail' => '50亿以上，100亿以下',
//                        'min' => 5000000000,
//                        'max' => 10000000000,
//                    ],
//                    $this->reg_capital_super_big_B => [
//                        'cname' => $this->reg_capital_super_big_B_des,
//                        'detail' => '100亿以上，500亿以下',
//                        'min' => 10000000000,
//                        'max' => 50000000000,
//                    ],
//                    $this->reg_capital_super_big_A => [
//                        'cname' => $this->reg_capital_super_big_A_des,
//                        'detail' => '500亿以上',
//                        'min' => 50000000000,
//                        'max' => 500000000000,
//                    ],
//                ],
//            ],
            [
                'pid' => 50,
                'desc' => '营收规模2021',
                'key' => 'ying_shou_gui_mo_2021',
                'detail' => '',
                'type' => 'select',
                'data' => [
                    'YF' => [
                        'cname' => 'YF',
                        'detail' => '负值',
                    ],
                    'Y0' => [
                        'cname' => 'Y0',
                        'detail' => '0值',
                    ],
                    'Y1' => [
                        'cname' => 'Y1',
                        'detail' => '万',
                    ],
                    'Y2' => [
                        'cname' => 'Y2',
                        'detail' => '十万',
                    ],
                    'Y3' => [
                        'cname' => 'Y3',
                        'detail' => 'Y3',
                    ],
                    'Y4' => [
                        'cname' => 'Y4',
                        'detail' => 'Y4',
                    ],
                    'Y5' => [
                        'cname' => 'Y5',
                        'detail' => 'Y5',
                    ],
                    'Y6' => [
                        'cname' => 'Y6',
                        'detail' => 'Y6',
                    ],
                    'Y7' => [
                        'cname' => 'Y7',
                        'detail' => 'Y7',
                    ],
                    'Y8' => [
                        'cname' => 'Y8',
                        'detail' => 'Y8',
                    ],
                    'Y9' => [
                        'cname' => 'Y9',
                        'detail' => 'Y9',
                    ],
                    'Y10' => [
                        'cname' => 'Y10',
                        'detail' => 'Y10',
                    ],
                    'Y11' => [
                        'cname' => 'Y11',
                        'detail' => 'Y11',
                    ],
                    'Y12' => [
                        'cname' => 'Y12',
                        'detail' => 'Y12',
                    ],
                    'Y13' => [
                        'cname' => 'Y13',
                        'detail' => 'Y13',
                    ],
                    'Y14' => [
                        'cname' => 'Y14',
                        'detail' => 'Y14',
                    ],
                    'Y15' => [
                        'cname' => 'Y15',
                        'detail' => 'Y15',
                    ],
                    'Y16' => [
                        'cname' => 'Y16',
                        'detail' => 'Y16',
                    ],
                    'Y17' => [
                        'cname' => 'Y17',
                        'detail' => 'Y17',
                    ],
                    'Y18' => [
                        'cname' => 'Y18',
                        'detail' => 'Y18',
                    ],
                    'Y19' => [
                        'cname' => 'Y19',
                        'detail' => 'Y19',
                    ],
                    'Y20' => [
                        'cname' => 'Y20',
                        'detail' => 'Y20',
                    ],
                    'Y21' => [
                        'cname' => 'Y21',
                        'detail' => 'Y21',
                    ],
                    'Y22' => [
                        'cname' => 'Y22',
                        'detail' => 'Y22',
                    ],
                    'Y23' => [
                        'cname' => 'Y23',
                        'detail' => 'Y23',
                    ],
                    'Y24' => [
                        'cname' => 'Y24',
                        'detail' => 'Y24',
                    ],
                    'Y25' => [
                        'cname' => 'Y25',
                        'detail' => 'Y25',
                    ],
                    'Y26' => [
                        'cname' => 'Y26',
                        'detail' => 'Y26',
                    ],
                    'Y27' => [
                        'cname' => 'Y27',
                        'detail' => 'Y27',
                    ],
                    'Y28' => [
                        'cname' => 'Y28',
                        'detail' => 'Y28',
                    ],
                    'Y29' => [
                        'cname' => 'Y29',
                        'detail' => 'Y29',
                    ],
                    'Y30' => [
                        'cname' => 'Y30',
                        'detail' => 'Y30',
                    ],
                    'Y31' => [
                        'cname' => 'Y31',
                        'detail' => 'Y31',
                    ],
                    'Y32' => [
                        'cname' => 'Y32',
                        'detail' => 'Y32',
                    ],
                    'Y33' => [
                        'cname' => 'Y33',
                        'detail' => 'Y33',
                    ],
                    'Y34' => [
                        'cname' => 'Y34',
                        'detail' => 'Y34',
                    ],
                    'Y35' => [
                        'cname' => 'Y35',
                        'detail' => 'Y35',
                    ],
                    'Y36' => [
                        'cname' => 'Y36',
                        'detail' => 'Y36',
                    ],
                    'Y37' => [
                        'cname' => 'Y37',
                        'detail' => 'Y37',
                    ],
                    'Y38' => [
                        'cname' => 'Y38',
                        'detail' => 'Y38',
                    ],
                    'Y39' => [
                        'cname' => 'Y39',
                        'detail' => 'Y39',
                    ],
                    'Y40' => [
                        'cname' => 'Y40',
                        'detail' => 'Y40',
                    ],
                    'Y41' => [
                        'cname' => 'Y41',
                        'detail' => 'Y41',
                    ],
                    'Y42' => [
                        'cname' => 'Y42',
                        'detail' => 'Y42',
                    ],
                    'Y43' => [
                        'cname' => 'Y43',
                        'detail' => 'Y43',
                    ],
                    'Y44' => [
                        'cname' => 'Y44',
                        'detail' => 'Y44',
                    ],
                    'Y45' => [
                        'cname' => 'Y45',
                        'detail' => 'Y45',
                    ],
                    'Y46' => [
                        'cname' => 'Y46',
                        'detail' => 'Y46',
                    ],
                ],
            ],
            [
                'pid' => 60,
                'desc' => '企业规模',
                'detail' => '',
                'key' => 'tuan_dui_ren_shu',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '10人以下',
                        'detail' => '',
                        'min' => 0,
                        'max' => 10,
                    ],
                    20 => [
                        'cname' => '10-50人',
                        'detail' => '',
                        'min' => 10,
                        'max' => 50,
                    ],
                    30 => [
                        'cname' => '50-100人',
                        'detail' => '',
                        'min' => 50,
                        'max' => 100,
                    ],
                    40 => [
                        'cname' => '100-500人',
                        'detail' => '',
                        'min' => 100,
                        'max' => 500,
                    ],
                    50 => [
                        'cname' => '500-1000人',
                        'detail' => '',
                        'min' => 500,
                        'max' => 1000,
                    ],
                    60 => [
                        'cname' => '1000-5000人',
                        'detail' => '',
                        'min' => 1000,
                        'max' => 5000,
                    ],
                    70 => [
                        'cname' => '5000人以上',
                        'detail' => '',
                        'min' => 5000,
                        'max' => 500000,
                    ],
                ],
            ],
            [
                'pid' => 65,
                'desc' => '纳税规模2021',
                'detail' => '',
                'key' => 'na_shui_gui_mo_2021',
                'type' => 'select',
                'data' => [
                    'NF' => [
                        'cname' => 'NF',
                        'detail' => '负值',
                    ],
                    'N0' => [
                        'cname' => 'N0',
                        'detail' => '0值',
                    ],
                    'N1' => [
                        'cname' => 'N1',
                        'detail' => '万',
                    ],
                    'N2' => [
                        'cname' => 'N2',
                        'detail' => '十万',
                    ],
                    'N3' => [
                        'cname' => 'N3',
                        'detail' => 'N3',
                    ],
                    'N4' => [
                        'cname' => 'N4',
                        'detail' => 'N4',
                    ],
                    'N5' => [
                        'cname' => 'N5',
                        'detail' => 'N5',
                    ],
                    'N6' => [
                        'cname' => 'N6',
                        'detail' => 'N6',
                    ],
                    'N7' => [
                        'cname' => 'N7',
                        'detail' => 'N7',
                    ],
                    'N8' => [
                        'cname' => 'N8',
                        'detail' => 'N8',
                    ],
                    'N9' => [
                        'cname' => 'N9',
                        'detail' => 'N9',
                    ],
                    'N10' => [
                        'cname' => 'N10',
                        'detail' => 'N10',
                    ],
                    'N11' => [
                        'cname' => 'N11',
                        'detail' => 'N11',
                    ],
                    'N12' => [
                        'cname' => 'N12',
                        'detail' => 'N12',
                    ],
                    'N13' => [
                        'cname' => 'N13',
                        'detail' => 'N13',
                    ],
                    'N14' => [
                        'cname' => 'N14',
                        'detail' => 'N14',
                    ],
                    'N15' => [
                        'cname' => 'N15',
                        'detail' => 'N15',
                    ],
                    'N16' => [
                        'cname' => 'N16',
                        'detail' => 'N16',
                    ],
                    'N17' => [
                        'cname' => 'N17',
                        'detail' => 'N17',
                    ],
                    'N18' => [
                        'cname' => 'N18',
                        'detail' => 'N18',
                    ],
                    'N19' => [
                        'cname' => 'N19',
                        'detail' => 'N19',
                    ],
                    'N20' => [
                        'cname' => 'N20',
                        'detail' => 'N20',
                    ],
                    'N21' => [
                        'cname' => 'N21',
                        'detail' => 'N21',
                    ],
                    'N22' => [
                        'cname' => 'N22',
                        'detail' => 'N22',
                    ],
                    'N23' => [
                        'cname' => 'N23',
                        'detail' => 'N23',
                    ],
                    'N24' => [
                        'cname' => 'N24',
                        'detail' => 'N24',
                    ],
                    'N25' => [
                        'cname' => 'N25',
                        'detail' => 'N25',
                    ],
                    'N26' => [
                        'cname' => 'N26',
                        'detail' => 'N26',
                    ],
                    'N27' => [
                        'cname' => 'N27',
                        'detail' => 'N27',
                    ],
                    'N28' => [
                        'cname' => 'N28',
                        'detail' => 'N28',
                    ],
                    'N29' => [
                        'cname' => 'N29',
                        'detail' => 'N29',
                    ],
                    'N30' => [
                        'cname' => 'N30',
                        'detail' => 'N30',
                    ],
                    'N31' => [
                        'cname' => 'N31',
                        'detail' => 'N31',
                    ],
                    'N32' => [
                        'cname' => 'N32',
                        'detail' => 'N32',
                    ],
                    'N33' => [
                        'cname' => 'N33',
                        'detail' => 'N33',
                    ],
                    'N34' => [
                        'cname' => 'N34',
                        'detail' => 'N34',
                    ],
                    'N35' => [
                        'cname' => 'N35',
                        'detail' => 'N35',
                    ],
                    'N36' => [
                        'cname' => 'N36',
                        'detail' => 'N36',
                    ],
                    'N37' => [
                        'cname' => 'N37',
                        'detail' => 'N37',
                    ],
                    'N38' => [
                        'cname' => 'N38',
                        'detail' => 'N38',
                    ],
                    'N39' => [
                        'cname' => 'N39',
                        'detail' => 'N39',
                    ],
                    'N40' => [
                        'cname' => 'N40',
                        'detail' => 'N40',
                    ],
                    'N41' => [
                        'cname' => 'N41',
                        'detail' => 'N41',
                    ],
                    'N42' => [
                        'cname' => 'N42',
                        'detail' => 'N42',
                    ],
                    'N43' => [
                        'cname' => 'N43',
                        'detail' => 'N43',
                    ],
                    'N44' => [
                        'cname' => 'N44',
                        'detail' => 'N44',
                    ],
                    'N45' => [
                        'cname' => 'N45',
                        'detail' => 'N45',
                    ],
                    'N46' => [
                        'cname' => 'N46',
                        'detail' => 'N46',
                    ],

                ],
            ],
            [
                'pid' => 66,
                'desc' => '利润规模2021',
                'detail' => '',
                'key' => 'li_run_gui_mo_2021',
                'type' => 'select',
                'data' => [
                    'F28' => [
                        'cname' => 'F28',
                        'detail' => 'F28',
                    ],
                    'F27' => [
                        'cname' => 'F27',
                        'detail' => 'F27',
                    ],
                    'F26' => [
                        'cname' => 'F26',
                        'detail' => 'F26',
                    ],
                    'F25' => [
                        'cname' => 'F25',
                        'detail' => 'F25',
                    ],
                    'F24' => [
                        'cname' => 'F24',
                        'detail' => 'F24',
                    ],
                    'F23' => [
                        'cname' => 'F23',
                        'detail' => 'F23',
                    ],
                    'F22' => [
                        'cname' => 'F22',
                        'detail' => 'F22',
                    ],
                    'F21' => [
                        'cname' => 'F21',
                        'detail' => 'F21',
                    ],
                    'F20' => [
                        'cname' => 'F20',
                        'detail' => 'F20',
                    ],
                    'F19' => [
                        'cname' => 'F19',
                        'detail' => 'F19',
                    ],
                    'F18' => [
                        'cname' => 'F18',
                        'detail' => 'F18',
                    ],
                    'F17' => [
                        'cname' => 'F17',
                        'detail' => 'F17',
                    ],
                    'F16' => [
                        'cname' => 'F16',
                        'detail' => 'F16',
                    ],
                    'F15' => [
                        'cname' => 'F15',
                        'detail' => 'F15',
                    ],
                    'F14' => [
                        'cname' => 'F14',
                        'detail' => 'F14',
                    ],
                    'F13' => [
                        'cname' => 'F13',
                        'detail' => 'F13',
                    ],
                    'F12' => [
                        'cname' => 'F12',
                        'detail' => 'F12',
                    ],
                    'F11' => [
                        'cname' => 'F11',
                        'detail' => 'F11',
                    ],
                    'F10' => [
                        'cname' => 'F10',
                        'detail' => 'F10',
                    ],
                    'F9' => [
                        'cname' => 'F9',
                        'detail' => 'F9',
                    ],
                    'F8' => [
                        'cname' => 'F8',
                        'detail' => 'F8',
                    ],
                    'F7' => [
                        'cname' => 'F7',
                        'detail' => 'F7',
                    ],
                    'F6' => [
                        'cname' => 'F6',
                        'detail' => 'F6',
                    ],
                    'F5' => [
                        'cname' => 'F5',
                        'detail' => 'F5',
                    ],
                    'F4' => [
                        'cname' => 'F4',
                        'detail' => 'F4',
                    ],
                    'F3' => [
                        'cname' => 'F3',
                        'detail' => 'F3',
                    ],
                    'F2' => [
                        'cname' => 'F2',
                        'detail' => 'F2',
                    ],
                    'F1' => [
                        'cname' => 'F1',
                        'detail' => 'F1',
                    ],
                    'L0' => [
                        'cname' => 'L0',
                        'detail' => '0值',
                    ],
                    'Z1' => [
                        'cname' => 'Z1',
                        'detail' => '万',
                    ],
                    'Z2' => [
                        'cname' => 'Z2',
                        'detail' => '十万',
                    ],
                    'Z3' => [
                        'cname' => 'Z3',
                        'detail' => 'Z3',
                    ],
                    'Z4' => [
                        'cname' => 'Z4',
                        'detail' => 'Z4',
                    ],
                    'Z5' => [
                        'cname' => 'Z5',
                        'detail' => 'Z5',
                    ],
                    'Z6' => [
                        'cname' => 'Z6',
                        'detail' => 'Z6',
                    ],
                    'Z7' => [
                        'cname' => 'Z7',
                        'detail' => 'Z7',
                    ],
                    'Z8' => [
                        'cname' => 'Z8',
                        'detail' => 'Z8',
                    ],
                    'Z9' => [
                        'cname' => 'Z9',
                        'detail' => 'Z9',
                    ],
                    'Z10' => [
                        'cname' => 'Z10',
                        'detail' => 'Z10',
                    ],
                    'Z11' => [
                        'cname' => 'Z11',
                        'detail' => 'Z11',
                    ],
                    'Z12' => [
                        'cname' => 'Z12',
                        'detail' => 'Z12',
                    ],
                    'Z13' => [
                        'cname' => 'Z13',
                        'detail' => 'Z13',
                    ],
                    'Z14' => [
                        'cname' => 'Z14',
                        'detail' => 'Z14',
                    ],
                    'Z15' => [
                        'cname' => 'Z15',
                        'detail' => 'Z15',
                    ],
                    'Z16' => [
                        'cname' => 'Z16',
                        'detail' => 'Z16',
                    ],
                    'Z17' => [
                        'cname' => 'Z17',
                        'detail' => 'Z17',
                    ],
                    'Z18' => [
                        'cname' => 'Z18',
                        'detail' => 'Z18',
                    ],
                    'Z19' => [
                        'cname' => 'Z19',
                        'detail' => 'Z19',
                    ],
                    'Z20' => [
                        'cname' => 'Z20',
                        'detail' => 'Z20',
                    ],
                    'Z21' => [
                        'cname' => 'Z21',
                        'detail' => 'Z21',
                    ],
                    'Z22' => [
                        'cname' => 'Z22',
                        'detail' => 'Z22',
                    ],
                    'Z23' => [
                        'cname' => 'Z23',
                        'detail' => 'Z23',
                    ],
                    'Z24' => [
                        'cname' => 'Z24',
                        'detail' => 'Z24',
                    ],
                    'Z25' => [
                        'cname' => 'Z25',
                        'detail' => 'Z25',
                    ],
                    'Z26' => [
                        'cname' => 'Z26',
                        'detail' => 'Z26',
                    ],
                    'Z27' => [
                        'cname' => 'Z27',
                        'detail' => 'Z27',
                    ],
                    'Z28' => [
                        'cname' => 'Z28',
                        'detail' => 'Z28',
                    ],
                    'Z29' => [
                        'cname' => 'Z29',
                        'detail' => 'Z29',
                    ],
                    'Z30' => [
                        'cname' => 'Z30',
                        'detail' => 'Z30',
                    ],
                    'Z31' => [
                        'cname' => 'Z31',
                        'detail' => 'Z31',
                    ],
                    'Z32' => [
                        'cname' => 'Z32',
                        'detail' => 'Z32',
                    ],
                    'Z33' => [
                        'cname' => 'Z33',
                        'detail' => 'Z33',
                    ],
                    'Z34' => [
                        'cname' => 'Z34',
                        'detail' => 'Z34',
                    ],
                    'Z35' => [
                        'cname' => 'Z35',
                        'detail' => 'Z35',
                    ],
                    'Z36' => [
                        'cname' => 'Z36',
                        'detail' => 'Z36',
                    ],
                    'Z37' => [
                        'cname' => 'Z37',
                        'detail' => 'Z37',
                    ],
                    'Z38' => [
                        'cname' => 'Z38',
                        'detail' => 'Z38',
                    ],
                    'Z39' => [
                        'cname' => 'Z39',
                        'detail' => 'Z39',
                    ],
                    'Z40' => [
                        'cname' => 'Z40',
                        'detail' => 'Z40',
                    ],
                    'Z41' => [
                        'cname' => 'Z41',
                        'detail' => 'Z41',
                    ],
                    'Z42' => [
                        'cname' => 'Z42',
                        'detail' => 'Z42',
                    ],
                    'Z43' => [
                        'cname' => 'Z43',
                        'detail' => 'Z43',
                    ],
                    'Z44' => [
                        'cname' => 'Z44',
                        'detail' => 'Z44',
                    ],
                    'Z45' => [
                        'cname' => 'Z45',
                        'detail' => 'Z45',
                    ],
                    'Z46' => [
                        'cname' => 'Z46',
                        'detail' => 'Z46',
                    ],
                ],
            ],
            [
                'pid' => 70,
                'desc' => '有无官网',
                'detail' => '',
                'key' => 'web',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '有',
                        'detail' => '',
                    ],
                ],
            ],
            [
                'pid' => 80,
                'desc' => '有无APP',
                'detail' => '',
                'key' => 'app',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '有',
                        'detail' => '',
                    ],
                ],
            ],
            [
                'pid' => 100,
                'desc' => '营收规模降序',
                'detail' => '',
                'key' => 'li_run_gui_mo_2021',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '是',
                        'detail' => '',
                    ],
                ],
            ],
            //
            [
                'pid' => 110,
                'desc' => '纳税规模降序',
                'detail' => '',
                'key' => 'na_shui_gui_mo_2021',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '是',
                        'detail' => '',
                    ],
                ],
            ],
            //
            [
                'pid' => 120,
                'desc' => '营收规模降序',
                'detail' => '导出时',
                'key' => 'ying_shou_gui_mo_2021',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '是',
                        'detail' => '',
                    ],
                ],
            ],
            [
                'pid' => 130,
                'desc' => '导出时不要分公司',
                'detail' => '导出时',
                'key' => 'ENTNAME',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '是',
                        'detail' => '',
                    ],
                ],
            ],
            [
                'pid' => 140,
                'desc' => '导出时不要子公司',
                'detail' => '导出时',
                'key' => 'ENTNAME',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '是',
                        'detail' => '',
                    ],
                ],
            ],

//            [
//                'pid' => 90,
//                'desc' => '是否物流',
//                'detail' => '',
//                'key' => 'wu_liu_xin_xi',
//                'type' => 'select',
//                'data' => [
//                    10 => [
//                        'cname' => '是',
//                        'detail' => '',
//                    ],
//                ],
//            ],
//            [
//                'pid' => 100,
//                'desc' => '营收剔除负值',
//                'detail' => '',
//                'key' => 'ying_shou_gui_mo',
//                'type' => 'select',
//                'data' => [
//                    10 => [
//                        'cname' => '是',
//                        'detail' => '',
//                    ],
//                ],
//            ],
        ];
    }


    //高级搜索
    function advancedSearch($elasticSearchService, $index = 'company_202207')
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
        $bean->setPreference("_primary");
        $bean->setType('_doc');
        $bean->setBody($elasticSearchService->query);
        $response = $elasticsearch->client()->search($bean)->getBody();
//        CommonService::getInstance()->log4PHP(json_encode(['es_query'=>$elasticSearchService->query]));
        return $response;
    }

    function saveSearchHistory($userId, $postDataStr, $canme = '')
    {
        return UserSearchHistory::create()->data([
            'userId' => $userId,
            'post_data' => $postDataStr,
            'query' => $canme,
        ])->save();
    }

    static function formatEsDate($dataArr, $fieldsArr)
    {
        foreach ($dataArr as &$dataItem) {
            foreach ($fieldsArr as $field) {
                if ($dataItem['_source'][$field] == '0000-00-00 00:00:00') {
                    $dataItem['_source'][$field] = '--';
                    continue;
                }
                $tmpArr = explode(' ', $dataItem['_source'][$field]);
                $dataItem['_source'][$field] = $tmpArr[0];

            }
        }

        return $dataArr;
    }

    static function formatObjDate($dataObj, $fieldsArr)
    {
        foreach ($fieldsArr as $field) {
            if ($dataObj->$field == '0000-00-00 00:00:00') {
                $dataObj->$field = '--';
                continue;
            }
            $tmpArr = explode(' ', $dataObj->$field);
            $dataObj->$field = $tmpArr[0];
        }

        return $dataObj;
    }

    static function formatObjMoney($dataObj, $fieldsArr)
    {
        foreach ($fieldsArr as $field) {
            if ($dataObj->$field > 0) {
                $dataObj->$field = self::replaceBetween(
                    $dataObj->$field,
                    '.',
                    '万',
                    ''
                );
                $dataObj->$field = str_replace(
                    '.',
                    '',
                    $dataObj->$field
                );
                // $dataItem['_source'][$field] = date('Y-m-d',strtotime($dataItem['_source'][$field])) ;
            }
        }

        return $dataObj;
    }

    static function formatEsMoney($dataArr, $fieldsArr)
    {
        foreach ($dataArr as &$dataItem) {
            foreach ($fieldsArr as $field) {
                if ($dataItem['_source'][$field] <= 0) {
                    continue;
                }

                // 不包含
                if (strpos($dataItem['_source'][$field], '.') === false) {
                    continue;
                }
                $dataItem['_source'][$field] = self::replaceBetween(
                    $dataItem['_source'][$field],
                    '.',
                    '万',
                    ''
                );
                $dataItem['_source'][$field] = str_replace(
                    '.',
                    '',
                    $dataItem['_source'][$field]
                );
            }
        }

        return $dataArr;
    }

    static function replaceBetween($str, $needle_start, $needle_end, $replacement)
    {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);

        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;

        return substr_replace($str, $replacement, $start, $end - $start);
    }

    static function mapYingShouGuiMo(): array
    {

        return [
            'A1' => '微型，一般指规模在100万以下',
            'A2' => '小型C类，一般指规模在100万以上，500万以下',
            'A3' => '小型B类，一般指规模在500万以上，1000万以下',
            'A4' => '小型A类，一般指规模在1000万以上，3000万以下',
            'A5' => '中型C类，一般指规模在3000万以上，5000万以下',
            'A6' => '中型B类，一般指规模在5000万以上，8000万以下',
            'A7' => '中型A类，一般指规模在8000万以上，1亿以下',
            'A8' => '大型C类，一般指规模在1亿以上，5亿以下',
            'A9' => '大型B类，一般指规模在5亿以上，10亿以下',
            'A10' => '大型A类，一般指规模在10亿以上，50亿以下',
            'A11' => '特大型C类，一般指规模在50亿以上，100亿以下',
            'A12' => '特大型B类，一般指规模在100亿以上，500亿以下',
            'A13' => '特大型A类，一般指规模在500亿以上',
        ];
    }

    static function getYingShouGuiMoTag(string $yingShouGuiMo): string
    {

        $str = self::mapYingShouGuiMo()[$yingShouGuiMo];
        $strArr = explode('，', $str);
        return $strArr[0];
    }

    static function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    static function getAllTagesByData($dataItem)
    {
        // 标签
        $tags = [];

        // 营收规模  
        if ($dataItem['ying_shou_gui_mo']) {
            $yingShouGuiMoTag = (new XinDongService())::getYingShouGuiMoTag(
                $dataItem['ying_shou_gui_mo']
            );
            $yingShouGuiMoTag && $tags[50] = $yingShouGuiMoTag;
        }

        // 团队规模
        if ($dataItem['tuan_dui_ren_shu']) {
            $tuanDuiGuiMoTag = self::getTuanDuiGuiMoTag(
                $dataItem['tuan_dui_ren_shu']
            );
            $tuanDuiGuiMoTag && $tags[60] = $tuanDuiGuiMoTag;
        }

        // 是否有ISO
        $dataItem['iso'] && $tags[80] = 'ISO';
        // 是否瞪羚 
        $dataItem['deng_ling_qi_ye'] && $tags[85] = '瞪羚';
        // 高新技术 
        $dataItem['gao_xin_ji_shu'] && $tags[90] = '高新技术';

        // 上市公司
        $dataItem['shang_shi_xin_xi'] && $tags[95] = '高新技术';

        //进出口企业 
        $dataItem['jin_chu_kou'] && $tags[100] = '进出口型企业';

        //商品 
        $dataItem['shang_pin_data'] && $tags[110] = '商品';

        //物流企业 
        $dataItem['wu_liu_xin_xi'] && $tags[120] = '物流企业';

        //市占率tag
        //$marketShare = XinDongService::getMarjetShare($dataItem['xd_id']);
        if ($dataItem['market_share']) {
            if (
                $dataItem['market_share']['ent_market_share']['bottom'] ||
                $dataItem['market_share']['ent_market_share']['top']
            ) {
                $tags[130] = $dataItem['market_share']['ent_market_share']['bottom'] . '~' .
                    $dataItem['market_share']['ent_market_share']['top'];
            }
        }

        return $tags;
    }

    static function getShangPinTag($companyName): string
    {
        if (
            self::checkIfIsShangPinCompany($companyName)
        ) {
            return '商品';
        }

        return "";
    }

    static function checkIfIsShangPinCompany($companyName): bool
    {
        return \App\HttpController\Models\RDS3\ShangPinTiaoMaJieBa::create()
            ->where('entname', $companyName)
            ->get() ? true : false;
    }

    static function getJinChuKouTag($companyId): string
    {
        if (
            self::checkIfJinChuKou($companyId)
        ) {
            return '进出口型企业';
        }

        return "";
    }

    static function checkIfJinChuKou($companyId): bool
    {
        return \App\HttpController\Models\RDS3\HgGoods::create()
            ->where('xd_id', $companyId)
            ->get() ? true : false;
    }

    static function getShangShiTag($companyId): string
    {
        if (
            self::checkIfShangeShi($companyId)
        ) {
            return '上市公司';
        }

        return "";
    }

    static function checkIfShangeShi($companyId): bool
    {
        return \App\HttpController\Models\RDS3\XdAggreListedFinance::create()
            ->where('xd_id', $companyId)
            ->get() ? true : false;
    }

    static function getIsoTag($companyId): string
    {
        if (
            self::checkIfHasIso($companyId)
        ) {
            return 'ISO';
        }

        return "";
    }

    static function getHighTecTag($companyId): string
    {
        if (
            self::checkIfIsHighTec($companyId)
        ) {
            return '高新技术';
        }

        return "";
    }

    static function getDengLingTag($companyId): string
    {
        if (
            self::checkIfHasDengLing($companyId)
        ) {
            return '瞪羚';
        }

        return "";
    }

    static function checkIfIsHighTec($companyId): bool
    {
        return \App\HttpController\Models\RDS3\XdHighTec::create()
            ->where('xd_id', $companyId)
            ->where('dateto', date('Y-m-d'), '>')
            ->get() ? true : false;
    }

    static function checkIfHasIso($companyId): bool
    {
        return \App\HttpController\Models\RDS3\XdDlRzGlTx::create()
            ->where('xd_id', $companyId)->get() ? true : false;
    }

    static function checkIfHasDengLing($companyId): bool
    {
        return \App\HttpController\Models\RDS3\XdDl::create()
            ->where('xd_id', $companyId)->get() ? true : false;
    }

    static function getTuanDuiGuiMoTag($nums)
    {
        $map = self::getTuanDuiGuiMoMap();
        foreach ($map as $item) {
            if (
                $item['min'] <= $nums &&
                $item['max'] >= $nums
            ) {
                return $item['des'];
            }
        }
    }

    static function getTuanDuiGuiMoMap()
    {
        return [
            10 => ['min' => 0, 'max' => 10, 'epreg' => ['[0-9]'], 'des' => '10人以下'],//,
            20 => ['min' => 10, 'max' => 50, 'epreg' => ['[1-4][0-9]'], 'des' => '10-50人'], //,
            30 => ['min' => 50, 'max' => 100, 'epreg' => ['[5-9][0-9]'], 'des' => '50-100人'], //,
            40 => ['min' => 100, 'max' => 500, 'epreg' => ['[1-4][0-9][0-9]'], 'des' => '100-500人'], //,
            50 => ['min' => 500, 'max' => 1000, 'epreg' => ['[5-9][0-9][0-9]'], 'des' => '500-1000人'], //,
            60 => ['min' => 1000, 'max' => 5000, 'epreg' => ['[1-4][0-9][0-9][0-9]'], 'des' => '1000-5000人'], //,
            70 => ['min' => 5000, 'max' => 10000000, 'epreg' =>
                ['[5-9][0-9][0-9][0-9]', '[1-9][0-9][0-9][0-9][0-9]', '[1-9][0-9][0-9][0-9][0-9][0-9]'],
                'des' => '5000人以上']//,
        ];
    }

    static function getYingShouGuiMoMapV2()
    {
        return [
            'A1' => ['min' => 0, 'max' => 49],
            'A2' => ['min' => 50, 'max' => 99],
            'A3' => ['min' => 100, 'max' => 299],
            'A4' => ['min' => 299, 'max' => 500],
            'A5' => ['min' => 500, 'max' => 999],
            'A6' => ['min' => 1000, 'max' => 1999],
            'A7' => ['min' => 2000, 'max' => 2999],
            'A8' => ['min' => 3000, 'max' => 3999],
            'A9' => ['min' => 4000, 'max' => 4999],
            'A10' => ['min' => 5000, 'max' => 5999],
            'A11' => ['min' => 6000, 'max' => 6999],
            'A12' => ['min' => 7000, 'max' => 7999],
            'A13' => ['min' => 8000, 'max' => 8999],
            'A14' => ['min' => 9000, 'max' => 9999],
            'A15' => ['min' => 10000, 'max' => 19999],
            'A16' => ['min' => 20000, 'max' => 29999],
            'A17' => ['min' => 30000, 'max' => 39999],
            'A18' => ['min' => 40000, 'max' => 49999],
            'A19' => ['min' => 50000, 'max' => 59999],
            'A20' => ['min' => 60000, 'max' => 69999],
            'A21' => ['min' => 70000, 'max' => 79999],
            'A22' => ['min' => 80000, 'max' => 89999],
            'A23' => ['min' => 90000, 'max' => 99999],
            'A24' => ['min' => 100000, 'max' => 199999],
            'A25' => ['min' => 200000, 'max' => 299999],
            'A26' => ['min' => 300000, 'max' => 399999],
            'A27' => ['min' => 400000, 'max' => 499999],
            'A28' => ['min' => 500000, 'max' => 599999],
            'A29' => ['min' => 600000, 'max' => 699999],
            'A30' => ['min' => 700000, 'max' => 799999],
            'A31' => ['min' => 800000, 'max' => 899999],
            'A32' => ['min' => 900000, 'max' => 999999],
            'A33' => ['min' => 1000000, 'max' => 1999999],
            'A34' => ['min' => 2000000, 'max' => 2999999],
            'A35' => ['min' => 3000000, 'max' => 3999999],
            'A36' => ['min' => 4000000, 'max' => 4999999],
            'A37' => ['min' => 5000000, 'max' => 5999999],
            'A38' => ['min' => 6000000, 'max' => 6999999],
            'A39' => ['min' => 7000000, 'max' => 7999999],
            'A40' => ['min' => 8000000, 'max' => 8999999],
            'A41' => ['min' => 9000000, 'max' => 9999999],
            'A42' => ['min' => 10000000, 'max' => 99999999],
        ];
    }

    static function getYingShouGuiMoMapV3()
    {
        return [
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
    }

    static function getZhuCeZiBenMap()
    {
        return [
            10 => ['min' => 0, 'max' => 100, 'epreg' => ['[0-9]', '[1-9][0-9]'], 'des' => '100万以下'],//,
            15 => ['min' => 10, 'max' => 500, 'epreg' => ['[1-4][0-9][0-9]'], 'des' => '100-500'], //,
            20 => ['min' => 500, 'max' => 1000, 'epreg' => ['[5-9][0-9][0-9]'], 'des' => '500-1000'], //,
            25 => ['min' => 1000, 'max' => 5000, 'epreg' => ['[1-4][0-9][0-9][0-9]'], 'des' => '1000-5000'], //,
            30 => ['min' => 5000, 'max' => 10000, 'epreg' => ['[5-9][0-9][0-9][0-9]'], 'des' => '5000-10000'], //,
            35 => ['min' => 10000, 'max' => 50000, 'epreg' => ['[1-9][0-9][0-9][0-9][0-9]'], 'des' => '100000-100000'], //,
            40 => ['min' => 5000, 'max' => 10000000, 'epreg' =>
                ['[1-9][0-9][0-9][0-9][0-9]', '[1-9][0-9][0-9][0-9][0-9][0-9]', '[1-9][0-9][0-9][0-9][0-9][0-9][0-9]'],
                'des' => '100000+']//,
        ];
    }

    static function getZhuCeZiBenMapV2()
    {
        return [
            10 => ['min' => 0, 'max' => 100, 'epreg' => ['[0-9]', '[1-9][0-9](\\.).+'], 'des' => '100万以下'],//,
            15 => ['min' => 10, 'max' => 500, 'epreg' => ['[1-4][0-9][0-9](\\.).+'], 'des' => '100-500'], //,
            20 => ['min' => 500, 'max' => 1000, 'epreg' => ['[5-9][0-9][0-9](\\.).+'], 'des' => '500-1000'], //,
            25 => ['min' => 1000, 'max' => 5000, 'epreg' => ['[1-4][0-9][0-9][0-9](\\.).+'], 'des' => '1000-5000'], //,
            30 => ['min' => 5000, 'max' => 10000, 'epreg' => ['[5-9][0-9][0-9][0-9](\\.).+'], 'des' => '5000-10000'], //,
            35 => ['min' => 10000, 'max' => 50000, 'epreg' => ['[1-9][0-9][0-9][0-9][0-9](\\.).+'], 'des' => '100000-100000'], //,
            40 => ['min' => 5000, 'max' => 10000000, 'epreg' =>
                ['[1-9][0-9][0-9][0-9][0-9]', '[1-9][0-9][0-9][0-9][0-9][0-9]', '[1-9][0-9][0-9][0-9][0-9][0-9][0-9](\\.).+'],
                'des' => '100000+']//,
        ];
    }


    // 获取所有曾用名称 $getAll: 为true的时候  当前的名字也要了
    static function getAllUsedNames($dataArr, $getAll = false)
    {
        if ($getAll) {
            $allNames = [$dataArr['name'] => $dataArr['name']];
        } else {
            $allNames = [];
        }
        $newNames = self::autoSearchNewNames($dataArr);
        $oldNames = self::autoSearchOldNames($dataArr);
        return array_values(array_merge($allNames, $newNames, $oldNames));
    }

    //往后找到最新的names
    static function autoSearchNewNames($dataArr)
    {
        $names = [];
        // 容错次数
        $nums = 1;
        while ($dataArr['property2'] > 0) {
            if ($nums >= 20) {
                break;
            }
            $retData = \App\HttpController\Models\RDS3\Company::create()
                ->field(['id', 'name', 'property2'])
                ->where('id', $dataArr['property2'])
                ->get();
            if ($retData) {
                $dataArr = [
                    'id' => $retData->id,
                    'name' => $retData->name,
                    'property2' => $retData->property2,
                ];
                $names[$dataArr['name']] = $dataArr['name'];
            } else {
                $dataArr = [
                    'id' => 0,
                    'name' => 0,
                    'property2' => 0,
                ];
            }
            $nums++;
        }

        return $names;
    }

    //往前找到旧的names
    static function autoSearchOldNames($dataArr)
    {
        $names = [];
        // 容错次数
        $nums = 1;
        while ($dataArr['id'] > 0) {
            if ($nums >= 20) {
                break;
            }
            $retData = \App\HttpController\Models\RDS3\Company::create()
                ->field(['id', 'name', 'property2'])
                ->where('property2', $dataArr['id'])
                ->get();
            if ($retData) {
                $dataArr = [
                    'id' => $retData->id,
                    'name' => $retData->name,
                    'property2' => $retData->property2,
                ];
                $names[$dataArr['name']] = $dataArr['name'];
            } else {
                $dataArr = [
                    'id' => 0,
                    'name' => 0,
                    'property2' => 0,
                ];
            }
            $nums++;
        }
        return $names;
    }

    static function saveOpportunity($dataItem)
    {
        if (
            UserBusinessOpportunity::create()->where([
                'userId' => $dataItem['userId'],
                'name' => $dataItem['name'],
            ])->get()
        ) {
            CommonService::getInstance()->log4PHP('该商机已经存在于客户池 ' . json_encode(
                    [
                        'userId' => $dataItem['userId'],
                        'name' => $dataItem['name'],
                    ]
                ));
            return true;
        }

        try {
            $res = UserBusinessOpportunity::create()->data([
                'userId' => $dataItem['userId'],
                'name' => $dataItem['name'],
                'code' => $dataItem['code'],
                'batchId' => $dataItem['batchId'],
                'source' => $dataItem['source'],
            ])->save();
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
        }
        return $res;
    }

    function getEntInfoByName($entNames): ?array
    {

        // CommonService::getInstance()->log4PHP(json_encode($entNames));
        $retData = Company::create()
            ->where('name', array_values($entNames), 'IN')
            ->field(["id", "name", "company_org_type", "reg_location", "estiblish_time"])
            ->get();

        return [
            'code' => 200,
            'paging' => [],
            'msg' => '成功',
            'result' => $retData,
        ];
    }


    function matchAainstEntName(
        $str,
        $mode = " IN NATURAL LANGUAGE MODE ",
        $companyName = "company_name_0",
        $field = "id,name",
        $limit = 1
    )
    {
        $sql = "SELECT
                    $field
                FROM
                    $companyName
                WHERE
                    MATCH(`name`) AGAINST(
                    '$str'  $mode
                    )  
                LIMIT $limit
        ";
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        CommonService::getInstance()->log4PHP('matchAainstComName sql' . $sql);
        CommonService::getInstance()->log4PHP('matchAainstComName res' . json_encode($list));

        return $list;

        // return [
        //     'sql' => $sql,
        //     'list' => $list,
        // ];
    }

    function splitChineseNameForMatchAgainst($entName): ?string
    {


        $arr = preg_split('/(?<!^)(?!$)/u', $entName);
        $matchStr = "";
        if ($arr[0] && $arr[1]) {
            $matchStr .= '+' . $arr[0] . $arr[1];
        }
        if ($arr[2] && $arr[3]) {
            $matchStr .= '+' . $arr[2] . $arr[3];
        }
        if ($arr[4] && $arr[5]) {
            $matchStr .= '+' . $arr[4] . $arr[5];
        }
        if ($arr[6] && $arr[7]) {
            $matchStr .= '+' . $arr[6] . $arr[7];
        }
        if ($arr[8] && $arr[9]) {
            $matchStr .= '+' . $arr[8] . $arr[9];
        }

        return $matchStr;
    }

    function matchEntByNameMatchByBooleanMode($csp, $entName)
    {
        foreach (
            CompanyName::getAllTables() as $tableName
        ) {
            $csp->add('BOOLEAN_MODE_' . $tableName, function () use ($entName, $tableName) {
                $timeStart2 = microtime(true);
                $matchStr = (new XinDongService())->splitChineseNameForMatchAgainst($entName);
                $retData = (new XinDongService())
                    ->matchAainstEntName(
                        $matchStr,
                        " IN BOOLEAN MODE ",
                        $tableName,
                        'id,name',
                        3
                    );
                $timeEnd2 = microtime(true);
                $execution_time11 = ($timeEnd2 - $timeStart2);
                return [
                    'data' => $retData,
                    'type' => 'Boolean',
                    'time' => $execution_time11
                ];
            });
        }
    }

    function matchEntByNameMatchByLanguageMode($csp, $entName)
    {
        foreach (
            CompanyName::getAllTables() as $tableName
        ) {
            $csp->add('NATURAL_LANGUAGE_MODE_' . $tableName, function () use ($entName, $tableName) {
                $timeStart2 = microtime(true);
                $retData = (new XinDongService())
                    ->matchAainstEntName(
                        $entName,
                        " IN NATURAL LANGUAGE MODE  ",
                        $tableName,
                        'id,name',
                        3
                    );
                $timeEnd2 = microtime(true);
                $execution_time11 = ($timeEnd2 - $timeStart2);
                return [
                    'data' => $retData,
                    'type' => 'Language',
                    'time' => $execution_time11
                ];
            });
        }
    }

    function matchEntByNameEqualMatchByName($csp, $entName)
    {
        $csp->add('company_match', function () use ($entName) {
            $timeStart2 = microtime(true);
            $sql = "SELECT  id,`name` FROM  `company`  WHERE   `name` = '$entName' LIMIT 1";
            $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
            $timeEnd2 = microtime(true);
            $execution_time11 = ($timeEnd2 - $timeStart2);
            return [
                'data' => $list,
                'type' => 'equal',
                'time' => $execution_time11
            ];
        });
    }

    function matchEntByNameMatchByEs($entName, $size = 4, $page = 1)
    {
        $ElasticSearchService = new ElasticSearchService();
        $ElasticSearchService->addMustMatchQuery('name', $entName);
        $offset = ($page - 1) * $size;
        $ElasticSearchService->addSize($size);
        $ElasticSearchService->addFrom($offset);
        // $ElasticSearchService->addSort('xd_id', 'desc') ;

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson, true);
        // CommonService::getInstance()->log4PHP('matchEntByNameMatchByEs '.
        //     $responseJson
        // ); 
        $datas = [];
        foreach ($responseArr['hits']['hits'] as $item) {
            $datas[] = [
                'id' => $item['_source']['xd_id'],
                'name' => $item['_source']['name'],
            ];
        }
        return $datas;
    }

    function getSimilarPercent($var_1, $var_2)
    {
        similar_text($var_1, $var_2, $percent);
        return number_format($percent);
    }


    function checkIfSimilar($name1, $name2)
    {
        $percent = $this->getSimilarPercent($name1, $name2);
        if ($percent >= 85) {
            return true;
        }
        return false;
    }


    // $matchType :1 boolean  2:lanague
    function matchEntByName($entName, $matchType = 1, $timeOut = 3.5): array
    {
        $timeStart = microtime(true);

        //先从es match   
        $esRes = $this->matchEntByNameMatchByEs($entName);
        CommonService::getInstance()->log4PHP('es match' .
            json_encode(
                [
                    'data' => $esRes,
                    'time' => (microtime(true) - $timeStart),
                ]
            )
        );
        // 如果es 就匹配到了 直接返回 
        foreach ($esRes as $data) {
            if ($this->checkIfSimilar($data['name'], $entName)) {
                CommonService::getInstance()->log4PHP('es match ok , return ' .
                    json_encode(
                        [
                            'data' => $matchedItem,
                            'time' => (microtime(true) - $timeStart),
                        ]
                    )
                );
                return $data;
            }
        }

        // es木有的 从 db找： 分词全文匹配+精确
        $csp = new \EasySwoole\Component\Csp();
        // 分词全文匹配找：Boolean mode 
        if ($matchType == 1) {
            $this->matchEntByNameMatchByBooleanMode($csp, $entName);
        }

        //分词全文匹配找： language mode 
        if ($matchType == 2) {
            $this->matchEntByNameMatchByLanguageMode($csp, $entName);
        }

        // 精确找 
        $this->matchEntByNameEqualMatchByName($csp, $entName);

        $dbres = ($csp->exec($timeOut));
        CommonService::getInstance()->log4PHP('从db找 res' .
            json_encode(
                [
                    'data' => $dbres,
                    'time' => (microtime(true) - $timeStart),
                ]
            )
        );
        // 从结果找
        $matchedDatas = [];
        // $matchedData = [];
        foreach ($dbres as $dataItem) {
            // 如果精确匹配到了 优先使用精确值
            if (
                $dataItem['type'] == 'equal' &&
                !empty($dataItem['data'])
            ) {
                CommonService::getInstance()->log4PHP('精确匹配到了' .
                    json_encode(
                        $dataItem['data'][0]
                    )
                );
                return $dataItem['data'][0];
            }
        }

        // 剩余的 按照相似度排序 然后返回相似度最高的
        foreach ($esRes as $dataItem) {
            $percent = $this->getSimilarPercent($dataItem['name'], $entName);
            $matchedDatas[$percent] = [
                'id' => $dataItem['id'],
                'name' => $dataItem['name'],
            ];
        }
        CommonService::getInstance()->log4PHP(' 根据匹配度1  ' .
            json_encode(
                $matchedDatas
            )
        );

        foreach ($dbres as $dataItem) {
            CommonService::getInstance()->log4PHP(' dataItem  ' .
                json_encode(
                    $dataItem
                )
            );
            foreach ($dataItem['data'] as $item) {
                $percent = $this->getSimilarPercent($item['name'], $entName);
                $matchedDatas[$percent] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                ];
            }
        }
        CommonService::getInstance()->log4PHP(' 根据匹配度2  ' .
            json_encode(
                $matchedDatas
            )
        );
        //根据匹配度 返回最高的一个
        ksort($matchedDatas);
        $resData = end($matchedDatas);
        CommonService::getInstance()->log4PHP(' 根据匹配度  ' .
            json_encode(
                $matchedDatas
            )
        );

        $timeEnd = microtime(true);
        $execution_time1 = (microtime(true) - $timeStart);

        return $resData;
        // return [
        //     'Time' => 'Total Execution Time:'.$execution_time1.' 秒  |',
        //     'data' => $resData,
        // ];  
    }

    function matchEntByName2($entName, $matchType = 1, $timeOut = 3.5)
    {
        $timeStart = microtime(true);

        $sql = "SELECT  id,`name` FROM  `company`  WHERE   `name` = '$entName' LIMIT 1";
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
        if (!empty($list)) {
            return $list[0];
        }

        // 从结果找
        $matchedDatas = [];
        for (
            $i = 0; $i <= 3; $i++
        ) {
            $csp = new \EasySwoole\Component\Csp();
            $start = $i * 2;
            $end = $start + 1;

            for ($j = $start; $j <= $end; $j++) {

                $csp->add('BOOLEAN_MODE_new_company_name_' . $j, function () use ($entName, $j) {
                    $timeStart2 = microtime(true);
                    $matchStr = (new XinDongService())->splitChineseNameForMatchAgainst($entName);
                    $retData = (new XinDongService())
                        ->matchAainstEntName(
                            $matchStr,
                            " IN BOOLEAN MODE ",
                            'company_name_' . $j,
                            'id,name',
                            5
                        );
                    $timeEnd2 = microtime(true);
                    $execution_time11 = ($timeEnd2 - $timeStart2);
                    return [
                        'data' => $retData,
                        'type' => 'Boolean',
                        'time' => $execution_time11
                    ];
                });
            }

            $dbres = ($csp->exec($timeOut));
            CommonService::getInstance()->log4PHP('从db找 res' .
                json_encode(
                    [
                        'data' => $dbres,
                        'time' => (microtime(true) - $timeStart),
                    ]
                )
            );
            foreach ($dbres as $dataItem) {
//                CommonService::getInstance()->log4PHP(' dataItem  '.
//                    json_encode(
//                        $dataItem
//                    )
//                );
                foreach ($dataItem['data'] as $item) {
                    if ($this->checkIfSimilar($item['name'], $entName)) {
                        CommonService::getInstance()->log4PHP('es match ok , return ' .
                            json_encode(
                                [
                                    'data' => $item,
                                    'time' => (microtime(true) - $timeStart),
                                ]
                            )
                        );
                        return $item;
                    }
                }
            }

            // 剩余的 按照相似度排序 然后返回相似度最高的
            foreach ($dbres as $dataItem) {
//                CommonService::getInstance()->log4PHP(' dataItem  '.
//                    json_encode(
//                        $dataItem
//                    )
//                );
                foreach ($dataItem['data'] as $item) {
                    $percent = $this->getSimilarPercent($item['name'], $entName);
                    $matchedDatas[$percent] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                    ];
                }
            }
            CommonService::getInstance()->log4PHP(' 根据匹配度2  ' .
                json_encode(
                    $matchedDatas
                )
            );
//            sleep(0.5);
        }

        //根据匹配度 返回最高的一个
        ksort($matchedDatas);
        $resData = end($matchedDatas);
        CommonService::getInstance()->log4PHP(' 根据匹配度  ' .
            json_encode(
                $matchedDatas
            )
        );

//        $timeEnd = microtime(true);
//        $execution_time1 = (microtime(true) - $timeStart);

        return $resData;
    }

    static function trace()
    {
        $old_traces = debug_backtrace();
        $new_traces = [];
        if (empty($old_traces)) {
            return [];
        }

        $allowed_field_arr = [
            'file',
            'line',
            'function',
        ];

        foreach ($old_traces as $traceArr) {
            $tmpArr = [];
            if (in_array($traceArr['function'], [
                'trace',
                'dispatch',
                'controllerHandler',
                '__hook',
                '__exec'
            ])) {
                continue;
            }
            foreach ($traceArr as $trac_key => $trace_value) {
                if (!in_array(
                    $trac_key,
                    $allowed_field_arr
                )) {
                    continue;
                }
                $tmpArr[$trac_key] = $trace_value;
            }
            if (empty($tmpArr)) {
                continue;
            }
            $new_traces[] = $tmpArr;
        }
        return $new_traces;
    }

    function getLogoByEntIdV2($entId)
    {
        $LogoRes = AggrePicsH::findByCompanyidId($entId);

        if (empty($LogoRes)) {
            return '';
        }
        return str_replace('logo', '', $LogoRes->getAttr('pic'));
    }

    function getLogoByEntId($entId)
    {
        $logoData = XsyA24Logo::create()
            ->where('id', $entId)
            ->get();
        // CommonService::getInstance()->log4PHP('logo '.json_encode([
        //     $logoData,
        //     ]));
        if (empty($logoData)) {
            return '';
        }
        return str_replace('logo', '', $logoData->getAttr('file_path'));
    }

    function getEsBasicInfoV2($companyId, $configData = [
        'fill_LAST_EMAIL' => true,
        'fill_logo' => true,
        'fill_tags' => true,
        'fill_gong_si_jian_jie_data_arr' => true,
    ]): array
    {
        $ElasticSearchService = new ElasticSearchService();

        $ElasticSearchService->addMustMatchQuery('companyid', $companyId);

        $size = 1;
        $page = 1;
        $offset = ($page - 1) * $size;
        $ElasticSearchService->addSize($size);
        $ElasticSearchService->addFrom($offset);
        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService, 'company_202211');
        $responseArr = @json_decode($responseJson, true);
        // CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
        //     [
        //         'es_query' => $ElasticSearchService->query,
        //         'post_data' => $this->request()->getRequestParam(),
        //     ]
        // ));

        // 格式化下日期和时间
        $hits = $responseArr['hits']['hits'];

        foreach ($hits as &$dataItem) {
            if ($configData['fill_LAST_EMAIL']) {
                $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmailV2($dataItem);
                $dataItem['_source']['LAST_DOM'] = $addresAndEmailData['LAST_DOM'];
                $dataItem['_source']['LAST_EMAIL'] = $addresAndEmailData['LAST_EMAIL'];
            }

            if ($configData['fill_logo']) {
                $dataItem['_source']['logo'] = (new XinDongService())->getLogoByEntIdV2($dataItem['_source']['companyid']);
            }

            if ($configData['fill_tags']) {
                // 添加tag
                $dataItem['_source']['tags'] = array_values(
                    (new XinDongService())::getAllTagesByData(
                        $dataItem['_source']
                    )
                );
            }

            if ($configData['fill_gong_si_jian_jie_data_arr']) {
                // 公司简介
                $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
                array_pop($tmpArr);
                $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
                foreach ($tmpArr as $tmpItem_) {
                    // $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
                    $dataItem['_source']['gong_si_jian_jie_data_arr'][] = $tmpItem_;
                }
            }


            // 官网
            $webStr = trim($dataItem['_source']['web']);
            if (!$webStr) {
                continue;
            }
            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);
        }
        $res = $hits[0]['_source'];
        return !empty($res) ? $res : [];
    }

    function getEsBasicInfoV3($value, $field = 'ENTNAME',$configs = [
        'needs_logo'=>true,
        'needs_email'=>true,
    ]): array
    {

        $ElasticSearchService = new ElasticSearchService();

        $ElasticSearchService->addMustMatchPhraseQuery($field, $value);

        $size = 1;
        $page = 1;
        $offset = ($page - 1) * $size;
        $ElasticSearchService->addSize($size);
        $ElasticSearchService->addFrom($offset);
        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService, 'company_202211');
        $responseArr = @json_decode($responseJson, true);
        // CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
        //     [
        //         'es_query' => $ElasticSearchService->query,
        //         'post_data' => $this->request()->getRequestParam(),
        //     ]
        // ));

        // 格式化下日期和时间
        $hits = $responseArr['hits']['hits'];

        foreach ($hits as &$dataItem) {
            if($configs['needs_email']){
                $addresAndEmailData = (new XinDongService())->getLastPostalAddressAndEmailV2($dataItem);
                $dataItem['_source']['LAST_DOM'] = $addresAndEmailData['LAST_DOM'];
                $dataItem['_source']['LAST_EMAIL'] = $addresAndEmailData['LAST_EMAIL'];
            }

            if($configs['needs_logo']){
                $dataItem['_source']['logo'] = (new XinDongService())->getLogoByEntIdV2($dataItem['_source']['companyid']);
            }


            // 添加tag
            if($configs['needs_tags']){
                $dataItem['_source']['tags'] = array_values(
                    (new XinDongService())::getAllTagesByData(
                        $dataItem['_source']
                    )
                );
            }


            // 公司简介
            $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
            array_pop($tmpArr);
            $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
            foreach ($tmpArr as $tmpItem_) {
                // $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
                $dataItem['_source']['gong_si_jian_jie_data_arr'][] = $tmpItem_;
            }


            // 官网
            $webStr = trim($dataItem['_source']['web']);
            if (!$webStr) {
                continue;
            }
            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);
        }
        $res = $hits[0]['_source'];
        return !empty($res) ? $res : [];
    }

    function getEsBasicInfo($companyId): array
    {

        $ElasticSearchService = new ElasticSearchService();

        $ElasticSearchService->addMustMatchQuery('xd_id', $companyId);

        $size = 1;
        $page = 1;
        $offset = ($page - 1) * $size;
        $ElasticSearchService->addSize($size);
        $ElasticSearchService->addFrom($offset);

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson, true);
        // CommonService::getInstance()->log4PHP('advancedSearch-Es '.@json_encode(
        //     [
        //         'es_query' => $ElasticSearchService->query,
        //         'post_data' => $this->request()->getRequestParam(),
        //     ]
        // )); 

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


        foreach ($hits as &$dataItem) {
            $addresAndEmailData = $this->getLastPostalAddressAndEmail($dataItem);
            $dataItem['_source']['last_postal_address'] = $addresAndEmailData['last_postal_address'];
            $dataItem['_source']['last_email'] = $addresAndEmailData['last_email'];

            // 公司简介
            $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
            array_pop($tmpArr);
            $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
            foreach ($tmpArr as $tmpItem_) {
                // $dataItem['_source']['gong_si_jian_jie_data_arr'][] = [$tmpItem_];
                $dataItem['_source']['gong_si_jian_jie_data_arr'][] = $tmpItem_;
            }

            // tag信息
            $dataItem['_source']['tags'] = array_values(
                (new XinDongService())::getAllTagesByData(
                    $dataItem['_source']
                )
            );

            // 官网信息
            $webStr = trim($dataItem['_source']['web']);
            if (!$webStr) {
                continue;
            }

            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr);
        }
        $res = $hits[0]['_source'];
        return !empty($res) ? $res : [];
    }

    function getLastPostalAddressAndEmail($dataItem)
    {
        if (!empty($dataItem['_source']['report_year'])) {
            $lastReportYearData = end($dataItem['_source']['report_year']);
            return [
                'last_postal_address' => $lastReportYearData['postal_address'],
                'last_email' => $lastReportYearData['email'],
            ];
        }
        return [
            'last_postal_address' => '',
            'last_email' => '',
        ];
    }

    function getLastPostalAddressAndEmailV2($dataItem)
    {
        if (!empty($dataItem['_source']['report_year'])) {
            $lastReportYearData = end($dataItem['_source']['report_year']);
            return [
                'LAST_DOM' => $lastReportYearData['DOM'],
                'LAST_EMAIL' => $lastReportYearData['EMAIL'],
            ];
        }
        return [
            'LAST_DOM' => '',
            'LAST_EMAIL' => '',
        ];
    }

    function addCarInsuranceInfo($dataItem)
    {
        $oldModel = CarInsuranceInfo::create()
            ->where(
                [
                    'vin' => $dataItem['vin'],
                    'entId' => $dataItem['entId'],
                ])->get();
        if ($oldModel) {
            return $oldModel->getAttr('id');
        }

        try {
            $newModel = CarInsuranceInfo::create()
                ->data([
                    'vin' => $dataItem['vin'],
                    'entId' => $dataItem['entId'],
                    'idCard' => $dataItem['idCard'],
                    'legalPerson' => $dataItem['legalPerson'],
                ])->save();
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        }
        return $newModel;
    }

    function addCompanyCarInsuranceStatusInfo($dataItem)
    {
        $oldModel = CompanyCarInsuranceStatusInfo::create()
            ->where(
                [
                    'entId' => $dataItem['entId'],
                ])->get();
        if ($oldModel) {
            return $oldModel;
        }

        try {
            $newModel = CarInsuranceInfo::create()
                ->where([
                    'entId' => $dataItem['entId'],
                ])->save();
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        }
        return $newModel;
    }

    function addUserCarsRelation($dataItem)
    {
        $oldModel = UserCarsRelation::create()
            ->where(
                [
                    'car_insurance_id' => $dataItem['car_insurance_id'],
                    'user_id' => $dataItem['user_id'],
                ])->get();
        if ($oldModel) {
            return $oldModel;
        }

        try {
            $newModel = CarInsuranceInfo::create()
                ->where([
                    'car_insurance_id' => $dataItem['car_insurance_id'],
                    'user_id' => $dataItem['user_id'],
                ])->save();
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        }
        return $newModel;
    }

    /*
    config:
    [
        'matchNamesByEqual' = true,
        'matchNamesByContain' = true,
    ]
    */
    function matchNames($tobeMatch, $target, $config)
    {
        //完全匹配
        if ($config['matchNamesByEqual']) {
            $res = $this->matchNamesByEqual($tobeMatch, $target);
            if ($res) {
                CommonService::getInstance()->log4PHP(
                    'matchNamesByEqual yes :' . $tobeMatch . $target
                );
                return true;
            }
        }

        //包含匹配  张三0808    张三
        if ($config['matchNamesByContain']) {
            $res = $this->matchNamesByContain($tobeMatch, $target);
            if ($res) {
                CommonService::getInstance()->log4PHP(
                    'matchNamesByContain yes :' . $tobeMatch . $target
                );
                return true;
            }
        }

        //包含被匹配  张三0808    张三
        if ($config['matchNamesByToBeContain']) {
            $res = $this->matchNamesByToBeContain($tobeMatch, $target);
            if ($res) {
                CommonService::getInstance()->log4PHP(
                    'matchNamesByToBeContain yes :' . $tobeMatch . $target
                );
                return true;
            }
        }

        //文本匹配度  张三0808    张三   
        if ($config['matchNamesBySimilarPercentage']) {
            $res = $this->matchNamesBySimilarPercentage(
                $tobeMatch,
                $target,
                $config['matchNamesBySimilarPercentageValue']
            );
            if ($res) {
                CommonService::getInstance()->log4PHP(
                    'matchNamesBySimilarPercentageValue yes :' . $tobeMatch . $target
                );
                return true;
            }
        }

        //文本匹配度  张三0808    张三   
        if ($config['matchNamesByPinYinSimilarPercentage']) {
            $res = $this->matchNamesByPinYinSimilarPercentage(
                $tobeMatch,
                $target,
                $config['matchNamesByPinYinSimilarPercentageValue']
            );
            if ($res) {
                CommonService::getInstance()->log4PHP(
                    'matchNamesByPinYinSimilarPercentageValue yes :' . $tobeMatch . $target
                );
                return true;
            }
        }
//        CommonService::getInstance()->log4PHP(
//            'matchNames no  :' .$tobeMatch.$target
//        );
        return false;

    }

    // $tobeMatch 姓名   $target：微信名
    function matchNamesV2($tobeMatch, $target)
    {

        //完全匹配
        $res = $this->matchNamesByEqual($tobeMatch, $target);
        if ($res) {
            return [
                'type' => '完全匹配',
                'details' => '名称完全匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }

        //包含被匹配  张三0808    张三
        $res = $this->matchNamesByToBeContain($tobeMatch, $target);
        if ($res) {
            return [
                'type' => '完全匹配',
                'details' => '中文被包含匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }

        //拼音全等
        $tobeMatchArr = $this->getPinYin($tobeMatch);
//        CommonService::getInstance()->log4PHP(json_encode(['$tobeMatchArr'=>$tobeMatchArr]));

        if (
            count($tobeMatchArr) == 2
        ) {
            //顺序拼音
            $str1 = $tobeMatchArr[0] . $tobeMatchArr[1];
            //逆序拼音
            $str2 = $tobeMatchArr[1] . $tobeMatchArr[0];
//            CommonService::getInstance()->log4PHP(json_encode(['match pinyin '=>[$str1,$str2]]));

            if (
                $str1 == $target ||
                $str2 == $target
            ) {
                return [
                    'type' => '完全匹配',
                    'details' => '拼音相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        if (
            count($tobeMatchArr) == 3
        ) {
            $str1 = $tobeMatchArr[0] . $tobeMatchArr[1] . $tobeMatchArr[2];
            $str2 = $tobeMatchArr[0] . $tobeMatchArr[2] . $tobeMatchArr[1];
            $str3 = $tobeMatchArr[1] . $tobeMatchArr[0] . $tobeMatchArr[2];
            $str4 = $tobeMatchArr[1] . $tobeMatchArr[2] . $tobeMatchArr[0];
            $str5 = $tobeMatchArr[2] . $tobeMatchArr[0] . $tobeMatchArr[1];
            $str6 = $tobeMatchArr[2] . $tobeMatchArr[1] . $tobeMatchArr[0];
//            CommonService::getInstance()->log4PHP(json_encode(['match pinyin2 '=>[$str1,$str2,$str3,$str4,$str5,$str6]]));
            if (
                $str1 == $target ||
                $str2 == $target ||
                $str3 == $target ||
                $str4 == $target ||
                $str5 == $target ||
                $str6 == $target
            ) {
                return [
                    'type' => '完全匹配',
                    'details' => '拼音相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        //拼音缩写
        if (
            count($tobeMatchArr) == 2
        ) {
            $name1 = PinYinService::getShortPinyin(substr($tobeMatch, 0, 3));
            $name2 = PinYinService::getShortPinyin(substr($tobeMatch, 3, 3));
//            CommonService::getInstance()->log4PHP(json_encode(['match short  pinyin '=>[$name1,$name2]]));

            $str1 = $name1 . $name2;
            $str2 = $name2 . $name1;
            if (
                $str1 == $target ||
                $str2 == $target
            ) {
                return [
                    'type' => '完全匹配',
                    'details' => '拼音首字母相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        //拼音缩写
        if (
            count($tobeMatchArr) == 3
        ) {
            $name1 = PinYinService::getShortPinyin(substr($tobeMatch, 0, 3));
            $name2 = PinYinService::getShortPinyin(substr($tobeMatch, 3, 3));
            $name3 = PinYinService::getShortPinyin(substr($tobeMatch, 6, 3));
//            CommonService::getInstance()->log4PHP(json_encode(['match short  pinyin2 '=>[$name1,$name2,$name3]]));

            $str1 = $name1 . $name2 . $name3;
            $str2 = $name1 . $name3 . $name2;
            $str3 = $name2 . $name1 . $name3;
            $str4 = $name2 . $name3 . $name1;
            $str5 = $name3 . $name2 . $name1;
            $str6 = $name3 . $name1 . $name2;

            if (
                $str1 == $target ||
                $str2 == $target ||
                $str3 == $target ||
                $str4 == $target ||
                $str5 == $target ||
                $str6 == $target
            ) {
                return [
                    'type' => '完全匹配',
                    'details' => '拼音首字母相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }


        //包含匹配  张三0808    张三
        $res = $this->matchNamesByContain($tobeMatch, $target);

        if ($res) {
            return [
                'type' => '近似匹配',
                'details' => '中文包含匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }



        //两个字的
        if(
            strlen($tobeMatch) == 6
        ){
            $sub = substr($tobeMatch, 3, 3);
            //如果去掉姓名后  微信名直接包含：张三  三爷
            if(strpos($target,$sub) !== false){
                return [
                    'type' => '近似匹配',
                    'details' => '包含姓名中的名',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }


            similar_text($sub, $target, $perc);
            $tobeMatchArr = $this->getPinYin($tobeMatch);
            $targetArr = $this->getPinYin($target);


        }


        //3个字的
        if(
            strlen($tobeMatch) == 9
        ){
            $sub = substr($tobeMatch, 3, 6);
            //如果去掉姓名后  微信名直接包含：张小三  小三爷
            if(strpos($target,$sub) !== false){
                return [
                    'type' => '近似匹配',
                    'details' => '包含姓名中的名',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }


            similar_text($sub, $target, $perc);
            if($perc>=50){
                return [
                    'type' => '近似匹配',
                    'details' => '名和微信比较近似',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        //4个字的
        if(
            strlen($tobeMatch) == 12
        ){
            $sub = substr($tobeMatch, 6, 6);
            //如果去掉姓名后  微信名直接包含：欧阳小三  小三爷
            if(strpos($target,$sub) !== false){
                return [
                    'type' => '近似匹配',
                    'details' => '包含姓名中的名',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }

            similar_text($sub, $target, $perc);
            if($perc>=50){
                return [
                    'type' => '近似匹配',
                    'details' => '名和微信比较近似',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        //拼音包含
        similar_text(PinYinService::getPinyin($tobeMatch), PinYinService::getPinyin($target), $perc);
        $res = array_intersect($tobeMatchArr, $targetArr);
        if (
            !empty($res) &&
            $perc >= 90
        ) {
            return [
                'type' => '近似匹配',
                'details' => '拼音包含匹配',
                'res' => '成功',
                'percentage' => number_format($perc, 2),
            ];
        }


        //多音字匹配
        $tobeMatchArr = $this->getPinYin($tobeMatch);
        $targetArr = $this->getPinYin($target);
//        CommonService::getInstance()->log4PHP(json_encode(['duo yin zi  '=>['$tobeMatchArr' => $tobeMatchArr,'$targetArr' =>$targetArr]]));

        $res = $this->checkIfArrayEqual($tobeMatchArr, $targetArr);
        if ($res) {
            return [
                'type' => '近似匹配',
                'details' => '多音字匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }



        //文本匹配度  张三0808    张三
        similar_text($tobeMatch, $target, $perc);
        if ($perc > 80) {
            return [
                'type' => '近似匹配',
                'details' => '中文相似度匹配',
                'res' => '成功',
                'percentage' => number_format($perc, 2),
            ];
        }

        //拼音相似度匹配  张三0808    张三
        similar_text(PinYinService::getPinyin($tobeMatch), PinYinService::getPinyin($target), $perc);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'similar_text' => [
//                    '$perc' => $perc,
//                    '$tobeMatch'=>$tobeMatch,
//                    '$target'=>$target,
//                ]
//            ],JSON_UNESCAPED_UNICODE)
//        );

        if ($perc >= 90) {
            return [
                'type' => '近似匹配',
                'details' => '拼音相似度匹配',
                'res' => '成功',
                'percentage' => number_format($perc, 2),
            ];
        }

        return [
            'type' => '',
            'details' => '',
            'res' => '失败',
            'percentage' => 0,
        ];

    }

    function checkIfArrayEqual($array1, $array2)
    {

        foreach ($array1 as $value1) {
            if (
                !in_array($value1, $array2)
            ) {
                return false;
            }
        }

        return true;
    }

    //  tobeMatch：张三丰  target：张三丰
    function matchNamesByEqual($tobeMatch, $target)
    {
        $res = $tobeMatch === $target ? true : false;
//        CommonService::getInstance()->log4PHP(
//            'matchNamesByEqual :'.json_encode([
//                $res,
//                $tobeMatch,
//                $target
//            ])
//        );
        return $res;
    }

    function getPinYin($target)
    {
        $targetList = [];
        $init = strlen($target);
        $nums = 0;
        while ($init > 0) {
            $targetList[] = PinYinService::getPinyin(substr($target, $nums, 3));
            $nums += 3;
            $init -= 3;
        }
        return $targetList;
    }

    // tobeMatch : 张三0808  target：张三
    function matchNamesByContain($tobeMatch, $target)
    {
        $res = false;

        if (strpos($tobeMatch, $target) !== false) {
            $res = true;
        }

//        CommonService::getInstance()->log4PHP(
//            'matchNamesByContain :'.json_encode([
//                $res,
//                $tobeMatch,
//                $target
//            ])
//        );
        return $res;
    }

    // tobeMatch : tobeMatch：三丰  target：张三丰 
    function matchNamesByToBeContain($tobeMatch, $target)
    {
        $res = false;

        if (strpos($target, $tobeMatch) !== false) {
            $res = true;
        }

//        CommonService::getInstance()->log4PHP(
//            'matchNamesByContain :'.json_encode([
//                $res,
//                $tobeMatch,
//                $target
//            ])
//        );
        return $res;
    }

    // tobeMatch : tobeMatch：三丰  target：张三丰 
    function matchNamesBySimilarPercentage($tobeMatch, $target, $percentage)
    {
        $res = false;
        similar_text($tobeMatch, $target, $perc);
        if ($perc >= $percentage) {
            $res = true;
        }

        CommonService::getInstance()->log4PHP(
            'matchNamesByContain :' . json_encode([
                $res,
                $tobeMatch,
                $target,
                $perc,
                $percentage
            ])
        );
        return $res;
    }


    // tobeMatch : tobeMatch：三丰  target：张三丰
    function matchNamesByPinYinSimilarPercentage($tobeMatch, $target, $percentage)
    {
        $res = false;
        $tobeMatchPin = PinYinService::getPinyin($tobeMatch);
        $targetPinYin = PinYinService::getPinyin($target);
        similar_text($tobeMatchPin, $targetPinYin, $perc);
        if ($perc >= $percentage) {
            $res = true;
        }

        CommonService::getInstance()->log4PHP(
            'matchNamesByContain :' . json_encode([
                $res,
                $tobeMatch,
                $target,
                $perc,
                $percentage,
                $tobeMatchPin,
                $targetPinYin,
            ])
        );
        return $res;
    }

    function matchContactNameByWeiXinName($entName, $WeiXin)
    {
        $matchedContactName = [];

        //获取所有联系人
        $staffsDatas = LongXinService::getLianXiByName($entName);
        if (empty($staffsDatas)) {
            return $matchedContactName;
        }

        foreach ($staffsDatas as $staffsDataItem) {
            $tmpName = trim($staffsDataItem['stff_name']);
            if (!$tmpName) {
                continue;
            };
            $res = (new XinDongService())->matchNames($tmpName, $WeiXin,
                [
                    'matchNamesByEqual' => true,
                    'matchNamesByContain' => true,
                    'matchNamesByToBeContain' => true,
                    'matchNamesBySimilarPercentage' => true,
                    'matchNamesBySimilarPercentageValue' => 60,
                    'matchNamesByPinYinSimilarPercentage' => true,
                    'matchNamesByPinYinSimilarPercentageValue' => 60,
                ]);
            if ($res) {
//                CommonService::getInstance()->log4PHP(
//                    'matchContactNameByWeiXinName yes  :' .$tmpName . $WeiXin
//                );
                return $staffsDataItem;
            }
        }

        return $matchedContactName;
    }

    function matchContactNameByWeiXinNameV2($entName, $WeiXin)
    {

        //获取所有联系人
        $staffsDatas = LongXinService::getLianXiByName($entName);
        if (empty($staffsDatas)) {
            return [];
        }

        foreach ($staffsDatas as $staffsDataItem) {
            $tmpName = trim($staffsDataItem['stff_name']);
            if (!$tmpName) {
                continue;
            };
            $res = (new XinDongService())->matchNamesV2($tmpName, $WeiXin);
            if ($res['res'] == '成功') {
//                CommonService::getInstance()->log4PHP(
//                    'matchContactNameByWeiXinName yes  :' .$tmpName . $WeiXin
//                );
                return [
                    'data' => $staffsDataItem,
                    'match_res' => $res
                ];
            }
        }

        return [];
    }

    function matchContactNameByWeiXinNameV3($entName, $WeiXin)
    {

        //获取所有联系人
        $staffsDatas = LongXinService::getLianXiByNameV2($entName);

        foreach ($staffsDatas as $staffsDataItem) {
            $tmpName = trim($staffsDataItem['NAME']);

            if (!$tmpName) {
                continue;
            };

            $res = (new XinDongService())->matchNamesV2($tmpName, $WeiXin);

            if ($res['res'] == '成功') {

                return [
                    'data' => $staffsDataItem,
                    'match_res' => $res
                ];
            }
        }

        return [];
    }

    //模糊匹配企业名称  根据企业简称  匹配企业
    static function fuzzyMatchEntName($fuzzyName, $size = 1)
    {
        $companyEsModel = new \App\ElasticSearch\Model\Company();
        $companyEsModel->es->addMustMatchQuery('ENTNAME', $fuzzyName);
        $companyEsModel
            ->addSize($size)
            ->addFrom(0)
            //->searchFromEs('company_202211',true);
            ->searchFromEs('company_202211');

        $returnData = [];
        foreach ($companyEsModel->return_data['hits']['hits'] as $dataItem) {
            $returnData[] = $dataItem;
        }
        return $returnData;
    }

    static function getMarjetShare($xd_id)
    {
        return '';
        $companyEsModel = new \App\ElasticSearch\Model\Company();
        $companyEsModel
            //根据id查询
            ->addMustTermQuery('xd_id', $xd_id)
            ->addSize(1)
            ->addFrom(0)
            ->searchFromEs()
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney();

        //四级分类
        $siJiFenLei = "";
        $ying_shou_gui_mo = "";
        foreach ($companyEsModel->return_data['hits']['hits'] as $dataItem) {
            $siJiFenLei = $dataItem['_source']['si_ji_fen_lei_code'];
            $ying_shou_gui_mo = $dataItem['_source']['ying_shou_gui_mo'];
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                'si_ji_fen_lei_code  ' => $siJiFenLei,
                'ying_shou_gui_mo  ' => $ying_shou_gui_mo,

            ])
        );
        if (empty($siJiFenLei)) {
            return "";
        }
        if (empty($ying_shou_gui_mo)) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__ . __LINE__ => 'empty $ying_shou_gui_mo',
                    '$ying_shou_gui_mo' => $ying_shou_gui_mo,
                ])
            );
            return "";
        }
        //三位以下的  企业太多了 不计算
        if (strlen($siJiFenLei) <= 3) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__ . __LINE__ => 'too shot  $ying_shou_gui_mo',
                    '$ying_shou_gui_mo' => $ying_shou_gui_mo,
                ])
            );
            return "";
        }

        //取前四位
        $tmpSiji = substr($siJiFenLei, 0, 5);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'first_for_letter_of_si_ji_fen_lei_code  '=>$tmpSiji
//            ])
//        );
        //所有满足的企业
        $companyEsModel = new \App\ElasticSearch\Model\Company();
        $companyEsModel
            ->SetQueryBySiJiFenLei($tmpSiji)
            ->addSize(1000)
            ->addSort("_id", "asc")
            ->setSource(['ying_shou_gui_mo'])
            ->searchFromEs()
            // 格式化下日期和时间
            // ->formatEsDate()

            // 格式化下金额
            //->formatEsMoney()
        ;
        //===========================
        $siJiFenLeiArrs = [];
        $nums = 0;
        while (!empty($companyEsModel->return_data['hits']['hits'])) {
            foreach ($companyEsModel->return_data['hits']['hits'] as $dataItem) {
                $lastId = $dataItem['_id'];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$lastId' => $lastId
//                    ])
//                );

//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$nums' => $nums
//                    ])
//                );
                $dataItem['_source']['ying_shou_gui_mo'] && $siJiFenLeiArrs[] = $dataItem['_source']['ying_shou_gui_mo'];
                $nums++;

            }

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                ->SetQueryBySiJiFenLei($tmpSiji)
                ->addSize(1000)
                ->addSort("_id", "asc")
                ->setSource(['ying_shou_gui_mo'])
                ->addSearchAfterV1($lastId)
                ->searchFromEs()
                // 格式化下日期和时间
                //->formatEsDate()
                // 格式化下金额
                //->formatEsMoney()
            ;
        }

        //===========================
        CommonService::getInstance()->log4PHP(
            json_encode([
                'match_companys_ying_shou_gui_mo_map_count  ' => count($siJiFenLeiArrs),
                '$nums' => $nums,
            ])
        );

        $totalMin = 0;
        $totalMax = 0;
        $yingShouGUiMoMap = XinDongService::getYingShouGuiMoMapV2();
        foreach ($siJiFenLeiArrs as $tmpSiJiFenLei) {
            $totalMin += $yingShouGUiMoMap[$tmpSiJiFenLei]['min'];
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    'cal_total_min_$tmpSiJiFenLei  '=>$tmpSiJiFenLei,
//                    'cal_total_min_value' => $yingShouGUiMoMap[$tmpSiJiFenLei]['min'],
//                ])
//            );
            $totalMax += $yingShouGUiMoMap[$tmpSiJiFenLei]['max'];
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    'cal_total_max_$tmpSiJiFenLei  '=>$tmpSiJiFenLei,
//                    'cal_total_max_value' => $yingShouGUiMoMap[$tmpSiJiFenLei]['max'],
//                ])
//            );
        }

        $rate1 = $yingShouGUiMoMap[$ying_shou_gui_mo]['min'] / $totalMin;
        $rate2 = $yingShouGUiMoMap[$ying_shou_gui_mo]['max'] / $totalMax;
        CommonService::getInstance()->log4PHP(
            json_encode([
                'market_share_$rate1  ' => [
                    '$rate1' => $rate1,
                    'fenzi' => $yingShouGUiMoMap[$ying_shou_gui_mo]['min'],
                    'fenmu' => $totalMin,
                ],
                'market_share_$rate2  ' => [
                    '$rate2' => $rate2,
                    'fenzi' => $yingShouGUiMoMap[$ying_shou_gui_mo]['max'],
                    'fenmu' => $totalMax,
                ],
            ])
        );
        $n1 = number_format($rate1, 5) * 100;
        $n2 = number_format($rate2, 5) * 100;
        return [
            'min' => $n1 . '%', 'max' => $n2 . '%',
        ];
    }


    // 破产重整排查 BankruptcyTs/GetList
    function getBankruptcyTs($entName)
    {
        $csp = CspService::getInstance()->create();

        //
        $csp->add('BankruptcyTs', function () use ($entName) {
            $postData = [
                'searchKey' => $entName,
            ];
            $res = (new LongDunService())
                ->setCheckRespFlag(true)
                ->get($this->ldUrl . 'BankruptcyTs/GetList', $postData);
            return empty($res['paging']) ? 0 : $res['paging']['total'];
        });

        //执行
        $res = CspService::getInstance()->exec($csp);
        $tmp = [];
        $tmp['BankruptcyTs'] = $res['BankruptcyTs'];

        return $this->checkResp(200, null, $tmp, '查询成功');
    }

    function getBankruptcyCheck($entName)
    {
        $csp = CspService::getInstance()->create();

        //
        $csp->add('BankruptcyCheck1', function () use ($entName) {
            $postData = [
                'searchKey' => $entName,
            ];
            $res = (new LongDunService())
                ->setCheckRespFlag(true)
                ->get($this->ldUrl . 'BankruptcyCheck/GetList', $postData);
            //return empty($res['paging']) ? 0 : $res['paging']['total'];
            return $res;
        });

        //执行
        $res = CspService::getInstance()->exec($csp);
        $tmp = [];
        $tmp['BankruptcyCheck1'] = $res['BankruptcyCheck1'];

        return $tmp['BankruptcyCheck1'];
    }

    static function collectInvoice($date, $monthsNums, $code)
    {

        for ($i = 1; $i <= $monthsNums; $i++) {
            $d1 = date("Y-m-01", strtotime("-1 month", strtotime($date)));
            $d2 = date("Y-m-t", strtotime("-1 month", strtotime($date)));

            $date = date("Y-m", strtotime("-1 month", strtotime($date)));

            $month = date('Y-m', strtotime($d1));
            //之前发起过任务
            if (
                InvoiceTask::findByNsrsbh($code, $month)
            ) {
                continue;
            };


            $res = (new JinCaiShuKeService())
                ->setCheckRespFlag(true)
                ->S000519($code, $d1, $d2);
            $dbId = InvoiceTask::addRecordV2([
                'nsrsbh' => $code,
                'month' => $month,
                'raw_return' => json_encode($res),
            ]);
            if ($dbId) {
                foreach ($res['result']['content'] as $dataItem) {

                    InvoiceTaskDetails::addRecordV2([
                        'invoice_task_id' => $dbId,
                        'fplx' => $dataItem['fplx'] ?: '',
                        'kprqq' => $dataItem['kprqq'] ?: '',
                        'kprqz' => $dataItem['kprqz'] ?: '',
                        'requuid' => $dataItem['requuid'] ?: '',
                        'rwh' => $dataItem['rwh'] ?: '',
                        'sjlx' => $dataItem['sjlx'] ?: '',
                    ]);
                }
            }
        }
        return true;
    }

    static function pullInvoice($code)
    {
        $dbRes = InvoiceTask::findBySql("WHERE nsrsbh = '" . $code . "'  AND  status = 1 LIMIT  2 ");
        foreach ($dbRes as $dbItem) {
            $details = InvoiceTaskDetails::findByInvoiceTaskId($dbItem['id']);
            foreach ($details as $detailItem) {
                $tmp = [];
                $datas = self::getYieldData($code, $detailItem['rwh']);
                foreach ($datas as $dataItem) {
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__ . __FUNCTION__ . __LINE__,
                            '$dataItem' => $dataItem
                        ])
                    );
                    $tmp[] = $dataItem;
                }
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__ . __FUNCTION__ . __LINE__,
                        '$tmp' => $tmp
                    ])
                );
                InvoiceTaskDetails::updateById(
                    $detailItem['id'], [
                        'raw_return' => json_encode($tmp)
                    ]
                );
            }

            InvoiceTask::updateById($dbItem['id'], [
                'status' => 5
            ]);
        }

        return true;
    }

    static function exportInvoice($code)
    {
        //
        $filenamesArr = [];

        $tasks = InvoiceTask::findBySql("WHERE nsrsbh = '" . $code . "'  AND  status = 5 limit 1 ");
        foreach ($tasks as $task) {
            $filename = 'Invoice_' . $task['nsrsbh'] . '_' . $task['month'] . '.csv';//设置文件名
            if (file_exists(TEMP_FILE_PATH . $filename)) {
                unlink(TEMP_FILE_PATH . $filename);
            }

            $f = fopen(TEMP_FILE_PATH . $filename, 'a'); // Configure fOpen to create, open and write only.
            fputcsv($f, [
                '开票人',//
                '开票日期',//
                '收款人',//
                '发票代码',//
                '发票类型代码',//
                '购买方地址电话',//
                '购买方名称',//
                '购买方纳税人识别号',//
                '购买方银行账号',//
                '备注',//
                '复核人',//
                '机器编号',//
                '价税合计',//
                '校验码',//
                '销售方地址电话',//
                '销售方名称',//
                '销售方纳税人识别号',//
                '销售方银行账号',//
                '不含税单价',//
                '规格型号',//
                '含税金额',//
                '税额',//
                '税率',//
                'xmmc',//
            ]);
            $details = InvoiceTaskDetails::findByInvoiceTaskId($task['id']);
            foreach ($details as $detailItem) {
                $returnDatas = json_decode($detailItem['raw_return'], true);
                foreach ($returnDatas as $returnData) {
                    if (empty($returnData['fpxxs']['data'])) {
                        continue;
                    };
                    foreach ($returnData['fpxxs']['data'] as $fpxxs_data) {
                        /**
                         * "fpfm": "110019313008360739",
                         * "fpzt": "0",
                         * "hjje": "-34881.6",
                         * "hjse": "-2092.9",
                         * "jdhm": "",
                         * "mmq": "0348>>72\/99+*4>*95043+853\/+-648639618+58878394\/<1-26\/97>+30>>482410+*4<<+190849+1>*0187\/-*0+1911*918033674\/\/+4*<",
                         * "mxs": [{
                         * "dw": "",
                         * "slv": "0.06",
                         * "ssflbm": "3040205000000000000",
                         * "xh": 1,
                         */

                        foreach ($fpxxs_data['mxs'] as $subItem) {
                            fputcsv($f, [
                                $fpxxs_data['kpr'],//开票人
                                $fpxxs_data['kprq'],//开票日期
                                $fpxxs_data['skr'],//收款人
                                $fpxxs_data['fpdm'],//发票代码
                                $fpxxs_data['fplx'],//发票类型代码
                                $fpxxs_data['gfdzdh'],//购买方地址电话
                                $fpxxs_data['gfmc'],//购买方名称
                                $fpxxs_data['gfsh'],//购买方纳税人识别号
                                $fpxxs_data['gfyhzh'],//购买方银行账号
                                $fpxxs_data['bz'],//备注
                                $fpxxs_data['fhr'],//复核人
                                $fpxxs_data['jqbh'],//机器编号
                                $fpxxs_data['jshj'],//价税合计
                                $fpxxs_data['jym'],//校验码
                                $fpxxs_data['xfdzdh'],//销售方地址电话
                                $fpxxs_data['xfmc'],//销售方名称
                                $fpxxs_data['xfsh'],//销售方纳税人识别号
                                $fpxxs_data['xfyhzh'],//销售方银行账号
                                $subItem['dj'],//不含税单价
                                $subItem['ggxh'],// 规格型号
                                $subItem['je'],//含税金额
                                $subItem['se'],//税额
                                $subItem['sl'],//税率
                                $subItem['xmmc'],//xmmc
                            ]);
                        };
                    }
                }
            }
            $filenamesArr[] = $filename;
            InvoiceTask::updateById($task['id'], [
                'status' => 10
            ]);
        }

        return $filenamesArr;
    }

    /**
     * '进项-list' => $tmpIncomeList,
     * '进项-detail' => $tmpIncomeDetail,
     * '销项-list' => $tmpOutcomeList,
     * '销项-detail' => $tmpOutcomeDetail,
     */
    static function getInvoiceYieldDataV2($code)
    {
        $datas = [];
        //所有状态为：已经拉回来数据的
        $tasks = InvoiceTask::findBySql("WHERE nsrsbh = '" . $code . "'  AND  status = 5");
        foreach ($tasks as $task) {
            $details = InvoiceTaskDetails::findByInvoiceTaskId($task['id']);
            foreach ($details as $detailItem) {
                $returnDatas = json_decode($detailItem['raw_return'], true);
                foreach ($returnDatas as $returnData) {
                    if (empty($returnData['fpxxs']['data'])) {
                        continue;
                    };
                    foreach ($returnData['fpxxs']['data'] as $fpxxs_data) {

                        $tmpIncomeList = [];
                        $tmpIncomeDetail = [];
                        $tmpOutcomeList = [];
                        $tmpOutcomeDetail = [];
                        $tmpList = [
                            //发票代码
                            $fpxxs_data['fpdm'],//发票代码
                            //发票号码
                            $fpxxs_data['fphm'],//发票号码
                            //开票类型

                            //销方税号
                            $fpxxs_data['xfsh'],//销售方纳税人识别号
                            //销方名称
                            $fpxxs_data['xfmc'],//销售方名称,
                            //销方地址
                            $fpxxs_data['xfdzdh'],//销售方地址电话
                            //销方账号
                            $fpxxs_data['xfyhzh'],//销售方银行账号
                            //购方税号
                            $fpxxs_data['gfsh'],//购买方纳税人识别号
                            //购方名称
                            $fpxxs_data['gfmc'],//购买方名称,
                            //购方地址
                            $fpxxs_data['gfdzdh'],//购买方地址电话
                            //购方账号
                            $fpxxs_data['gfyhzh'],//购买方银行账号
                            //开票人
                            $fpxxs_data['kpr'],//开票人
                            //收款人
                            $fpxxs_data['skr'],//收款人
                            //复核人
                            $fpxxs_data['fhr'],//复核人
                            //原发票代码
                            //原发票号码
                            //金额
                            $subItem['je'],//含税金额
                            //税额
                            $subItem['se'],//税额
                            //价税合计
                            $fpxxs_data['jshj'],//价税合计
                            //作废标志
                            //作废时间
                            //开票日期
                            $fpxxs_data['kprq'],//开票日期
                            //发票类型
                            $fpxxs_data['fplx'],//发票类型代码
                            //发票状态
                            $fpxxs_data['fpzt'],
                            //含税标志
                            //认证状态
                            //认证日期
                            //进销标志
                        ];
                        $tmpDeatl = [
                            //发票代码
                            $fpxxs_data['fpdm'],//发票代码
                            //发票号码
                            $fpxxs_data['fphm'],//发票号码
                            //税收分类编码
                            $subItem['ssflbm'],
                            // xmmc
                            $subItem['ssflbm'],
                            //单位
                            $subItem['dw'],
                            //数量
                            $subItem['sl'],
                            //金额
                            $subItem['je'],
                            //税率
                            $subItem['slv'],
                            //税额
                            $subItem['se'],
                            //不含税单价
                            $subItem['dj'],
                            //规格型号
                            $subItem['ggxh'],
                        ];
                        //进项
                        if (
                            $fpxxs_data['fplx'] == 1
                        ) {
                            //进项 list
                            $tmpIncomeList = $tmpList;
                            $tmpIncomeDetail = $tmpDeatl;
                        }
                        //进项
                        if (
                            $fpxxs_data['fplx'] == '01'
                        ) {
                            //进项 list
                            $tmpOutcomeList = $tmpList;
                            $tmpOutcomeDetails = $tmpDeatl;
                        }

                        yield $datas[] = [
                            '进项-list' => $tmpIncomeList,
                            '进项-detail' => $tmpIncomeDetail,
                            '销项-list' => $tmpOutcomeList,
                            '销项-detail' => $tmpOutcomeDetail,
                        ];
                    }
                }
            }
        }
    }

    static function getInvoiceYieldDataV3_income_detail($code, $type)
    {
        $datas = [];
        //所有状态为：已经拉回来数据的
        $tasks = InvoiceTask::findBySql("WHERE nsrsbh = '" . $code . "'  AND  status = 5");
        foreach ($tasks as $task) {
            $details = InvoiceTaskDetails::findByInvoiceTaskId($task['id']);
            foreach ($details as $detailItem) {
                $returnDatas = json_decode($detailItem['raw_return'], true);
                foreach ($returnDatas as $returnData) {
                    if (empty($returnData['fpxxs']['data'])) {
                        continue;
                    };
                    foreach ($returnData['fpxxs']['data'] as $fpxxs_data) {
                        foreach ($fpxxs_data['mxs'] as $subItem) {
                            CommonService::getInstance()->log4PHP(
                                json_encode([
                                    __CLASS__ . __FUNCTION__ . __LINE__,
                                    'sjlx1' => $type,
                                    'sjlx2' => $returnData['sjlx']
                                ])
                            );
                            if (
                                $returnData['sjlx'] == $type
                            ) {
                                yield $datas[] = [
                                    //发票代码
                                    $fpxxs_data['fpdm'],//发票代码
                                    //发票号码
                                    $fpxxs_data['fphm'],//发票号码
                                    //税收分类编码
                                    $subItem['ssflbm'],
                                    // xmmc
                                    $subItem['xmmc'],
                                    //单位
                                    $subItem['dw'],
                                    //数量
                                    $subItem['sl'],
                                    //金额
                                    $subItem['je'],
                                    //税率
                                    $subItem['slv'],
                                    //税额
                                    $subItem['se'],
                                    //不含税单价
                                    $subItem['dj'],
                                    //规格型号
                                    $subItem['ggxh'],
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    static function getInvoiceYieldDataV3_income_list($code, $type)
    {
        $datas = [];
        //所有状态为：已经拉回来数据的
        $tasks = InvoiceTask::findBySql("WHERE nsrsbh = '" . $code . "'  AND  status = 5");
        foreach ($tasks as $task) {
            $details = InvoiceTaskDetails::findByInvoiceTaskId($task['id']);
            foreach ($details as $detailItem) {
                $returnDatas = json_decode($detailItem['raw_return'], true);
                foreach ($returnDatas as $returnData) {
                    if (empty($returnData['fpxxs']['data'])) {
                        continue;
                    };
                    foreach ($returnData['fpxxs']['data'] as $fpxxs_data) {
                        CommonService::getInstance()->log4PHP(
                            json_encode([
                                __CLASS__ . __FUNCTION__ . __LINE__,
                                'sjlx1' => $type,
                                'sjlx2' => $returnData['sjlx']
                            ])
                        );
                        if (
                            $returnData['sjlx'] == $type
                        ) {
                            yield $datas[] = [
                                //发票代码
                                $fpxxs_data['fpdm'],//发票代码
                                //发票号码
                                $fpxxs_data['fphm'],//发票号码
                                //开票类型
                                $returnData['sjlx'],//发票类型代码
                                //销方税号
                                $fpxxs_data['xfsh'],//销售方纳税人识别号
                                //销方名称
                                $fpxxs_data['xfmc'],//销售方名称,
                                //销方地址
                                $fpxxs_data['xfdzdh'],//销售方地址电话
                                //销方账号
                                $fpxxs_data['xfyhzh'],//销售方银行账号
                                //购方税号
                                $fpxxs_data['gfsh'],//购买方纳税人识别号
                                //购方名称
                                $fpxxs_data['gfmc'],//购买方名称,
                                //购方地址
                                $fpxxs_data['gfdzdh'],//购买方地址电话
                                //购方账号
                                $fpxxs_data['gfyhzh'],//购买方银行账号
                                //开票人
                                $fpxxs_data['kpr'],//开票人
                                //收款人
                                $fpxxs_data['skr'],//收款人
                                //复核人
                                $fpxxs_data['fhr'],//复核人
                                // 不要：原发票代码 //原发票号码
                                //金额
                                $fpxxs_data['hjje'],//含税金额
                                //税额
                                $fpxxs_data['hjse'],//税额
                                //价税合计
                                $fpxxs_data['jshj'],//价税合计
                                //不需要：作废标志 //作废时间
                                //开票日期
                                $fpxxs_data['kprq'],//开票日期
                                //发票类型
                                $fpxxs_data['fplx'],//发票类型代码
                                //发票状态
                                $fpxxs_data['fpzt'],
                                //不需要：含税标志  //认证状态  //认证日期  //进销标志
                            ];
                        }
                    }
                }
            }
        }
    }


    static function exportInvoiceV2($code)
    {
        $filename = '发票数据_' . date('YmdHis') . '.xlsx';

        //===============================
        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];

        $excel = new \Vtiful\Kernel\Excel($config);
        //进项 list
        $fileObject = $excel->fileName($filename, '进项 list');
        $fileHandle = $fileObject->getHandle();
        $file = $fileObject
            ->header(
                [
                    '发票代码',
                    '发票号码',
                    '开票类型',
                    '销方税号',
                    '销方名称',
                    '销方地址',
                    '销方账号',
                    '购方税号',
                    '购方名称',
                    '购方地址',
                    '购方账号',
                    '开票人',
                    '收款人',
                    '复核人',
                    '金额',
                    '税额',
                    '价税合计',
                    '开票日期',
                    '发票类型',
                    '发票状态'
                ]
            );
        $incomeLists = self::getInvoiceYieldDataV3_income_list($code, '1');
        $i = 1;
        foreach ($incomeLists as $dataItem) {
            if ($i >= 10) {
//                continue;
            }
            $i++;
            $fileObject->data([$dataItem]);
        }
        //==============================================
        //进项 detail
        $file->addSheet('进项 detail')
            ->header([
                '发票代码',
                '发票号码',
                '税收分类编码',
                'xmmc',
                '单位',
                '数量',
                '金额',
                '税率',
                '税额',
                '不含税单价',
                '规格型号'
            ]);
        $incomeLists = self::getInvoiceYieldDataV3_income_detail($code, '1');
        $i = 1;
        foreach ($incomeLists as $dataItem) {
            if (empty($dataItem)) {
                continue;
            }
            if ($i >= 10) {
//                continue;
            }
            $i++;
            $file->data([$dataItem]);
        }
        //===============================
        //销项 list
        $file->addSheet('销项 list')
            ->header([
                '发票代码',
                '发票号码',
                '开票类型',
                '销方税号',
                '销方名称',
                '销方地址',
                '销方账号',
                '购方税号',
                '购方名称',
                '购方地址',
                '购方账号',
                '开票人',
                '收款人',
                '复核人',
                '金额',
                '税额',
                '价税合计',
                '开票日期',
                '发票类型',
                '发票状态'
            ]);
        $incomeLists = self::getInvoiceYieldDataV3_income_list($code, '2');
        $i = 1;
        foreach ($incomeLists as $dataItem) {
            if ($i >= 10) {
//                continue;
            }
            $i++;
            $file->data([$dataItem]);
        }
        //===============================
        //销项 detail
        $file->addSheet('销项 detail')
            ->header([
                '发票代码',
                '发票号码',
                '税收分类编码',
                'xmmc',
                '单位',
                '数量',
                '金额',
                '税率',
                '税额',
                '不含税单价',
                '规格型号'

            ]);
        $incomeLists = self::getInvoiceYieldDataV3_income_detail($code, '2');
        $i = 1;
        foreach ($incomeLists as $dataItem) {
            if ($i >= 10) {
//                continue;
            }
            $i++;
            $file->data([$dataItem]);
        }
        //===============================
        $fileObject->output();


        return [
            'filename' => $filename,
        ];
    }

    static function exportInvoiceV3($code)
    {
        $financeDatas = [
            ['xx', 'xx'],
            ['xx', 'xx'],
            ['xx', 'xx']
        ];
        $filename = '发票数据_' . date('YmdHis') . '.xlsx';

        //===============================
        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];

        $excel = new \Vtiful\Kernel\Excel($config);
        //进项 list
        $fileObject = $excel->fileName($filename, '进项 list');
        $fileHandle = $fileObject->getHandle();
        $file = $fileObject
            ->header(
                [
                    '标题', //
                    '项目名称', //
                ]
            );

        foreach ($financeDatas as $dataItem) {
            $fileObject->data([$dataItem]);
        }
        //==============================================
        //进项 detail
        $file->addSheet('进项 detail')
            ->header([
                '标题', //
                '项目名称', //

            ]);
        foreach ($financeDatas as $dataItem) {
            $file->data([$dataItem]);
        }
        //===============================
        //销项 list
        $file->addSheet('销项 list')
            ->header([
                '标题', //
                '项目名称', //

            ]);
        foreach ($financeDatas as $dataItem) {
            $file->data([$dataItem]);
        }
        //===============================
        //销项 detail
        $file->addSheet('销项 detail')
            ->header([
                '标题', //
                '项目名称', //

            ]);
        foreach ($financeDatas as $dataItem) {
            $file->data([$dataItem]);
        }
        //===============================
        $fileObject->output();


        return [
            'filename' => $filename,
        ];
    }

    static function getYieldData($code, $rwh)
    {
        $datas = [];
        $page = 1;
        $size = 10;

        while (true) {
            $res = (new JinCaiShuKeService())
                ->setCheckRespFlag(false)
                ->S000523($code, $rwh, $page, $size);
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    'S000523$res' => $res
//                ])
//            );
            //TODO：需要看什么时候有数据 什么时候无数据！ 注意： 原始返回 {
            //	"uuid": "6c80bdc7bfe242c4b5e4cdd8c3137182",
            //	"code": "0000",
            //	"msg": "归集任务执行结果查询成功",
            //	"content": "eyJyd2giOiIyNGE5NDU4ODVjM2E0ZWE1OTVkMmQ3ZWMxNWY5ZTk5OSIsImtwcnFxIjoiMjAyMC0wNy0wMSIsInNqbHgiOiIyIiwiZnBseCI6IjE1Iiwic3F6dHh4Ijoi5b6F5o+Q5Lqk5b2S6ZuG55Sz6K+3Iiwic3F6dCI6IjAiLCJrcHJxeiI6IjIwMjAtMDctMzEifQ=="
            //}
            $contentJson = base64_decode($res['content']);
            $contentArr = json_decode($contentJson, true);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__ . __FUNCTION__ . __LINE__,
                    'content' => $res['content'],
                    '$contentJson' => $contentJson,
                    '$contentArr' => $contentArr,
                    'page' => $page
                ])
            );
            $contentArr['page'] = $page;
            yield $datas[] = $contentArr;
            if (empty($contentArr['fpxxs']['data'])) {
                break;
            }

//            if ($page>2) {
//                break;
//            }

            $page++;
            // yield $datas[] = $res;
//            foreach ($res as $resItem){
//                $resItem['my_tmp_page'] = $page;
//                yield $datas[] = $resItem;
//            }
        }
    }

    static function getYieldDataV2($code, $rwh)
    {
        $datas = [];
        $page = 1;
        $size = 20;

        while (true) {
            $res = (new JinCaiShuKeService())
                ->setCheckRespFlag(false)
                ->S000523($code, $rwh, $page, $size);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__ . __FUNCTION__ . __LINE__,
                    'S000523$res' => $res
                ])
            );
            if (empty($res['result']['content'])) {
                break;
            }

            if ($page > 3) {
                break;
            }

            $page++;
            $datas[] = $res;
//            foreach ($res as $resItem){
//                $resItem['my_tmp_page'] = $page;
//                $datas[] = $resItem;
//            }
        }

        return $datas;
    }

    //是否纳税一般人
    function getEnterprise($code): array
    {
        $code = trim($code);

        $sql = "select NSRSBH,GSZCRQ from enterprise where NSRSBH = '{$code}'";

        $res = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'));

        CommonService::getInstance()->log4PHP($res);

        return $this->checkResp(200, null, $res, '查询成功');
    }

    function getEntMarketInfo($UNISCID): array
    {
        $info = CompanyBasic::create()->where(['UNISCID' => $UNISCID])->get();
        $data = [];
        if (!empty($info)) {
            $list = AggreListedH::create()
                ->where(['companyid' => $info->getAttr('companyid')])
                ->all();
            foreach ($list as $val) {
                $v = [
                    'SEC_CODE' => $val->getAttr('SEC_CODE'),
                    'SEC_SNAME' => $val->getAttr('SEC_SNAME'),
                    'SEC_STYPE' => $val->getAttr('SEC_STYPE'),
                    'MKT_TYPE' => $val->getAttr('MKT_TYPE'),
                    'LIST_STATUS' => $val->getAttr('LIST_STATUS'),
                    'LIST_SECTOR' => $val->getAttr('LIST_SECTOR'),
                    'LIST_DATE' => $val->getAttr('LIST_DATE'),
                    'LIST_ENDDATE' => $val->getAttr('LIST_ENDDATE'),
                    'ISIN' => $val->getAttr('ISIN'),
                    'nic_csrc' => $val->getAttr('nic_csrc'),
                    'nic_cf' => $val->getAttr('nic_cf'),
                    'nic_sws' => $val->getAttr('nic_sws'),
                    'nic_gisc' => $val->getAttr('nic_gisc'),
                    'EMPNUM' => $val->getAttr('EMPNUM'),
                    'TSTAFFNUM' => $val->getAttr('TSTAFFNUM'),
                ];
                $data[] = $v;
            }
        }
        return $this->checkResp(200, null, $data, '查询成功');
    }

    function getEntLiquidation($UNISCID): array
    {
        $info = CompanyBasic::create()
            ->where('UNISCID', $UNISCID)
            ->get();
        $list = [];
        if (!empty($info)) {
            $list = CompanyLiquidation::create()
                ->where('companyid', $info->getAttr('companyid'))
                ->field([
                    'PUBLISH_DATE',
                    'LIQMEN',
                    'LIGPRINCIPAL',
                ])->all();
        }
        return $this->checkResp(200, null, $list, '查询成功');
    }

    //认监委-ISO管理体系认证
    public function getCncaRzGltx_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CncaRzGltxH::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key]['cert_code']         = $item->getAttr('cert_code');
            $data[$key]['cert_project']      = $item->getAttr('cert_project');
            $data[$key]['cert_num']          = $item->getAttr('cert_num');
            $data[$key]['org_num']           = $item->getAttr('org_num');
            $data[$key]['cert_status']       = $item->getAttr('cert_status');
            $data[$key]['award_date']        = $item->getAttr('award_date');
            $data[$key]['expire_date']       = $item->getAttr('expire_date');
            $data[$key]['certificate_basis'] = $item->getAttr('certificate_basis');
            $data[$key]['certificate_scope'] = $item->getAttr('certificate_scope');
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-行政处罚
    public function getCaseAll_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CaseAll::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key]['CASETIME']      = $item->getAttr('CASETIME');
            $data[$key]['NAME']          = $item->getAttr('NAME');
            $data[$key]['REGNO']         = $item->getAttr('REGNO');
            $data[$key]['CERNO']         = $item->getAttr('CERNO');
            $data[$key]['CASEREASON']    = $item->getAttr('CASEREASON');
            $data[$key]['CASEVAL']       = $item->getAttr('CASEVAL');
            $data[$key]['CASETYPE']      = $item->getAttr('CASETYPE');
            $data[$key]['EXESORT']       = $item->getAttr('EXESORT');
            $data[$key]['CASERESULT']    = $item->getAttr('CASERESULT');
            $data[$key]['PENDECNO']      = $item->getAttr('PENDECNO');
            $data[$key]['PENDECISSDATE'] = $item->getAttr('PENDECISSDATE');
            $data[$key]['PENAUTHNAME']   = $item->getAttr('PENAUTHNAME');
            $data[$key]['PENAUTHID']     = $item->getAttr('PENAUTHID');
            $data[$key]['ILLEGFACT']     = $item->getAttr('ILLEGFACT');
            $data[$key]['PENBASIS']      = $item->getAttr('PENBASIS');
            $data[$key]['PENTYPE']       = $item->getAttr('PENTYPE');
            $data[$key]['PENRESULT']     = $item->getAttr('PENRESULT');
            $data[$key]['PENAM']         = $item->getAttr('PENAM');
            $data[$key]['PENEXEST']      = $item->getAttr('PENEXEST');
            $data[$key]['PUBDATE']       = $item->getAttr('PUBDATE');
            $data[$key]['ENDDATE']       = $item->getAttr('ENDDATE');
            $data[$key]['CONTENT']       = $item->getAttr('CONTENT');
        }

        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-抽查检查信息
    public function getCaseCheck_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CaseCheck::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
//                'REGNO'       => $item->getAttr('REGNO'),
//                'UNISCID'     => $item->getAttr('UNISCID'),
                'CHECKDATE'   => $item->getAttr('CHECKDATE'),
                'INSTYPE'     => $item->getAttr('INSTYPE'),
                'LOCALADM'    => $item->getAttr('LOCALADM'),
                'LOCALADMID'  => $item->getAttr('LOCALADMID'),
                'FOUNDPROB'   => $item->getAttr('FOUNDPROB'),
                'NOTICETITLE' => $item->getAttr('NOTICETITLE'),
                'NOTICEID'    => $item->getAttr('NOTICEID'),
                'SETST'       => $item->getAttr('SETST'),
                'SETSUG'      => $item->getAttr('SETSUG'),
                'REMARK'      => $item->getAttr('REMARK'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-严重违法失信
    public function getCaseYzwfsx_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CaseYzwfsx::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
//                'UNISCID'   => $item->getAttr('UNISCID'),
//                'REGNO'     => $item->getAttr('REGNO'),
                'inreason'  => $item->getAttr('inreason'),
                'indate'    => $item->getAttr('indate'),
                'inorg'     => $item->getAttr('inorg'),
                'outreason' => $item->getAttr('outreason'),
                'outdate'   => $item->getAttr('outdate'),
                'outorg'    => $item->getAttr('outorg'),
                'yztype'    => $item->getAttr('yztype'),
                'yzfact'    => $item->getAttr('yzfact'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-经营异常
    public function getCompanyAbnormity_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyAbnormity::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'INDATE'    => $item->getAttr('INDATE'),
                'INREASON'  => $item->getAttr('INREASON'),
                'YR_REGORG' => $item->getAttr('YR_REGORG'),
                'OUTDATE'   => $item->getAttr('OUTDATE'),
                'OUTREASON' => $item->getAttr('OUTREASON'),
                'YC_REGORG' => $item->getAttr('YC_REGORG'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //获取企业基本信息
    public function getCompanyBasic_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $data = [
//            'UNISCID'     => $info->getAttr('UNISCID'),
//            'REGNO'       => $info->getAttr('REGNO'),
            'NACAOID'     => $info->getAttr('NACAOID'),
            'NAME'        => $info->getAttr('NAME'),
            'NAMETITLE'   => $info->getAttr('NAMETITLE'),
            'ENTTYPE'     => $info->getAttr('ENTTYPE'),
            'ESDATE'      => $info->getAttr('ESDATE'),
            'APPRDATE'    => $info->getAttr('APPRDATE'),
            'ENTSTATUS'   => $info->getAttr('ENTSTATUS'),
            'REGCAP'      => $info->getAttr('REGCAP'),
            'REGCAP_NAME' => $info->getAttr('REGCAP_NAME'),
            'REGCAPCUR'   => $info->getAttr('REGCAPCUR'),
            'RECCAP'      => $info->getAttr('RECCAP'),
            'REGORG'      => $info->getAttr('REGORG'),
            'OPFROM'      => $info->getAttr('OPFROM'),
            'OPTO'        => $info->getAttr('OPTO'),
            'OPSCOPE'     => $info->getAttr('OPSCOPE'),
            'DOM'         => $info->getAttr('DOM'),
            'DOMDISTRICT' => $info->getAttr('DOMDISTRICT'),
            'NIC_ID'      => $info->getAttr('NIC_ID'),
            'CANDATE'     => $info->getAttr('CANDATE'),
            'REVDATE'     => $info->getAttr('REVDATE'),
        ];

        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-年报-主表
    public function getCompanyAr_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyAr::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'ANCHEDATE'  => $item->getAttr('ANCHEDATE'),
//                'ENTNAME' => $item->getAttr('ENTNAME'),
//                'REGNO'   => $item->getAttr('REGNO'),
//                'UNISCID' => $item->getAttr('UNISCID'),
                'LEGAL_PERSON' => $item->getAttr('LEGAL_PERSON'),
                'TEL' => $item->getAttr('TEL'),
                'DOM' => $item->getAttr('DOM'),
                'POSTALCODE' => $item->getAttr('POSTALCODE'),
                'EMAIL' => $item->getAttr('EMAIL'),
                'BUSST' => $item->getAttr('BUSST'),
                'EMPNUM' => $item->getAttr('EMPNUM'),
                'EMPNUMDIS' => $item->getAttr('EMPNUMDIS'),
                'WOMEMPNUM' => $item->getAttr('WOMEMPNUM'),
                'WOMEMPNUMDIS' => $item->getAttr('WOMEMPNUMDIS'),
                'MAINBUSIACT' => $item->getAttr('MAINBUSIACT'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-年报股权变更
    public function getCompanyArAlterstockinfo_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyArAlterstockinfo::create()->where('companyid' , $info->getAttr('companyid'))->all();
        CommonService::getInstance()->log4PHP([$info,$list,$postData], 'info', 'getCompanyArAlterstockinfo_h');

        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'INV'  => $item->getAttr('INV'),
                'TRANSAMPR' => $item->getAttr('TRANSAMPR'),
                'TRANSAMAFT'   => $item->getAttr('TRANSAMAFT'),
                'ALTDATE' => $item->getAttr('ALTDATE'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-年报股权变更
    public function getCompanyArForinvestment_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyArForinvestment::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'ENTNAME'  => $item->getAttr('ENTNAME'),
//                'UNISCID' => $item->getAttr('UNISCID'),
//                'REGNO'   => $item->getAttr('REGNO'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-年报-资产
    public function getCompanyArAsset_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyArForinvestment::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'ENTNAME'  => $item->getAttr('ENTNAME'),
                'UNISCID' => $item->getAttr('UNISCID'),
                'REGNO'   => $item->getAttr('REGNO'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    public function getCompanyArForguaranteeinfo_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyArForguaranteeinfo::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'PEFPER_TERM'  => $item->getAttr('PEFPER_TERM'),
                'RAGE' => $item->getAttr('RAGE'),
                'MORE'   => $item->getAttr('MORE'),
                'PRICLASECKIND'    => $item->getAttr('PRICLASECKIND'),
                'GUARANPERIOD'  => $item->getAttr('GUARANPERIOD'),
                'GATYPE' => $item->getAttr('GATYPE'),
                'MORTGAGOR'   => $item->getAttr('MORTGAGOR'),
                'PRICLASECAM'   => $item->getAttr('PRICLASECAM'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    public function getCompanyArCapital_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyArCapital::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'INV'  => $item->getAttr('INV'),
                'SUBCONAM' => $item->getAttr('SUBCONAM'),
                'SUBCONDATE'   => $item->getAttr('SUBCONDATE'),
                'SUBCONFORM'    => $item->getAttr('SUBCONFORM'),
                'ACCONAM'  => $item->getAttr('ACCONAM'),
                'ACCONDATE' => $item->getAttr('ACCONDATE'),
                'ACCONFORM'   => $item->getAttr('ACCONFORM'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-年报变更信息
    public function getCompanyArModify_h($code,$entName, $page, $pageSize){
        $info = $this->getCompanyId(['entName'=>$entName,'code'=>$code]);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $entName . ',code:'.$code.'）的信息');
        }
        $list = CompanyArModify::create()->where('companyid' , $info->getAttr('companyid'))
            ->limit($this->exprOffset($page, $pageSize), (int)$pageSize)
            ->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'ALTITEM'  => $item->getAttr('ALTITEM'),
                'ALTBE' => $item->getAttr('ALTBE'),
                'ALTAF'   => $item->getAttr('ALTAF'),
                'ALTDATE'    => $item->getAttr('ALTDATE'),

            ];
        }
        return $this->checkResp(200, null, $data, '成功');

    }

    public function getCompanyArSocialfee_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyArSocialfee::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'SO1'    => $item->getAttr('SO1'),
                'SO2'    => $item->getAttr('SO2'),
                'SO3'    => $item->getAttr('SO3'),
                'SO4'    => $item->getAttr('SO4'),
                'SO5'    => $item->getAttr('SO5'),
                'TOTALWAGESSO1'    => $item->getAttr('TOTALWAGESSO1'),
                'TOTALWAGESSO2'    => $item->getAttr('TOTALWAGESSO2'),
                'TOTALWAGESSO3'    => $item->getAttr('TOTALWAGESSO3'),
                'TOTALWAGESSO4'    => $item->getAttr('TOTALWAGESSO4'),
                'TOTALWAGESSO5'    => $item->getAttr('TOTALWAGESSO5'),
                'TOTALPAYMENTSO1'    => $item->getAttr('TOTALPAYMENTSO1'),
                'TOTALPAYMENTSO2'    => $item->getAttr('TOTALPAYMENTSO2'),
                'TOTALPAYMENTSO3'    => $item->getAttr('TOTALPAYMENTSO3'),
                'TOTALPAYMENTSO4'    => $item->getAttr('TOTALPAYMENTSO4'),
                'TOTALPAYMENTSO5'    => $item->getAttr('TOTALPAYMENTSO5'),
                'UNPAIDSOCIALINSSO1'    => $item->getAttr('UNPAIDSOCIALINSSO1'),
                'UNPAIDSOCIALINSSO2'    => $item->getAttr('UNPAIDSOCIALINSSO2'),
                'UNPAIDSOCIALINSSO3'    => $item->getAttr('UNPAIDSOCIALINSSO3'),
                'UNPAIDSOCIALINSSO4'    => $item->getAttr('UNPAIDSOCIALINSSO4'),
                'UNPAIDSOCIALINSSO5'    => $item->getAttr('UNPAIDSOCIALINSSO5'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    public function getCompanyArWebsiteinfo_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyArWebsiteinfo::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ANCHEYEAR'    => $item->getAttr('ANCHEYEAR'),
                'WEBSITNAME'  => $item->getAttr('WEBSITNAME'),
                'DOMAIN' => $item->getAttr('DOMAIN'),
                'WEBTYPE'   => $item->getAttr('WEBTYPE'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-注吊销信息
    public function getCompanyCancelInfo_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyCancelInfo::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ENTNAME'    => $item->getAttr('ENTNAME'),
                'PROVINCE'  => $item->getAttr('PROVINCE'),
                'CANCEL_DATE' => $item->getAttr('CANCEL_DATE'),
                'CANCEL_REASON'   => $item->getAttr('CANCEL_REASON'),
                'REG_CAPITAL'   => $item->getAttr('REG_CAPITAL'),
                'REG_CAPITAL_AMOUNT'   => $item->getAttr('REG_CAPITAL_AMOUNT'),
                'PUBLISH_DATE'   => $item->getAttr('PUBLISH_DATE'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //工商-分支机构
    public function getCompanyFiliation_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyFiliation::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'BRANCH_ID'    => $item->getAttr('BRANCH_ID'),
                'BRANCH_NAME'  => $item->getAttr('BRANCH_NAME'),
                'CAPITAL' => $item->getAttr('CAPITAL'),
                'CAPITALACTL'  => $item->getAttr('CAPITALACTL'),
                'AMOUNT' => $item->getAttr('AMOUNT'),
                'CERTNAME'  => $item->getAttr('CERTNAME'),
                'CERTNO' => $item->getAttr('CERTNO'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //大数据-曾用名表
    public function getCompanyHistoryName_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyHistoryName::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ENTNAME'    => $item->getAttr('ENTNAME'),
                'ALTDATE'  => $item->getAttr('ALTDATE'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }


    //工商-企业股东
    public function getCompanyInv_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyInv::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'INV'      => $item->getAttr('INV'),
                'INVTYPE'  => $item->getAttr('INVTYPE'),
                'BLICNO'   => $item->getAttr('BLICNO'),
                'BLICTYPE' => $item->getAttr('BLICTYPE'),
                'AMOUNT'   => $item->getAttr('AMOUNT'),
                'SUBCONAM' => $item->getAttr('SUBCONAM'),
                'ACCONAM'  => $item->getAttr('ACCONAM'),
                'CONPROP'  => $item->getAttr('CONPROP'),
                'CONDATE'  => $item->getAttr('CONDATE'),
                'CONFORM'  => $item->getAttr('CONFORM'),
                'CURRENCY' => $item->getAttr('CURRENCY'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }
    //工商-企业主要人员
    public function getCompanyManager_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyManager::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'NAME'    => $item->getAttr('NAME'),
                'POSITION'    => $item->getAttr('POSITION'),
                'LEREPSIGN'    => $item->getAttr('LEREPSIGN'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    public function getCompanyId($postData){
        $info = [];
        if(!empty($postData['code'])){
            $info = CompanyBasic::create()->where('UNISCID' , $postData['code'])->get();
        }
        if(empty($info) && !empty($postData['entName'])){
            $info = CompanyBasic::create()->where('ENTNAME' , $postData['entName'])->get();
        }
        return $info;
    }


    //工商-行政许可
    public function getCompanyCertificate_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyCertificate::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ADLIC_NUM'     => $item->getAttr('ADLIC_NUM'),
                'ADLIC_NAME'    => $item->getAttr('ADLIC_NAME'),
                'INDLIC_CLASS'  => $item->getAttr('INDLIC_CLASS'),
                'AUDIT_TYPE'    => $item->getAttr('AUDIT_TYPE'),
                'CONTENT_LIC'   => $item->getAttr('CONTENT_LIC'),
                'FILE_NUM'      => $item->getAttr('FILE_NUM'),
                'LEGAL_PERSON'  => $item->getAttr('LEGAL_PERSON'),
                'VALIDITY_DATE' => $item->getAttr('VALIDITY_DATE'),
                'DECIDE_DATE'   => $item->getAttr('DECIDE_DATE'),
                'END_DATE'      => $item->getAttr('END_DATE'),
                'ADLIC_OFFICE'  => $item->getAttr('ADLIC_OFFICE'),
                'ADLIC_DEP'     => $item->getAttr('ADLIC_DEP'),
                'LOCAL_CODE'    => $item->getAttr('LOCAL_CODE'),
                'ADLIC_STATE'   => $item->getAttr('ADLIC_STATE'),
                'ADLIC_GRADE'   => $item->getAttr('ADLIC_GRADE'),
                'ADLIC_FLAG'    => $item->getAttr('ADLIC_FLAG'),
                'AREA_CODE'     => $item->getAttr('AREA_CODE'),
                'AREA_NAME'     => $item->getAttr('AREA_NAME'),

            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }
    //大数据-股权轨迹表	company_history_inv
    public function getCompanyHistoryInv_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyHistoryInv::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'INV'    => $item->getAttr('INV'),
                'CONAM_before'    => $item->getAttr('CONAM_before'),
                'CONAM_after'    => $item->getAttr('CONAM_after'),
                'CONPROP_before'    => $item->getAttr('CONPROP_before'),
                'CONPROP_after'    => $item->getAttr('CONPROP_after'),
                'TTYPE'    => $item->getAttr('TTYPE'),
                'ATDATE'    => $item->getAttr('ATDATE'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //大数据-历史高管表	company_history_manager
    public function getCompanyHistoryManager_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyHistoryManager::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'NAME'    => $item->getAttr('NAME'),
                'INDATE'    => $item->getAttr('INDATE'),
                'OUTDATE'    => $item->getAttr('OUTDATE'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }

    //getCompanyInvestment_h 工商-对外投资信息	company_investment
    public function getCompanyInvestment_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyInvestment::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ENTNAME_INV'    => $item->getAttr('ENTNAME_INV'),
                'INVESTDATE'    => $item->getAttr('INVESTDATE'),
                'CAPITAL'    => $item->getAttr('CAPITAL'),
                'CAPITALACTL'    => $item->getAttr('CAPITALACTL'),
                'AMOUNT'    => $item->getAttr('AMOUNT'),
                'CERTNAME'    => $item->getAttr('CERTNAME'),
                'CERTNO'    => $item->getAttr('CERTNO'),
                'STAKES_RATIO'    => $item->getAttr('STAKES_RATIO'),
                'REMARKS'    => $item->getAttr('REMARKS'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyIpr_h 工商-知识产权出质	company_ipr
    public function getCompanyIpr_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyIpr::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'id'=> $item->getAttr('id'),
                'IPRNO'    => $item->getAttr('IPRNO'),
                'IPRNAME'    => $item->getAttr('IPRNAME'),
                'IPRTYPE'    => $item->getAttr('IPRTYPE'),
                'PLEDGOR'    => $item->getAttr('PLEDGOR'),
                'IMPORG'    => $item->getAttr('IMPORG'),
                'REGDATE'    => $item->getAttr('REGDATE'),
                'IPRSTATE'    => $item->getAttr('IPRSTATE'),
                'PUBDATE'    => $item->getAttr('PUBDATE'),
                'CANDATE'    => $item->getAttr('CANDATE'),
                'CANREA'    => $item->getAttr('CANREA'),
            ];
        }
        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyIprChange_h 工商-知识产权出质-变更信息	company_ipr_change
    public function getCompanyIprChange_h($id){
        $list = CompanyIprChange::create()->where('pid' , $id)->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ALTITEM'    => $item->getAttr('ALTITEM'),
                'ALTBE'    => $item->getAttr('ALTBE'),
                'ALTAF'    => $item->getAttr('ALTAF'),
                'ALTDATE'    => $item->getAttr('ALTDATE'),
            ];

        }
        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyLiquidation_h 工商-清算信息	company_liquidation
    public function getCompanyLiquidation_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyLiquidation::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'PUBLISH_DATE'    => $item->getAttr('PUBLISH_DATE'),
                'LIQMEN'    => $item->getAttr('LIQMEN'),
                'LIGPRINCIPAL'    => $item->getAttr('LIGPRINCIPAL'),
            ];
        }

        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyModify_h 工商-公司变更信息	company_modify
    public function getCompanyModify_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyModify::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'ALTDATE'    => $item->getAttr('ALTDATE'),
                'ALTITEM'    => $item->getAttr('ALTITEM'),
                'ALTBE'    => $item->getAttr('ALTBE'),
                'ALTAF'    => $item->getAttr('ALTAF'),
            ];
        }

        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyMort_h 工商-动产抵押	company_mort
    public function getCompanyMort_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyMort::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'REG_NUM'    => $item->getAttr('REG_NUM'),
                'REG_DATE'    => $item->getAttr('REG_DATE'),
                'PUBLISH_DATE'    => $item->getAttr('PUBLISH_DATE'),
                'STATUS'    => $item->getAttr('STATUS'),
                'REG_DEPARTMENT'    => $item->getAttr('REG_DEPARTMENT'),
                'TYPE'    => $item->getAttr('TYPE'),
                'AMOUNT'    => $item->getAttr('AMOUNT'),
                'TERM'    => $item->getAttr('TERM'),
                'SCOPE'    => $item->getAttr('SCOPE'),
                'REMARK'    => $item->getAttr('REMARK'),
                'OVERVIEW_TYPE'    => $item->getAttr('OVERVIEW_TYPE'),
                'OVERVIEW_AMOUNT'    => $item->getAttr('OVERVIEW_AMOUNT'),
                'OVERVIEW_SCOPE'    => $item->getAttr('OVERVIEW_SCOPE'),
                'OVERVIEW_TERM'    => $item->getAttr('OVERVIEW_TERM'),
                'OVERVIEW_REMARK'    => $item->getAttr('OVERVIEW_REMARK'),
                'CANCEL_DATE'    => $item->getAttr('CANCEL_DATE'),
                'CANCEL_REASON'    => $item->getAttr('CANCEL_REASON'),
            ];
        }

        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyMortChange_h 工商-动产抵押-变更信息	company_mort_change
    public function getCompanyMortChange_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyMortChange::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'COMPANY_MORTGAGE_ID'    => $item->getAttr('COMPANY_MORTGAGE_ID'),
                'COMPANY_NAME'    => $item->getAttr('COMPANY_NAME'),
                'CHANGE_DATE'    => $item->getAttr('CHANGE_DATE'),
                'CHANGE_CONTENT'    => $item->getAttr('CHANGE_CONTENT'),
            ];
        }

        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyMortPawn_p 工商-动产抵押-抵押物信息	company_mort_pawn
    public function getCompanyMortPawn_p($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyMortPawn::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'PAWN_NAME'    => $item->getAttr('PAWN_NAME'),
//                'OWNERSHIP_ID'    => $item->getAttr('OWNERSHIP_ID'),
                'OWNERSHIP_NAME'    => $item->getAttr('OWNERSHIP_NAME'),
                'DETAIL'    => $item->getAttr('DETAIL'),
            ];
        }

        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyMortPeople_h 工商-动产抵押-抵押权人信息	company_mort_people
    public function getCompanyMortPeople_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyMortPeople::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
//                'PLEDGEE_ID'    => $item->getAttr('PLEDGEE_ID'),
                'PLEDGEE_NAME'    => $item->getAttr('PLEDGEE_NAME'),
                'LICENSE_TYPE'    => $item->getAttr('LICENSE_TYPE'),
                'LICENSE_NUM'    => $item->getAttr('LICENSE_NUM'),
            ];
        }

        return $this->checkResp(200, null, $data, '成功');
    }
    //getCompanyStockImpawn_h 工商-股权质押	company_stock_impawn
    public function getCompanyStockImpawn_h($postData){
        $info = $this->getCompanyId($postData);
        if (empty($info)) {
            return $this->checkResp(203, null, [], '没有查询到这个企业（entName:' . $postData['entName'] . ',code:'.$postData['code'].'）的信息');
        }
        $list = CompanyStockImpawn::create()->where('companyid' , $info->getAttr('companyid'))->all();
        $data = [];
        foreach ($list as $key => $item) {
            $data[$key] = [
                'PLEDGOR'    => $item->getAttr('PLEDGOR'),
                'IMPORG'    => $item->getAttr('IMPORG'),
                'IMPORGTYPE'    => $item->getAttr('IMPORGTYPE'),
                'IMPAM'    => $item->getAttr('IMPAM'),
                'IMPONRECDATE'    => $item->getAttr('IMPONRECDATE'),
                'IMPEXAEEP'    => $item->getAttr('IMPEXAEEP'),
                'IMPSANDATE'    => $item->getAttr('IMPSANDATE'),
                'IMPTO'    => $item->getAttr('IMPTO'),
                'RELATEDCOMPANY'    => $item->getAttr('RELATEDCOMPANY'),
                'EXESTATE'    => $item->getAttr('EXESTATE'),
                'CHANGESITU'    => $item->getAttr('CHANGESITU'),
                'CANDATE'    => $item->getAttr('CANDATE'),
                'CANREASON'    => $item->getAttr('CANREASON'),
            ];
        }

        return $this->checkResp(200, null, $data, '成功');
    }
}
