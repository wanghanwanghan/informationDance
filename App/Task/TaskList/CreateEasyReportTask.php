<?php

namespace App\Task\TaskList;

use App\Csp\Service\CspService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
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

            return $res;
//            $tmp = [];
//
//            if (count($res['Result']['BreakThroughList']) > 0) {
//                $total = current($res['Result']['BreakThroughList']);
//                $total = substr($total['TotalStockPercent'], 0, -1);
//
//                if ($total >= 50) {
//                    //如果第一个人就是大股东了，就直接返回
//                    $tmp = $res['Result']['BreakThroughList'][0];
//
//                } else {
//                    //把返回的所有人加起来和100做减法，求出坑
//                    $hole = 100;
//                    foreach ($res['Result']['BreakThroughList'] as $key => $val) {
//                        $hole -= substr($val['TotalStockPercent'], 0, -1);
//                    }
//
//                    //求出坑的比例，如果比第一个人大，那就是特殊机构，如果没第一个人大，那第一个人就是控制人
//                    if ($total > $hole) $tmp = $res['Result']['BreakThroughList'][0];
//                }
//            }
//
//            $res['Result'] = $tmp;

        });

        $csp->add('123', function () {

        });

        $res = CspService::getInstance()->exec($csp);

        var_export($res['Beneficiary']);
    }


}
