<?php

namespace App\HttpController\Business\AdminRoles\User;

use App\HttpController\Business\AdminRoles\Export\BusinessController;
use App\HttpController\Business\AdminRoles\Export\FinanceContorller;
use App\HttpController\Business\AdminRoles\Export\FinancialRegulationController;
use App\HttpController\Business\AdminRoles\Export\IntellectualPropertyContorller;
use App\HttpController\Business\AdminRoles\Export\SheshuiContorller;
use App\HttpController\Business\AdminRoles\Export\SifaContorller;
use App\HttpController\Business\Api\TaoShu\TaoShuController;
use App\HttpController\Models\AdminNew\AdminNewApi;
use App\HttpController\Models\Provide\BarchChargingLog;
use App\HttpController\Models\Provide\BarchTypeApiRelation;
use App\HttpController\Models\Provide\BatchSeachLog;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Models\Provide\RequestUserInfoLog;
use App\HttpController\Models\Provide\RoleInfo;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\User\UserService;
use Carbon\Carbon;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\Pool\Manager;
use EasySwoole\Utility\File;
use PhpOffice\PhpWord\IOFactory;
use wanghanwanghan\someUtils\control;

class UserController extends UserBase
{


    function onRequest(?string $action): ?bool
    {
        if (!$this->checkRouter()) {
            $appId = $this->getRequestData('username') ?? '';
            $token = $this->getRequestData('token') ?? '';
            //dingAlarmSimple(['$appId'=>$appId,'$token'=>$token]);
            if (empty($token) || empty($appId)) return $this->writeJson(201, null, null, '参数不可以为空');
            $info = RequestUserInfo::create()->where("token = '{$token}' and appId = '{$appId}'")->get();
            if (empty($info)) {
                 $this->writeJson(201, null, null, '用户未登录');
                 return false;
            }
        }
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //check router
    private function checkRouter(): bool
    {
        //直接放行的url，只判断url最后两个在不在数组中
        $pass = CreateConf::getInstance()->getConf('env.passRouter');
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


    /**
     * 用户登录
     */
    function userLogin()
    {
        $appId = $this->getRequestData('username') ?? '';
        $password = $this->getRequestData('password') ?? '';
        if (empty($appId) || empty($password)) return $this->writeJson(201, null, null, '登录信息错误');
        $info = RequestUserInfo::create()->where("appId = '{$appId}' and password = '{$password}'")->get();
        if (empty($info)) {
            return $this->writeJson(201, null, null, '账号密码错误');
        } else {
            $newToken = UserService::getInstance()->createAccessToken($info->appId, $info->password);
            $info->update(['token' => $newToken]);
            $data = [
                'token' => $newToken,
                'username' => $info->username,
                'money' => $info->money,
                'roles' => $info->roles,
                'id' => $info->id
            ];
            return $this->writeJson(200, '', $data, '登录成功');
        }
    }

    /**
     * 根据token 获取用户明细
     */
    function getInfoByToken()
    {
        $token = $this->getRequestData('token') ?? '';
        if (empty($token)) return $this->writeJson(201, null, null, 'token不可以为空');
        $info = RequestUserInfo::create()->where("token = '{$token}'")->get();
        if (empty($info)) {
            return $this->writeJson(201, null, null, 'token不存在');
        }
        $data = [
            'username' => $info->username,
            'money' => $info->money,
            'roles' => $info->roles,
            'id' => $info->id
        ];
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 根据用户获取用户的接口明细
     */
    function getApiListByUser()
    {
        $appId = $this->getRequestData('appId') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $shipList = RequestUserApiRelationship::create()->where(" userId = {$info->id}")->all();
        $data = [];
        foreach ($shipList as $item) {
            $apiId = $item->getAttr('apiId');
            $apiInfo = RequestApiInfo::create()->where("id={$apiId}")->get();
            $data[$apiId] = [
                'path' => $apiInfo->path,
                'name' => $apiInfo->name,
                'desc' => $apiInfo->desc,
                'source' => $apiInfo->source,
                'price' => $item->price,
                'status' => $apiInfo->status,
                'apiDoc' => $apiInfo->apiDoc,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'billing_plan' => $item->billing_plan,
                'cache_day' => $item->cache_day,
            ];
        }
        return $this->writeJson(200, '', array_values($data), '成功');
    }

    /**
     * 修改接口详情
     */
    function editApi()
    {
        $aid = $this->getRequestData('aid');
        $path = $this->getRequestData('path');
        $name = $this->getRequestData('name');
        $desc = $this->getRequestData('desc');
        $price = $this->getRequestData('price');
        $status = $this->getRequestData('status');
        $apiDoc = $this->getRequestData('apiDoc');
        $sort_num = $this->getRequestData('sort_num');
        $source = $this->getRequestData('source');

        $info = RequestApiInfo::create()->where('id', $aid)->get();
        $infoAdmin = AdminNewApi::create()->where('path', $path)->get();

        $update = [];
        $updateAdmin = [];
        empty($sort_num) ?: $updateAdmin['sort_num'] = $sort_num;
        empty($path) ?: $update['path'] = $path;
        $updateAdmin['path'] = $path;
        empty($name) ?: $update['name'] = $name;
        $updateAdmin['name'] = $name;
        empty($desc) ?: $update['desc'] = $desc;
        $updateAdmin['desc'] = $desc;
        empty($source) ?: $update['source'] = $source;
        $updateAdmin['source'] = $source;
        empty($price) ?: $update['price'] = sprintf('%3.f', $price);
        $status === '启用' ? $update['status'] = 1 : $update['status'] = 0;
        empty($apiDoc) ?: $update['apiDoc'] = $apiDoc;
        if (empty($infoAdmin)) {
            AdminNewApi::create()->data([
                'path' => $path,
                'api_name' => $name,
                'desc' => $desc,
                'source' => $source,
                'price' => $price,
                'sort_num' => $sort_num,
            ])->save();
        } else {
            $infoAdmin->update($updateAdmin);
        }
        $info->update($update);

        return $this->writeJson();
    }

    /**
     * 修改user和api的关系
     */
    function editUserApi()
    {
        $uid = $this->getRequestData('uid');
        $apiInfo = $this->getRequestData('apiInfo');
        if (empty($uid)) return $this->writeJson(201);
        //先将这个用户的所有接口改为不可用
        RequestUserApiRelationship::create()->where('userId', $uid)->update([
            'status' => 0
        ]);
        //再将可用的接口改为可用
        foreach ($apiInfo as $one) {
            $check = RequestUserApiRelationship::create()->where('userId', $uid)->where('apiId', $one['id'])->get();
            if (empty($check)) {
                RequestUserApiRelationship::create()->data([
                    'userId' => $uid,
                    'apiId' => $one['id'],
                    'price' => $one['price'] + 0.2,
                    'billing_plan' => $one['billing_plan'],
                    'cache_day' => $one['cache_day'],
                    'kidTypes' => $one['kidTypes'],
                    'year_price_detail' => $one['year_price_detail']
                ])->save();
            } else {
                $check->update([
                    'status' => 1
                ]);
            }
        }
        return $this->writeJson();
    }

    /**
     * 获取用户列表
     */
    public function getUserList()
    {
        $resList = RequestUserInfo::create()->all();
        $data = [];
        foreach ($resList as $item) {
            $data[] = [
                'id' => $item->getAttr('id'),
                'username' => $item->getAttr('username'),
                'appId' => $item->getAttr('appId'),
                'appSecret' => $item->getAttr('appSecret'),
                'rsaPub' => $item->getAttr('rsaPub'),
                'rsaPri' => $item->getAttr('rsaPri'),
                'allowIp' => $item->getAttr('allowIp'),
                'money' => $item->getAttr('money'),
                'status' => $item->getAttr('status'),
                'roles' => $item->getAttr('roles'),
            ];
        }
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 根据appId获取用户信息
     */
    public function getUserInfoByAppId()
    {
        $appId = $this->getRequestData('username') ?? '';
        if (empty($appId)) return $this->writeJson(201, null, null, 'username不可以为空');
        $info = RequestUserInfo::create()->where("appId = '{$appId}'")->get();
        if (empty($info)) {
            return $this->writeJson(201, null, null, 'token不存在');
        }
        $data = [
            'username' => $info->username,
            'money' => $info->money,
            'roles' => $info->roles,
            'id' => $info->id,
            'appId' => $info->appId,
            'appSecret' => $info->appSecret,
            'rsaPub' => $info->rsaPub,
            'rsaPri' => $info->rsaPri,
            'allowIp' => $info->allowIp,
            'status' => $info->status,
        ];
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 修改角色
     */
    public function editRole()
    {
        $id = $this->getRequestData('roleId') ?? '';
        $name = $this->getRequestData('roleName') ?? '';
        $status = $this->getRequestData('status') ?? '';
        $info = RoleInfo::create()->where("id = '{$id}'")->get();
        if (empty($info)) {
            return $this->writeJson(201, null, null, $name . '不存在');
        }
        $info->update([
            'status' => $status
        ]);
        return $this->writeJson();
    }

    /**
     * 获取所有角色
     */
    public function getRoleList()
    {
        $list = RoleInfo::create()->all();
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'id' => $item->getAttr('id'),
                'name' => $item->getAttr('name'),
                'status' => $item->getAttr('status'),
            ];
        }
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 添加用户,修改用户信息
     */
    function addUser()
    {
        $actionType = $this->getRequestData('actionType');
        $username = $this->getRequestData('username');
        $money = $this->getRequestData('money');
        $roles = $this->getRequestData('roles');

        if (empty($username) || empty($money)) return $this->writeJson(201);

        $check = RequestUserInfo::create()->where('username', $username)->get();

        if ($actionType === 'update') {
            if (empty($check)) return $this->writeJson(201);
            $check->update([
                'username' => $username,
                'money' => $money + $check->getAttr('money'),
                'roles' => $roles,
            ]);
            RequestUserInfoLog::create()->addOne($username, $money);
        } else {
            if (!empty($check)) return $this->writeJson(201);
            $appId = strtoupper(control::getUuid());
            $appSecret = substr(strtoupper(control::getUuid()), 5, 20);
            RequestUserInfo::create()->data([
                'username' => $username,
                'appId' => $appId,
                'appSecret' => $appSecret,
                'money' => $money,
                'roles' => $roles
            ])->save();
            RequestUserInfoLog::create()->addOne($username, $money);
        }

        return $this->writeJson(200);
    }

    /*
     * 获取所有接口
     */
    public function getAllApiList()
    {
        $res = RequestApiInfo::create()->all();
        return $this->writeJson(200, null, $res);
    }

    /**
     * 获取所有接口和用户的关系和这个用户的接口价格
     */
    public function getUserApiList()
    {
        $appId = $this->getRequestData('appId') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, [],'没有查询到这个用户的相关信息');
        }
        $shipList = RequestUserApiRelationship::create()->where(" userId = {$info->id}")->all();
        $res = RequestApiInfo::create()->all();
        $res = $this->getArrSetKey($res, 'id');
        $shipList = $this->getArrSetKey($shipList, 'apiId');
        foreach ($res as $key => $v) {
            if (isset($shipList[$key]) && $shipList[$key]['status'] == 1) {
                $res[$key]['price'] = $shipList[$key]['price'];
                $res[$key]['billing_plan'] = $shipList[$key]['billing_plan'];
                $res[$key]['cache_day'] = $shipList[$key]['cache_day'];
                $res[$key]['kidTypes'] = $shipList[$key]['kidTypes'];
                $res[$key]['kidTypes'] = $shipList[$key]['kidTypes'];
                $res[$key]['relationshipId'] = $shipList[$key]['id'];
                $res[$key]['year_price_detail'] = $shipList[$key]['year_price_detail'];
                $res[$key]['price_type'] = $shipList[$key]['price_type'];
                $res[$key]['ent_price_detail'] = $shipList[$key]['ent_price_detail'];
                $res[$key]['own'] = 1;
            }else{
                $res[$key]['own'] = 2;
            }

        }
//        dingAlarmSimple(['$res' => json_encode($res)]);
        return $this->writeJson(200, null, array_values($res));
    }

    public function getArrSetKey($data, $key)
    {
        $data = json_decode(json_encode($data), true);
//        dingAlarmSimple(['getArrSetKey' => json_encode($data)]);
        if (empty($data)) return [];
        $arr = [];
        foreach ($data as $datum) {
            $arr[$datum[$key]] = $datum;
        }
        return $arr;
    }

    /**
     * 批量导入数据
     */
    public function importData()
    {
        $appId = $this->getRequestData('username') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        try {
            $files = $this->request()->getUploadedFiles();
            $path = $fileName = '';
            foreach ($files as $key => $oneFile) {
                if ($oneFile instanceof UploadFile) {
                    try {
                        $fileName = $oneFile->getClientFilename();
                        $path = TEMP_FILE_PATH . $fileName;
                        $oneFile->moveTo($path);
                    } catch (\Throwable $e) {
                        return $this->writeErr($e, __FUNCTION__);
                    }
                }
            }
            $config = [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];
            $excel_read = new \Vtiful\Kernel\Excel($config);
            $read = $excel_read->openFile($fileName)->openSheet();
            $excel_read->nextRow([]);
            $data = [];
            $i = 0;
            $batchNum = control::getUuid();
            while ($one = $excel_read->nextRow([])) {
                $data[$i]['userId'] = $info->id;
                $data[$i]['batchNum'] = $batchNum;
                $data[$i]['entName'] = $one['0'] ?? '';
                $data[$i]['socialCredit'] = $one['1'] ?? '';
                $i++;
            }
            $res = BatchSeachLog::create()->saveAll($data);
            return $this->writeJson(200, null, $batchNum,'导入成功');
        } catch (\Throwable $throwable) {
//            dingAlarmSimple(['error' => $throwable->getMessage()]);
            return $this->writeJson(201, null, $throwable->getMessage());
        }
    }

    /**
     * 获取这个用户下的导入的批次号list
     */
    public function getBatchNumList(){
        $pageNo = $this->getRequestData('pageNO') ?? '';
        $pageSize = $this->getRequestData('pageSize') ?? '';
        $appId = $this->getRequestData('username') ?? '';
        $createdAt = $this->getRequestData('created_at')?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $pageSize = empty($pageSize)?10:$pageSize;
        $pageNo = empty($pageNo)?1:$pageNo;
        $limit = ($pageNo-1)*$pageSize;
        $whereStr = '';
        if (!empty($createdAt)) {
            $tmp = explode('|||', $createdAt);
            $date1 = strtotime($tmp['0']);
            $date2 = strtotime($tmp['1']);
            $whereStr .= ' and created_at between '.$date1.' and '.$date2 ;
        }
        $countSql = <<<Eof
SELECT count(DISTINCT ( batchNum ))as num FROM information_dance_batch_seach_log where userId = {$info->id} {$whereStr}
Eof;
        $count = sqlRaw($countSql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $dataSql = <<<Eof
SELECT DISTINCT ( batchNum ) FROM information_dance_batch_seach_log where userId = {$info->id} {$whereStr} order by id desc LIMIT {$limit},{$pageSize} 
Eof;
        $list = sqlRaw($dataSql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $batchNums = array_column($list,'batchNum');
        $batchNumsStr = "'".implode("','",$batchNums)."'";
        $sql = <<<Eof
SELECT
	batchNum,
	count( entName ) as entCount,
	created_at 
FROM
	information_dance_batch_seach_log where batchNum in({$batchNumsStr} )
GROUP BY
	batchNum  order by id desc
Eof;
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $paging = [
            'page' => $pageNo,
            'pageSize' => $pageSize,
            'total' => $count['0']['num'],
            'totalPage' => (int)($count['0']['num']/$pageSize)+1,
        ];
        return $this->writeJson(200, $paging, $list,'成功');
    }

    /**
     * 获取这个批次号导出的所有数据信息
     */
    public function getBatchNumDetail(){
        $batchNum = $this->getRequestData('batchNum') ?? '';
        $appId = $this->getRequestData('username') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $list = BarchChargingLog::create()->where("userId = {$info->id} and batchNum = '{$batchNum}'")->all();
        $data = [];
        foreach ($list as $item) {
            $data[] = [
                'type' => $item->getAttr('type'),
                'file_path' => $item->getAttr('file_path'),
                'created_at' => $item->getAttr('created_at'),
            ];
        }
        return $this->writeJson(200, null, $data,'成功');
    }

    /**
     * 根据需要导出的类型批次号获取导出的文件
     */
    public function exportBaseInformation()
    {
        $types = $this->getRequestData('types') ?? '';
        $batchNum = $this->getRequestData('batchNum') ?? '';
        $appId = $this->getRequestData('username') ?? '';
        if (empty($types) || empty($batchNum) || empty($appId)) {
//            dingAlarm('部分参数为空',['$types'=>$types,'$batchNum'=>$batchNum,'$appId'=>$appId]);
            return $this->writeJson(201, null, '', '部分参数为空，请检查后再次请求');
        }
        $typeArr = json_decode($types,true);
        $fileArr = [];
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $emptyTypes = [];
        $kidTypes = '';
        foreach ($typeArr as $type) {
            if($type == 15 || $type == 13){
                $barchTypeApiRelationInfo = BarchTypeApiRelation::create()->where('id', $type)->get();
                $requestUserApiRelationship = RequestUserApiRelationship::create()->where("userId = {$info->id} and apiId = {$barchTypeApiRelationInfo->apiId}")->get();
                $kidTypes = $requestUserApiRelationship->kidTypes;
            }
            $file = $this->searchChargingLog($info->id, $batchNum, $type,$kidTypes);
            if (!empty($file)) {
                $fileArr[$type] = $file;
            } else {
                $emptyTypes[] = $type;
            }
        }
        //表示需要查询的数据已经在数据库中查询过了，只需要返回数据库中的文件即可
        if (empty($emptyTypes)) {
            return $this->writeJson(200, null, $fileArr, '成功');
        }

        $list = BatchSeachLog::create()->where("batchNum = '{$batchNum}' and userId = {$info->id}")->all();
        $nameArr = [];
        foreach ($list as $k => $v) {
            $nameArr[$k]['entName'] = $v->getAttr('entName');
            $nameArr[$k]['socialCredit'] = $v->getAttr('socialCredit');
        }
        $dataFinanceSmhz = [];
        foreach ($emptyTypes as $emptyType) {
            $barchTypeApiRelationInfo = BarchTypeApiRelation::create()->where('id', $emptyType)->get();
            if(empty($barchTypeApiRelationInfo))continue;
            $requestUserApiRelationship = RequestUserApiRelationship::create()->where("userId = {$info->id} and apiId = {$barchTypeApiRelationInfo->apiId}")->get();
            $fun = $barchTypeApiRelationInfo->fun;
            switch ($barchTypeApiRelationInfo->typeBase){
                case 1:
                    $BusinessController = new BusinessController();
                    list($filePath, $data) = $BusinessController->{$fun}($nameArr);
                    break;
                case 2:
                    $FinanceContorller = new FinanceContorller();
                    list($filePath, $data) = $FinanceContorller->{$fun}($nameArr,$requestUserApiRelationship,$info->appId,$batchNum);
                    break;
                case 3:
                    $SifaContorller = new SifaContorller();
                    list($filePath, $data) = $SifaContorller->{$fun}($nameArr);
                    break;
                case 4:
                    $SheshuiContorller = new SheshuiContorller();
                    list($filePath, $data) = $SheshuiContorller->{$fun}($nameArr);
                    break;
                case 5:
                    list($filePath, $data) = (new IntellectualPropertyContorller())->{$fun}($nameArr);
                    break;
                case 6:
                    list($filePath, $data) = (new FinancialRegulationController())->{$fun}($nameArr);
                    break;
            }
//            dingAlarm('导出数据返回', ['$filePath' => $filePath]);
            $fileArr[$emptyType] = $filePath;
            $this->inseartChargingLog($info->id, $batchNum, $emptyType,$kidTypes, $data, $filePath);
            if(!empty($data['2'])){
                $dataFinanceSmhz = array_merge($dataFinanceSmhz,$data['2']);
            }
        }
        if(in_array(15,$emptyTypes) && !empty($fileArr)){
            return $this->writeJson(200, null, ['file'=>$fileArr,'data'=>$dataFinanceSmhz], '成功');
        }
        if (empty($fileArr)) {
            return $this->writeJson(201, null, '', "没有找到对应类型{$types}的数据信息");
        }else{
            return $this->writeJson(200, null, $fileArr, '成功');
        }
    }

    /**
     * 查询这个用户对应批次，对应数据类型，以往的查询记录
     */
    public function searchChargingLog($user_id, $batchNum, $type,$kidTypes = '',$status=1)
    {
        $startTime = strtotime('-3 day');
        $sql = "userId = '{$user_id}' and batchNum = '{$batchNum}' and type = '{$type}' and created_at>{$startTime}";
        if(!empty($kidTypes))//财务
        {
            $sql .= " and kidTypes = '{$kidTypes}'";
        }
//        dingAlarm('searchChargingLog',['$sql'=>$sql]);
        $log = BarchChargingLog::create()->where($sql." and status = {$status} order by created_at desc ")->get();
        if (empty($log)) {
            return '';
        }
        return $log->file_path;
    }

    /**
     * 添加计费的查询记录
     */
    public function inseartChargingLog($user_id, $batchNum, $type, $kidTypes,$data, $file,$status = 1)
    {
        BarchChargingLog::create()->data([
            'type' => $type,
            'ret' => json_encode($data),
            'userId' => $user_id,
            'batchNum' => $batchNum,
            'file_path' => is_array($file)?json_encode($file):$file,
            'kidTypes' => $kidTypes,
            'status' => $status
        ])->save();
        return true;
    }

    public function replace($arr)
    {
        foreach ($arr as &$item) {
            $item = str_replace(',', '  ', $item);
            $item = str_replace("\n", '  ', $item);
            $item = str_replace("\r", '  ', $item);
        }
        return $arr;
    }


    /**
     * 获取这个用户可以导出的接口类型和接口名称
     */
    public function getTypeMapByAPPId(){
        $appId = $this->getRequestData('username') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $listRelation = RequestUserApiRelationship::create()->where("userId = {$info->id} and status = 1")->all();
        if(empty($listRelation)){
            return $this->writeJson(201, null, '', "这个用户没有开通接口");
        }
        $apiIds = [];
        $kidTypes = [];
        foreach ($listRelation as $item) {
            $apiIds[$item->getAttr('apiId')] = $item->getAttr('apiId');
            $kidTypes[$item->getAttr('apiId')] = $item->getAttr('kidTypes');
        }
        $listTypeApiRelation =  BarchTypeApiRelation::create()->where("apiId in (".implode(',',$apiIds).")")->all();
        $data = [];
        foreach ($listTypeApiRelation as $item) {
            if($item->getAttr('id') == 15) continue;
            $data[] = [
                'typeBase' => $item->getAttr('typeBase'),
                'type' => $item->getAttr('id'),
                'name' => $item->getAttr('name'),
                'kidTypes' => $kidTypes[$item->getAttr('apiId')]
            ];
        }
        return $this->writeJson(200, null, $data, "成功");
    }

    /**
     * 获取所有可以导出的类型
     */
    public function getAllTypeMap(){
        $list = BarchTypeApiRelation::create()->all();
        return $this->writeJson(
            200,
            null,
            [
                'list'=>$list,
                'tripartite'=>BarchTypeApiRelation::TRIPARTITE_MAP,
                'typeBase'=>BarchTypeApiRelation::TYPE_BASE_MAP
            ],
            "成功");
    }

    /**
     * 添加或修改批次导出的类型
     */
    public function addBatchType(){
        $apiId = $this->getRequestData('apiId');
        $typeSanfang = $this->getRequestData('typeSanfang');
        $typeBase = $this->getRequestData('typeBase');
        $name = $this->getRequestData('name');
        $remarks = $this->getRequestData('remarks');
        $fun = $this->getRequestData('fun');
        $info = BarchTypeApiRelation::create()->where('apiId', $apiId)->get();
        $update = [
            'apiId' => $apiId,
            'typeSanfang' => $typeSanfang,
            'typeBase' => $typeBase,
            'name' => $name,
            'remarks' => $remarks,
            'fun' => $fun,
        ];
        if (empty($info)) {
            BarchTypeApiRelation::create()->data($update)->save();
        } else {
            $info->update($update);
        }
        return $this->writeJson();
    }

    public function editApiUserRelation(){
        $relationshipId = $this->getRequestData('relationshipId');
        $cache_day = $this->getRequestData('cache_day');
        $billing_plan = $this->getRequestData('billing_plan');
        $kidTypes = $this->getRequestData('kidTypes');
        $priceType = $this->getRequestData('priceType');
        $ent_price_detail = $this->getRequestData('ent_price_detail');
        $year_price_detail = $this->getRequestData('year_price_detail');
        if(empty($relationshipId)){
            return $this->writeJson(201, null, '', "关系ID不可以为空");
        }
        $info = RequestUserApiRelationship::create()->where("id = ".$relationshipId)->get();
        if(empty($info)){
            return $this->writeJson(201, null, '', "根据关系ID没有查到数据");
        }
        $update = [];
        if(!empty($cache_day)){
            $update['cache_day'] = $cache_day;
        }
        if(!empty($billing_plan)){
            $update['billing_plan'] = $billing_plan;
        }
        if(!empty($kidTypes)){
            $update['kidTypes'] = $kidTypes;
        }
        if(!empty($year_price_detail)){
            $update['year_price_detail'] = $year_price_detail;
        }
        if(!empty($ent_price_detail)){
            $update['ent_price_detail'] = $ent_price_detail;
        }
        if(!empty($priceType)){
            $update['price_type'] = $priceType;
        }
        $info->update($update);
        return $this->writeJson();
    }

    /**
     * 导出非正常财务数据
     */
    public function getAbnormalFinance(){
        $appId = $this->getRequestData('appId');
        $ids = $this->getRequestData('ids');
        $batchNum = $this->getRequestData('batchNum');
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        if(empty($ids)){
            return $this->writeJson(201, null, '', "没有查到数据");
        }
        $FinanceContorller = new FinanceContorller();

        $file = $this->searchChargingLog($info->id, $batchNum, 15,implode(',',json_decode($ids,true)),2);
        if(!empty($file)){
            return $this->writeJson(200,[],$file,'成功');
        }
        $file = $FinanceContorller->getSmhzAbnormalFinance(json_decode($ids,true),$appId,$batchNum,$info->id);
        return $this->writeJson(200,[],$file,'成功');
    }

    /*
     * 获取赛博绘制年报的批次号
     */
    public function getFBatchNumList(){
        $pageNo = $this->getRequestData('pageNO') ?? '';
        $pageSize = $this->getRequestData('pageSize') ?? '';
        $appId = $this->getRequestData('username') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $pageSize = empty($pageSize)?10:$pageSize;
        $pageNo = empty($pageNo)?1:$pageNo;
        $limit = ($pageNo-1)*$pageSize;

        $countSql = <<<Eof
SELECT count(DISTINCT ( batchNum ))as num FROM information_dance_barch_charging_log where userId = {$info->id}  and  type= 15
Eof;
        $count = sqlRaw($countSql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $dataSql = <<<Eof
SELECT DISTINCT ( batchNum ) FROM information_dance_barch_charging_log where userId = {$info->id}   and  type= 15 order by id desc LIMIT {$limit},{$pageSize} 
Eof;
        $list = sqlRaw($dataSql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $batchNums = array_column($list,'batchNum');
        $batchNumsStr = "'".implode("','",$batchNums)."'";
        $sql = <<<Eof
SELECT
	batchNum,
	count( entName ) as entCount,
	created_at 
FROM
	information_dance_batch_seach_log where batchNum in({$batchNumsStr} )
GROUP BY
	batchNum  order by id desc
Eof;
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        $dataSql = <<<Eof
SELECT ret,batchNum FROM information_dance_barch_charging_log where batchNum in({$batchNumsStr} )
Eof;
        $charging_log_list = sqlRaw($dataSql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $retMap = [];
        $abnormal = [];
        $normal = [];
        foreach ($charging_log_list as $item) {
            $ret = json_decode($item['ret'],true);
            foreach ($ret['1'] as $item1) {
                $normal[$item['batchNum']][$item1['entName']] = $item1['entName'];
            }
            foreach ($ret['2'] as $item2) {
                $abnormal[$item['batchNum']][$item2['entName']] = $item2['entName'];
            }

        }
        $list = json_decode(json_encode($list),true);
        foreach ($list as $k=>$v){
            $list[$k]['data_detail']['abnormal'] = count($abnormal[$v['batchNum']]);
            $list[$k]['data_detail']['normal'] = count($normal[$v['batchNum']]);
        }
        $paging = [
            'page' => $pageNo,
            'pageSize' => $pageSize,
            'total' => $count['0']['num'],
            'totalPage' => (int)($count['0']['num']/$pageSize)+1,
        ];
        return $this->writeJson(200, $paging, $list,'成功');
    }

    /**
     * 获取这个年报批次号的异常数据
     */
    public function getFAbnormalData(){
        $batchNum = $this->getRequestData('batchNum');
        $appId = $this->getRequestData('username');
        if(empty($appId) || empty($batchNum)){
            return $this->writeJson(201, null, '', "没有查到数据");
        }
        $info = RequestUserInfo::create()->where(" appId = '{$appId}' ")->get();
        $logInfo = BarchChargingLog::create()->where("type = 15 and userId = {$info->id} and batchNum = '{$batchNum}'")->get();
        if(empty($logInfo)){
            return $this->writeJson(201, null, '', "没有查到数据");
        }
        $ret = json_decode($logInfo->ret,true);
        return $this->writeJson(200, '', $ret['2']??[],'成功');
    }

    public function getFAbnormalDataText(){
        $batchNum = $this->getRequestData('batchNum');
        $appId = $this->getRequestData('username');
        if(empty($appId) || empty($batchNum)){
            return $this->writeJson(201, null, '', "没有查到数据");
        }

        $file = (new FinanceContorller())->getAbnormalDataText($batchNum,$appId);
        return $this->writeJson(200, '', $file,'成功');
    }

    public function getFinanceChargeLogByUser(){
        $appId = $this->getRequestData('username');
        $pageSize = empty($this->getRequestData('pageSize'))?10:$this->getRequestData('pageSize');
        $info = RequestUserInfo::create()->where(" appId = '{$appId}' ")->get();
        $param = [
            'userId' => $info->id,
            'batchNum' => $this->getRequestData('batchNum'),
            'pageSize' => $pageSize,
            'pageNo' => $this->getRequestData('pageNo'),
            'status' => $this->getRequestData('status'),
            'created_at' => $this->getRequestData('created_at'),
        ];
        list($list,$count) = (new FinanceContorller())->getFinanceChargeLog($param);

        $paging = [
            'page' => $this->getRequestData('pageNo')??1,
            'pageSize' => $pageSize,
            'total' => $count,
            'totalPage' => (int)($count/$pageSize)+1,
        ];
        return $this->writeJson(200, $paging, $list,'成功');
    }

    public function actionRefund(){
        $appId = $this->getRequestData('username');
        $id = $this->getRequestData('id');
        if(empty($id)){
            return $this->writeJson(201, null, '', "参数为空");
        }
        $res = (new FinanceContorller())->refund($id,$appId);
        if(!$res){
            return $this->writeJson(201, null, '', "没有查询到计费日志");
        }
        return $this->writeJson(200, '', $res,'成功');
    }
}