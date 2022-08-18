<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceChargeLog;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadeRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecordV3;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\InsuranceData;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Models\MRXD\InsuranceDataHuiZhong;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\Mail\Email;
use App\HttpController\Service\Sms\SmsService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use Vtiful\Kernel\Format;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use EasySwoole\RedisPool\Redis;



class RunDealEmailReceiver extends AbstractCronTask
{
    public $crontabBase;
    public $filePath = ROOT_PATH . '/Static/Temp/';
    public static $workPath;

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

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
        $this->createDir(); 
    }

    static function getRule(): string
    {
        return '*/1 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function createDir(): bool
    {
        self::$workPath = $this->filePath ;

        return true;
    }  
    
    static function setworkPath($filePath): bool
    {

        self::$workPath = $filePath ;

        return true;
    }  


     /*
      * step1:上传客户名单
      * step2:定时将客户名单解析到数据库
      * step3:定时算一下价格（大致价格|有的缺失数据的 需要拉完财务数据重算） 算一下总价
      * step4:点击导出-加入到队列
      * step5:跑api数据
      * step7:先去确认
      * step8:重算价格
      * step9:生成文件，扣费，记录导出详情
      * */
    function run(int $taskId, int $workerIndex): bool
    {

        //return  true;
        //防止重复跑
        if(
            !ConfigInfo::checkCrontabIfCanRun(__CLASS__)
        ){
            return     CommonService::getInstance()->log4PHP(json_encode(
                [
                    __CLASS__ . ' is already running  ',
                ]
            ));
        }

        //设置为正在执行中
        ConfigInfo::setIsRunning(__CLASS__);
        //用户咨询后，将询价信息发送给保鸭
        self::sendEmail();
        //用户咨询后，将询价信息发送给挥众
        self::sendEmailHuiZhong();
        //拉取收件箱 单纯拉取邮件
        self::pullEmail(2);
        //收到邮件询价结果后  短信通知
        self::dealMail(date('Y-m-d'));
        //设置为已执行完毕
        ConfigInfo::setIsDone(__CLASS__);

        return true ;   
    }

    //拉取收件箱
    static function  pullEmail($dayNums = 1 ){
        $mail = new Email();
        $emailAddress = CreateConf::getInstance()->getConf('mail.user_receiver');
        $mail->mailConnect(
            CreateConf::getInstance()->getConf('mail.host_receiver'),//'imap.exmail.qq.com',
            CreateConf::getInstance()->getConf('mail.port_receiver'),//'143',
            $emailAddress,//'mail@meirixindong.com',
            CreateConf::getInstance()->getConf('mail.pass_receiver') //'Mrxd1816'
        );
        $date = date ( "d M Y", strToTime ( "-$dayNums days" ) );
        $emailData = $mail->mailListBySinceV2($date);
        $totalCount = $mail->mailTotalCount();
        $msgcount = $totalCount;

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'pull_Email_lists'=>[
                    'msg'=>'start',
                    'pull_by_date'=>$date,
                    'pull_by_date_res_count'=>$totalCount,
                ]
            ])
        );

        //单纯加数据
        foreach ($emailData as $emailDataItem){
            $mailHeader = $mail->mailHeader($msgcount);
            $emailBody = $mail->getBody($msgcount);
            $attachs = $mail->getAttach($msgcount,OTHER_FILE_PATH.'MailAttach/');
            $newFileArr = [];
            foreach ($attachs as $file){
                $newFileArr[]=   '/Static/OtherFile/MailAttach/'.$file;
            }
            $datas =[
                'user_id' => 0,
                'email_id' => $emailDataItem['Uid'],
                'to' => $emailAddress,
                'to_other' => $mailHeader['toOther']?:'',
                'attachs' => empty($newFileArr)?'':json_encode($newFileArr),
                'from' => $mailHeader['from']?:'',
                'subject' => $mailHeader['subject']?stripslashes($mailHeader['subject']):'',
                'body' => $emailBody?:'',
                'status' => '1',
                'type' => '1',
                'reamrk' => '',
                'raw_return' => stripslashes(json_encode($mail)),
                'date' => date('Y-m-d H:i:s',strtotime($mailHeader['date'])) ,
            ];
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'pull_Email_lists_attachs'=>[
                        'msg'=>'add_to_db',
                        '$attachs'=>$attachs,
                        '$mailHeader'=>$mailHeader,
                        'email_id'=>$emailDataItem['Uid'],
                        'subject'=>stripslashes($emailDataItem['mailHeader']['subject']),
                    ]
                ])
            );
            MailReceipt::addRecordV2(
                $datas
            );
            $msgcount  --;
        }

        //单纯发短信|状态控制下 防止强制触发

        return true;
    }
    //收到邮件询价结果后  短信通知
    static  function  dealMail($day){
        $emailAddress = CreateConf::getInstance()->getConf('mail.user_receiver');
        $emails = MailReceipt::findBySql(
            " WHERE  `date`>= '".$day."' AND 
                    `to` = '$emailAddress' AND 
                    `status` =  ".MailReceipt::$status_init." 
                "
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'receive_email.lest_deal_with_it'=>[
                        'deal_by_date'=>$day,
                        'deal_by_sender'=>$emailAddress,
                        'data_count'=>count($emails),
                    ]
                ]
            )
        );
        foreach ($emails as $email){

            if(
               !in_array(
                   $email['from'] ,
                   [
                       'tianyongshan@meirixindong.com',
                       'wanghan@meirixindong.com',
                       'liyunxian@meirixindong.com',
                       'guoxinxia@meirixindong.com',
                       '10000@exmail.weixin.qq.com'
                   ]
               )
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'receive_email.lest_deal_with_it'=>[
                                'msg'=>'invalid_email_sender',
                                'email_sender'=>$email['from'] ,
                                'valid_email_sender_lists'=>[
                                    'tianyongshan@meirixindong.com',
                                    'wanghan@meirixindong.com',
                                    'liyunxian@meirixindong.com',
                                    'guoxinxia@meirixindong.com',
                                    '10000@exmail.weixin.qq.com'
                                ],
                            ]
                        ]
                    )
                );
               //其他人的文件  直接更新为其他状态
                MailReceipt::updateById($email['id'],[
                    'status'=>MailReceipt::$status_succeed
                ]);
                continue;
            }
            else{
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'receive_email.lest_deal_with_it'=>[
                                'msg'=>'valid_email_sender',
                                'email_sender'=>$email['from'] ,
                                'valid_email_sender_lists'=>[
                                    'tianyongshan@meirixindong.com',
                                    'wanghan@meirixindong.com',
                                    'liyunxian@meirixindong.com',
                                    'guoxinxia@meirixindong.com',
                                    '10000@exmail.weixin.qq.com'
                                ],
                            ]
                        ]
                    )
                );
            }

            //解析保险数据id
            preg_match('/信动数据id01:&lt;&lt;&lt;(.*?)&gt;&gt;&gt;/',$email['body'],$match);
            $huizhongId = $match[1];
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'receive_email.lest_deal_with_it'=>[
                            'msg'=>'try_decode_hui_zhong_id',
                            '$huizhongId'=>$huizhongId ,
                        ]
                    ]
                )
            );
            if($huizhongId){
                MailReceipt::updateById($email['id'],[
                    'insurance_hui_zhong_id'=>intval($huizhongId),
                ]);
            }

            preg_match('/信动数据id:&lt;&lt;&lt;(.*?)&gt;&gt;&gt;/',$email['body'],$match);
            $baoyaId = $match[1];
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'receive_email.lest_deal_with_it'=>[
                            'msg'=>'try_decode_bao_ya_id',
                            '$baoyaId'=>$baoyaId ,
                        ]
                    ]
                )
            );
            if($baoyaId){
                MailReceipt::updateById($email['id'],[
                    'insurance_id'=>intval($baoyaId),
                ]);
            } 

            if($baoyaId){
                $InsuranceData = InsuranceData::findById($baoyaId);
            }
            if($huizhongId){
                $InsuranceData = InsuranceDataHuiZhong::findById($huizhongId);
            }

            if(empty($InsuranceData)){
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'receive_email.lest_deal_with_it'=>[
                                'msg'=>'empty_db_record_.decode_id_went_wrong?',
                                '$baoyaId'=>$baoyaId ,
                                '$huizhongId'=>$huizhongId ,
                            ]
                        ]
                    )
                );
                MailReceipt::updateById($email['id'],['status' => MailReceipt::$status_failed]);
                continue;
            }

            $userData = OnlineGoodsUser::findById($InsuranceData->getAttr('user_id'));
            $userData = $userData->toArray();
            //需要发短信了
            $res = SmsService::getInstance()->sendByTemplete(
                $userData['phone'], 'SMS_244025473',[
                'name' => '有询价的结果了',
                'money' => '多钱'
            ]);
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'receive_email.lest_deal_with_it'=>[
                            'msg'=>'send_msg_to_user_.remind_them_to_check_conlsole_res.',
                            'phone'=>$userData['phone'] ,
                            'send_res'=>$res ,
                        ]
                    ]
                )
            );
            OperatorLog::addRecord(
                [
                    'user_id' => $userData['id'],
                    'msg' =>   " 有询价的了",
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '有询价的了',
                ]
            );
            MailReceipt::updateById($email['id'],['status' => MailReceipt::$status_succeed]);
        }
    }
    static  function  getTableHtml($data,$dataRes){
        $html = "
<style>
    body {text-align: center;}

    .styled-table {
        border-collapse: collapse;
        margin: auto;
        font-size: 0.9em;
        font-family: sans-serif;
        min-width: 650px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);

    }
    .styled-table thead tr {
        background-color: #009879;
        color: #ffffff;
        text-align: left;
    }
    .styled-table th,
    .styled-table td {
        padding: 12px 15px;
    }
    .styled-table tbody tr {
        border-bottom: 1px solid #dddddd;
    }

    .styled-table tbody tr:nth-of-type(even) {
        background-color: #f3f3f3;
    }

    .styled-table tbody tr:last-of-type {
        border-bottom: 2px solid #009879;
    }

    .styled-table tbody tr.active-row {
        font-weight: bold;
        color: #009879;
    }

