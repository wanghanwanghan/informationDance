<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceData;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\LongXin\LongXinService;

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
            dingAlarm('年报',['$entName'=>$ent['entName'],'$data'=>json_encode($resData)]);
        }
        return [$fileName, $resData];
    }

    public function smhzGetFinanceOriginal($entNames,$kidTypes){
        $kidTypes = explode('|',$kidTypes);
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
            $res = $this->getFinanceOriginalData($ent['entName'],$kidTypeList['1'],$kidTypeList['0']);
            dingAlarm('getFinanceOriginalData',['$res'=>json_encode($res)]);

            if(empty($res)){
                continue;
            }
            if(!empty($res['1'])) {
                foreach ($res['1'] as $datum) {
                    $insertData = [
                        $ent['entName'],
                        $datum['year'],
                    ];
                    foreach ($kidTypesKeyArr as $item) {
                        if (isset($datum[$item]) && is_numeric($datum[$item])) {
                            $insertData[] = round($datum[$item], 2);
                        } else if (isset($datum[$item]) && !is_numeric($datum[$item])) {
                            $insertData[] = '';
                        }
                    }
                    $resData['1'][] = $insertData;
                    file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
                }
            }
            if(!empty($res['2'])) {
                foreach ($res['2'] as $datum) {
                    $insertData = [
                        'entName'=>$ent['entName'],
                        'year'=>$datum['year'],
                        'id'=>$datum['id'],
                    ];
                    foreach ($kidTypesKeyArr as $item) {
                        if (isset($datum[$item]) && is_numeric($datum[$item])) {
                            $insertData[$item] = '正常';
                        } else if (isset($datum[$item]) && !is_numeric($datum[$item])) {
                            $insertData[$item] = '0';
                        }
                    }
                    $resData['2'][] = $insertData;
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
                $resData[$data[$i]['status']][] = $data[$i];
            }else{
                $this->getFinanceOriginal($entname,1,$i);
                $yearData = FinanceData::create()->where("entName = '{$entname}' and year = {$i}")->get();
                $resData[$yearData['status']][] = $yearData;
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
            $status = 1;
            if(!is_numeric($value['VENDINC']) || empty($value['VENDINC']) ||
                !is_numeric($value['ASSGRO']) || empty($value['ASSGRO']) ||
                !is_numeric($value['MAIBUSINC']) || empty($value['MAIBUSINC']) ||
                !is_numeric($value['TOTEQU']) || empty($value['TOTEQU']) ||
                !is_numeric($value['RATGRO']) || empty($value['RATGRO']) ||
                !is_numeric($value['PROGRO']) || empty($value['PROGRO']) ||
                !is_numeric($value['NETINC']) || empty($value['NETINC']) ||
                !is_numeric($value['LIAGRO']) || empty($value['LIAGRO']) ||
                !is_numeric($value['SOCNUM']) || empty($value['SOCNUM'])
            ){
                $status = 2;
            }
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
                'status'=>$status,
            ];
            dingAlarm('insertFinanceData',['$entName'=>$entname,'$insert'=>json_encode($insert)]);

            FinanceData::create()->data($insert)->save();
        }
        return true;
    }

    public function getSmhzAbnormalFinance($ids,$appId){
        $list = FinanceData::create()->where("id in (".implode($ids).")")->all();
        $info = RequestUserInfo::create()->where(" appId = '{$appId}'")->get();
        $listRelation = RequestUserApiRelationship::create()->where("userId = {$info->id} and status = 1 and apiId = 157")->get();
        if(empty($list)){
            return '';
        }
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

        }
        return $file;
    }

}