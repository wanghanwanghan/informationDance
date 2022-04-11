<?php

namespace App\HttpController\Business\AdminRoles\Export;

use App\HttpController\Business\AdminRoles\User\UserController;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;

class IntellectualPropertyContorller extends UserController
{
    /**
     * 企查查软件著作权
     */
    public function getSearchSoftwareCr($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];

        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'CopyRight/SearchSoftwareCr', $postData);
    }

    /*
     * 作品著作权
     */
    public function getSearchCopyRight($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];

        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'CopyRight/SearchCopyRight', $postData);
    }

    /*
     * 企业证书查询
     */
    public function getSearchCertification($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];

        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'ECICertification/SearchCertification', $postData);
     }

     /*
      * 商标
      */
    public function getTmSearch($entName,$page){
        $postData = [
            'keyword' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];
        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'tm/Search', $postData);
    }

    /*
     * 专利
     */
    public function getPatentV4Search($entName,$page){
        $postData = [
            'searchKey' => $entName,
            'pageIndex' => $page,
            'pageSize' => 10,
        ];
        return (new LongDunService())->get(CreateConf::getInstance()->getConf('longdun.baseUrl') . 'PatentV4/Search', $postData);
    }

}