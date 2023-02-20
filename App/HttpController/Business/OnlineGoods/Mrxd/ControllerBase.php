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
        $noNeedsLoginAtions = [
            'login',
            'sendSms',
            'register',
            'getSearchOption',
            'getProducts',
            'getProductDetail',

        ];

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '智慧金荣-不需要登录就可以访问的方法集' => $noNeedsLoginAtions,
                '当前方法'=>$this->actionName
            ],JSON_UNESCAPED_UNICODE)
        );

        if(
            in_array(
                $this->actionName,$noNeedsLoginAtions
            )
        ){
            return false;
        }
        return true;
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
                    '需要checkToken ' => [
                        '方法名'=>$action
                    ],
                ],JSON_UNESCAPED_UNICODE)
            );
            if (!$this->checkToken() ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '需要checkToken ' => [
                            '方法名'=>$action,
                            'checkToken失败'=>'',
                        ],
                    ],JSON_UNESCAPED_UNICODE)
                );
                $this->writeJson(243, null, null, 'token错误');
                return false;
            }
            else{
               
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
        if (empty($requestToken) || strlen($requestToken) < 50){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    "checkToken异常-token为空"=>[
                        '请求的token'=>$requestToken
                    ]
                ],JSON_UNESCAPED_UNICODE)
            );
            return false;
        } 
        try {
            $res = OnlineGoodsUser::findByToken($requestToken);
            $res && $res = $res->toArray();
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    "checkToken异常-找不到token"=>[
                        '请求的token'=>$requestToken,
                        '报错信息'=>$e->getMessage(),
                    ],
                ],JSON_UNESCAPED_UNICODE)
            );
            // $this->writeErr($e, __FUNCTION__);
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

        if (strlen($tokenPhone) != 11 || strlen($reqPhone) != 11){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    "checkToken异常-token格式异常-长度不是11位"=>[
                        '请求的token'=>$requestToken,
                        'token解析到的手机'=>$tokenPhone,
                        '请求的手机号'=>$reqPhone,
                    ],
                ],JSON_UNESCAPED_UNICODE)
            );
            return false;
        } 
        $res = $reqPhone - 0 === $tokenPhone - 0;

        return $res;
    }

    function getRequestData($key = '', $default = '')
    {
        $string = $this->request()->getBody()->__toString();

        $raw = jsonDecode($string);
        $form = $this->request()->getRequestParam();

        !empty($raw) ?: $raw = [];
        !empty($form) ?: $form = [];

        $requestData = array_merge($raw, $form);

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