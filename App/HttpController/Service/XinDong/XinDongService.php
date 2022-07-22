<?php

namespace App\HttpController\Service\XinDong;

use App\Csp\Service\CspService;
use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Models\Api\CarInsuranceInfo;
use App\HttpController\Models\Api\CompanyCarInsuranceStatusInfo;
use App\HttpController\Models\Api\CompanyName;
use App\HttpController\Models\Api\UserSearchHistory;
use App\HttpController\Models\BusinessBase\VendincScale2020Model;
use App\HttpController\Models\EntDb\EntDbNacao;
use App\HttpController\Models\EntDb\EntDbNacaoBasic;
use App\HttpController\Models\EntDb\EntDbNacaoClass;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;
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
    public $company_org_type_youxian_des =  '有限责任公司'; 
    public $company_org_type_youxian2 = 15;
    public $company_org_type_youxian2_des =  '有限公司'; 

    public $company_org_type_gufen = 20;
    public $company_org_type_gufen_des =  '股份有限公司'; 

    public $company_org_type_fengongsi = 25;
    public $company_org_type_fengongsi_des =  '分公司'; 
    public $company_org_type_zongsongsi = 30;
    public $company_org_type_zongsongsi_des =  '总公司'; 

    public $company_org_type_youxianhehuo = 35;
    public $company_org_type_youxianhehuo_des =  '有限合伙企业'; 

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
           $this->reg_capital_50  =>  $this->reg_capital_50_des,
           $this->reg_capital_50to100  =>  $this->reg_capital_50to100_des, 
           $this->reg_capital_100to200  =>  $this->reg_capital_100to200_des,  
           $this->reg_capital_200to500  =>  $this->reg_capital_200to500_des,  
           $this->reg_capital_500to1000  =>  $this->reg_capital_500to1000_des,  
           $this->reg_capital_1000to10000  =>  $this->reg_capital_1000to10000_des,  
        //    $this->reg_capital_10000to100000  =>  $this->reg_capital_10000to100000_des,
           $this->reg_capital_minddle_a  =>  $this->reg_capital_minddle_a_des,
           $this->reg_capital_big_c  =>  $this->reg_capital_big_c_des,
           $this->reg_capital_big_b  =>  $this->reg_capital_big_b_des,
           $this->reg_capital_big_A  =>  $this->reg_capital_big_A_des,
           $this->reg_capital_super_big_C  =>  $this->reg_capital_super_big_C_des,
           $this->reg_capital_super_big_B  =>  $this->reg_capital_super_big_B_des,
           $this->reg_capital_super_big_A  =>  $this->reg_capital_super_big_A_des,
       ];

       if ($getAll) {
           return array_merge($map,[0 => '全部']);
       }
       return $map;
    } 

    // 获取营业状态
    function getRegStatus($getAll = false) 
    {
       $map = [
           $this->reg_status_cunxu  =>  $this->reg_status_cunxu_des,
           $this->reg_status_zaiye  =>  $this->reg_status_zaiye_des,
           $this->reg_status_diaoxiao  =>  $this->reg_status_diaoxiao_des,
           $this->reg_status_zhuxiao  =>  $this->reg_status_zhuxiao_des,
           $this->reg_status_tingye  =>  $this->reg_status_tingye_des,
       ];

       if ($getAll) {
           return array_merge($map,[0 => '全部']);
       }
       return $map;

    } 

    // 获取企业成立年限
    function getEstiblishYear($getAll = false) 
    {
       $map = [
           $this->estiblish_year_under_2 => $this->estiblish_year_under_2_des,
           $this->estiblish_year_2to5  => $this->estiblish_year_2to5_des,
           $this->estiblish_year_5to10  => $this->estiblish_year_5to10_des,
           $this->estiblish_year_10to15  => $this->estiblish_year_10to15_des,
           $this->estiblish_year_15to20  => $this->estiblish_year_15to20_des, 
           $this->estiblish_year_more_than_20  => $this->estiblish_year_more_than_20_des,
       ];

       if ($getAll) {
           return array_merge($map,[0 => '全部']);
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
            return array_merge($map,[0 => '全部']);
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

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'BusinessStateV4/SearchCompanyFinancings', $postData);

            if (empty($res['result'])) return null;

            foreach ($res['result'] as $one) {
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

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);

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

                    $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandPublish/LandPublishList', $postData);

                    if ($res['code'] != 200 || empty($res['result'])) break;

                    foreach ($res['result'] as $one) {
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

                    foreach ($res['result'] as $one) {
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

                    $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandTransfer/LandTransferList', $postData);

                    if ($res['code'] != 200 || empty($res['result'])) break;

                    foreach ($res['result'] as $one) {
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
        $res['PatentSearch'] > 0 ? $said = "共有{$res['PatentSearch']}个专利，具体登录 信动智调 查看" : $said = "共有{$res['PatentSearch']}个专利";
        array_push($tmp, $said);
        $res['GetAdministrativeLicenseList'] > 0 ? $said = "共有{$res['GetAdministrativeLicenseList']}个行政许可，具体登录 信动智调 查看" : $said = "共有{$res['GetAdministrativeLicenseList']}个行政许可";
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
            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandPublish/LandPublishList', $postData);
            return empty($res['paging']) ? 0 : $res['paging']['total'];
        });

        //龙盾 土地转让
        $csp->add('LandTransferList', function () use ($entName) {
            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 5,
            ];
            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'LandTransfer/LandTransferList', $postData);
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
        
        return  [
           [
                'pid' => 10,
                'desc' => '企业类型',
                'detail' => '',
                'key' => 'company_org_type',
                'type' => 'select',
                'data' =>  [
                    $this->company_org_type_youxian => [
                        'cname' =>$this->company_org_type_youxian_des, 
                        'detail' => '',
                    ],
                    $this->company_org_type_youxian2 => [
                        'cname' => $this->company_org_type_youxian2_des,
                        'detail' => '',
                    ],
                    $this->company_org_type_gufen => [
                        'cname' =>  $this->company_org_type_gufen_des,
                        'detail' => '',
                    ],
                    $this->company_org_type_fengongsi => [
                        'cname' => $this->company_org_type_fengongsi_des,
                        'detail' => '',
                    ],
                    $this->company_org_type_zongsongsi => [
                        'cname' => $this->company_org_type_zongsongsi_des,
                        'detail' => '',
                    ],
                    $this->company_org_type_youxianhehuo => [
                        'cname' => $this->company_org_type_youxianhehuo_des,
                        'detail' => '',
                    ], 
                    40 => [
                        'cname' =>  '外商独资公司',
                        'detail' => '',
                    ], 
                    50 =>  [
                        'cname' =>  '个人独资企业',
                        'detail' => '',
                    ],  
                    60 =>  [
                        'cname' =>  '国有独资公司',
                        'detail' => '',
                    ],  
                ],
            ], 
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
                    $this->estiblish_year_2to5  => [
                        'cname' => $this->estiblish_year_2to5_des ,
                        'detail' => '',
                        'min' => 2,
                        'max' => 5,
                    ],
                    $this->estiblish_year_5to10  => [
                        'cname' => $this->estiblish_year_5to10_des,
                        'detail' => '',
                        'min' => 5,
                        'max' => 10,
                    ],
                    $this->estiblish_year_10to15  => [
                        'cname' =>  $this->estiblish_year_10to15_des,
                        'detail' => '',
                        'min' => 10,
                        'max' => 15,
                    ],
                    $this->estiblish_year_15to20  => [
                        'cname' => $this->estiblish_year_15to20_des,
                        'detail' => '',
                        'min' => 15,
                        'max' => 20,
                    ], 
                    $this->estiblish_year_more_than_20  => [
                        'cname' => $this->estiblish_year_more_than_20_des,
                        'detail' => '',
                        'min' => 20,
                        'max' => 2000,
                    ],
                ],
            ], 
             [
                'pid' => 30,
                'desc' => '营业状态',
                'detail' => '',
                'key' => 'reg_status',
                'type' => 'select',
                'data' => [
                    $this->reg_status_cunxu  =>  [
                        'cname' => $this->reg_status_cunxu_des,
                        'detail' => '',
                    ],
                    $this->reg_status_zaiye  =>  [
                        'cname' => $this->reg_status_zaiye_des,
                        'detail' => '',
                    ],
                    $this->reg_status_diaoxiao  =>  [
                        'cname' => $this->reg_status_diaoxiao_des,
                        'detail' => '',
                    ],
                    $this->reg_status_zhuxiao  =>  [
                        'cname' => $this->reg_status_zhuxiao_des,
                        'detail' => '',
                    ],
                    $this->reg_status_tingye  => [
                        'cname' => $this->reg_status_tingye_des,
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
                'data' =>  [
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
                        'cname' =>  '500-1000万',
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
                        'cname' =>  '5000万-1亿',
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
            [
                'pid' => 50,
                'desc' => '营收规模',
                'key' => 'ying_shou_gui_mo',
                'detail' => '',
                'type' => 'select',
                'data' => [
                    $this->reg_capital_50  =>  [
                        'cname' => $this->reg_capital_50_des,
                        'detail' => '100万以下',
                        'min' => 0,
                        'max' => 1000000,
                    ],
                    $this->reg_capital_50to100  =>  [
                        'cname' => $this->reg_capital_50to100_des,
                        'detail' => '100万以上，500万以下',
                        'min' => 1000000,
                        'max' => 5000000,
                    ], 
                    $this->reg_capital_100to200  =>  [
                        'cname' => $this->reg_capital_100to200_des,
                        'detail' => '500万以上，1000万以下',
                        'min' => 5000000,
                        'max' => 10000000,
                    ],  
                    $this->reg_capital_200to500  =>  [
                        'cname' => $this->reg_capital_200to500_des,
                        'detail' => '1000万以上，3000万以下',
                        'min' => 10000000,
                        'max' => 30000000,
                    ],  
                    $this->reg_capital_500to1000  =>  [
                        'cname' => $this->reg_capital_500to1000_des,
                        'detail' => '3000万以上，5000万以下',
                        'min' => 30000000,
                        'max' => 50000000,
                    ],  
                    $this->reg_capital_1000to10000  => [
                        'cname' =>  $this->reg_capital_1000to10000_des,
                        'detail' => '5000万以上，8000万以下',
                        'min' => 50000000,
                        'max' => 80000000,
                    ],  
                 //    $this->reg_capital_10000to100000  =>  $this->reg_capital_10000to100000_des,
                    $this->reg_capital_minddle_a  =>  [
                        'cname' => $this->reg_capital_minddle_a_des,
                        'detail' => '8000万以上，1亿以下',
                        'min' => 80000000,
                        'max' => 100000000,
                    ],
                    $this->reg_capital_big_c  =>  [
                        'cname' => $this->reg_capital_big_c_des,
                        'detail' => '1亿以上，5亿以下',
                        'min' => 100000000,
                        'max' => 500000000,
                    ],
                    $this->reg_capital_big_b  =>  [
                        'cname' => $this->reg_capital_big_b_des,
                        'detail' => '5亿以上，10亿以下',
                        'min' => 500000000,
                        'max' => 1000000000,
                    ],
                    $this->reg_capital_big_A  =>  [
                        'cname' => $this->reg_capital_big_A_des,
                        'detail' => '10亿以上，50亿以下',
                        'min' => 1000000000,
                        'max' => 5000000000,
                    ],
                    $this->reg_capital_super_big_C  =>  [
                        'cname' => $this->reg_capital_super_big_C_des,
                        'detail' => '50亿以上，100亿以下',
                        'min' => 5000000000,
                        'max' => 10000000000,
                    ],
                    $this->reg_capital_super_big_B  =>  [
                        'cname' => $this->reg_capital_super_big_B_des,
                        'detail' => '100亿以上，500亿以下',
                        'min' => 10000000000,
                        'max' => 50000000000,
                    ],
                    $this->reg_capital_super_big_A  =>  [
                        'cname' => $this->reg_capital_super_big_A_des,
                        'detail' => '500亿以上',
                        'min' => 50000000000,
                        'max' => 500000000000,
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
                'pid' => 90,
                'desc' => '是否物流企业',
                'detail' => '',
                'key' => 'wu_liu_xin_xi',
                'type' => 'select',
                'data' => [
                    10 => [
                        'cname' => '是',
                        'detail' => '',
                    ],  
                ],
            ],
        ];
    }


     //高级搜索
     function advancedSearch($elasticSearchService, $index = 'company_202207')
     { 
        $elasticsearch = new ElasticSearch(
            new  Config([
                'host' => "es-cn-7mz2m3tqe000cxkfn.public.elasticsearch.aliyuncs.com",
                'port' => 9200,
                'username'=>'elastic',
                'password'=>'zbxlbj@2018*()',
            ])
        ); 
        $bean = new  Search();
        $bean->setIndex($index);
        $bean->setPreference("_primary");
        $bean->setType('_doc');
        $bean->setBody($elasticSearchService->query);
        $response = $elasticsearch->client()->search($bean)->getBody(); 
        CommonService::getInstance()->log4PHP(json_encode(['es_query'=>$elasticSearchService->query]));
        return  $response;
     } 

     function saveSearchHistory($userId,  $postDataStr, $canme = ''){
        return UserSearchHistory::create()->data([
            'userId' => $userId, 
            'post_data' => $postDataStr,
            'query' => $canme,
        ])->save();
     }
     
     static function formatEsDate($dataArr, $fieldsArr){
        foreach($dataArr as &$dataItem){
            foreach($fieldsArr as $field){
                if($dataItem['_source'][$field] == '0000-00-00 00:00:00'){
                    $dataItem['_source'][$field] = '--';
                    continue;
                } 
                $tmpArr = explode(' ', $dataItem['_source'][$field]);
                $dataItem['_source'][$field] = $tmpArr[0];
 
            }
        }

        return $dataArr;
     }

     static function formatObjDate($dataObj, $fieldsArr){
        foreach($fieldsArr as $field){
            if($dataObj->$field  == '0000-00-00 00:00:00'){
                $dataObj->$field =  '--';
                continue;
            }
            $tmpArr = explode(' ', $dataObj->$field);
            $dataObj->$field = $tmpArr[0];
        } 

        return $dataObj;
     }

     static function formatObjMoney($dataObj, $fieldsArr){
        foreach($fieldsArr as $field){
            if($dataObj->$field>0){
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

     static function formatEsMoney($dataArr, $fieldsArr){
        foreach($dataArr as &$dataItem){
            foreach($fieldsArr as $field){
                if($dataItem['_source'][$field]<=0){
                    continue;
                }

                // 不包含
                if(strpos($dataItem['_source'][$field],'.') === false){
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

     static function replaceBetween($str, $needle_start, $needle_end, $replacement) {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);
    
        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;
    
        return substr_replace($str, $replacement, $start, $end - $start);
    }

    static function mapYingShouGuiMo(): array
    { 

        return  [
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

    static function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
     }

    static function getAllTagesByData($dataItem){
        // 标签
        $tags = [];

        // 营收规模  
        if($dataItem['ying_shou_gui_mo']){
            $yingShouGuiMoTag = (new XinDongService())::getYingShouGuiMoTag(
                $dataItem['ying_shou_gui_mo']
            );
            $yingShouGuiMoTag && $tags[50] = $yingShouGuiMoTag;
        } 

        // 团队规模
        if($dataItem['tuan_dui_ren_shu']){
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
        if($dataItem['market_share']){
            $tags[130] = $dataItem['market_share']['ent_market_share']['bottom'].'~'.
                $dataItem['market_share']['ent_market_share']['top'];
        }

        return $tags;
    }

    static function getShangPinTag($companyName):string
    {
        if(
            self::checkIfIsShangPinCompany($companyName)
        ){
            return '商品';
        }

        return ""; 
    }
    static function checkIfIsShangPinCompany($companyName):bool
    {
       return  \App\HttpController\Models\RDS3\ShangPinTiaoMaJieBa::create()
                ->where('entname', $companyName) 
                ->get() ?true : false;
    }

    static function getJinChuKouTag($companyId):string
    {
        if(
            self::checkIfJinChuKou($companyId)
        ){
            return '进出口型企业';
        }

        return ""; 
    }
    static function checkIfJinChuKou($companyId):bool
    {
       return  \App\HttpController\Models\RDS3\HgGoods::create()
                ->where('xd_id', $companyId) 
                ->get() ?true : false;
    }

    static function getShangShiTag($companyId):string
    {
        if(
            self::checkIfShangeShi($companyId)
        ){
            return '上市公司';
        }

        return ""; 
    }

    static function checkIfShangeShi($companyId):bool
    {
       return  \App\HttpController\Models\RDS3\XdAggreListedFinance::create()
                ->where('xd_id', $companyId) 
                ->get() ?true : false;
    }

    static function getIsoTag($companyId):string
    {
        if(
            self::checkIfHasIso($companyId)
        ){
            return 'ISO';
        }

        return ""; 
    }

    static function getHighTecTag($companyId):string
    {
        if(
            self::checkIfIsHighTec($companyId)
        ){
            return '高新技术';
        }

        return ""; 
    }

    static function getDengLingTag($companyId):string
    {
        if(
            self::checkIfHasDengLing($companyId)
        ){
            return '瞪羚';
        }

        return ""; 
    }

    static function checkIfIsHighTec($companyId):bool
    {
       return  \App\HttpController\Models\RDS3\XdHighTec::create()
                ->where('xd_id', $companyId)
                ->where('dateto', date('Y-m-d'), '>')
                ->get() ?true : false;
    }

    static function checkIfHasIso($companyId):bool
    {
        return  \App\HttpController\Models\RDS3\XdDlRzGlTx::create()
                ->where('xd_id', $companyId)->get() ?true : false;
    }

    static function checkIfHasDengLing($companyId):bool
    {
        return  \App\HttpController\Models\RDS3\XdDl::create()
                ->where('xd_id', $companyId)->get() ?true : false;
    }

    static function getTuanDuiGuiMoTag($nums){ 
        $map = self::getTuanDuiGuiMoMap();
        foreach($map as $item){
            if(
                $item['min'] <= $nums &&
                $item['max'] >= $nums 
            ){
                return $item['des'];
            }
        }
   }
    static function getTuanDuiGuiMoMap(){ 
         return  [
            10 => ['min' => 0, 'max' => 10 , 'epreg' => ['[0-9]'], 'des' => '10人以下' ],//, 
            20 => ['min' => 10, 'max' => 50 , 'epreg' => ['[1-4][0-9]'], 'des' => '10-50人' ], //, 
            30 => ['min' => 50, 'max' => 100  ,'epreg' => ['[5-9][0-9]'], 'des' => '50-100人' ], //, 
            40 => ['min' => 100, 'max' => 500 ,'epreg' => ['[1-4][0-9][0-9]'], 'des' => '100-500人'  ], //, 
            50 => ['min' => 500, 'max' => 1000  ,'epreg' => ['[5-9][0-9][0-9]'], 'des' => '500-1000人' ], //, 
            60 => ['min' => 1000, 'max' => 5000  ,'epreg' => ['[1-4][0-9][0-9][0-9]'], 'des' => '1000-5000人' ], //, 
            70 => ['min' => 5000, 'max' => 10000000 ,'epreg' => 
            ['[5-9][0-9][0-9][0-9]','[1-9][0-9][0-9][0-9][0-9]','[1-9][0-9][0-9][0-9][0-9][0-9]'], 
            'des' => '5000人以上' ]//, 
        ];
    }

    static function getYingShouGuiMoMapV2(){
        return  [
            'A1' => ['min' => 0,'max' => 49],
            'A2' => ['min' => 50,'max' => 99],
            'A3' => ['min' => 100,'max' => 299],
            'A4' => ['min' => 299,'max' => 500],
            'A5' => ['min' => 500,'max' => 999],
            'A6' => ['min' => 1000,'max' => 1999],
            'A7' => ['min' => 2000,'max' => 2999],
            'A8' => ['min' => 3000,'max' => 3999],
            'A9' => ['min' => 4000,'max' => 4999],
            'A10' => ['min' => 5000,'max' => 5999],
            'A11' => ['min' => 6000,'max' => 6999],
            'A12' => ['min' => 7000,'max' => 7999],
            'A13' => ['min' => 8000,'max' => 8999],
            'A14' => ['min' => 9000,'max' => 9999],
            'A15' => ['min' => 10000,'max' => 19999],
            'A16' => ['min' => 20000,'max' => 29999],
            'A17' => ['min' => 30000,'max' => 39999],
            'A18' => ['min' => 40000,'max' => 49999],
            'A19' => ['min' => 50000,'max' => 59999],
            'A20' => ['min' => 60000,'max' => 69999],
            'A21' => ['min' => 70000,'max' => 79999],
            'A22' => ['min' => 80000,'max' => 89999],
            'A23' => ['min' => 90000,'max' => 99999],
            'A24' => ['min' => 100000,'max' => 199999],
            'A25' => ['min' => 200000,'max' => 299999],
            'A26' => ['min' => 300000,'max' => 399999],
            'A27' => ['min' => 400000,'max' => 499999],
            'A28' => ['min' => 500000,'max' => 599999],
            'A29' => ['min' => 600000,'max' => 699999],
            'A30' => ['min' => 700000,'max' => 799999],
            'A31' => ['min' => 800000,'max' => 899999],
            'A32' => ['min' => 900000,'max' => 999999],
            'A33' => ['min' => 1000000,'max' => 1999999],
            'A34' => ['min' => 2000000,'max' => 2999999],
            'A35' => ['min' => 3000000,'max' => 3999999],
            'A36' => ['min' => 4000000,'max' => 4999999],
            'A37' => ['min' => 5000000,'max' => 5999999],
            'A38' => ['min' => 6000000,'max' => 6999999],
            'A39' => ['min' => 7000000,'max' => 7999999],
            'A40' => ['min' => 8000000,'max' => 8999999],
            'A41' => ['min' => 9000000,'max' => 9999999],
            'A42' => ['min' => 10000000,'max' => 99999999],
        ];
    }

    static function getZhuCeZiBenMap(){ 
        return  [
            10 => ['min' => 0, 'max' => 100 , 'epreg' => ['[0-9]','[1-9][0-9]'], 'des' => '100万以下' ],//,
            15 => ['min' => 10, 'max' => 500 , 'epreg' => ['[1-4][0-9][0-9]'], 'des' => '100-500' ], //, 
            20 => ['min' => 500, 'max' => 1000  ,'epreg' => ['[5-9][0-9][0-9]'], 'des' => '500-1000' ], //, 
            25 => ['min' => 1000, 'max' => 5000 ,'epreg' => ['[1-4][0-9][0-9][0-9]'], 'des' => '1000-5000'  ], //, 
            30 => ['min' => 5000, 'max' => 10000  ,'epreg' => ['[5-9][0-9][0-9][0-9]'], 'des' => '5000-10000' ], //, 
            35 => ['min' => 10000, 'max' => 50000  ,'epreg' => ['[1-9][0-9][0-9][0-9][0-9]'], 'des' => '100000-100000' ], //, 
            40 => ['min' => 5000, 'max' => 10000000 ,'epreg' => 
            ['[1-9][0-9][0-9][0-9][0-9]','[1-9][0-9][0-9][0-9][0-9][0-9]','[1-9][0-9][0-9][0-9][0-9][0-9][0-9]'], 
            'des' => '100000+' ]//, 
        ];
   }
    static function getZhuCeZiBenMapV2(){
        return  [
            10 => ['min' => 0, 'max' => 100 , 'epreg' => ['[0-9]','[1-9][0-9](\\.).+'], 'des' => '100万以下' ],//,
            15 => ['min' => 10, 'max' => 500 , 'epreg' => ['[1-4][0-9][0-9](\\.).+'], 'des' => '100-500' ], //,
            20 => ['min' => 500, 'max' => 1000  ,'epreg' => ['[5-9][0-9][0-9](\\.).+'], 'des' => '500-1000' ], //,
            25 => ['min' => 1000, 'max' => 5000 ,'epreg' => ['[1-4][0-9][0-9][0-9](\\.).+'], 'des' => '1000-5000'  ], //,
            30 => ['min' => 5000, 'max' => 10000  ,'epreg' => ['[5-9][0-9][0-9][0-9](\\.).+'], 'des' => '5000-10000' ], //,
            35 => ['min' => 10000, 'max' => 50000  ,'epreg' => ['[1-9][0-9][0-9][0-9][0-9](\\.).+'], 'des' => '100000-100000' ], //,
            40 => ['min' => 5000, 'max' => 10000000 ,'epreg' =>
                ['[1-9][0-9][0-9][0-9][0-9]','[1-9][0-9][0-9][0-9][0-9][0-9]','[1-9][0-9][0-9][0-9][0-9][0-9][0-9](\\.).+'],
                'des' => '100000+' ]//,
        ];
    }




    // 获取所有曾用名称 $getAll: 为true的时候  当前的名字也要了
    static function getAllUsedNames($dataArr, $getAll = false){
        if ($getAll) {
            $allNames = [ $dataArr['name'] => $dataArr['name']];    
        }
        else{
            $allNames = [ ];    
        }     
        $newNames = self::autoSearchNewNames($dataArr);
        $oldNames = self::autoSearchOldNames($dataArr);
        return array_values(array_merge($allNames, $newNames, $oldNames));
    }

    //往后找到最新的names
    static function autoSearchNewNames($dataArr){  
        $names = [];
        // 容错次数
        $nums = 1;
        while($dataArr['property2']>0) {
            if($nums>=20){
                break;
            }
            $retData  =\App\HttpController\Models\RDS3\Company::create()
                ->field(['id','name','property2'])
                ->where('id', $dataArr['property2'])
                ->get();
            if($retData){
                $dataArr = [
                    'id' => $retData->id,
                    'name' => $retData->name,
                    'property2' => $retData->property2,
                ]; 
                $names[$dataArr['name']] = $dataArr['name'];
            }
            else{
                $dataArr = [
                    'id' => 0,
                    'name' => 0,
                    'property2' => 0,
                ]; 
            }
           $nums ++;
        }
        
       return $names;
    }

    //往前找到旧的names
    static function autoSearchOldNames($dataArr){ 
        $names = [];
        // 容错次数
        $nums = 1;
        while($dataArr['id']>0) {
            if($nums>=20){
                break;
            }
            $retData  =\App\HttpController\Models\RDS3\Company::create()
                ->field(['id','name','property2'])
                ->where('property2', $dataArr['id'])
                ->get();
            if($retData){
                $dataArr = [
                    'id' => $retData->id,
                    'name' => $retData->name,
                    'property2' => $retData->property2,
                ];
                $names[$dataArr['name']] = $dataArr['name'];
            }
            else{
                $dataArr = [
                    'id' => 0,
                    'name' => 0,
                    'property2' => 0,
                ];
            }
            $nums ++; 
        } 
       return $names;
    }

     static function saveOpportunity($dataItem){
        if (
            UserBusinessOpportunity::create()->where([
                'userId' => $dataItem['userId'], 
                'name' => $dataItem['name'], 
            ])->get()
        ) {
            CommonService::getInstance()->log4PHP('该商机已经存在于客户池 '.json_encode(
                [
                    'userId' => $dataItem['userId'], 
                    'name' => $dataItem['name'], 
                ]
            ));
            return true ;
        }

        try {
            $res = UserBusinessOpportunity::create()->data([
                        'userId' => $dataItem['userId'], 
                        'name' => $dataItem['name'],
                        'code' =>  $dataItem['code'], 
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
        $retData  = Company::create()
            ->where('name', array_values($entNames),'IN')
            ->field(["id", "name", "company_org_type","reg_location","estiblish_time"])
            ->get(); 
         
        return [
            'code' => 200,
            'paging' => [],
            'msg' =>  '成功',
            'result' => $retData,
        ];
    } 
 

    function matchAainstEntName(
        $str, 
        $mode = " IN NATURAL LANGUAGE MODE " , 
        $companyName = "company_name_0",
        $field = "id,name",
        $limit = 1
    ){
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
        
        CommonService::getInstance()->log4PHP('matchAainstComName sql'.$sql ); 
        CommonService::getInstance()->log4PHP('matchAainstComName res'.json_encode($list) );
         
        return $list;

        // return [
        //     'sql' => $sql,
        //     'list' => $list,
        // ];
    }
 
    function splitChineseNameForMatchAgainst($entName): ?string
    {
        
        
        $arr = preg_split('/(?<!^)(?!$)/u', $entName );
        $matchStr = "";
        if($arr[0] && $arr[1]){
            $matchStr .= '+'.$arr[0].$arr[1];
        }
        if($arr[2] && $arr[3]){
            $matchStr .= '+'.$arr[2].$arr[3];
        }
        if($arr[4] && $arr[5]){
            $matchStr .= '+'.$arr[4].$arr[5];
        }
        if($arr[6] && $arr[7]){
            $matchStr .= '+'.$arr[6].$arr[7];
        }
        if($arr[8] && $arr[9]){
            $matchStr .= '+'.$arr[8].$arr[9];
        }
        
        return  $matchStr;
    }

    function matchEntByNameMatchByBooleanMode($csp,$entName){
        foreach(
            CompanyName::getAllTables() as $tableName
        ){
            $csp->add('BOOLEAN_MODE_'.$tableName, function () use ($entName, $tableName) {
                $timeStart2 = microtime(true); 
                $matchStr = (new XinDongService())->splitChineseNameForMatchAgainst($entName);
                $retData =  (new XinDongService())
                            ->matchAainstEntName(
                                $matchStr,
                                " IN BOOLEAN MODE ", 
                                $tableName ,
                                'id,name',
                                3
                        );  
                $timeEnd2 = microtime(true); 
                $execution_time11 = ($timeEnd2 - $timeStart2);  
                return  [ 
                    'data' => $retData,
                    'type' => 'Boolean',
                    'time' => $execution_time11
                ];
            }); 
        }
    }

    function matchEntByNameMatchByLanguageMode($csp,$entName){
        foreach(
            CompanyName::getAllTables() as $tableName
        ){
            $csp->add('NATURAL_LANGUAGE_MODE_'.$tableName, function () use ($entName, $tableName) {
                $timeStart2 = microtime(true);
                $retData =  (new XinDongService())
                    ->matchAainstEntName(
                        $entName,
                        " IN NATURAL LANGUAGE MODE  " ,
                        $tableName,
                        'id,name',
                        3
                    );
                $timeEnd2 = microtime(true);
                $execution_time11 = ($timeEnd2 - $timeStart2);
                return  [
                    'data' => $retData,
                    'type' => 'Language',
                    'time' => $execution_time11
                ];
            });
        }
    }

    function matchEntByNameEqualMatchByName($csp,$entName){
        $csp->add('company_match', function () use ($entName) { 
            $timeStart2 = microtime(true);  
            $sql = "SELECT  id,`name` FROM  `company`  WHERE   `name` = '$entName' LIMIT 1"; 
            $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
            $timeEnd2 = microtime(true); 
            $execution_time11 = ($timeEnd2 - $timeStart2); 
            return  [
                'data' => $list,
                'type' => 'equal',
                'time' => $execution_time11
            ];
        }); 
    }

    function matchEntByNameMatchByEs($entName,$size = 4, $page = 1){
        $ElasticSearchService = new ElasticSearchService();  
        $ElasticSearchService->addMustMatchQuery('name', $entName) ;   
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize($size) ;
        $ElasticSearchService->addFrom($offset) ;
        // $ElasticSearchService->addSort('xd_id', 'desc') ;
 
        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true);  
        // CommonService::getInstance()->log4PHP('matchEntByNameMatchByEs '.
        //     $responseJson
        // ); 
        $datas = [];
        foreach($responseArr['hits']['hits'] as $item){
            $datas[] = [
                'id' => $item['_source']['xd_id'],
                'name' => $item['_source']['name'],
            ]; 
        }
        return $datas;
    }

    function getSimilarPercent($var_1, $var_2){
        similar_text($var_1, $var_2, $percent);
        return number_format($percent);
    }
 

    function checkIfSimilar($name1,$name2){
        $percent = $this->getSimilarPercent($name1,$name2);
        if($percent >= 85 ){
            return true ;
        }
        return false ;
    }
    

    // $matchType :1 boolean  2:lanague
    function matchEntByName($entName, $matchType = 1, $timeOut = 3.5): array
    {
        $timeStart = microtime(true);    
  
        //先从es match   
        $esRes = $this->matchEntByNameMatchByEs($entName); 
        CommonService::getInstance()->log4PHP('es match'.
            json_encode( 
               [
                    'data' => $esRes,
                    'time' => (microtime(true) - $timeStart),
               ]
            ) 
        ); 
        // 如果es 就匹配到了 直接返回 
        foreach($esRes as $data){ 
            if( $this->checkIfSimilar($data['name'], $entName) ){
                CommonService::getInstance()->log4PHP('es match ok , return '.
                    json_encode( 
                            [
                                'data' => $matchedItem,
                                'time' => (microtime(true) - $timeStart),
                        ]
                        ) 
                    ); 
                return $data ;
            }
        }   

        // es木有的 从 db找： 分词全文匹配+精确
        $csp = new \EasySwoole\Component\Csp(); 
        // 分词全文匹配找：Boolean mode 
        if ($matchType == 1) {
            $this->matchEntByNameMatchByBooleanMode($csp,$entName);
        }
        
        //分词全文匹配找： language mode 
        if ($matchType == 2) { 
            $this->matchEntByNameMatchByLanguageMode($csp,$entName);
        }  
         
        // 精确找 
        $this->matchEntByNameEqualMatchByName($csp,$entName);
        
        $dbres = ($csp->exec($timeOut)); 
        CommonService::getInstance()->log4PHP('从db找 res'.
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
        foreach($dbres as $dataItem){
            // 如果精确匹配到了 优先使用精确值
            if(
                $dataItem['type'] == 'equal' &&
                !empty($dataItem['data'])
            ){
                CommonService::getInstance()->log4PHP('精确匹配到了'.
                json_encode( 
                    $dataItem['data'][0]
                ) 
            ); 
                return $dataItem['data'][0];
            }  
        } 

        // 剩余的 按照相似度排序 然后返回相似度最高的
        foreach($esRes as $dataItem){  
            $percent = $this->getSimilarPercent($dataItem['name'], $entName) ;
            $matchedDatas[$percent] = [
                'id' => $dataItem['id'] ,
                'name' => $dataItem['name'] ,
            ];
        }
        CommonService::getInstance()->log4PHP(' 根据匹配度1  '.
            json_encode( 
                $matchedDatas 
            ) 
        ); 

        foreach($dbres as $dataItem){  
            CommonService::getInstance()->log4PHP(' dataItem  '.
            json_encode( 
                $dataItem 
            ) 
        ); 
            foreach( $dataItem['data'] as $item){
                $percent = $this->getSimilarPercent($item['name'], $entName) ;
                $matchedDatas[$percent] = [
                    'id' => $item['id'] ,
                    'name' => $item['name'] ,
                ];
            }
        } 
        CommonService::getInstance()->log4PHP(' 根据匹配度2  '.
            json_encode( 
                $matchedDatas 
            ) 
        ); 
        //根据匹配度 返回最高的一个
        ksort($matchedDatas);
        $resData =  end($matchedDatas);
        CommonService::getInstance()->log4PHP(' 根据匹配度  '.
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
        if(!empty($list)){
            return $list[0];
        }

        // 从结果找
        $matchedDatas = [];
        for(
            $i = 0; $i<=3; $i++
        ){
            $csp = new \EasySwoole\Component\Csp();
            $start = $i*2;
            $end = $start+1;

            for ($j = $start; $j<=$end; $j++) {

                $csp->add('BOOLEAN_MODE_new_company_name_'.$j, function () use ($entName, $j) {
                    $timeStart2 = microtime(true);
                    $matchStr = (new XinDongService())->splitChineseNameForMatchAgainst($entName);
                    $retData =  (new XinDongService())
                        ->matchAainstEntName(
                            $matchStr,
                            " IN BOOLEAN MODE ",
                            'company_name_'.$j ,
                            'id,name',
                            5
                        );
                    $timeEnd2 = microtime(true);
                    $execution_time11 = ($timeEnd2 - $timeStart2);
                    return  [
                        'data' => $retData,
                        'type' => 'Boolean',
                        'time' => $execution_time11
                    ];
                });
            }

            $dbres = ($csp->exec($timeOut));
            CommonService::getInstance()->log4PHP('从db找 res'.
                json_encode(
                    [
                        'data' => $dbres,
                        'time' => (microtime(true) - $timeStart),
                    ]
                )
            );
            foreach($dbres as $dataItem){
//                CommonService::getInstance()->log4PHP(' dataItem  '.
//                    json_encode(
//                        $dataItem
//                    )
//                );
                foreach( $dataItem['data'] as $item){
                    if( $this->checkIfSimilar($item['name'], $entName) ){
                        CommonService::getInstance()->log4PHP('es match ok , return '.
                            json_encode(
                                [
                                    'data' => $item,
                                    'time' => (microtime(true) - $timeStart),
                                ]
                            )
                        );
                        return $item ;
                    }
                }
            }

            // 剩余的 按照相似度排序 然后返回相似度最高的
            foreach($dbres as $dataItem){
//                CommonService::getInstance()->log4PHP(' dataItem  '.
//                    json_encode(
//                        $dataItem
//                    )
//                );
                foreach( $dataItem['data'] as $item){
                    $percent = $this->getSimilarPercent($item['name'], $entName) ;
                    $matchedDatas[$percent] = [
                        'id' => $item['id'] ,
                        'name' => $item['name'] ,
                    ];
                }
            }
            CommonService::getInstance()->log4PHP(' 根据匹配度2  '.
                json_encode(
                    $matchedDatas
                )
            );
//            sleep(0.5);
        }

        //根据匹配度 返回最高的一个
        ksort($matchedDatas);
        $resData =  end($matchedDatas);
        CommonService::getInstance()->log4PHP(' 根据匹配度  '.
            json_encode(
                $matchedDatas
            )
        );

//        $timeEnd = microtime(true);
//        $execution_time1 = (microtime(true) - $timeStart);

        return $resData;
    }

    static function trace(){
        $old_traces = debug_backtrace();
        $new_traces = [];
        if(empty($old_traces)){
            return [];
        }
        
        $allowed_field_arr = [
            'file',
            'line',
            'function',
        ];

        foreach ($old_traces as $traceArr){
            $tmpArr = [];
            if(in_array($traceArr['function'],[
                'trace',
                'dispatch',
                'controllerHandler',
                '__hook',
                '__exec'
            ])){
                continue;
            }
            foreach ($traceArr as $trac_key=>$trace_value){
                if(!in_array(
                    $trac_key,
                    $allowed_field_arr
                )){
                    continue;
                }
                $tmpArr[$trac_key]=$trace_value;
            }
            if(empty($tmpArr)){
                continue;
            }
            $new_traces[] = $tmpArr;
        }
        return  $new_traces ;
    }

    function getLogoByEntId($entId){
        $logoData = XsyA24Logo::create()
            ->where('id', $entId)
            ->get();
        // CommonService::getInstance()->log4PHP('logo '.json_encode([
        //     $logoData,
        //     ])); 
        if(empty($logoData)){
            return '';
        }
        return str_replace('logo', '', $logoData->getAttr('file_path'));
    } 
    function getEsBasicInfo($companyId): array
    {
        
        $ElasticSearchService = new ElasticSearchService(); 
        
        $ElasticSearchService->addMustMatchQuery( 'xd_id' , $companyId) ;  

        $size = 1;
        $page = 1;
        $offset  =  ($page-1)*$size;
        $ElasticSearchService->addSize($size) ;
        $ElasticSearchService->addFrom($offset) ; 

        $responseJson = (new XinDongService())->advancedSearch($ElasticSearchService);
        $responseArr = @json_decode($responseJson,true); 
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


        foreach($hits as &$dataItem){
            $addresAndEmailData = $this->getLastPostalAddressAndEmail($dataItem);
            $dataItem['_source']['last_postal_address'] = $addresAndEmailData['last_postal_address'];
            $dataItem['_source']['last_email'] = $addresAndEmailData['last_email']; 

            // 公司简介
            $tmpArr = explode('&&&', trim($dataItem['_source']['gong_si_jian_jie']));
            array_pop($tmpArr);
            $dataItem['_source']['gong_si_jian_jie_data_arr'] = [];
            foreach($tmpArr as $tmpItem_){
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
            if(!$webStr){
                continue; 
            }

            $webArr = explode('&&&', $webStr);
            !empty($webArr) && $dataItem['_source']['web'] = end($webArr); 
        }
        $res = $hits[0]['_source'];
        return !empty($res)? $res:[];
    }

    function getLastPostalAddressAndEmail($dataItem){
        if(!empty($dataItem['_source']['report_year'])){
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

    function addCarInsuranceInfo($dataItem){  
        $oldModel = CarInsuranceInfo::create()
            ->where(
            [
                'vin' => $dataItem['vin'],
                'entId' => $dataItem['entId'], 
            ])->get();
        if($oldModel){
            return $oldModel->getAttr('id') ;
        }

        try {
            $newModel = CarInsuranceInfo::create()
                ->data([
                    'vin' => $dataItem['vin'],
                    'entId' => $dataItem['entId'], 
                    'idCard' => $dataItem['idCard'],
                    'legalPerson' => $dataItem['legalPerson'],
                ])->save() ;
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        } 
        return $newModel ;
    }

    function addCompanyCarInsuranceStatusInfo($dataItem){  
        $oldModel = CompanyCarInsuranceStatusInfo::create()
            ->where(
            [
                'entId' => $dataItem['entId'], 
            ])->get();
        if($oldModel){
            return $oldModel ;
        }

        try {
            $newModel = CarInsuranceInfo::create()
                ->where([
                    'entId' => $dataItem['entId'], 
                ])->save() ;
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        } 
        return $newModel ;
    }

    function addUserCarsRelation($dataItem){  
        $oldModel = UserCarsRelation::create()
            ->where(
            [
                'car_insurance_id' => $dataItem['car_insurance_id'], 
                'user_id' => $dataItem['user_id'], 
            ])->get();
        if($oldModel){
            return $oldModel ;
        }
        
        try {
            $newModel = CarInsuranceInfo::create()
                ->where([
                    'car_insurance_id' => $dataItem['car_insurance_id'], 
                    'user_id' => $dataItem['user_id'], 
                ])->save() ;
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        } 
        return $newModel ;
    }

    /*
    config:
    [
        'matchNamesByEqual' = true,
        'matchNamesByContain' = true,
    ]
    */
    function matchNames($tobeMatch,$target,$config){
        //完全匹配
        if($config['matchNamesByEqual']){
            $res = $this->matchNamesByEqual($tobeMatch,$target);
            if($res){
                CommonService::getInstance()->log4PHP(
                    'matchNamesByEqual yes :' .$tobeMatch.$target
                );
                return true;
            }
        }
        
        //包含匹配  张三0808    张三
        if($config['matchNamesByContain']){
            $res = $this->matchNamesByContain($tobeMatch,$target);
            if($res){
                CommonService::getInstance()->log4PHP(
                    'matchNamesByContain yes :' .$tobeMatch.$target
                );
                return true;
            }
        }

        //包含被匹配  张三0808    张三
        if($config['matchNamesByToBeContain']){
            $res = $this->matchNamesByToBeContain($tobeMatch,$target);
            if($res){
                CommonService::getInstance()->log4PHP(
                    'matchNamesByToBeContain yes :' .$tobeMatch.$target
                );
                return true;
            }
        }

        //文本匹配度  张三0808    张三   
        if($config['matchNamesBySimilarPercentage']){
            $res = $this->matchNamesBySimilarPercentage(
                $tobeMatch,
                $target,
                $config['matchNamesBySimilarPercentageValue']
            );
            if($res){
                CommonService::getInstance()->log4PHP(
                    'matchNamesBySimilarPercentageValue yes :' .$tobeMatch.$target
                );
                return true;
            }
        }
         
        //文本匹配度  张三0808    张三   
        if($config['matchNamesByPinYinSimilarPercentage']){
            $res = $this->matchNamesByPinYinSimilarPercentage(
                $tobeMatch,
                $target,
                $config['matchNamesByPinYinSimilarPercentageValue']
            );
            if($res){
                CommonService::getInstance()->log4PHP(
                    'matchNamesByPinYinSimilarPercentageValue yes :' .$tobeMatch.$target
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
    function matchNamesV2($tobeMatch,$target){
        CommonService::getInstance()->log4PHP(json_encode(['$tobeMatch'=>$tobeMatch,'$target'=>$target]));

        //完全匹配
        $res = $this->matchNamesByEqual($tobeMatch,$target);
        if($res){
            return [
                'type' => '精准匹配',
                'details' => '名称完全匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }

        //拼音全等
        $tobeMatchArr = $this->getPinYin($tobeMatch);
        CommonService::getInstance()->log4PHP(json_encode(['$tobeMatchArr'=>$tobeMatchArr]));

        if(
            count($tobeMatchArr) == 2
        ){
            //顺序拼音
            $str1 = $tobeMatchArr[0].$tobeMatchArr[1];
            //逆序拼音
            $str2 = $tobeMatchArr[1].$tobeMatchArr[0];
            CommonService::getInstance()->log4PHP(json_encode(['match pinyin '=>[$str1,$str2]]));

            if(
                $str1 == $target ||
                $str2 == $target
            ){
                return [
                    'type' => '精准匹配',
                    'details' => '拼音相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        if(
            count($tobeMatchArr) == 3
        ){
            $str1 = $tobeMatchArr[0].$tobeMatchArr[1].$tobeMatchArr[2];
            $str2 = $tobeMatchArr[0].$tobeMatchArr[2].$tobeMatchArr[1];
            $str3 = $tobeMatchArr[1].$tobeMatchArr[0].$tobeMatchArr[2];
            $str4 = $tobeMatchArr[1].$tobeMatchArr[2].$tobeMatchArr[0];
            $str5 = $tobeMatchArr[2].$tobeMatchArr[0].$tobeMatchArr[1];
            $str6 = $tobeMatchArr[2].$tobeMatchArr[1].$tobeMatchArr[0];
            CommonService::getInstance()->log4PHP(json_encode(['match pinyin2 '=>[$str1,$str2,$str3,$str4,$str5,$str6]]));
            if(
                $str1 == $target ||
                $str2 == $target ||
                $str3 == $target ||
                $str4 == $target ||
                $str5 == $target ||
                $str6 == $target
            ){
                return [
                    'type' => '精准匹配',
                    'details' => '拼音相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        //拼音缩写
        if(
            count($tobeMatchArr) == 2
        ){
            $name1 =  PinYinService::getShortPinyin(substr($tobeMatch, 0, 3));
            $name2 =  PinYinService::getShortPinyin(substr($tobeMatch, 3, 3));
            CommonService::getInstance()->log4PHP(json_encode(['match short  pinyin '=>[$name1,$name2]]));

            $str1 = $name1.$name2;
            $str2 = $name2.$name1;
            if(
                $str1 == $target ||
                $str2 == $target
            ){
                return [
                    'type' => '精准匹配',
                    'details' => '拼音首字母相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        //拼音缩写
        if(
            count($tobeMatchArr) == 3
        ){
            $name1 =  PinYinService::getShortPinyin(substr($tobeMatch, 0, 3));
            $name2 =  PinYinService::getShortPinyin(substr($tobeMatch, 3, 3));
            $name3 =  PinYinService::getShortPinyin(substr($tobeMatch, 6, 3));
            CommonService::getInstance()->log4PHP(json_encode(['match short  pinyin2 '=>[$name1,$name2,$name3]]));

            $str1 = $name1.$name2.$name3;
            $str2 = $name1.$name3.$name2;
            $str3 = $name2.$name1.$name3;
            $str4 = $name2.$name3.$name1;
            $str5 = $name3.$name2.$name1;
            $str6 = $name3.$name1.$name2;

            if(
                $str1 == $target ||
                $str2 == $target ||
                $str3 == $target ||
                $str4 == $target ||
                $str5 == $target ||
                $str6 == $target
            ){
                return [
                    'type' => '精准匹配',
                    'details' => '拼音首字母相等',
                    'res' => '成功',
                    'percentage' => '',
                ];
            }
        }

        //多音字匹配
        $tobeMatchArr = $this->getPinYin($tobeMatch);
        $targetArr = $this->getPinYin($target);
        CommonService::getInstance()->log4PHP(json_encode(['duo yin zi  '=>['$tobeMatchArr' => $tobeMatchArr,'$targetArr' =>$targetArr]]));

        $res = $this->checkIfArrayEqual($tobeMatchArr,$targetArr);
        if($res){
            return [
                'type' => '精准匹配',
                'details' => '多音字匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }

        //包含匹配  张三0808    张三
        $res = $this->matchNamesByContain($tobeMatch,$target);

        if($res){
            return [
                'type' => '精准匹配',
                'details' => '中文包含匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }

        //包含被匹配  张三0808    张三
        $res = $this->matchNamesByToBeContain($tobeMatch,$target);
        if($res){
            return [
                'type' => '精准匹配',
                'details' => '中文被包含匹配',
                'res' => '成功',
                'percentage' => '',
            ];
        }

        //拼音包含
        similar_text(PinYinService::getPinyin($tobeMatch), PinYinService::getPinyin($target), $perc);
        $res = array_intersect($tobeMatchArr,$targetArr);
        if(
            !empty($res) &&
            $perc >= 50
        ){
            return [
                'type' => '模糊匹配',
                'details' => '拼音包含匹配',
                'res' => '成功',
                'percentage' => number_format($perc,2),
            ];
        }

        //文本匹配度  张三0808    张三
        similar_text($tobeMatch, $target, $perc);
        if($perc > 70){
            return [
                'type' => '模糊匹配',
                'details' => '中文相似度匹配',
                'res' =>  '成功'  ,
                'percentage' => number_format($perc,2),
            ];
        }

        //拼音相似度匹配  张三0808    张三
        similar_text(PinYinService::getPinyin($tobeMatch), PinYinService::getPinyin($target), $perc);
        if($perc >= 80 ){
            return [
                'type' => '模糊匹配',
                'details' => '拼音相似度匹配',
                'res' =>  '成功'  ,
                'percentage' =>  number_format($perc,2),
            ];
        }

        return [
            'type' => '',
            'details' => '',
            'res' =>  '失败'  ,
            'percentage' =>  0,
        ];

    }

    function  checkIfArrayEqual($array1,$array2){

        foreach ($array1 as $value1){
            if(
                !in_array($value1,$array2)
            ){
                return false;
            }
        }

        return  true;
    }
    //  tobeMatch：张三丰  target：张三丰 
    function matchNamesByEqual($tobeMatch,$target){
        $res =  $tobeMatch === $target ? true :false;
//        CommonService::getInstance()->log4PHP(
//            'matchNamesByEqual :'.json_encode([
//                $res,
//                $tobeMatch,
//                $target
//            ])
//        );
        return $res;
    }

   function  getPinYin($target){
       $targetList = [];
       $init = strlen($target);
       $nums = 0;
       while ($init>0){
           $targetList[] = PinYinService::getPinyin( substr($target, $nums, 3));
           $nums += 3;
           $init -= 3;
       }
       return $targetList;
   }

    // tobeMatch : 张三0808  target：张三
    function matchNamesByContain($tobeMatch,$target){
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
    function matchNamesByToBeContain($tobeMatch,$target){
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
    function matchNamesBySimilarPercentage($tobeMatch,$target,$percentage){
        $res = false;
        similar_text($tobeMatch, $target, $perc);
        if ($perc >= $percentage) {
          $res = true;
        }

        CommonService::getInstance()->log4PHP(
            'matchNamesByContain :'.json_encode([
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
    function matchNamesByPinYinSimilarPercentage($tobeMatch,$target,$percentage){
        $res = false;
        $tobeMatchPin = PinYinService::getPinyin($tobeMatch);
        $targetPinYin = PinYinService::getPinyin($target);
        similar_text($tobeMatchPin, $targetPinYin, $perc);
        if ($perc >= $percentage) {
          $res = true;
        }

        CommonService::getInstance()->log4PHP(
            'matchNamesByContain :'.json_encode([
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

    function matchContactNameByWeiXinName($entName,$WeiXin){
        $matchedContactName =  [];
        
        //获取所有联系人
        $staffsDatas = LongXinService::getLianXiByName($entName); 
        if (empty($staffsDatas)) {
            return $matchedContactName;
        }

        foreach($staffsDatas as $staffsDataItem){
            $tmpName= trim($staffsDataItem['stff_name']);
            if(!$tmpName){
                continue;
            };
            $res = (new XinDongService())->matchNames($tmpName,$WeiXin,
            [
                'matchNamesByEqual' => true,
                'matchNamesByContain' => true,
                'matchNamesByToBeContain' => true,
                'matchNamesBySimilarPercentage' => true,
                'matchNamesBySimilarPercentageValue' => 60,
                'matchNamesByPinYinSimilarPercentage' => true,
                'matchNamesByPinYinSimilarPercentageValue' => 60,
            ]);  
            if($res){
//                CommonService::getInstance()->log4PHP(
//                    'matchContactNameByWeiXinName yes  :' .$tmpName . $WeiXin
//                );
                return $staffsDataItem;
            }
        }

        return $matchedContactName;
    }

    function matchContactNameByWeiXinNameV2($entName,$WeiXin){

        //获取所有联系人
        $staffsDatas = LongXinService::getLianXiByName($entName);
        if (empty($staffsDatas)) {
            return [];
        }

        foreach($staffsDatas as $staffsDataItem){
            $tmpName= trim($staffsDataItem['stff_name']);
            if(!$tmpName){
                continue;
            };
            $res = (new XinDongService())->matchNamesV2($tmpName,$WeiXin);
            if($res['res'] == '成功'){
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

    //模糊匹配企业名称  根据企业简称  匹配企业
    static function  fuzzyMatchEntName($fuzzyName,$size = 1 ){
        $companyEsModel = new \App\ElasticSearch\Model\Company();
        $companyEsModel->es->addMustMatchQuery('name',$fuzzyName) ;
        $companyEsModel
            ->addSize($size)
            ->addFrom(0)
            ->searchFromEs() ;

        $returnData = [];
        foreach ($companyEsModel->return_data['hits']['hits'] as $dataItem){
            $returnData[] = $dataItem ;
        }
        return $returnData;
    }

    static  function  getMarjetShare($xd_id){
        return '';
        $companyEsModel = new \App\ElasticSearch\Model\Company();
        $companyEsModel
            //根据id查询
            ->addMustTermQuery('xd_id',$xd_id)
            ->addSize(1)
            ->addFrom(0)
            ->searchFromEs()
            // 格式化下日期和时间
            ->formatEsDate()
            // 格式化下金额
            ->formatEsMoney()
        ;

        //四级分类
        $siJiFenLei = "";
        $ying_shou_gui_mo = "";
        foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
            $siJiFenLei = $dataItem['_source']['si_ji_fen_lei_code'];
            $ying_shou_gui_mo = $dataItem['_source']['ying_shou_gui_mo'];
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                'si_ji_fen_lei_code  '=>$siJiFenLei,
                'ying_shou_gui_mo  '=>$ying_shou_gui_mo,

            ])
        );
        if(empty($siJiFenLei)){
            return  "";
        }
        if(empty($ying_shou_gui_mo)){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__LINE__=>'empty $ying_shou_gui_mo',
                    '$ying_shou_gui_mo' => $ying_shou_gui_mo,
                ])
            );
            return  "";
        }
        //三位以下的  企业太多了 不计算
        if(strlen($siJiFenLei) <=3 ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__LINE__=>'too shot  $ying_shou_gui_mo',
                    '$ying_shou_gui_mo' => $ying_shou_gui_mo,
                ])
            );
            return  "";
        }

        //取前四位
        $tmpSiji = substr($siJiFenLei , 0 , 5) ;
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
            ->addSort("_id","asc")
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
            foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
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
                $nums ++;

            }

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                ->SetQueryBySiJiFenLei($tmpSiji)
                ->addSize(1000)
                ->addSort("_id","asc")
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
                'match_companys_ying_shou_gui_mo_map_count  '=>count($siJiFenLeiArrs),
                '$nums' => $nums,
            ])
        );

        $totalMin = 0;
        $totalMax = 0;
        $yingShouGUiMoMap = XinDongService::getYingShouGuiMoMapV2();
        foreach ($siJiFenLeiArrs as $tmpSiJiFenLei){
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

        $rate1 = $yingShouGUiMoMap[$ying_shou_gui_mo]['min']/$totalMin;
        $rate2 = $yingShouGUiMoMap[$ying_shou_gui_mo]['max']/$totalMax;
        CommonService::getInstance()->log4PHP(
            json_encode([
                'market_share_$rate1  '=>[
                    '$rate1'=>$rate1,
                    'fenzi'=>$yingShouGUiMoMap[$ying_shou_gui_mo]['min'],
                    'fenmu'=>$totalMin,
                ],
                'market_share_$rate2  '=>[
                    '$rate2'=>$rate2,
                    'fenzi'=>$yingShouGUiMoMap[$ying_shou_gui_mo]['max'],
                    'fenmu'=>$totalMax,
                ],
            ])
        );
       $n1 =  number_format($rate1,5)*100;
       $n2 =  number_format($rate2,5)*100;
        return  [
            'min' => $n1.'%', 'max' => $n2.'%',
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

}
