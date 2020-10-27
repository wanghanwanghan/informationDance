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
        return <<<Eof
<h1>这</h1>
<h2>是</h2>
<h3>您</h3>
<h4>生</h4>
<h5>成</h5>
<h6>的</h6>
<h7>!</h7>
Eof;
    }

}
