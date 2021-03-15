<?php

namespace App\HttpController\Service\Sms;

use App\HttpController\Service\CreateConf;
use Qiniu\Auth;
use Qiniu\Sms\Sms;

class QiniuSms
{
    public $ak;
    public $sk;

    function __construct()
    {
        $this->ak = CreateConf::getInstance()->getConf('env.qiNiuAk');
        $this->sk = CreateConf::getInstance()->getConf('env.qiNiuSk');
    }

    private function createObj()
    {
        $auth = new Auth($this->ak, $this->sk);
        return  new Sms($auth);
    }
}
