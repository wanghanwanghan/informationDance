<?php

namespace App\Task\TaskList;

use App\Csp\Service\CspService;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\Report\Tcpdf;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\Task\TaskBase;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class CreateEasyReportCustomizedTask extends TaskBase implements TaskInterface
{
    private $entName;
    private $reportNum;
    private $phone;
    private $type;
    private $dataKey;

    function __construct($entName, $reportNum, $phone, $type, $dataKey)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;
        $this->phone = $phone;
        $this->type = $type;
        $this->dataKey = $dataKey;

        return parent::__construct();
    }

    private function dataKeyToKey(): array
    {
        $dataKeyIndex = explode(',', $this->dataKey);
        $dataKeyIndex = array_filter($dataKeyIndex);

        return [];
    }

    function run(int $taskId, int $workerIndex)
    {
        $pdf = new Tcpdf();

        // 设置文档信息
        $pdf->SetCreator('王瀚');
        $pdf->SetAuthor('王瀚');
        $pdf->SetTitle('王瀚');
        $pdf->SetSubject('王瀚');
        $pdf->SetKeywords('TCPDF, PDF, PHP');

        $pdf->setPrintHeader(false);

        // 设置页脚信息
        $pdf->setFooterData([0, 64, 0], [0, 64, 128]);
        // 设置页脚字体
        $pdf->setFooterFont(['helvetica', '', '8']);

        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');

        // 设置间距
        $pdf->SetMargins(15, 15, 15);//页面间隔
        $pdf->SetHeaderMargin(5);//页眉top间隔
        $pdf->SetFooterMargin(5);//页脚bottom间隔

        // 设置分页
        $pdf->SetAutoPageBreak(true, 25);

        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        //设置字体 stsongstdlight支持中文
        $pdf->SetFont('stsongstdlight', '', 10);

        $this->fillData($pdf, []);

        //输出PDF
        $pdf->Output(REPORT_PATH . 't.pdf', 'F');//I输出、D下载

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        try {
            $info = ReportInfo::create()->where('phone', $this->phone)->where('filename', $this->reportNum)->get();

            $file = $throwable->getFile();
            $line = $throwable->getLine();
            $msg = $throwable->getMessage();

            $content = "[file => {$file}] [line => {$line}] [msg => {$msg}]";

            $info->update(['status' => 1, 'errInfo' => $content]);

        } catch (\Throwable $e) {

        }
    }

    //填充数据
    private function fillData(Tcpdf $pdf, $cspReturnData)
    {
        $pdf->AddPage();

        for ($i = 1; $i <= 20; $i++) {
            $pdf->SetFont('stsongstdlight', '', $i);
            $pdf->writeHTML("<div style='text-align: center;font-size: {$i}px'><h1>第 {$i} 页内容</h1></div>");
        }
    }

    //并发请求数据
    private function cspHandleData()
    {
        //创建csp对象
        $csp = CspService::getInstance()->create();

        //淘数 基本信息 工商信息
        $csp->add('getRegisterInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $this->entName], 'getRegisterInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

            return $res;
        });

