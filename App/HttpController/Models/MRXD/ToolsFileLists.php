<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;

// use App\HttpController\Models\AdminRole;

class ToolsFileLists extends ModelBase
{

    protected $tableName = 'tools_file_lists';

    static $state_init = 0 ;
    static $state_init_cname =  '处理中(0)' ;

    static $state_succeed =  10  ;
    static $state_succeedt_cname =  '处理成功' ;

    static  $type_bu_quan_zi_duan =  5 ;
    static  $type_bu_quan_zi_duan_cname =  '补全字段' ;

    static  $type_upload_weixin =  10 ;
    static  $type_upload_weixin_cname =  '上传微信' ;

    static  $type_upload_pull_gong_kai_contact =  15 ;
    static  $type_upload_pull_gong_kai_contact_cname =  '拉取公开联系人' ;

    static  $type_upload_pull_fei_gong_kai_contact =  20 ;
    static  $type_upload_pull_fei_gong_kai_contact_cname =  '拉取非公开联系人' ;

    static  function  stateMaps(){

        return [
            self::$state_init => self::$state_init_cname,
            self::$state_succeed => self::$state_succeedt_cname,
        ] ;
    }

    static  function  addRecordV2($info){

        return ToolsFileLists::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  ToolsFileLists::create()->data([
                'admin_id' => $requestData['admin_id'],
                'file_name' => $requestData['file_name']?:'',
                'new_file_name' => $requestData['new_file_name']?:'',
                'remark' => $requestData['remark']?:'',
                'type' => $requestData['type']?:'',
                'state' => $requestData['state']?:'',
                'touch_time' => $requestData['touch_time']?:'',
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'msg' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  ToolsFileLists::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = ToolsFileLists::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = ToolsFileLists::create()
                ->where($whereArr)
                ->page($page)
                ->order('id', 'DESC')
                ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV2($whereArr,$page){
        $model = ToolsFileLists::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }


    public static function findById($id){
        $res =  ToolsFileLists::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = ToolsFileLists::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `tools_file_lists` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    //补全联系人
    static function buQuanZiDuan($params){
       $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = 5 
            AND state = 0 
            LIMIT 3 
       ");
       foreach ($filesDatas as $filesData){
           self::setTouchTime($filesData['id'],date('Y-m-d H:i:s'));

           //写到csv里
           $fileName = pathinfo($filesData['file_name'])['filename'];
           $f = fopen(OTHER_FILE_PATH.$fileName.".csv", "w");
           fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

           $allFields = AdminUserSoukeConfig::getAllFieldsV2();
           foreach ($allFields as $field=>$cname){

               $title[] = $cname ;
           }
           fputcsv($f, $title);

           $yieldDatas = self::getXlsxYieldData($filesData['file_name'],OTHER_FILE_PATH);
           foreach ($yieldDatas as $dataItem) {
               //需要补全字段
               if($dataItem[1]){
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[1],'UNISCID');
               }
               else{
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[0],'ENTNAME');
               }
               $baseArr = [];
               //====================================
               foreach ($allFields as $field=>$cname){
                   if($field=='UNISCID'){

                       $res['UNISCID'] = ''.$res['UNISCID']. "\t";
                   }
                   if($field=='ENTTYPE'){
                       $cname =   CodeCa16::findByCode($res['ENTTYPE']);
                       $res['ENTTYPE'] =  $cname?$cname->getAttr('name'):'';
                   }
                   if($field=='ENTSTATUS'){
                       $cname =   CodeEx02::findByCode($res['ENTSTATUS']);
                       $res['ENTSTATUS'] =  $cname?$cname->getAttr('name'):'';
                   }

                   //地区
                   if(
                       $field=='DOMDISTRICT' &&
                       $res['DOMDISTRICT'] >0
                   ){
                       $res['DOMDISTRICT'] =  $res['DOM'];
                   }

                   //行业分类代码  findNICID
                   if(
                       $field=='NIC_ID' &&
                       !empty( $res['NIC_ID'])
                   ){
                       $res['NIC_ID'] =  $res['nic_full_name'];
                   }

                   //一般人
                   if(
                       $field=='yi_ban_ren'
                   ){
                       $res['yi_ban_ren'] =  $res['yi_ban_ren']?'有':'无';
                   }

                   //战略新兴产业
                   if(
                       $field=='zlxxcy'
                   ){
                       $res['zlxxcy'] =  $res['zlxxcy']?'有':'无';
                   }

                   //数字经济产业
                   if(
                       $field=='szjjcy'
                   ){
                       $res['szjjcy'] =  $res['szjjcy']?'有':'无';
                   }


                   if(
                       $field=='jin_chu_kou'
                   ){
                       $res['jin_chu_kou'] =  $res['jin_chu_kou']?'有':'无';
                   }


                   if(
                       $field=='iso'
                   ){
                       $res['iso'] =  $res['iso']?'有':'无';
                   }

                   // 高新技术
                   if(
                       $field=='gao_xin_ji_shu'
                   ){
                       $res['gao_xin_ji_shu'] =  $res['gao_xin_ji_shu']?'有':'无';
                   }

                   if(
                       is_array($res[$field])
                   ){
                       $baseArr[] = empty($res[$field])?'无':'有' ;
                   }else{

                       $baseArr[] = str_split ( $res[$field], 32766 )[0] ;
                   }
               }

               //====================================

               fputcsv($f, $baseArr);
           }

           self::updateById($filesData['id'],[
               'new_file_name' => $fileName.".csv",
               'state' => self::$state_succeed,
           ]);
       }
    }

    //上传公开联系人
    static function pullGongKaiContacts($params){
        //
        $dbId = $params['db_id'];


       $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = ".self::$type_upload_pull_gong_kai_contact." 
            AND state = 0 
            LIMIT 3 
       ");
       foreach ($filesDatas as $filesData){
           self::setTouchTime($filesData['id'],date('Y-m-d H:i:s'));

           //写到csv里
           $fileName = pathinfo($filesData['file_name'])['filename'];
           $f = fopen(OTHER_FILE_PATH.$fileName.".csv", "w");
           fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

           //插入表头
           $allFields = AdminUserSoukeConfig::getAllFieldsV2();
           foreach ($allFields as $field=>$cname){
               $title[] = $cname ;
           }
           fputcsv($f, $title);

           //插入数据
           $yieldDatas = self::getXlsxYieldData($filesData['file_name'],OTHER_FILE_PATH);
           foreach ($yieldDatas as $dataItem) {
               //需要补全字段
               if($dataItem[1]){
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[1],'UNISCID');
               }
               else{
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[0],'ENTNAME');
               }
               $baseArr = [];
               //====================================
               foreach ($allFields as $field=>$cname){
                   if($field=='UNISCID'){

                       $res['UNISCID'] = ''.$res['UNISCID']. "\t";
                   }
                   if($field=='ENTTYPE'){
                       $cname =   CodeCa16::findByCode($res['ENTTYPE']);
                       $res['ENTTYPE'] =  $cname?$cname->getAttr('name'):'';
                   }
                   if($field=='ENTSTATUS'){
                       $cname =   CodeEx02::findByCode($res['ENTSTATUS']);
                       $res['ENTSTATUS'] =  $cname?$cname->getAttr('name'):'';
                   }

                   //地区
                   if(
                       $field=='DOMDISTRICT' &&
                       $res['DOMDISTRICT'] >0
                   ){
                       $res['DOMDISTRICT'] =  $res['DOM'];
                   }

                   //行业分类代码  findNICID
                   if(
                       $field=='NIC_ID' &&
                       !empty( $res['NIC_ID'])
                   ){
                       $res['NIC_ID'] =  $res['nic_full_name'];
                   }

                   //一般人
                   if(
                       $field=='yi_ban_ren'
                   ){
                       $res['yi_ban_ren'] =  $res['yi_ban_ren']?'有':'无';
                   }

                   //战略新兴产业
                   if(
                       $field=='zlxxcy'
                   ){
                       $res['zlxxcy'] =  $res['zlxxcy']?'有':'无';
                   }

                   //数字经济产业
                   if(
                       $field=='szjjcy'
                   ){
                       $res['szjjcy'] =  $res['szjjcy']?'有':'无';
                   }


                   if(
                       $field=='jin_chu_kou'
                   ){
                       $res['jin_chu_kou'] =  $res['jin_chu_kou']?'有':'无';
                   }


                   if(
                       $field=='iso'
                   ){
                       $res['iso'] =  $res['iso']?'有':'无';
                   }

                   // 高新技术
                   if(
                       $field=='gao_xin_ji_shu'
                   ){
                       $res['gao_xin_ji_shu'] =  $res['gao_xin_ji_shu']?'有':'无';
                   }

                   if(
                       is_array($res[$field])
                   ){
                       $baseArr[] = empty($res[$field])?'无':'有' ;
                   }else{

                       $baseArr[] = str_split ( $res[$field], 32766 )[0] ;
                   }
               }

               //====================================

               fputcsv($f, $baseArr);
           }

           self::updateById($filesData['id'],[
               'new_file_name' => $fileName.".csv",
               'state' => self::$state_succeed,
           ]);
       }
    }

