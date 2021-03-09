<?php

namespace App\HttpController\Service\HttpClient;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\RedisPool\Redis;

class CoHttpClient extends ServiceBase
{
    private $db = 0;
    private $ttlDay = 1;
    private $needJsonDecode = true;
    private $useCache = true;

    function __construct()
    {
        parent::__construct();

        $this->db = CreateConf::getInstance()->getConf('env.coHttpCacheRedisDB');
        $this->ttlDay = CreateConf::getInstance()->getConf('env.coHttpCacheDay');
    }

    function send($url = '', $postData = [], $headers = [], $options = [], $method = 'post')
    {
        //从缓存中拿
        $this->useCache ? $take = $this->takeResult($url, $postData, $options) : $take = [];

        //不是空，说明缓存里有数据，直接返回
        if (!empty($take)) return $this->needJsonDecode ? jsonDecode($take) : $take;

        $method = strtoupper($method);

        //新建请求
        $request = new HttpClient($url);
        $request->setTimeout(60);
        //这三个read connect write设置的话会直接覆盖timeout
        $request->setConnectTimeout(60);

        //设置head头
        empty($headers) ?: $request->setHeaders($headers, true, false);

        try {
            //发送请求
            if ($method === 'POST') $data = $request->post($postData);
            if ($method === 'POSTJSON') $data = $request->postJson(jsonEncode($postData));
            if ($method === 'GET') $data = $request->get();

            //整理结果
            $data = $data->getBody();

        } catch (\Exception $e) {
            $this->writeErr($e, 'CoHttpClient');
            return ['coHttpErr' => 'error'];
        }

        //缓存起来
        !$this->useCache ?: $this->storeResult($url, $postData, $data, $options);

        return $this->needJsonDecode ? jsonDecode($data) : $data;
    }

    function useCache($type = true)
    {
        $this->useCache = $type;
        return $this;
    }

    function needJsonDecode($type)
    {
        $this->needJsonDecode = $type;
        return $this;
    }

    private function storeResult($url, $postData, $result, $options)
    {
        $key = $this->createKey($url, $postData, $options);

        $res = Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key, $result, $url, $postData) {
            $redis->select($this->db);
            $redis->setEx($url, 3600, jsonEncode($postData));
            return $redis->setEx($key, $this->ttlDay * 86400, $result);
        });

        return $res;
    }

    private function takeResult($url, $postData, $options)
    {
        $key = $this->createKey($url, $postData, $options);

        $res = Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key) {
            $redis->select($this->db);
            return $redis->get($key);
        });

        return $res;
    }

    private function createKey($url, $postData, $options): string
    {
        if (isset($options['useThisKey'])) return $options['useThisKey'];

        $unsetTarget = [
            'rt', 'sign'
        ];

        $data = [];

        if (is_array($postData)) {
            foreach ($postData as $key => $val) {
                if (in_array($key, $unsetTarget)) continue;
                $data[$key] = $val;
            }
            krsort($data);
        } else {
            $data = $postData;
        }

        return md5($url . jsonEncode($data));
    }

}
