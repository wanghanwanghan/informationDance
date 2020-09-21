<?php

namespace App\Task\TaskList;

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
        $tmp = new TemplateProcessor(REPORT_MODEL_PATH . 'EasyReportModel_1.docx');

        $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'logo.jpg', 'width' => 200, 'height' => 40]);

        $tmp->setValue('entName', $this->entName);

        $tmp->setValue('reportNum', $this->reportNum);

        $tmp->setValue('time', Carbon::now()->format('Y年m月d日'));

        $tmp->saveAs(REPORT_PATH . $this->reportNum . '.docx');

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