//        //企查查 基本信息 工商信息
//        $csp->add('GetBasicDetailsByName', function () {
//
//            $postData = ['keyWord' => $this->entName];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIV4/GetBasicDetailsByName', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //淘数 基本信息 股东信息
//        $csp->add('getShareHolderInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 10,
//            ], 'getShareHolderInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //淘数 基本信息 高管信息
//        $csp->add('getMainManagerInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 10,
//            ], 'getMainManagerInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //淘数 基本信息 变更信息
//        $csp->add('getRegisterChangeInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 10,
//            ], 'getRegisterChangeInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 经营异常
//        $csp->add('GetOpException', function () {
//
//            $postData = ['keyNo' => $this->entName];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIException/GetOpException', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 实际控制人
//        $csp->add('Beneficiary', function () {
//
//            $postData = [
//                'companyName' => $this->entName,
//                'percent' => 0,
//                'mode' => 0,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Beneficiary/GetBeneficiary', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            $tmp = [];
//
//            if (count($res['BreakThroughList']) > 0) {
//                $total = current($res['BreakThroughList']);
//                $total = substr($total['TotalStockPercent'], 0, -1);
//
//                if ($total >= 50) {
//                    //如果第一个人就是大股东了，就直接返回
//                    $tmp = current($res['BreakThroughList']);
//
//                } else {
//                    //把返回的所有人加起来和100做减法，求出坑
//                    $hole = 100;
//                    foreach ($res['BreakThroughList'] as $key => $val) {
//                        $hole -= substr($val['TotalStockPercent'], 0, -1);
//                    }
//
//                    //求出坑的比例，如果比第一个人大，那就是特殊机构，如果没第一个人大，那第一个人就是控制人
//                    if ($total > $hole) $tmp = current($res['BreakThroughList']);
//                }
//            } else {
//                $tmp = null;
//            }
//
//            return $tmp;
//        });
//
//        //淘数 企查查 历史沿革
//        $csp->add('getHistoricalEvolution', function () {
//
//            $res = XinDongService::getInstance()->getHistoricalEvolution($this->entName);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //淘数 法人对外投资
//        $csp->add('lawPersonInvestmentInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 10,
//            ], 'lawPersonInvestmentInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //淘数 法人对外任职
//        $csp->add('getLawPersontoOtherInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 10,
//            ], 'getLawPersontoOtherInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //淘数 企业对外投资
//        $csp->add('getInvestmentAbroadInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 10,
//            ], 'getInvestmentAbroadInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //淘数 分支机构
//        $csp->add('getBranchInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 10,
//            ], 'getBranchInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 银行信息
//        $csp->add('GetCreditCodeNew', function () {
//
//            $postData = ['keyWord' => $this->entName];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICreditCode/GetCreditCodeNew', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 公司概况
//        $csp->add('SearchCompanyFinancings', function () {
//
//            $postData = ['searchKey' => $this->entName];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BusinessStateV4/SearchCompanyFinancings', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 招投标
//        $csp->add('TenderSearch', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Tender/Search', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 购地信息
//        $csp->add('LandPurchaseList', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandPurchase/LandPurchaseList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 土地公示
//        $csp->add('LandPublishList', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandPublish/LandPublishList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 土地转让
//        $csp->add('LandTransferList', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $post = ['id' => $one['Id']];
//                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferDetail', $post);
//                    ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = $detail['result'] : $detail = null;
//                    $one['detail'] = $detail;
//                }
//                unset($one);
//            }
//
//            return $res;
//        });
//
//        //企查查 建筑资质证书
//        $csp->add('Qualification', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Qualification/GetList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 建筑工程项目
//        $csp->add('BuildingProject', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BuildingProject/GetList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 债券信息
//        $csp->add('BondList', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Bond/BondList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 网站信息
//        $csp->add('GetCompanyWebSite', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'WebSiteV4/GetCompanyWebSite', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 微博
//        $csp->add('Microblog', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Microblog/GetList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 新闻舆情
//        $csp->add('CompanyNews', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CompanyNews/SearchNews', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //乾启 近三年团队人数变化率
//        $csp->add('itemInfo', function () {
//
//            $postData = ['entName' => $this->entName];
//
//            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);
//
//            if ($res['code'] === 200 && !empty($res['result'])) {
//
//                $yearArr = array_keys($res['result']);
//                $dataArr = array_values($res['result']);
//                $res = [];
//
//                for ($i = 0; $i < 3; $i++) {
//
//                    if (isset($dataArr[$i]['SOCNUM']) && is_numeric($dataArr[$i]['SOCNUM'])) {
//                        $SOCNUM_1 = (int)$dataArr[$i]['SOCNUM'];
//                    } else {
//                        $SOCNUM_1 = null;
//                    }
//
//                    if (isset($dataArr[$i + 1]['SOCNUM']) && is_numeric($dataArr[$i + 1]['SOCNUM'])) {
//                        $SOCNUM_2 = (int)$dataArr[$i + 1]['SOCNUM'];
//                    } else {
//                        $SOCNUM_2 = null;
//                    }
//
//                    if ($SOCNUM_1 !== null && $SOCNUM_2 !== null && $SOCNUM_2 !== 0) {
//                        $res[] = ['year' => $yearArr[$i], 'yoy' => ($SOCNUM_1 - $SOCNUM_2) / $SOCNUM_2, 'num' => $SOCNUM_1];
//                    } else {
//                        $res[] = ['year' => $yearArr[$i], 'yoy' => null, 'num' => $SOCNUM_1];
//                    }
//                }
//
//            } else {
//                $res = null;
//            }
//
//            return $res;
//        });
//
//        //企查查 建筑企业-专业注册人员
//        $csp->add('BuildingRegistrar', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BuildingRegistrar/GetList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 招聘信息
//        $csp->add('Recruitment', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Recruitment/GetList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //乾启 财务
//        $csp->add('FinanceData', function () {
//
//            $postData = ['entName' => $this->entName];
//
//            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);
//
//            if ($res['code'] === 200 && !empty($res['result'])) {
//                $res = (new QianQiService())->toPercent($res['result']);
//            } else {
//                $res = null;
//            }
//
//            if ($res === null) return $res;
//
//            $count1 = 0;
//
//            ksort($res);
//
//            foreach ($res as $year => $dataArr) {
//                $legend[] = $year;
//                array_pop($dataArr);
//                $tmp = array_map(function ($val) {
//                    return is_numeric($val) ? (int)round($val) : null;//四舍五入
//                }, array_values($dataArr));
//                $data[] = $tmp;
//                !empty(array_filter($tmp)) ?: $count1++;
//            }
//
//            $labels = ['资产总额', '负债总额', '营业总收入', '主营业务收入', '利润总额', '净利润', '纳税总额', '所有者权益'];
//
//            $extension = [
//                'width' => 1200,
//                'height' => 700,
//                'title' => $count1 == 2 ? '缺少上一年财务数据，财务图表未生成' : $this->entName . ' - 财务非授权 - 同比',
//                'xTitle' => '此图为概况信息',
//                //'yTitle'=>$this->entName,
//                'titleSize' => 14,
//                'legend' => $legend
//            ];
//
//            $tmp = [];
//            $tmp['pic'] = CommonService::getInstance()->createBarPic($data, $labels, $extension);
//            $tmp['data'] = $data;
//
//            return $tmp;
//        });
//
//        //企查查 业务概况
//        $csp->add('SearchCompanyCompanyProducts', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 10,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CompanyProductV4/SearchCompanyCompanyProducts', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            return $res;
//        });
//
//        //企查查 专利
//        $csp->add('PatentV4Search', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'PatentV4/Search', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 软件著作权
//        $csp->add('SearchSoftwareCr', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchSoftwareCr', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 商标
//        $csp->add('tmSearch', function () {
//
//            $postData = [
//                'keyword' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'tm/Search', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 作品著作权
//        $csp->add('SearchCopyRight', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchCopyRight', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 证书资质
//        $csp->add('SearchCertification', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICertification/SearchCertification', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 纳税信用等级
//        $csp->add('satparty_xin', function () {
//
//            $doc_type = 'satparty_xin';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 税务许可信息
//        $csp->add('satparty_xuke', function () {
//
//            $doc_type = 'satparty_xuke';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 税务登记信息
//        $csp->add('satparty_reg', function () {
//
//            $doc_type = 'satparty_reg';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 税务非正常户
//        $csp->add('satparty_fzc', function () {
//
//            $doc_type = 'satparty_fzc';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 欠税信息
//        $csp->add('satparty_qs', function () {
//
//            $doc_type = 'satparty_qs';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 涉税处罚公示
//        $csp->add('satparty_chufa', function () {
//
//            $doc_type = 'satparty_chufa';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 行政许可
//        $csp->add('GetAdministrativeLicenseList', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = ['id' => $one['Id']];
//
//                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseDetail', $postData);
//
//                    if ($detail['code'] == 200 && !empty($detail['result'])) {
//                        $one['detail'] = $detail['result'];
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 行政处罚
//        $csp->add('GetAdministrativePenaltyList', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = ['id' => $one['Id']];
//
//                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyDetail', $postData);
//
//                    if ($detail['code'] == 200 && !empty($detail['result'])) {
//                        $one['detail'] = $detail['result'];
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 环保 环保处罚
//        $csp->add('epbparty', function () {
//
//            $doc_type = 'epbparty';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 环保 重点监控企业名单
//        $csp->add('epbparty_jkqy', function () {
//
//            $doc_type = 'epbparty_jkqy';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 环保 环保企业自行监测结果
//        $csp->add('epbparty_zxjc', function () {
//
//            $doc_type = 'epbparty_zxjc';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 环保 环评公示数据
//        $csp->add('epbparty_huanping', function () {
//
//            $doc_type = 'epbparty_huanping';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 海关 海关企业
//        $csp->add('custom_qy', function () {
//
//            $doc_type = 'custom_qy';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 海关 海关许可
//        $csp->add('custom_xuke', function () {
//
//            $doc_type = 'custom_xuke';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 海关 海关信用
//        $csp->add('custom_credit', function () {
//
//            $doc_type = 'custom_credit';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 海关 海关处罚
//        $csp->add('custom_punish', function () {
//
//            $doc_type = 'custom_punish';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 一行两会 央行行政处罚
//        $csp->add('pbcparty', function () {
//
//            $doc_type = 'pbcparty';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 一行两会 银保监会处罚公示
//        $csp->add('pbcparty_cbrc', function () {
//
//            $doc_type = 'pbcparty_cbrc';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 一行两会 证监处罚公示
//        $csp->add('pbcparty_csrc_chufa', function () {
//
//            $doc_type = 'pbcparty_csrc_chufa';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 一行两会 证监会许可信息
//        $csp->add('pbcparty_csrc_xkpf', function () {
//
//            $doc_type = 'pbcparty_csrc_xkpf';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 一行两会 外汇局处罚
//        $csp->add('safe_chufa', function () {
//
//            $doc_type = 'safe_chufa';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 一行两会 外汇局许可
//        $csp->add('safe_xuke', function () {
//
//            $doc_type = 'safe_xuke';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 法院公告
//        $csp->add('fygg', function () {
//
//            $doc_type = 'fygg';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 开庭公告
//        $csp->add('ktgg', function () {
//
//            $doc_type = 'ktgg';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 裁判文书
//        $csp->add('cpws', function () {
//
//            $doc_type = 'cpws';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 执行公告
//        $csp->add('zxgg', function () {
//
//            $doc_type = 'zxgg';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 失信公告
//        $csp->add('shixin', function () {
//
//            $doc_type = 'shixin';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 被执行人
//        $csp->add('SearchZhiXing', function () {
//
//            $postData = [
//                'searchKey' => $this->entName,
//                'isExactlySame' => true,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CourtV4/SearchZhiXing', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 司法查冻扣
//        $csp->add('sifacdk', function () {
//
//            $doc_type = 'sifacdk';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //淘数 动产抵押
//        $csp->add('getChattelMortgageInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 20,
//            ], 'getChattelMortgageInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //淘数 股权出质
//        $csp->add('getEquityPledgedInfo', function () {
//
//            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
//                'entName' => $this->entName,
//                'pageNo' => 1,
//                'pageSize' => 20,
//            ], 'getEquityPledgedInfo');
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 企业年报 其中有对外担保 这个字段ProvideAssuranceList
//        $csp->add('GetAnnualReport', function () {
//
//            $postData = [
//                'keyNo' => $this->entName,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AR/GetAnnualReport', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;
//
//            $total = null;
//
//            if (!empty($res)) {
//                $list = [];
//
//                //不是空就找出来有没有对外担保
//                foreach ($res as $arr) {
//                    if (!isset($arr['ProvideAssuranceList']) || empty($arr['ProvideAssuranceList'])) continue;
//
//                    //如果有对外担保数据
//                    foreach ($arr['ProvideAssuranceList'] as $one) {
//                        if (count($list) < 20) {
//                            $list[] = $one;
//                        }
//
//                        $total = (int)$total + 1;
//                    }
//                }
//            }
//
//            $tmp['list'] = empty($list) ? null : $list;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //企查查 土地抵押
//        $csp->add('GetLandMortgageList', function () {
//
//            $postData = [
//                'keyWord' => $this->entName,
//                'pageIndex' => 1,
//                'pageSize' => 20,
//            ];
//
//            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandMortgage/GetLandMortgageList', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 中登动产融资 应收账款
//        $csp->add('company_zdw_yszkdsr', function () {
//
//            $doc_type = 'company_zdw_yszkdsr';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 中登动产融资 租赁登记
//        $csp->add('company_zdw_zldjdsr', function () {
//
//            $doc_type = 'company_zdw_zldjdsr';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 中登动产融资 保证金质押登记
//        $csp->add('company_zdw_bzjzydsr', function () {
//
//            $doc_type = 'company_zdw_bzjzydsr';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 中登动产融资 仓单质押
//        $csp->add('company_zdw_cdzydsr', function () {
//
//            $doc_type = 'company_zdw_cdzydsr';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 中登动产融资 所有权保留
//        $csp->add('company_zdw_syqbldsr', function () {
//
//            $doc_type = 'company_zdw_syqbldsr';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });
//
//        //法海 中登动产融资 其他动产融资
//        $csp->add('company_zdw_qtdcdsr', function () {
//
//            $doc_type = 'company_zdw_qtdcdsr';
//
//            $postData = [
//                'doc_type' => $doc_type,
//                'keyword' => $this->entName,
//                'pageno' => 1,
//                'range' => 20,
//            ];
//
//            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);
//
//            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];
//
//            if (!empty($res)) {
//                foreach ($res as &$one) {
//                    //取详情
//                    $postData = [
//                        'id' => $one['entryId'],
//                        'doc_type' => $doc_type
//                    ];
//
//                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);
//
//                    if ($detail['code'] === 200 && !empty($detail['result'])) {
//                        $one['detail'] = current($detail['result']);
//                    } else {
//                        $one['detail'] = null;
//                    }
//                }
//                unset($one);
//            }
//
//            $tmp['list'] = $res;
//            $tmp['total'] = $total;
//
//            return $tmp;
//        });

        return CspService::getInstance()->exec($csp, 10);
    }


}
