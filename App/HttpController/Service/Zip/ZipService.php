<?php

namespace App\HttpController\Service\Zip;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\control;

class ZipService extends ServiceBase
{
    use Singleton;

    //返回本次解压后的文件名
    function unzip($filename, $path): ?array
    {
        $unzip_filename = [];

        //先判断待解压的文件是否存在
        if (!file_exists($filename)) {
            return null;
        }

        //打开压缩包
        $resource = zip_open($filename);

        //遍历读取压缩包里面的一个个文件
        while ($dir_resource = zip_read($resource)) {
            //如果能打开则继续
            if (zip_entry_open($resource, $dir_resource)) {
                //获取当前项目的名称,即压缩包里面当前对应的文件名
                $filename_now = zip_entry_name($dir_resource);
                $file_name = $path . $filename_now;
                //如果不是目录，则写入文件
                if (!is_dir($file_name)) {
                    //读取这个文件
                    $file_size = zip_entry_filesize($dir_resource);
                    if ($file_size < (1024 * 1024 * 3000)) {
                        $file_content = zip_entry_read($dir_resource, $file_size);
                        file_put_contents($file_name, $file_content);
                        $unzip_filename[] = $filename_now;
                    }
                }
                //关闭当前
                zip_entry_close($dir_resource);
            }
        }

        //关闭压缩包
        zip_close($resource);

        return $unzip_filename;
    }

    function zip($fileArr, $zipName, $overwrite = false): ?string
    {
        $zip = new \ZipArchive;

        ($overwrite === true && file_exists($zipName)) ? $overwrite = true : $overwrite = false;

        $zip->open($zipName, $overwrite ? \ZIPARCHIVE::OVERWRITE : \ZIPARCHIVE::CREATE);

        foreach ($fileArr as $one) {
            $arr = explode(DIRECTORY_SEPARATOR, $one);
            $name = end($arr);
            $res = $zip->addFile($one, $name);
            CommonService::getInstance()->log4PHP(
                 json_encode([
                     'zip res '=>$res,
                     '$one'=>$one,
                     '$name'=>$name,
                 ],JSON_UNESCAPED_UNICODE)
            ); 
        }

        $zip->close();

        return $zipName;
    }

}
