<?php

namespace App\HttpController\Business\Admin\Finance;

use App\HttpController\Models\Api\User;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
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
            if (strlen($row) < 5) {
                break;
            }
            $arr = explode(',', $row);
            $pinyinContent = (new Pinyin())->convert($arr[0]);
            CommonService::getInstance()->log4PHP($pinyinContent);
//            if (!empty($pinyinContent)) {
//                $modifyFromTo = ['lyu' => 'lv', 'nyu' => 'nv', 'ɑ' => 'a'];
//                foreach ($pinyinContent as $key => $value) {
//                    if (isset($modifyFromTo[$value])) {
//                        $pinyinContent[$key] = $modifyFromTo[$value];
//                    } else {
//                        foreach ($modifyFromTo as $k => $v) {
//                            $pinyinContent[$key] = str_replace($k, $v, $pinyinContent[$key]);
//                        }
//                    }
//                }
//            } else {
//                $pinyinContent = '';
//            }
//
//            if (empty($pinyinContent)) {
//                $patch['pinyin'] = substr($patch['subject'], 0, -1);
//            } else {
//                $pinyinContent = ModifyPinyin::getInstance()->modifyArray($pinyinContent);
//                $patch['pinyin'] = implode('', $pinyinContent);
//            }
            $content[] = $arr[0];
        }

        fclose($fp);

        return $this->writeJson(200, null, $content);
    }


}