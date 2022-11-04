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

    /**
    上传公开联系人
    'fill_position_by_name' => intval($requestData['get_zhiwei']),
    'fill_weixin' => intval($requestData['get_wxname']),
    'fill_name_and_position_by_weixin' => intval($requestData['get_namezhiwei']),

    {
    "duty": "公司最高代表",
    "source": "企查查",
    "lid": 380325846,
    "ltype": "1",
    "name": "--",
    "idx": "S",
    "quhao": "河北省廊坊市",
    "url": "--",
    "lianxi": "13031482555",
    "lianxitype": "手机",
    "mobile_check_res": "1",
    "mobile_check_res_cname": "正常",
    "staff_position": "--"
    }
     */
    //TODO:上传文件格式文案：企业名称
    static function pullGongKaiContacts($params){
        $title = [
            "企业名称",
            '联系人职位',
            '联系方式来源',
            '联系人姓名',
            '手机归属地/座机区号',
            '联系方式来源网页链接',
            '联系方式',
            '联系方式类型(手机/座机/邮箱)',
            '手机号码状态',
            '手机微信号',
            '联系人名称(疑似/通过微信名匹配)',
            '职位(疑似/通过微信名匹配)',
            '微信匹配类型',
            '微信匹配子类型',
            '微信匹配值',
        ];

        //通过联系人名称 补全职位信息
        $fill_position_by_name = $params['fill_position_by_name'];
        //补全微信名称
        $fill_weixin_by_phone = $params['fill_weixin_by_phone'];
        //通过微信补全联系人姓名和职位
        $fill_name_and_position_by_weixin = $params['fill_name_and_position_by_weixin'];

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
           fputcsv($f, $title);

           //插入数据
           $yieldDatas = self::getXlsxYieldData($filesData['file_name'],OTHER_FILE_PATH);

           $i = 0;
           foreach ($yieldDatas as $dataItem) {
               $i++;
               //if($i%100==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           'pullGongKaiContacts_$i' => $i
                       ])
                   );
               //}


               // 企业名称：$dataItem[0]
               $entname = $dataItem[0];
               if(empty($entname)){
//                    CommonService::getInstance()->log4PHP(
//                        json_encode([
//                            __CLASS__.__FUNCTION__ .__LINE__,
//                            'pullGongKaiContacts_empty_ent_name' => [
//                                '$entname' => $entname,
//                            ]
//                        ])
//                    );
                   continue;
               }

               //取公开联系人信息
               $retData =  (new LongXinService())
                   ->setCheckRespFlag(true)
                   ->getEntLianXi([
                       'entName' => $entname,
                   ])['result'];
               CommonService::getInstance()->log4PHP(
                   json_encode([
                       __CLASS__.__FUNCTION__ .__LINE__,
                       'pullGongKaiContacts_pull_url_contact' => [
                           '$retData_nums' => count($retData),
                           'entName' => $entname,
                       ]
                   ])
               );
               //手机号状态检测 一次网络请求
               $retData = LongXinService::complementEntLianXiMobileState($retData);
//               CommonService::getInstance()->log4PHP(
//                   json_encode([
//                       __CLASS__.__FUNCTION__ .__LINE__,
//                       'pullGongKaiContacts_check_url_contact' => [
//                           '$retData' => $retData,
//                       ]
//                   ])
//               );

               //通过名称补全联系人职位信息 两次db请求：查询company_basic 查询 company_manager
               $retData = LongXinService::complementEntLianXiPositionV2($retData, $entname);
