<?php

namespace App\Crontab\Service;

use App\Crontab\CrontabList\CreateDeepReport;
use App\Crontab\CrontabList\DeleteTimeoutOrder;
use App\Crontab\CrontabList\FillEntAllField;
use App\Crontab\CrontabList\GetAuthBook;
use App\Crontab\CrontabList\GetAuthBookDZQ;
use App\Crontab\CrontabList\GetInvData;
use App\Crontab\CrontabList\GetInvDataJinCai;
use App\Crontab\CrontabList\MoveOut;
use App\Crontab\CrontabList\RunDianZiQianGetPdf;
use App\Crontab\CrontabList\RunJinCaiShuKeRWH;
use App\Crontab\CrontabList\RunSaiMengHuiZhiCaiWu;
use App\Crontab\CrontabList\RunSouKeUploadFiles;
use App\Crontab\CrontabList\RunCompleteCompanyData;
use App\Crontab\CrontabList\RunDealFinanceCompanyDataNewV2;
use App\Crontab\CrontabList\RunFillCompanyName;
use App\Crontab\CrontabList\RunDealApiSouKe;
use App\Crontab\CrontabList\RunDealToolsFile;
use App\Crontab\CrontabList\RunReadAndDealXls;
use App\Crontab\CrontabList\RunSupervisor;
use App\Crontab\CrontabList\RunShouQuanCheXian;
use App\Crontab\CrontabList\RunDealZhaoTouBiao;
use App\Crontab\CrontabList\RunDealEmailReceiver;
use App\Crontab\CrontabList\RunDealCarInsuranceInstallment;
use App\Crontab\CrontabList\RunDealBussinessOpportunity;

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
//        $this->getAuthBook();
        $this->getAuthBookDZQ();
        $this->RunDianZiQianGetPdf();
        $this->getInvData();//123123123
        $this->GetInvDataJinCai();//金财取数
        $this->RunSaiMengHuiZhiCaiWu();
        $this->RunSouKeUploadFiles();
        $this->RunCompleteCompanyData();
        $this->RunFillCompanyName();
        $this->RunReadAndDealXls();
        $this->RunShouQuanCheXian();
        $this->FillEntAllField();//补全给筛选出的企业 <全字段>RunDealFinanceCompanyData
        $this->RunDealFinanceCompanyDataNew();//补全给筛选出的企业 <全字段>
        $this->RunJinCaiShuKeRWH();//金财数科通过任务号获取发票
        $this->RunDealApiSouKe();//新APi-搜客模块相关的定时
        $this->RunDealToolsFile();//新APi-搜客模块相关的定时
        $this->RunDealZhaoTouBiao();//新APi-搜客模块相关的定时
        $this->RunDealEmailReceiver();//
        $this->RunDealCarInsuranceInstallment();//
        $this->RunDealBussinessOpportunity();//
        return true;
    }

    private function RunJinCaiShuKeRWH():Crontab
    {
        return Crontab::getInstance()->addTask(RunJinCaiShuKeRWH::class);
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
    private function getAuthBookDZQ(): Crontab
    {
        return Crontab::getInstance()->addTask(GetAuthBookDZQ::class);
    }

    private function getInvData(): Crontab
    {
        return Crontab::getInstance()->addTask(GetInvData::class);
    }

    private function GetInvDataJinCai(): Crontab
    {
        return Crontab::getInstance()->addTask(GetInvDataJinCai::class);
    }

    private function RunDianZiQianGetPdf(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDianZiQianGetPdf::class);
    }

    private function RunSaiMengHuiZhiCaiWu(): Crontab
    {
        return Crontab::getInstance()->addTask(RunSaiMengHuiZhiCaiWu::class);
    }

    private function RunSouKeUploadFiles(): Crontab
    {
        return Crontab::getInstance()->addTask(RunSouKeUploadFiles::class);
    }

    private function RunCompleteCompanyData(): Crontab
    {
        return Crontab::getInstance()->addTask(RunCompleteCompanyData::class);
    }

    private function RunFillCompanyName(): Crontab
    {
        return Crontab::getInstance()->addTask(RunFillCompanyName::class);
    }

    private function RunReadAndDealXls(): Crontab
    {
        return Crontab::getInstance()->addTask(RunReadAndDealXls::class);
    }

    private function RunShouQuanCheXian(): Crontab
    {
        return Crontab::getInstance()->addTask(RunShouQuanCheXian::class);
    }

    private function FillEntAllField(): Crontab
    {
        return Crontab::getInstance()->addTask(FillEntAllField::class);
    }

    private function RunDealFinanceCompanyDataNew(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDealFinanceCompanyDataNewV2::class);
    }

    //后台搜客模块-相关定时任务
    private function RunDealApiSouKe(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDealApiSouKe::class);
    }

    //新api系统-工具类定时
    private function RunDealToolsFile(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDealToolsFile::class);
    }

    //新api系统-工具类定时
    private function RunDealZhaoTouBiao(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDealZhaoTouBiao::class);
    }

    private function RunDealEmailReceiver(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDealEmailReceiver::class);
    }
    private function RunDealCarInsuranceInstallment(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDealCarInsuranceInstallment::class);
    }

    private function RunDealBussinessOpportunity(): Crontab
    {
        return Crontab::getInstance()->addTask(RunDealBussinessOpportunity::class);
    }

}
