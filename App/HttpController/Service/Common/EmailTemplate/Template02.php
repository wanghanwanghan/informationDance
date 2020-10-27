<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template02
{
    use Singleton;

    function getSubject($entName)
    {
        $res = $entName . ' 律师自用版 生成完成';

        return trim($res);
    }

    function getBody()
    {
        return '';
    }

}
