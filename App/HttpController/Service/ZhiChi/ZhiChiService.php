<?php

namespace App\HttpController\Service\ZhiChi;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class ZhiChiService extends ServiceBase
{
    public $appId;
    public $appKey;

    function __construct()
    {
        parent::__construct();
        $this->appKey = '231x7vI7Gq3o';
        $this->appId = '5a60f9ddc9be42d28b7b08e70b34d6b7';
        return true;
    }

    private function getToken(){
        $create_time = time();
        $sign = md5($this->appId.$create_time.$this->appKey);
        $url = "https://www.soboten.com/api/get_token?appid={$this->appId}&sign={$sign}&create_time=".$create_time;
        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($url, [], [],[],'GET');
        CommonService::getInstance()->log4PHP([$url, $res], 'info', 'zhichi_getToken');
        return $res;
    }

    public function directUrl(){
        $tokenRes = $this->getToken();
        if(!isset($tokenRes['item']['token']) || empty($tokenRes['item']['token'])){
            return '';
        }
        $token = $tokenRes['item']['token'];
        $header = [
            'token'=>$token,
//            'content-type'=>"application/json",
        ];

        $url = 'https://www.soboten.com/api/oss/5/direct_url';
        $data = [
            'agent_email' => 'mrxd@sobot.com',
            'type' => '3',
        ];
        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $header);
        CommonService::getInstance()->log4PHP([$url, $data, $header, $res], 'info', 'zhichi_directUrl');
        return $res;

    }
}