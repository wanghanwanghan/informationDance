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
            case 'getInOrOutDetailByClient':
                $res['Result'] = $res['data']['invoices'];
                break;
            case 'getInOrOutDetailByCert':
                $res['Result'] = 123;
                break;
            default:
                $res['Result'] = null;
        }

        return $this->createReturn($res['code'],$res['Paging'],$res['Result'],$res['msg']);
    }

    //进项销项发票详情 客户端（税盘）专用
    public function getInOrOutDetailByClient($code, $dataType, $startDate, $endDate, $page, $pageSize)
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

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res,__FUNCTION__) : $res;
    }

    //进项销项发票详情 证书专用
    public function getInOrOutDetailByCert($code, $dataType, $startDate, $endDate, $page, $pageSize)
    {
        $param['taxNumber'] = $code;
        $param['invoiceType'] = '';//查询全部种类
        $param['dataType'] = $dataType;//1是进项，2是销项
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['currentPage'] = $page;
        $param['pageSize'] = $pageSize;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/invoiceCollection';

        $res = $this->readyToSend($api_path, $body);

        CommonService::getInstance()->log4PHP($res);

        return $this->checkRespFlag ? $this->checkResp($res,__FUNCTION__) : $res;
    }

    //统一发送
    private function readyToSend($api_path, $body)
    {
        $param = $body['param'];
        $json_param = jsonEncode($param);
        $encryptedData = $this->encrypt($json_param);
        $base64_str = base64_encode($encryptedData);
        $body['param'] = $base64_str;

        $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($this->urlTest . $api_path, $body);
        $res = base64_decode($res);
        $res = $this->decrypt($res);

        return jsonDecode($res);
    }
}
