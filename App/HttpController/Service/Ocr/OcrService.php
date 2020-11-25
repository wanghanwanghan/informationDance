<?php

namespace App\HttpController\Service\Ocr;

use App\HttpController\Models\Api\OcrQueue;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class OcrService extends ServiceBase
{
    use Singleton;

    function getOcrContentForReport($phone, $reportNum, $catalogueNum): string
    {
        //'<w:br/>' word中换行

        try
        {
            $info = OcrQueue::create()
                ->where(['phone' => $phone, 'reportNum' => $reportNum, 'catalogueNum' => $catalogueNum])
                ->get();

            empty($info) ? $info = '' : $info = str_replace('|||', '<w:br/>', trim($info->content));

        }catch (\Throwable $e)
        {
            $info = '';
            CommonService::getInstance()->writeErr($e,__FUNCTION__);
        }

        return $info;
    }


}
