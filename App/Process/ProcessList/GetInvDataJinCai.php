<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\JinCaiRwh;
use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\OSS\OSSService;
use App\HttpController\Service\Zip\ZipService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;
use wanghanwanghan\someUtils\control;

class GetInvDataJinCai extends ProcessBase
{
    const ProcessNum = 3;
    const QueueKey = 'JinCaiWuPanRwh';

    public $p_index;
    public $currentAesKey;
    public $iv = '1234567890abcdef';
    public $redisKey;
    public $readToSendAntFlag;
    public $oss_expire_time = 86400 * 60;
    public $oss_bucket = 'invoice-mrxd';
    public $taxNo = '91110108MA01KPGK0L';

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        //获取注册进程名称
        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;

        $redis = Redis::defer('redis');
        $redis->select(15);

        // 消费
        while (true) {
            $rwh_info = $redis->rPop(self::QueueKey);
            if (empty($rwh_info)) {
                \co::sleep(60);
                continue;
            }
            $this->getDataByJinCai(jsonDecode($rwh_info));
        }

    }

    //01增值税专用发票 *** 本次蚂蚁用 type1
    //02货运运输业增值税专用发票
    //03机动车销售统一发票
    //04增值税普通发票 *** 本次蚂蚁用 type1
    //10增值税普通发票电子 *** 本次蚂蚁用 type1
    //11增值税普通发票卷式 *** 本次蚂蚁用 type1
    //14通行费电子票 *** 本次蚂蚁用 type2
    //15二手车销售统一发票

    function getDataByJinCai(array $rwh_info): bool
    {
        $info = JinCaiTrace::create()
            ->where('traceNo', $rwh_info['traceNo'])
            ->get();

        if (empty($info)) return false;

        if (empty($info['socialCredit']) || empty($info['province']) || empty($info['traceNo'])) {
            return false;
        }

        $main_isComplete = $detail_isComplete = 0;

        // 先取主票
        $main = (new JinCaiShuKeService())
            ->obtainFpInfo($info['socialCredit'], $info['province'], $rwh_info['wupanTraceNo']);

        if (!empty($main['result']['convertResult']['fieldMapping'])) {
            $nsrsbh = trim($main['result']['convertResult']['nsrsbh']);
            $kprqq = trim($main['result']['convertResult']['kprqq']);// 起
            $kprqz = trim($main['result']['convertResult']['kprqz']);// 止
            $fplx = trim($main['result']['convertResult']['fplx']);
            $cxlx = trim($main['result']['convertResult']['cxlx']);
            foreach ($main['result']['convertResult']['fieldMapping'] as $one_main) {
                // 目前主票字段是20个
                if (count($one_main) !== 20) {
                    CommonService::getInstance()->log4PHP([
                        '税号' => $nsrsbh,
                        '问题' => '主票字段不是20个',
                        '主票信息' => $one_main
                    ], 'main-getDataByJinCai', 'GetInvDataJinCai.log');
                    continue;
                }
                $this->mainStoreMysql($one_main, $nsrsbh, $cxlx, $fplx);
            }
            $main_isComplete = 1;
        }

        // 再取详情
        $detail = (new JinCaiShuKeService())
            ->obtainFpDetailInfo($info['socialCredit'], $info['province'], $rwh_info['wupanTraceNo']);

        if (!empty($detail['result']['convertResult']['result']) && count($detail['result']['convertResult']['result']) >= 2) {
            $nsrsbh = trim($detail['result']['convertResult']['nsrsbh']);
            $kprqq = trim($detail['result']['convertResult']['kprqq']);// 起
            $kprqz = trim($detail['result']['convertResult']['kprqz']);// 止
            $fplx = trim($detail['result']['convertResult']['fplx']);
            $cxlx = trim($detail['result']['convertResult']['cxlx']);
            $mxxh = 0;
            foreach ($detail['result']['convertResult']['result'] as $key => $one_detail) {
                // 目前详情字段是12个
                if (count($one_detail) !== 12) {
                    CommonService::getInstance()->log4PHP([
                        '税号' => $nsrsbh,
                        '问题' => '详情字段不是12个',
                        '详情信息' => $one_detail
                    ], 'detail-getDataByJinCai', 'GetInvDataJinCai.log');
                    continue;
                }
                // 第一个是字段中文名称
                if ($key === 0) {
                    continue;
                }
                $mxxh++;
                $this->detailStoreMysql($one_detail, $nsrsbh, $cxlx, $fplx);
            }
            $detail_isComplete = 1;
        }

        // 入库完毕
        if ($main_isComplete === 1 && $detail_isComplete === 1) {
            JinCaiRwh::create()
                ->where('wupanTraceNo', $rwh_info['wupanTraceNo'])
                ->update(['isComplete' => 1]);
        }

        return true;
    }

    private function mainStoreMysql(array $arr, string $nsrsbh, string $cxlx, string $fplx): void
    {
        // 0销项 1 进项
        $cxlx === '0' ? $cxlx = '02' : $cxlx = '01';

        $check_exists = EntInvoice::create()->addSuffix($nsrsbh, 'test')->where([
            'fpdm' => $arr['fpdm'],
            'fphm' => $arr['fphm'],
            'direction' => $cxlx,//01-购买方 02-销售方
        ])->get();

        // 已经存在了
        if (!empty($check_exists)) return;

        $insert_main = [
            'fpdm' => changeNull($arr['fpdm']),//'发票代码',
            'fphm' => changeNull($arr['fphm']),//'发票号码',
            'kplx' => '',//'开票类型 0-蓝字 1-红字',
            'xfsh' => changeNull($arr['xfsh']),//'销售方纳税人识别号',
            'xfmc' => changeNull($arr['xfmc']),//'销售方名称',
            'xfdzdh' => changeNull($arr['xfdzdh']),//'销售方地址电话',
            'xfyhzh' => changeNull($arr['xfyhzh']),//'销售方银行账号',
            'gfsh' => changeNull($arr['gfsh']),//'购买方纳税人识别号',
            'gfmc' => changeNull($arr['gfmc']),//'购买方名称',
            'gfdzdh' => changeNull($arr['gfdzdh']),//'购买方地址电话',
            'gfyhzh' => changeNull($arr['gfyhzh']),//'购买方银行账号',
            'gmflx' => '',//'购买方类型 1企业 2个人 3其他',
            'kpr' => empty(changeNull($arr['kpr'])) ? changeNull($arr['gfmc']) : changeNull($arr['kpr']),//'开票人',
            'skr' => changeNull($arr['skr']),//'收款人',
            'fhr' => changeNull($arr['fhr']),//'复核人',
            'yfpdm' => '',//'原发票代码 kplx为1时必填',
            'yfphm' => '',//'原发票号码 kplx为1时必填',
            'je' => changeDecimal($arr['fpje'], 2),//'金额',
            'se' => changeDecimal($arr['se'], 2),//'税额',
            'jshj' => changeDecimal($arr['jshj'], 2),//'价税合计 单位元 2位小数',
            'bz' => changeNull($arr['bz']),//'备注',
            'zfbz' => changeNull(changeFPZT($arr['fpzt'])) === '2' ? 'Y' : 'N',//'作废标志 N-未作废 Y-作废',
            'zfsj' => '',//'作废时间',
            'kprq' => changeNull($arr['kprq']),//'开票日期',
            'kprq_sort' => microTimeNew() - 0,//'排序用',
            'fplx' => changeNull($fplx),//'发票类型代码 01 02 03 04 10 11 14 15',
            'fpztDm' => changeNull(changeFPZT($arr['fpzt'])),//'发票状态代码 0-正常 1-失控 2-作废 3-红字 4-异常票',
            'slbz' => (is_numeric(changeNull($arr['se'])) && changeNull($arr['se']) > 0) ? '1' : '0',//'税率标识 0-不含税税率 1-含税税率',
            'rzdklBdjgDm' => '',//'认证状态 0-未认证 1-已认证 2-已认证未抵扣',
            'rzdklBdrq' => '',//'认证日期',
            'direction' => $cxlx,//'01-购买方 02-销售方',
            'nsrsbh' => $nsrsbh,//'查询企业税号',
            'jym' => changeNull($arr['jym']),//'校验码',
            'jqbh' => '',//'机器编号',
            'rzsq' => '',//'认证归属期',
            'rzfs' => '',//'认证方式 1-勾选认证 2-扫描认证',
            'gmfsf' => '',//'购买方省份',
            'gmfsj' => '',//'购买方手机',
            'gmfwx' => '',//'购买方微信',
            'gmfyx' => '',//'购买方邮箱',
            'qdbs' => '',//'是否有销货清单 0否 1是 默认为0',
        ];

        try {
            EntInvoice::create()->addSuffix($nsrsbh, 'test')->data($insert_main)->save();
        } catch (\Throwable $e) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
            CommonService::getInstance()->log4PHP($content, 'main-storeMysql', 'GetInvDataJinCai.log');
        }
    }

    private function detailStoreMysql(array $arr, string $nsrsbh, string $cxlx, string $fplx): void
    {
        //        0 => "序号"
        //        1 => "发票代码"
        //        2 => "发票号码"
        //        3 => "税收分类编码"
        //        4 => "货物或应税劳务名称"
        //        5 => "规格型号"
        //        6 => "单位"
        //        7 => "数量"
        //        8 => "单价"
        //        9 => "金额"
        //        10 => "税率"
        //        11 => "税额"

        $check_exists = EntInvoiceDetail::create()->addSuffix($arr[1], $arr[2], 'test')->where([
            'fpdm' => $arr[1],
            'fphm' => $arr[2],
        ])->get();

        // 已经存在了
        if (!empty($check_exists)) return;

        $insert_detail = [
            'spbm' => changeNull($arr[3]),//'税收分类编码',
            'mc' => changeNull($arr[4]),//'如果为折扣行 商品名称须与被折扣行的商品名称相同 不能多行折扣',
            'jldw' => changeNull($arr[6]),//'单位',
            'shul' => changeNull($arr[7]),//'数量',
            'je' => changeDecimal($arr[9], 2),//'含税金额 2位小数',
            'sl' => changeDecimal($arr[10], 3),//'税率 3位小数 例1%为0.010',
            'se' => changeDecimal(changeNull($arr[11]), 2),//'税额',
            'dj' => changeDecimal(changeNull($arr[8]), 2),//'不含税单价',
            'ggxh' => changeNull($arr[5]),//'规格型号',
            'mxxh' => $arr[0],
            'fpdm' => $arr[1],//'发票代码',
            'fphm' => $arr[2],//'发票号码',
            'fphxz' => '',//'是否折扣行 0否 1是 默认0表示正常商品行',
            'hsdj' => '',//'含税单价',
        ];

        try {
            EntInvoiceDetail::create()->addSuffix($arr[1], $arr[2], 'test')->data($insert_detail)->save();
        } catch (\Throwable $e) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
            CommonService::getInstance()->log4PHP($content, 'detail-storeMysql', 'GetInvDataJinCai.log');
        }
    }

    protected function onPipeReadable(Process $process): bool
    {
        parent::onPipeReadable($process);

        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $msg = $throwable->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content, 'onException', 'GetInvDataJinCai.log');
    }


}
