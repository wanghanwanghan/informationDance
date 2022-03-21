<?php

namespace App\HttpController\Business\AdminRoles\User;

use App\HttpController\Business\Api\TaoShu\TaoShuController;
use App\HttpController\Models\AdminNew\AdminNewApi;
use App\HttpController\Models\Provide\BarchChargingLog;
use App\HttpController\Models\Provide\BatchSeachLog;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Models\Provide\RequestUserInfoLog;
use App\HttpController\Models\Provide\RoleInfo;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\User\UserService;
use EasySwoole\Http\Message\UploadFile;
use EasySwoole\Pool\Manager;
use EasySwoole\Utility\File;
use PhpOffice\PhpWord\IOFactory;
use wanghanwanghan\someUtils\control;

class UserController extends UserBase
{


    function onRequest(?string $action): ?bool
    {
        if(!$this->checkRouter()){
            $appId = $this->getRequestData('username') ?? '';
            $token = $this->getRequestData('token') ?? '';
            //dingAlarmSimple(['$appId'=>$appId,'$token'=>$token]);
            if (empty($token) || empty($appId)) return $this->writeJson(201, null, null, '参数不可以为空');
            $info = RequestUserInfo::create()->where("token = '{$token}' and appId = '{$appId}'")->get();
            if (empty($info)) {
                return $this->writeJson(201, null, null, '用户未登录');
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
                'id'=>$item->getAttr('id'),
                'username'=>$item->getAttr('username'),
                'appId'=>$item->getAttr('appId'),
                'appSecret'=>$item->getAttr('appSecret'),
                'rsaPub'=>$item->getAttr('rsaPub'),
                'rsaPri'=>$item->getAttr('rsaPri'),
                'allowIp'=>$item->getAttr('allowIp'),
                'money'=>$item->getAttr('money'),
                'status'=>$item->getAttr('status'),
                'roles'=>$item->getAttr('roles'),
            ];
        }
        return $this->writeJson(200, '', $data, '成功');
    }

    /**
     * 根据appId获取用户信息
     */
    public function getUserInfoByAppId(){
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
    public function editRole(){
        $id = $this->getRequestData('roleId') ?? '';
        $name = $this->getRequestData('roleName') ?? '';
        $status = $this->getRequestData('status') ?? '';
        $info = RoleInfo::create()->where("id = '{$id}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, $name.'不存在');
        }
        $info->update([
            'status' => $status
        ]);
        return $this->writeJson();
    }

    /**
     * 获取所有角色
     */
    public function getRoleList(){
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
            RequestUserInfoLog::create()->addOne($username,$money);
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
            RequestUserInfoLog::create()->addOne($username,$money);
        }

        return $this->writeJson(200);
    }

    /*
     * 获取所有接口
     */
    public function getAllApiList(){
        $res = RequestApiInfo::create()->all();
        return $this->writeJson(200,null,$res);
    }

    /**
     * 获取所有接口和用户的关系和这个用户的接口价格
     */
    public function getUserApiList(){
        $appId = $this->getRequestData('appId') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $shipList = RequestUserApiRelationship::create()->where(" userId = {$info->id}")->all();
        $res = RequestApiInfo::create()->all();
        $res = $this->getArrSetKey($res,'id');
        $shipList = $this->getArrSetKey($res,'apiId');
        foreach ($res as $key => $v){
            if(isset($shipList[$key]) && $shipList[$key]['status'] == 1) {
                $res[$key]['price'] = $shipList[$key]['price'];
                $res[$key]['billing_plan'] = $shipList[$key]['billing_plan'];
                $res[$key]['cache_day'] = $shipList[$key]['cache_day'];
                $res[$key]['own'] = 1;
            }
            $res[$key]['own'] = 2;
        }
        dingAlarmSimple(['$res'=>$res]);
        return $this->writeJson(200,null,$res);
    }

    public function getArrSetKey($data,$key){
        $data = json_decode(json_encode($data),true);
        if(empty($datum)) return [];
        $arr = [];
        foreach ($data as $datum) {
            $arr[$datum[$key]] = $datum;
        }
        return $arr;
    }

    /**
     * 批量导入数据
     */
    public function importData(){
        $appId = $this->getRequestData('username') ?? '';
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        try {
            $files = $this->request()->getUploadedFiles();
            $path = '';
            foreach ($files as $key => $oneFile) {
                if ($oneFile instanceof UploadFile) {
                    try {
                        $path = EASYSWOOLE_ROOT.TEMP_FILE_PATH . $oneFile->getClientFilename();
                        $oneFile->moveTo($path);
                    } catch (\Throwable $e) {
                        return $this->writeErr($e, __FUNCTION__);
                    }
                }
            }
            dingAlarmSimple(['$path'=>$path]);
            $spreadsheet = IOFactory::load($path);
            //读取默认工作表
            $worksheet = $spreadsheet->getSheet(0);
            //取得一共有多少行
            $allRow = $worksheet->getHighestRow();
            $data = [];
            $userId = $info->id;
            $batchNum = control::getUuid();
            for($i = 2; $i <= $allRow; $i++)
            {
                $data[$i]['userId'] = $userId;
                $data[$i]['batchNum'] = $batchNum;
                $data[$i]['entName'] = $spreadsheet->getActiveSheet()->getCell('A'.$i)->getValue();
                $data[$i]['socialCredit'] = $spreadsheet->getActiveSheet()->getCell('B'.$i)->getValue();
//                UserModel::create($data)->save();
            }
            $res = BatchSeachLog::create()->saveAll($data);
            dingAlarmSimple(['$data'=>$data,'BatchSeachLog-$res'=>$res]);
            $this->writeJson(200,null,'导入成功');
        }catch (\Throwable $throwable){
            $this->writeJson(201,null,$throwable->getMessage());
        }
    }

