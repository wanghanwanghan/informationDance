<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceChargeLog;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceData;
use App\HttpController\Models\Provide\BarchChargingLog;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\LongXin\LongXinService;
use Carbon\Carbon;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;

class FinanceContorller extends UserController
{
    public $kidTpye = [
        'ASSGRO'    => '资产总额',
        'LIAGRO'    => '负债总额',
        'VENDINC'   => '营业总收入',
        'MAIBUSINC' => '主营业务收入',
        'PROGRO'    => '利润总额',
        'NETINC'    => '净利润',
        'RATGRO'    => '纳税总额',
        'TOTEQU'    => '所有者权益合计',
        'SOCNUM'    => '社保人数',
    ];
    const PRICE_TYPE_1 = 1;//按年收费
    const PRICE_TYPE_2 = 2;//按公司收费

    /**
     * 西南年报
     * 2018-3/ASSGRO,LIAGRO,VENDINC,MAIBUSINC,PROGRO,NETINC,RATGRO,TOTEQU,SOCNUM
     */
    public function xinanGetFinanceNotAuth($entNames, $relation, $appId, $batchNum)
    {
//        dingAlarm('xinanGetFinanceNotAuth_tou',['$entNames'=>$entNames]);
        $kidTypes       = explode('|', $relation->kidTypes);
        $kidTypeList    = explode('-', $kidTypes['0']);
        $kidTypesKeyArr = explode(',', $kidTypes['1']);
        $fileName       = date('YmdHis', time()) . '年报.csv';
        $file           = TEMP_FILE_PATH . $fileName;
        $insertData     = ['公司名称', '年'];
        foreach ($kidTypesKeyArr as $item) {
            $insertData[] = $this->kidTpye[$item];
        }
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $postData = [
                'entName'   => $ent['entName'],
                'code'      => $ent['socialCredit'],
                'beginYear' => $kidTypeList['0'],
                'dataCount' => $kidTypeList['1'],//取最近几年的
            ];
            $res      = (new LongXinService())->getFinanceData($postData, false);
            if (empty($res['data'])) {
                $insertStr = $ent['entName'] . ',' . $kidTypeList['0'];
                for ($i = 1; $i < count($kidTypesKeyArr); $i++) {
                    $insertStr .= ',0';
                }
                file_put_contents($file, $insertStr . PHP_EOL, FILE_APPEND);
                continue;
            }
            foreach ($res['data'] as $year => $datum) {
                $insertData = [
                    $ent['entName'],
                    $year,
                ];
                foreach ($kidTypesKeyArr as $item) {
                    $insertData[] = $datum[$item] ?? '0';
                }
                $resData[] = $insertData;
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
            }
        }
        return [$fileName, $resData];
    }

    public function smhzGetFinanceOriginal($entNames, $relation, $appId, $batchNum)
    {
        $kidTypes          = explode('|', $relation->kidTypes);
        $year_price_detail = getArrByKey(json_decode($relation->year_price_detail), 'year');
        $ent_price_detail  = $relation->ent_price_detail;
        $price_type        = $relation->price_type;
        $kidTypeList       = explode('-', $kidTypes['0']);
        $year              = $kidTypeList['0'];
        $yearCount         = $kidTypeList['1'];
        $kidTypesKeyArr    = explode(',', $kidTypes['1']);
        $fileName          = date('YmdHis', time()) . '年报.csv';
        $file              = TEMP_FILE_PATH . $fileName;
        $insertData        = ['公司名称', '年'];
        foreach ($kidTypesKeyArr as $item) {
            $insertData[] = $this->kidTpye[$item];
        }
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $res          = $this->getFinanceOriginalData($ent['entName'], $yearCount, $year);
            $inserDataArr = [];
            $flag         = false;
            if (!empty($res)) {
                foreach ($res as $vYear => $datum) {
                    if (empty($datum) || ($price_type == self::PRICE_TYPE_1 && !isset($year_price_detail[$vYear]['cond']))) {
                        $insertDataEmpty = [
                            'entName' => $ent['entName'],
                            'year'    => $vYear,
                            'id'      => '',
                        ];
                        foreach ($kidTypesKeyArr as $item) {
                            $insertDataEmpty[$item] = '0';
                        }
                        $inserDataArr[] = $insertDataEmpty;
                        $flag           = true;
                        continue;
                    }
                    $insertData = [
                        'entName' => $ent['entName'],
                        'year'    => $datum['year'],
                        'id'      => $datum['id'],
                    ];
                    foreach ($kidTypesKeyArr as $item1) {
                        if (in_array($item1, $year_price_detail[$vYear]['cond']) && $datum[$item1] == 0) {
                            $flag = true;
                        }
                        if (isset($datum[$item1]) && !empty($datum[$item1])) {
                            $insertData[$item1] = round($datum[$item1], 2);
                        } else if (isset($datum[$item1]) && empty($datum[$item1])) {
                            $insertData[$item1] = 0;
                        }
                    }
                    $inserDataArr[] = $insertData;
                }
            } else {
                for ($i = ($year - $yearCount); $i <= $year; $i++) {
                    $insertDataEmpty = [
                        'entName' => $ent['entName'],
                        'year'    => $i,
                        'id'      => ''
                    ];
                    foreach ($kidTypesKeyArr as $item) {
                        $insertDataEmpty[$item] = '0';
                    }
                    $inserDataArr[] = $insertDataEmpty;
                    $flag           = true;
                }
            }
            //按年收费
            if (!$flag && !empty($res) && $price_type == self::PRICE_TYPE_2 && $this->searchFinanceChargeLog('', $ent_price_detail, $relation->userId, $ent['entName'])) {
                RequestUserInfo::create()->where('appId', $appId)->update([
                                                                              'money' => QueryBuilder::dec($ent_price_detail)
                                                                          ]);
                $this->insertFinanceChargeLog('', $ent_price_detail, $relation->userId, $batchNum, $ent['entName'], 1);
            }

            if ($flag) {
                foreach ($inserDataArr as $v) {
                    $data = [];
                    foreach ($v as $key => $item) {
                        if (in_array($key, ['id', 'year', 'entName'])) {
                            $data[$key] = $item;
                        } else {
                            $data[$key] = empty($item) ? '0' : '正常';
                        }
                    }
                    $resData[2][] = $data;
                }
            } else {
                foreach ($inserDataArr as $value) {
                    if ($price_type == self::PRICE_TYPE_1 && $this->searchFinanceChargeLog($value['id'], $year_price_detail[$value['year']]['price'], $relation->userId, '')) {
                        $price = $year_price_detail[$value['year']]['price'] ?? 0;
                        RequestUserInfo::create()->where('appId', $appId)->update([
                                                                                      'money' => QueryBuilder::dec($price)
                                                                                  ]);
                        $this->insertFinanceChargeLog($value['id'], $price, $relation->userId, $batchNum, $ent['entName'], 1);
                    }
                    unset($value['id']);
                    file_put_contents($file, implode(',', $this->replace($value)) . PHP_EOL, FILE_APPEND);
                }
                $resData[1] = array_merge($resData[1], $inserDataArr);
            }
//            dingAlarm('$resData',['$resData'=>json_encode($resData)]);
        }

        return [$fileName, $resData];
    }

    function getFinanceOriginalData($entname, $dataCount, $year): ?array
    {
        $data = FinanceData::create()->where("entName = '{$entname}'")->all();
        if (empty($data)) {
            $this->getFinanceOriginal($entname, $dataCount, $year);
        }
        $resData = [];
        $data    = $this->getArrSetKey($data, 'year');
        for ($i = ($year - $dataCount); $i <= $year; $i++) {
            if (isset($data[$i])) {
                $resData[$i] = $data[$i];
            } else {
                $this->getFinanceOriginal($entname, 1, $i);
                $yearData    = FinanceData::create()->where("entName = '{$entname}' and year = {$i}")->get();
                $resData[$i] = json_decode(json_encode($yearData), true);
            }
        }
        return $resData;

    }

    public function getFinanceOriginal($entname, $dataCount, $year)
    {
//        dingAlarm('insertFinanceData',['$entName'=>$entname,'$dataCount'=>$dataCount,'$year'=>$year]);
        $url       = 'https://api.meirixindong.com/provide/v1/xd/getFinanceOriginal';
        $appId     = '5BBFE57DE6DD0C8CDBC5D16A31125D5F';
        $appSecret = 'C2F24A85DF750882FAD7';
        $time      = time() . mt_rand(100, 999);
        $sign      = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);
        $data      = [
            'appId'     => $appId,
            'time'      => $time,
            'sign'      => $sign,
            'entName'   => $entname,
            'dataCount' => 10,
            'year'      => $year
        ];
        $res       = (new CoHttpClient())->useCache(true)->send($url, $data);
