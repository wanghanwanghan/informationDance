<?php

namespace App\HttpController\Business\Api\HuoYan;

use App\HttpController\Index;
use wanghanwanghan\someUtils\control;

class HuoYanBase extends Index
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //重写writeJson
    function writeJson($statusCode = 200, $paging = null, $result = null, $msg = null, $format = true)
    {
        if (!$this->response()->isEndResponse()) {
            if (!empty($paging) && is_array($paging)) {
                foreach ($paging as $key => $val) {
                    $paging[$key] = (int)$val;
                }
            }
            $data = [
                'code' => $statusCode,
                'paging' => $paging,
                'result' => $format === true ? control::changeArrVal($result, ['', null], '--', true) : $result,
                'msg' => $msg
            ];
            $this->response()->write(jsonEncode($data, false));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        } else {
            return false;
        }
    }
}