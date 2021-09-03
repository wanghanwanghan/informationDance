<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template04
{
    use Singleton;

    function getSubject($entName)
    {
        $res = $entName . ' 税务版 生成完成';

        return trim($res);
    }

    function getBody()
    {
        return '';
    }

}
