<?php

namespace App\HttpController\Service\BaiDu;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\utils\arr;

class BaiDuService extends ServiceBase
{
    use Singleton;

    private $clientId;
    private $clientSecret;
    private $checkWordUrl;
    private $clientIdForOrc;
    private $clientSecretForOcr;
    private $ocrUrl;
    private $getTokenUrl;
    private $ak;
    private $sk;
    private $ak_tmp;
    private $sk_tmp;

    private $ak_image;
    private $sk_image;

    function __construct()
    {
        $this->clientId = CreateConf::getInstance()->getConf('baidu.clientId');
        $this->clientSecret = CreateConf::getInstance()->getConf('baidu.clientSecret');
        $this->checkWordUrl = CreateConf::getInstance()->getConf('baidu.checkWordUrl');
        $this->clientIdForOrc = CreateConf::getInstance()->getConf('baidu.clientIdForOrc');
        $this->clientSecretForOcr = CreateConf::getInstance()->getConf('baidu.clientSecretForOcr');
        $this->ocrUrl = CreateConf::getInstance()->getConf('baidu.ocrUrl');
        $this->getTokenUrl = CreateConf::getInstance()->getConf('baidu.getTokenUrl');
        $this->ak = CreateConf::getInstance()->getConf('baidu.ak');
        $this->sk = CreateConf::getInstance()->getConf('baidu.sk');
        $this->ak_tmp = CreateConf::getInstance()->getConf('baidu.ak_tmp');
        $this->sk_tmp = CreateConf::getInstance()->getConf('baidu.sk_tmp');
        $this->ak_image = 'lwQ5Fmy3UimGjKBs5ghQEkXF';//图片增强
        $this->sk_image = 'zA5hr3DGpi3gwnLKRWiX3PvdukjVi5Q3';//图片增强
        return parent::__construct();
    }

    //获取token
    private function getToken($client_id, $client_secret)
    {
        $res = (new CoHttpClient())->needJsonDecode(true)->send($this->getTokenUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ], [], 'get');

        return $res['access_token'];
    }

    //百度内容审核 - 纯文本
    function checkWord($content)
    {
        //https://login.bce.baidu.com/?account=&redirect=http%3A%2F%2Fconsole.bce.baidu.com%2F%3Ffromai%3D1#/aip/overview

        $label = [
            0 => '绝对没有',
            1 => '暴恐违禁',
            2 => '文本色情',
            3 => '政治敏感',
            4 => '恶意推广',
            5 => '低俗辱骂',
            6 => '低质灌水'
        ];

        $token = $this->getToken($this->clientId, $this->clientSecret);

        $url = $this->checkWordUrl . "?access_token={$token}";

        $postData = ['content' => $content];

        $res = (new CoHttpClient())->needJsonDecode(true)->send($url, $postData);

        $res = obj2Arr($res);

        //reject里是敏感词信息
        if (!empty($res) && isset($res['result']) && isset($res['result']['reject']) && !empty($res['result']['reject'])) {
            //如果有敏感词汇就替换
            foreach ($res['result']['reject'] as $reject) {
                foreach ($reject['hit'] as $one) {
                    $content = str_replace([$one], '***', $content);
                }
            }
        }

        //review里是 涉嫌 敏感词信息
        if (!empty($res) && isset($res['result']) && isset($res['result']['review']) && !empty($res['result']['review'])) {
            //如果有敏感词汇就替换
            foreach ($res['result']['review'] as $reject) {
                foreach ($reject['hit'] as $one) {
                    $content = str_replace([$one], '???', $content);
                }
            }
        }

        return $content;
    }

    //百度ocr提取图片中文字
    function ocr($file)
    {
        $token = $this->getToken($this->clientIdForOrc, $this->clientSecretForOcr);

        $url = $this->ocrUrl . "?access_token={$token}";

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

        if ($file instanceof UploadFile) $file = $file->getStream()->__toString();

        $postData = ['image' => base64_encode($file)];

        $res = (new CoHttpClient())->useCache(false)->send($url, $postData, $headers);

        return $res;
    }

    //地址转经纬度
    function addressToLatLng($address): ?array
    {
        if (strlen($address) > 84) return null;

        $url = 'https://api.map.baidu.com/geocoding/v3/?address=%s&output=%s&ak=%s&sn=%s';

        $data = [
            'address' => $address,
            'output' => 'json',
            'ak' => $this->ak,
        ];

        $querystring = http_build_query($data);

        $sn = md5(urlencode('/geocoding/v3/?' . $querystring . $this->sk));

        $url = sprintf($url, urlencode($address), 'json', $this->ak, $sn);

        $res = (new CoHttpClient())->useCache(false)->send($url, [], [], [], 'get');

        return is_string($res) ? jsonDecode($res) : $res;
    }

    //识别自然语言地址并进行少量补充和纠错
    function addressToStructured($address): ?array
    {
        $url = 'https://api.map.baidu.com/address_analyzer/v1/?address=%s&ak=%s&sn=%s';

        if (time() % 2) {
            $ak = $this->ak;
            $sk = $this->sk;
        } else {
            $ak = $this->ak_tmp;
            $sk = $this->sk_tmp;
        }

        $data = [
            'address' => $address,
            'ak' => $ak,
        ];

        $querystring = http_build_query($data);

        $sn = md5(urlencode('/address_analyzer/v1/?' . $querystring . $sk));

        $url = sprintf($url, urlencode($address), $ak, $sn);

        $res = (new CoHttpClient())->useCache(false)->send($url, [], [], [], 'get');

        return is_string($res) ? jsonDecode($res) : $res;
    }

    //图片无损放大
    function imageQualityEnhance(UploadFile $file): ?array
    {
        $url = 'https://aip.baidubce.com/rest/2.0/image-process/v1/image_quality_enhance';

        $token = $this->getToken($this->ak_image, $this->sk_image);

        $url = $url . "?access_token={$token}";

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

        $file = $file->getStream()->__toString();

        $postData = ['image' => base64_encode($file)];

        $res = (new CoHttpClient())->useCache(false)->send($url, $postData, $headers);

        return is_string($res) ? jsonDecode($res) : $res;
    }

    //圆形区域地址检索
    function circularSearch(string $query, float $lat, float $lng, int $radius): ?array
    {
        $url = 'https://api.map.baidu.com/place/v2/search?query=%s&location=%s&radius=%s&ak=%s&sn=%s';

        if (time() % 2) {
            $ak = $this->ak;
            $sk = $this->sk;
        } else {
            $ak = $this->ak_tmp;
            $sk = $this->sk_tmp;
        }

        $data = [
            'query' => $query,
            'location' => implode(',', [$lat, $lng]),
            'radius' => $radius,
            'ak' => $ak,
        ];

        $querystring = http_build_query($data);

        $sn = md5(urlencode('/place/v2/?' . $querystring . $sk));

        $url = sprintf($url, urlencode($query), implode(',', [$lat, $lng]), $radius, $ak, $sn);

        CommonService::getInstance()->log4PHP($url);

        $res = (new CoHttpClient())->useCache(false)->send($url, [], [], [], 'get');

        CommonService::getInstance()->log4PHP($res);

        return is_string($res) ? jsonDecode($res) : $res;
    }


}