//        dingAlarm('insertFinanceData',['$entName'=>$entname,'$data'=>json_encode($res)]);
        if ($res['code'] == 200 && !empty($res['result'])) {
            $this->insertFinanceData($res['result'], $entname);
        }
        return true;
    }

    public function insertFinanceData($data, $entname)
    {

        foreach ($data as $year1 => $datum) {
            if (empty($datum)) unset($data[$year1]);
            $value = [];
            foreach ($datum as $k => $item) {
                if ($k != 'ANCHEYEAR' && empty($item)) {
                    $value['2'] = 2;
                }
                if ($k != 'ANCHEYEAR' && !empty($item)) {
                    $value['1'] = 1;
                }
            }
            if (count($value) == 1 && isset($value['2'])) unset($data[$year1]);
        }
        if (empty($data)) {
            return false;
        }
        foreach ($data as $year => $value) {
            $info = FinanceData::create()->where("entName = '{$entname}' and year = '$year'")->get();
            if (!empty($info)) {
                continue;
            }
            $insert = [
                'entName'   => $entname,
                'year'      => $year,
                'VENDINC'   => $value['VENDINC'] ?? '',
                'ASSGRO'    => $value['ASSGRO'] ?? '',
                'MAIBUSINC' => $value['MAIBUSINC'] ?? '',
                'TOTEQU'    => $value['TOTEQU'] ?? '',
                'RATGRO'    => $value['RATGRO'] ?? '',
                'PROGRO'    => $value['PROGRO'] ?? '',
                'NETINC'    => $value['NETINC'] ?? '',
                'LIAGRO'    => $value['LIAGRO'] ?? '',
                'SOCNUM'    => empty($value['SOCNUM']) ? '' : $value['SOCNUM'],
            ];
            FinanceData::create()->data($insert)->save();
        }
        return true;
    }

    public function getSmhzAbnormalFinance($ids, $appId, $batchNum, $user_id)
    {
        $ids = array_filter($ids);
        foreach ($ids as $k => $id) {
            if ($id == '--') {
                unset($ids[$k]);
            }
        }
        $list         = FinanceData::create()->where("id in (" . implode(',', $ids) . ")")->all();
        $listRelation = RequestUserApiRelationship::create()->where("userId = {$user_id} and status = 1 and apiId = 157")->get();
        if (empty($list)) {
            return '';
        }
        $year_price_detail = getArrByKey(json_decode($listRelation->year_price_detail), 'year');
        $ent_price_detail  = $listRelation->ent_price_detail;
        $price_type        = $listRelation->price_type;
        $kidTypes          = explode('|', $listRelation->kidTypes);
        $kidTypesKeyArr    = explode(',', $kidTypes['1']);
        $fileName          = date('YmdHis', time()) . '年报.csv';
        $file              = TEMP_FILE_PATH . $fileName;
        $insertData        = ['公司名称', '年'];
        foreach ($kidTypesKeyArr as $item) {
            $insertData[] = $this->kidTpye[$item];
        }
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $list     = json_decode(json_encode($list), true);
        $entNames = array_column($list, 'entName');
        if ($price_type == self::PRICE_TYPE_2) {
            foreach ($entNames as $entName) {
                if ($this->searchFinanceChargeLog('', $ent_price_detail, $user_id, $entName)) {
                    RequestUserInfo::create()->where('appId', $appId)->update([
                                                                                  'money' => QueryBuilder::dec($ent_price_detail)
                                                                              ]);
                    $this->insertFinanceChargeLog($item['id'], $ent_price_detail, $user_id, $batchNum, $entName, 1);
                }
            }
        }
        $data = [];
        foreach ($list as $item) {
            $insertData = [
                $item['entName'],
                $item['year'],
            ];
            foreach ($kidTypesKeyArr as $kidType) {
                if (isset($item[$kidType]) && is_numeric($item[$kidType])) {
                    $insertData[] = round($item[$kidType], 2);
                } else if (isset($item[$kidType]) && !is_numeric($item[$kidType])) {
                    $insertData[] = 0;
                }
            }
            file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
            if ($price_type == self::PRICE_TYPE_1 && $this->searchFinanceChargeLog($item['id'], $year_price_detail[$item['year']]['price'], $user_id, '')) {
                RequestUserInfo::create()->where('appId', $appId)->update([
                                                                              'money' => QueryBuilder::dec($year_price_detail[$item['year']]['price'])
                                                                          ]);
                $this->insertFinanceChargeLog($item['id'], $year_price_detail[$item['year']]['price'], $user_id, $batchNum, $item['entName'], 1);
            }
            $data = $insertData;

        }
        $this->inseartChargingLog($user_id, $batchNum, 15, implode(',', $ids), $data, $fileName, 2);
        return $fileName;
    }

    public function searchFinanceChargeLog($financeId, $price, $userId, $entName)
    {
//        dingAlarm('searchFinanceChargeLog',['sql'=>"financeId = {$financeId} and price = '{$price}' and userId = {$userId} and batchNum = '{$batchNum}'"]);
        $sql = "  userId = {$userId} and status!=1  ";
        if (!empty($financeId)) {
            $sql .= " and financeId = {$financeId}";
        }
        if (!empty($entName)) {
            $sql .= " and entName = '{$entName}'";
        }
        if (!empty($price)) {
            $sql .= " and price = '{$price}'";
        }
        $info = FinanceChargeLog::create()->where($sql)->get();
        return empty($info) ? true : false;
    }

    public function insertFinanceChargeLog($financeId, $price, $userId, $batchNum, $entName, $status)
    {
        $add = [
            'financeId' => $financeId,
            'price'     => $price,
            'userId'    => $userId,
            'batchNum'  => $batchNum,
            'entName'   => $entName,
            'status'    => $status
        ];
        FinanceChargeLog::create()->data($add)->save();
        return true;
    }

    public function getAbnormalDataText($batchNum, $appId)
    {
        $Ym       = Carbon::now()->format('Ym');
        $d        = 'day' . Carbon::now()->format('d');
        $workPath = ROOT_PATH . '/TempWork/SaiMengHuiZhi/' . 'Work/' . $Ym . '/' . $d . '/';
        $fileName = 'DESC' . control::getUuid() . '.text';
        $path     = '/TempWork/SaiMengHuiZhi/' . 'Work/' . $Ym . '/' . $d . '/' . $fileName;
        $info     = RequestUserInfo::create()->where(" appId = '{$appId}' ")->get();
        $logInfo  = BarchChargingLog::create()->where("type = 15 and userId = {$info->id} and batchNum = '{$batchNum}'")->get();
        if (!isset($logInfo->ret['2']) || empty($logInfo) || empty($logInfo->ret['2'])) {
            return '';
        }
        $ret = json_decode($logInfo->ret, true);
        $res = [];
        foreach ($ret['2'] as $v) {
            $insertData = [
                $v['entName'],
                $v['year'],
                $v['VENDINC'] == '' ? '' : $v['VENDINC'],
                $v['ASSGRO'] == '' ? '' : $v['ASSGRO'],
                $v['MAIBUSINC'] == '' ? '' : $v['MAIBUSINC'],
                $v['TOTEQU'] == '' ? '' : $v['TOTEQU'],
                $v['RATGRO'] == '' ? '' : $v['RATGRO'],
                $v['PROGRO'] == '' ? '' : $v['PROGRO'],
                $v['NETINC'] == '' ? '' : $v['NETINC'],
                $v['LIAGRO'] == '' ? '' : $v['LIAGRO'],
                $v['SOCNUM'] == '' ? '' : $v['SOCNUM'],
            ];
            foreach ($insertData as $k => $val) {
                if ($val == '') {
                    unset($insertData[$k]);
                }
            }
            $res[] = $insertData;

        }
        foreach ($res as $val) {
            file_put_contents(
                $workPath . $fileName,
                implode('|', $val) . PHP_EOL,
                FILE_APPEND
            );
        }
        return $path;
    }

    /**
     * 获取计费日志
     */
    public function getFinanceChargeLog($postData)
    {
        $pageNo          = empty($postData['pageNo'])?1:$postData['pageNo'] ;
        $pageSize        = $postData['pageSize'] ?? 10;
        $whereStr = 'userId = '.$postData['userId'];
        if (!empty($postData['batchNum'])) {
            $whereStr.=" and batchNum = {$postData['batchNum']}";
        }
        if (!empty($postData['status'])) {
            $whereStr.=" and status = {$postData['status']}";
        }
        if (!empty($postData['created_at'])) {
                $tmp = explode('|||', $postData['created_at']);
                $date1 = strtotime($tmp['0']);
                $date2 = strtotime($tmp['1']);
            $whereStr .= ' and created_at between '.$date1.' and '.$date2 ;
        }
        $limit = ($pageNo - 1) * $pageSize;
        dingAlarm('getFinanceChargeLog',['$whereStr'=>$whereStr]);
        $count = FinanceChargeLog::create()->where($whereStr)->count();
        dingAlarm('getFinanceChargeLog',['$whereStr'=>$whereStr." order by id desc limit {$limit},{$pageSize}"]);
        $list  = FinanceChargeLog::create()->where($whereStr." order by id desc limit {$limit},{$pageSize}")->all();
        return [$list, $count];
    }

    public function refund($id,$appId){
        $info = FinanceChargeLog::create()->where("id = {$id} and status = 1")->get();
        if(empty($info)){
            return false;
        }
        FinanceChargeLog::create()->where("id = {$id} and status = 1")->update(['status'=>2]);
        RequestUserInfo::create()->where('appId', $appId)->update([
            'money' => QueryBuilder::inc($info->price)]);
        return true;
    }
}