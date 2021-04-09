<?php

namespace App\HttpController\Service\Sms;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class SmsService extends ServiceBase
{
    use Singleton;

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    function __construct()
    {
        parent::__construct();
    }

    function reg($phone, $code)
    {
        return (new AliSms())->reg($phone, $code);
    }

    function login($phone, $code)
    {
        return (new AliSms())->login($phone, $code);
    }

    function afterUploadAuthBook($phone, $ext)
    {
        return (new AliSms())->afterUploadAuthBook($phone, $ext);
    }


}
