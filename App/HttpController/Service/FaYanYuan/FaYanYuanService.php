<?php

namespace App\HttpController\Service\FaYanYuan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class FaYanYuanService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $authCode;
    private $rt;

    function __construct()
    {
        $this->authCode = CreateConf::getInstance()->getConf('fayanyuan.authCode');
        $this->rt = time() * 1000;

        return parent::__construct();
    }

    private function checkResp($res, $docType, $type = 'list')
    {
        $type = ucfirst($type);

        if (isset($res['pageNo']) && isset($res['range']) && isset($res['totalCount']) && isset($res['totalPageNum'])) {
            $res['Paging'] = [
                'page' => $res['pageNo'],
                'pageSize' => $res['range'],
                'total' => $res['totalCount'],
                'totalPage' => $res['totalPageNum'],
            ];

        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['code'] === 's' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        if ($type === 'List') {
            isset($res[$docType . $type]) ? $res['Result'] = $res[$docType . $type] : $res['Result'] = [];
        } else {
            isset($res[$docType]) ? $res['Result'] = $res[$docType] : $res['Result'] = [];
        }

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    private function checkResps($res)
    {
        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    function getList($url, $body)
    {
        $sign_num = md5($this->authCode . $this->rt);
        $doc_type = $body['doc_type'];
        $keyword = $body['keyword'];
        $pageno = $body['pageno'] ?? 1;
        $range = $body['range'] ?? 10;

        $json_data = [
            'dataType' => $doc_type,
            'keyword' => $keyword,
            'pageno' => $pageno,
            'range' => $range
        ];

        $json_data = jsonEncode($json_data);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'args' => $json_data
        ];

        $resp = (new CoHttpClient())->send($url, $data);

        return $this->checkRespFlag ? $this->checkResp($resp, $doc_type) : $resp;
    }

    function getListForPerson($url, $body)
    {
        $sign_num = md5($this->authCode . $this->rt);
        $doc_type = $body['doc_type'];
        $name = $body['name'];
        $idcardNo = $body['idcardNo'];
        $pageno = $body['pageno'] ?? 1;
        $range = $body['range'] ?? 10;

        $json_data = [
            'dataType' => $doc_type,
            'name' => $name,
            'idcardNo' => $idcardNo,
            'pageno' => $pageno,
            'range' => $range
        ];

        $json_data = jsonEncode($json_data);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'args' => $json_data
        ];

        return (new CoHttpClient())->send($url, $data);
    }

    function getDetail($url, $body)
    {
        $sign_num = md5($this->authCode . $this->rt);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'id' => $body['id']
        ];

        $resp = (new CoHttpClient())->send($url, $data);

        return $this->checkRespFlag ? $this->checkResp($resp, $body['doc_type'], 'detail') : $resp;
    }

    function entoutOrg($postData)
    {
        $list = CreateConf::getInstance()->getConf('fayanyuan.list');

        $url = $list . 'entout/portrait/org';

        $postData['inquired_auth'] = 'authed:20210419-20220419';

        $query = [
            'query' => jsonEncode($postData)
        ];

        $headers = [
            'shesu_auth' => jsonEncode([
                'uid' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_uid'),
                'pwd' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_pwd')
            ]),
            'Accept-Encoding' => 'gzip',
        ];

        $options = [
            'cliTimeout' => 5
        ];

        CommonService::getInstance()->log4PHP($query);
        CommonService::getInstance()->log4PHP($headers);
        CommonService::getInstance()->log4PHP($options);

        $res = (new CoHttpClient())->useCache(false)->send($url, $query, $headers, $options);

        return $this->checkRespFlag ? $this->checkResps($res) : $res;
    }
}
