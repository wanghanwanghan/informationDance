<?php

namespace App\Task\TaskList;

use Amenadiel\JpGraph\Graph\PieGraph;
use Amenadiel\JpGraph\Plot\PiePlot;
use App\Csp\Service\CspService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use PhpOffice\PhpWord\TemplateProcessor;

class CreateEasyReportTask extends TaskBase implements TaskInterface
{
    private $entName;
    private $reportNum;

    function __construct($entName, $reportNum)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $tmp1 = new TemplateProcessor(REPORT_MODEL_PATH . 'EasyReportModel_1.docx');

        $tmp1->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'logo.jpg', 'width' => 200, 'height' => 40]);

        $tmp1->setValue('entName', $this->entName);

        $tmp1->setValue('reportNum', $this->reportNum);

        $tmp1->setValue('time', Carbon::now()->format('Y年m月d日'));

        $tmp1->saveAs(REPORT_PATH . $this->reportNum . '_123.docx');


        $this->cspHandleData();


        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

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

        //淘数 基本信息 股东信息
        $csp->add('getShareHolderInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getShareHolderInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 变更信息
        $csp->add('getRegisterChangeInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getRegisterChangeInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 实际控制人
        $csp->add('Beneficiary', function () {

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
        $csp->add('getHistoricalEvolution', function () {

            $res = XinDongService::getInstance()->getHistoricalEvolution($this->entName);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外投资
        $csp->add('lawPersonInvestmentInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'lawPersonInvestmentInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外任职
        $csp->add('getLawPersontoOtherInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getLawPersontoOtherInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 企业对外投资
        $csp->add('getInvestmentAbroadInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getInvestmentAbroadInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 分支机构
        $csp->add('getBranchInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getBranchInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 银行信息
        $csp->add('GetCreditCodeNew', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICreditCode/GetCreditCodeNew', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 银行信息
        $csp->add('GetCreditCodeNew', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICreditCode/GetCreditCodeNew', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 招投标
        $csp->add('TenderSearch', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Tender/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 购地信息
        $csp->add('LandPurchaseList', function () {

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
        $csp->add('LandPublishList', function () {

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
        $csp->add('LandTransferList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 建筑资质证书
        $csp->add('Qualification', function () {

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
        $csp->add('BuildingProject', function () {

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
        $csp->add('BondList', function () {

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
        $csp->add('GetCompanyWebSite', function () {

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
        $csp->add('Microblog', function () {

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
        $csp->add('CompanyNews', function () {

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
        $csp->add('itemInfo', function () {

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
                        $res[] = ['year' => $yearArr[$i], 'yoy' => ($SOCNUM_1 - $SOCNUM_2) / $SOCNUM_2];
                    } else {
                        $res[] = ['year' => $yearArr[$i], 'yoy' => null];
                    }
                }
            } else {
                $res = null;
            }

            return $res;
        });

        //企查查 建筑企业-专业注册人员
        $csp->add('BuildingRegistrar', function () {

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
        $csp->add('Recruitment', function () {

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
        $csp->add('FinanceData', function () {

            $postData = ['entName' => $this->entName];

            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);

            if ($res['code'] === 200 && !empty($res['result'])) {
                $res = (new QianQiService())->toPercent($res['result']);
            } else {
                $res = null;
            }

            return $res;
        });

        //
        //
        //
        //
        //

        $res = CspService::getInstance()->exec($csp);




        $FinanceData = $res['FinanceData'];

        $graph = new PieGraph(350, 250);

        $graph->title->Set("A Simple Pie Plot");
        $graph->SetBox(true);

        $data = [40, 21, 17, 14, 23];
        $p1   = new PiePlot($data);
        $p1->ShowBorder();
        $p1->SetColor('black');
        $p1->SetSliceColors(['#1E90FF', '#2E8B57', '#ADFF2F', '#DC143C', '#BA55D3']));

        $graph->Add($p1);
        $graph->Stroke('wang.jpg');


    }


}
