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
        foreach ($entNames as $ent) {
            $data = $this->getSatpartyChufa($ent['entName'], 1);
//            dingAlarm('涉税处罚公示',['$data'=>json_encode($data)]);
            if (empty($data)) continue;
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