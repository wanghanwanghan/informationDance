<?php

namespace App\HttpController\Service\XinDong;

use App\Csp\Service\CspService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\TaoShu\TaoShuService;
use wanghanwanghan\someUtils\traits\Singleton;

class XinDongService extends ServiceBase
{
    use Singleton;

    private $fhList;
    private $qccUrl;

    function __construct()
    {
        $this->fhList = \Yaconf::get('fahai.listBaseUrl');
        $this->qccUrl = \Yaconf::get('qichacha.baseUrl');

        return parent::onNewService();
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'zdw', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'zdw', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'zdw', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'zdw', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'zdw', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'zdw', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'sat', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'sat', $postData);
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
            return (new FaHaiService())->setCheckRespFlag(true)->getList($this->fhList . 'sat', $postData);
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

        //企查查 融资
        $csp->add('SearchCompanyFinancings', function () use ($entName) {

            $data = [];

            $postData = ['searchKey' => $entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BusinessStateV4/SearchCompanyFinancings', $postData);

            foreach ($res['result'] as $one) {
                $data[] = $one['Date'] . "，拿到了来自{$one['Investment']}的{$one['Round']}融资，{$one['Amount']}";
            }

            return empty($data) ? null : $data;
        });

        //企查查 行政许可 只要数字
        $csp->add('GetAdministrativeLicenseList', function () use ($entName) {

            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);

            ($res['code'] === 200 && !empty($res['paging'])) ? $total = (int)$res['paging']['total'] : $total = 0;

            return $total;
        });

        //企查查 专利 只要数字
        $csp->add('PatentSearch', function () use ($entName) {

            $postData = [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'PatentV4/Search', $postData);

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

                var_export($res);

                if ($res['code'] != 200 || empty($res['result'])) break;

                foreach ($res['result'] as $one) {

                    $data[] = $one['ESDATE'] . "，{$one['ENTNAME']}成立了，当前状态是{$one['ENTSTATUS']}";
                }

                $page++;

            } while ($page <= 5);

            var_export('wanghan');

            return empty($data) ? null : $data;
        });

        //土地资源
        $csp->add('tudiziyuan', function () {

        });

        return $this->checkResp(200, null, CspService::getInstance()->exec($csp), '查询成功');
    }


}
