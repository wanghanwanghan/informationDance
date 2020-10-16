<?php

namespace App\HttpController\Service\RequestUtils;

use App\HttpController\Models\Api\Statistics;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\User\UserService;
use EasySwoole\Http\Request;

class StatisticsService extends ServiceBase
{
    private $pathInfo;
    private $token;

    function __construct(Request $request)
    {
        parent::__construct();

        $this->pathInfo = $request->getSwooleRequest()->server['path_info'];
        $this->token = $request->getHeaderLine('authorization');

        return true;
    }

    //通过请求地址统计
    function byPath()
    {
        if (empty($this->pathInfo)) return true;

        $path = trim($this->pathInfo);

        if (empty($this->token) || strlen($this->token) < 50) {
            $info = ['', '', ''];
        } else {
            try {
                $info = UserService::getInstance()->decodeAccessToken($this->token);
            } catch (\Throwable $e) {
                $info = ['', '', ''];
            }
        }

        $phone = current($info);

        try {
            $insert = [
                'path' => $path,
                'phone' => $phone,
            ];

            Statistics::create()->data($insert, false)->save();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return true;
    }


}
