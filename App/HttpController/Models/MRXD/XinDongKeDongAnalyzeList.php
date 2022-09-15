<?php

namespace App\HttpController\Models\MRXD;

use App\ElasticSearch\Model\Company;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;

// use App\HttpController\Models\AdminRole;

class XinDongKeDongAnalyzeList extends ModelBase
{

    protected $tableName = 'xin_dong_ke_dong_analyze_list';

    static  $state_del = 1;
    static  $state_del_cname =  '已删除';
    static  $state_ok = 0;
    static  $state_ok_cname =  '正常';

    static $status_init = 0;
    static $status_init_cname =  '初始';

    static function getDelStateMap(){
        return [
            self::$state_del =>self::$state_del_cname,
            self::$state_ok =>self::$state_ok_cname,
        ];
    }

    static  function  addRecordV2($info){
        $oldRes = self::findByEntName($info['user_id'],$info['ent_name']);
        //如果被删除了 重新找回来
        $oldRes2 = self::findByEntNameV3($info['user_id'],$info['ent_name']);
        if($oldRes2){
            return self::updateById(
                $oldRes2->getAttr('id'),
                [
                    'is_del' => self::$state_ok_cname
                ]
            );
        }
        if( $oldRes ){
            return  $oldRes->getAttr('id');
        }

        return XinDongKeDongAnalyzeList::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  XinDongKeDongAnalyzeList::create()->data([
                'user_id' => $requestData['user_id'],
                'companyid' => $requestData['companyid'],
                'is_del' => intval($requestData['is_del']),
                'status' => intval($requestData['status']),
                'name' => $requestData['name']?:'',
                'ent_name' => $requestData['ent_name']?:'',
                'remark' => $requestData['remark']?:'',
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'err_msg' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }

    /**
    从es筛选
    用户：
    企业分：

     *
     */
    public static function searchFromEs($whereArr,$page,$limit){

        //Company::serachFromEs();

        $model = XinDongKeDongAnalyzeList::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$limit)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();
        foreach ($res as &$data){
            if($data['companyid'] <=0){
                continue;
            }
            $res = Company::serachFromEs(
                [
                    'companyids' => $data['companyid'],
                    'size' => 1,
                    'page' => 1,
                ]
            );

            //营收规模
            $data['ying_shou_gui_mo'] = $res['data'][0]['_source']['ying_shou_gui_mo'];
            if($data['ying_shou_gui_mo']){
                $data['ying_shou_gui_mo'] =  XinDongService::mapYingShouGuiMo()[$data['ying_shou_gui_mo']];
            }

            //地域
            $data['DOMDISTRICT'] = $res['data'][0]['_source']['DOMDISTRICT'];
            if($data['DOMDISTRICT']){
                $data['DOMDISTRICT'] =  Company::findRegion($data['DOMDISTRICT']);
            }

            //团队规模
            $data['tuan_dui_ren_shu'] = $res['data'][0]['_source']['tuan_dui_ren_shu'];

            // 营业期限开始日期  OPFROM
            $data['OPFROM'] = $res['data'][0]['_source']['OPFROM'];

            /****
            "companyid": "83830977",
            "ENTNAME": "\u9e21\u4e1c\u53bf\u94f6\u79cb\u654f\u8fbe\u7cae\u98df\u8d38\u6613\u6709\u9650\u516c\u53f8",
            "UNISCID": "91230321MA1CKWLP4M",
            "REGNO": "",
            "NACAOID": "MA1CKWLP4",
            "NAME": "\u675c\u8273\u79cb",
            "NAMETITLE": "",
            "ENTTYPE": "1151",
            "ESDATE": "2021-05-25",
            "APPRDATE": "2021-05-25",
            "ENTSTATUS": "1",
            "REGCAP": "300",
            "REGCAP_NAME": "",
            "REGCAPCUR": "156",
            "RECCAP": "0",
            "REGORG": "230321",
            "OPFROM": "2021-05-25",
            "OPTO": "",
            "OPSCOPE": "\u8c37\u7269,\u8c46\u53ca\u85af\u7c7b\u9500\u552e;\u8c37\u7269\u78e8\u5236;\u7cae\u98df\u6536\u8d2d,\u70d8\u5e72,\u4ed3\u50a8.",
            "DOM": "\u9ed1\u9f99\u6c5f\u7701\u9e21\u897f\u5e02\u9e21\u4e1c\u53bf\u5e73\u9633\u9547\u6c38\u5174\u6751",
            "DOMDISTRICT": "230321",
            "NIC_ID": "F52",
            "CANDATE": "",
            "REVDATE": "",
            "updated": "2022-04-11 10:12:47",
            "ying_shou_gui_mo": "",
            "nic_full_name": "\u6279\u53d1\u548c\u96f6\u552e\u4e1a-\u96f6\u552e\u4e1a",
            "market_share": {
            "ent_market_share": {
            "top": "",
            "bottom": ""
            },
            "top": "601960387",
            "bottom": "713433923",
            "ent_num": "102956"
            },
            "gong_si_jian_jie": "",
            "gao_xin_ji_shu": "",
            "deng_ling_qi_ye": "",
            "tuan_dui_ren_shu": "",
            "tong_xun_di_zhi": "",
            "web": "",
            "yi_ban_ren": "",
            "shang_shi_xin_xi": "",
            "app": "",
            "manager": "",
            "inv": "",
            "email": "",
            "wu_liu_xin_xi": "",
            "szjjcy": "050602",
            "zlxxcy": "",
            "app_data": [],
            "shang_pin_data": [],
            "report_year": [],
            "iso": "",
            "jin_chu_kou": "",
            "location": {
            "type": "point",
            "coordinates": [131.237956, 45.149395]
            },
            "estiblish_time": "",
            "from_time": "",
            "to_time": "",
            "approved_time": "",
            "ENTTYPE_CNAME": "",
            "ENTSTATUS_CNAME": "",
            "gong_si_jian_jie_data_arr": []
             */

            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'serachFromEs' =>  $res['data'][0]['_source'],
                    ]
                ])
            );

        }

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findAllByCondition($whereArr){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = XinDongKeDongAnalyzeList::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    //软删除
    public static function delRecord($id){
        $info = XinDongKeDongAnalyzeList::findById($id);
        return $info->update([
            'is_del' => self::$state_del,
        ]);
    }


    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = XinDongKeDongAnalyzeList::create()
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

    public static function findByConditionV2($whereArr,$page, $size){
        $model = XinDongKeDongAnalyzeList::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$size)
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
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByUserId($userId){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$userId)
            ->all();
        return $res;
    }

    public static function findByEntName($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->get();
        return $res;
    }

    public static function findByUser($user_id){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$user_id)
            ->where('is_del',0)
            ->get();
        return $res;
    }


    public static function findByEntNameV2($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',0)
            ->get();
        return $res;
    }

    public static function findByEntNameV3($user_id,$ent_name){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$user_id)
            ->where('ent_name',$ent_name)
            ->where('is_del',1)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = XinDongKeDongAnalyzeList::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `xin_dong_ke_dong_analyze_list` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    //注意  这里是异步调用的  是mysql 队列调用的
    static function addRecordByFile($paramsArr){
        $records = DataModelExample::getYieldData(
            $paramsArr['file'],
            TEMP_FILE_PATH
        );
        $return = [];
        foreach ($records as $record){
            $entName = trim($paramsArr['ent_name']);
            if(empty($entName)){
                continue;
            }
            $res = self::addRecordV2(
                [
                    'user_id' => $paramsArr['user_id'],
                    'is_del' => XinDongKeDongAnalyzeList::$state_ok,
                    'status' => XinDongKeDongAnalyzeList::$status_init,
                    'name' => $paramsArr['name']?:'',
                    'ent_name' => $entName?:'',
                    'remark' => $paramsArr['remark']?:'',
                ]
            );
            $return[] = $res;
        }

        return $return;
    }
    static function calYearsNums($date){
        $yearsNums =  date('Y') - date('Y',strtotime($date));
        if($yearsNums<=2){
            return '0-2';
        }
        if(
            $yearsNums >= 2 &&
            $yearsNums <= 5
        ){
            return '2-5';
        }
        if(
            $yearsNums >= 5 &&
            $yearsNums <= 10
        ){
            return '5-10';
        }
        if(
            $yearsNums >= 10 &&
            $yearsNums <= 15
        ){
            return '10-15';
        }
        if(
            $yearsNums >= 15 &&
            $yearsNums <= 20
        ){
            return '10-15';
        }
        if(
            $yearsNums >= 20
        ){
            return '20年以上';
        }
    }
    //提取用户传的目标客户的特征
    static function extractFeature($userId,$returnRaw =false){
        //找到所有的目标客户群体
        $fields = [
            '营收规模'=>'ying_shou_gui_mo',
            '国标行业'=>'NIC_ID',
            '所在行政区划'=>'DOMDISTRICT',
        ];
        $fields2 = [
            'OPFROM'=>[
                'des'=>'营业期限开始日期',
                'filed'=>'OPFROM',
                'static_func'=>'calYearsNums',
            ],
        ];
        $res = [];
        $lists = XinDongKeDongAnalyzeList::findAllByUserId($userId);
        foreach ($lists as $list){
            $tmpEsData = \App\ElasticSearch\Model\Company::getNamesByText(
                1,
                1,
                $list['ent_name'],
                true
            );
            foreach ($tmpEsData['hits']['hits'] as $esData){
                //直接比较的字段
                foreach ($fields as $field){
                    if(empty($esData['_source'][$field])){
                        continue;
                    }
                    $res[$field][$esData['_source'][$field]] += 1 ;
                }
                //需要计算的字段
                foreach ($fields2 as $field){
                    if(empty($esData['_source'][$field]['filed'])){
                        continue;
                    }
                    if($returnRaw){
                        $newRes = $esData['_source'][$field['filed']];
                    }
                    else{
                        $newRes = self::$field['static_func']($esData['_source'][$field['filed']]);
                    }
                    $res[$field][$newRes] += 1 ;
                }
            }
        }

        $returnData = [];
        foreach ($res as $field=>$fieldValue){
            asort($fieldValue) ;
            $tmp = array_keys($fieldValue);
            $returnData[$field] =  end($tmp);
        }

        //开始分析
        return $returnData;
    }

    static function getFeatrueArray($userId){
        $returnData = [];

        $rawData = self::extractFeatureV2($userId,false,true);
        $nicIdsArr = $rawData['NIC_ID'];
        $allNicScore = array_sum($nicIdsArr);

        foreach ($nicIdsArr as $key => $value){
            $returnData['nicX'][] = $key;
            $tmpres =
            $returnData['nicY'][] = number_format($value/$allNicScore,2,".",".")*100;
        }

        $OPFROMArr = $rawData['OPFROM'];
        $allOPFROMScore = array_sum($OPFROMArr);
        foreach ($OPFROMArr as $key => $value){
            $returnData['openFromX'][] = $key;
            $returnData['openFromY'][] = number_format($value/$allOPFROMScore,2,".",".")*100;
        }

        $ying_shou_gui_moArr = $rawData['ying_shou_gui_mo'];
        $allYingSHouGuiMoScore = array_sum($ying_shou_gui_moArr);
        foreach ($ying_shou_gui_moArr as $key => $value){
            $returnData['YingShouX'][] = $key;
            $returnData['YingShouY'][] = number_format($value/$allYingSHouGuiMoScore,2,".",".")*100;
        }


        $DOMDISTRICTArr = $rawData['DOMDISTRICT'];
        $allDOMDISTRICTScore = array_sum($DOMDISTRICTArr);
        foreach ($DOMDISTRICTArr as $key => $value){
            $returnData['areaX'][] = $key;
            $returnData['areaY'][] = number_format($value/$allDOMDISTRICTScore,2,".",".")*100;
        }

        /***
        {
        "NIC_ID": {
        "F521": 1,
        "C1469": 1,
        "F5179": 1,
        "M7590": 1,
        "F522": 1,
        "C1495": 1,
        "7210": 1
        },
        "OPFROM": {
        "10-15": 2,
        "0-2": 1,
        "2-5": 3,
        "20年以上": 1
        },
        "ying_shou_gui_mo": {
        "F": 2,
        "A3": 1,
        "A15": 1
        },
        "DOMDISTRICT": {
        "371000": 1,
        "440300": 1,
        "110108": 1,
        "371482": 1,
        "120118": 1
        }
        }
         */

    return $returnData;
    }

    static function extractFeatureV2($userId, $returnRaw =false, $retrunAllData = false){
        //找到所有的目标客户群体
        $fields = [
            '营收规模'=>'ying_shou_gui_mo',
            '国标行业'=>'NIC_ID',
            '所在行政区划'=>'DOMDISTRICT',
        ];
        $fields2 = [
            'OPFROM'=>[
                'des'=>'营业期限开始日期',
                'filed'=>'OPFROM',
                'static_func'=>'calYearsNums',
            ],
        ];
        $res = [];
        $lists = XinDongKeDongAnalyzeList::findAllByUserId($userId);
        $companyIds = array_column($lists,'companyid');
        $esRes = \App\ElasticSearch\Model\Company::serachFromEs(
            [
                'companyids' => join(',',$companyIds),
            ],
            [

            ]
        ) ;
        foreach ($esRes['data'] as $esData){
            //直接比较的字段
            foreach ($fields as $field){
                if(empty($esData['_source'][$field])){
                    continue;
                }
                $res[$field][$esData['_source'][$field]] += 1 ;
            }
            //需要计算的字段
            foreach ($fields2 as $field){
                if(empty($esData['_source'][$field['filed']])){
                    continue;
                }
                if($returnRaw){
                    $newRes = $esData['_source'][$field['filed']];
                }
                else{
                    $tmpRes = $field['static_func'];
                    $newRes = self::$tmpRes($esData['_source'][$field['filed']]);
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__.__FUNCTION__ .__LINE__,
                            [
                                $esData['_source'][$field['filed']] => $newRes,
                            ]
                        ])
                    );
                }
                $res[$field['filed']][$newRes] += 1 ;
            }
        }

        if($retrunAllData){
            return  $res;
        }
        $returnData = [];
        foreach ($res as $field=>$fieldValue){
            asort($fieldValue) ;
            $tmp = array_keys($fieldValue);
            $returnData[$field] =  end($tmp);
        }

        //开始分析
        return $returnData;
    }

    static function  updateListsByUser($requestData,$userId){

        $newEntNames = $requestData['entNames'];

        //最新企业名称
//        $newEntNamesArr = explode(',',$newEntNames);
        $newEntNamesArr = json_decode($newEntNames,true);

        //当前所有
        $allLists = XinDongKeDongAnalyzeList::findAllByUserId($userId);
        foreach ($allLists as $data){
            //如果被删除了的话
            if(!in_array($data['ent_name'],$newEntNamesArr)){
                XinDongKeDongAnalyzeList::delRecord($data['id']);
            }
        }

        foreach ($newEntNamesArr as $newEntName){
            if(empty($newEntName)){
                continue;
            }

            $companyBasicRes = CompanyBasic::findByName($newEntName);
            if(empty($companyBasicRes)){
                continue;
            }
            $companyBasicRes = $companyBasicRes->toArray();
            // 添加
            $id = XinDongKeDongAnalyzeList::addRecordV2(
                [
                    'user_id' => $userId,
                    'is_del' => XinDongKeDongAnalyzeList::$state_ok,
                    'status' => XinDongKeDongAnalyzeList::$status_init,
                    'name' => $requestData['name']?:'',
                    'ent_name' => $newEntName,
                    'companyid' => $companyBasicRes['companyid'],
                    'remark' => $requestData['remark']?:'',
                ]
            );

        }

        return true;
    }
}
