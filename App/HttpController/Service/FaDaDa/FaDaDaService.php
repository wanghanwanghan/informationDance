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

    //【每日信动】法大大API测试对接说明 邮件已发送至wanghan@meirixindong.com
    //请查收！测试过程如有疑问，随时沟通~
    //工作日：9:00-12:00、13:30-18:00
    //方案：认证切存证-编号
    //合作模式：法大大提供标准API接口，贵司开发上线电子签约功能。
    //
    //实名存证方案基础对接流程：
    //一、【注册账号+实名存证】
    //1. 注册账号
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

        return true;
    }

    private function checkResp(bool $flag)
    {

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

    private function sign(array $arr): string
    {
        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $this->account_type . $this->getOpenId($arr)));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        return base64_encode($section_3);
    }

    function getRegister(array $arr)
    {
        $url_ext = 'account_register.api';

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $this->sign($arr),
            'open_id' => $this->getOpenId($arr),
            'account_type' => $this->account_type,
        ];

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        CommonService::getInstance()->log4PHP($resp);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }


}
