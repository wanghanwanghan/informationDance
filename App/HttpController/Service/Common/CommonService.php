<?php

namespace App\HttpController\Service\Common;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\control;

class CommonService extends ServiceBase
{
    use Singleton;

    function storeImage(UploadFile $uploadFile,$type): string
    {
        $type=strtolower($type);

        switch ($type)
        {
            case 'avatar':
                $newFilename=control::getUuid().'.jpg';
                $uploadFile->moveTo(AVATAR_PATH.$newFilename);
                $returnPath=str_replace(ROOT_PATH,'',AVATAR_PATH.$newFilename);
                break;
            default:
                $returnPath='';
        }

        return $returnPath;
    }





}
