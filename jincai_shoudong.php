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
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\MaYi\MaYiService;
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

class jincai_shoudong extends AbstractProcess
{
    public $currentAesKey = 'jfhgnvhd64yhf854';
    public $iv = '1234567890abcdef';
    public $oss_bucket = 'invoice-mrxd';
    public $oss_expire_time = 86400 * 60;

    public $out_list = [
        '北京分贝通科技有限公司|91110108MA00654L08|北京市|北京市',
        '北京分贝商贸有限公司|91110105MA00823H4A|北京市|北京市',
        '北京分贝国际旅行社有限公司|91110105MA004MNF8T|北京市|北京市',
//        '北京金堤科技有限公司|9111010831813798XE|北京市|北京市',// 设置了平台密码
        '北京金堤征信服务有限公司|91110111MA0076A807|北京市|北京市',
//        '北京商事创新科技有限公司|91110108MA0206K96J|北京市|北京市',// 非一般纳税人
        '北京天眼查科技有限公司|91110108MA00FP4F5A|北京市|北京市',
        '海口天眼查科技有限公司|91460100MAA8YF8T7L|海南省|海口市',
        '企查查科技有限公司|91320594088140947F|江苏省|苏州市',
        '人民数据管理（北京）有限公司|91640500MA774K1K2E|北京市|北京市',
        '苏州贝尔塔数据技术有限公司|913205943021120597|江苏省|苏州市',
        '苏州客找找网络科技有限公司|91320594MA21M50H4M|江苏省|苏州市',
        '苏州新歌科技有限责任公司|91320594MA26UTJJ53|江苏省|苏州市',
        '盐城金堤科技有限公司|91320913MA1MA43G41|江苏省|盐城市',
        '元素征信有限责任公司|911101080628135175|北京市|北京市',
    ];

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

    //上传到oss 发票已经入完mysql
    function sendToOSS($NSRSBH, $bigKprq): bool
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
            ->count();

        //随机文件名
        $fileSuffix = control::getUuid(8);

