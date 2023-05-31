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
        // CommonService::getInstance()->log4PHP('  tmpStr '.$tmpStr);
        if($tmpStr){
            $tmpArr = @json_decode($tmpStr,true);
            // CommonService::getInstance()->log4PHP('  tmpArr '.json_encode($tmpArr));
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

    //计算分页
    function exprOffset($page, $pageSize): int
    {
        return ($page - 1) * $pageSize;
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
        $requestToken = $this->request()->getHeaderLine('x-token');
        if(empty($requestToken)){
            $requestToken = $this->request()->getHeaderLine('X-Token');
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                $this->request()->getHeaderLine('x-token'),
                $this->request()->getHeaderLine('X-Token'),
            ],JSON_UNESCAPED_UNICODE)
        );

        if (empty($requestToken) || strlen($requestToken) < 50){
            CommonService::getInstance()->log4PHP(' empty token  '.$requestToken);
            return false;
        } 
        try {
            $res = AdminNewUser::create()
                    ->where('token', $requestToken)
                    ->field(['id', 'user_name', 'phone','email','money','status','created_at','updated_at'])
                    ->get();
        } catch (\Throwable $e) {
            // $this->writeErr($e, __FUNCTION__);
            CommonService::getInstance()->log4PHP(' invalid token  '.$requestToken);
            return false;
        }

        if (empty($res)){
            return false;
        } 
        $this->setLoginUserInfo($res);
        $tokenInfo = UserService::getInstance()->decodeAccessToken($requestToken);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'check token   1 ',
//                '$tokenInfo' => $tokenInfo
//            ])
//        );
        if (!is_array($tokenInfo) || count($tokenInfo) != 3){
//            CommonService::getInstance()->log4PHP(
//                json_encode(
//                    [
//                        ' !is_array($tokenInfo) || count($tokenInfo) != 3',
//                        '$requestToken'=>$requestToken,
//                        '$tokenInfo'=>$tokenInfo,
//                    ]
//                )
//            );
            return false;
        } 

        $reqPhone = $this->request()->getRequestParam('phone') ?? '';
        if(empty($reqPhone)){
            $body = $this->request()->getBody()->__toString();
            $body = jsonDecode($body);
            $reqPhone = $body['phone'];
        }

        $tokenPhone = current($tokenInfo);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'check token 2  ',
//                '$reqPhone' => $reqPhone,
//                '$tokenPhone' => $tokenPhone,
//                '$body'=>$body['phone'],
//            ])
//        );
        if (strlen($tokenPhone) != 11 || strlen($reqPhone) != 11){
//            CommonService::getInstance()->log4PHP(
//                json_encode(
//                    [
//                        ' $tokenPhone  error ',
//                        '$tokenPhone' => $tokenPhone,
//                        '$reqPhone' => $reqPhone,
//                        '$tokenInfo'=>$tokenInfo,
//                        'current($tokenInfo)'=>current($tokenInfo),
//                    ]
//                )
//            );
            return false;
        } 
        $res = $reqPhone - 0 === $tokenPhone - 0;
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'check token res  ',
//                '$res' => $res,
//            ])
//        );
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

    function writeJsonV2($statusCode = 200, $paging = null, $result = null, $msg = null, $ext = [])
    {
        $data = [
            'code' => $statusCode,
            'paging' => $paging,
            'result' =>  $result,
            'msg' => $msg,
            'ext' => $ext
        ];
        $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->withStatus($statusCode);

            return true;

    }
}