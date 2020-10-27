<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template01
{
    use Singleton;

    function getSubject($entName)
    {
        $res = $entName . ' 企业速透版 生成完成';

        return trim($res);
    }

    function getBody()
    {
        return '';
    }

}
