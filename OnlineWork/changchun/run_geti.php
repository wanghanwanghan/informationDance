<?php

require_once '../../vendor/autoload.php';
require_once '../../bootstrap.php';

use App\HttpController\Service\HttpClient\CoHttpClient;
use wanghanwanghan\someUtils\control;

class Test
{
    function strtr_fun($str): string
    {
        if (empty($str)) {
            return '';
        }

        $arr = [
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
        ];

        return strtr($str, $arr);
    }

    function readFile($filename): Generator
    {
        $handle = fopen($filename, 'rb');

        while (feof($handle) === false) {
            yield fgets($handle);
        }

        fclose($handle);
    }

    //分割企业和个体
    function run_qufen()
    {
        $obj_mysql = \wanghanwanghan\someUtils\moudles\laravelDB\laravelDB::getInstance([
            'pridb' => [
                'driver' => 'mysql',
                'host' => 'rm-2ze5r17pbzd3l7rakyo.mysql.rds.aliyuncs.com',
                'port' => '3306',
                'database' => 'prism1',
                'username' => 'mrxd',
                'password' => 'zbxlbj@2018*()',
                'charset' => 'utf8',
                'collation' => 'utf8_general_ci',
                'strict' => false,
                'prefix' => '',
            ],
            'comm' => [
                'driver' => 'mysql',
                'host' => '182.92.78.50',
                'port' => '63306',
                'database' => 'comm',
                'username' => 'chinaiiss',
                'password' => 'zbxlbj@2018*()',
                'charset' => 'utf8',
                'collation' => 'utf8_general_ci',
                'strict' => false,
                'prefix' => '',
            ]
        ])->connection('comm');

        $fp_c = fopen('qiye.txt', 'w+');
        $fp_g = fopen('geti.txt', 'w+');

        while (true) {

            $totalPage = 99999999 / 2000 + 1;

            for ($page = 1; $page <= $totalPage; $page++) {

                $offset = ($page - 1) * 2000;

                $row = $obj_mysql->table('company')->limit(2000)->offset($offset)->get();

                $arr = $row->toArray();

                if (empty($arr)) {
                    break 2;
                }

                //处理
                foreach ($arr as $val) {

                    //0  企业名称
                    //22 统一社会信用代码
                    //15 状态
                    //16 注册资本
                    //8  注册地址
                    //股东
                    //股东注册地
                    //股权比例
                    //认缴金额

                    $str_id = $val->id;
                    $str_0 = str_replace(',', '', $this->strtr_fun($val->name));
                    $str_22 = str_replace(',', '', $this->strtr_fun($val->property1));
                    $str_15 = str_replace(',', '', $this->strtr_fun($val->reg_status));
                    $str_16 = str_replace(',', '', $this->strtr_fun($val->reg_capital));
                    $str_8 = str_replace(',', '', $this->strtr_fun($val->reg_location));

                    $tmp = [
                        $str_id,
                        $str_0,
                        $str_22,
                        $str_15,
                        $str_16,
                        $str_8,
                    ];

                    if ((!empty($str_22) && strlen($str_22) > 5 && substr($str_22, 0, 2) === '91') || (empty($str_22) && !empty($str_0) && mb_substr($str_0, -2) === '公司')) {
                        // qiye
                        file_put_contents('qiye.txt', implode(',', $tmp) . PHP_EOL, FILE_APPEND);
                    } else {
                        // geti
                        file_put_contents('geti.txt', implode(',', $tmp) . PHP_EOL, FILE_APPEND);
                    }


                }

                echo $page . PHP_EOL;


            }


        }


    }

