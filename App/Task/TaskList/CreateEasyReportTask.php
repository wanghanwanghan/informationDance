<?php

namespace App\Task\TaskList;

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
        echo 'task执行'.PHP_EOL;

        var_dump(new TemplateProcessor(REPORT_MODEL_PATH . 'EasyReportModel_1.docx'));

        try
        {
            $tmp = new TemplateProcessor(REPORT_MODEL_PATH . 'EasyReportModel_1.docx');

        }catch (\Exception $e)
        {
            var_dump($e->getMessage());
        }

        echo '1'.PHP_EOL;

        $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'logo.jpg', 'width' => 200, 'height' => 40]);

        echo '2'.PHP_EOL;

        $tmp->setValue('entName', $this->entName);

        echo '3'.PHP_EOL;

        $tmp->setValue('reportNum', $this->reportNum);

        echo '4'.PHP_EOL;

        $tmp->setValue('time', Carbon::now()->format('Y年m月d日'));

        echo '5'.PHP_EOL;

        $tmp->saveAs(REPORT_PATH . $this->reportNum . '.docx');

        echo '6'.PHP_EOL;

        //企业基本信息
        $res=(new TaoShuService())->setCheckRespFlag(true)->post(['entName'=>$this->entName],'getRegisterInfo');

        echo '7'.PHP_EOL;

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
