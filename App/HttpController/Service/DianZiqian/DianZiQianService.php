<?php

namespace App\HttpController\Service\DianZiqian;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;

class DianZiQianService extends ServiceBase
{
    public $url;
    public $app_id;
    public $app_secret;
    public $account_type;
    public $timestamp;
    public $curl_use_cache;

    function __construct($type = '')
    {
        parent::__construct();

        $this->url = 'https://sandbox.letsign.com';
        $this->app_code = 'E7094079418854802183';
        $this->app_secret = '6%T8s0h!cSx4^M$7vb0Xjr5e75r6n18NxGuK1V7$942e7*2&2G64d7#3#^8x4G44';

//        if (strtolower($type) === 'test') {
//            $this->url = 'https://testapi.fadada.com:8443/api/';
//            $this->app_id = CreateConf::getInstance()->getConf('fadada.app_id_test');
//            $this->app_secret = CreateConf::getInstance()->getConf('fadada.app_secret_test');
//        }

        $this->timestamp = Carbon::now()->format('YmdHis');
        $this->curl_use_cache = false;

        return true;
    }

    public function getAuthFile($postData){
        CommonService::getInstance()->log4PHP([$postData],'info','getAuthFile');
        $signerPersonres = $this->signerPerson($postData);
        $contractFile = $this->contractFile();
        dingAlarm('电子牵-getAuthFile',['$signerPersonres'=>json_encode($signerPersonres),'$contractFile'=>json_encode($contractFile)]);
        return $this->createReturn(200, null, ['$signerPersonres'=>$signerPersonres,'$contractFile'=>$contractFile], 'test');
    }

    /**
     * 创建签署人
     */
    public function signerPerson($postData){
        $path = '/open-api-lite/signer/person';
        $param = array(
            "personIdType" => "0",
            "personName" => $postData['legalPerson'],
            "personIdCard" => $postData['idCard']
        );
        $param = $this->buildParam($param,$path);
        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path,$param, $this->getHeader('form'), ['enableSSL' => true]);
        CommonService::getInstance()->log4PHP([$this->url . $path,$param],'info','signerPerson');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 创建合同
     */
    public function contractFile(){
        $path = '/open-api-lite/contract/file';
        $file = STATIC_PATH."AuthBookModel/dx_template.pdf";
        $param = $this->buildParam([],$path,$file);
        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path,$param, $this->getHeader('form'), ['enableSSL' => true]);
        CommonService::getInstance()->log4PHP([$this->url . $path,$param],'info','contractFile');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 计算签名
     * @param $data
     * @param $path
     * @return String
     */
    public function makeSign($data, $path)
    {
        // 业务方密钥
        $appSecret = '6%T8s0h!cSx4^M$7vb0Xjr5e75r6n18NxGuK1V7$942e7*2&2G64d7#3#^8x4G44';
        //签名步骤一：按字典序排序参数
        ksort($data);
        $String = $this->toUrlParams($data, $path);
        echo "md5:" . $String;
        echo "\n";
        //签名步骤二：拼接secret
        $String = $String . $appSecret;
        //签名步骤三：sha1加密
        $result = sha1($String);
        echo "sha1:" . $result;
        echo "\n";
        return $result;
    }

    /**
     * @param $data
     * @param $path
     * @return String
     */
    public function toUrlParams($data, $path)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v;
            }
        }
        $buff .= $path;
        echo "buf:" . $buff;
        echo "\n";
        return md5($buff);
    }
    /**
     * @param $path
     * @return array
     */
    public function buildParam($param,$path,$file = '')
    {
        // 业务方AppCode
        $param["appCode"] = "E7094079418854802183";
        $param["version"] = "v1";
        $param["timestamp"] = time();
        $token = $this->makeSign($param, $path);
        if(!empty($file)){
            $f = curl_file_create($file);
            $param["contractFile"] = $f;
        }
        $param["token"] = $token;
        return $param;
    }
    /**
     * 发送请求
     * @param $param
     * @param $header
     * @param $url
     */
    function doCurl($param, $url)
    {
        $header = [
            "Content-Type:multipart/form-data",
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);

        dd($output);
    }
    private function getHeader(string $type = ''): array
    {
        switch (strtolower($type)) {
            case 'json':
                return ['Content-Type' => 'application/json;charset=UTF-8'];
            case 'form':
                return ['Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'];
            case 'file':
                return ['Content-Type' => 'multipart/form-data;charset=utf8'];
            default:
                return [];
        }
    }

    private function checkResp(array $res): array
    {
        if (isset($res['description']) && $res['description'] === 'success') {
            $code = 200;
        } else {
            $code = $res['code'] - 0;
        }

        $paging = null;

        $result = $res['data'] ?? null;

        $msg = $res['description'] ?? null;

        return $this->createReturn($code, $paging, $result, $msg);
    }
}