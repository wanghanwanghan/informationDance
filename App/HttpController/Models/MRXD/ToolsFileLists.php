<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\MobileCheckInfo;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\BusinessBase\CompanyClue;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\BusinessBase\ZhifubaoInfo;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\HdSaic\CompanyManager;
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

    static  $type_upload_gong_kai_contact =  25 ;
    static  $type_upload_gong_kai_contact_cname =  '上传非公开联系人' ;

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
                    'tools_file_lists_入库失败'=>[
                        '参数' => $requestData,
                        '错误信息' => $e->getMessage(),
                    ]
                ],JSON_UNESCAPED_UNICODE)
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
            LIMIT 2 
       ");
       foreach ($filesDatas as $filesData){
           CommonService::getInstance()->log4PHP(
               json_encode([
                   __CLASS__.__FUNCTION__ .__LINE__,
                   '开始执行补全字段'=>[
                       '参数' => $params,
                       '执行的数据' => $filesData,
                   ]
               ],JSON_UNESCAPED_UNICODE)
           );

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
           $i = 1;
           foreach ($yieldDatas as $dataItem) {
               $i ++;
               if($i%300==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           '开始执行补全字段' => [
                               '已生成'.$i,
                               $filesData['file_name']
                           ]
                       ], JSON_UNESCAPED_UNICODE)
                   );
               }

               //需要补全字段
               if($dataItem[1]){
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[1],'UNISCID',[]);
               }
               else{
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[0],'ENTNAME',[]);
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


                   //iso_tags
                   if(
                       $field=='iso_tags'
                   ){
                       $str = "";
                       foreach ($dataItem['iso_tags'] as $subItem){
                           $str.= $subItem['cert_project'];
                       }
                       $dataItem['iso_tags'] =  $str;
                   }

                   if(
                       $field=='jin_chu_kou'
                   ){
                       $res['jin_chu_kou'] =  $res['jin_chu_kou']?'有':'无';
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
               fputcsv($f, $baseArr);
           }

           self::updateById($filesData['id'],[
               'new_file_name' => $fileName.".csv",
               'state' => self::$state_succeed,
           ]);
       }
    }
    static function buQuanZiDuanES($params){
       $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = 5 
            AND state = 0 
            LIMIT 2 
       ");
       foreach ($filesDatas as $filesData){
           CommonService::getInstance()->log4PHP(
               json_encode([
                   __CLASS__.__FUNCTION__ .__LINE__,
                   '开始执行补全字段'=>[
                       '参数' => $params,
                       '执行的数据' => $filesData,
                   ]
               ],JSON_UNESCAPED_UNICODE)
           );

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
           $i = 1;
           foreach ($yieldDatas as $dataItem) {
               $i ++;
               if($i%300==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           '开始执行补全字段' => [
                               '已生成'.$i,
                               $filesData['file_name']
                           ]
                       ], JSON_UNESCAPED_UNICODE)
                   );
               }

               //需要补全字段
               if($dataItem[1]){
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[1],'UNISCID',[]);
               }
               else{
                   $res = (new XinDongService())->getEsBasicInfoV3($dataItem[0],'ENTNAME',[]);
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


                   //iso_tags
                   if(
                       $field=='iso_tags'
                   ){
                       $str = "";
                       foreach ($dataItem['iso_tags'] as $subItem){
                           $str.= $subItem['cert_project'];
                       }
                       $dataItem['iso_tags'] =  $str;
                   }

                   if(
                       $field=='jin_chu_kou'
                   ){
                       $res['jin_chu_kou'] =  $res['jin_chu_kou']?'有':'无';
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
    //
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
            '联系人职位(通过联系人姓名匹配到的)',
            '手机号码状态',
            '手机微信号',
            '联系人名称(疑似/通过微信名匹配)',
            '职位(疑似/通过微信名匹配)',
            '微信匹配类型',
            '微信匹配子类型',
            '微信匹配值',
        ];

       $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = ".self::$type_upload_pull_gong_kai_contact." 
            AND state = 0 
            LIMIT 2 
       ");
       foreach ($filesDatas as $filesData){
           CommonService::getInstance()->log4PHP(
               json_encode([
                   __CLASS__.__FUNCTION__ .__LINE__,
                   '拉取公开联系人开始执行'=>[
                       '参数' => $params,
                       '执行的数据' => $filesData,
                   ]
               ],JSON_UNESCAPED_UNICODE)
           );

            $tmp = json_decode($filesData['remark'],true);
           //通过联系人名称 补全职位信息
           $fill_position_by_name = $tmp['fill_position_by_name'];
           //补全微信名称
           $fill_weixin_by_phone = $tmp['fill_weixin_by_phone'];
           //通过微信补全联系人姓名和职位
           $fill_name_and_position_by_weixin = $tmp['fill_name_and_position_by_weixin'];
           //过滤掉企查查
           $filter_qcc_phone = $tmp['filter_qcc_phone'];

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
               if($i%300==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           '拉取公开联系人_已生成' => $i,
                           '$dataItem' => $dataItem,
                       ], JSON_UNESCAPED_UNICODE)
                   );
               }


               // 企业名称：$dataItem[0]
               $entname = $dataItem[0];
               $code = $dataItem[1];
               if($code){
                   $companyRes = CompanyBasic::findByCode($code);
               }else{
                   $companyRes = CompanyBasic::findByName($entname);
               }

               if(empty($companyRes)){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           '拉取公开联系人-找不到企业信息-continue'=>[
                               '信用代码' => $code,
                               '企业名称' => $entname,
                           ]
                       ],JSON_UNESCAPED_UNICODE)
                   );
                   continue;
               }
               $entname = $companyRes->ENTNAME;
               //取公开联系人信息
               $retData =  (new LongXinService())
                   ->setCheckRespFlag(true)
                   ->getEntLianXi([
                       'entName' => $entname,
                   ])['result'];

               //如果需要过滤企查查里的联系人
               if(
                   $companyRes->UNISCID &&
                   $filter_qcc_phone
               ){
                   $allConatcts = CompanyClue::getAllContactByCode($companyRes->UNISCID);
                   $newRetData = [];
                   foreach ($retData as $key1 => $datum1){
                       if(
                           !in_array($datum1['lianxi'],$allConatcts['qcc'])
                       ){
                           $newRetData[$key1] = $datum1;
                       }else{
//                           CommonService::getInstance()->log4PHP(
//                               json_encode([
//                                   __CLASS__.__FUNCTION__ .__LINE__,
//                                   '拉取公开联系人-企查查掉了-'=>[
//                                       '信用代码' => $code,
//                                       '企业名称' => $entname,
//                                       'qcc' => $allConatcts['qcc'],
//                                       'lianxi' => $datum1['lianxi'],
//                                   ]
//                               ],JSON_UNESCAPED_UNICODE)
//                           );
                       }
                   }
                   $retData = $newRetData;
               }

               //手机号状态检测 一次网络请求
               $retData = LongXinService::complementEntLianXiMobileState($retData);

               //通过名称补全联系人职位信息 两次db请求：查询company_basic 查询 company_manager
               if($fill_position_by_name){
                   $retData = LongXinService::complementEntLianXiPositionV2($retData, $entname);
               }

               //有效的联系人
               $validContacts = CompanyManager::getManagesNamesByCompanyId($companyRes->companyid);
               foreach($retData as $datautem){
                   //公开联系人姓名
                   if($datautem['name']){
                        if(
                            !empty($validContacts) &&
                            !in_array($datautem['name'],$validContacts)
                        ){
                            CommonService::getInstance()->log4PHP(
                                json_encode([
                                    __CLASS__.__FUNCTION__ .__LINE__,
                                    '拉取公开联系人-不是有效的-continue'=>[
                                        '联系人名称' => $datautem['name'],
                                        '有效联系人' => $validContacts,
                                        '信用代码' => $code,
                                        '企业名称' => $entname,
                                    ]
                                ],JSON_UNESCAPED_UNICODE)
                            );
                            continue;
                        }
                   }

                   $tmpDataItem = [
                       $entname,
                       $datautem['duty'],//'公开联系人职位',
                       $datautem['source'],//'公开联系方式来源',
                       $datautem['name'],//'公开联系人姓名',
                       $datautem['quhao'],//'公开手机归属地/座机区号',
                       $datautem['url'],//'公开联系方式来源网页链接',
                       $datautem['lianxi'],//'公开联系方式',
                       $datautem['lianxitype'],//'公开联系方式类型(手机/座机/邮箱)',
                       $datautem['POSITION'],//'通过姓名批匹配到的联系人职位,
                       $datautem['mobile_check_res_cname'].''.$datautem['mobile_check_res'].'',//'公开手机号码状态',
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

                   //不需要补充微信名
                   if(!$fill_weixin_by_phone){
                       $tmpDataItem[] = '';//'公开手机微信号码',
                       $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                       fputcsv($f, $tmpDataItem);
                       continue;
                   }

                   $matchedWeiXinName = WechatInfo::findByPhoneV2(($datautem['lianxi']));

                   if(empty($matchedWeiXinName)){
                        $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                        fputcsv($f, $tmpDataItem);

                       continue;
                   }

                   $tmpDataItem[] = $matchedWeiXinName['nickname'];//'公开手机微信号码',

                   //不需要微信匹配职位
                   if(!$fill_name_and_position_by_weixin){
                       $tmpDataItem[] = '';
                       fputcsv($f, $tmpDataItem);
                       continue;
                   }

                   //用微信名匹配联系人职位信息
                   $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV3($entname, $matchedWeiXinName['nickname']);

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
    static function pullFeiGongKaiContacts($params){
        $title = [
            '企业名称',
            '信用代码',
            '手机号',
            '手机号码状态',
            '手机微信号',
            '联系人名称(疑似/通过微信名匹配)',
            '职位(疑似/通过微信名匹配)',
            '微信匹配类型',
            '微信匹配子类型',
            '微信匹配值',
        ];



       $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = ".self::$type_upload_pull_fei_gong_kai_contact." 
            AND state = 0 
            LIMIT 2 
       ");
       foreach ($filesDatas as $filesData){
           CommonService::getInstance()->log4PHP(
               json_encode([
                   '拉取非公开开始执行' => [
                       "参数"=>$params,
                       "执行的数据"=>$filesData,
                   ]
               ], JSON_UNESCAPED_UNICODE)
           );

           self::setTouchTime($filesData['id'],date('Y-m-d H:i:s'));

           $tmp = json_decode($filesData['remark'],true);
           //通过联系人名称 补全职位信息
           $fill_position_by_name = $tmp['fill_position_by_name'];
           //补全微信名称
           $fill_weixin_by_phone = $tmp['fill_weixin_by_phone'];
           //通过微信补全联系人姓名和职位
           $fill_name_and_position_by_weixin = $tmp['fill_name_and_position_by_weixin'];
           //过滤掉企查查
           $filter_qcc_phone = $tmp['filter_qcc_phone'];

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
               if($i%300==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           '拉取非公开已生成' => $i
                       ], JSON_UNESCAPED_UNICODE)
                   );
               }

               // 企业名称：$dataItem[0]
               $entname = self::strtr_func($dataItem[0]);
               $code =  self::strtr_func($dataItem[1]);

               if($code>0){
                   $companyRes = CompanyBasic::findByCode($code);
               }
               else{
                   $companyRes = CompanyBasic::findByName($entname);
               }

               //有效的联系人
               $validContacts = CompanyManager::getManagesNamesByCompanyId($companyRes->companyid);
               if(
                   empty($companyRes) ||
                   empty($companyRes->UNISCID)
               ){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                           __CLASS__.__FUNCTION__ .__LINE__,
                           '拉取非公开联系人-找不到企业信息-continue'=>[
                               '信用代码' => $code,
                               '企业名' => $entname,
                           ]
                       ],JSON_UNESCAPED_UNICODE)
                   );
                   continue;
               }

               //取公开联系人信息
               $allConatcts = CompanyClue::getAllContactByCode($companyRes->UNISCID);

               $tmpContacts = [];
               foreach ($allConatcts['xn'] as $tmpPhone){
                   if($filter_qcc_phone){
                       if(
                           !in_array($tmpPhone,$allConatcts['qcc'])
                       ){
                           $tmpContacts[$tmpPhone] = $tmpPhone;
                       }
                   }else{
                       $tmpContacts[$tmpPhone] = $tmpPhone;
                   }
               }

               $allConatcts['xn'] = $tmpContacts;
              if(empty($tmpContacts)){
                  $tmpDataItem = [
                      $entname,//企业名称
                      $companyRes->UNISCID."\t",//信用代码
                  ];
                  fputcsv($f, $tmpDataItem);
                  continue;
              }

               //手机号状态检测 一次网络请求
               $needsCheckMobilesStr = join(",", $tmpContacts);
               $postData = [
                   'mobiles' => $needsCheckMobilesStr,
               ];

               $res = (new ChuangLanService())->getCheckPhoneStatus($postData);
               // 转换为以手机号为key的数组
               $mobilesRes = LongXinService::shiftArrayKeys($res['data'], 'mobile');

               foreach($tmpContacts as $item){
                   $tmpDataItem = [
                       $entname,//企业名称
                       $companyRes->UNISCID."\t",//信用代码
                       $item,//手机号
                       ChuangLanService::getStatusCnameMap()[$mobilesRes[$item]['status']].$mobilesRes[$item]['status'],//手机号状态
                   ];

                   if(
                       !LongXinService::isValidPhone($item)
                   ){
                       $tmpDataItem[] = '';//'公开手机微信号码',
                       $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                       fputcsv($f, $tmpDataItem);
                       continue;
                   }

                    //不需要微信名
                    if(!$fill_weixin_by_phone){
                        $tmpDataItem[] = '';
                        fputcsv($f, $tmpDataItem);

                        continue;
                    }

                   $matchedWeiXinName = WechatInfo::findByPhoneV2(($item));

                   if(empty($matchedWeiXinName)){
                        $tmpDataItem[] = '';//'联系人名称(疑似/通过微信名匹配)',
                        fputcsv($f, $tmpDataItem);

                       continue;
                   }

                   $tmpDataItem[] = $matchedWeiXinName['nickname'];//'公开手机微信号码',

                   //不需要通过微信匹配职位信息
                   if(!$fill_name_and_position_by_weixin){
                       $tmpDataItem[] = '';
                       fputcsv($f, $tmpDataItem);

                       continue;
                   }

                   //用微信名匹配联系人职位信息
                   $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV3($entname, $matchedWeiXinName['nickname']);
                   //需要过滤下无效的联系人、离职的联系人
                   if(
                       $tmpRes['data']['NAME'] &&
                       !empty($validContacts) &&
                       !in_array($tmpRes['data']['NAME'],$validContacts)
                   ){
                       CommonService::getInstance()->log4PHP(
                           json_encode([
                               __CLASS__.__FUNCTION__ .__LINE__,
                               '拉取非公开联系人-不是有效的联系人-continue'=>[
                                   '联系人名称' => $tmpRes['data']['NAME'],
                                   '信用代码' => $code,
                                   '企业名称' => $entname,
                                   '有效联系人' => $validContacts,
                               ]
                           ],JSON_UNESCAPED_UNICODE)
                       );
                       continue;
                   }

                   $tmpDataItem[] =  $tmpRes['data']['NAME']; //联系人名称(疑似/通过微信名匹配)',
                   $tmpDataItem[] =  $tmpRes['data']['POSITION']; //'职位(疑似/通过微信名匹配)',
                   $tmpDataItem[] =  $tmpRes['match_res']['type']; //'微信匹配类型',
                   $tmpDataItem[] =  $tmpRes['match_res']['details']; //'微信匹配子类型',
                   $tmpDataItem[] =  $tmpRes['match_res']['percentage']; // '微信匹配值',

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
            LIMIT 2 
       ");
       foreach ($filesDatas as $filesData){
           CommonService::getInstance()->log4PHP(
               json_encode([
                   // __CLASS__.__FUNCTION__ .__LINE__,
                   [
                       '开始处理上传的微信号文件'=>[
                           '参数' => $params ,
                           '数据' => $filesData ,
                       ]
                   ]
               ], JSON_UNESCAPED_UNICODE)
           );

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

               if($i%300==0){
                   CommonService::getInstance()->log4PHP(
                       json_encode([
                          // __CLASS__.__FUNCTION__ .__LINE__,
                           [
                               '上传微信联系人'=>[
                                    '企业名' => $companyName ,
                                    '信用代码' => $companyCode ,
                                    '手机号' => $phone ,
                                    '微信号' => $wechat ,
                                    '性别' => $sex ,
                                    '已生成' => $i ,
                               ]
                           ]
                       ], JSON_UNESCAPED_UNICODE)
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
           }

           self::updateById($filesData['id'],[
               'new_file_name' => "",
               'state' => self::$state_succeed,
           ]);
       }
    }

    //上传支付宝
    static function shangChuanZhiFubao($params){
        $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = 10 
            AND state = 0 
            LIMIT 2 
       ");
        foreach ($filesDatas as $filesData){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    // __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        '通用文件-开始处理'=>[
                            '参数' => $params,
                            '数据' =>  $filesData,
                        ]
                    ]
                ], JSON_UNESCAPED_UNICODE)
            );

            self::setTouchTime($filesData['id'],date('Y-m-d H:i:s'));

            $yieldDatas = self::getXlsxYieldData($filesData['file_name'],OTHER_FILE_PATH);
            $i = 1;
            foreach ($yieldDatas as $dataItem) {
                //企业 税号 电话 支付宝
                $companyName = $dataItem[0];
                $companyCode = $dataItem[1];
                $phone = $dataItem[2];
                $wechat = $dataItem[3];

                if($i%300==0){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            // __CLASS__.__FUNCTION__ .__LINE__,
                            [
                                '上传支付宝'=>[
                                    '企业' => $companyName ,
                                    '信用代码' => $companyCode ,
                                    '手机号' => $phone ,
                                    '支付宝' => $wechat ,
                                    '已生成' => $i ,
                                ]
                            ]
                        ], JSON_UNESCAPED_UNICODE)
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
                    'sex' => '',
                    'phone' => $phone_aes,
                    'phone_md5' => $phone_md5,
                    'nickname' => $wechat,
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ];

                ZhifubaoInfo::addRecordV2(
                    $insert
                );
            }

            self::updateById($filesData['id'],[
                'new_file_name' => "",
                'state' => self::$state_succeed,
            ]);
        }
    }

    //上传公开联系人
    static function shangChuanGongKaiContact($params){
       $filesDatas = self::findBySql("
            WHERE touch_time < 1
            AND type = 25 
            AND state = 0 
            LIMIT 2 
       ");

        $i = 1;
       foreach ($filesDatas as $filesData){
           CommonService::getInstance()->log4PHP(
               json_encode([
                   __CLASS__.__FUNCTION__ .__LINE__,
                   [
                       '上传公开联系人开始执行'=>[
                           '参数'=>$params,
                           '数据'=>$filesData,
                       ]
                   ]
               ],JSON_UNESCAPED_UNICODE)
           );

           self::setTouchTime($filesData['id'],date('Y-m-d H:i:s'));

           $yieldDatas = self::getXlsxYieldData($filesData['file_name'],OTHER_FILE_PATH);

           foreach ($yieldDatas as $dataItem) {
               $companysContacts = [];
               //企业 税号 电话 微信名 性别
               $companyName = $dataItem[0];
               $companyRes = CompanyBasic::findByName($companyName);

               //$companyCode = $dataItem[1];
               if(empty($companyRes)){
                   continue;
               }
               $companyCode = $companyRes->UNISCID;
               if(empty($companyCode)){
                   continue;
               }
               $phone = $dataItem[1];
               $phone2 = $dataItem[2];

               if(
                   !empty($phone) &&
                   $phone!='-'
               ){
                   $companysContacts[$companyName][$phone] = $phone;
               }
               if(
                   !empty($phone2) &&
                   $phone2!='-'
               ){

                   $tmpArr = explode('; ',$phone2);
                   foreach ($tmpArr as $tmpPhone){
                       $companysContacts[$companyName][$tmpPhone] = $tmpPhone;
                   }
               }

               foreach ($companysContacts as $phonesArr){
                   $time = time();
                   $str = join(";",$phonesArr);
                   $str_aes = \wanghanwanghan\someUtils\control::aesEncode($str, $time . '');
                   $str2 = count($phonesArr)."@".$str_aes;

                   $dbArr = [
                       'entname' => $companyName,
                       'code' => $companyCode?:'',
                       'fr' => '',
                       'qcc' =>    $str2,
                       'pub' => '',
                       'pri' => '',
                       'created_at' => $time,
                       'updated_at' => $time,
                   ];
                   CompanyClue::addRecordV2(
                       $dbArr
                   );

                   if($i%100==0){
                       CommonService::getInstance()->log4PHP(
                           json_encode([
                               //  __CLASS__.__FUNCTION__ .__LINE__,
                               [
                                   '上传公开联系人'=>[
                                       '已执行' => $i ,
                                       '$dbArr' => $dbArr ,
                                   ]
                               ]
                           ], JSON_UNESCAPED_UNICODE)
                       );
                   }
                   $i ++;
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
