<?php

namespace App\HttpController\Business;

use App\HttpController\Index;
use App\HttpController\Models\Api\EntLimitEveryday;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\RequestUtils\LimitService;
use App\HttpController\Service\RequestUtils\StatisticsService;
use App\HttpController\Service\User\UserService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class BusinessBase extends Index
{
    public $startResTime = 0;//请求开始时间，带毫秒
    public $stopResTime = 0;//请求结束时间，带毫秒

    public $loginUserinfo = [];

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        $this->startResTime = microtime(true);

        $checkRouter = $this->checkRouter();
        $checkToken = $this->checkToken();

        if (!$checkRouter && !$checkToken) $this->writeJson(240, null, null, 'token错误');

        $checkLimit = $this->checkLimit();

        if (!$checkLimit) $this->writeJson(201, null, null, '到达limit上限');

        //其他的一些验证
        $otherCheck = $this->otherCheck();

        return ($checkRouter || ($checkToken && $checkLimit && $otherCheck));
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);

        $this->stopResTime = microtime(true);

        $time = $this->stopResTime - $this->startResTime;

        //统计
        (new StatisticsService($this->request()))->addResTime($time)->byPath();
    }

    //重写writeJson
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

    //链接池系列抛出异常
    function writeErr(\Throwable $e, $which = 'comm'): bool
    {
        //给用户看的
        $this->writeJson(9527, null, null, $which . '错误');

        $logFileName = $which . '.log.' . date('Ymd', time());

        //给程序员看的
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();

        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";

        //返回log写入成功或者写入失败
        return control::writeLog($content, LOG_PATH, 'info', $logFileName);
    }

    //check router
    private function checkRouter(): bool
    {
        //直接放行的url，只判断url最后两个在不在数组中
        $pass = CreateConf::getInstance()->getConf('env.passRouter');

        // /api/v1/comm/create/verifyCode
        $path = $this->request()->getSwooleRequest()->server['path_info'];

        $path = rtrim($path, '/');
        $path = explode('/', $path);

        if (!empty($path)) {
            //检查url在不在直接放行数组
            $len = count($path);

            //取最后两个
            $path = implode('/', [$path[$len - 2], $path[$len - 1]]);

            //在数组里就放行
            if (in_array($path, $pass)) return true;
        }

        return false;
    }

    private function setLoginUserInfo($userInfo){
        $this->loginUserinfo = $userInfo;
    }

    //check token
    private function checkToken(): bool
    {
        $requestToken = $this->request()->getHeaderLine('authorization');

        if (empty($requestToken) || strlen($requestToken) < 50) return false;

        try {
            $res = User::create()->where('token', $requestToken)->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
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

    //check limit
    private function checkLimit(): bool
    {
        $token = $this->request()->getHeaderLine('authorization');

        isset($this->request()->getHeader('x-real-ip')[0]) ?
            $realIp = $this->request()->getHeader('x-real-ip')[0] :
            $realIp = null;

        return LimitService::getInstance()->check($token, $realIp);
    }

    //其他的一些验证
    private function otherCheck(): bool
    {
        $entName = $this->request()->getRequestParam('entName');
        $token = $this->request()->getHeaderLine('authorization');
        $todayStart = Carbon::now()->startOfDay()->timestamp;
        $todayEnd = Carbon::now()->endOfDay()->timestamp;

        $entName = [];

        //每天只能浏览100个企业
        if (!empty($entName) && !empty($token)) {
            try {
                $limitList = EntLimitEveryday::create()
                    ->field('entName')
                    ->where('token', $token)
                    ->where('created_at', $todayStart, '>')
                    ->all();

                if (empty($limitList)) {
                    $limitList = [];
                } else {
                    $tmp = [];

                    foreach ($limitList as $one) {
                        $tmp[] = $one->entName;
                    }

                    $limitList = array_unique($tmp);
                }

                EntLimitEveryday::create()->data([
                    'token' => $token,
                    'entName' => $entName,
                ])->save();

            } catch (\Throwable $e) {
                return $this->writeErr($e, __FUNCTION__);
            }

            //超过了限定次数
            if (!empty($limitList) && count($limitList) > 100) {
                $this->writeJson(230, null, null, '当天访问企业超过限制');
                return false;
            }
        }

        return true;
    }

    //计算分页
    function exprOffset($page, $pageSize): int
    {
        return ($page - 1) * $pageSize;
    }

    //form raw
    function getRequestData($key = '', $default = '')
    {
        $string = $this->request()->getBody()->__toString();

        $raw = jsonDecode($string);
        $form = $this->request()->getRequestParam();

        !empty($raw) ?: $raw = [];
        !empty($form) ?: $form = [];

        $requestData = array_merge($raw, $form);

        return (isset($requestData[$key])) ? $requestData[$key] : $default;
    }

    //删除n天前创建的文件
    function delFileByCtime($dir, $n = 10, $ignore = []): bool
    {
        if (strpos($dir, 'informationDance') === false) return true;

        $ignore = array_merge($ignore, ['.', '..', '.gitignore']);

        if (is_dir($dir) && is_numeric($n)) {
            if ($dh = opendir($dir)) {
                while (false !== ($file = readdir($dh))) {
                    if (!in_array($file, $ignore, true)) {
                        $fullpath = $dir . $file;
                        if (is_dir($fullpath)) {
                            if (count(scandir($fullpath)) == 2) {
                                //rmdir($fullpath);
                                CommonService::getInstance()->log4PHP("rmdir {$fullpath}");
                            } else {
                                $this->delFileByCtime($fullpath, $n, $ignore);
                            }
                        } else {
                            $filedate = filectime($fullpath);
                            $day = round((time() - $filedate) / 86400);
                            if ($day >= $n) {
                                unlink($fullpath);
                            }
                        }
                    }
                }
            }
            closedir($dh);
        }

        return true;
    }
}
