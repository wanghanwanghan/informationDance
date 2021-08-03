<?php

namespace App\HttpController\Business\Admin\Finance;

use App\HttpController\Models\Api\User;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use EasySwoole\Http\Message\UploadFile;
use Overtrue\Pinyin\Pinyin;

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
            if (strlen($row) < 5) {
                break;
            }
            $arr = explode(',', $row);
            $pinyin = (new Pinyin())->convert($arr[0]);
            $content[] = ['name' => $arr[0], 'pinyin' => implode('', $pinyin)];
        }

        fclose($fp);

        return $this->writeJson(200, null, $content);
    }

    function getFinanceData()
    {
        $payEntValue = $this->getRequestData('payEntValue');
        $payUserValue = $this->getRequestData('payUserValue');
        $entList = $this->getRequestData('entList');

        foreach (jsonDecode($entList) as $oneEnt) {
            $postData = [
                'entName' => $oneEnt,
                'code' => '',
                'beginYear' => date('Y') - 1,
                'dataCount' => 5,
            ];
            $res = (new LongXinService())
                ->setCheckRespFlag(true)
                ->getFinanceData($postData, false);
            CommonService::getInstance()->log4PHP($res);
        }


        return $this->writeJson(200, null, $res);
    }

}