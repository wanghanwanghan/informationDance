<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Service\LongXin\LongXinService;

class FinanceContorller  extends UserController
{
    public $kidTpye = [
        'ASSGRO' => ['key'=>'ASSGRO_yoy','value'=>'资产总额'],
        'LIAGRO' => ['key'=>'LIAGRO_yoy','value'=>'负债总额'],
        'VENDINC' => ['key'=>'VENDINC_yoy','value'=>'营业总收入'],
        'MAIBUSINC' => ['key'=>'MAIBUSINC_yoy','value'=>'主营业务收入'],
        'PROGRO' => ['key'=>'PROGRO_yoy','value'=>'利润总额'],
        'NETINC' => ['key'=>'NETINC_yoy','value'=>'净利润'],
        'RATGRO' => ['key'=>'RATGRO_yoy','value'=>'纳税总额'],
        'TOTEQU' => ['key'=>'TOTEQU_yoy','value'=>'所有者权益合计'],
        'SOCNUM' => ['key'=>'','value'=>'社保人数'],
    ];
    /**
     * 西南年报
     */
    public function xinanGetFinanceNotAuth($entNames,$kidTypes)
    {
        $kidTypeList = explode('-',$kidTypes);
        $fileName = date('YmdHis', time()) . '年报.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $insertData = [
            '公司名称', '年', '资产总额', '负债总额', '营业总收入', '主营业务收入', '利润总额', '净利润', '纳税总额','所有者权益合计','社保人数'
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        $data = [];
        foreach ($entNames as $ent) {
            $postData = [
                'entName' => $ent['entName'],
                'code' => $ent['socialCredit'],
                'beginYear' => $kidTypeList['0'],
                'dataCount' => $kidTypeList['1'],//取最近几年的
            ];
            $res = (new LongXinService())->getFinanceData($postData, false);
            dingAlarm('年报',['$entName'=>$ent['entName'],'$data'=>json_encode($res)]);

        }

    }

}