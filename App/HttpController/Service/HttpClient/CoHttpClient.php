<?php

namespace App\HttpController\Service\HttpClient;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\RedisPool\Redis;

class CoHttpClient extends ServiceBase
{
    private $db;
    private $ttlDay;
    private $needJsonDecode = true;
    private $useCache = true;

    function __construct()
    {
        parent::__construct();

        $this->db = CreateConf::getInstance()->getConf('env.coHttpCacheRedisDB');
        $this->ttlDay = CreateConf::getInstance()->getConf('env.coHttpCacheDay') * 86400;
    }

    function send($url = '', $postData = [], $headers = [], $options = [], $method = 'post')
    {
        //从缓存中拿
        $this->useCache ? $take = $this->takeResult($url, $postData, $options) : $take = [];

        //不是空，说明缓存里有数据，直接返回
        if (!empty($take)) return $this->needJsonDecode ? jsonDecode($take) : $take;

        $method = strtoupper($method);

        if ($method === 'GET' && strpos($url, '?') === false) {
            $url .= '?' . http_build_query($postData);
        }

        //新建请求
        $request = new HttpClient($url);

        if (isset($options['cliTimeout']) && is_numeric($options['cliTimeout'])) {
            $time = $options['cliTimeout'] - 0;
        } else {
            $time = 60;
        }

        if (isset($options['enableSSL'])) {
            $request->setEnableSSL(!!$options['enableSSL']);
        }

        $request->setTimeout($time);
        $request->setConnectTimeout($time);

        //设置head头
        empty($headers) ?: $request->setHeaders($headers, true, false);

        try {
            //发送请求
            if ($method === 'POST') $data = $request->post($postData);
            if ($method === 'POSTJSON') $data = $request->postJson(
                is_string($postData) ? $postData : jsonEncode($postData)
            );
            if ($method === 'GET') $data = $request->get();
            //整理结果
            $data = $data->getBody();
//            dingAlarm('http返回',['$data'=>json_encode($data)]);
            if($url === 'https://testapi.gnete.com:9083/routejson'){
                CommonService::getInstance()->log4PHP([$postData,$data],'info','http_return_data');
            }
        } catch (\Exception $e) {
            $this->writeErr($e, 'CoHttpClient');
            return ['coHttpErr' => 'error'];
        }

        //缓存起来
        !$this->useCache ?: $this->storeResult($url, $postData, $data, $options);

        return $this->needJsonDecode ? jsonDecode($data) : $data;
    }

    function setEx($day): CoHttpClient
    {
        $this->ttlDay = (int)($day * 86400);
        return $this;
    }

    function useCache($type = true): CoHttpClient
    {
        $this->useCache = $type;
        return $this;
    }

    function needJsonDecode($type): CoHttpClient
    {
        $this->needJsonDecode = $type;
        return $this;
    }

    private function storeResult($url, $postData, $result, $options)
    {
        $key = $this->createKey($url, $postData, $options);

        return Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key, $result, $url, $postData) {
            $redis->select($this->db);
            $redis->setEx($url, 3600, jsonEncode($postData));
            return $redis->setEx($key, $this->ttlDay, $result);
        });
    }

    private function takeResult($url, $postData, $options)
    {
        $key = $this->createKey($url, $postData, $options);

        return Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key) {
            $redis->select($this->db);
            return $redis->get($key);
        });
    }

    private function createKey($url, $postData, $options): string
    {
        if (isset($options['useThisKey'])) return $options['useThisKey'];

        $unsetTarget = [
            'rt', 'sign', 'timestamp'
        ];

        $data = [];

        if (is_array($postData)) {
            foreach ($postData as $key => $val) {
                if (in_array($key, $unsetTarget, true)) continue;
                $data[$key] = $val;
            }
            krsort($data);
        } else {
            $data = $postData;
        }

        return md5($url . jsonEncode($data));
    }

}
