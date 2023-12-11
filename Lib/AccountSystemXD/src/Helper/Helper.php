<?php

namespace AccountSystemXD\Helper;

class Helper
{
    private static $instance;

    static function getInstance(...$args): Helper
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(...$args);
        }

        return self::$instance;
    }

    // 毫秒时间戳
    function getMicroTime(): string
    {
        return substr(microtime(true) * 1000, 0, 13);
    }

    function jsonEncode($arr)
    {
        return json_encode(
            json_decode(json_encode($arr), true),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    function jsonDecode($arr)
    {
        return json_decode($arr, true);
    }

    // 去掉数组里空值
    function arrayFilter(array $arr): array
    {
        return array_filter($arr, function ($raw) {
            return !(($raw === '' || $raw === null));
        });
    }

    // 写log 暂时用
    function writeLog($content)
    {
        $file = getcwd() . '/' . date('Ymd', time()) . '.log';
        !is_array($content) ?: $content = $this->jsonEncode($content);
        return file_put_contents($file, trim($content) . PHP_EOL, FILE_APPEND);
    }
}
