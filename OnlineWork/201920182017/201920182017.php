<?php

include '../../vendor/autoload.php';

$list = [
    '上海动联信息技术股份限公司',
    '上海百络信息技术有限公司',
    '中创安控（北京）科技有限公司',
    '云南瑞迅达通信技术有限公司',
    '云卫科技（北京）有限公司',
    '内蒙古网智科技服务有限责任公司',
    '北京天下信安技术有限公司',
    '北京天信恒安科技有限公司',
    '北京安证通信息科技股份有限公司',
    '北京极安融信息技术有限公司',
    '北京神州绿盟科技有限公司',
    '北京路兴达源建筑工程材料有限公司',
    '北京金山安全软件有限公司',
    '北京霍因科技有限公司',
    '华测电子认证有限责任公司',
    '南京索特软件有限公司',
    '合肥安珀信息科技有限公司',
    '吉林伍陆柒捌股份有限公司',
    '大庆中基石油通信建设有限公司',
    '山东贝格通软件科技有限公司',
    '广东诚晟交通科技投资有限公司',
    '广州安锐信息技术有限公司',
    '成都石斧世纪软件有限公司',
    '数字广东网络建设有限公司',
    '江苏易安联网络技术有限公司',
    '深圳市丰鑫科技有限公司',
    '深圳市乐游创梦科技有限公司',
    '深圳市携网科技有限公司',
    '深圳齐杉科技有限公司',
    '珠海网创网络科技有限公司',
];

$list = array_unique($list);

$field = [
    'name',
    'year',
    'ASSGRO',
    'LIAGRO',
    'VENDINC',
    'MAIBUSINC',
    'PROGRO',
    'NETINC',
    'RATGRO',
    'TOTEQU',
    'SOCNUM',//
    'C_ASSGROL',
    'A_ASSGROL',
    'CA_ASSGRO',
    'C_INTRATESL',
    'ATOL',
    'ASSGRO_C_INTRATESL',
    'A_VENDINCL',
    'A_PROGROL',
    'ROAL',
    'ROE_AL',
    'ROE_BL',
    'DEBTL',
    'EQUITYL',//
    'MAIBUSINC_RATIOL',
    'NALR',
    'OPM',
    'ROCA',
    'NOR',
    'PMOTA',
    'TBR',
    'EQUITYL_new',//
    'ASSGRO_yoy',
    'LIAGRO_yoy',
    'VENDINC_yoy',
    'MAIBUSINC_yoy',
    'PROGRO_yoy',
    'NETINC_yoy',
    'RATGRO_yoy',
    'TOTEQU_yoy',
    'TBR_new',
    'SOCNUM_yoy',
];

//财务
f($list, $field);

//基本信息
//b($list)

function b($list)
{

}

function f($list, $field)
{
    $url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceCalData';

    $fp = fopen('res.csv', 'w+');

    fwrite($fp, implode(',', $field) . PHP_EOL);

    foreach ($list as $ent) {
        $appId = 'PHP_is_the_best_language_in_the_world';
        $appSecret = 'PHP_GO';
        $time = time();
        $sign = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);
        $data = [
            'appId' => $appId,
            'time' => $time,
            'sign' => $sign,
            'entName' => $ent,
            'year' => '2019',
            'dataCount' => '3',
        ];
        $curl = curl_init();//初始化
        curl_setopt($curl, CURLOPT_URL, $url);//设置请求地址
        curl_setopt($curl, CURLOPT_POST, true);//设置post方式请求
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);//几秒后没链接上就自动断开
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);//提交的数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//返回值不直接显示
        $res = curl_exec($curl);//发送请求
        $res = json_decode($res, true);
        if (!empty($res) && !empty($res['result'])) {
            foreach ($res['result'] as $year => $arr) {
                $content = "{$ent},{$year},";
                foreach ($arr as $field => $val) {
                    if (is_array($val)) {
                        $content .= $val['name'] . ',';
                    } else {
                        $content .= ',';
                    }
                }
                fwrite($fp, $content . PHP_EOL);
            }
        } else {
            $target[] = $ent;
            fwrite($fp, "{$ent},2019" . PHP_EOL);
            fwrite($fp, "{$ent},2018" . PHP_EOL);
            fwrite($fp, "{$ent},2017" . PHP_EOL);
        }
    }

    fclose($fp);
}
