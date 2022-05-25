<?php

namespace App\HttpController\Business\Admin\TenderingAndBidding;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use Carbon\Carbon;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config;
use wanghanwanghan\someUtils\control;

class TenderingAndBiddingController extends TenderingAndBiddingBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function do_strtr($str): string
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
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => ''
        ]);

        return str_replace(["\r\n", "\r", "\n", '|', "\t", ' '], '', trim($str));
    }

    function mysqlCli(): Client
    {
        $conf = new Config([
            'host' => CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'),
            'port' => 3306,
            'user' => CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'),
            'password' => CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'),
            'database' => 'zhao_tou_biao',
            'timeout' => 5,
            'charset' => 'utf8mb4',
        ]);

        return new Client($conf);
    }

    function getList(): bool
    {
        $date = Carbon::now()->format('Y-m-d');

        $cli = $this->mysqlCli();

        $cli->queryBuilder()
            ->where('updated_at', "{$date}%", 'LIKE')
            ->get('zhao_tou_biao');

        try {
            $res = $cli->execBuilder();
        } catch (\Throwable $e) {
            $res = null;
        }

        if (!empty($res)) {
            //处理数据
            $res = obj2Arr($res);
            foreach ($res as $key => $arr) {
                foreach ($arr as $k => $v) {
                    $v = $this->do_strtr($v);
                    $res[$key][$k] = mb_strlen($v) > 100 ? mb_substr($v, 0, 100) . '...' : $v;
                }
            }
        }

        return $this->writeJson(200, null, $res);
    }

    function createZip(): bool
    {
        $zip_arr = $this->getRequestData('zip_arr');

        $data = [];

        foreach ($zip_arr as $one) {
            $cli = $this->mysqlCli();
            $cli->queryBuilder()->where([
                'DLSM_UUID' => $one['DLSM_UUID'] === '--' ? '' : $one['DLSM_UUID'],
                '中标供应商' => $one['中标供应商'] === '--' ? '' : $one['中标供应商'],
                '中标金额' => $one['中标金额'] === '--' ? '' : $one['中标金额'],
            ])->getOne('zhao_tou_biao');
            try {
                $res = $cli->execBuilder();
            } catch (\Throwable $e) {
                $res = null;
            }

            CommonService::getInstance()->log4PHP($one);


            if (!empty($res)) {
                $res = obj2Arr($res);
                foreach ($res as $k => $v) {
                    $v = $this->do_strtr($v);
                    $res[$k] = mb_strlen($v) > 1000 ? mb_substr($v, 0, 1000) . '...' : $v;
                }
                $data[] = $res;
            }
        }

        CommonService::getInstance()->log4PHP($data);

        $filename = control::getUuid();

        if (!empty($data)) {
            $config = ['path' => TEMP_FILE_PATH];
            $excel = new \Vtiful\Kernel\Excel($config);
            $fileObject = $excel->constMemory("{$filename}.xlsx", null, false);
            $res = $fileObject->data($data)->output();
        }

        return $this->writeJson(200, null, $filename);
    }

}