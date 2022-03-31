<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class SheshuiContorller  extends UserController
{
    /**
     * 法海 - 涉税处罚公示
     */
    public function fhGetSatpartyChufa($entNames){
        $fileName = date('YmdHis', time()) . '涉税处罚公示.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '局（政府单位）',
            '内容',
            '事件结果',
            '企业法定代表人',
            '法人身份证号码',
            '企业名称',
            '发布时间',
            '处罚时间',
            '税务登记号',
            '标题',
            '数据类型',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyChufa($ent['entName'], 1);
            dingAlarm('涉税处罚公示',['$data'=>json_encode($data)]);
            if (empty($data)) {
                continue;
            }
        }
        return ['',[]];
    }

    /**
     * 法海 - 涉税处罚公示
     */
    public function getSatpartyChufa($entName,$page){
        $docType = 'satparty_chufa';
        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => 10,
        ];
        return (new FaYanYuanService())->getList(CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl') . 'sat', $postData);
    }
}