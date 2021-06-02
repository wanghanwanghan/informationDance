<?php

namespace App\HttpController\Service\Zip;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class ZipService extends ServiceBase
{
    use Singleton;

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    function unzip($filename, $path): ?string
    {
        $filename = $filename . '.zip';

        //先判断待解压的文件是否存在
        if (!file_exists($filename)) {
            return "文件 {$filename} 不存在！";
        }

        $starttime = explode(' ', microtime()); //解压开始的时间

        //打开压缩包
        $resource = zip_open($filename);

        //遍历读取压缩包里面的一个个文件
        while ($dir_resource = zip_read($resource)) {
            //如果能打开则继续
            if (zip_entry_open($resource, $dir_resource)) {
                //获取当前项目的名称,即压缩包里面当前对应的文件名
                $file_name = $path . zip_entry_name($dir_resource);
                //如果不是目录，则写入文件
                if (!is_dir($file_name)) {
                    //读取这个文件
                    $file_size = zip_entry_filesize($dir_resource);
                    if ($file_size < (1024 * 1024 * 100)) {
                        $file_content = zip_entry_read($dir_resource, $file_size);
                        file_put_contents($file_name, $file_content);
                    }
                }
                //关闭当前
                zip_entry_close($dir_resource);
            }
        }

        //关闭压缩包
        zip_close($resource);
        $endtime = explode(' ', microtime()); //解压结束的时间
        $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
        $thistime = round($thistime, 3); //保留3为小数

        return "本次解压花费 {$thistime} 秒";
    }

}
