<?php

namespace App\HttpController\Service\HttpClient;

use App\HttpController\Service\ServiceBase;
use EasySwoole\HttpClient\HttpClient;

class CoHttpClient extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $needJsonDecode=true;

    function send($url='',$data=[],$headers=[],$options=[],$method='post')
    {
        $method=strtoupper($method);

        //新建请求
        $request=new HttpClient($url);

        //设置head头
        empty($headers) ?: $request->setHeaders($headers,true,false);

        try
        {
            //发送请求
            $method === 'POST' ? $data=$request->post($data) : $data=$request->get();

            //整理结果
            $data=$data->getBody();

        }catch (\Exception $e)
        {
            return $this->writeErr($e,__CLASS__);
        }

        return $this->needJsonDecode ? json_decode($data,true) : $data;
    }

    function needJsonDecode($type)
    {
        $this->needJsonDecode=$type;

        return $this;
    }




}
