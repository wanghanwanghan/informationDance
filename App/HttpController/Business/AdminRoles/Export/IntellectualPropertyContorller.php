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
        $fileName = date('YmdHis', time()) . '软件著作权.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '名称',//Name
            '软件简称',//ShortName
            '登记号',//RegisterNo
            '归属',//Owner
            '登记批准日期',//RegisterAperDate
            '版本号',//VersionNo
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSearchSoftwareCr($ent['entName'], 1);
            dingAlarm('软件著作权',['$datum'=>json_encode($data),'entName'=>$ent['entName']]);
            if (empty($data['Result'])) {
                continue;
            }
            if (isset($data['TotalRecords']) && $data['TotalRecords'] > $data['PageSize']) {
                $totalP = (int)($data['TotalRecords']/$data['PageSize'])+1;
                for ($i = 2; $i <= $totalP; $i++) {
                    $data2 = $this->getSearchSoftwareCr($ent['entName'], $i);
                    $data['Result'] = array_merge($data['Result'], $data2['Result']);
                }
            }
            foreach ($data['Result'] as $datum) {
                dingAlarm('软件著作权',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $insertData = [
                    $ent['entName'],
                    $datum['Name'],
                    $datum['ShortName'],
                    $datum['RegisterNo'],
                    $datum['Owner'],
                    $datum['RegisterAperDate'],
                    $datum['VersionNo'],
                ];
                file_put_contents($file, implode(',', $insertData) . PHP_EOL, FILE_APPEND);
                $resData[] = $insertData;
            }
        }
        return [$fileName, $resData];
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
     * 企查查--作品著作权
     */
    public function qccGetSearchCopyRight($entNames){
        $fileName = date('YmdHis', time()) . '作品著作权.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '名称',//Name
            '类型',//Category
            '登记号',//RegisterNo
            '登记日期',//RegisterDate
            '完成日期',//FinishDate
            '首次发表日期',//PublishDate
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSearchCopyRight($ent['entName'], 1);
            dingAlarm('作品著作权',['$datum'=>json_encode($data),'entName'=>$ent['entName']]);
            if (empty($data['Result'])) {
                continue;
            }
            if (isset($data['TotalRecords']) && $data['TotalRecords'] > $data['PageSize']) {
                $totalP = (int)($data['TotalRecords']/$data['PageSize'])+1;
                for ($i = 2; $i <= $totalP; $i++) {
                    $data2 = $this->getSearchCopyRight($ent['entName'], $i);
                    $data['Result'] = array_merge($data['Result'], $data2['Result']);
                }
            }
            foreach ($data['Result'] as $datum) {
                dingAlarm('作品著作权',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $insertData = [
                    $ent['entName'],
                    $datum['Name'],
                    $datum['Category'],
                    $datum['RegisterNo'],
                    $datum['RegisterDate'],
                    $datum['FinishDate'],
                    $datum['PublishDate'],
                ];
                file_put_contents($file, implode(',', $insertData) . PHP_EOL, FILE_APPEND);
                $resData[] = $insertData;
            }
        }
        return [$fileName, $resData];
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
    public function qccGetSearchCertification($entNames){
        $fileName = date('YmdHis', time()) . '企业证书查询.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '企业名称',
            '有效期起',
            '有效期至',
            '认证机关',
            '证书编号',
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getSearchCertification($ent['entName'], 1);
            dingAlarm('企业证书查询',['$datum'=>json_encode($data),'entName'=>$ent['entName']]);
            if (empty($data['Result'])) {
                continue;
            }
            if (isset($data['TotalRecords']) && $data['TotalRecords'] > $data['PageSize']) {
                $totalP = (int)($data['TotalRecords']/$data['PageSize'])+1;
                for ($i = 2; $i <= $totalP; $i++) {
                    $data2 = $this->getSearchCertification($ent['entName'], $i);
                    $data['Result'] = array_merge($data['Result'], $data2['Result']);
                }
            }
            foreach ($data['Result'] as $datum) {
                dingAlarm('企业证书查询',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->getSearchCertificationDetail($datum['Id'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];
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
    public function getSearchCertificationDetail($id,$file,$name)
    {
        $postData = ['certId' => $id];
        $res = (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'ECICertification/GetCertificationDetailById', $postData);
        dingAlarm('企业证书查询详情',['$res'=>json_encode($res)]);
        $data = $res['Result']['Data'];
        if(empty($data)){
            file_put_contents($file, ',,,,,,,,,,,,,,,,,,,,,,,,,,' . PHP_EOL, FILE_APPEND);
            return [];
        }
        $insertData = [
            $name,
            $data['企业名称'],
            $data['有效期起'],
            $data['有效期至'],
            $data['认证机关'],
            $data['证书编号'],
        ];
        file_put_contents($file, implode(',', $this->replace($insertData)) . PHP_EOL, FILE_APPEND);
        return $insertData;
    }

    /**
     * 企查查--商标
     */
    public function qccGetTmSearch($entNames){
        $fileName = date('YmdHis', time()) . '商标.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '名称',//Name
            '类型',//Category
            '登记号',//RegisterNo
            '登记日期',//RegisterDate
            '完成日期',//FinishDate
            '首次发表日期',//PublishDate
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getTmSearch($ent['entName'], 1);
            dingAlarm('商标',['$datum'=>json_encode($data),'entName'=>$ent['entName']]);
            if (empty($data['Result'])) {
                continue;
            }
            if (isset($data['TotalRecords']) && $data['TotalRecords'] > $data['PageSize']) {
                $totalP = (int)($data['TotalRecords']/$data['PageSize'])+1;
                for ($i = 2; $i <= $totalP; $i++) {
                    $data2 = $this->getTmSearch($ent['entName'], $i);
                    $data['Result'] = array_merge($data['Result'], $data2['Result']);
                }
            }
            foreach ($data['Result'] as $datum) {
                dingAlarm('商标',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->getTmSearchDetail($datum['Id'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];
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
     * 企查查--商标详情
     */
    public function getTmSearchDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $res = (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'tm/GetDetails', $postData);
        dingAlarm('商标详情',['$res'=>json_encode($res)]);
        return '';
    }


    /**
     * 企查查--专利
     */
    public function qccGetPatentV4Search($entNames){
        $fileName = date('YmdHis', time()) . '专利.csv';
        $file = TEMP_FILE_PATH . $fileName;
        $header = [
            '公司名',
            '名称',//Name
            '类型',//Category
            '登记号',//RegisterNo
            '登记日期',//RegisterDate
            '完成日期',//FinishDate
            '首次发表日期',//PublishDate
        ];
        file_put_contents($file, implode(',', $header) . PHP_EOL, FILE_APPEND);
        $resData = [];
        foreach ($entNames as $ent) {
            $data = $this->getPatentV4Search($ent['entName'], 1);
            dingAlarm('专利',['$datum'=>json_encode($data),'entName'=>$ent['entName']]);
            if (empty($data['Result'])) {
                continue;
            }
            if (isset($data['TotalRecords']) && $data['TotalRecords'] > $data['PageSize']) {
                $totalP = (int)($data['TotalRecords']/$data['PageSize'])+1;
                for ($i = 2; $i <= $totalP; $i++) {
                    $data2 = $this->getPatentV4Search($ent['entName'], $i);
                    $data['Result'] = array_merge($data['Result'], $data2['Result']);
                }
            }
            foreach ($data['Result'] as $datum) {
                dingAlarm('专利',['$datum'=>json_encode($datum),'entName'=>$ent['entName']]);
                $resData[] = $this->getPatentV4SearchDetail($datum['Id'], $file, $ent['entName']);
            }
        }
        return [$fileName, $resData];
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
    public function getPatentV4SearchDetail($id,$file,$name)
    {
        $postData = ['id' => $id];
        $res = (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'PatentV4/GetDetails', $postData);
        dingAlarm('专利详情',['$res'=>json_encode($res)]);
        return '';
    }
}