        if (empty($total)) {
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
                foreach ($list as $oneInv) {
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
                            $oss = new OSSService();
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
                    'big_kprq' => $bigKprq
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

            // 国家政务服务平台 全网 第一个 更多 就业服务专栏
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

    //get分贝通
    function getFenBeiTong(): bool
    {
        $list = [
            //'91110108MA00654L08',
            //'91110105MA00823H4A',
            '91110105MA004MNF8T',
        ];

        foreach ($list as $code) {
            $filename_main = $code . '_main.txt';
            $filename_detail = $code . '_detail.txt';
            //取全部发票写入文件
            $id = 0;
            while (true) {
                $list = EntInvoice::create()
                    ->addSuffix($code, 'test')
                    ->where('nsrsbh', $code)
                    ->where('id', $id, '>')
                    ->field([
                        'id',
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
                    ])->limit(3000)->all();
                //没有数据了
                if (empty($list)) break;
                foreach ($list as $oneInv) {
                    $id = $oneInv->getAttr('id');
                    // 写主票
                    $main = array_map(function ($row) {
                        return str_replace('|', '', trim($row));
                    }, obj2Arr($oneInv));
                    file_put_contents($filename_main, implode('|', $main) . PHP_EOL, FILE_APPEND);
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
                    // 写明细
                    if (!empty($detail)) {
                        foreach ($detail as $fpxx) {
                            $info = array_map(function ($row) {
                                return str_replace('|', '', trim($row));
                            }, obj2Arr($fpxx));
                            file_put_contents($filename_detail, implode('|', $info) . PHP_EOL, FILE_APPEND);
                        }
                    }
                }
            }
        }

        return true;
    }

    protected function run($arg)
    {
        $this->sendToAnt();
        dd(123);


//        foreach (['91110105MA004MNF8T', '91110111MA0076A807', '91110000722617379C'] as $one) {
//            $this->updateAddTask($one);
//        }
//        dd(11111);

        // 这6个有问题
        $error_list = [
            '91320106339391784W',// 部分任务超2000
            '91510107MA65XA3Y77',// 四川的
            '91510105MA66072L7N',// 四川的
            '9133020431684033X3',// 部分任务超2000
            '91330382725250541E',// 部分任务超2000
            '91211022MA0U95ER7Y',// 部分任务超2000

//            '91110108MA00654L08',
//            '91110105MA00823H4A',
//            '91110105MA004MNF8T',
//            '91110111MA0076A807',
//            '91110108MA00FP4F5A',
//            '91460100MAA8YF8T7L',
//            '91320594088140947F',
//            '91640500MA774K1K2E',
//            '91654201MA7ABWLT7L',
//            '91210211588083598A',
//            '91110000722617379C',
//            '912201047826342268',
        ];

        $list = JinCaiTrace::create()->all();

        $continue_at = 0;
        $wupan_continue_at = 0;

        foreach ($list as $index => $one) {

            // ================================================================================================
            $socialCredit = $one->getAttr('socialCredit');
            if ($socialCredit !== '9133020479005351XX' && $continue_at === 0) {
                continue;
            }
            // ================================================================================================

            $rwh_list = (new JinCaiShuKeService())
                ->obtainResultTraceNo($one->getAttr('traceNo'));

            $timeout = time() - $one->getAttr('updated_at');

            foreach ($rwh_list['result'] as $rwh) {
                $province = $one->getAttr('province');
                $socialCredit = $one->getAttr('socialCredit');
                if (in_array($socialCredit, $error_list)) {
                    continue;
                }
                $retry = $rwh['retry'];
                $taskStatus = $rwh['taskStatus'] - 0;
                $traceNo = $rwh['traceNo'];
                $wupanTraceNo = $rwh['wupanTraceNo'];


                // ================================================================================================
                if ($wupanTraceNo !== '9133020479005351XX1667924787422' && $wupan_continue_at === 0) {
                    continue;
                }
                $wupan_continue_at = 1;
                // ================================================================================================


                if ($taskStatus !== 2) {
                    //$check = $this->refreshTask($traceNo);
                    //if (!$check) {
                    //    dd($traceNo);
                    //}
                    //echo $socialCredit . PHP_EOL;
                    //echo $wupanTraceNo . PHP_EOL;
                    //break;
                }


                // 取数
                $w = [
                    $socialCredit,
                    Carbon::now()->format('H:i:s'),
                    $province,
                    $wupanTraceNo
                ];

                echo jsonEncode($w, false) . PHP_EOL;

                $this->getData($socialCredit, $province, $wupanTraceNo);

                $continue_at = 1;

                sleep(2);

            }

        }

        echo 'wanghan123' . PHP_EOL;
    }

    function getData(string $nsrsbh, string $province, string $traceNo)
    {
        $res = (new JinCaiShuKeService())->obtainFpInfo($nsrsbh, $province, $traceNo);
        $this->handleMain($res);
        $res = (new JinCaiShuKeService())->obtainFpDetailInfo($nsrsbh, $province, $traceNo);
        $this->handleDetail($res);
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
                    $this->mainStoreMysql($insert, $nsrsbh, $cxlx, $fplx);
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
                    $this->detailStoreMysql($insert, $nsrsbh, $cxlx, $fplx);
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
//        foreach ($this->out_list as $item) {
//
//            $arr = explode('|', $item);
//
//            $ywBody = [
//                "kprqq" => "2020-12-01",
//                "kprqz" => "2022-10-31",
//                "nsrsbh" => "{$arr[1]}",
//            ];
//
//            for ($try = 3; $try--;) {
//                // 发送 试3次
//                $addTaskInfo = (new JinCaiShuKeService())->addTask(
//                    $arr[1],
//                    $arr[2],
//                    $arr[3],
//                    $ywBody
//                );
//                if (isset($addTaskInfo['code']) && strlen($addTaskInfo['code']) > 1) {
//                    break;
//                }
//                \co::sleep(120);
//            }
//
//            JinCaiTrace::create()->data([
//                'entName' => $arr[0],
//                'socialCredit' => $arr[1],
//                'code' => $addTaskInfo['code'] ?? '未返回',
//                'type' => 1,// 无盘
//                'province' => $addTaskInfo['result']['province'] ?? '未返回',
//                'taskCode' => $addTaskInfo['result']['taskCode'] ?? '未返回',
//                'taskStatus' => $addTaskInfo['result']['taskStatus'] ?? '未返回',
//                'traceNo' => $addTaskInfo['result']['traceNo'] ?? '未返回',
//                'kprqq' => '2020-12-01',
//                'kprqz' => '2022-10-31',
//            ])->save();
//
//            echo $item . PHP_EOL;
//
//            \co::sleep(120);
//
//        }

        $list = AntAuthList::create()
            ->where('id', 684, '<=')
            ->where('id', 450, '>=')
            ->where('isElectronics', '%成功', 'like')
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
                \co::sleep(120);
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

    function updateAddTask($code)
    {
        $info = JinCaiTrace::create()->where('socialCredit', $code)->get();

        // 开票日期止
        $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;

        // 开票日期起
        $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;

        // 拼task请求参数
        $ywBody = [
            'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
            'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
            'nsrsbh' => $code,// 纳税人识别号
        ];

        try {
            for ($try = 3; $try--;) {
                // 发送 试3次
                $addTaskInfo = (new JinCaiShuKeService())->addTask(
                    $code,
                    '北京市',
                    '北京市',
                    $ywBody
                );
                if (isset($addTaskInfo['code']) && strlen($addTaskInfo['code']) > 1) {
                    break;
                }
                \co::sleep(5);
            }

            dump($addTaskInfo);

            $info->update([
                'code' => $addTaskInfo['code'] ?? '未返回',
                'province' => $addTaskInfo['result']['province'] ?? '未返回',
                'taskCode' => $addTaskInfo['result']['taskCode'] ?? '未返回',
                'taskStatus' => $addTaskInfo['result']['taskStatus'] ?? '未返回',
                'traceNo' => $addTaskInfo['result']['traceNo'] ?? '未返回',
                'created_at' => time()]);

            // 还要间隔2分钟
            \co::sleep(120);
        } catch (\Throwable $e) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
            CommonService::getInstance()->log4PHP($content, 'try-catch', 'GetJinCaiTrace.log');
        }

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
