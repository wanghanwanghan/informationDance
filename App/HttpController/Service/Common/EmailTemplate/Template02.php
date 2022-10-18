<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template02
{
    use Singleton;

    private $subject = '';

    function setSubject($str): Template02
    {
        $this->subject = $str;
        return $this;
    }

    function getSubject($entName): string
    {
        return empty($this->subject) ? $entName . ' 律师自用版 生成完成' : $this->subject;
    }

    function getBody(): string
    {
        return '';
    }

}
