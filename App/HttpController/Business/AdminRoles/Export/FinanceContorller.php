<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceData;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\LongXin\LongXinService;
use EasySwoole\Mysqli\QueryBuilder;

class FinanceContorller  extends UserController
{
    public $kidTpye = [
        'ASSGRO' => '资产总额',
        'LIAGRO' => '负债总额',
        'VENDINC' => '营业总收入',
        'MAIBUSINC' => '主营业务收入',
        'PROGRO' => '利润总额',
        'NETINC' => '净利润',
        'RATGRO' => '纳税总额',
        'TOTEQU' => '所有者权益合计',
        'SOCNUM' => '社保人数',
    ];
    /**
     * 西南年报
     * 2018-3/ASSGRO,LIAGRO,VENDINC,MAIBUSINC,PROGRO,NETINC,RATGRO,TOTEQU,SOCNUM
     */
    public function xinanGetFinanceNotAuth($entNames,$kidTypes)
    {
        $kidTypes = explode('|',$kidTypes);
        $kidTypeList = explode('-',$kidTypes['0']);
        $kidTypesKeyArr = explode(',',$kidTypes['1']);
        $fileName = date('YmdHis', time()) . '年报.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $insertData = ['公司名称', '年'];
        foreach ($kidTypesKeyArr as $item) {
            $insertData[] = $this->kidTpye[$item];
        }
//        dingAlarm('年报头',['$data'=>json_encode($insertData)]);

        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $postData = [
                'entName' => $ent['entName'],
                'code' => $ent['socialCredit'],
                'beginYear' => $kidTypeList['0'],
                'dataCount' => $kidTypeList['1'],//取最近几年的
            ];
            $res = (new LongXinService())->getFinanceData($postData, false);
            if(empty($res['data'])){
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            foreach ($res['data'] as $year=>$datum) {
                $insertData = [
                    $ent['entName'],
                    $year,
                ];
                foreach ($kidTypesKeyArr as $item) {
                    $insertData[] = $datum[$item]??'';
                }
                $resData[] = $insertData;
                file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
            }
//            dingAlarm('年报',['$entName'=>$ent['entName'],'$data'=>json_encode($resData)]);
        }
        return [$fileName, $resData];
    }