    //上传微信联系人
    static function shangChuanWeiXinHao($params){
       $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = 10 
            AND state = 0 
            LIMIT 3 
       ");
       foreach ($filesDatas as $filesData){
           self::setTouchTime($filesData['id'],date('Y-m-d H:i:s'));

           $yieldDatas = self::getXlsxYieldData($filesData['file_name'],OTHER_FILE_PATH);
           $i = 1;
           foreach ($yieldDatas as $dataItem) {
               //企业 税号 电话 微信名 性别
               $companyName = $dataItem[0];
               $companyCode = $dataItem[1];
               $phone = $dataItem[2];
               $wechat = $dataItem[3];
               $sex = $dataItem[4];

               if($i%100==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           [
                               'addWeChatInfo'=>[
                                    '$companyDataItem' => $companyName ,
                                    '$companyCode' => $companyCode ,
                                    '$phone' => $phone ,
                                    '$wechat' => $wechat ,
                                    '$sex' => $sex ,
                                    '$i' => $i ,
                               ]
                           ]
                       ])
                   );
               }


               if($phone<=0){
                   continue;
               }

               if(empty($wechat)){
                   continue;
               }
               if(strlen($phone) !== 11){
                   continue;
               }

               $created_at = time();
               $phone_aes = \wanghanwanghan\someUtils\control::aesEncode($phone, $created_at . '');
               $phone_md5 = md5($phone);
               $insert = [
                   'code' => $companyCode?:'',
                   'sex' => $sex?:'',
                   'phone' => $phone_aes,
                   'phone_md5' => $phone_md5,
                   'nickname' => $wechat,
                   'created_at' => $created_at,
                   'updated_at' => $created_at,
               ];

