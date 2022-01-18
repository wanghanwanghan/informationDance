<?php

require_once './vendor/autoload.php';

$fp = fopen('/mnt/tiaoma.log', 'r');

$i = 1;

// 0、1、2，其中 0 是提取关键字，1 是切字分词， 2 是获取词性标注。

while (feof($fp) === false) {

    $str = trim(fgets($fp));

    $arr = explode('|||', $str);

    if (count($arr) !== 3) {
        continue;
    }

    $entname = trim($arr[1]);
    $terms = trim($arr[2]);

    if (empty($entname) || empty($terms)) {
        continue;
    }

    $jieba = jieba($terms, 0);

    if (!empty($jieba)) {
        $jieba = array_map(function ($row) {
            if (preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $row) > 0) {
                return $row;
            }
            return null;
        }, $jieba);
    } else {
        continue;
    }

    $jieba = array_values(array_filter($jieba));

    if (empty($jieba)) {
        continue;
    }

    $insert = [];

    foreach ($jieba as $one) {
        $insert[] = [
            'entname' => $entname,
            'jieba' => $one,
        ];
    }

    $mysql = \wanghanwanghan\someUtils\moudles\laravelDB\laravelDB::getInstance([
        'SPTM' => [
            'driver' => 'mysql',
            'host' => 'rm-2ze5r17pbzd3l7rakyo.mysql.rds.aliyuncs.com',
            'port' => '3306',
            'database' => 'shang_pin_tiao_ma',
            'username' => 'mrxd',
            'password' => 'zbxlbj@2018*()',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'strict' => false,
            'prefix' => '',
        ]
    ])->connection('SPTM')->table('tiao_ma_' . ord($entname) % 20);

    $mysql->insert($insert);

    $i++;

    if ($i % 100000 === 0) {
        $o_o = date('Y-m-d H:i:s') . " 已经处理到了第 {$i} 行";
        file_put_contents(
            '/home/wwwroot/informationDance/Static/Log/jieba.log', $o_o . PHP_EOL, FILE_APPEND
        );
    }

}