    //
    function run_qiye()
    {
        $obj_mysql = \wanghanwanghan\someUtils\moudles\laravelDB\laravelDB::getInstance([
            'pridb' => [
                'driver' => 'mysql',
                'host' => 'rm-2ze5r17pbzd3l7rakyo.mysql.rds.aliyuncs.com',
                'port' => '3306',
                'database' => 'prism1',
                'username' => 'mrxd',
                'password' => 'zbxlbj@2018*()',
                'charset' => 'utf8',
                'collation' => 'utf8_general_ci',
                'strict' => false,
                'prefix' => '',
            ],
            'comm' => [
                'driver' => 'mysql',
                'host' => '182.92.78.50',
                'port' => '63306',
                'database' => 'comm',
                'username' => 'chinaiiss',
                'password' => 'zbxlbj@2018*()',
                'charset' => 'utf8',
                'collation' => 'utf8_general_ci',
                'strict' => false,
                'prefix' => '',
            ]
        ])->connection('pridb');

        $fp_qiye = fopen('qiye_fill_inv.txt', 'w+');

        foreach ($this->readFile('qiye.txt') as $row) {

            $row = trim($row);

            if (empty($row)) continue;

            $arr = explode(',', $row);

            $inv_arr = $obj_mysql->table('company_investor')->where([
                'company_id' => $arr[0],
                'investor_type' => 2,
            ])->get()->toArray();

            //股东
            //股东注册地
            //股权比例
            //认缴金额

            if (!empty($inv_arr)) {

                //有企业的股东的企业
                foreach ($inv_arr as $one_inv) {

                    $inv_info = $obj_mysql->table('company')->where('id', $one_inv->investor_id)->first();

                    if (empty($inv_info)) {
                        //没有找到股东的情况
                        $inv_name = '未找到';
                        $inv_reg = '未找到';
                    } else {
                        $inv_name = str_replace(',', '', trim($inv_info->name));
                        $inv_reg = str_replace(',', '', trim($inv_info->reg_location));
                    }

                    //金额
                    $inv_money = trim($one_inv->capital);

                    if (empty($inv_money)) {
                        $inv_money = '没数据';
                    } else {
                        if (empty(jsonDecode($inv_money))) {
                            //直接是金额 正则匹配小数
                            preg_match_all('/[0-9]+(\.?[0-9]+)?/', str_replace(' ', '', trim($inv_money)), $money);
                            $inv_money = current(current($money));
                        } else {
                            if (mb_strpos($inv_money, 'amomon')) {
                                $inv_money = mb_substr($inv_money, mb_strpos($inv_money, 'amomon'));
                                preg_match_all('/[0-9]+(\.?[0-9]+)?/', str_replace(' ', '', $inv_money), $money);
                                $inv_money = current(current($money));
                            } else {
                                $inv_money = '没数据';
                            }
                        }
                    }

                    //数据都全了，写文件
                    $temp_data = $row . ',' . $inv_name . ',' . $inv_reg . ',' . $inv_money . PHP_EOL;
                    file_put_contents('qiye_fill_inv.txt', $temp_data, FILE_APPEND);


                }


            }


        }


    }

    //
    function run_geti()
    {
        $obj_mysql = \wanghanwanghan\someUtils\moudles\laravelDB\laravelDB::getInstance([
            'pridb' => [
                'driver' => 'mysql',
                'host' => 'rm-2ze5r17pbzd3l7rak.mysql.rds.aliyuncs.com',//内网
                'port' => '3306',
                'database' => 'prism1',
                'username' => 'mrxd',
                'password' => 'zbxlbj@2018*()',
                'charset' => 'utf8',
                'collation' => 'utf8_general_ci',
                'strict' => false,
                'prefix' => '',
            ],
            'comm' => [
                'driver' => 'mysql',
                'host' => '182.92.78.50',
                'port' => '63306',
                'database' => 'comm',
                'username' => 'chinaiiss',
                'password' => 'zbxlbj@2018*()',
                'charset' => 'utf8',
                'collation' => 'utf8_general_ci',
                'strict' => false,
                'prefix' => '',
            ]
        ])->connection('pridb');

        $fp_qiye = fopen('geti_fill_inv.txt', 'w+');

        foreach ($this->readFile('geti.txt') as $row) {

            $row = trim($row);

            if (empty($row)) continue;

            $arr = explode(',', $row);

            $inv_arr = $obj_mysql->table('company_investor')->where([
                'company_id' => $arr[0],
                'investor_type' => 2,
            ])->get()->toArray();

            //股东
            //股东注册地
            //股权比例
            //认缴金额

            if (!empty($inv_arr)) {

                //有企业的股东的企业
                foreach ($inv_arr as $one_inv) {

                    $inv_info = $obj_mysql->table('company')->where('id', $one_inv->investor_id)->first();

                    if (empty($inv_info)) {
                        //没有找到股东的情况
                        $inv_name = '未找到';
                        $inv_reg = '未找到';
                    } else {
                        $inv_name = str_replace(',', '', trim($inv_info->name));
                        $inv_reg = str_replace(',', '', trim($inv_info->reg_location));
                    }

                    //金额
                    $inv_money = trim($one_inv->capital);

                    if (empty($inv_money)) {
                        $inv_money = '没数据';
                    } else {
                        if (empty(jsonDecode($inv_money))) {
                            //直接是金额 正则匹配小数
                            preg_match_all('/[0-9]+(\.?[0-9]+)?/', str_replace(' ', '', trim($inv_money)), $money);
                            $inv_money = current(current($money));
                        } else {
                            if (mb_strpos($inv_money, 'amomon')) {
                                $inv_money = mb_substr($inv_money, mb_strpos($inv_money, 'amomon'));
                                preg_match_all('/[0-9]+(\.?[0-9]+)?/', str_replace(' ', '', $inv_money), $money);
                                $inv_money = current(current($money));
                            } else {
                                $inv_money = '没数据';
                            }
                        }
                    }

                    //数据都全了，写文件
                    $temp_data = $row . ',' . $inv_name . ',' . $inv_reg . ',' . $inv_money . PHP_EOL;
                    file_put_contents('geti_fill_inv.txt', $temp_data, FILE_APPEND);


                }


            }


        }


    }
}

(new Test())->run_geti();
