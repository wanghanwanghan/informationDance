<?php

namespace App\HttpController\Service\FaDaDa;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;

class FaDaDaService extends ServiceBase
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

        $this->url = 'https://testapi.fadada.com:8443/api/';
        $this->app_id = '405806';
        $this->app_secret = 'UFPU9C3kfnKb6kdDyugOhqir';
        $this->account_type = '2';

        if (strtolower($type) === 'test') {
            $this->url = 'https://testapi.fadada.com:8443/api/';
            $this->app_id = '405806';
            $this->app_secret = 'UFPU9C3kfnKb6kdDyugOhqir';
        }

        $this->timestamp = Carbon::now()->format('YmdHis');
        $this->curl_use_cache = false;

        return true;
    }

    private function checkResp(array $res): array
    {
        if (isset($res['code']) && $res['code'] - 0 === 1) {
            $code = 200;
        } else {
            $code = $res['code'] - 0;
        }

        $paging = null;

        $result = $res['data'] ?? null;

        $msg = $res['msg'] ?? null;

        return $this->createReturn($code, $paging, $result, $msg);
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

    //企业在信动的主键
    private function getOpenId(array $arr): string
    {
        return md5(trim($arr['entName']) . 'meirixindong123');
    }

    function setCurlUseCache(bool $type): FaDaDaService
    {
        $this->curl_use_cache = $type;
        return $this;
    }

    //
    function getRegister(array $arr)
    {
        $url_ext = 'account_register.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $this->account_type . $this->getOpenId($arr)));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'open_id' => $this->getOpenId($arr),
            'account_type' => $this->account_type,
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //
    function getHashDeposit(array $arr)
    {
        $url_ext = 'hash_deposit.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1(
            $this->app_secret .
            $arr['cert_flag'] .
            $arr['customer_id'] .
            $arr['file_name'] .
            $arr['file_size'] .
            $arr['noper_time'] .
            $arr['original_sha256'] .
            $arr['preservation_desc'] .
            $arr['preservation_name'] .
            $arr['transaction_id']
        ));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'customer_id' => $arr['customer_id'],//客户编号 注册账号时返回
            'preservation_name' => $arr['preservation_name'],//存证名称
            'preservation_desc' => $arr['preservation_desc'],//存证描述
            'file_name' => $arr['file_name'],//文件名 字符 len <= 50
            'noper_time' => $arr['noper_time'],//文件最后修改时间
            'file_size' => $arr['file_size'],//文件大小
            'original_sha256' => $arr['original_sha256'],//文件hash值 sha256算法
            'transaction_id' => $arr['transaction_id'],//交易号 字符 len <= 32
            'cert_flag' => $arr['cert_flag'],//是否认证成功后自动申请编号证书 默认参数值为0 不申请 参数值为1 自动申请
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //
    function getCustomSignature(array $arr)
    {
        $url_ext = 'custom_signature.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['content'] . $arr['customer_id']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'customer_id' => $arr['customer_id'],
            'content' => $arr['content'],
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //
    function uploadTemplate(array $arr)
    {
        $url_ext = 'uploadtemplate.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['template_id']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'template_id' => $arr['template_id'],
            'doc_url' => $arr['doc_url'],
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //
    function uploadSignature(array $arr)
    {
        $url_ext = 'add_signature.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['customer_id'] . $arr['signature_img_base64']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'customer_id' => $arr['customer_id'],
            'signature_img_base64' => $arr['signature_img_base64'],
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //
    function fillTemplate(array $arr)
    {
        $url_ext = 'generate_contract.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        ksort($arr['parameter_map']);

        $arr['parameter_map'] = jsonEncode($arr['parameter_map'], false);

        $section_2 = strtoupper(sha1(
                $this->app_secret . $arr['template_id'] . $arr['contract_id']) . $arr['parameter_map']
        );

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'doc_title' => $arr['doc_title'],
            'template_id' => $arr['template_id'],
            'contract_id' => $arr['contract_id'],//合同编号
            'font_size' => $arr['font_size'],//字体大小
            'font_type' => $arr['font_type'],//字体类型 0-宋体；1-仿宋；2-黑体；3-楷体；4-微软雅黑
            'fill_type' => $arr['fill_type'],//填充类型 0 pdf 模板、1 在线填充模板
            'parameter_map' => $arr['parameter_map'],//填充内容 json key val
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //
    function getExtsignAuto(array $arr)
    {
        $url_ext = 'extsign_auto.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['customer_id']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'transaction_id' => $arr['transaction_id'],
            'contract_id' => $arr['contract_id'],
            'customer_id' => $arr['customer_id'],
            'doc_title' => $arr['doc_title'],
            'position_type' => $arr['position_type'],//定位类型 0-关键字（默认） 1-坐标
            'sign_keyword' => $arr['sign_keyword'],//定位关键字 关键字为文档中的文字内容（能被ctrl+f查找功能检索到）
            'keyword_strategy' => $arr['keyword_strategy'],//0 所有关键字签章 1 第一个关键字签章 2 最后一个关键字签章
            'signature_id' => $arr['signature_id'],
            'signature_show_time' => $arr['signature_show_time'],//时间戳显示方式 1 显示 2 不显示
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

}
