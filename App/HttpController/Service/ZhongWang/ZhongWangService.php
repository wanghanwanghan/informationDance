<?php

namespace App\HttpController\Service\ZhongWang;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class ZhongWangService extends ServiceBase
{
    public $taxNo;
    public $key;
    public $keyTest;
    public $url;
    public $urlTest;

    function __construct()
    {
        parent::__construct();

        $this->taxNo = '91110108MA01KPGK0L';
        $this->key = CreateConf::getInstance()->getConf('zhongwang.key');
        $this->keyTest = CreateConf::getInstance()->getConf('zhongwang.keyTest');
        $this->url = CreateConf::getInstance()->getConf('zhongwang.url');
        $this->urlTest = CreateConf::getInstance()->getConf('zhongwang.urlTest');

        return true;
    }

    private function encrypt($str)
    {
        return openssl_encrypt($str, 'aes-128-ecb', $this->keyTest, OPENSSL_RAW_DATA);
    }

    private function decrypt($str)
    {
        return openssl_decrypt($str, 'aes-128-ecb', $this->keyTest, OPENSSL_RAW_DATA);
    }

    private function checkResp($res,$type)
    {
        if (isset($res['data']['total']) &&
            isset($res['data']['totalPage']) &&
            isset($res['data']['pageSize']) &&
            isset($res['data']['currentPage']))
        {
            $res['Paging']=[
                'page'=>$res['data']['currentPage'],
                'pageSize'=>$res['data']['pageSize'],
                'total'=>$res['data']['total'],
                'totalPage'=>$res['data']['totalPage'],
            ];

        }else
        {
            $res['Paging']=null;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500,$res['Paging'],[],'co请求错误');

        $res['code'] === 0 ? $res['code'] = 200 : $res['code'] = 600;

        //拿结果
        switch ($type)
        {
            case 'getInOrOutDetail':
                $res['Result'] = $res['data']['invoices'];
                break;
            default:
                $res['Result'] = null;
        }

        return $this->createReturn($res['code'],$res['Paging'],$res['Result'],$res['msg']);
    }

    //进项销项发票详情
    public function getInOrOutDetail($code, $dataType, $startDate, $endDate, $page, $pageSize)
    {
        $param['taxNumber'] = $code;
        $param['dataType'] = $dataType;//1是进项，2是销项
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['currentPage'] = $page;
        $param['pageSize'] = $pageSize;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/getClientInvoices';

        CommonService::getInstance()->log4PHP($body);

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res,__FUNCTION__) : $res;
    }

    private function readyToSend($api_path, $body)
    {
        $param = $body['param'];
        $json_param = jsonEncode($param);
        $encryptedData = $this->encrypt($json_param);
        $base64_str = base64_encode($encryptedData);
        $body['param'] = $base64_str;

        $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)
            ->send($this->urlTest . $api_path, $body);
        $res = base64_decode($res);
        $res = $this->decrypt($res);

        return jsonDecode($res);
    }
}
