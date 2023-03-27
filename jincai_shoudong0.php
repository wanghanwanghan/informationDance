<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntEmptyLog;
use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateMysqlPoolForJinCai;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\OSS\OSSService;
use Carbon\Carbon;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use wanghanwanghan\someUtils\control;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class jincai_shoudong0 extends AbstractProcess
{
    public $currentAesKey = '21bf9a5d127a1a8b';
    public $iv = '1234567890abcdef';
    public $oss_bucket = 'invoice-mrxd';
    public $oss_expire_time = 86400 * 60;

    protected function run($arg)
    {
        // 不要删除这行
        // $this->createCurrentAesKey();

        $this->addTask();
    }

    //取票时候调用 从数据库
    function _getInvoice()
    {
        $target_t = JinCaiTrace::create()->field('socialCredit')->all();
        $target = [];
        foreach ($target_t as $one) {
            $target[] = $one->getAttr('socialCredit');
        }

        preg_match_all('/\d+/', basename(__FILE__), $no);
        $no = current(current($no)) - 0;

        $start = 0;
        $step = 500;
        $max_id_in = 3955900;//每次改
        $max_id_out = 1148042;//每次改
        $max_id_in_detail = 7177096;//每次改
        $max_id_out_detail = 2773176;//每次改

        $csp = \App\Csp\Service\CspService::getInstance()->create();

        //总共需要80个数据库链接
        $p_total = 20;

        //add进项
        for ($p_index = 1; $p_index <= $p_total; $p_index++) {
            $csp->add('in' . $p_index, function () use ($target, $max_id_in, $start, $p_total, $p_index, $step) {
                $start_time = Carbon::now()->format('Y-m-d H:i:s');
                while (true) {
                    $p_start = $max_id_in - ($start * $p_total + $p_index) * $step;
                    $p_end = $p_start - $step;
                    if ($p_start < 0 && $p_end < 0) break;
                    $raw = \App\HttpController\Models\RDS3\JinCai\MainIn::create()
                        ->where('pid', $p_start, '<=')
                        ->where('pid', $p_end, '>')
                        ->where('gfsh', $target, 'IN')
                        ->all();
                    if (!empty($raw)) {
                        // ===============================================
                        $this->handleMain($raw, 'in');
                        // ===============================================
                    }
                    $start++;
                }
                return [$start_time, Carbon::now()->format('Y-m-d H:i:s')];
            });
        }

        //add销项
        for ($p_index = 1; $p_index <= $p_total; $p_index++) {
            $csp->add('out' . $p_index, function () use ($target, $max_id_out, $start, $p_total, $p_index, $step) {
                $start_time = Carbon::now()->format('Y-m-d H:i:s');
                while (true) {
                    $p_start = $max_id_out - ($start * $p_total + $p_index) * $step;
                    $p_end = $p_start - $step;
                    if ($p_start < 0 && $p_end < 0) break;
                    $raw = \App\HttpController\Models\RDS3\JinCai\MainOut::create()
                        ->where('pid', $p_start, '<=')
                        ->where('pid', $p_end, '>')
                        ->where('xfsh', $target, 'IN')
                        ->all();
                    if (!empty($raw)) {
                        // ===============================================
                        $this->handleMain($raw, 'out');
                        // ===============================================
                    }
                    $start++;
                }
                return [$start_time, Carbon::now()->format('Y-m-d H:i:s')];
            });
        }

        //add进项 detail
        for ($p_index = 1; $p_index <= $p_total; $p_index++) {
            $csp->add('in_detail' . $p_index, function () use ($target, $max_id_in_detail, $start, $p_total, $p_index, $step) {
                $start_time = Carbon::now()->format('Y-m-d H:i:s');
                while (true) {
                    $p_start = $max_id_in_detail - ($start * $p_total + $p_index) * $step;
                    $p_end = $p_start - $step;
                    if ($p_start < 0 && $p_end < 0) break;
                    $raw = \App\HttpController\Models\RDS3\JinCai\DetailIn::create()
                        ->where('pid', $p_start, '<=')
                        ->where('pid', $p_end, '>')
                        ->all();
                    if (!empty($raw)) {
                        // ===============================================
                        $this->handleDetail($raw, 'in');
                        // ===============================================
                    }
                    $start++;
                }
                return [$start_time, Carbon::now()->format('Y-m-d H:i:s')];
            });
        }

        //add销项 detail
        for ($p_index = 1; $p_index <= $p_total; $p_index++) {
            $csp->add('out_detail' . $p_index, function () use ($target, $max_id_out_detail, $start, $p_total, $p_index, $step) {
                $start_time = Carbon::now()->format('Y-m-d H:i:s');
                while (true) {
                    $p_start = $max_id_out_detail - ($start * $p_total + $p_index) * $step;
                    $p_end = $p_start - $step;
                    if ($p_start < 0 && $p_end < 0) break;
                    $raw = \App\HttpController\Models\RDS3\JinCai\DetailOut::create()
                        ->where('pid', $p_start, '<=')
                        ->where('pid', $p_end, '>')
                        ->all();
                    if (!empty($raw)) {
                        // ===============================================
                        $this->handleDetail($raw, 'out');
                        // ===============================================
                    }
                    $start++;
                }
                return [$start_time, Carbon::now()->format('Y-m-d H:i:s')];
            });
        }

        dd($csp->exec(-1));

    }

    function getTaskStatus()
    {
//        $all = JinCaiTrace::create()->all();
//        foreach ($all as $one) {
//            if( $one->getAttr('code')== 'S000'){
        $addTaskInfo = (new JinCaiShuKeService())->getTaskStatus('aacd1990009814b1d183b30e6051f06c');//$one->getAttr('pTraceNo')
        var_dump($addTaskInfo);
//            }
//        }
    }

    //上传oss时候调用
    function _sendToOSS()
    {
        $all = JinCaiTrace::create()->all();
        foreach ($all as $one) {
            $this->sendToOSS(
                $one->getAttr('socialCredit'),//
                $one->getAttr('kprqq'),
                $one->getAttr('kprqz')
            );
        }
    }

    function createCurrentAesKey()
    {
        $timestamp_s = Carbon::now()->startOfMonth()->timestamp;
        $timestamp_e = Carbon::now()->endOfMonth()->timestamp;
        $ym = Carbon::now()->format('Y-m');
        $currentAesKey = md5(implode('|', [$timestamp_s, $ym, $timestamp_e]));
        $this->currentAesKey = substr($currentAesKey, 0, 16);
    }

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

    //上传到oss 发票已经入完mysql 这里要改成用阿里云内网
    function sendToOSS($NSRSBH, $kprqq, $kprqz): bool
    {
        //只有蚂蚁的税号才上传oss
        //蚂蚁区块链dev id 36
        //蚂蚁区块链pre id 41
        //蚂蚁区块链pro id 42

        //每个文件存多少张发票
        $dataInFile = 3000;

        $store = MYJF_PATH . $NSRSBH . DIRECTORY_SEPARATOR . Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR;

        is_dir($store) || mkdir($store, 0755, true);

        //取全部发票写入文件
        $total = EntInvoice::create()
            ->addSuffix($NSRSBH, 'test')
            ->where('nsrsbh', $NSRSBH)
            ->where('kprq_int', $kprqq, '>=')
            ->where('kprq_int', $kprqz, '<=')
            ->count();

        //随机文件名
        $fileSuffix = control::getUuid(8);

        if ($total === 0) {
            $filename = "{$NSRSBH}_page_1_{$fileSuffix}.json";
            file_put_contents($store . $filename, '');
        } else {
            $totalPage = $total / $dataInFile + 1;
            //每个文件存3000张发票
            for ($page = 1; $page <= $totalPage; $page++) {
                //每个文件存3000张发票
                $filename = "{$NSRSBH}_page_{$page}_{$fileSuffix}.json";
                $offset = ($page - 1) * $dataInFile;
                $list = EntInvoice::create()
                    ->addSuffix($NSRSBH, 'test')
                    ->where('nsrsbh', $NSRSBH)
                    ->where('kprq_int', $kprqq, '>=')
                    ->where('kprq_int', $kprqz, '<=')
                    ->field([
                                'fpdm',//
                                'fphm',//
                                'kplx',//
                                'xfsh',//
                                'xfmc',//
                                'xfdzdh',//
                                'xfyhzh',//
                                'gfsh',//
                                'gfmc',//
                                'gfdzdh',//
                                'gfyhzh',//
                                'kpr',//
                                'skr',//
                                'fhr',//
                                'yfpdm',
                                'yfphm',
                                'je',//
                                'se',//
                                'jshj',//
                                'bz',//
                                'zfbz',//
                                'zfsj',//
                                'kprq',//
                                'fplx',//
                                'fpztDm',//
                                'slbz',
                                'rzdklBdjgDm',
                                'rzdklBdrq',
                                'direction',
                                'nsrsbh',
                            ])->limit($offset, $dataInFile)->all();
                //没有数据了
                if (empty($list)) break;
                foreach ($list as $key => $oneInv) {
                    //每张添加明细
                    $detail = EntInvoiceDetail::create()
                        ->addSuffix($oneInv->getAttr('fpdm'), $oneInv->getAttr('fphm'), 'test')
                        ->where(['fpdm' => $oneInv->getAttr('fpdm'), 'fphm' => $oneInv->getAttr('fphm')])
                        ->field([
                                    'spbm',//
                                    'mc',//
                                    'jldw',//
                                    'shul',//
                                    'je',//
                                    'sl',//
                                    'se',//
                                    'mxxh',//
                                    'dj',//
                                    'ggxh',//
                                ])->all();
                    empty($detail) ? $oneInv->fpxxMxs = null : $oneInv->fpxxMxs = $detail;
                    echo $NSRSBH . '的' . '第' . $key . '张详情' . PHP_EOL;
                }
                $content = jsonEncode($list, false);
                //AES-128-CTR
                $content = base64_encode(openssl_encrypt(
                                             $content,
                                             'AES-128-CTR',
                                             $this->currentAesKey,
                                             OPENSSL_RAW_DATA,
                                             $this->iv
                                         ));
                echo "put 中 {$filename}" . PHP_EOL;
                file_put_contents($store . $filename, $content . PHP_EOL);
            }
        }

        //上传oss
        $file_arr = [];

        if ($dh = opendir($store)) {
            $ignore = [
                '.', '..', '.gitignore',
            ];
            while (false !== ($file = readdir($dh))) {
                if (!in_array($file, $ignore, true)) {
                    if (strpos($file, $fileSuffix) !== false) {
                        CommonService::getInstance()->log4PHP($file, 'info', 'upload_oss.log');
                        try {
                            $oss = new OSSService('internal');
                            $file_arr[] = $oss->doUploadFile(
                                $this->oss_bucket,
                                Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR . $file,
                                $store . $file,
                                $this->oss_expire_time
                            );
                        } catch (\Throwable $e) {
                            $file = $e->getFile();
                            $line = $e->getLine();
                            $msg = $e->getMessage();
                            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                            CommonService::getInstance()->log4PHP($content, 'sendToOSS', 'send_fapiao_err.log');
                        }
                    }
                }
            }
            AntAuthList::create()
                ->where('socialCredit', $NSRSBH)
                ->update([
                             'lastReqTime' => time(),
                             'lastReqUrl' => empty($file_arr) ? '' : implode(',', $file_arr),
                             'big_kprq' => $kprqz
                         ]);
        }
        closedir($dh);

        return true;
    }

    //通知蚂蚁
    function sendToAnt(): bool
    {
        //根据三个id，通知不同的url
        $url_arr = [
            41 => 'https://trustdata.antgroup.com/api/wezTech/collectNotify',
        ];

        $list = JinCaiTrace::create()->all();

        foreach ($list as $oneReadyToSend) {

            $socialCredit = $oneReadyToSend->getAttr('socialCredit');

            //拿私钥
            $id = 41;
            $info = RequestUserInfo::create()->get($id);
            $rsa_pub_name = $info->getAttr('rsaPub');
            $rsa_pri_name = $info->getAttr('rsaPri');

            $authResultCode = '0000';

            //拿公钥加密
            $stream = file_get_contents(RSA_KEY_PATH . $rsa_pub_name);
            //AES加密key用RSA加密
            $fileSecret = control::rsaEncrypt($this->currentAesKey, $stream, 'pub');

            $check_file = AntAuthList::create()->where('socialCredit', $socialCredit)->get();

            if (empty($check_file)) continue;

            $fileKeyList = empty($check_file->getAttr('lastReqUrl')) ?
                [] :
                array_filter(explode(',', $check_file->getAttr('lastReqUrl')));

            //拿一下这个企业的进项销项总发票数字
            $in = EntInvoice::create()->addSuffix($oneReadyToSend->getAttr('socialCredit'), 'test')->where([
                                                                                                               'nsrsbh' => $socialCredit,
                                                                                                               'direction' => '01',//01-进项
                                                                                                           ])->count();
            $out = EntInvoice::create()->addSuffix($oneReadyToSend->getAttr('socialCredit'), 'test')->where([
                                                                                                                'nsrsbh' => $socialCredit,
                                                                                                                'direction' => '02',//02-销项
                                                                                                            ])->count();

            $body = [
                'nsrsbh' => $check_file->getAttr('socialCredit'),//授权的企业税号
                'authResultCode' => $authResultCode,//取数结果状态码 0000取数成功 XXXX取数失败
                'fileSecret' => $fileSecret,//对称钥秘⽂
                'companyName' => $check_file->getAttr('entName'),//公司名称
                'authTime' => date('Y-m-d H:i:s', $check_file->getAttr('requestDate')),//授权时间
                'totalCount' => ($in + $out) . '',
                'fileKeyList' => $fileKeyList,//文件路径
                //'notifyType' => 'INVOICE' //通知发票
            ];

            $num = $in + $out;

            $dateM = (time() - $check_file->getAttr('requestDate')) / 86400;

            if (empty($num) && $dateM < 30) {
                $body['authResultCode'] = '9000';//'没准备好';
                AntEmptyLog::create()->data([
                                                'nsrsbh' => $body['nsrsbh'],
                                                'data' => json_encode($body)
                                            ])->save();
            }

            ksort($body);//周平说参数升序

            //sign md5 with rsa
            $private_key = file_get_contents(RSA_KEY_PATH . $rsa_pri_name);
            $pkeyid = openssl_pkey_get_private($private_key);
            $verify = openssl_sign(jsonEncode([$body], false), $signature, $pkeyid, OPENSSL_ALGO_MD5);

            //准备通知
            $collectNotify = [
                'body' => [$body],
                'head' => [
                    'sign' => base64_encode($signature),//签名
                    'notifyChannel' => 'ELEPHANT',//通知 渠道
                ],
            ];

            $url = $url_arr[$id];

            $header = [
                'content-type' => 'application/json;charset=UTF-8',
            ];

            //通知
            CommonService::getInstance()->log4PHP(jsonEncode($collectNotify, false), 'send', 'notify_fp');
            $ret = (new CoHttpClient())
                ->useCache(false)
                ->needJsonDecode(true)
                ->send($url, jsonEncode($collectNotify, false), $header, [], 'postjson');
            CommonService::getInstance()->log4PHP($ret, 'return', 'notify_fp');

        }

        return true;
    }

    function handleMain($main, $type)
    {
        foreach ($main as $one_main) {
            $one_main = obj2Arr($one_main);
            $fpdm = $this->do_strtr($one_main['fpdm'] ?? '');
            $fphm = $this->do_strtr($one_main['fphm'] ?? '');
            $type === 'in' ?
                $nsrsbh = $this->do_strtr($one_main['gfsh']) :
                $nsrsbh = $this->do_strtr($one_main['xfsh']);
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
                'fplx' => isset($one_main['fplx']) ? $this->do_strtr($one_main['fplx']) : '',
                'cxlx' => isset($one_main['type']) ? $this->do_strtr($one_main['type']) : '',
            ];
            $check = array_filter($insert);
            // 全空就不入库了
            if (!empty($check)) {
                $this->mainStoreMysql($insert, $nsrsbh);
            }
        }
    }

    function mainStoreMysql(array $arr, string $nsrsbh): void
    {
        // 0销项 1 进项
        $arr['cxlx'] === '0' ? $arr['cxlx'] = '02' : $arr['cxlx'] = '01';

        $check_exists = EntInvoice::create()
            ->addSuffix($nsrsbh, 'test')
            ->where([
                        'fpdm' => $arr['fpdm'],
                        'fphm' => $arr['fphm'],
                        'direction' => $arr['cxlx'],//01-购买方 02-销售方
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
            'kprq_int' => is_numeric(strtotime($arr['kprq'])) ? strtotime($arr['kprq']) : 0,//'范围查询用',
            'kprq_sort' => microTimeNew() - 0,//'排序用',
            'fplx' => changeNull($arr['fplx']),//'发票类型代码 01 02 03 04 10 11 14 15',
            'fpztDm' => changeNull(changeFPZT($arr['fpzt'])),//'发票状态代码 0-正常 1-失控 2-作废 3-红字 4-异常票',
            'slbz' => (is_numeric(changeNull($arr['se'])) && changeNull($arr['se']) > 0) ? '1' : '0',//'税率标识 0-不含税税率 1-含税税率',
            'rzdklBdjgDm' => '',//'认证状态 0-未认证 1-已认证 2-已认证未抵扣',
            'rzdklBdrq' => '',//'认证日期',
            'direction' => $arr['cxlx'],//'01-购买方 02-销售方',
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

    function handleDetail($detail, $type)
    {
        foreach ($detail as $mxxh => $one_detail) {
            $one_detail = obj2Arr($one_detail);
            // 全空就不入库了
            $check = array_filter($one_detail);
            if (!empty($check)) {
                $insert = [
                    'mxxh' => 0,
                    'fpdm' => $one_detail['fpdm'] ?? '',
                    'fphm' => $one_detail['fphm'] ?? '',
                    'spbm' => $one_detail['ssflbm'] ?? '',// 税收分类编码
                    'mc' => $one_detail['hwhyslwmc'] ?? '',// 货物或应税劳务名称
                    'ggxh' => $one_detail['ggxh'] ?? '',
                    'jldw' => $one_detail['dw'] ?? '',// 单位
                    'shul' => $one_detail['shuliang'] ?? '',
                    'dj' => $one_detail['dj'] ?? '',
                    'je' => $one_detail['je'] ?? '',
                    'sl' => $one_detail['sl'] ?? '',// 税率
                    'se' => '',
                ];
                $this->detailStoreMysql($insert, '');
            }
        }
    }

    function detailStoreMysql(array $arr, string $nsrsbh): void
    {
        $check_exists = EntInvoiceDetail::create()
            ->addSuffix($arr['fpdm'], $arr['fphm'], 'test')
            ->where([
                        'fpdm' => $arr['fpdm'],
                        'fphm' => $arr['fphm'],
                        'mxxh' => $arr['mxxh']
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
//205,497,383
        $list = AntAuthList::create()
//            ->where('getDataSource', 2)
//            ->where('belong', 41)
////            ->where('id', 1621, '<=')// 这个数字要改
//            ->where('id', 1611, '>')
//and id > 1245

//        and id > 1407
//        and id < 1612
//        and id <= 1694
//        and id > 1132
            ->where("
            getDataSource = 2 
            and belong =41
            and id in(497)
            and (isElectronics LIKE '%属%成功%' OR isElectronics LIKE '%非一般%')
            ")
            ->all();

        // =============================================================================================================
//        dd(count($list));
        foreach ($list as $key => $target) {
            echo $target->getAttr('id');
            // 开票日期止
            $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;

            // 曾经推送过，就说明已经推送过24个月的了，本次推送只推前一个月
            if (empty($target->getAttr('lastReqUrl'))) {
                $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;
            } else {
                $kprqq = Carbon::now()->subMonths(1)->startOfMonth()->timestamp;
            }

            // 拼task请求参数
            $ywBody = [
                'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
                'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
                'nsrsbh' => $target->getAttr('socialCredit'),// 纳税人识别号
            ];

            $addTaskInfo = (new JinCaiShuKeService())->addTaskNew(
                $target->getAttr('socialCredit'),
                $target->getAttr('province'),
                $target->getAttr('city'),
                $ywBody
            );

            $p_traceNo = '';
            if (isset($addTaskInfo['code']) && $addTaskInfo['code'] === 'S000') {
                if (isset($addTaskInfo['result']) && !empty($addTaskInfo['result'])) {
                    if (!empty($addTaskInfo['result']['data'])) {
                        $p_traceNo = trim($addTaskInfo['result']['data']);
                    } else {
                        dd($addTaskInfo, $ywBody, '暂停3');
                    }
                } else {
                    dd($addTaskInfo, $ywBody, '暂停2');
                }
            } else {
                dd($addTaskInfo, $ywBody, '暂停1');
            }

            JinCaiTrace::create()->data([
                                            'entName' => $target->getAttr('entName'),
                                            'socialCredit' => $target->getAttr('socialCredit'),
                                            'code' => $addTaskInfo['code'] ?? '未返回',
                                            'msg' => $addTaskInfo['msg'] ?? '未返回',
                                            'pTraceNo' => $p_traceNo,
                                            'kprqq' => $kprqq,
                                            'kprqz' => $kprqz,
                                        ])->save();

            \co::sleep(1);

            echo '当前第: ' . $key . ' 发送完毕' . PHP_EOL;

        }

        dd('完成');
    }

    function addTaskOne()
    {
        $entname = '北京华品博睿网络技术有限公司';
        $socialCredit = '911101050896860603';
        $province = '北京市';
        $city = '北京市';

        $entname = '凌雄技术（深圳）有限公司';
        $socialCredit = '914403000685577373';
        $province = '深圳市';
        $city = '福田区';

        $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;
        $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;

        $ywBody = [
            'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
            'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
            'nsrsbh' => $socialCredit,// 纳税人识别号
        ];

        $addTaskInfo = (new JinCaiShuKeService())->addTaskNew(
            $socialCredit, $province, $city, $ywBody
        );

        dd($addTaskInfo, $kprqq, $kprqz);
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
CreateMysqlPoolForJinCai::getInstance()->createMysql();

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();
CreateMysqlOrm::getInstance()->createRDS3JinCai();

CreateRedisPool::getInstance()->createRedis();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new jincai_shoudong0($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
