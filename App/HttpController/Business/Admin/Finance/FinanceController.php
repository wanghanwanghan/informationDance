<?php

namespace App\HttpController\Business\Admin\Finance;

use App\HttpController\Models\Api\User;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\control;

class FinanceController extends FinanceBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getIndex()
    {
        $uid = $this->getRequestData('uid');
        $aid = $this->getRequestData('aid');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 20);

        //个人用户
        $userList = User::create()->all();

        //企业用户
        $entUserList = RequestUserInfo::create()->all();


        return $this->writeJson(200, null, [
            'userList' => $userList,
            'entUserList' => $entUserList,
        ]);
    }

    function uploadEntList()
    {
        $files = $this->request()->getUploadedFiles();

        foreach ($files as $key => $oneFile) {
            if ($oneFile instanceof UploadFile) {
                try {
                    $filename = $oneFile->getTempName();
                    break;
                } catch (\Throwable $e) {
                    return $this->writeErr($e, __FUNCTION__);
                }
            }
        }

        $fp = fopen($filename, 'r+');

        while (feof($fp) === false) {
            $row = fgets($fp);
            $row = trim($row);
            if (strlen($row) < 20) {
                break;
            }
            $arr = explode(',', $row);
            $content[] = $arr[0];
        }

        return $this->writeJson(200, null, $content);
    }


}