//               CommonService::getInstance()->log4PHP(
//                   json_encode([
//                       __CLASS__.__FUNCTION__ .__LINE__,
//                       'pullGongKaiContacts_fill_position_by_name' => [
//                           '$retData' => $retData,
//                       ]
//                   ])
//               );

               foreach($retData as $datautem){
                   $tmpDataItem = [
                       $entname,
                       $datautem['duty'],//'公开联系人职位',
                       $datautem['source'],//'公开联系方式来源',
                       $datautem['name'],//'公开联系人姓名',
                       $datautem['quhao'],//'公开手机归属地/座机区号',
                       $datautem['url'],//'公开联系方式来源网页链接',
                       $datautem['lianxi'],//'公开联系方式',
                       $datautem['lianxitype'],//'公开联系方式类型(手机/座机/邮箱)',
                       $datautem['mobile_check_res_cname'].''.$dataItem['mobile_check_res'].'',//'公开手机号码状态', 
                   ];

                   //通过手机号补全微信信息
                   if(
                       $datautem['lianxitype']!== '手机'
                   ){
                       $tmpDataItem[] = '';//'公开手机微信号码',
                       $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                       fputcsv($f, $tmpDataItem);

                       continue;
                   }

                   $matchedWeiXinName = WechatInfo::findByPhoneV2(($datautem['lianxi']));
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           'pullGongKaiContacts_fill_weixin_by_phone' => [
                               'lianxi' => $datautem['lianxi'],
                               '$matchedWeiXinName'=>$matchedWeiXinName,
                           ]
                       ])
                   );

                   if(empty($matchedWeiXinName)){
                        $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                        fputcsv($f, $tmpDataItem);

                       continue;
                   }

                   $tmpDataItem[] = $matchedWeiXinName['nickname'];//'公开手机微信号码',

                   //用微信名匹配联系人职位信息
                   $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV3($entname, $matchedWeiXinName['nickname']);
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           'pullGongKaiContacts_fill_name_and_position_by_weixin' => [
                               'nickname' => $matchedWeiXinName['nickname'],
                               '$tmpRes'=>$tmpRes,
                           ]
                       ])
                   );

                   $tmpDataItem[] =  $tmpRes['data']['NAME'];//联系人名称(疑似/通过微信名匹配)',
                   $tmpDataItem[] =  $tmpRes['data']['POSITION'];//'职位(疑似/通过微信名匹配)',
                   $tmpDataItem[] =  $tmpRes['match_res']['type'];//'微信匹配类型',
                   $tmpDataItem[] =  $tmpRes['match_res']['details'];//'微信匹配子类型',
                   $tmpDataItem[] =  $tmpRes['match_res']['percentage'];// '微信匹配值',

                   fputcsv($f, $tmpDataItem);
               }
           }

           self::updateById($filesData['id'],[
               'new_file_name' => $fileName.".csv",
               'state' => self::$state_succeed,
           ]);
       }

    }

    //TODO:上传文件格式文案：企业名称
    static function pullFeiGongKaiContacts($params){
        $title = [
            "企业名称",
            '联系人职位',
            '联系方式来源',
            '联系人姓名',
            '手机归属地/座机区号',
            '联系方式来源网页链接',
            '联系方式',
            '联系方式类型(手机/座机/邮箱)',
            '手机号码状态',
            '手机微信号',
            '联系人名称(疑似/通过微信名匹配)',
            '职位(疑似/通过微信名匹配)',
            '微信匹配类型',
            '微信匹配子类型',
            '微信匹配值',
        ];

        //通过联系人名称 补全职位信息
        $fill_position_by_name = $params['fill_position_by_name'];
        //补全微信名称
        $fill_weixin_by_phone = $params['fill_weixin_by_phone'];
        //通过微信补全联系人姓名和职位
        $fill_name_and_position_by_weixin = $params['fill_name_and_position_by_weixin'];

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
           fputcsv($f, $title);

           //插入数据
           $yieldDatas = self::getXlsxYieldData($filesData['file_name'],OTHER_FILE_PATH);

           $i = 0;
           foreach ($yieldDatas as $dataItem) {
               $i++;
               //if($i%100==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           'pullGongKaiContacts_$i' => $i
                       ])
                   );
               //}


               // 企业名称：$dataItem[0]
               $entname = $dataItem[0];
               if(empty($entname)){
//                    CommonService::getInstance()->log4PHP(
//                        json_encode([
//                            __CLASS__.__FUNCTION__ .__LINE__,
//                            'pullGongKaiContacts_empty_ent_name' => [
//                                '$entname' => $entname,
//                            ]
//                        ])
//                    );
                   continue;
               }

               //取公开联系人信息
               $retData =  (new LongXinService())
                   ->setCheckRespFlag(true)
                   ->getEntLianXi([
                       'entName' => $entname,
                   ])['result'];
               CommonService::getInstance()->log4PHP(
                   json_encode([
                       __CLASS__.__FUNCTION__ .__LINE__,
                       'pullGongKaiContacts_pull_url_contact' => [
                           '$retData_nums' => count($retData),
                           'entName' => $entname,
                       ]
                   ])
               );
               //手机号状态检测 一次网络请求
               $retData = LongXinService::complementEntLianXiMobileState($retData);
