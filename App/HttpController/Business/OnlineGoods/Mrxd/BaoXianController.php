<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\InsuranceData;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;

class BaoXianController extends \App\HttpController\Business\OnlineGoods\Mrxd\ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    /*
     * 筛选选型
     * */
    function getSearchOption(): bool
    {
        $searchOptionArr = (new XinDongService())->getSearchOption([]);
        return $this->writeJson(200, null, $searchOptionArr, '成功', false, []);
    }

    function getProducts(): bool
    {
        return $this->writeJson(
            200,[ ] ,(new \App\HttpController\Service\BaoYa\BaoYaService())->getProducts(),
            '成功',
            true,
            []
        );
    }

    function getProductDetail(): bool
    {
        if($this->getRequestData('id')<=0){
            return $this->writeJson(
                200,[ ] ,[],
                '参数缺失',
                true,
                []
            );
        }
        return $this->writeJson(
            200,[ ] ,
            (new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail
            (
                $this->getRequestData('id')
            ),
            '成功',
            true,
            []
        );
    }

    //咨询
    function consultProduct(): bool
    {
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [

                'product_id' => [
                    'not_empty' => 1,
                    'field_name' => 'product_id',
                    'err_msg' => '参数缺失',
                ],
                'insured' => [
                    'not_empty' => 1,
                    'field_name' => 'insured',
                    'err_msg' => '参数缺失',
                ]
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $res = InsuranceData::addRecordV2(
            [
                'post_params' => json_encode(
                    $requestData
                ),
                'product_id' => $requestData['product_id']?:'',
                'name' => $requestData['name']?:'',
                'status' =>  1,
            ]
        );

        return $this->writeJson(
            200,[ ] ,
             $res,
            '成功',
            true,
            []
        );
    }



    public function uploadeFile(){
        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();
        $fileNames = [];
        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $path = OTHER_FILE_PATH . $fileName;
//                if(file_exists($path)){
//                    return $this->writeJson(203, [], [],'文件已存在！');;
//                }

                $res = $oneFile->moveTo($path);
                if(!file_exists($path)){
                    CommonService::getInstance()->log4PHP(
                        json_encode(['uploadeCompanyLists   file_not_exists moveTo false ', 'params $path '=> $path,  ])
                    );
                    return $this->writeJson(203, [], [],'文件移动失败！');
                }
                $succeedNums ++;
                $fileNames[] = '/Static/OtherFile/'.$fileName;
            } catch (\Throwable $e) {
                return $this->writeJson(202, [], $fileNames,'上传失败'.$e->getMessage());
            }
        }

        return $this->writeJson(200, [], $fileNames,'上传成功 文件数量:'.$succeedNums);
    }

}