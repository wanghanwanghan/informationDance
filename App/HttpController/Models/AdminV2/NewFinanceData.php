<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use Vtiful\Kernel\Format;

// use App\HttpController\Models\AdminRole;

class NewFinanceData extends ModelBase
{
    protected $tableName = 'new_finance_data';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function getByEntNameAndYear($entName,$year){
        $res = NewFinanceData::create()
            ->where(['entName' => $entName , 'year' => $year])
            ->get(); 

        // CommonService::getInstance()->log4PHP(
        //     [ 'res' =>$res]
        //  );
        return $res;
    } 
    
    public static function addRecord(
        $postData
    ){
        try {
           $res =  NewFinanceData::create()->data([
                'entName' => $postData['entName'],
                'year' => $postData['year'],   
                'VENDINC' => $postData['VENDINC']?:'',//
                'C_ASSGROL' => $postData['C_ASSGROL']?:'',//
                'A_ASSGROL' => $postData['A_ASSGROL']?:'',//
                'CA_ASSGRO' => $postData['CA_ASSGRO']?:'',//
                'C_INTRATESL' => $postData['C_INTRATESL']?:'',//
                'ASSGRO_C_INTRATESL' => $postData['ASSGRO_C_INTRATESL']?:'',//
                'A_VENDINCL' => $postData['A_VENDINCL']?:'',//
                'EQUITYL' => $postData['EQUITYL']?:'',//
                'ATOL' => $postData['ATOL']?:'',//
                'A_PROGROL' => $postData['A_PROGROL']?:'',//
                'DEBTL' => $postData['DEBTL']?:'',//
                'ROA' => $postData['ROA']?:'',//
                'ROAL' => $postData['ROAL']?:'',//
                'ROE_AL' => $postData['ROE_AL']?:'',//
                'ROE' => $postData['ROE_AL']?:'',//
                'ROEA' => $postData['ROEA']?:'',//
                'ROEB' => $postData['ROEB']?:'',//
               'NOR' => $postData['NOR']?:'',//
                'MAIBUSINC_RATIOL' => $postData['MAIBUSINC_RATIOL']?:'',//
               'NALR' => $postData['NALR']?:'',//
               'OPM' => $postData['OPM']?:'',//
               'ROCA' => $postData['ROCA']?:'',//
               'PMOTA' => $postData['PMOTA']?:'',//
               'TBR' => $postData['TBR']?:'',//
               'EQUITYL_new' => $postData['EQUITYL_new']?:'',//
               'LIAGRO_yoy' => $postData['LIAGRO_yoy']?:'',//
               'VENDINC_yoy' => $postData['VENDINC_yoy']?:'',//
               'MAIBUSINC_yoy' => $postData['VENDINC_yoy']?:'',//
               'PROGRO_yoy' => $postData['PROGRO_yoy']?:'',//
               'NETINC_yoy' => $postData['NETINC_yoy']?:'',//
               'RATGRO_yoy' => $postData['RATGRO_yoy']?:'',//
               'TOTEQU_yoy' => $postData['TOTEQU_yoy']?:'',//
               'SOCNUM_yoy' => $postData['SOCNUM_yoy']?:'',//
               'TBR_new' => $postData['TBR_new']?:'',//
               'C_ASSGROL_yoy' => $postData['C_ASSGROL_yoy']?:'',//
               'A_ASSGROL_yoy' => $postData['A_ASSGROL_yoy']?:'',//
               'CA_ASSGROL_yoy' => $postData['CA_ASSGROL_yoy']?:'',//
               'A_VENDINCL_yoy' => $postData['A_VENDINCL_yoy']?:'',//
               'A_PROGROL_yoy' => $postData['A_PROGROL_yoy']?:'',//
               'VENDINC_CGR' => $postData['VENDINC_CGR']?:'',//
               'VENDINC_yoy_ave_2' => $postData['VENDINC_yoy_ave_2']?:'',//
               'NETINC_yoy_ave_2' => $postData['NETINC_yoy_ave_2']?:'',//
               'NPMOMB' => $postData['NPMOMB']?:'',//
               'LIAGRO' => $postData['LIAGRO']?:'',
                'ASSGRO' => $postData['ASSGRO']?:'',
                'MAIBUSINC' => $postData['MAIBUSINC']?:'',
                'TOTEQU' => $postData['TOTEQU']?:'',
                'RATGRO' => $postData['RATGRO']?:'',
                'PROGRO' => $postData['PROGRO']?:'',
                'NETINC' => $postData['NETINC']?:'',
                'SOCNUM' => $postData['SOCNUM']?:'',
                'EMPNUM' => $postData['EMPNUM']?:'',//
               'raw_return' => $postData['raw_return']?:'',//
                'status' => $postData['status']?:1,
                'last_pull_api_time' => $postData['last_pull_api_time']?:date('Y-m-d H:i:s'),
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'addCarInsuranceInfo Throwable continue',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    }

    public static function addRecordV2(
        $postData
    ){
         $res = self::findByEntAndYear($postData['entName'], $postData['year']
         );
         if($res){
             return  $res->getAttr('id');
         }

        return self::addRecord($postData);
    }

    public static function findByCondition($whereArr,$limit){
        $res =  NewFinanceData::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }
    public static function findALl(){
        $res =  NewFinanceData::create()
            ->all();
        return $res;
    }


    public static function findById($id){
        $res =  NewFinanceData::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    //将字段设置为有无
    public static function findByIdV2($id,$needsChangeFields){
        $res =  self::findById($id);
        $data = $res->toArray();
        $newData = [];
        foreach ($data as $field => $dataItem){
               if(
                   $needsChangeFields[$field]
               ){
                   $checkRes = '无';
                   if(
                       $dataItem > 0 ||
                       $dataItem < 0
                   ){
                       $checkRes = '有';
                   }
                     $newData[$needsChangeFields[$field]] =  $checkRes;

//                   CommonService::getInstance()->log4PHP(
//                       json_encode([
//                           'findByIdV2  '=> 'start',
//                           'params $field' => $field,
//                           '$needsChangeFields[$field]' =>$needsChangeFields[$field],
//                           '$checkRes' =>$checkRes,
//                       ])
//                   );
               }
               else{
                   //$newData[$field] = empty($dataItem)?'无':'有';
               }
        }
        return $newData;
    }

    public static function findByEntAndYear($entName,$year){
        $res =  NewFinanceData::create()
            ->where('entName',$entName)
            ->where('year',$year)
            ->get();
        return $res;
    }

    static function getFieldCname($getALl = true){
        $rawArr = [
            'ASSGRO' => '资产总额',
            'LIAGRO' => '负债总额',
            'VENDINC' => '营业总收入',
            'MAIBUSINC' => '主营业务收入',
            'PROGRO' => '利润总额',
            'NETINC' => '净利润',
            'RATGRO' => '纳税总额',
            'TOTEQU' => '所有者权益',
            'SOCNUM' => '社保人数',
            'C_ASSGROL' => '净资产',
            'A_ASSGROL' => '平均资产总额',
            'CA_ASSGRO' => '平均净资产',
            'C_INTRATESL' => '净利率',
            'ATOL' => '资产周转率',
            'ASSGRO_C_INTRATESL' => '总资产净利率',
            'A_VENDINCL' => '企业人均产值',
            'A_PROGROL' => '企业人均盈利',
            'ROA' => '总资产回报率',
            'ROAL' => '总资产回报率',
            'ROE_AL' => '净资产回报率',
            'ROE' => '净资产回报率',
            'ROEA' => '净资产回报率',
            'ROEB' => '净资产回报率',
            'DEBTL' => '资产负债率',
            'EQUITYL' => '权益乘数',
            'MAIBUSINC_RATIOL' => '主营业务比率',
            'NALR' => '净资产负债率',
            'OPM' => '营业利润率',
            'ROCA' => '资本保值增值率',
            'NOR' => '营业净利率',
            'PMOTA' => '总资产利润率',
            'TBR' => '税收负担率',
            'EQUITYL_new' => '权益乘数',
            'LIAGRO_yoy' => '负债总额同比',
            'VENDINC_yoy' => '营业总收入同比',
            'MAIBUSINC_yoy' => '主营业务收入同比',
            'PROGRO_yoy' => '利润总额同比',
            'NETINC_yoy' => '净利润同比',
            'RATGRO_yoy' => '纳税总额同比',
            'TOTEQU_yoy' => '所有者权益同比',
            'TBR_new' => '税收负担率',
            'SOCNUM_yoy' => '社保人数同比',
            'C_ASSGROL_yoy' => '净资产同比',
            'A_ASSGROL_yoy' => '平均资产总额同比',
            'CA_ASSGROL_yoy' => '平均净资产同比',
            'A_VENDINCL_yoy' => '企业人均产值同比',
            'A_PROGROL_yoy' => '企业人均盈利同比',
            'VENDINC_CGR' => '营业总收入复合增速（两年）',
            'VENDINC_yoy_ave_2' => '营业总收入同比的平均（两年）',
            'NETINC_yoy_ave_2' => '净利润同比的平均（两年）',
            'NPMOMB' => '主营业务净利润率',
            'EMPNUM'    => '从业人数',
        ];
        if($getALl){
            return  array_merge(
                [
                    'entName' => '企业名称',
                    'year' => '年度',
                ],
                $rawArr
            );
        }

        return  $rawArr;
    }
    static function getExportHeaders($uploadId){
        $allowedFields = AdminUserFinanceUploadRecord::getAllowedFieldArray($uploadId);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'getExportHeaders  $allowedFields ',
                $allowedFields
            ])
        );
        $headers = [];
        $allFields = NewFinanceData::getFieldCname();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'getExportHeaders  $allFields ',
                $allFields
            ])
        );
        foreach ($allowedFields as $field){
            $headers[] = $allFields[$field];
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'getExportHeaders  $field2 ',
                    $field
                ])
            );
        }

        return $headers;
    }
    static function exportFinanceToXlsx($uploadId,$financeDatas){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'new finance data   exportFinanceToXlsx  '=> 'start',
                'params $uploadId' => $uploadId,
                'params $financeDatas' => $financeDatas,
            ])
        );
        $uploadRes = AdminUserFinanceUploadRecord::findById($uploadId)->toArray();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'new finance data   exportFinanceToXlsx  '=> 'find $uploadRes',
                'params $uploadId' => $uploadId,
                'params $uploadRes' => $uploadRes,
            ])
        );
        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $pathinfo = pathinfo($uploadRes['file_name']);
        $filename = $pathinfo['filename'].'_'.date('YmdHis').'.'.$pathinfo['extension'];
        CommonService::getInstance()->log4PHP(
            json_encode([
                '$config ' => $config,
                '$filename' => $filename
            ])
        ); 
        $header = self::getExportHeaders($uploadId);
        CommonService::getInstance()->log4PHP(
            json_encode([
                '$header ',
                $header
            ])
        );
        $exportDataToXlsRes = self::parseDataToXls(
            $config,$filename,$header,$financeDatas,'sheet1'
        );

        return [
            'path' => '/Static/Temp/',
            'filename' => $filename
        ];
    }
    static  function  parseDataToXls($config,$filename,$header,$exportData,$sheetName){

        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, $sheetName);
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
            ->header($header)
            ->defaultFormat($alignStyle)
            ->data($exportData)
            // ->setColumn('B:B', 50)
        ;

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource();

        return $fileObject->output();
    }


    public static function setStatus($id,$status){
        CommonService::getInstance()->log4PHP(
            [
                'NewFinanceData setStatus',
                $id,$status,
            ]
        );
        $info = AdminUserFinanceExportDataQueue::findById($id);

        return $info->update([
            'status' => $status,
        ]);
    }

}