//               CommonService::getInstance()->log4PHP(
//                   json_encode([
//                       __CLASS__.__FUNCTION__ .__LINE__,
//                       'pullGongKaiContacts_check_url_contact' => [
//                           '$retData' => $retData,
//                       ]
//                   ])
//               );

               //通过名称补全联系人职位信息 两次db请求：查询company_basic 查询 company_manager
               $retData = LongXinService::complementEntLianXiPositionV2($retData, $entname);
//               CommonService::getInstance()->log4PHP(
//                   json_encode([
//                       __CLASS__.__FUNCTION__ .__LINE__,
//                       'pullGongKaiContacts_fill_position_by_name' => [
//                           '$retData' => $retData,
//                       ]
//                   ])
//               );

               foreach($retData as $datautem){
                   $tmpDataItem = [
                       $entname,
                       $datautem['duty'],//'公开联系人职位',
                       $datautem['source'],//'公开联系方式来源',
                       $datautem['name'],//'公开联系人姓名',
                       $datautem['quhao'],//'公开手机归属地/座机区号',
                       $datautem['url'],//'公开联系方式来源网页链接',
                       $datautem['lianxi'],//'公开联系方式',
                       $datautem['lianxitype'],//'公开联系方式类型(手机/座机/邮箱)',
                       $datautem['mobile_check_res_cname'].'('.$dataItem['mobile_check_res'].')',//'公开手机号码状态',

                   ];

                   //通过手机号补全微信信息
                   if(
                       $datautem['lianxitype']!== '手机'
                   ){
                       $tmpDataItem[] = '';//'公开手机微信号码',
                       $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                       fputcsv($f, $tmpDataItem);

                       continue;
                   }

                   $matchedWeiXinName = WechatInfo::findByPhoneV2(($datautem['lianxi']));
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           'pullGongKaiContacts_fill_weixin_by_phone' => [
                               'lianxi' => $datautem['lianxi'],
                               '$matchedWeiXinName'=>$matchedWeiXinName,
                           ]
                       ])
                   );

                   if(empty($matchedWeiXinName)){
                        $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                        fputcsv($f, $tmpDataItem);

                       continue;
                   }

                   $tmpDataItem[] = $matchedWeiXinName['nickname'];//'公开手机微信号码',

                   //用微信名匹配联系人职位信息
                   $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV3($entname, $matchedWeiXinName['nickname']);
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           'pullGongKaiContacts_fill_name_and_position_by_weixin' => [
                               'nickname' => $matchedWeiXinName['nickname'],
                               '$tmpRes'=>$tmpRes,
                           ]
                       ])
                   );

                   $tmpDataItem[] =  $tmpRes['data']['NAME'];//联系人名称(疑似/通过微信名匹配)',
                   $tmpDataItem[] =  $tmpRes['data']['POSITION'];//'职位(疑似/通过微信名匹配)',
                   $tmpDataItem[] =  $tmpRes['match_res']['type'];//'微信匹配类型',
                   $tmpDataItem[] =  $tmpRes['match_res']['details'];//'微信匹配子类型',
                   $tmpDataItem[] =  $tmpRes['match_res']['percentage'];// '微信匹配值',

                   fputcsv($f, $tmpDataItem);
               }
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