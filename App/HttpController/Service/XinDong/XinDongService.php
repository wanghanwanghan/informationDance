<?php

namespace App\HttpController\Service\XinDong;

use App\Csp\Service\CspService;
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

class XinDongService extends ServiceBase
{
    use Singleton;

    private $fyyList;
    private $ldUrl;

    function __construct()
    {
        $this->fyyList = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $this->ldUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');

        return parent::__construct();
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
    function getNaCaoRegisterInfo($entName)
    {

    }

    //二次特征分数
    function getFeatures($entName)
    {
        $res = (new xds())->cwScore($entName);

        return $this->checkResp(200, null, $res, '查询成功');
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

}
