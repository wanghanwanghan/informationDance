<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Documentation;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecordV3;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\Documentation;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\User\UserService;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;
use App\HttpController\Models\AdminV2\AdminUserRole;

class DocumentationController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {   
        // $this->setChckToken(true);
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function userReg(): bool
    {
        return $this->writeJson();
    }

    public function getAll(){
        $requestData = $this->getRequestData();

        $page = $requestData['page']??1;
        $createdAtStr = $this->getRequestData('created_at');
        $createdAtArr = explode('|||',$createdAtStr);
        $whereArr = [];
        if (
            !empty($createdAtArr) &&
            !empty($createdAtStr)
        ) {
            $whereArr = [
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[0]),
                    'operate' => '>=',
                ],
                [
                    'field' => 'created_at',
                    'value' => strtotime($createdAtArr[1]),
                    'operate' => '<=',
                ]
            ];
        }

        if(!empty($requestData['name'])){
            $whereArr[] =  [
                'field' => 'name',
                'value' => $requestData['name'],
                'operate' => '=',
            ];
        }

        $res = Documentation::findByConditionV2(
            $whereArr,
            $page
        );

        return $this->writeJson(200,  [
            'page' => $page,
            'pageSize' =>10,
            'total' => $res['total'],
            'totalPage' =>   $totalPages = ceil( $res['total']/ 10 ),
        ],  $res['data'],'成功');
    }

    // add
    public function addDocumention(){
        $requestData = $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [

                'name' => [
                    'not_empty' => 1,
                    'field_name' => 'name',
                    'err_msg' => '名称不能为空',
                ],
                'content' => [
                    'not_empty' => 1,
                    'field_name' => 'content',
                    'err_msg' => '内容不能为空',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $res = Documentation::addRecordV2(
           [
                'name' => $requestData['name'],
                'type' => Documentation::$type_api_wen_dang,//
                'content' => $requestData['content'],//
           ]
        );

        return $this->writeJson(200,  [ ],  $res,'成功');
    }

    //update
    public function editDocumention(){
        $requestData = $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [

                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => 'id不能为空',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $res = Documentation::updateById(
            $requestData['id'],
            [
                'name' => $requestData['name'],
                'type' => Documentation::$type_api_wen_dang,//
                'content' => $requestData['content'],//
            ]
        );

        return $this->writeJson(200,  [ ],  $res,'成功');
    }

    //
    public function downloadDocumention(){
        $requestData = $this->getRequestData();

        $checkRes = DataModelExample::checkField(
            [

                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => 'id不能为空',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $res = Documentation::findById($requestData['id'])->toArray();
        $fileName = $res['name'].'.html';
        file_put_contents(TEMP_FILE_PATH.$fileName, $res['content'], FILE_APPEND | LOCK_EX);

        return $this->writeJson(200,  [ ],  '/Static/Temp/'.$fileName,'成功');
    }

}