    public function smhzGetFinanceOriginal($entNames,$relation,$appId){
        dingAlarm('getFinanceOriginalData',['$relation'=>json_encode($relation)]);
        $kidTypes = explode('|',$relation->kidTypes);
        $year_price_detail = getArrByKey(json_decode($relation->year_price_detail),'year');
        $kidTypeList = explode('-',$kidTypes['0']);
        $kidTypesKeyArr = explode(',',$kidTypes['1']);
        $fileName = date('YmdHis', time()) . '年报.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $insertData = ['公司名称', '年'];
        foreach ($kidTypesKeyArr as $item) {
            $insertData[] = $this->kidTpye[$item];
        }
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
//            dingAlarm('getFinanceOriginalData',['$kidTypeList'=>json_encode($kidTypeList)]);
            $res = $this->getFinanceOriginalData($ent['entName'],$kidTypeList['1'],$kidTypeList['0']);
//            dingAlarm('getFinanceOriginalData',['$res'=>json_encode($res)]);
            if(empty($res)){
                file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
                continue;
            }
            if(!empty($res)) {
                foreach ($res as $datum) {
                    $insertData = [
                        $ent['entName'],
                        $datum['year'],
                    ];
                    $insertData2 = [
                        'entName'=>$ent['entName'],
                        'year'=>$datum['year'],
                        'id'=>$datum['id'],
                    ];
                    foreach ($kidTypesKeyArr as $item) {
                        if(in_array($item,$year_price_detail[$datum['year']]['cond']) && (!is_numeric($datum[$item]) || empty($datum[$item]))){
                            if (isset($datum[$item]) && is_numeric($datum[$item])) {
                                $insertData2[$item] = '正常';
                            } else if (isset($datum[$item]) && !is_numeric($datum[$item])) {
                                $insertData2[$item] = '0';
                            }
                            $resData['2'][] = $insertData2;
                        }else{
                            if (isset($datum[$item]) && is_numeric($datum[$item])) {
                                $insertData[] = round($datum[$item], 2);
                            } else if (isset($datum[$item]) && !is_numeric($datum[$item])) {
                                $insertData[] = '';
                            }
                            dingAlarm('getFinanceOriginalData',['$year_price_detail'=>json_encode($year_price_detail),'year'=>json_encode($year_price_detail[$datum['year']])]);
                            RequestUserInfo::create()->where('appId', $appId)->update([
                                                'money' => QueryBuilder::dec($year_price_detail[$datum['year']]['price'])
                                            ]);
                        }
                    }
                    $resData['1'][] = $insertData;
                    file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                }
            }
        }
        return [$fileName, $resData];
    }

    function getFinanceOriginalData($entname,$dataCount,$year): ?array
    {
        $data = FinanceData::create()->where("entName = '{$entname}'")->all();
        if(empty($data)){
            $this->getFinanceOriginal($entname,$dataCount,$year);
        }
        $resData = [];
        $data = $this->getArrSetKey($data,'year');
        for ($i=$year;$i<=($year+$dataCount-1);$i++)
        {
            if(isset($data[$i])){
                $resData[] = $data[$i];
            }else{
                $this->getFinanceOriginal($entname,1,$i);
                $yearData = FinanceData::create()->where("entName = '{$entname}' and year = {$i}")->get();
                $resData[] = $yearData;
            }
        }
        return $resData;

    }

    public function getFinanceOriginal($entname,$dataCount,$year){
        dingAlarm('insertFinanceData',['$entName'=>$entname,'$dataCount'=>$dataCount,'$year'=>$year]);
        $url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceOriginal';
        $appId = '5BBFE57DE6DD0C8CDBC5D16A31125D5F';
        $appSecret = 'C2F24A85DF750882FAD7';
        $time = time() . mt_rand(100, 999);
        $sign = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);
        $data = [
            'appId' => $appId,
            'time' => $time,
            'sign' => $sign,
            'entName' => $entname,
            'dataCount' => $dataCount,
            'year' => $year
        ];
        $res = (new CoHttpClient())->useCache(true)->send($url, $data);
        dingAlarm('insertFinanceData',['$entName'=>$entname,'$data'=>json_encode($res)]);
        $this->insertFinanceData($res['result'],$entname);
        return true;
    }

    public function insertFinanceData($data,$entname){
        if(empty($data)){
            return false;
        }
        foreach ($data as $year=>$value){
            $insert = [
                'entName'=>$entname,
                'year'=>$year,
                'VENDINC'=>$value['VENDINC']??'',
                'ASSGRO'=>$value['ASSGRO']??'',
                'MAIBUSINC'=>$value['MAIBUSINC']??'',
                'TOTEQU'=>$value['TOTEQU']??'',
                'RATGRO'=>$value['RATGRO']??'',
                'PROGRO'=>$value['PROGRO']??'',
                'NETINC'=>$value['NETINC']??'',
                'LIAGRO'=>$value['LIAGRO']??'',
                'SOCNUM'=>empty($value['SOCNUM'])?'':$value['SOCNUM'],
            ];
            dingAlarm('insertFinanceData',['$entName'=>$entname,'$insert'=>json_encode($insert)]);
            FinanceData::create()->data($insert)->save();
        }
        return true;
    }

    public function getSmhzAbnormalFinance($ids,$appId,$batchNum){
        $list = FinanceData::create()->where("id in (".implode($ids).")")->all();
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $listRelation = RequestUserApiRelationship::create()->where("userId = {$info->id} and status = 1 and apiId = 157")->get();
        if(empty($list)){
            return '';
        }
        $year_price_detail = getArrByKey(json_decode($listRelation->year_price_detail),'year');
        $kidTypes = explode('|',$listRelation->kidTypes);
        $kidTypesKeyArr = explode(',',$kidTypes['1']);
        $fileName = date('YmdHis', time()) . '年报.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $insertData = ['公司名称', '年'];
        foreach ($kidTypesKeyArr as $item) {
            $insertData[] = $this->kidTpye[$item];
        }
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $list = json_decode(json_encode($list),true);
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
            RequestUserInfo::create()->where('appId', $appId)->update([
                'money' => QueryBuilder::dec($year_price_detail[$item['year']]['price'])
            ]);
            $data = $insertData;
        }
        $this->inseartChargingLog($info->id, $batchNum, 15, $kidTypes,$data, $file, 2);
        return $file;
    }

}