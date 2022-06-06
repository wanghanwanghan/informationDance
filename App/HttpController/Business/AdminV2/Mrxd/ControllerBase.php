<?php

namespace App\HttpController\Business\AdminV2\Mrxd;

use App\HttpController\Index;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\User\UserService;
use wanghanwanghan\someUtils\control;

class ControllerBase extends Index
{   
    public $needsCheckToken =  false;
    public $loginUserinfo = [];
    public $actionName ;
    private function setActionName($action){
        $this->actionName = $action;
    }
    function needsCheckToken(){
         if(
             in_array(
                $this->actionName,$this->getNoNeedCheckMethods()
             )
         ){
            return false;
         }
         return true ;
    }
    function getNoNeedCheckMethods(){
        // 需要加层缓存
        $res = ConfigInfo::create()->where('name', 'admin_no_check_methods')->get();
        $methodsLists = [];
        $tmpStr = trim($res->getAttr('value'));
        CommonService::getInstance()->log4PHP('  tmpStr '.$tmpStr);
        if($tmpStr){
            $tmpArr = @json_decode($tmpStr,true);
            CommonService::getInstance()->log4PHP('  tmpArr '.json_encode($tmpArr));
            if(
                is_array($tmpArr) &&
                !empty($tmpArr) 
            ){
                $methodsLists = $tmpArr;
    
                return array_keys($methodsLists);
            }
        }; 
        return $methodsLists;
    }

    function onRequest(?string $action): ?bool
    {
        $this->setActionName($action);
        if($this->needsCheckToken()){ 
            if (!$this->checkToken() ){
                $this->writeJson(243, null, null, 'token错误');
                return false;
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
        // $requestToken = $this->request()->getHeaderLine('authorization');
        $requestToken = $this->request()->getHeaderLine('x-token');
        CommonService::getInstance()->log4PHP('  requestToken '.$requestToken);

        if (empty($requestToken) || strlen($requestToken) < 50){
            CommonService::getInstance()->log4PHP(
                ' token error 1 '.$requestToken .' empty: '.empty($requestToken). 
                    ' strlen '.strlen($requestToken) ); 
            return false;
        } 
        try {
            $res = AdminNewUser::create()->where('token', $requestToken)->get();
        } catch (\Throwable $e) {
            // $this->writeErr($e, __FUNCTION__);
            CommonService::getInstance()->log4PHP(' token error 2 '.$requestToken);  
            return false;
        }

        if (empty($res)){
            CommonService::getInstance()->log4PHP(' token error 2.5 '.$requestToken .' res'.json_encode($res));  
            return false;
        } 
        $this->setLoginUserInfo($res);

        $tokenInfo = UserService::getInstance()->decodeAccessToken($requestToken);

        if (!is_array($tokenInfo) || count($tokenInfo) != 3){
            CommonService::getInstance()->log4PHP(' token error 3 '.$requestToken .json_encode($tokenInfo));   
            return false;
        } 

        $reqPhone = $this->request()->getRequestParam('phone') ?? '';

        $tokenPhone = current($tokenInfo);

        if (strlen($tokenPhone) != 11 || strlen($reqPhone) != 11){
            CommonService::getInstance()->log4PHP(' token error 4 '.$requestToken .$tokenPhone);   
            return false;
        } 
        $res = $reqPhone - 0 === $tokenPhone - 0;
        CommonService::getInstance()->log4PHP('  return  '.json_encode(
            [
                $res,
                $tokenPhone,
                $reqPhone
            ]
        ) );   

        return $res;
    }

    function getRequestData($key = '', $default = '')
    {
        $string = $this->request()->getBody()->__toString();

        $raw = jsonDecode($string);
        $form = $this->request()->getRequestParam();
        // CommonService::getInstance()->log4PHP(
        //     [
        //         'getRequestData',
        //         'string' => $string,
        //         'raw' => $raw,
        //         'form' => $form,
        //     ]
        // );
        !empty($raw) ?: $raw = [];
        !empty($form) ?: $form = [];

        $requestData = array_merge($raw, $form);
        // CommonService::getInstance()->log4PHP(
        //     [
        //         'getRequestData',
        //         'string' => $string,
        //         'raw' => $raw,
        //         'form' => $form,
        //         'requestData' => $requestData,
        //     ]
        // );
        if($key){
            return (isset($requestData[$key])) ? $requestData[$key] : $default;
        }
        else{
            return$requestData;
        }
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