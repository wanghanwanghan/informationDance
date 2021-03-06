<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;

class CreateDefine extends ServiceBase
{
    use Singleton;

    //只能在mainServerCreate中用
    public function createDefine($root)
    {
        //用来做str_replace的
        define('ROOT_PATH', $root);

        define('STATIC_PATH', $root . DIRECTORY_SEPARATOR . 'Static' . DIRECTORY_SEPARATOR);

        define('LOG_PATH', STATIC_PATH . 'Log' . DIRECTORY_SEPARATOR);

        define('OCR_PATH', STATIC_PATH . 'Image' . DIRECTORY_SEPARATOR . 'Ocr' . DIRECTORY_SEPARATOR);
        define('AVATAR_PATH', STATIC_PATH . 'Image' . DIRECTORY_SEPARATOR . 'Avatar' . DIRECTORY_SEPARATOR);
        define('AUTH_BOOK_PATH', STATIC_PATH . 'Image' . DIRECTORY_SEPARATOR . 'AuthBook' . DIRECTORY_SEPARATOR);
        define('OTHER_FILE_PATH', STATIC_PATH . 'OtherFile' . DIRECTORY_SEPARATOR);
        define('TEMP_FILE_PATH', STATIC_PATH . 'Temp' . DIRECTORY_SEPARATOR);

        define('REPORT_IMAGE_PATH', STATIC_PATH . 'Image' . DIRECTORY_SEPARATOR . 'ReportImage' . DIRECTORY_SEPARATOR);
        define('REPORT_IMAGE_TEMP_PATH', REPORT_IMAGE_PATH . 'Temp' . DIRECTORY_SEPARATOR);

        define('REPORT_MODEL_PATH', STATIC_PATH . 'ReportModel' . DIRECTORY_SEPARATOR);

        define('REPORT_PATH', STATIC_PATH . 'Report' . DIRECTORY_SEPARATOR);

        define('SIMSUN_TTC', STATIC_PATH . 'TTF' . DIRECTORY_SEPARATOR . 'simsun.ttc');
        define('BKAI00MP_TTF', STATIC_PATH . 'TTF' . DIRECTORY_SEPARATOR . 'bkai00mp.ttf');

        define('SESSION_PATH', STATIC_PATH . 'Session' . DIRECTORY_SEPARATOR);

        return true;
    }
}
