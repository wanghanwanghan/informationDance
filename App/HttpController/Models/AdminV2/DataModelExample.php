<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class DataModelExample extends ModelBase
{

    protected $tableName = 'data_example';

    static  $state_init = 1;
    static  $state_init_cname =  '内容生成中';

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
        ];
    }

    static  function  addRecordV2($info){

        if(
            self::findByBatch($info['batch'])
        ){
            return  true;
        }

        return AdminUserFinanceExportDataQueue::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){

        try {
           $res =  AdminUserFinanceExportDataQueue::create()->data([
                'upload_record_id' => $requestData['upload_record_id'],
                'status' => $requestData['status']?:1,
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }

    public static function cost(){
        $start = microtime(true);
        $startMemory = memory_get_usage();

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'memory_use' => round((memory_get_usage()-$startMemory)/1024/1024,3).' M',
                'costs_seconds '=> number_format(microtime(true) - $start,3)
            ])
        );
    }

    public static function findAllByCondition($whereArr){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where($whereArr)
            ->all();
        return $res;
    }
    /*
     示范：
    [
        'user_id' => [
            'not_empty' => 1,
            'field_name' => 'user_id',
            'err_msg' => '',
        ]
    ]

     * */
    public static function checkField($configs,$requestData){
        foreach ($configs as $configItem){
            if(
                $configItem['not_empty']
            ){
                if(
                    empty($requestData[$configItem['field_name']])
                ){
                    return [
                        'res' => false,
                        'msgs'=>$configItem['err_msg'],
                    ];
                }
            };

            if(
                isset($configItem['bigger_than'])
            ){
                if(
                    $requestData[$configItem['field_name']] < $configItem['bigger_than']
                ){
                    return [
                        'res' => false,
                        'msgs'=>$configItem['err_msg'],
                    ];
                }
            };

            if(
                isset($configItem['less_than'])
            ){
                if(
                    $requestData[$configItem['field_name']] > $configItem['less_than']
                ){
                    CommonService::getInstance()->log4PHP(json_encode(
                            [
                                'less_than check false '  ,
                                'params 1' => $requestData[$configItem['field_name']],
                                'params 2' => $configItem['less_than']
                            ]
                        ));
                    return [
                        'res' => false,
                        'msgs'=>$configItem['err_msg'],
                    ];
                }
            };

            if(
                !empty($configItem['in_array'])
            ){
                if(
                   !in_array( $requestData[$configItem['field_name']] ,$configItem['in_array'])
                ){
                    return [
                        'res' => false,
                        'msgs'=>$configItem['err_msg'],
                    ];
                }
            };
        }
        return [
            'res' => true,
            'msgs'=>'',
        ];
    }


    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceUploadRecord::findById($id);

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
        $model = AdminUserFinanceExportDataQueue::create()
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
        $model = AdminUserFinanceExportDataQueue::create();
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
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = AdminUserFinanceExportDataQueue::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `admin_new_user` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    static function dealUploadFiles($files){
        $succeedNums = 0;
        $fileNames = [];
        foreach ($files as $key => $oneFile) {
            try {
                $fileName = $oneFile->getClientFilename();
                $fileName = date('YmdHis').'_'.$fileName;
                $fileNames[] = $fileName;
                $path = TEMP_FILE_PATH . $fileName;
                $res = $oneFile->moveTo($path);
                $succeedNums ++;
            } catch (\Throwable $e) {
                return  [
                    'msg' => $e->getMessage(),
                    'res' => 'failed',
                ];
            }
        }

        return  [
            'succeedNums' => $succeedNums,
            'fileNames' => $fileNames,
            'res' => 'succeed',
        ];
    }

    static function getYieldData($xlsx_name,$workPath){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => $workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        while (true) {

            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            $value0 = self::strtr_func($one[0]);
            $value1 = self::strtr_func($one[1]);
            $value2 = self::strtr_func($one[2]);
            $value3 = self::strtr_func($one[3]);
            $value4 = self::strtr_func($one[4]);
            $value5 = self::strtr_func($one[5]);
            $value6 = self::strtr_func($one[6]);
            $value6 = self::strtr_func($one[7]);
            $value8 = self::strtr_func($one[8]);
            $value9 = self::strtr_func($one[9]);
            $tmpData = [
                $value0,
                $value1,
                $value2,
                $value3,
            ] ;
            yield $datas[] = $tmpData;
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
