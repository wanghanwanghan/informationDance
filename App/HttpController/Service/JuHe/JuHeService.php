<?php

namespace App\HttpController\Service\JuHe;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class JuHeService extends ServiceBase
{
    use Singleton;

    private $addressCompletionUrl;
    private $addressCompletionKey;

    function __construct()
    {
        $this->addressCompletionUrl = CreateConf::getInstance()->getConf('juhe.addressCompletionUrl');
        $this->addressCompletionKey = CreateConf::getInstance()->getConf('juhe.addressCompletionKey');
        return parent::__construct();
    }

    function addressCompletion(): ?array
    {
        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->addressCompletionUrl . '?key=' . $this->addressCompletionKey);

        return is_array($res) ? $res : jsonDecode($res);
    }


}
