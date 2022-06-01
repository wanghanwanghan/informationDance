<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Finance;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadeHistory;
use App\HttpController\Service\Common\CommonService;

class FinanceController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
    
    public function getConfigLists(){ 
        return $this->writeJson(
            200,
            [],
           AdminUserFinanceConfig::create()->where("status = 1")->all()
        );
    }

    public function getAllRoles(){ 
        return $this->writeJson(
            200,
            [],
           AdminRoles::create()->where("status = 1")->all()
        );
    }

    public function getAllMenu(){  
        return $this->writeJson(
            200,
            [],
            AdminPrivilegedUser::getMenus(false,$this->loginUserinfo['id'])
        );
    }

    /**
     *  增加菜单
     */
    public function addConfig(){
        $requestData = $this->getRequestData(); 
        if (
            !$requestData['user_id'] ||
            //包的年限
            !$requestData['annually_years'] || 
            !$requestData['annually_price']  ||
            !$requestData['normal_years_price_json'] ||
            !$requestData['allowed_fields'] ||
            !$requestData['type'] ||
            !$requestData['cache']   
        ) {
            return $this->writeJson(201);
        }
        
        AdminUserFinanceConfig::create()->data([
            'user_id' => $requestData['user_id'], 
            'annually_price' => $requestData['annually_price'],  
            'annually_years' => $requestData['annually_years'],  
            'normal_years_price_json' => $requestData['normal_years_price_json'],  
            'cache' => $requestData['cache'],  
            'type' => $requestData['type'],  
            'allowed_fields' => $requestData['allowed_fields'],  
            'status' => 1,  
        ])->save();
        return $this->writeJson(200);
    }

     /**
     *  修改菜单
     */
    public function updateConfig(){
        $requestData = $this->getRequestData(); 
        $info = AdminUserFinanceConfig::create()->where('id',$requestData['id'])->get(); 
        $info->update([
            'id' => $requestData['id'],
            'annually_price' => $requestData['annually_price'] ?   $requestData['annually_price']: $info['annually_price'],
            'annually_years' => $requestData['annually_years'] ? $requestData['annually_years']: $info['annually_years'],
            'normal_years_price_json' => $requestData['normal_years_price_json'] ? $requestData['normal_years_price_json']: $info['normal_years_price_json'],
            'cache' => $requestData['cache'] ? $requestData['cache']: $info['cache'],
            'type' => $requestData['type'] ? $requestData['type']: $info['type'],
            'allowed_fields' => $requestData['allowed_fields'] ? $requestData['allowed_fields']: $info['allowed_fields'],
        ]);
        return $this->writeJson();
    }

    public function queryPower(){
        return AdminNewMenu::create()->all();
    }

    /*
     * 冻结
     */
    public function updateConfigStatus(){
       
        $id = $this->getRequestData('id');
        $status = $this->getRequestData('status');
        if (empty($phone)) return $this->writeJson(201, null, null, '参数 不能是空');
        if (empty($status)) return $this->writeJson(201, null, null, 'status 不能是空');
        $info = AdminUserFinanceConfig::create()->where("id = '{$id}' ")->get();
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'role_id' => $role_id,
            'status' => $status,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

    public function uploadeCompanyLists(){
        $years = $this->getRequestData('years');
        if($years <= 0){
            return $this->writeJson(206, [] ,   [], '缺少必要参数', true, []); 
        } 
        $requestData =  $this->getRequestData();
        $files = $this->request()->getUploadedFiles();
        $path = $fileName = '';

        $succeedNums = 0;
        foreach ($files as $key => $oneFile) {
            if (!$oneFile instanceof UploadFile) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'not instanceof UploadFile ',
                    ])
                ); 
                    continue;
            }

            try {
                $fileName = $oneFile->getClientFilename();
                $path = TEMP_FILE_PATH . $fileName;
                if(file(file_exists($path))){
                    CommonService::getInstance()->log4PHP(
                        'file  already exists. '.$path
                    );  
                    continue;
                }

                $res = $oneFile->moveTo($path);  
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        'move file   failed . '.$path
                    ); 
                    continue;
                }
                
                //todo 不允许重名
                //todo 不同文件 相同企业的处理 
                 AdminUserFinanceUploadeHistory::addUploadRecord(
                     [
                        'user_id' => $this->loginUserinfo['id'], 
                        'file_path' => $requestData['file_path'],  
                        'file_name' => $requestData['file_name'],  
                        'title' => $requestData['title'],  
                        'finance_config' => $requestData['finance_config'],  
                        'reamrk' => $requestData['reamrk'],  
                        'status' => 1,  
                     ]
                 );

            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'addCarInsuranceInfo Throwable continue',
                        $e->getMessage(),
                    ])
                );  
            } 
        }  

        return $this->writeJson(200, null, $batchNum,'导入成功 入库数量:'.$succeedNums); 
    }

}