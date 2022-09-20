<?php

namespace App\HttpController\Models\MRXD;

use App\ElasticSearch\Model\Company;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;
use Vtiful\Kernel\Format;

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

    static function getStatusMap(){
        return  [
            'score_range'=>[
                5=>'70-80',
                10=>'80-90',
                15=>'90-100',
            ],
            'establish_yeasr_range'=>[
                2=>'2年以内',
                5=>'2-5年',
                10=>'5-10年',
                15=>'10-15年',
                20=>'15-20年',
                25=>'20年以上',
            ],
            'ying_shou_range'=>[
                5=>'微型',
                10=>'小型C类',
                15=>'小型B类',
                20=>'小型A类',
                25=>'中型C类',
                30=>'中型B类',
                40=>'中型A类',
                45=>'大型C类',
                50=>'大型B类',
                60=>'大型A类',
                65=>'特大型C类',
                70=>'特大型B类',
                80=>'特大型A类',
            ]
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
            $esres = Company::serachFromEs(
                [
                    'companyids' => $data['companyid'],
                    'size' => 1,
                    'page' => 1,
                ]
            );

            //营收规模
            $data['ying_shou_gui_mo'] = $esres['data'][0]['_source']['ying_shou_gui_mo'];
            if($data['ying_shou_gui_mo']){
                $data['ying_shou_gui_mo'] =  XinDongService::mapYingShouGuiMo()[$data['ying_shou_gui_mo']];
            }

            //地域
            $data['DOMDISTRICT'] = $esres['data'][0]['_source']['DOMDISTRICT'];
            if($data['DOMDISTRICT']){
                $data['DOMDISTRICT'] =  CompanyBasic::findRegion($data['DOMDISTRICT'])['fulltitle'];
            }

            //团队规模
            $data['tuan_dui_ren_shu'] = $esres['data'][0]['_source']['tuan_dui_ren_shu'];

            // 营业期限开始日期  OPFROM
            $data['OPFROM'] = $esres['data'][0]['_source']['OPFROM'];
            $data['OPFROM'] = $esres['data'][0]['_source']['OPFROM'];

            $data['short_name'] =  CompanyBasic::findBriefName($esres['data'][0]['_source']['ENTNAME']);
            $data['logo'] =  (new XinDongService())->getLogoByEntIdV2($esres['data'][0]['_source']['companyid']);

            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'serachFromEs' =>  $esres['data'][0]['_source'],
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

    public static function findAllByUserIdV2($userId){
        $res =  XinDongKeDongAnalyzeList::create()
            ->where('user_id',$userId)
            ->where('is_del',0)
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
            return '15-20';
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
            $returnData['nicY'][] = number_format($value/$allNicScore,2,".","")*100;

        }

        $OPFROMArr = $rawData['OPFROM'];
        $allOPFROMScore = array_sum($OPFROMArr);
        foreach ($OPFROMArr as $key => $value){
            $returnData['openFromX'][] = $key;
            $returnData['openFromY'][] = number_format($value/$allOPFROMScore,2,".","")*100;
        }

        $ying_shou_gui_moArr = $rawData['ying_shou_gui_mo'];
        $allYingSHouGuiMoScore = array_sum($ying_shou_gui_moArr);
        foreach ($ying_shou_gui_moArr as $key => $value){
            $returnData['YingShouX'][] = $key;
            $returnData['YingShouY'][] = number_format($value/$allYingSHouGuiMoScore,2,".","")*100;
        }


        $DOMDISTRICTArr = $rawData['DOMDISTRICT'];
        $allDOMDISTRICTScore = array_sum($DOMDISTRICTArr);
        foreach ($DOMDISTRICTArr as $key => $value){
            $returnData['areaX'][] = $key;
            $returnData['areaY'][] = number_format($value/$allDOMDISTRICTScore,2,".","")*100;
        }

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
        $lists = XinDongKeDongAnalyzeList::findAllByUserIdV2($userId);
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
//                    CommonService::getInstance()->log4PHP(
//                        json_encode([
//                            __CLASS__.__FUNCTION__ .__LINE__,
//                            [
//                                $esData['_source'][$field['filed']] => $newRes,
//                            ]
//                        ])
//                    );
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

    //导出
    static function  exportRecommendCompanys($requestData,$userId){
        //
        //    $datas = self::searchFromEs();
        $exportNums = 0;
        $minScore = 0 ;
        $maxScore = 0 ;

        //============================分割线=========================================
        $filename = date('Y-m-d H:i:s')."_优企名单.xlsx";
        // xlsx文件保存路径
        $config=  [
            'path' => TEMP_FILE_PATH
        ];

        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, 'sheet');
        $fileHandle = $fileObject->getHandle();

        $format = new Format($fileHandle);
        $colorStyle = $format
            ->fontColor(Format::COLOR_ORANGE)
            ->border(Format::BORDER_DASH_DOT)
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $format = new Format($fileHandle);

        $alignStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $fileObject
            ->defaultFormat($colorStyle)
            ->header([
                'x',
                'xx',
                'xxx',
                'xxxx',
                'xxxxx',
                'xxxxxx',
            ])
            ->defaultFormat($alignStyle)
        ;

        foreach ($datas as $dataItem){
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$dataItem' => $dataItem
//                    ])
//                );
            $tmp = [
                //'xd_id'=>$dataItem['xd_id'],
            ];

            //$tmp['xd_id'] = $dataItem['xd_id'];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        '$dataItem' => $dataItem,
//                        '$featureArr'=>$featureArr,
//                        '$tmp'=>$tmp,
//                    ])
//                );
            $fileObject ->data([$tmp]);
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
            ])
        );

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource();

        $fileObject->output();

        //============================分割线=========================================

        return  [
            'patch' => '/Static/Temp/'.$filename,
            'countnums' => $exportNums,
            'minscore' => $minscore,
        ]
         ;
    }

}
