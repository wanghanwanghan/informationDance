<?php

namespace App\HttpController\Service\Common\EmailTemplate;

use EasySwoole\Component\Singleton;

class Template03
{
    use Singleton;

    private $subject = '';

    function setSubject($str): Template03
    {
        $this->subject = $str;
        return $this;
    }

    function getSubject($entName): string
    {
        return empty($this->subject) ? $entName . ' 尽调版 生成完成' : $this->subject;
    }

    function getBody(): string
    {
        return '';
    }

}