               WechatInfo::addRecordV2(
                   $insert
               );
               if($i%100==0){
                   CommonService    ::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           $insert
                       ])
                   );
               }

           }

           self::updateById($filesData['id'],[
               'new_file_name' => "",
               'state' => self::$state_succeed,
           ]);
       }
    }

    static  function getXlsxYieldData($xlsx_name,$path){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => $path]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $i = 1;
        while (true) {

            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            $i ++;
            yield $datas[] = [
                self::strtr_func($one[0]),
                self::strtr_func($one[1]),
                self::strtr_func($one[2]),
                self::strtr_func($one[3]),
                self::strtr_func($one[4]),
                self::strtr_func($one[5]),
                self::strtr_func($one[6]),
                self::strtr_func($one[7]),
                self::strtr_func($one[8]),
            ];
        }
    }

    static function strtr_func($str): string
    {
        $str = trim($str);

        if (empty($str)) {
            return '';
        }

        $arr = [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '(', '〕' => ')', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
            '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+', '—' => '-', '－' => '-',
            '～' => '~', '：' => ':', '。' => '.', '，' => ',', '、' => ',', '；' => ';', '？' => '?', '！' => '!', '…' => '-',
            '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' ', '×' => '*', '￣' => '~', '．' => '.', '＊' => '*',
            '＆' => '&', '＜' => '<', '＞' => '>', '＄' => '$', '＠' => '@', '＾' => '^', '＿' => '_', '＂' => '"', '￥' => '$', '＝' => '=',
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => ''
        ];

        return str_replace([',', ' '], '', strtr($str, $arr));
    }

}
