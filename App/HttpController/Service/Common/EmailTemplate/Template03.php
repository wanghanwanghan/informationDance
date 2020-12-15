<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template03
{
    use Singleton;

    function getSubject($entName)
    {
        $res = $entName . ' 尽调版 生成完成';

        return trim($res);
    }

    function getBody()
    {
        return '';
    }

}
