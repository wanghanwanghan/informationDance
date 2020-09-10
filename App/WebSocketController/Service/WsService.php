<?php

namespace App\WebSocketController\Service;

use App\WebSocketController\WebSocketParser;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\Socket\Config;
use EasySwoole\Socket\Dispatcher;

class WsService
{
    use Singleton;

    //在mainServerCreate中调用
    function create(EventRegister $register)
    {
        // 创建一个 Dispatcher 配置
        $conf = new Config();

        // 设置 Dispatcher 为 WebSocket 模式
        $conf->setType(Config::WEB_SOCKET);

        // 设置解析器对象
        $conf->setParser(new WebSocketParser());

        // 创建 Dispatcher 对象 并注入 config 对象
        $dispatch = new Dispatcher($conf);

        // 给server 注册相关事件 在 WebSocket 模式下  on message 事件必须注册 并且交给 Dispatcher 对象处理
        $register->set(EventRegister::onMessage, function (\swoole_websocket_server $server, \swoole_websocket_frame $frame) use ($dispatch) {
            $dispatch->dispatch($server, $frame->data, $frame);
        });

        // 注册onOpen
        $register->set(EventRegister::onOpen, function (\swoole_websocket_server $server, \swoole_http_request $request) use ($dispatch) {
            //取得fd，然后通过参数发给不同的controller进行业务处理
        });

        // 注册onClose
        $register->set(EventRegister::onClose, function (\swoole_websocket_server $server, $fd) use ($dispatch) {
            //取得fd，然后通过参数发给不同的controller进行业务处理
        });

        return true;
    }


}
