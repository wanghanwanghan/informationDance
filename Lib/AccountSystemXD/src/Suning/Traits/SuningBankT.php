<?php

namespace AccountSystemXD\Suning\Traits;

trait SuningBankT
{
    // ssl双向 cert.pem
    function setSslCert(string $path)
    {
        $this->certPem = $path;
        return $this;
    }

    // ssl双向 private.pem
    function setSslPrivate(string $path)
    {
        $this->privatePem = $path;
        return $this;
    }

    // ssl双向 密码
    function setSslPwd(string $pwd)
    {
        $this->sslPwd = $pwd;
        return $this;
    }

    // app服务端公钥二进制内容
    function setAppSer(string $path)
    {
        $this->serAppPem = file_get_contents($path);
        return $this;
    }

    // app客户端私钥二进制内容
    function setAppCli(string $path)
    {
        $this->cliAppPem = file_get_contents($path);
        return $this;
    }

    // sit uat prd 切换url环境
    function setBaseUrl(string $url)
    {
        $this->urlBase = $url;
        return $this;
    }

    function setTerminal(string $terminal)
    {
        $this->terminal = $terminal;
        return $this;
    }

    function setIpAddress(string $ipAddress)
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    function setPlatformcd(string $platformcd)
    {
        $this->platformcd = $platformcd;
        return $this;
    }

    function setChannelId(string $channelId)
    {
        $this->channelId = $channelId;
        return $this;
    }

    function setMerchantId(string $merchantId)
    {
        $this->merchantId = $merchantId;
        return $this;
    }

    function setAppCode(string $appCode)
    {
        $this->appCode = $appCode;
        return $this;
    }

}