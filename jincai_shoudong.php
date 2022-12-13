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
use App\HttpController\Service\LongXin\FinanceRange;
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
    public $currentAesKey = 'rycn45bmdklhshfs';
    public $iv = '1234567890abcdef';
    public $oss_bucket = 'invoice-mrxd';
    public $oss_expire_time = 86400 * 60;

    public $out_list = [
        '浙江正理生能科技有限公司|91330382725250541E|浙江省|温州市',
//        '北京分贝通科技有限公司|91110108MA00654L08|北京市|北京市',
//        '北京分贝商贸有限公司|91110105MA00823H4A|北京市|北京市',
//        '北京分贝国际旅行社有限公司|91110105MA004MNF8T|北京市|北京市',
////        '北京金堤科技有限公司|9111010831813798XE|北京市|北京市',// 设置了平台密码
//        '北京金堤征信服务有限公司|91110111MA0076A807|北京市|北京市',
////        '北京商事创新科技有限公司|91110108MA0206K96J|北京市|北京市',// 非一般纳税人
//        '北京天眼查科技有限公司|91110108MA00FP4F5A|北京市|北京市',
//        '海口天眼查科技有限公司|91460100MAA8YF8T7L|海南省|海口市',
//        '企查查科技有限公司|91320594088140947F|江苏省|苏州市',
//        '人民数据管理（北京）有限公司|91640500MA774K1K2E|北京市|北京市',
//        '苏州贝尔塔数据技术有限公司|913205943021120597|江苏省|苏州市',
//        '苏州客找找网络科技有限公司|91320594MA21M50H4M|江苏省|苏州市',
//        '苏州新歌科技有限责任公司|91320594MA26UTJJ53|江苏省|苏州市',
//        '盐城金堤科技有限公司|91320913MA1MA43G41|江苏省|盐城市',
//        '元素征信有限责任公司|911101080628135175|北京市|北京市',
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

    function getLogo()
    {
        $list = [
            'logo/445/31119293.png',
            'logo/220/27823324.png',
            'logo/300/30645036.png',
            'logo/509/161277.png',
            'logo/473/187353.png',
            'logo/304/30961968.png',
            'logo/289/30079265.png',
            'logo/441/30085049.png',
            'qxb_logo/202210/141ee4397eadeded3dd3483a5f6d9563.jpg',
            'logo/323/4419.png',
            'logo/122/28069498.png',
            'logo/197/177861.png',
            'logo/464/193488.png',
            'logo/352/175968.png',
            'logo/411/161179.png',
            'logo/479/28639711.png',
            'logo/433/28644785.png',
            'logo/454/27811782.png',
            'logo/278/28427030.png',
            'logo/325/195909.png',
            'logo/7/181255.png',
            'qxb_logo/202211/ae03b6ee85dbffc3c41b1c26a554f820.jpg',
            'qxb_logo/202211/aad6e00d9495e8423a68c3a48c30c928.jpg',
            'logo/145/28460689.png',
            'logo/355/31637859.png',
            'logo/466/31004114.png',
            'logo/289/192801.png',
            'qxb_logo/202211/02e9ffb6e23654f32c586d7de6274bfb.jpg',
            'logo/130/31091330.png',
            'logo/0/30987264.png',
            'logo/505/29882873.png',
            'logo/204/32276172.png',
            'logo/460/137164.png',
            'logo/443/10381243.png',
            'logo/69/28088901.png',
            'logo/324/27945796.png',
            'logo/75/63563.png',
            'logo/387/27889539.png',
            'logo/47/29206575.png',
            'qxb_logo/202210/b08c1f38468270625d79cdec296eec66.jpg',
            'logo/438/10557366.png',
            'logo/314/31109946.png',
            'logo/85/27770453.png',
            'logo/43/29132843.png',
            'logo/374/28495734.png',
            'logo/325/31020869.png',
            'logo/303/150831.png',
            'logo/149/177301.png',
            'logo/153/173721.png',
            'logo/421/28067749.png',
            'logo/163/172195.png',
            'logo/230/12642022.jpg',
            'logo/312/29182264.png',
            'logo/40/28019752.png',
            'logo/198/28417222.png',
            'logo/425/27958185.png',
            'logo/43/28436523.png',
            'logo/84/27769428.png',
            'logo/268/27824908.png',
            'logo/314/177466.png',
            'logo/64/31040064.png',
            'logo/472/140248.png',
            'qxb_logo/202211/e444d80b544e1db53a32e12e972fe59d.jpg',
            'logo/483/28393443.png',
            'logo/416/30965664.png',
            'logo/29/27775517.png',
            'logo/18/28146706.png',
            'logo/194/27852482.png',
            'qxb_logo/202211/0177241008c12f91c77a729ea94294bc.jpg',
            'logo/219/27915483.png',
            'logo/106/31138922.png',
            'logo/399/187279.png',
            'logo/59/10644027.jpg',
            'logo/12/32494092.png',
            'logo/414/31127966.png',
            'logo/248/11206904.png',
            'logo/465/12403153.png',
            'logo/7/27990023.png',
            'qxb_logo/202210/13648da52d00f41980a064a493f492c3.jpg',
            'logo/424/27969448.png',
            'logo/483/21915107.png',
            'logo/92/28478044.png',
            'logo/64/27881536.png',
            'logo/126/24983166.png',
            'logo/142/30215310.png',
            'logo/133/28418693.png',
            'logo/400/28573072.png',
            'logo/476/12613596.jpg',
            'logo/68/28453444.png',
            'logo/360/110440.jpg',
            'logo/169/192681.png',
            'logo/331/187211.png',
            'logo/221/28035805.png',
            'logo/283/28417307.png',
            'logo/325/28566853.png',
            'logo/150/27798.jpg',
            'logo/184/29174968.png',
            'logo/255/27899135.png',
            'logo/484/28326372.png',
            'logo/152/30939288.png',
            'logo/80/12884560.png',
            'logo/378/29140346.png',
            'qxb_logo/202211/8f94fdcc70aa515716e2c5741c46667d.jpg',
            'logo/24/166424.png',
            'logo/63/29271615.png',
            'logo/288/28532512.png',
            'logo/457/27932617.png',
            'logo/402/12669842.jpg',
            'logo/167/193191.png',
            'qcc_logo/api/202211/7aded297e395113539ab603e5b426f8a.jpg',
            'logo/277/27946773.png',
            'logo/190/10897598.jpg',
            'logo/42/38347306.jpg',
            'logo/463/29901263.png',
            'logo/352/187232.png',
            'logo/437/29473205.png',
            'qxb_logo/202211/26e7b04edce3f20cfd5ab641c0ff869e.jpg',
            'qxb_logo/202211/b6fe74594bd0356298375e28b1c58337.jpg',
            'logo/194/31574210.png',
            'qxb_logo/202210/80416b5500309957e5a3bb94ebbac077.jpg',
            'logo/176/29169328.png',
            'logo/392/32086920.png',
            'logo/51/28558899.png',
            'logo/55/28568119.png',
            'logo/174/32678574.png',
            'logo/430/166318.png',
            'logo/436/184244.png',
            'logo/178/10444466.jpg',
            'logo/441/27945913.png',
            'logo/195/28218563.png',
            'logo/294/30613798.png',
            'logo/279/194327.png',
            'logo/53/173621.png',
            'logo/186/32616122.png',
            'logo/198/28094662.png',
            'logo/300/28175148.png',
            'logo/41/10884649.jpg',
            'logo/204/30173900.png',
            'logo/323/136515.png',
            'logo/357/133477.png',
            'logo/137/28442249.png',
            'qxb_logo/202210/54c8352755d94b7689abfa0d908864a5.jpg',
            'logo/471/30449623.png',
            'logo/146/28263058.png',
            'logo/106/194666.png',
            'logo/199/30259399.png',
            'logo/354/11893602.png',
            'logo/312/28337464.png',
            'logo/504/29180920.png',
            'logo/486/176614.png',
            'logo/422/11957158.png',
            'logo/85/28495445.png',
            'logo/461/27996621.png',
            'qxb_logo/202211/92478443cccf7965756f37f32be7b433.jpg',
            'logo/110/29366382.png',
            'logo/145/27949201.png',
            'logo/373/17751925.png',
            'logo/419/167843.png',
            'logo/242/26354.jpg',
            'logo/283/24859.jpg',
            'logo/453/32741829.png',
            'logo/353/27800929.png',
            'logo/445/30434237.png',
            'logo/304/28525360.png',
            'logo/494/155118.png',
            'logo/265/28552457.png',
            'logo/188/27860668.png',
            'logo/241/182001.png',
            'logo/7/188935.png',
            'logo/179/27814067.png',
            'logo/340/16736596.png',
            'logo/366/168302.png',
            'logo/275/185619.png',
            'logo/281/30474521.png',
            'logo/496/27906032.png',
            'logo/313/28488505.png',
            'logo/49/28467761.png',
            'logo/238/28230894.png',
            'logo/439/28289975.png',
            'logo/202/28038858.png',
            'logo/318/28147006.png',
            'logo/296/30939432.png',
            'logo/358/174950.png',
            'logo/319/31221567.png',
            'logo/76/32500812.png',
            'logo/510/30218238.png',
            'logo/278/29918998.png',
            'logo/21/30479381.png',
            'logo/347/38238555.png',
            'logo/320/195392.png',
            'logo/423/19033511.png',
            'qxb_logo/202211/0ba6db627c4d9f0e86cc55d8498804ea.jpg',
            'logo/455/29915591.png',
            'logo/380/30934396.png',
            'logo/352/28049248.png',
            'logo/156/38748316.png',
            'logo/155/41283227.jpg',
            'logo/136/31156872.png',
            'qxb_logo/202210/54c8352755d94b7689abfa0d908864a5.jpg',
            'logo/429/27770285.png',
            'logo/31/173599.png',
            'logo/497/196081.png',
            'logo/495/27919343.png',
            'logo/313/27949369.png',
            'logo/447/7615.jpg',
            'logo/413/28039069.png',
            'qxb_logo/202211/a55ff2eaffc825dedf77cd46ea2c4d97.jpg',
            'logo/385/28503425.png',
            'logo/273/20693265.png',
            'qxb_logo/202210/f47ad80441e81b7cf5daa28d1b42fff8.jpg',
            'logo/37/146469.png',
            'qcc_logo/api/202210/9e90ce522f55f18be14d858de1303f5d.jpg',
            'logo/15/29901839.png',
            'logo/192/29183680.png',
            'logo/160/180896.png',
            'logo/107/194667.png',
            'logo/346/28392794.png',
            'logo/464/179664.png',
            'logo/415/25503.jpg',
            'qxb_logo/202211/8da91e2a28134d801206f3219c10c814.jpg',
            'logo/116/195188.png',
            'logo/423/187303.png',
            'logo/430/30134702.png',
            'logo/10/173066.png',
            'logo/437/27757493.png',
            'logo/404/177556.png',
            'logo/95/194655.png',
            'logo/177/28659377.png',
            'logo/160/158368.png',
            'logo/151/38881431.png',
            'logo/41/30084137.png',
            'logo/392/29175688.png',
            'logo/275/28309779.png',
            'logo/383/28003199.png',
            'logo/87/28633687.png',
            'logo/277/12101397.jpg',
            'logo/271/131855.png',
            'logo/352/30665568.png',
            'logo/461/30414797.png',
            'qxb_logo/202211/b6fe74594bd0356298375e28b1c58337.jpg',
            'logo/453/32213445.png',
            'logo/2/41799682.jpg',
            'logo/178/133298.png',
            'logo/448/32321472.png',
            'logo/448/11939776.png',
            'logo/97/30079073.png',
            'logo/161/28382881.png',
            'logo/293/28038949.png',
            'logo/388/27724164.png',
            'logo/312/29180728.png',
            'logo/28/16084508.png',
            'logo/225/27951841.png',
            'logo/162/186530.png',
            'logo/333/28017997.png',
            'logo/473/28507097.png',
            'logo/218/27925722.png',
            'logo/30/168478.png',
            'logo/321/195905.png',
            'qxb_logo/202210/deefe1fb528cdec61d9c00e4579a655c.jpg',
            'logo/108/30173804.png',
            'logo/284/27831580.png',
            'qxb_logo/202210/482a2bef492302e7200f0f5ee782f3cb.jpg',
            'logo/305/164657.png',
            'logo/87/11369559.png',
            'logo/441/30075833.png',
            'logo/50/28127794.png',
            'logo/412/31338908.png',
            'logo/475/28392411.png',
            'logo/249/157433.png',
            'qcc_invest/202211/10/_7d43d419-c8c7-464f-8d78-18f7bfe7a5b8.jpg',
            'logo/0/28148224.png',
            'logo/184/29176504.png',
            'logo/310/20840758.png',
            'logo/423/27906983.png',
            'logo/74/37429834.png',
            'logo/376/28474744.png',
            'logo/142/138894.png',
            'logo/466/31133138.png',
            'logo/17/28264465.png',
            'logo/157/28354717.png',
            'logo/216/1240.jpg',
            'logo/492/31749612.png',
            'logo/101/11118181.jpg',
            'logo/202/29137610.png',
            'logo/57/27950137.png',
            'logo/448/28487616.png',
            'logo/321/833.jpg',
            'logo/257/57089.png',
            'logo/33/32710689.png',
            'logo/501/27773429.png',
            'logo/257/30479105.png',
            'logo/76/194124.png',
            'logo/145/27939985.png',
            'logo/119/177783.png',
            'logo/365/181101.png',
            'logo/109/28529773.png',
            'logo/145/28092049.png',
            'logo/118/187510.png',
            'logo/279/28318487.png',
            'logo/223/27902687.png',
            'qxb_logo/202210/1afa624f25487be985b9a849b479c3ea.jpg',
            'logo/58/195642.png',
            'logo/358/17053542.png',
            'logo/356/28129124.png',
            'logo/340/32490324.png',
            'logo/339/28514643.png',
            'logo/393/28032393.png',
            'logo/141/194189.png',
            'logo/185/194745.png',
            'logo/350/79198.jpg',
            'logo/149/141973.png',
            'logo/35/5667.png',
            'qxb_logo/202211/b928f35df7f0e48b2202622800d6c129.jpg',
            'logo/240/164592.png',
            'logo/318/174910.png',
            'logo/402/178578.png',
            'logo/321/30089537.png',
            'logo/308/27680052.png',
            'logo/389/30445445.png',
            'logo/237/30442733.png',
            'logo/69/27848261.png',
            'logo/166/30714022.png',
            'logo/107/27704427.png',
            'logo/442/28462010.png',
            'logo/340/27811156.png',
            'logo/13/34700813.png',
            'logo/127/178815.png',
            'logo/171/30698155.png',
            'logo/281/28427033.png',
            'logo/325/28030789.png',
            'logo/53/28037173.png',
            'logo/307/33281331.png',
            'logo/66/31138370.png',
            'logo/228/30614244.png',
            'qxb_logo/202210/54c8352755d94b7689abfa0d908864a5.jpg',
            'logo/442/171450.png',
            'logo/337/187217.png',
            'logo/3/10310659.png',
            'logo/506/27731450.png',
            'logo/377/27931513.png',
            'qxb_logo/202211/feab727ec3359875cc2f820c363c6175.jpg',
            'qxb_logo/202211/a30e5670f2c2f366886abd3198d5aafc.jpg',
            'logo/442/122810.PNG',
            'logo/41/132649.png',
            'logo/164/27741348.png',
            'logo/504/37886456.jpg',
            'logo/112/28653168.png',
            'logo/237/172781.png',
            'logo/196/27776196.png',
            'logo/112/28427888.png',
            'logo/225/27934945.png',
            'qxb_logo/202211/ab20e50f183c346757f9432581ee821a.jpg',
            'logo/430/28542894.png',
            'logo/497/28660721.png',
            'logo/110/185454.png',
            'logo/425/28464041.png',
            'logo/45/13318189.png',
            'logo/41/27940393.png',
            'logo/42/31136810.png',
            'logo/191/28513471.png',
            'qxb_logo/202211/467fd23c8843f286ea5569fb5725d130.jpg',
            'logo/409/326553.jpg',
            'logo/335/29459279.png',
            'logo/269/10562317.png',
            'logo/343/32025943.png',
            'qxb_logo/20229/9d623ee74fbd33e9a01c7cc8412d9ef4.jpg',
            'logo/152/28468376.png',
            'logo/128/27925120.png',
            'logo/379/39047035.png',
            'logo/256/137472.png',
            'logo/131/27978883.png',
            'logo/106/31134314.png',
            'logo/473/28573145.png',
            'logo/260/30934276.png',
            'logo/214/142038.png',
            'logo/356/27775332.png',
            'logo/229/31100645.png',
            'logo/359/32713063.png',
            'logo/371/27939699.png',
            'logo/71/28465735.png',
            'logo/2/29864450.png',
            'logo/492/27830764.png',
            'logo/284/2332.jpg',
            'logo/175/27917999.png',
            'logo/145/30077585.png',
            'logo/177/27933361.png',
            'logo/378/11212666.png',
            'logo/412/24988.jpg',
            'logo/350/27822942.png',
            'logo/140/27896972.png',
            'logo/360/37521768.png',
            'logo/207/28604623.png',
            'logo/454/30189510.png',
            'logo/253/32162557.png',
            'logo/165/28673701.png',
            'logo/421/28090789.png',
            'logo/455/20047815.png',
            'logo/52/27818548.png',
            'logo/9/27945481.png',
            'logo/140/194188.png',
            'logo/231/28601575.png',
            'logo/440/29178808.png',
            'logo/322/31010114.png',
            'logo/402/27937170.png',
            'logo/435/28425139.png',
            'logo/18/19367954.png',
            'logo/100/28148324.png',
            'logo/172/32779948.png',
            'logo/256/32308992.png',
            'logo/247/194295.png',
            'logo/200/28315848.png',
            'logo/350/159070.png',
            'logo/123/186491.png',
            'logo/448/67008.jpg',
            'logo/289/30083873.png',
            'logo/25/27930649.png',
            'logo/64/30961728.png',
            'logo/429/28071853.png',
            'logo/26/27904538.png',
            'logo/122/11024506.png',
            'logo/65/30076481.png',
            'logo/392/187272.png',
            'logo/254/153342.png',
            'logo/244/28206324.png',
            'logo/252/28304636.png',
            'logo/416/28549024.png',
            'logo/100/27776100.png',
            'logo/119/30109303.png',
            'logo/379/192379.png',
            'logo/27/27915291.png',
            'logo/200/29175496.png',
            'logo/296/29181736.png',
            'logo/135/194695.png',
            'logo/371/28444019.png',
            'logo/258/21530882.png',
            'logo/95/28492895.png',
            'logo/116/28097652.png',
            'logo/436/11951540.jpg',
            'logo/422/29246374.png',
            'logo/186/28037306.png',
            'logo/217/29155545.png',
            'logo/230/29267686.png',
            'logo/508/32688124.png',
            'logo/477/61405.png',
            'logo/209/28151505.png',
            'logo/56/29183032.png',
            'logo/264/161544.png',
            'logo/76/27705420.png',
            'logo/505/30076409.png',
            'logo/89/27941977.png',
            'logo/20/27826708.png',
            'logo/351/165215.png',
            'logo/331/27917643.png',
            'logo/424/192936.png',
            'logo/464/28498896.png',
            'logo/107/27824747.png',
            'logo/460/27724236.png',
            'logo/397/28087693.png',
            'logo/59/195131.png',
            'logo/83/11871315.png',
            'logo/387/28354947.png',
            'logo/219/148187.png',
            'logo/127/13338751.png',
            'logo/28/28421148.png',
            'logo/210/27853010.png',
            'logo/434/28329906.png',
            'logo/175/8261295.jpg',
            'logo/390/142214.png',
            'logo/371/27808115.png',
            'logo/273/59153.png',
            'logo/392/12702088.jpg',
            'logo/251/28514043.png',
            'logo/459/27595.jpg',
            'logo/97/23573089.png',
            'logo/418/28217250.png',
            'logo/244/194292.png',
            'logo/400/194448.png',
            'logo/41/176681.png',
            'logo/414/28599710.png',
            'logo/192/29176000.png',
            'logo/350/28482398.png',
            'logo/313/176953.png',
            'logo/78/28398670.png',
            'logo/215/11841751.png',
            'logo/346/28663642.png',
            'logo/5/41767429.jpg',
            'logo/68/27809860.png',
            'logo/125/173181.png',
            'logo/306/27979570.png',
            'logo/34/28511266.png',
            'logo/333/27932493.png',
            'logo/393/40841.png',
            'logo/29/798749.jpg',
            'logo/455/162247.png',
            'logo/341/27955029.png',
            'logo/422/173478.png',
            'logo/28/28434972.png',
            'logo/371/174963.png',
            'logo/394/27968906.png',
            'logo/107/15467.jpg',
            'logo/327/30433095.png',
            'logo/309/37997877.png',
            'logo/410/31123866.png',
            'logo/172/27893420.png',
            'logo/64/28030528.png',
            'logo/94/190046.png',
            'logo/496/28231152.png',
            'logo/23/29904407.png',
            'logo/306/28503346.png',
            'logo/370/28483442.png',
            'logo/115/160371.png',
            'logo/379/28532091.png',
            'logo/247/29894903.png',
            'logo/373/28181877.png',
            'logo/234/155882.png',
            'logo/41/655401.jpg',
            'logo/147/31241363.png',
            'logo/134/194694.png',
            'logo/259/18334979.png',
            'logo/488/28303336.png',
            'logo/357/30459749.png',
            'logo/201/27502793.png',
            'logo/406/36246.jpg',
            'logo/435/28101555.png',
            'logo/426/28479914.png',
            'logo/135/26666119.png',
            'logo/236/28489452.png',
            'logo/53/40565301.jpg',
            'logo/507/193531.png',
            'logo/510/23044094.png',
            'logo/216/179416.png',
            'logo/330/162122.png',
            'logo/441/27835321.png',
            'logo/375/31765879.png',
            'logo/361/30701417.png',
            'logo/88/13071960.jpg',
            'logo/57/8834105.jpg',
            'logo/424/32722856.png',
            'logo/188/27842236.png',
            'logo/78/6734.jpg',
            'logo/2/194050.png',
            'logo/485/28636645.png',
            'logo/344/10989912.png',
            'logo/123/10689659.png',
            'logo/92/184924.png',
            'logo/181/30024885.png',
            'logo/224/32321760.png',
            'logo/288/151328.png',
            'logo/17/30084113.png',
            'logo/462/12571598.png',
            'logo/82/28447826.png',
            'logo/208/29179600.png',
            'logo/211/28324051.png',
            'logo/325/27759941.png',
            'logo/484/25770980.png',
            'logo/94/31624798.png',
            'logo/391/32715655.png',
            'logo/492/28439020.png',
            'logo/81/27936337.png',
            'logo/37/28529701.png',
            'logo/86/143446.png',
            'logo/89/12670041.jpg',
            'logo/509/30427133.png',
            'logo/296/31174440.png',
            'logo/319/188223.png',
            'logo/157/32244381.png',
            'logo/214/32099542.png',
            'logo/493/28608493.png',
            'logo/88/29177944.png',
            'logo/384/35003776.png',
            'logo/79/28525135.png',
            'logo/78/27856462.png',
            'logo/135/31120007.png',
            'logo/149/30478997.png',
            'logo/509/94205.jpg',
            'logo/82/31143506.png',
            'logo/157/175773.png',
            'logo/344/185688.png',
            'logo/233/30477545.png',
            'logo/336/12672848.png',
            'logo/330/171850.png',
            'logo/258/28561666.png',
            'logo/196/1220.jpg',
            'logo/78/18638414.png',
            'logo/361/30079337.png',
            'logo/335/171343.png',
            'logo/511/29901311.png',
            'logo/453/140229.png',
            'logo/436/30171572.png',
            'logo/236/28584172.png',
            'logo/384/29182336.png',
            'logo/175/29209263.png',
            'logo/12/27780108.png',
            'logo/405/28483989.png',
            'logo/400/11197840.jpg',
            'logo/302/27921198.png',
            'logo/13/46093.jpg',
            'logo/296/32476456.png',
            'logo/15/144399.png',
            'logo/105/12039785.png',
            'logo/436/28387252.png',
            'logo/21/30970901.png',
            'logo/194/181442.png',
            'logo/175/141999.png',
            'logo/148/31941268.png',
            'logo/177/26675889.png',
            'logo/76/15937100.png',
            'logo/378/47482.jpg',
            'logo/128/30939264.png',
            'logo/170/28511914.png',
            'logo/396/31942540.png',
            'logo/248/30939384.png',
            'logo/494/16962542.png',
            'logo/163/193699.png',
            'logo/391/180103.png',
            'logo/123/28982907.png',
            'logo/139/32959115.png',
            'logo/137/189577.png',
            'logo/383/27983231.png',
            'logo/85/49749.jpg',
            'logo/390/174982.png',
            'logo/461/8141.jpg',
            'logo/115/188531.png',
            'logo/366/187758.png',
            'logo/240/173808.png',
            'logo/262/11260678.png',
            'logo/165/27770021.png',
            'logo/368/27969904.png',
            'logo/505/179193.png',
            'logo/310/28230966.png',
            'logo/78/29684302.png',
            'logo/454/28587462.png',
            'logo/17/173585.png',
            'logo/130/196226.png',
            'logo/58/194618.png',
            'logo/409/27942809.png',
            'logo/123/179835.png',
            'logo/384/30976896.png',
            'logo/314/150330.png',
            'logo/67/147011.png',
            'logo/305/165681.png',
            'logo/423/30431655.png',
            'logo/424/191400.png',
            'logo/368/11709808.png',
            'logo/76/30616140.png',
            'logo/209/30084305.png',
            'logo/424/163752.png',
            'logo/459/20939.jpg',
            'logo/145/11913873.png',
            'logo/197/28046533.png',
            'logo/58/39076410.png',
            'logo/243/178931.png',
            'logo/161/32749729.png',
            'logo/51/32164915.png',
            'logo/319/150335.png',
            'logo/269/30157581.png',
            'logo/4/23589380.png',
            'logo/409/186777.png',
            'logo/79/146511.png',
            'logo/141/28018317.png',
            'logo/329/28573001.png',
            'logo/106/12351082.png',
            'logo/475/28436955.png',
            'logo/317/27755837.png',
            'logo/329/102217.jpg',
            'logo/13/190477.png',
            'logo/192/32599744.png',
            'logo/307/28319539.png',
            'logo/291/32944419.png',
            'logo/228/27781860.png',
            'logo/371/192883.png',
            'logo/409/194457.png',
            'logo/144/37842064.png',
            'logo/476/11625948.png',
            'logo/22/27935766.png',
            'logo/332/27925324.png',
            'logo/165/153253.png',
            'logo/377/27937657.png',
            'logo/440/28438456.png',
            'logo/485/28426213.png',
            'logo/449/27946945.png',
            'logo/406/31511446.png',
            'logo/125/28341373.png',
            'logo/327/194375.png',
            'logo/232/150248.png',
            'logo/124/27944572.png',
            'logo/314/28410170.png',
            'logo/145/30063761.png',
            'logo/13/30421517.png',
            'logo/139/28280971.png',
            'logo/445/4541.jpg',
            'logo/145/28613777.png',
            'logo/446/149950.png',
            'logo/175/63151.JPG',
            'logo/69/32741445.png',
            'logo/174/38880430.png',
            'logo/95/139359.png',
            'logo/332/30618444.png',
            'logo/358/21350.jpg',
            'logo/451/33203139.png',
            'logo/25/10220057.jpg',
            'logo/376/31041912.png',
            'logo/109/30463085.png',
            'logo/396/38768012.png',
            'logo/326/30194502.png',
        ];

        foreach ($list as $path) {
            if (preg_match('/^logo/', $path)) {
                $path = str_replace('logo', '', $path);
                $url = 'https://img-mrxd.oss-cn-beijing.aliyuncs.com/ent-logo-hd-saic-202208' . $path;
                $name = explode('/', $path);
                $name = last($name);
                $commod = "wget -qO ./Temp/{$name} {$url}";
                $system_res = system($commod);
            }
        }

        dd(123123123123123);
    }

    protected function run($arg)
    {
        $this->addTask();
        dd(123);

        $list = JinCaiTrace::create()->all();

        foreach ($list as $key => $item) {
            $check = $key % 2;
            if ($check === 0) continue;
            for ($page = 1; $page <= 9999999; $page++) {
                $param = [
                    $item->getAttr('socialCredit'),
                    date('Y-m-d', $item->getAttr('kprqq')),
                    date('Y-m-d', $item->getAttr('kprqz')),
                    $page
                ];
                $main = (new JinCaiShuKeService())->obtainFpInfoNew(false, ...$param);
                dd($main);
                $return_check = $this->handleMain($main, $item->getAttr('socialCredit'));
                if (!$return_check) {
                    // 没有票了
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

    function handleMain($main, $nsrsbh): bool
    {
        // 默认是一张都没有
        $return_check = false;
        if (!empty($main['result']['data']['content'])) {
            $return_check = true;
            foreach ($main['result']['data']['content'] as $one_main) {
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
        return $return_check;
    }

    private function mainStoreMysql(array $arr, string $nsrsbh): void
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
        $list = AntAuthList::create()
            ->where('getDataSource', 2)
            ->where('belong', 41)
            ->where('id', 829, '<')
            ->where("isElectronics LIKE '%属%成功%' OR isElectronics LIKE '%非一般%'")
            ->all();

        // =============================================================================================================

        foreach ($list as $key => $target) {

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

            // ===========================================================================
            if ($key <= 191) {
                continue;
            }
            // ===========================================================================

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