</style>
        ";

        $html .=  '
 
<body>
<table class="styled-table">
    <thead>
        <tr>
            <th colspan="2" style="text-align: center; font-size:20px">保险询价单</th> 
        </tr>
    </thead>
    <tbody> 
';

        $html .=  ' 
        <tr>
            <td>产品</td>
            <td>'.$dataRes['data']['title'].'('.$dataRes['data']['id'].')</td>
        </tr>';
        $html .=  ' 
        <tr>
            <td>描述</td>
            <td>'.$dataRes['data']['description'].'</td>
        </tr>';
        $templetesArr = $dataRes['data']['template'];
        foreach ($templetesArr['fields'] as $fieldItem){
            if($fieldItem['type'] == 'text'){
                 $html .=  ' 
                    <tr>
                        <td>'.$fieldItem['title'].'</td>
                        <td>'.$data[$fieldItem['name']].'</td>
                    </tr>';
             }
            if($fieldItem['type'] == 'file'){
                $html .=  ' 
                    <tr>
                        <td>'.$fieldItem['title'].'</td>
                        
                        <td>
                            <a  
                                href="https://api.meirixindong.com/'.$data[$fieldItem['name']].'">
                                点击查看
                            </a>
                        </td>
                    </tr>';
            }
        }
        $html .=  ' 
                    <tr>
                        <td>ID</td>
                        <td>信动数据id:<<<'.$data['id'].'>>></td>
                    </tr>';
        $html .=  ' 
    </tbody>
