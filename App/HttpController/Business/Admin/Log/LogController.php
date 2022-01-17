<?php

namespace App\HttpController\Business\Admin\Log;

use App\HttpController\Business\Admin\User\UserBase;
use App\HttpController\Models\Api\User;

class LogController extends LogBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    public function getErrList(){

        $ignore = [
            '.', '..', '.gitignore',
        ];

        $dir = LOG_PATH ;

        $res = [];

        if ($dh = opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                if (!in_array($file, $ignore, true)) {
                    $fullpath = $dir . DIRECTORY_SEPARATOR . $file;
                    $info = [];
                    //文件名称
                    $info['file_name'] = $file;
                    //获取文件的类型,返回的是文件的类型
                    $info['file_type'] = filetype($fullpath);
                    //获得文件的大小,返回byte
                    $info['file_size_byte'] = round(filesize($fullpath));
                    //获得文件的大小,返回kb
                    $info['file_size_kb'] = round(filesize($fullpath) / 1024);
                    //获得文件的大小,返回mb
                    $info['file_size_mb'] = round(filesize($fullpath) / 1024 / 1024);
                    //获得文件行数
                    $info['file_line'] = $this->getFileLineNum($fullpath);
                    //获取文件的创建时间
                    $info['file_c_time'] = filectime($fullpath);
                    //文件的修改时间
                    $info['file_m_time'] = filemtime($fullpath);
                    //文件的最后访问时间
                    $info['file_a_time'] = fileatime($fullpath);
                    $info['info'] = $this->readLog($fullpath);
                    $res[] = $info;
                }
            }
        }
        closedir($dh);
        return $this->writeJson(200, [], $res);
    }

    /**
     * 获取文件总行数
     * @param $file
     * @return int
     */
    function getFileLineNum($file): int
    {
        if (!is_file($file)) return 0;

        $fp = fopen($file, 'r');

        $i = 0;

        while (!feof($fp)) {
            //每次读取2M
            if ($data = fread($fp, 1024 * 1024 * 2)) {
                //计算读取到的行数
                $num = substr_count($data, "\n");
                $i += $num;
            }
        }

        fclose($fp);

        return $i - 0;
    }

    /**
     * 获取文件内容
     * @param $file
     * @return array|string
     */
    function readLog($file): string
    {
        if (!is_file($file)) return '文件内容为空';

        $handle = new \SplFileObject($file, 'r');
        $handle->seek(0);
        $res = '';
        while (true){
            $current = $handle->current();
            if (!$current) break;
            $res .= $current;
            $handle->next();
        }
        return $res;
    }
}
