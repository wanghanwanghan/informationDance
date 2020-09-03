<?php

namespace App\HttpController\Service\HttpClient;

use App\HttpController\Service\ServiceBase;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\RedisPool\Redis;

class CoHttpClient extends ServiceBase
{
    function onNewService(): ?bool
    {
        parent::onNewService();

        $this->db = \Yaconf::get('env.coHttpCacheRedisDB');
        $this->ttlDay = \Yaconf::get('env.coHttpCacheDay');

        return true;
    }

    public $db=0;
    public $ttlDay=1;
    private $needJsonDecode=true;

    function __construct()
    {
        $this->onNewService();
    }

    function send($url='',$postData=[],$headers=[],$options=[],$method='post')
    {
        //从缓存中拿
        $take=$this->takeResult($url,$postData);

        //不是空，说明缓存里有数据，直接返回
        if (!empty($take)) return $this->needJsonDecode ? json_decode($take,true) : $take;

        $method=strtoupper($method);

        //新建请求
        $request=new HttpClient($url);

        //设置head头
        empty($headers) ?: $request->setHeaders($headers,true,false);

        try
        {
            //发送请求
            $method === 'POST' ? $data=$request->post($postData) : $data=$request->get();

            //整理结果
            $data=$data->getBody();

        }catch (\Exception $e)
        {
            return $this->writeErr($e,__CLASS__);
        }

        //缓存起来
        $this->storeResult($url,$postData,$data);

        return $this->needJsonDecode ? json_decode($data,true) : $data;
    }

    function needJsonDecode($type)
    {
        $this->needJsonDecode=$type;

        return $this;
    }

    private function storeResult($url,$postData,$result)
    {
        $key=$this->createKey($url,$postData);

        $redis=Redis::defer('redis');

        $redis->select($this->db);

        return $redis->setEx($key,$this->ttlDay * 86400,$result);
    }

    private function takeResult($url,$postData)
    {
        $key=$this->createKey($url,$postData);

        $redis=Redis::defer('redis');

        $redis->select($this->db);

        return $redis->get($key);
    }

    private function createKey($url,$postData): string
    {
        $unsetTarget=[
            'rt','sign'
        ];

        $data=[];

        foreach ($postData as $key => $val)
        {
            if (in_array($key,$unsetTarget)) continue;
            $data[$key]=$val;
        }

        krsort($data);

        return md5($url.json_encode($data));
    }

}