</table>
</body> 
';
        return $html;
    }
    static  function  getTableHtmlHuiZhong($data){
        $maps = [
            'ent_name' =>  '企业名称',
            'business_license_file' =>  '营业执照照片',
            'public_account' =>  '公户账号',
            'legal_person_phone' =>  '法人手机号',
            //'business_license' =>  '营业执照',
            'id_card_front_file' =>  '身份证照片正面',
            'id_card_back_file' =>  '身份证照片反面',
        ];
        $fileMap = [
            'business_license_file',
            'id_card_front_file',
            'id_card_back_file',
        ];
        $html = "
<style>
    body {text-align: center;}

    .styled-table {
        border-collapse: collapse;
        margin: auto;
        font-size: 0.9em;
        font-family: sans-serif;
        min-width: 650px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);

    }
    .styled-table thead tr {
        background-color: #009879;
        color: #ffffff;
        text-align: left;
    }
    .styled-table th,
    .styled-table td {
        padding: 12px 15px;
    }
    .styled-table tbody tr {
        border-bottom: 1px solid #dddddd;
    }

    .styled-table tbody tr:nth-of-type(even) {
        background-color: #f3f3f3;
    }

    .styled-table tbody tr:last-of-type {
        border-bottom: 2px solid #009879;
    }

    .styled-table tbody tr.active-row {
        font-weight: bold;
        color: #009879;
    }

</style>
        ";

        $html .=  '
 
<body>
<table class="styled-table">
    <thead>
        <tr>
            <th colspan="2" style="text-align: center; font-size:20px">车险分期预授信</th> 
        </tr>
    </thead>
    <tbody> 
';

