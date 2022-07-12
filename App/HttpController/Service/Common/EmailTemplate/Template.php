<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template
{
    use Singleton;

    function getSubject($title)
    {


        return trim($title);
    }

    function getBody($htmlBody)
    {
        return $htmlBody;
    }

}
