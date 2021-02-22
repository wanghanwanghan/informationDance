<?php

include './vendor/autoload.php';

$url = 'https://api.meirixindong.com/provide/v1/xd/getProductStandard';

$appId = 'PHP_is_the_best_language_in_the_world';
$appSecret = 'PHP_GO';
$time = time();
$sign = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);

$data = [
    'appId' => $appId,
    'time' => $time,
    'sign' => $sign,
    // 'image' => new \CURLFile(realpath('./WechatIMG261-tuya.png'))
    'entName' => '福建省华渔教育科技有限公司',
    'page' => 1,
    'pageSize' => 10,
];

//$curl = curl_init();//初始化
//curl_setopt($curl, CURLOPT_URL, $url);//设置请求地址
//curl_setopt($curl, CURLOPT_POST, true);//设置post方式请求
//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);//几秒后没链接上就自动断开
//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//curl_setopt($curl, CURLOPT_POSTFIELDS, $data);//提交的数据
//curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//返回值不直接显示
//$res = curl_exec($curl);//发送请求
//
//
//dd(json_decode($res, 1));

$str='gmt_create=2021-02-22+15%3A37%3A05&charset=GBK&seller_email=liqi%40meirixindong.com&subject=%B2%E2%CA%D4&sign=jAxV7qlH7J6TG5SLKcaZTQrScEe9g0F44V9O0S2ezDXT1WWJQRLdN56H2GTpA0Tveuxvol4VFQljr8zUgsRteZKcW7rVs5AEJS7Z%2F1nrncF7rHGZEPDdtTNrS96xQu3KJhGEkbPbsFzhNkUSqWntIrztwQ38AEAI5Qe%2Ff%2FYw7Lyi%2FL9GyEUPpuLrPRPXTOmq4CvR1TWVJOkWG2fWr%2Fv4nZAc30XO%2BmXYx7hI3nDxko134V%2B%2B619uIfij%2BO4n%2B3uIQ7TtyeI9zqyL17jfhBLgAnkFjKhzicmPfzRFqZ2i47jCgJ6mS%2FPZ8%2Bpr5OPcp5Na7WqbSxM96BpSXhnV%2FeElAg%3D%3D&buyer_id=2088702671559842&invoice_amount=0.01&notify_id=2021022200222153708059841429400763&fund_bill_list=%5B%7B%22amount%22%3A%220.01%22%2C%22fundChannel%22%3A%22PCREDIT%22%7D%5D&notify_type=trade_status_sync&trade_status=TRADE_SUCCESS&receipt_amount=0.01&app_id=2021001188613166&buyer_pay_amount=0.01&sign_type=RSA2&seller_id=2088931260191242&gmt_payment=2021-02-22+15%3A37%3A07&notify_time=2021-02-22+15%3A37%3A08&version=1.0&out_trade_no=1613979421&total_amount=0.01&trade_no=2021022222001459841410823307&auth_app_id=2021001188613166&buyer_logon_id=186****7910&point_amount=0.00';

parse_str($str,$data);

dd($data);

