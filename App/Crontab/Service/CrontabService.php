<?php

namespace App\Crontab\Service;

use App\Crontab\CrontabList\CreateDeepReport;
use App\Crontab\CrontabList\DeleteTimeoutOrder;
use App\Crontab\CrontabList\GetAuthBook;
use App\Crontab\CrontabList\GetInvData;
use App\Crontab\CrontabList\MoveOut;
use App\Crontab\CrontabList\RunSaiMengHuiZhiCaiWu;
use App\Crontab\CrontabList\RunSouKeUploadFiles;
use App\Crontab\CrontabList\RunSupervisor;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Crontab\Crontab;

class CrontabService
{
    use Singleton;

    //只能在mainServerCreate中调用
    function create(): bool
    {
        $this->createDeepReport();
        $this->deleteTimeoutOrder();
        $this->runSupervisor();
        $this->runMoveOut();
        $this->getAuthBook();
        $this->getInvData();//123123123
        $this->RunSaiMengHuiZhiCaiWu();
        $this->RunSouKeUploadFiles();

        return true;
    }

    //生成深度报告
    private function createDeepReport(): Crontab
    {
        return Crontab::getInstance()->addTask(CreateDeepReport::class);
    }

    //删除待支付订单
    private function deleteTimeoutOrder(): Crontab
    {
        return Crontab::getInstance()->addTask(DeleteTimeoutOrder::class);
    }

    //风险监控
    private function runSupervisor(): Crontab
    {
        return Crontab::getInstance()->addTask(RunSupervisor::class);
    }

    //迁出
    private function runMoveOut(): Crontab
    {
        return Crontab::getInstance()->addTask(MoveOut::class);
    }

    private function getAuthBook(): Crontab
    {
        return Crontab::getInstance()->addTask(GetAuthBook::class);
    }

    private function getInvData(): Crontab
    {
        return Crontab::getInstance()->addTask(GetInvData::class);
    }

    private function RunSaiMengHuiZhiCaiWu(): Crontab
    {
        return Crontab::getInstance()->addTask(RunSaiMengHuiZhiCaiWu::class);
    }

    private function RunSouKeUploadFiles(): Crontab
    {
        return Crontab::getInstance()->addTask(RunSouKeUploadFiles::class);
    }

}
