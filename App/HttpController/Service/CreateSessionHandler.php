<?php

namespace App\HttpController\Service;

use Carbon\Carbon;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Session\Session;
use EasySwoole\Session\SessionFileHandler;

class CreateSessionHandler extends ServiceBase
{
    use Singleton;

    private $cookiePrefix = 'information_dance_';

    private function getCookieName()
    {
        $ymd = Carbon::now()->format('Ymd');
        return $this->cookiePrefix . $ymd;
    }

    //只在mainServerCreate调用
    function create($dir)
    {
        $handler = new SessionFileHandler($dir);
        Session::getInstance($handler, $this->getCookieName(), $dir);
        return true;
    }

    //在onRequest调用
    function check(Request $request, Response $response)
    {
        $cookieName = $this->getCookieName();

        $cookie = $request->getCookieParams($cookieName);

        if (empty($cookie)) {
            $sid = Session::getInstance()->sessionId();
            $response->setCookie($cookieName, $sid);
        } else {
            Session::getInstance()->sessionId($cookie);
        }

        return true;
    }

}
