<?php

namespace App\HttpController\Service\XinDong;

use App\Csp\Service\CspService;
use App\ElasticSearch\Service\ElasticSearchService;
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

class XinDongService extends ServiceBase
{
    use Singleton;

    private $fyyList;
    private $ldUrl; 
    
    // 企业类型
    public $company_org_type_youxian = 10;
    public $company_org_type_youxian_des =  '有限责任'; 
    public $company_org_type_youxian2 = 15;
    public $company_org_type_youxian2_des =  '有限公司'; 

    public $company_org_type_gufen = 20;
    public $company_org_type_gufen_des =  '股份'; 

    public $company_org_type_fengongsi = 25;
    public $company_org_type_fengongsi_des =  '分公司'; 
    public $company_org_type_zongsongsi = 30;
    public $company_org_type_zongsongsi_des =  '总公司'; 

    public $company_org_type_youxianhehuo = 35;
    public $company_org_type_youxianhehuo_des =  '有限合伙'; 

    // 成立年限
    public $estiblish_year_under_2 = 2;
    public $estiblish_year_under_2_des = '2年以内';

    public $estiblish_year_2to5 = 5;
    public $estiblish_year_2to5_des = '2-5年';

    public $estiblish_year_5to10 = 5;
    public $estiblish_year_5to10_des = '5-10年';

    public $estiblish_year_10to15 = 5;
    public $estiblish_year_10to15_des = '5-10年';

    public $estiblish_year_more_than_10 = 10;
    public $estiblish_year_more_than_10_des = '10年以上';

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
    public $reg_capital_50_des = '50万元以下';
    public $reg_capital_50to100 = 10;
    public $reg_capital_50to100_des = '50-100万';
    public $reg_capital_100to200 = 20;
    public $reg_capital_100to200_des = '100-200万';
    public $reg_capital_200to500 = 30;
    public $reg_capital_200to500_des = '200-500万';
    public $reg_capital_500to1000 = 40;
    public $reg_capital_500to1000_des = '500-1000万';
    public $reg_capital_1000to10000 = 50;
    public $reg_capital_1000to10000_des = '1000万-1亿';
    public $reg_capital_10000to100000 = 60;
    public $reg_capital_10000to100000_des = '1亿-10亿'; 

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
           $this->reg_capital_10000to100000  =>  $this->reg_capital_10000to100000_des, 
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
           $this->estiblish_year_more_than_10  => $this->estiblish_year_more_than_10_des,
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
    function getSearchOption($postData)
    {
        
        return  [
           [
                'pid' => 10,
                'desc' => '企业类型',
                'key' => 'company_org_type',
                'type' => 'select',
                'data' => $this->getCompanyOrgType(),
            ], 
             [
                'pid' => 20,
                'desc' => '成立年限',
                'key' => 'estiblish_year_nums',
                'type' => 'select',
                'data' => $this->getEstiblishYear(),
            ], 
             [
                'pid' => 30,
                'desc' => '营业状态',
                'key' => 'reg_status',
                'type' => 'select',
                'data' => $this->getRegStatus(),
            ], 
             [
                'pid' => 40,
                'desc' => '注册资本',
                'key' => 'reg_capital',
                'type' => 'select',
                'data' => $this->getRegCapital(),
            ],
            [
                'pid' => 50,
                'desc' => '营收规模',
                'key' => 'ying_shou_gui_mo',
                'type' => 'select',
                'data' => $this->getRegCapital(),
            ],
        ];
    }

     //高级搜索
     function advancedSearch($elasticSearchService)
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
        $bean->setIndex('company_287_all');
        $bean->setType('_doc');
        $bean->setBody($elasticSearchService->query);
        $response = $elasticsearch->client()->search($bean)->getBody(); 
        CommonService::getInstance()->log4PHP(json_encode(['re-query'=>$elasticSearchService->query]), 'info', 'souke.log');
        CommonService::getInstance()->log4PHP(json_encode(['re-response'=>$response]), 'info', 'souke.log');
        
        return  $response;
     }

     function setEsSearchQuery($postData, $elasticSearchService){ 
        $elasticSearchService->setByPage($postData['page'],$postData['size']); 
       
        // must match
        $addMustMatchQueryLists = [
            'company_org_type',
            'business_scope',
            'business_scope',
            'reg_status',
            'property1',
            'ying_shou_gui_mo',
            'si_ji_fen_lei_code',
            'gao_xin_ji_shu',
            'deng_ling_qi_ye',
            'tuan_dui_ren_shu',
            'tong_xun_di_zhi',
            'web',
            'yi_ban_ren',
            'shang_shi_xin_xi',
            'app',
            'shang_pin_data',
        ];
        foreach($addMustMatchQueryLists as $field){
            !empty($postData[$field]) && $elasticSearchService->addMustMatchQuery($field, $postData[$field]);
        }

        // must match_phrase
        $addMustMatchPhraseQueryLists = [
            'name',
        ];
        foreach($addMustMatchPhraseQueryLists as $field){
            !empty($postData[$field]) && $elasticSearchService->addMustAatchPhraseQuery($field, $postData[$field]);
        } 
        
        // must range
        $addMustRangeQueryLists = [
            'estiblish_time' =>['min'=> $postData['min_estiblish_time'], 'max'=> $postData['max_estiblish_time']],
            'reg_capital' =>['min'=> $postData['min_reg_capital'], 'max'=> $postData['max_reg_capital']]
        ];
        foreach($addMustRangeQueryLists as $field=>$item){
            (!empty($postData[$item['min']])||!empty($postData[$item['max']])) && 
                $elasticSearchService->addMustRangeQuery($field, $postData[$item['min']], $postData[$item['max']]);
        }
        
        $elasticSearchService->setDefault();
       return  $elasticSearchService;
     }

     function saveSearchHistory($userId, $query, $queryCname){
        return UserSearchHistory::create()->data([
            'userId' => $userId,
            'query' => $query,
            'query_cname' => $queryCname,
        ])->save();
     }

     function getCompanyBasicInfo(){ 
        return [ ];
     }
}