    /**
     * 根据需要导出的类型批次号获取导出的文件
     */
    public function exportBaseInformation(){
        $types = $this->getRequestData('types') ?? '';
        $batchNum = $this->getRequestData('batchNum') ?? '';
        $appId = $this->getRequestData('username') ?? '';
        if(empty($types) || empty($batchNum) || empty($appId)){
            $this->writeJson(201,null,'','部分参数为空，请检查后再次请求');
        }
        $typeArr = explode(',',$types);
        $fileArr = [];
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $emptyTypes = [];
        foreach ($typeArr as $type) {
            $file = $this->searchChargingLog($info->id,$batchNum,$type);
            if(!empty($file)){
                $fileArr[$type] = $file;
            }else{
                $emptyTypes[] = $type;
            }
        }
        if(empty($emptyTypes)){
            $this->writeJson(200,null, $fileArr,'成功');
        }
        $list = BatchSeachLog::create()->where("batchNum = {$batchNum} and userId = {$info->id}")->all();
        $nameArr = [];
        foreach($list as $k=>$v){
            $nameArr[$k]['entName'] = $v->getAttr('entName');
            $nameArr[$k]['socialCredit'] = $v->getAttr('socialCredit');
        }
        foreach ($emptyTypes as $emptyType) {
            $emptyType = explode('-',$emptyType);
            $fun = BarchChargingLog::$type_map[$emptyType['0']][$emptyType['2']];
            list($filePath ,$data)= $this->$fun($nameArr);
            $fileArr[$emptyType] = $filePath;
            $this->inseartChargingLog($info->id,$batchNum,$emptyType,$data,$filePath);
        }
        if(empty($fileArr)){
            $this->writeJson(201,null,'',"没有找到对应类型{$types}的数据信息");
        }
        $this->writeJson(200,null, $fileArr,'成功');
    }

    /**
     * 陶数导出多个公司的基本信息
     */
    public function taoshuRegisterInfo($entNames){
        $fileName = EASYSWOOLE_ROOT.TEMP_FILE_PATH.date('YmdHis',time()).'企业基本信息.csv';
        $insertData = [
            '公司名称', '企业名称', '曾用名','统一社会信用代码','法定代表人','成立日期','经营状态','注册资本','注册资本币种','地址','企业类型',
            '经营业务范围', '登记机关','经营期限自','经营期限至','核准日期','死亡日期','吊销日期','注销日期','地理坐标',
            '行业领域','行业领域代码','省份','组织机构代码','企业英文名','企业官网'
        ];
        file_put_contents($fileName,  implode(',', replace($insertData)). PHP_EOL, FILE_APPEND);
        $data = [];
        foreach ($entNames as $ent) {
            $postData = ['entName' => $ent['entName']];
            $res = (new TaoShuService())->post($postData, 'getRegisterInfo');
            $TaoShuController = new TaoShuController();
            $res = $TaoShuController->checkResponse($res, false);
            dingAlarm('陶数查询企业基本信息异常',['$postData'=>$postData,'$res'=>$res]);
            if (!is_array($res)) continue;

            if ($res['code'] == 200 || !empty($res['result'])) {
                //2018年营业收入区间
                $mysql = CreateConf::getInstance()->getConf('env.mysqlDatabase');
                try {
                    $obj = Manager::getInstance()->get($mysql)->getObj();
                    $obj->queryBuilder()->where('entName', $ent['entName'])->get('qiyeyingshoufanwei');
                    $range = $obj->execBuilder();
                    Manager::getInstance()->get($mysql)->recycleObj($obj);
                } catch (\Throwable $e) {
                    $this->writeErr($e, 'getRegisterInfo');
                    $range = [];
                }

                $vendinc = [];

                foreach ($range as $one) {
                    $vendinc[] = $one;
                }

                !empty($vendinc) ?: $vendinc = '';
                $res['result'][0]['VENDINC'] = $vendinc;
                $re = $res['result']['0'];
                $insertData = [
                    $entName,
                    $re['ENTNAME'],
                    $re['OLDNAME'],
                    $re['SHXYDM'],
                    $re['FRDB'],
                    $re['ESDATE'],
                    $re['ENTSTATUS'],
                    $re['REGCAP'],
                    $re['REGCAPCUR'],
                    $re['DOM'],
                    $re['ENTTYPE'],
                    $re['OPSCOPE'],
                    $re['REGORG'],
                    $re['OPFROM'],
                    $re['OPTO'],
                    $re['APPRDATE'],
                    $re['ENDDATE'],
                    $re['REVDATE'],
                    $re['CANDATE'],
                    $re['JWD'],
                    $re['INDUSTRY'],
                    $re['INDUSTRY_CODE'],
                    $re['PROVINCE'],
                    $re['ORGID'],
                    $re['ENGNAME'],
                    $re['WEBSITE'],
                ];
            file_put_contents($fileName,  implode(',', replace($insertData)). PHP_EOL, FILE_APPEND);
                $data[] = $insertData;
            }
        }
        return [$fileName,$data];
    }

