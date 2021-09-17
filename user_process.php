<?php

use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\Common\CommonService;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class user_process extends AbstractProcess
{
    protected function run($arg)
    {
        $NSRSBH = '911199999999CN0008';
        $FPLXDM = 'type1';
        $invType = '01';

        $arr = '{"FPDM":"5000192130","FPHM":"09994864","KPRQ":"2021-04-12","FPLXDM":"01","JYM":"76236963391546246318","JQBH":null,"HJJE":"59358.67","HJSE":"7716.63","JSHJ":"67075.30","FPZT":"1","HLPBS":"0","YFPDM":"","YFPHM":"","SKR":"","FHR":"","KPR":"","BZ":"无纺布手提袋、围裙订制","RZZT":"1","RZRQ":"20200222","RZSQ":"202001","RZFS":null,"GMFYHZH":"购方银行账号9999999999999999","GMFMC":"测试专用购方名称","GMFSBH":"911199999999CN0008","GMFLX":null,"GMFSF":null,"GMFSJ":null,"GMFWX":null,"GMFYX":null,"GMFDZDH":"购方地址电话11111111111","XHFMC":"测试专用销方名称","XHFSBH":"911199999999CN0007","XHFDZDH":"销方地址电话888888888888","XHFYHZH":"销方银行账号77777777777","QDBS":"0","FPMX":[{"FPHXZ":"0","SPBM":null,"SPMC":"*纺织产品*制作费","GGXH":"","DW":"","SPSL":"","DJ":"","HSDJ":null,"JE":"59358.67","SL":"0.13","SE":"7716.63"}]}';

        $arr = jsonDecode($arr);

        //一张发票只属于一个公司的进项和另一个公司的销项
        $check_exists = EntInvoice::create()->addSuffix($NSRSBH, $FPLXDM)->where([
            'fpdm' => $arr['FPDM'],
            'fphm' => $arr['FPHM'],
            'direction' => $invType,//01-购买方 02-销售方
        ])->get();
        if (!empty($check_exists)) return false;//已经存在了
        $insert = [
            'fpdm' => changeNull($arr['FPDM']),//'发票代码',
            'fphm' => changeNull($arr['FPHM']),//'发票号码',
            'kplx' => changeNull($arr['HLPBS']),//'开票类型 0-蓝字 1-红字',
            'xfsh' => changeNull($arr['XHFSBH']),//'销售方纳税人识别号',
            'xfmc' => changeNull($arr['XHFMC']),//'销售方名称',
            'xfdzdh' => changeNull($arr['XHFDZDH']),//'销售方地址电话',
            'xfyhzh' => changeNull($arr['XHFYHZH']),//'销售方银行账号',
            'gfsh' => changeNull($arr['GMFSBH']),//'购买方纳税人识别号',
            'gfmc' => changeNull($arr['GMFMC']),//'购买方名称',
            'gfdzdh' => changeNull($arr['GMFDZDH']),//'购买方地址电话',
            'gfyhzh' => changeNull($arr['GMFYHZH']),//'购买方银行账号',
            'gmflx' => changeNull(changeGMFLX($arr['GMFLX'])),//'购买方类型 1企业 2个人 3其他',
            'kpr' => changeNull($arr['KPR']),//'开票人',
            'skr' => changeNull($arr['SKR']),//'收款人',
            'fhr' => changeNull($arr['FHR']),//'复核人',
            'yfpdm' => changeNull($arr['YFPDM']),//'原发票代码 kplx为1时必填',
            'yfphm' => changeNull($arr['YFPHM']),//'原发票号码 kplx为1时必填',
            'je' => changeNull($arr['HJJE']),//'金额',
            'se' => changeNull($arr['HJSE']),//'税额',
            'jshj' => changeNull($arr['JSHJ']),//'价税合计 单位元 2位小数',
            'bz' => changeNull($arr['BZ']),//'备注',
            'zfbz' => changeNull(changeFPZT($arr['FPZT'])) === '2' ? '1' : '0',//'作废标志 0-未作废 1-作废',
            'zfsj' => '',//'作废时间',
            'kprq' => changeNull($arr['KPRQ']),//'开票日期',
            'kprq_sort' => microTimeNew() - 0,//'排序用',
            'fplx' => changeNull($arr['FPLXDM']),//'发票类型代码 01 02 03 04 10 11 14 15',
            'fpztDm' => changeNull(changeFPZT($arr['FPZT'])),//'发票状态代码 0-正常 1-失控 2-作废 3-红字 4-异常票',
            'slbz' => (is_numeric(changeNull($arr['HJSE'])) && changeNull($arr['HJSE']) > 0) ? '1' : '0',//'税率标识 0-不含税税率 1-含税税率',
            'rzdklBdjgDm' => changeNull($arr['RZZT']),//'认证状态 0-未认证 1-已认证 2-已认证未抵扣',
            'rzdklBdrq' => changeNull($arr['RZRQ']),//'认证日期',
            'direction' => $invType,//'01-购买方 02-销售方',
            'nsrsbh' => $NSRSBH,//'查询企业税号',
            'jym' => changeNull($arr['JYM']),//'校验码',
            'jqbh' => changeNull($arr['JQBH']),//'机器编号',
            'rzsq' => changeNull($arr['RZSQ']),//'认证归属期',
            'rzfs' => changeNull($arr['RZFS']),//'认证方式 1-勾选认证 2-扫描认证',
            'gmfsf' => changeNull($arr['GMFSF']),//'购买方省份',
            'gmfsj' => changeNull($arr['GMFSJ']),//'购买方手机',
            'gmfwx' => changeNull($arr['GMFWX']),//'购买方微信',
            'gmfyx' => changeNull($arr['GMFYX']),//'购买方邮箱',
            'qdbs' => changeNull($arr['QDBS']),//'是否有销货清单 0否 1是 默认为0',
        ];
        $insert_detail = [];
        if (!empty($arr['FPMX'])) {
            //先要含有明细
            $dm = changeNull($arr['FPDM']);
            $hm = changeNull($arr['FPHM']);
            if (!empty($dm) && !empty($hm)) {
                //发票代码和号码不能错误
                $check_exists = EntInvoiceDetail::create()->addSuffix($dm, $hm, $FPLXDM)->where([
                    'fpdm' => $dm,
                    'fphm' => $hm,
                ])->get();
                if (empty($check_exists)) {
                    //没存过明细才会存
                    $i_num = 1;
                    foreach ($arr['FPMX'] as $oneDetail) {
                        $insert_detail[] = [
                            'spbm' => changeNull($oneDetail['SPBM']),//'税收分类编码',
                            'mc' => changeNull($oneDetail['SPMC']),//'如果为折扣行 商品名称须与被折扣行的商品名称相同 不能多行折扣',
                            'jldw' => changeNull($oneDetail['DW']),//'单位',
                            'shul' => changeNull($oneDetail['SPSL']),//'数量 6位小数',
                            'je' => changeNull($oneDetail['JE']),//'含税金额 2位小数',
                            'sl' => changeNull($oneDetail['SL']),//'税率 3位小数 例1%为0.010',
                            'se' => changeNull($oneDetail['SE']),//'税额 3位小数 例1%为0.010',
                            'dj' => changeNull($oneDetail['DJ']),//'不含税单价',
                            'ggxh' => changeNull($oneDetail['GGXH']),//'规格型号',
                            'mxxh' => $i_num,
                            'fpdm' => $dm,//'发票代码',
                            'fphm' => $hm,//'发票号码',
                            'fphxz' => changeNull($oneDetail['FPHXZ']),//'是否折扣行 0否 1是 默认0表示正常商品行',
                            'hsdj' => changeNull($oneDetail['HSDJ']),//'含税单价',
                        ];
                        $i_num++;
                    }
                }
            }
        }

        //do insert
        $conn = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');
        try {
            DbManager::getInstance()->startTransaction($conn);
            //发票主表
            EntInvoice::create()->addSuffix($NSRSBH, $FPLXDM)->data($insert)->save();
            if (!empty($insert_detail)) {
                //发票明细表
                EntInvoiceDetail::create()->addSuffix($arr['FPDM'], $arr['FPHM'], $FPLXDM)
                    ->saveAll($insert_detail, false, false);
            }
            DbManager::getInstance()->commit($conn);
        } catch (\Throwable $e) {
            DbManager::getInstance()->rollback($conn);
            return CommonService::getInstance()->log4PHP([
                'data1' => $insert,
                'data2' => $insert_detail,
                'NSRSBH' => $NSRSBH,
                'FPLXDM' => $FPLXDM,
                'invType' => $invType,
                'error' => $e->getTraceAsString(),
            ], 'doinsert', 'inv_store_mysql_error.log');
        }
    }

    function writeErr(\Throwable $e): void
    {
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content);
    }

    protected function onShutDown()
    {

    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        $this->writeErr($throwable);
    }
}

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new user_process($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
