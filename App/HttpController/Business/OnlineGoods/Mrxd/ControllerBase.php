<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\HttpController\Index;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
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

        $set = ConfigInfo::sMembers('online_needs_login');
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'needsCheckToken set ' => $set,
                'actionName'=>$this->actionName
            ])
        );
        if(empty($set)){
            //redis 异常了 先锁住
            return true;
        }
        if(
            in_array(
                $this->actionName,$set
            )
        ){
            return true;
        }
        return false;
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
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'needsCheckToken ' => [
                        'yes',
                        '$action'=>$action
                    ],
                ])
            );
            if (!$this->checkToken() ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'checkToken pass' => false,
                    ])
                );
                $this->writeJson(243, null, null, 'token错误');
                return false;
            }
            else{
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'checkToken pass' => true,
                    ])
                );
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
        CommonService::getInstance()->log4PHP(json_encode(
            [
                '$requestToken'=>$requestToken,
            ]
        ));
        if (empty($requestToken) || strlen($requestToken) < 50){
            CommonService::getInstance()->log4PHP(' empty token  '.$requestToken);
            return false;
        } 
        try {
            $res = OnlineGoodsUser::findByToken($requestToken);
            $res && $res = $res->toArray();
        } catch (\Throwable $e) {
            // $this->writeErr($e, __FUNCTION__);
            CommonService::getInstance()->log4PHP('mysql has not find token: '.$requestToken);
            return false;
        }

        if (empty($res)){
            return false;
        } 
        $this->setLoginUserInfo($res);

        $tokenInfo = UserService::getInstance()->decodeAccessToken($requestToken);

        if (!is_array($tokenInfo) || count($tokenInfo) != 3){
            return false;
        } 

        $reqPhone = $this->request()->getRequestParam('phone') ?? '';
        if(empty($reqPhone)){
            $body = $this->request()->getBody()->__toString();
            $body = jsonDecode($body);
            $reqPhone = $body['phone'];
        }

        $tokenPhone = current($tokenInfo);
        CommonService::getInstance()->log4PHP(
            json_encode([
                '$tokenInfo' => $tokenInfo,
                '$tokenPhone' => $tokenPhone,
                '$reqPhone' => $reqPhone,
            ])
        );
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
}