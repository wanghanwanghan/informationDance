<?php

namespace App\HttpController\Service\LiuLengJing;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class LiuLengJingService extends ServiceBase
{
    use Singleton;

    private $baseUrl;
    private $AppId;
    private $Ak;
    private $Sk;
    private $Token;
    private $Pem;

    function __construct()
    {
        $this->baseUrl = 'http://open.linkinip.com/apis/';
        $this->AppId = CreateConf::getInstance()->getConf('liulengjing.AppId');
        $this->Ak = CreateConf::getInstance()->getConf('liulengjing.Ak');
        $this->Sk = CreateConf::getInstance()->getConf('liulengjing.Sk');
        $this->Token = CreateConf::getInstance()->getConf('liulengjing.Token');
        $this->Pem = implode(PHP_EOL, CreateConf::getInstance()->getConf('liulengjing.pem'));
        return parent::__construct();
    }


}
