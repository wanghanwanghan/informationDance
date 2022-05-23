<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\HttpController\Index;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Service\User\UserService;

class ControllerBase extends Index
{   
    public $needsCheckToken =  false;
    public $loginUserinfo = [];
    function setChckToken($res){
        $this->needsCheckToken = $res;
    }
    function onRequest(?string $action): ?bool
    {
        if($this->needsCheckToken){
            $checkToken = $this->checkToken();
            if (!$checkToken ){
                $this->writeJson(240, null, null, 'token错误');
            } 
        }
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function checkToken(): bool
    { 
        $requestToken = $this->request()->getHeaderLine('authorization');

        if (empty($requestToken) || strlen($requestToken) < 50) return false;

        try {
            $res = AdminNewUser::create()->where('token', $requestToken)->get();
        } catch (\Throwable $e) {
            // $this->writeErr($e, __FUNCTION__);
            return false;
        }

        if (empty($res)) return false;
        $this->setLoginUserInfo($res);

        $tokenInfo = UserService::getInstance()->decodeAccessToken($requestToken);

        if (!is_array($tokenInfo) || count($tokenInfo) != 3) return false;

        $reqPhone = $this->request()->getRequestParam('phone') ?? '';

        $tokenPhone = current($tokenInfo);

        if (strlen($tokenPhone) != 11 || strlen($reqPhone) != 11) return false;

        return $reqPhone - 0 === $tokenPhone - 0;
    }

    private function setLoginUserInfo($userInfo){
        $this->loginUserinfo = $userInfo;
    }

    function writeJson($statusCode = 200, $paging = null, $result = null, $msg = null, $format = true, $ext = [])
    {
        if (!$this->response()->isEndResponse()) {
            if (!empty($paging) && is_array($paging)) {
                foreach ($paging as $key => $val) {
                    $paging[$key] = $val - 0;
                }
            }
            $data = [
                'code' => $statusCode,
                'paging' => $paging,
                'result' => $format === true ? control::changeArrVal($result, ['', null], '--', true) : $result,
                'msg' => $msg,
                'ext' => $ext
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