<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template
{
    use Singleton;

    function getSubject($entName)
    {
        $res = $entName ;

        return trim($res);
    }

    function getBody()
    {
        return '';
    }

}