//        <tr>
//            <td>描述</td>
//            <td>这一款产品</td>
//        </tr>
        $html .=  ' 
        <tr>
            <td>产品</td>
            <td>车险分期</td>
        </tr>';
        $html .=  ' 
        
        ';

        foreach ($maps as $fieldItem=>$cname){

            if(in_array($fieldItem,$fileMap)){
                $html .=  ' 
                    <tr>
                        <td>'.$cname.'</td>
                        
                        <td>
                            <a  
                                href="https://api.meirixindong.com/'.$data[$fieldItem].'">
                                点击查看
                            </a>
                        </td>
                    </tr>';

            } else{
                $html .=  ' 
                    <tr>
                        <td>'.$cname.'</td>
                        <td>'.$data[$fieldItem].'</td>
                    </tr>';
            }
        }
        $html .=  ' 
                    <tr>
                        <td>ID</td>
                        <td>信动数据id01:<<<'.$data['id'].'>>></td>
                    </tr>';
        $html .=  ' 
    </tbody>
</table>
</body> 
';
        return $html;
    }

    //用户咨询后，将询价信息发送给保鸭
    static function sendEmail()
    {
        //保鸭
        $datas = InsuranceData::findBySql(
            " WHERE  
                    `status` =  ".InsuranceData::$status_init." 
                "
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'send_consloe_data_to_bao_ya'=> [
                    'msg'=>'start',
                    'data_count'=>count($datas),
                ],
            ])
        );

        foreach ($datas as $data){
            $insuranceDatas  = json_decode($data['post_params'],true);
            $dataRes = (new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail
            (
                $insuranceDatas['product_id']
            );
            $insuranceDatas['id'] = $data['id'];
            $tableHtml = self::getTableHtml($insuranceDatas,$dataRes);
            $res1 = CommonService::getInstance()->sendEmailV2(
                'tianyongshan@meirixindong.com',
                // 'minglongoc@me.com',
                '询价'.$dataRes['data']['title'],
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            $res2 = CommonService::getInstance()->sendEmailV2(
                'wanghan@meirixindong.com',
                // 'minglongoc@me.com',
                '询价'.$dataRes['data']['title'],
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            $res3 = CommonService::getInstance()->sendEmailV2(
                'guoxinxia@meirixindong.com',
                // 'minglongoc@me.com',
                '询价'.$dataRes['data']['title'],
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );

            $res4 = CommonService::getInstance()->sendEmailV2(
                'liyunxian@meirixindong.com',
                // 'minglongoc@me.com',
                '用户询价'.$dataRes['data']['title'],
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'send_consloe_data_to_bao_ya'=> [
                        'msg'=>'send_email',
                        'res'=>[
                            '$res4'=>$res4,
                            '$res3'=>$res3,
                            '$res2'=>$res2,
                            '$res1'=>$res1,
                        ],
                    ],
                ])
            );
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' =>  "邮件内容:".$tableHtml .' 邮件结果:'.$res1,
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '宝押发邮件',
                ]
            );
            InsuranceData::updateById($data['id'],[
                'status' => InsuranceData::$status_email_succeed
            ]);
        }
        return true ;
    }

    static function sendEmailHuiZhong()
    {
        //保鸭
        $datas = InsuranceDataHuiZhong::findBySql(
            " WHERE  
                    `status` =  ".InsuranceDataHuiZhong::$status_init." 
                "
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'send_consloe_data_to_hui_zhong'=> [
                    'msg'=>'start',
                    'data_count'=>count($datas),
                ],
            ])
        );
        foreach ($datas as $data){
            $insuranceDatas  = json_decode($data['post_params'],true);
            $insuranceDatas['id'] = $data['id'];
            $tableHtml = self::getTableHtmlHuiZhong($insuranceDatas);
            $res1 = CommonService::getInstance()->sendEmailV2(
                'tianyongshan@meirixindong.com',
                '用户车险分期预授信',
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            $res2= CommonService::getInstance()->sendEmailV2(
                'guoxinxia@meirixindong.com',
                // 'minglongoc@me.com',
                '用户车险分期预授信',
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            $res3= CommonService::getInstance()->sendEmailV2(
                'wanghan@meirixindong.com',
                // 'minglongoc@me.com',
                '用户车险分期预授信',
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            $res4= CommonService::getInstance()->sendEmailV2(
                'liyunxian@meirixindong.com',
                // 'minglongoc@me.com',
                '用户车险分期预授信',
                $tableHtml
                ,
                [
                    // TEMP_FILE_PATH . 'personal.png',
                    //TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'send_consloe_data_to_hui_zhong'=> [
                        'msg'=>'send_email',
                        'res'=>[
                            '$res4'=>$res4,
                            '$res3'=>$res3,
                            '$res2'=>$res2,
                            '$res1'=>$res1,
                        ],
                    ],
                ])
            );
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' =>  "邮件内容:".$tableHtml .' 邮件结果:'.$res1,
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '麾众发邮件',
                ]
            );
            InsuranceDataHuiZhong::updateById($data['id'],[
                'status' => InsuranceDataHuiZhong::$status_sended
            ]);
        }
        return true ;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
