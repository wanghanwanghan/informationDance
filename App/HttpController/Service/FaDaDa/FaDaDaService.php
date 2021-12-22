<?php

namespace App\HttpController\Service\FaDaDa;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class FaDaDaService extends ServiceBase
{
    public $url;
    public $app_id;
    public $app_secret;
    public $account_type;
    public $timestamp;
    public $curl_use_cache;

    //【每日信动】法大大API测试对接说明 邮件已发送至wanghan@meirixindong.com
    //请查收！测试过程如有疑问，随时沟通~
    //工作日：9:00-12:00、13:30-18:00
    //方案：认证切存证-编号
    //合作模式：法大大提供标准API接口，贵司开发上线电子签约功能。
    //
    //实名存证方案基础对接流程：
    //一、【注册账号+实名存证】
    //1. 注册账号✅
    //2. 实名存证/哈希存证（注：设置cert_flag=1自动申请编号证书）
    //3. 印章上传/自定义印章
    //二、【生成合同+发起签署】
    //1. 合同上传/模板上传+模板填充
    //2. 手动签署/自动签署
    //3. 合同归档
    //
    //【对接说明】
    //1、测试环境仅供调试接口使用，如需压测，请务必提前联系我们 ；
    //2、如贵我双方未签署合作协议，测试环境有效期为 2 个月。测试环境签署的合同无法律效力；
    //3、测试环境通用短信验证码为999999。若测试法人授权代理人认证，请提供姓名+手机号申请添加白名单；
    //4、如需企业自动签，可对接授权签页面接口，企业线上签署授权函；
    //5、实名认证申请提交后，测试环境提供认证序列号到对接群进行审核，正式环境需要等待法大大审核组审核；
    //6、如正式上线请提前3个工作日提交资料至api@fadada.com， 以便我司走流程配置生产环境。

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

    private function checkResp(string $res): array
    {
        $res = jsonDecode($res);

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


}
