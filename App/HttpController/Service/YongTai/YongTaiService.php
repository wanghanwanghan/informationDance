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
        // 返回样式1
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

        if (!empty($res['result']['items'])) {
            $result = $res['result']['items'];
        } elseif (!empty($res['result'])) {
            $result = $res['result'];
        } else {
            $result = null;
        }

        // 返回样式2
        if (isset($res['error_code']) && isset($res['message'])) {
            if ($res['error_code'] === 0) {
                $code = 200;
            } else {
                $code = $res['error_code'] - 0;
            }

            if (!empty($res['data']) && isset($res['data']['total'])) {
                $paging['total'] = $res['data']['total'] - 0;
            } else {
                $paging = null;
            }

            if (!empty($res['data']['items'])) {
                $result = $res['data']['items'];
            } elseif (!empty($res['data'])) {
                $result = $res['data'];
            } else {
                $result = null;
            }
        }

        return [
            'code' => $code,
            'paging' => $paging,
            'result' => $result,
            'msg' => $res['retMsg'] ?? $res['message'] ?? null,
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

    function getHolder(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/holderV5/1003';

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

    function getHolderChange(string $entName, string $id, string $page): array
    {
        $url = 'https://ibd.cn/api/open/ic/holderChange/1015';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'id' => trim($id),//公司id
            'pageNum' => trim($page) - 0,
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getChangeinfo(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/changeinfoV3/1004';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
            'pageNum' => trim($page) - 0,
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getBaseinfo(string $entName, string $code): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/baseinfoV2/1001';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getEnterpriseTicketQuery(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/yy/ic/enterpriseTicketQuery/3034';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'keyword' => trim($entName),
            'pageNum' => trim($page) - 0,
            'size' => 10,
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getStaff(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/staffV3/1002';

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

    function getSearch(string $entName, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/search/1036';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'word' => trim($entName),
            'pageNum' => trim($page) - 0,
        ];

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getHistorynames(string $entName): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/historynames/1053';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
        ];

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getTaxescode(string $entName): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/taxescode/1032';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
        ];

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getParentcompany(string $entName, string $code): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/parentcompany/1054';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getEciother(string $entName): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/eciother/1052';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getAnnualreport(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/annualreportv2/1008';

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

    function getBaseinfop(string $entName, string $code): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/baseinfoV3/1005';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getBaseinfos(string $entName, string $code): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/baseinfoV5/1006';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getSpecial(string $entName): array
    {
        $url = 'https://quanweidu.cn/api/open/tpa/ic/special/4006';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'keyword' => trim($entName),
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getInverst(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/inverstV2/1007';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
            'page_num' => trim($page) - 0,
            'page_size' => 20,
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getComverify(string $entName, string $code, string $fr): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/comverify/1009';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
            'legal_person_name' => trim($fr),
        ];

        $data = array_filter($data);

        $url .= '?' . http_build_query($data);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'get');

        return $this->check($res);
    }

    function getContact(string $entName, string $code, string $page): array
    {
        $url = 'https://quanweidu.cn/api/open/ic/contact/1010';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'token' => $this->token,
        ];

        $data = [
            'name' => trim($entName),
            'code' => trim($code),
            'page_num' => trim($page) - 0,
            'page_size' => 20,
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
