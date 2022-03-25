<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
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
        dingAlarm('年报头',['$data'=>json_encode($insertData)]);

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

}