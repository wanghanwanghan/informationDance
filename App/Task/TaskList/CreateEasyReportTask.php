<?php

namespace App\Task\TaskList;

use App\Csp\Service\CspService;
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

        $csp->add('getRegisterInfo', function () {
            return (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $this->entName], 'getRegisterInfo');
        });

        $csp->add('123', function () {

        });


        var_dump(CspService::getInstance()->exec($csp));
    }


}