    public function qichachaRegisterInfo($entNames){

    }
    public function taoshuGetOperatingExceptionRota($entNames)
    {
        $fileName = EASYSWOOLE_ROOT.TEMP_FILE_PATH.date('YmdHis',time()).'企业经商异常信息.csv';
        $insertData = [
            '公司名称', '列入经营异常名录原因', '列入日期','作出决定机关（列入）','移出经营异常名录原因','移出日期','作出决定机关（移出）'
        ];
        file_put_contents($fileName,  implode(',', replace($insertData)). PHP_EOL, FILE_APPEND);
        $data = [];
        foreach ($entNames as $ent) {
            $postData = [
                'entName' => $ent['entName'],
            ];
            $res = (new TaoShuService())->post($postData, 'getOperatingExceptionRota');
            foreach ($res['result'] as $re) {
                $insertData = [
                    $ent['entName'],
                    $re['REASONIN'],
                    $re['DATEIN'],
                    $re['REGORGIN'],
                    $re['REASONOUT'],
                    $re['DATEOUT'],
                    $re['REGORGOUT']
                ];
                file_put_contents($fileName,  implode(',', replace($insertData)). PHP_EOL, FILE_APPEND);
                $data[] = $insertData;
            }
        }

        return [$fileName,$data];
    }

    public function qichahchaGetOpException($entNames)
    {
        foreach ($entNames as $ent) {
            $postData = [
                'keyNo' => $ent['entName'],
            ];
            $res = (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'ECIException/GetOpException', $postData);
        }
    }

    public function taoshuGetShareHolderInfo($entNames)
    {
        $fileName = EASYSWOOLE_ROOT.TEMP_FILE_PATH.date('YmdHis',time()).'企业经商异常信息.csv';
        $insertData = [
            '公司名称', '股东名称', '统一社会信用代码','股东类型','认缴出资额','出资币种','出资比例','出资时间'
        ];
        file_put_contents($fileName,  implode(',', replace($insertData)). PHP_EOL, FILE_APPEND);
        $data = [];
        foreach ($entNames as $ent) {
            $entName = $ent['entName'];
            list($data1,$totalPage) = $this->getShareHolderInfo($entName,1);
            if($totalPage>1){
                for ($i=2;$i<=$totalPage;$i++){
                    list($data2,$totalPage2) = $this->getShareHolderInfo($entName,$i);
                    $data1 = array_merge($data2,$data1);
                }
            }
            foreach ($data1 as $re) {
                $insertData = [
                    $entName,
                    $re['INV'],
                    $re['SHXYDM'],
                    $re['INVTYPE'],
                    $re['SUBCONAM'],
                    $re['CONCUR'],
                    $re['CONRATIO'],
                    $re['CONDATE'],
                ];
                file_put_contents($fileName, implode(',', replace($insertData)) . PHP_EOL, FILE_APPEND);
                $data[] = $insertData;
            }
        }
        return [$fileName,$data];
    }

    public function getShareHolderInfo($entName,$pageNo = 1){
        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => 100,
        ];

        $res = (new TaoShuService())->post($postData, 'getShareHolderInfo');
        $TaoShuController = new TaoShuController();
        $res = $TaoShuController->checkResponse($res, false);
        if (!is_array($res)) return [];
        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                $one['CONRATIO'] = formatPercent($one['CONRATIO']);
            }
            unset($one);
        }
        return [$res['result'],$res['paging']['totalPage']];
    }
    /**
     * 查询这个用户对应批次，对应数据类型，以往的查询记录
     */
    public function searchChargingLog($user_id,$batchNum,$type){
        $startTime = strtotime('-3 day');
        $log = BarchChargingLog::create()->where("userId = {$user_id} and batchNum = {$batchNum} and type = {$type} and created_at>{$startTime} order by created_at desc limit 1")->get();
        if(empty($log)){
            return '';
        }
        return $log->file_path;
    }

    /**
     * 添加计费的查询记录
     */
    public function inseartChargingLog($user_id,$batchNum,$type,$data,$file){
        BarchChargingLog::create([
            'type' => $type,
            'ret' => $data,
            'userId' => $user_id,
            'batchNum' => $batchNum,
            'file_path' => $file
        ])->save();
        return true;
    }
}