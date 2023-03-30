<?php

namespace App\HttpController\Service\FaYanYuan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\RequestUtils\StatisticsService;
use App\HttpController\Service\ServiceBase;

class FaYanYuanService extends ServiceBase
{
    private $authCode;
    private $rt;
    private $sourceName = '法海';

    function __construct()
    {
        $this->authCode = CreateConf::getInstance()->getConf('fayanyuan.authCode');
        $this->rt = time() * 1000;

        return parent::__construct();
    }

    private function checkResp($res, $docType, $type = 'list'): array
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

    private function checkResps($res): array
    {
        if (isset($res['coHttpErr'])) return $this->createReturn(500, null, null, 'co请求错误');

        $result = $msg = $code = null;

        if (isset($res['data'])) {
            $code = 200;
            $result = $res['data'];
        }

        if (isset($res['status'])) {
            $code = 201;
            $msg = $res['status'];
        }

        return $this->createReturn($code, null, $result, $msg);
    }

    //法海
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
        CommonService::getInstance()->log4PHP(
                json_encode([ "法海-getlist"=>[
                    '$body'=>$body,
                    '$json_data'=>$json_data,
                ]],JSON_UNESCAPED_UNICODE)
        );

        $json_data = jsonEncode($json_data, false);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'args' => $json_data
        ];

        $resp = (new CoHttpClient())->useCache(false)->send($url, $data);

        $this->recodeSourceCurl([
            'sourceName' => $this->sourceName,
            'apiName' => last(explode('/', trim($url, '/'))),
            'requestUrl' => trim(trim($url), '/'),
            'requestData' => $data,
            'responseData' => $resp,
        ]);

        return $this->checkRespFlag ? $this->checkResp($resp, $doc_type) : $resp;
    }

    //法海
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

        $json_data = jsonEncode($json_data, false);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'args' => $json_data
        ];

        $resp = (new CoHttpClient())->send($url, $data);

        $this->recodeSourceCurl([
            'sourceName' => $this->sourceName,
            'apiName' => last(explode('/', trim($url, '/'))),
            'requestUrl' => trim(trim($url), '/'),
            'requestData' => $data,
            'responseData' => $resp,
        ]);

        return $resp;
    }

    //法海
    function getDetail($url, $body)
    {
        $sign_num = md5($this->authCode . $this->rt);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'id' => $body['id']
        ];
//        CommonService::getInstance()->log4PHP([$url, $body,$data], 'info', 'getDetail');
        $resp = (new CoHttpClient())->useCache(false)->send($url, $data);
//        CommonService::getInstance()->log4PHP($resp, 'info', 'getDetail');
        $this->recodeSourceCurl([
            'sourceName' => $this->sourceName,
            'apiName' => last(explode('/', trim($url, '/'))),
            'requestUrl' => trim(trim($url), '/'),
            'requestData' => $data,
            'responseData' => $resp,
        ]);
        $detail = $this->checkRespFlag ? $this->checkResp($resp, $body['doc_type']??'', 'detail') : $resp;
//        CommonService::getInstance()->log4PHP($detail, 'info', 'getDetail');
        return $detail;
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
            'shesu-auth' => jsonEncode([
                'uid' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_uid'),
                'pwd' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_pwd')
            ]),
            'Accept-Encoding' => 'gzip',
        ];

        $res = (new CoHttpClient())->useCache(false)->send($url, $query, $headers);

        return $this->checkRespFlag ? $this->checkResps($res) : $res;
    }

    function entoutPeople($postData)
    {
        $list = CreateConf::getInstance()->getConf('fayanyuan.list');

        $url = $list . 'entout/portrait/people';

        $postData['inquired_auth'] = 'authed:20210419-20220419';

        $query = [
            'query' => jsonEncode($postData)
        ];

        $headers = [
            'shesu-auth' => jsonEncode([
                'uid' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_uid'),
                'pwd' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_pwd')
            ]),
            'Accept-Encoding' => 'gzip',
        ];

        $res = (new CoHttpClient())->useCache(false)->send($url, $query, $headers);

        return $this->checkRespFlag ? $this->checkResps($res) : $res;
    }

    function sxbzxrPeople($postData)
    {
        $list = CreateConf::getInstance()->getConf('fayanyuan.list');

        $url = $list . 'sxbzxr/portrait/people';

        $postData['inquired_auth'] = 'authed:20210419-20220419';

        $query = [
            'query' => jsonEncode($postData)
        ];

        $headers = [
            'shesu-auth' => jsonEncode([
                'uid' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_uid'),
                'pwd' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_pwd')
            ]),
            'Accept-Encoding' => 'gzip',
        ];

        $res = (new CoHttpClient())->useCache(false)->send($url, $query, $headers);

        return $this->checkRespFlag ? $this->checkResps($res) : $res;
    }

    function xgbzxrPeople($postData)
    {
        $list = CreateConf::getInstance()->getConf('fayanyuan.list');

        $url = $list . 'xgbzxr/portrait/people';

        $postData['inquired_auth'] = 'authed:20210419-20220419';

        $query = [
            'query' => jsonEncode($postData)
        ];

        $headers = [
            'shesu-auth' => jsonEncode([
                'uid' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_uid'),
                'pwd' => CreateConf::getInstance()->getConf('fayanyuan.shesu_auth_pwd')
            ]),
            'Accept-Encoding' => 'gzip',
        ];

        $res = (new CoHttpClient())->useCache(false)->send($url, $query, $headers);

        return $this->checkRespFlag ? $this->checkResps($res) : $res;
    }

}
