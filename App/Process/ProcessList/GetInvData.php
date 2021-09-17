<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\OSS\OSSService;
use App\HttpController\Service\Zip\ZipService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;

class GetInvData extends ProcessBase
{
    const ProcessNum = 16;

    public $p_index;
    public $redisKey;
    public $oss_expire_time = 86400 * 7;
    public $oss_bucket = 'invoice-mrxd';
    public $taxNo = '140301321321333';//91110108MA01KPGK0L

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        //获取注册进程名称
        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;
        //要消费的队列名
        $this->redisKey = 'readyToGetInvData_' . $this->p_index;
        $redis = Redis::defer('redis');
        $redis->select(15);

        //开始消费
        while (true) {
            $entInRedis = $redis->rPop($this->redisKey);
            if (empty($entInRedis)) {
                mt_srand();
                \co::sleep(mt_rand(3, 9));
                continue;
            }
            $this->getDataByEle(jsonDecode($entInRedis));
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

    function getDataByEle($entInfo): bool
    {
        if (empty($entInfo)) {
            return false;
        }

        $KPKSRQ = Carbon::now()->subMonths(23)->startOfMonth()->format('Y-m-d');//开始日
        $KPJSRQ = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');//截止日
        $NSRSBH = $entInfo['socialCredit'];

        $KPKSRQ = '2020-01-01';
        $KPJSRQ = '2021-08-31';
        $NSRSBH = '911199999999CN0008';

        $FPLXDMS = [
            '01', '02', '03', '04', '10', '11', '14', '15'
        ];

        //进项
        foreach ($FPLXDMS as $FPLXDM) {
            $KM = '1';
            for ($page = 1; $page <= 999999; $page++) {
                $res = (new DaXiangService())
                    ->getInv($this->taxNo, $page . '', $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ);
                $content = jsonDecode(base64_decode($res['content']));
                if ($content['code'] === '0000' && !empty($content['data']['records'])) {
                    foreach ($content['data']['records'] as $row) {
                        $this->writeFile($row, $NSRSBH, 'in', $FPLXDM);
                    }
                } else {
                    $info = "{$NSRSBH} : page={$page} KM={$KM} FPLXDM={$FPLXDM} KPKSRQ={$KPKSRQ} KPJSRQ={$KPJSRQ}";
                    CommonService::getInstance()->log4PHP($info);
                    $this->writeFile([], $NSRSBH, 'in', $FPLXDM);
                    break;
                }
            }
        }

        //销项
        foreach ($FPLXDMS as $FPLXDM) {
            $KM = '2';
            for ($page = 1; $page <= 999999; $page++) {
                $res = (new DaXiangService())
                    ->getInv($this->taxNo, $page . '', $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ);
                $content = jsonDecode(base64_decode($res['content']));
                if ($content['code'] === '0000' && !empty($content['data']['records'])) {
                    foreach ($content['data']['records'] as $row) {
                        $this->writeFile($row, $NSRSBH, 'out', $FPLXDM);
                    }
                } else {
                    $info = "{$NSRSBH} : page={$page} KM={$KM} FPLXDM={$FPLXDM} KPKSRQ={$KPKSRQ} KPJSRQ={$KPJSRQ}";
                    CommonService::getInstance()->log4PHP($info);
                    break;
                }
            }
        }

        //通知蚂蚁
        $this->sendToAnt($NSRSBH);

        return true;
    }

    //上传到oss并且通知蚂蚁
    function sendToAnt($NSRSBH)
    {
        $dir = MYJF_PATH . $NSRSBH . DIRECTORY_SEPARATOR . Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR;

        $file_arr = [];

        if ($dh = opendir($dir)) {
            $ignore = [
                '.', '..', '.gitignore',
            ];
            while (false !== ($file = readdir($dh))) {
                if (!in_array($file, $ignore, true)) {
                    $file_arr[] = $dir . $file;
                }
            }
        }
        closedir($dh);

        if (!empty($file_arr)) {
            $name = Carbon::now()->format('Ym') . "_{$NSRSBH}.zip";
            $zip_file_name = ZipService::getInstance()->zip($file_arr, $dir . $name, true);
            $oss_file_name = OSSService::getInstance()
                ->doUploadFile($this->oss_bucket, $name, $zip_file_name, $this->oss_expire_time);
            //更新上次取数时间和oss地址
            AntAuthList::create()
                ->where('socialCredit', $NSRSBH)
                ->update([
                    'lastReqTime' => time(),
                    'lastReqUrl' => $oss_file_name,
                ]);
        }
    }

    function writeFile(array $row, string $NSRSBH, string $invType, string $FPLXDM): bool
    {
        $store = MYJF_PATH . $NSRSBH . DIRECTORY_SEPARATOR . Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR;

        $filename = $NSRSBH . "_{$FPLXDM}_{$invType}.json";

        is_dir($store) || mkdir($store, 0755, true);

        if (empty($row)) {
            $content = '' . PHP_EOL;
        } else {
            $this->storeMysql($row, $NSRSBH, $FPLXDM, $invType);
            $content = jsonEncode($row, false) . PHP_EOL;
        }

        file_put_contents($store . $filename, $content, FILE_APPEND | LOCK_EX);

        return true;
    }

    private function storeMysql(array $arr, string $NSRSBH, string $FPLXDM, string $invType): bool
    {
        $invType === 'in' ? $invType = '01' : $invType = '02';

        switch ($FPLXDM) {
            case '01':
            case '04':
            case '10':
            case '11':
                $FPLXDM = 'type1';
                break;
            case '14':
                $FPLXDM = 'type2';
                break;
            default:
                $FPLXDM = '';
        }

        if (empty($FPLXDM)) return false;

        try {
            if ($FPLXDM === 'type1') {
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
            } elseif ($FPLXDM === 'type2') {
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
            } else {
                $wanghan = 1;
            }
        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP([
                'data' => $arr,
                'NSRSBH' => $NSRSBH,
                'FPLXDM' => $FPLXDM,
                'invType' => $invType,
                'errorTrace' => $e->getTraceAsString(),
                'errorMsg' => $e->getMessage(),
            ], 'info', 'inv_store_mysql_error.log');
        }

        return true;
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }


}
