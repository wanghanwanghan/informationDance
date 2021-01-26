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
    private $dataIndex;

    function __construct($entName, $reportNum, $phone, $type, $dataIndex)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;
        $this->phone = $phone;
        $this->type = $type;
        $this->dataIndex = $dataIndex;

        return parent::__construct();
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
        $pdf->SetMargins(5, 5, 5);//页面间隔
        $pdf->SetHeaderMargin(5);//页眉top间隔
        $pdf->SetFooterMargin(5);//页脚bottom间隔

        // 设置分页
        $pdf->SetAutoPageBreak(true, 25);

        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        //设置字体 stsongstdlight支持中文
        $pdf->SetFont('stsongstdlight', '', $this->pdf_BigTitle);

        $pdf->setJPEGQuality(100);

        $res = $this->cspHandleData($this->dataIndex);

        $this->fillData($pdf, $res);

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
        CommonService::getInstance()->log4PHP($cspReturnData);

        $pdf->AddPage();

        //logo
        $pdf->Image(REPORT_IMAGE_PATH . 'logo.jpg', '', '', 55, 20, '', '', 'T');

        //换行
        $pdf->ln(100);

        //entName
        $pdf->SetFont('stsongstdlight', '', $this->pdf_BigTitle);
        $pdf->writeHTML("<div>{$this->entName}</div>", true, false, false, false, 'C');

        //换行
        $pdf->ln(60);

        $pdf->SetFont('stsongstdlight', '', $this->pdf_Text);
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse">
    <tr>
        <td width="100">
            报告编号
        </td>
        <td>
            20210121202154
        </td>
    </tr>
    <tr>
        <td width="100">
            查询单位
        </td>
        <td>
            民族基地风险监控专用
        </td>
    </tr>
