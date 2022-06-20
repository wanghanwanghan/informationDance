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
//                'user_id' => $postData['user_id'],
                'year' => $postData['year'],   
                'VENDINC' => $postData['VENDINC']?:'',
                'ASSGRO' => $postData['ASSGRO']?:'',
                'MAIBUSINC' => $postData['MAIBUSINC']?:'',
                'TOTEQU' => $postData['TOTEQU']?:'',
                'RATGRO' => $postData['RATGRO']?:'',
                'PROGRO' => $postData['PROGRO']?:'',
                'NETINC' => $postData['NETINC']?:'',
                'SOCNUM' => $postData['SOCNUM']?:'',
                'EMPNUM' => $postData['EMPNUM']?:'',
                'status' => $postData['status']?:1,
                'last_pull_api_time' => $postData['last_pull_api_time']?:'',
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

    public static function findById($id){
        $res =  NewFinanceData::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByEntAndYear($entName,$year){
        $res =  NewFinanceData::create()
            ->where('entName',$entName)
            ->where('year',$year)
            ->get();
        return $res;
    }

    static function getFieldCname(){
        return [
            'entName' => '企业名称',
            'year' => '年度',
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
        ];


        //19   () ROE_BL
        //20资产负债率 DEBTL
        //21权益乘数 EQUITYL
        //22主营业务比率 MAIBUSINC_RATIOL

        //23净资产负债率 NALR
        //24营业利润率 OPM
        //25资本保值增值率 ROCA
        //26营业净利率 NOR
        //27总资产利润率 PMOTA
        //28税收负担率 TBR
        //29权益乘数 EQUITYL_new

        //30资产总额同比 ASSGRO_yoy
        //31负债总额同比 LIAGRO_yoy
        //32营业总收入同比 VENDINC_yoy
        //33主营业务收入同比 MAIBUSINC_yoy
        //34利润总额同比 PROGRO_yoy
        //35净利润同比 NETINC_yoy
        //36纳税总额同比 RATGRO_yoy
        //37所有者权益同比 TOTEQU_yoy

        //38税收负担率 TBR_new
        //39社保人数同比 SOCNUM_yoy

        //40净资产同比 C_ASSGROL_yoy
        //41平均资产总额同比 A_ASSGROL_yoy
        //42平均净资产同比 CA_ASSGROL_yoy
        //43企业人均产值同比 A_VENDINCL_yoy
        //44企业人均盈利同比 A_PROGROL_yoy

        //45营业总收入复合增速（两年） VENDINC_CGR
        //46营业总收入同比的平均（两年） VENDINC_yoy_ave_2
        //47净利润同比的平均（两年） NETINC_yoy_ave_2
        //48主营业务净利润率 NPMOMB
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
                'exportFinanceToXlsx ',
                $uploadId,$financeDatas
            ])
        );
        $uploadRes = AdminUserFinanceUploadRecord::findById($uploadId)->toArray();

        $config = [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];
        $filename = date('YmdHis'). '_'.$uploadRes['file_name'];
        CommonService::getInstance()->log4PHP(
            json_encode([
                '$config ' => $config,
                '$filename' => $filename
            ])
        );
        $header = array_merge(
            [
                '企业名称',
                '年度',
            ],self::getExportHeaders($uploadId));
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
