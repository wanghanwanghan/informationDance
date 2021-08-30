<?php

include '../../vendor/autoload.php';

$list = [
    '上海信息安全工程技术研究中心',
    '上海动联信息技术股份限公司',
    '上海天宏信息工程股份限公司',
    '上海百络信息技术有限公司',
    '中创安控（北京）科技有限公司',
    '中孚信息产业股份有限公司',
    '中福彩技术研究中心',
    '云南瑞迅达通信技术有限公司',
    '云南省信息安全测评中心',
    '云卫科技（北京）有限公司',
    '公安部第一研究所',
    '内蒙古信息系统安全等级测评中心',
    '内蒙古网智科技服务有限责任公司',
    '前沿信安（北京）科技限公司',
    '化为技术有限公司',
    '北京京航计算通讯研究所',
    '北京准星科技有限公司',
    '北京天下信安技术有限公司',
    '北京天信恒安科技有限公司',
    '北京安证通信息科技股份有限公司',
    '北京宜信嘉合网络科技有限责任公司',
    '北京控制与电子技术研究所',
    '北京机电工程所',
    '北京极安融信息技术有限公司',
    '北京汉光朗威技术有限公司',
    '北京电子科技学院',
    '北京神州绿盟科技有限有限公司',
    '北京空间机电研究所',
    '北京立思辰计算技术有限公司',
    '北京网御星云信息安全技术有限公司',
    '北京航天情报与信息研究所',
    '北京计算机技术及应用研究所',
    '北京路兴达源建筑工程材料有限公司',
    '北京金山安全软件有限公司',
    '北京霍因科技有限公司',
    '北信源内网安全管理及补丁分发准入控制系统',
    '华北计算技术研究所计算机测评中心',
    '华测电子认证有限责任',
    '南京清华永新网络技术有限公司',
    '南京索特软件有限公司',
    '卡巴斯基技术开发北京有限公司',
    '合肥安珀信息科技有限公司',
    '吉林伍陆柒捌股份有限公司',
    '吉林信息安全测评中心',
    '四川省信息安全测评中心',
    '四川精荣数安科技有限公司',
    '国家保密科学技术研究所',
    '国家信息中心',
    '国家新闻出版广电总局广播科学研究院',
    '国家计算机网络与信息安全管理中心',
    '外交部通信总台',
    '大庆中基石油通信建设有限公司',
    '天津思睿软件有限公司',
    '山东贝格通软件科技有公司',
    '工业和信息化部电信传输研究所',
    '广东南方信息安全研究院',
    '广东省信息安全测评中心',
    '广东诚晟交通科技投资有限公司',
    '广州安锐信息技术有限公司',
    '广州市网恒信息技术有限公司',
    '慧盾安全信息技术（苏州）股份有限公司',
    '成都曙光光纤网络有限公司',
    '成都石斧世纪软件有限公司',
    '成都锐信安信息安全技术有限公司',
    '数字广东网络建设有限公司',
    '无锡市软件测评中心',
    '无锡江南信息安全工程技术中心',
    '杭州天谷信息技术有限公司',
    '杭州智贝信息科技有公司',
    '核工业计算机应用研究所',
    '江苏易安联网络技术有限公司',
    '江苏省未来网络创新研究院',
    '浙江大华技术有限公司',
    '深圳市丰鑫科技有限公司',
    '深圳市乐游创梦科技有限公司',
    '深圳市携网科技有限公司',
    '深圳齐杉科技有限公司',
    '湖北盛鸿远网络科技有限责任公司',
    '湖南奇正思远网络科技公司',
    '湖南省信息安全测评中心',
    '爱渠西来艾颂信息技术上海有限公司',
    '珠海网创网络科技有限公司',
    '珠海网盈网络有限公司',
    '甘肃万立信息科技发展有限公司',
    '网域神州科技（北京）有限公司',
    '网络卫士防火墙系统',
    '西安市信息安全测评中心',
    '赛门铁克软件北京有限公司',
    '长沙市规划信息服务中心',
    '韦伯森斯网络安全技术（研发）北京有限公司',
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

$url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceCalData';

$fp = fopen('res.csv', 'w+');

fwrite($fp, implode(',', $field) . PHP_EOL);

$target = [];

foreach ($list as $ent) {
    dump($ent);
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