</table>
TEMP;

        $pdf->writeHTML($html, true, false, false, false, 'C');


        //##########################################################################################//
    }

    //并发请求数据
    private function cspHandleData($indexStr = '')
    {
        $catalog = $this->pdf_Catalog();

        //创建csp对象
        $csp = CspService::getInstance()->create();

        //淘数 基本信息 工商信息
        array_search('getRegisterInfo', $catalog) === false ?: $csp->add('getRegisterInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $this->entName], 'getRegisterInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

            return $res;
        });

        //企查查 基本信息 工商信息
        array_search('GetBasicDetailsByName', $catalog) === false ?: $csp->add('GetBasicDetailsByName', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIV4/GetBasicDetailsByName', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 股东信息
        array_search('getShareHolderInfo', $catalog) === false ?: $csp->add('getShareHolderInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getShareHolderInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 高管信息
        array_search('getMainManagerInfo', $catalog) === false ?: $csp->add('getMainManagerInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getMainManagerInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 变更信息
        array_search('getRegisterChangeInfo', $catalog) === false ?: $csp->add('getRegisterChangeInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getRegisterChangeInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 经营异常
        array_search('GetOpException', $catalog) === false ?: $csp->add('GetOpException', function () {

            $postData = ['keyNo' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIException/GetOpException', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 实际控制人
        array_search('Beneficiary', $catalog) === false ?: $csp->add('Beneficiary', function () {

            $postData = [
                'companyName' => $this->entName,
                'percent' => 0,
                'mode' => 0,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Beneficiary/GetBeneficiary', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            $tmp = [];

            if (count($res['BreakThroughList']) > 0) {
                $total = current($res['BreakThroughList']);
                $total = substr($total['TotalStockPercent'], 0, -1);

                if ($total >= 50) {
                    //如果第一个人就是大股东了，就直接返回
                    $tmp = current($res['BreakThroughList']);

                } else {
                    //把返回的所有人加起来和100做减法，求出坑
                    $hole = 100;
                    foreach ($res['BreakThroughList'] as $key => $val) {
                        $hole -= substr($val['TotalStockPercent'], 0, -1);
                    }

                    //求出坑的比例，如果比第一个人大，那就是特殊机构，如果没第一个人大，那第一个人就是控制人
                    if ($total > $hole) $tmp = current($res['BreakThroughList']);
                }
            } else {
                $tmp = null;
            }

            return $tmp;
        });

        //淘数 企查查 历史沿革
        array_search('getHistoricalEvolution', $catalog) === false ?: $csp->add('getHistoricalEvolution', function () {

            $res = XinDongService::getInstance()->getHistoricalEvolution($this->entName);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外投资
        array_search('lawPersonInvestmentInfo', $catalog) === false ?: $csp->add('lawPersonInvestmentInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'lawPersonInvestmentInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外任职
        array_search('getLawPersontoOtherInfo', $catalog) === false ?: $csp->add('getLawPersontoOtherInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getLawPersontoOtherInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 企业对外投资
        array_search('getInvestmentAbroadInfo', $catalog) === false ?: $csp->add('getInvestmentAbroadInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getInvestmentAbroadInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //淘数 分支机构
        array_search('getBranchInfo', $catalog) === false ?: $csp->add('getBranchInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getBranchInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 银行信息
        array_search('GetCreditCodeNew', $catalog) === false ?: $csp->add('GetCreditCodeNew', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICreditCode/GetCreditCodeNew', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 公司概况
        array_search('SearchCompanyFinancings', $catalog) === false ?: $csp->add('SearchCompanyFinancings', function () {

            $postData = ['searchKey' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BusinessStateV4/SearchCompanyFinancings', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 招投标
        array_search('TenderSearch', $catalog) === false ?: $csp->add('TenderSearch', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Tender/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 购地信息
        array_search('LandPurchaseList', $catalog) === false ?: $csp->add('LandPurchaseList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandPurchase/LandPurchaseList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 土地公示
        array_search('LandPublishList', $catalog) === false ?: $csp->add('LandPublishList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandPublish/LandPublishList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 土地转让
        array_search('LandTransferList', $catalog) === false ?: $csp->add('LandTransferList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $post = ['id' => $one['Id']];
                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferDetail', $post);
                    ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = $detail['result'] : $detail = null;
                    $one['detail'] = $detail;
                }
                unset($one);
            }

            return $res;
        });

        //企查查 建筑资质证书
        array_search('Qualification', $catalog) === false ?: $csp->add('Qualification', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Qualification/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 建筑工程项目
        array_search('BuildingProject', $catalog) === false ?: $csp->add('BuildingProject', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BuildingProject/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 债券信息
        array_search('BondList', $catalog) === false ?: $csp->add('BondList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Bond/BondList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 网站信息
        array_search('GetCompanyWebSite', $catalog) === false ?: $csp->add('GetCompanyWebSite', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'WebSiteV4/GetCompanyWebSite', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 微博
        array_search('Microblog', $catalog) === false ?: $csp->add('Microblog', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Microblog/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 新闻舆情
        array_search('CompanyNews', $catalog) === false ?: $csp->add('CompanyNews', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CompanyNews/SearchNews', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //乾启 近三年团队人数变化率
        array_search('itemInfo', $catalog) === false ?: $csp->add('itemInfo', function () {

            $postData = ['entName' => $this->entName];

            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);

            if ($res['code'] === 200 && !empty($res['result'])) {

                $yearArr = array_keys($res['result']);
                $dataArr = array_values($res['result']);
                $res = [];

                for ($i = 0; $i < 3; $i++) {

                    if (isset($dataArr[$i]['SOCNUM']) && is_numeric($dataArr[$i]['SOCNUM'])) {
                        $SOCNUM_1 = (int)$dataArr[$i]['SOCNUM'];
                    } else {
                        $SOCNUM_1 = null;
                    }

                    if (isset($dataArr[$i + 1]['SOCNUM']) && is_numeric($dataArr[$i + 1]['SOCNUM'])) {
                        $SOCNUM_2 = (int)$dataArr[$i + 1]['SOCNUM'];
                    } else {
                        $SOCNUM_2 = null;
                    }

                    if ($SOCNUM_1 !== null && $SOCNUM_2 !== null && $SOCNUM_2 !== 0) {
                        $res[] = ['year' => $yearArr[$i], 'yoy' => ($SOCNUM_1 - $SOCNUM_2) / $SOCNUM_2, 'num' => $SOCNUM_1];
                    } else {
                        $res[] = ['year' => $yearArr[$i], 'yoy' => null, 'num' => $SOCNUM_1];
                    }
                }

            } else {
                $res = null;
            }

            return $res;
        });

        //企查查 建筑企业-专业注册人员
        array_search('BuildingRegistrar', $catalog) === false ?: $csp->add('BuildingRegistrar', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BuildingRegistrar/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 招聘信息
        array_search('Recruitment', $catalog) === false ?: $csp->add('Recruitment', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Recruitment/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //乾启 财务
        array_search('FinanceData', $catalog) === false ?: $csp->add('FinanceData', function () {

            $postData = ['entName' => $this->entName];

            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);

            if ($res['code'] === 200 && !empty($res['result'])) {
                $res = (new QianQiService())->toPercent($res['result']);
            } else {
                $res = null;
            }

            if ($res === null) return $res;

            $count1 = 0;

            ksort($res);

            foreach ($res as $year => $dataArr) {
                $legend[] = $year;
                array_pop($dataArr);
                $tmp = array_map(function ($val) {
                    return is_numeric($val) ? (int)round($val) : null;//四舍五入
                }, array_values($dataArr));
                $data[] = $tmp;
                !empty(array_filter($tmp)) ?: $count1++;
            }

            $labels = ['资产总额', '负债总额', '营业总收入', '主营业务收入', '利润总额', '净利润', '纳税总额', '所有者权益'];

            $extension = [
                'width' => 1200,
                'height' => 700,
                'title' => $count1 == 2 ? '缺少上一年财务数据，财务图表未生成' : $this->entName . ' - 财务非授权 - 同比',
                'xTitle' => '此图为概况信息',
                //'yTitle'=>$this->entName,
                'titleSize' => 14,
                'legend' => $legend
            ];

            $tmp = [];
            $tmp['pic'] = CommonService::getInstance()->createBarPic($data, $labels, $extension);
            $tmp['data'] = $data;

            return $tmp;
        });

        //企查查 业务概况
        array_search('SearchCompanyCompanyProducts', $catalog) === false ?: $csp->add('SearchCompanyCompanyProducts', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CompanyProductV4/SearchCompanyCompanyProducts', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 专利
        array_search('PatentV4Search', $catalog) === false ?: $csp->add('PatentV4Search', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'PatentV4/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 软件著作权
        array_search('SearchSoftwareCr', $catalog) === false ?: $csp->add('SearchSoftwareCr', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchSoftwareCr', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 商标
        array_search('tmSearch', $catalog) === false ?: $csp->add('tmSearch', function () {

            $postData = [
                'keyword' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'tm/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 作品著作权
        array_search('SearchCopyRight', $catalog) === false ?: $csp->add('SearchCopyRight', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchCopyRight', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 证书资质
        array_search('SearchCertification', $catalog) === false ?: $csp->add('SearchCertification', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICertification/SearchCertification', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 纳税信用等级
        array_search('satparty_xin', $catalog) === false ?: $csp->add('satparty_xin', function () {

            $doc_type = 'satparty_xin';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 税务许可信息
        array_search('satparty_xuke', $catalog) === false ?: $csp->add('satparty_xuke', function () {

            $doc_type = 'satparty_xuke';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 税务登记信息
        array_search('satparty_reg', $catalog) === false ?: $csp->add('satparty_reg', function () {

            $doc_type = 'satparty_reg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 税务非正常户
        array_search('satparty_fzc', $catalog) === false ?: $csp->add('satparty_fzc', function () {

            $doc_type = 'satparty_fzc';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 欠税信息
        array_search('satparty_qs', $catalog) === false ?: $csp->add('satparty_qs', function () {

            $doc_type = 'satparty_qs';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 涉税处罚公示
        array_search('satparty_chufa', $catalog) === false ?: $csp->add('satparty_chufa', function () {

            $doc_type = 'satparty_chufa';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 行政许可
        array_search('GetAdministrativeLicenseList', $catalog) === false ?: $csp->add('GetAdministrativeLicenseList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = ['id' => $one['Id']];

                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseDetail', $postData);

                    if ($detail['code'] == 200 && !empty($detail['result'])) {
                        $one['detail'] = $detail['result'];
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 行政处罚
        array_search('GetAdministrativePenaltyList', $catalog) === false ?: $csp->add('GetAdministrativePenaltyList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = ['id' => $one['Id']];

                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyDetail', $postData);

                    if ($detail['code'] == 200 && !empty($detail['result'])) {
                        $one['detail'] = $detail['result'];
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 环保处罚
        array_search('epbparty', $catalog) === false ?: $csp->add('epbparty', function () {

            $doc_type = 'epbparty';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 重点监控企业名单
        array_search('epbparty_jkqy', $catalog) === false ?: $csp->add('epbparty_jkqy', function () {

            $doc_type = 'epbparty_jkqy';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 环保企业自行监测结果
        array_search('epbparty_zxjc', $catalog) === false ?: $csp->add('epbparty_zxjc', function () {

            $doc_type = 'epbparty_zxjc';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 环评公示数据
        array_search('epbparty_huanping', $catalog) === false ?: $csp->add('epbparty_huanping', function () {

            $doc_type = 'epbparty_huanping';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关企业
        array_search('custom_qy', $catalog) === false ?: $csp->add('custom_qy', function () {

            $doc_type = 'custom_qy';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关许可
        array_search('custom_xuke', $catalog) === false ?: $csp->add('custom_xuke', function () {

            $doc_type = 'custom_xuke';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关信用
        array_search('custom_credit', $catalog) === false ?: $csp->add('custom_credit', function () {

            $doc_type = 'custom_credit';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关处罚
        array_search('custom_punish', $catalog) === false ?: $csp->add('custom_punish', function () {

            $doc_type = 'custom_punish';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 央行行政处罚
        array_search('pbcparty', $catalog) === false ?: $csp->add('pbcparty', function () {

            $doc_type = 'pbcparty';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 银保监会处罚公示
        array_search('pbcparty_cbrc', $catalog) === false ?: $csp->add('pbcparty_cbrc', function () {

            $doc_type = 'pbcparty_cbrc';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 证监处罚公示
        array_search('pbcparty_csrc_chufa', $catalog) === false ?: $csp->add('pbcparty_csrc_chufa', function () {

            $doc_type = 'pbcparty_csrc_chufa';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 证监会许可信息
        array_search('pbcparty_csrc_xkpf', $catalog) === false ?: $csp->add('pbcparty_csrc_xkpf', function () {

            $doc_type = 'pbcparty_csrc_xkpf';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 外汇局处罚
        array_search('safe_chufa', $catalog) === false ?: $csp->add('safe_chufa', function () {

            $doc_type = 'safe_chufa';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 外汇局许可
        array_search('safe_xuke', $catalog) === false ?: $csp->add('safe_xuke', function () {

            $doc_type = 'safe_xuke';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 法院公告
        array_search('fygg', $catalog) === false ?: $csp->add('fygg', function () {

            $doc_type = 'fygg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 开庭公告
        array_search('ktgg', $catalog) === false ?: $csp->add('ktgg', function () {

            $doc_type = 'ktgg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 裁判文书
        array_search('cpws', $catalog) === false ?: $csp->add('cpws', function () {

            $doc_type = 'cpws';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 执行公告
        array_search('zxgg', $catalog) === false ?: $csp->add('zxgg', function () {

            $doc_type = 'zxgg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 失信公告
        array_search('shixin', $catalog) === false ?: $csp->add('shixin', function () {

            $doc_type = 'shixin';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 被执行人
        array_search('SearchZhiXing', $catalog) === false ?: $csp->add('SearchZhiXing', function () {

            $postData = [
                'searchKey' => $this->entName,
                'isExactlySame' => true,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CourtV4/SearchZhiXing', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 司法查冻扣
        array_search('sifacdk', $catalog) === false ?: $csp->add('sifacdk', function () {

            $doc_type = 'sifacdk';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //淘数 动产抵押
        array_search('getChattelMortgageInfo', $catalog) === false ?: $csp->add('getChattelMortgageInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 20,
            ], 'getChattelMortgageInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //淘数 股权出质
        array_search('getEquityPledgedInfo', $catalog) === false ?: $csp->add('getEquityPledgedInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 20,
            ], 'getEquityPledgedInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 企业年报 其中有对外担保 这个字段ProvideAssuranceList
        array_search('GetAnnualReport', $catalog) === false ?: $csp->add('GetAnnualReport', function () {

            $postData = [
                'keyNo' => $this->entName,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AR/GetAnnualReport', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            $total = null;

            if (!empty($res)) {
                $list = [];

                //不是空就找出来有没有对外担保
                foreach ($res as $arr) {
                    if (!isset($arr['ProvideAssuranceList']) || empty($arr['ProvideAssuranceList'])) continue;

                    //如果有对外担保数据
                    foreach ($arr['ProvideAssuranceList'] as $one) {
                        if (count($list) < 20) {
                            $list[] = $one;
                        }

                        $total = (int)$total + 1;
                    }
                }
            }

            $tmp['list'] = empty($list) ? null : $list;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 土地抵押
        array_search('GetLandMortgageList', $catalog) === false ?: $csp->add('GetLandMortgageList', function () {

            $postData = [
                'keyWord' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandMortgage/GetLandMortgageList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 应收账款
        array_search('company_zdw_yszkdsr', $catalog) === false ?: $csp->add('company_zdw_yszkdsr', function () {

            $doc_type = 'company_zdw_yszkdsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 租赁登记
        array_search('company_zdw_zldjdsr', $catalog) === false ?: $csp->add('company_zdw_zldjdsr', function () {

            $doc_type = 'company_zdw_zldjdsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 保证金质押登记
        array_search('company_zdw_bzjzydsr', $catalog) === false ?: $csp->add('company_zdw_bzjzydsr', function () {

            $doc_type = 'company_zdw_bzjzydsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 仓单质押
        array_search('company_zdw_cdzydsr', $catalog) === false ?: $csp->add('company_zdw_cdzydsr', function () {

            $doc_type = 'company_zdw_cdzydsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 所有权保留
        array_search('company_zdw_syqbldsr', $catalog) === false ?: $csp->add('company_zdw_syqbldsr', function () {

            $doc_type = 'company_zdw_syqbldsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 其他动产融资
        array_search('company_zdw_qtdcdsr', $catalog) === false ?: $csp->add('company_zdw_qtdcdsr', function () {

            $doc_type = 'company_zdw_qtdcdsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        return CspService::getInstance()->exec($csp, 15);
    }


}
