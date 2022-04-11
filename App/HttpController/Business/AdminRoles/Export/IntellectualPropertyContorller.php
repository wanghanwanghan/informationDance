<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;

class IntellectualPropertyContorller extends UserController
{
    /**
     * 企查查--软件著作权
     */
    public function qccGetSearchSoftwareCr($entNames){
//        dingAlarm('涉税处罚公示',['fhGetSatpartyChufa'=>json_encode($entNames)]);
//        $fileName = date('YmdHis', time()) . '涉税处罚公示.csv';
//        $file = TEMP_FILE_PATH . $fileName;
//        $header = [
//
//        ];
//        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSearchSoftwareCr($ent['entName'], 1);
            dingAlarm('软件著作权',['$datum'=>json_encode($data),'entName'=>$ent['entName']]);
//            if (empty($data['satparty_chufaList'])) {
//                continue;
//            }
//            if (isset($data['totalPageNum']) && $data['totalPageNum'] > 1) {
//                for ($i = 2; $i <= $data['totalPageNum']; $i++) {
//                    $data2 = $this->getSearchSoftwareCr($ent['entName'], 1);
//                    $data['satparty_chufaList'] = array_merge($data['satparty_chufaList'], $data2['satparty_chufaList']);
//                }
//            }
//            foreach ($data['satparty_chufaList'] as $datum) {
////                dingAlarm('涉税处罚公示',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
////                $resData[] = $this->fhGetSatpartyChufaDetail($datum['entryId'], $file, $ent['entName']);
//            }
        }
//        return [$fileName, $resData];
        return ['',''];
    }

    /**
     * 企查查--软件著作权
     */
    public function getSearchSoftwareCr($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];
        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'CopyRight/SearchSoftwareCr', $postData);
    }
    /**
     * 企查查--商标详情
     */
    public function getTmSearchDetail($id)
    {
        $postData = ['id' => $id];
        $res = (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'tm/GetDetails', $postData);
    }

    /**
     * 企查查--作品著作权
     */
    public function getSearchCopyRight($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];

        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'CopyRight/SearchCopyRight', $postData);
    }

    /**
     * 企查查--企业证书查询
     */
    public function getSearchCertification($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];

        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'ECICertification/SearchCertification', $postData);
     }
    /**
     * 企查查--企业证书查询详情
     */
    public function getSearchCertificationDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['certId' => $id];

        $res = (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'ECICertification/GetCertificationDetailById', $postData);

    }
     /**
      * 企查查--商标
      */
    public function getTmSearch($entName,$page){
        $postData = [
            'keyword' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];
        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'tm/Search', $postData);
    }

    /**
     * 企查查--专利
     */
    public function getPatentV4Search($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];
        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'PatentV4/Search', $postData);
    }

    /**
     * 企查查--专利详情
     */
    public function getPatentV4SearchDetail()
    {
        $id = $this->request()->getRequestParam('id');

        $postData = ['id' => $id];

        $res = (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'PatentV4/GetDetails', $postData);

    }
}