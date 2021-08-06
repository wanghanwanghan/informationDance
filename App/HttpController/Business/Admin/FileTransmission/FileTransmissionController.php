<?php

namespace App\HttpController\Business\Admin\FileTransmission;

use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\control;

class FileTransmissionController extends FileTransmissionBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getFileList(): bool
    {
        $ignore = [
            '.', '..', '.gitignore',
        ];
        $dir = TEMP_FILE_PATH;
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
                    $info['file_size_kb'] = round(filesize($fullpath) / 1024, 2);
                    //获得文件的大小,返回mb
                    $info['file_size_mb'] = round(filesize($fullpath) / 1024 / 1024, 2);
                    //获取文件的创建时间
                    $info['file_c_time'] = filectime($fullpath);
                    //文件的修改时间
                    $info['file_m_time'] = filemtime($fullpath);
                    //文件的最后访问时间
                    $info['file_a_time'] = fileatime($fullpath);
                    $res[] = $info;
                }
            }
        }
        closedir($dh);

        if (!empty($res)) {
            $res = control::sortArrByKey($res, 'file_c_time', 'desc', true);
        }

        return $this->writeJson(200, null, $res);
    }

    function uploadFileToDir(): bool
    {
        $files = $this->request()->getUploadedFiles();

        foreach ($files as $key => $oneFile) {
            if ($oneFile instanceof UploadFile) {
                try {
                    $oneFile->moveTo(TEMP_FILE_PATH . $oneFile->getClientFilename());
                } catch (\Throwable $e) {
                    return $this->writeErr($e, __FUNCTION__);
                }
            }
        }

        $this->delFileByCtime(TEMP_FILE_PATH, 14);

        return $this->writeJson(200);
    }


}