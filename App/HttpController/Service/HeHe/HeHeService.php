<?php

namespace App\HttpController\Service\HeHe;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\traits\Singleton;

class HeHeService extends ServiceBase
{
    use Singleton;

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $appKey;
    private $appSecret;
    private $ocrUrl;

    function __construct()
    {
        $this->appKey = CreateConf::getInstance()->getConf('hehe.appKey');
        $this->appSecret = CreateConf::getInstance()->getConf('hehe.appSecret');
        $this->ocrUrl = CreateConf::getInstance()->getConf('hehe.ocrUrl');
        return parent::__construct();
    }

    //识别图片中的文字
    function ocrImageToWord($file)
    {
        $headers = [
            'app-key' => $this->appKey,
            'app-secret' => $this->appSecret,
        ];

        if ($file instanceof UploadFile) $file = $file->getStream()->__toString();

        $res = TaskService::getInstance()->create(function () use ($headers, $file) {
            return (new CoHttpClient())->useCache(false)->send($this->ocrUrl, $file, $headers);
        }, 'sync');

        return $res;
    }


}
