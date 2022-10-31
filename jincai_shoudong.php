<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use Carbon\Carbon;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class jincai_shoudong extends AbstractProcess
{
    function do_strtr(?string $str): string
    {
        $str = strtr($str, [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '(', '〕' => ')', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
            '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+', '—' => '-', '－' => '-',
            '～' => '~', '：' => ':', '。' => '.', '，' => ',', '、' => ',', '；' => ';', '？' => '?', '！' => '!', '…' => '-',
            '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' ', '×' => '*', '￣' => '~', '．' => '.', '＊' => '*',
            '＆' => '&', '＜' => '<', '＞' => '>', '＄' => '$', '＠' => '@', '＾' => '^', '＿' => '_', '＂' => '"', '￥' => '$', '＝' => '=',
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => '', "\r\n" => '', "\r" => '', "\n" => '', "\t" => ''
        ]);
        return trim($str);
    }

    static $hahaha = [];

    protected function run($arg)
    {
        $list = JinCaiTrace::create()->all();

        foreach ($list as $index => $one) {
            $rwh_list = (new JinCaiShuKeService())
                ->obtainResultTraceNo($one->getAttr('traceNo'));
            $timeout = time() - $one->getAttr('updated_at');
            foreach ($rwh_list['result'] as $rwh) {
                $province = $one->getAttr('province');
                $socialCredit = $one->getAttr('socialCredit');
                $retry = $rwh['retry'];
                $taskStatus = $rwh['taskStatus'] - 0;
                $traceNo = $rwh['traceNo'];
                $wupanTraceNo = $rwh['wupanTraceNo'];
                if ($taskStatus !== 2) {
//                    $check = $this->refreshTask($traceNo);
//                    if (!$check) {
//                        dd($traceNo);
//                    }
                    echo $socialCredit . PHP_EOL;
                    break;
                }
            }
        }

    }

    function refreshTask($traceNo): bool
    {
        $refreshTask = (new JinCaiShuKeService())->refreshTask($traceNo);
        if ($refreshTask['result'] === '重置任务成功') {
            $ret = JinCaiTrace::create()
                ->where('traceNo', $traceNo)
                ->update(['isComplete' => 0]);
        } else {
            $ret = false;
        }
        return $ret;
    }

    function handleMain($main)
    {
        if (!empty($main['result']['convertResult']['fieldMapping'])) {
            $nsrsbh = trim($main['result']['convertResult']['nsrsbh']);
            $kprqq = trim($main['result']['convertResult']['kprqq']);// 起
            $kprqz = trim($main['result']['convertResult']['kprqz']);// 止
            $fplx = trim($main['result']['convertResult']['fplx']);
            $cxlx = trim($main['result']['convertResult']['cxlx']);
            foreach ($main['result']['convertResult']['fieldMapping'] as $one_main) {
                $insert = [
                    'fpdm' => isset($one_main['fpdm']) ? $this->do_strtr($one_main['fpdm']) : '',
                    'gfsh' => isset($one_main['gfsh']) ? $this->do_strtr($one_main['gfsh']) : '',
                    'gfmc' => isset($one_main['gfmc']) ? $this->do_strtr($one_main['gfmc']) : '',
                    'gfyhzh' => isset($one_main['gfyhzh']) ? $this->do_strtr($one_main['gfyhzh']) : '',
                    'kpr' => isset($one_main['kpr']) ? $this->do_strtr($one_main['kpr']) : '',
                    'xfyhzh' => isset($one_main['xfyhzh']) ? $this->do_strtr($one_main['xfyhzh']) : '',
                    'fhr' => isset($one_main['fhr']) ? $this->do_strtr($one_main['fhr']) : '',
                    'se' => isset($one_main['se']) ? $this->do_strtr($one_main['se']) : '',
                    'fpzt' => isset($one_main['fpzt']) ? $this->do_strtr($one_main['fpzt']) : '',
                    'fpje' => isset($one_main['fpje']) ? $this->do_strtr($one_main['fpje']) : '',
                    'kprq' => isset($one_main['kprq']) ? $this->do_strtr($one_main['kprq']) : '',
                    'gfdzdh' => isset($one_main['gfdzdh']) ? $this->do_strtr($one_main['gfdzdh']) : '',
                    'bz' => isset($one_main['bz']) ? $this->do_strtr($one_main['bz']) : '',
                    'jshj' => isset($one_main['jshj']) ? $this->do_strtr($one_main['jshj']) : '',
                    'xfdzdh' => isset($one_main['xfdzdh']) ? $this->do_strtr($one_main['xfdzdh']) : '',
                    'xfsh' => isset($one_main['xfsh']) ? $this->do_strtr($one_main['xfsh']) : '',
                    'skr' => isset($one_main['skr']) ? $this->do_strtr($one_main['skr']) : '',
                    'xfmc' => isset($one_main['xfmc']) ? $this->do_strtr($one_main['xfmc']) : '',
                    'fphm' => isset($one_main['fphm']) ? $this->do_strtr($one_main['fphm']) : '',
                    'jym' => isset($one_main['jym']) ? $this->do_strtr($one_main['jym']) : '',
                ];
                // 全空就不入库了
                $check = array_filter($insert);
                if (!empty($check)) {
                    $this->mainStoreMysql($insert, '91340700MA2TGDDJ8X', $cxlx, $fplx);
                }
            }
        }
    }

    private function mainStoreMysql(array $arr, string $nsrsbh, string $cxlx, string $fplx): void
    {
        // 0销项 1 进项
        $cxlx === '0' ? $cxlx = '02' : $cxlx = '01';

        $check_exists = EntInvoice::create()
            ->addSuffix($nsrsbh, 'test')
            ->where([
                'fpdm' => $arr['fpdm'],
                'fphm' => $arr['fphm'],
                'direction' => $cxlx,//01-购买方 02-销售方
            ])->get();

        // 已经存在了
        if (!empty($check_exists)) return;

        $insert_main = [
            'fpdm' => changeNull($arr['fpdm']),//'发票代码',
            'fphm' => changeNull($arr['fphm']),//'发票号码',
            'kplx' => changeDecimal($arr['jshj'], 2) < 0 ? '1' : '0',//'开票类型 0-蓝字 1-红字',
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
            'yfpdm' => '',//'原发票代码 kplx为1时必填 换金财后必填不了了',
            'yfphm' => '',//'原发票号码 kplx为1时必填 换金财后必填不了了',
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
            EntInvoice::create()
                ->addSuffix($nsrsbh, 'test')
                ->data($insert_main)
                ->save();
        } catch (\Throwable $e) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
            CommonService::getInstance()->log4PHP($content, 'main-storeMysql', __FUNCTION__);
        }
    }

    function handleDetail($detail)
    {
        if (!empty($detail['result']['convertResult']['result']) && count($detail['result']['convertResult']['result']) >= 2) {
            $nsrsbh = trim($detail['result']['convertResult']['nsrsbh']);
            $kprqq = trim($detail['result']['convertResult']['kprqq']);// 起
            $kprqz = trim($detail['result']['convertResult']['kprqz']);// 止
            $fplx = trim($detail['result']['convertResult']['fplx']);
            $cxlx = trim($detail['result']['convertResult']['cxlx']);
            $model = [
                'mxxh' => '序号',
                'fpdm' => '发票代码',
                'fphm' => '发票号码',
                'spbm' => '税收分类编码',
                'mc' => '货物或应税劳务名称',
                'ggxh' => '规格型号',
                'jldw' => '单位',
                'shul' => '数量',
                'dj' => '单价',
                'je' => '金额',
                'sl' => '税率',
                'se' => '税额',
            ];
            foreach ($detail['result']['convertResult']['result'] as $key => $one_detail) {
                if ($key === 0) {
                    // 组成一个 key val
                    $index = array_values(array_filter($one_detail));
                    if (in_array('货物或应税劳务、服务名称', $index, true) || in_array('项目', $index, true)) {
                        foreach ($index as $i => $v) {
                            if (in_array($v, ['货物或应税劳务、服务名称', '项目'], true)) {
                                $index[$i] = '货物或应税劳务名称';
                            }
                        }
                    }
                    if (!in_array(jsonEncode($index, false), self::$hahaha)) {
                        self::$hahaha[] = jsonEncode($index, false);
                        dump(self::$hahaha);
                    }
                    continue;
                }
                // 全空就不入库了
                $check = array_filter(array_slice($one_detail, 0, count($index)));
                if (!empty($check)) {
                    $temp = array_combine($index, array_slice($one_detail, 0, count($index)));
                    $insert = [];
                    foreach ($model as $key_en => $key_cn) {
                        array_key_exists($key_cn, $temp) ? $insert[$key_en] = $temp[$key_cn] : $insert[$key_en] = '';
                    }
                    $this->detailStoreMysql($insert, '91340700MA2TGDDJ8X', $cxlx, $fplx);
                }
            }
        }
    }

    private function detailStoreMysql(array $arr, string $nsrsbh, string $cxlx, string $fplx): void
    {
        $check_exists = EntInvoiceDetail::create()
            ->addSuffix($arr['fpdm'], $arr['fphm'], 'test')
            ->where([
                'fpdm' => $arr['fpdm'],
                'fphm' => $arr['fphm'],
            ])->get();

        // 已经存在了
        if (!empty($check_exists)) return;

        $insert_detail = [
            'spbm' => changeNull($arr['spbm']),//'税收分类编码',
            'mc' => changeNull($arr['mc']),//'如果为折扣行 商品名称须与被折扣行的商品名称相同 不能多行折扣',
            'jldw' => changeNull($arr['jldw']),//'单位',
            'shul' => changeNull($arr['shul']),//'数量',
            'je' => changeDecimal($arr['je'], 2),//'含税金额 2位小数',
            'sl' => changeDecimal($arr['sl'], 3),//'税率 3位小数 例1%为0.010',
            'se' => changeDecimal(changeNull($arr['se']), 2),//'税额',
            'dj' => changeDecimal(changeNull($arr['dj']), 2),//'不含税单价',
            'ggxh' => changeNull($arr['ggxh']),//'规格型号',
            'mxxh' => $arr['mxxh'],
            'fpdm' => $arr['fpdm'],//'发票代码',
            'fphm' => $arr['fphm'],//'发票号码',
            'fphxz' => '',//'是否折扣行 0否 1是 默认0表示正常商品行',
            'hsdj' => '',//'含税单价',
        ];

        try {
            EntInvoiceDetail::create()
                ->addSuffix($arr['fpdm'], $arr['fphm'], 'test')
                ->data($insert_detail)
                ->save();
        } catch (\Throwable $e) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
            CommonService::getInstance()->log4PHP($content, 'detail-storeMysql', __FUNCTION__);
        }
    }

    function addTask()
    {
        $list = \App\HttpController\Models\Api\AntAuthList::create()
            ->where('id', $all, 'IN')
            ->all();
        foreach ($list as $target) {
            // 开票日期止
            $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;
            // 最大取票月份
            $big_kprq = $target->getAttr('big_kprq');
            // 本月取过了 不取了
            if ($kprqz === $big_kprq) {
                continue;
            }
            // 开票日期起
            if ($big_kprq - 0 === 0) {
                $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;
            } else {
                $kprqq = Carbon::createFromTimestamp($big_kprq)->subMonths(1)->startOfMonth()->timestamp;
            }
            // 拼task请求参数
            $ywBody = [
                'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
                'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
                'nsrsbh' => $target->getAttr('socialCredit'),// 纳税人识别号
            ];
            try {
                for ($try = 3; $try--;) {
                    // 发送 试3次
                    $addTaskInfo = (new JinCaiShuKeService())->addTask(
                        $target->getAttr('socialCredit'),
                        $target->getAttr('province'),
                        $target->getAttr('city'),
                        $ywBody
                    );
                    if (isset($addTaskInfo['code']) && strlen($addTaskInfo['code']) > 1) {
                        break;
                    }
                    \co::sleep(120);
                }
                JinCaiTrace::create()->data([
                    'entName' => $target->getAttr('entName'),
                    'socialCredit' => $target->getAttr('socialCredit'),
                    'code' => $addTaskInfo['code'] ?? '未返回',
                    'type' => 1,// 无盘
                    'province' => $addTaskInfo['result']['province'] ?? '未返回',
                    'taskCode' => $addTaskInfo['result']['taskCode'] ?? '未返回',
                    'taskStatus' => $addTaskInfo['result']['taskStatus'] ?? '未返回',
                    'traceNo' => $addTaskInfo['result']['traceNo'] ?? '未返回',
                    'kprqq' => $kprqq,
                    'kprqz' => $kprqz,
                ])->save();
            } catch (\Throwable $e) {
                $file = $e->getFile();
                $line = $e->getLine();
                $msg = $e->getMessage();
                $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                CommonService::getInstance()->log4PHP($content, 'try-catch', 'GetJinCaiTrace.log');
            }
        }
        dd('完成');
    }

    function addTaskOne($id)
    {
        $list = \App\HttpController\Models\Api\AntAuthList::create()
            ->where('id', $id)
            ->all();

        foreach ($list as $target) {

            // 开票日期止
            $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;

            // 最大取票月份
            $big_kprq = $target->getAttr('big_kprq');

            // 本月取过了 不取了
            if ($kprqz === $big_kprq) {
                continue;
            }

            // 开票日期起
            if ($big_kprq - 0 === 0) {
                $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;
            } else {
                $kprqq = Carbon::createFromTimestamp($big_kprq)->subMonths(1)->startOfMonth()->timestamp;
            }

            // 拼task请求参数
            $ywBody = [
                'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
                'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
                'nsrsbh' => $target->getAttr('socialCredit'),// 纳税人识别号
            ];

            try {
                for ($try = 3; $try--;) {
                    // 发送 试3次
                    $addTaskInfo = (new JinCaiShuKeService())->addTask(
                        $target->getAttr('socialCredit'),
                        $target->getAttr('province'),
                        $target->getAttr('city'),
                        $ywBody
                    );
                    if (isset($addTaskInfo['code']) && strlen($addTaskInfo['code']) > 1) {
                        break;
                    }
                    \co::sleep(5);
                }
                JinCaiTrace::create()->data([
                    'entName' => $target->getAttr('entName'),
                    'socialCredit' => $target->getAttr('socialCredit'),
                    'code' => $addTaskInfo['code'] ?? '未返回',
                    'type' => 1,// 无盘
                    'province' => $addTaskInfo['result']['province'] ?? '未返回',
                    'taskCode' => $addTaskInfo['result']['taskCode'] ?? '未返回',
                    'taskStatus' => $addTaskInfo['result']['taskStatus'] ?? '未返回',
                    'traceNo' => $addTaskInfo['result']['traceNo'] ?? '未返回',
                    'kprqq' => $kprqq,
                    'kprqz' => $kprqz,
                ])->save();
                // 还要间隔2分钟
                \co::sleep(120);
            } catch (\Throwable $e) {
                $file = $e->getFile();
                $line = $e->getLine();
                $msg = $e->getMessage();
                $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                CommonService::getInstance()->log4PHP($content, 'try-catch', 'GetJinCaiTrace.log');
                continue;
            }

        }
    }

    protected function onShutDown()
    {

    }

    protected function onException(\Throwable $throwable, ...$args)
    {

    }

}

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);

//mysql pool
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlPoolForRDS3NicCode::getInstance()->createMysql();
CreateMysqlPoolForRDS3SiJiFenLei::getInstance()->createMysql();

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();

CreateRedisPool::getInstance()->createRedis();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new jincai_shoudong($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
