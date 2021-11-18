<?php

namespace App\HttpController\Service\YongTai;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class YongTaiService extends ServiceBase
{
    use Singleton;

    private $ak;
    private $sk;
    private $token;

    function __construct()
    {
        //用户端登录地址：https://api.quanweidu.com/

        $this->ak = '18618457910';
        $this->sk = 'Quan18618457910';
        $this->token = '34055631-c92e-4acc-b53f-f0d2080974d8';

        return parent::__construct();
    }

    private function check($res): array
    {
        if ($res['retCode'] === '000000' || $res['retCode'] === '000001') {
            $code = 200;
        } else {
            $code = $res['retCode'] - 0;
        }

        if (!empty($res['result']) && isset($res['result']['total'])) {
            $paging['total'] = $res['result']['total'] - 0;
        } else {
            $paging = null;
        }

        $result = $res['result']['items'] ?? null;

        return [
            'code' => $code,
            'paging' => $paging,
            'result' => $result,
            'msg' => $res['retMsg'] ?? null,
        ];
    }

    function getBranch(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/branchV2/1051';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
            'pageNum' => trim($page) - 0,
            'pageSize' => 10,
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }


}
