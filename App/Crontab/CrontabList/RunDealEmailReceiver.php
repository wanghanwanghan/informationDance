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
        self::sendEmailHuiZhong();
        //拉取收件箱
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
        //单纯加数据
        foreach ($emailData as $emailDataItem){
            $attachs = $mail->getAttach($msgcount,OTHER_FILE_PATH.'MailAttach/');
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$attachs' =>  $attachs,
                    'subject'=>$emailDataItem['mailHeader']['subject']
                ])
            );
            MailReceipt::addRecordV2(
                [
                    'email_id' => $emailDataItem['Uid'],
                    'to' => $emailAddress,
                    'to_other' => $emailDataItem['mailHeader']['toOther']?:'',
                    'from' => $emailDataItem['mailHeader']['from']?:'',
                    'subject' => $emailDataItem['mailHeader']['subject']?:'',
                    'body' => $emailDataItem['body']?:'',
                    'status' => '1',
                    'type' => '1',
                    'reamrk' => '',
                    'raw_return' => json_encode($emailDataItem),
                    'date' => date('Y-m-d H:i:s',strtotime($emailDataItem['mailHeader']['date'])) ,
                ]
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
        foreach ($emails as $email){
            CommonService::getInstance()->log4PHP(
                "needs to send text msg now"
            );

            //需要发短信了
            $res = SmsService::getInstance()->sendByTemplete(
                13269706193, 'SMS_244025473',[
                'name' => '有询价的了',
                'money' => '多钱'
            ]);

//            OperatorLog::addRecord(
//                [
//                    'user_id' => $userInfo['id'],
//                    'msg' =>   '用户余额：'.$balance." 配置的余额下限：".$Config['sms_notice_value']." 上次发送时间：".$chargeConfigs['send_sms_notice_date']." ",
//                    'details' =>json_encode( XinDongService::trace()),
//                    'type_cname' => '新后台导出财务数据-发送短信提醒余额不足',
//                ]
//            );
            MailReceipt::updateById($email['id'],['status' => MailReceipt::$status_succeed]);
        }
    }
    static  function  getTableHtml($data){
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
        <tr>
            <td>产品</td>
            <td>'.$data['product_id'].'</td>
        </tr>
        <tr class="active-row">
            <td>被保人</td>
            <td>'.$data['insured'].'</td>
        </tr>
         <tr >
            <td>营业执照</td>
            <td>
                <a  href="http://test.51baoya.com/uploads/product_briefs/knj4CUlnZBOLz5HfHdk6hcZ6D.png">
                点击查看
                </a>
            </td>
        </tr>
        <tr >
            <td>发动机号</td>
            <td>
                 '.$data['engine_number'].'
            </td>
        </tr>
        <tr >
                <td>车架号</td>
                <td>
                     '.$data['VIN'].'
                </td>
        </tr>
        <tr >
                <td>死伤限额</td>
                <td>
                     '.$data['death_limit_coverage'].'
                </td>
        </tr>
        <tr >
                <td>医疗限额</td>
                <td>
                     '.$data['medical_limit_coverage'].'
                </td>
        </tr>
        <tr >
                <td>住院津贴</td>
                <td>
                     '.$data['hospitalization_benefit'].'
                </td>
        </tr>
        <tr >
                <td>其他需求</td>
                <td>
                     '.$data['other_requirement'].'
                </td>
        </tr>
    </tbody>
</table>
</body> 
';
        return $html;
    }
    static  function  getTableHtmlHuiZhong($data){
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
        <tr>
            <td>产品</td>
            <td>'.$data['product_id'].'</td>
        </tr>
        <tr class="active-row">
            <td>被保人</td>
            <td>'.$data['insured'].'</td>
        </tr>
         <tr >
            <td>营业执照</td>
            <td>
                <a  href="http://test.51baoya.com/uploads/product_briefs/knj4CUlnZBOLz5HfHdk6hcZ6D.png">
                点击查看
                </a>
            </td>
        </tr>
        <tr >
            <td>发动机号</td>
            <td>
                 '.$data['engine_number'].'
            </td>
        </tr>
        <tr >
                <td>车架号</td>
                <td>
                     '.$data['VIN'].'
                </td>
        </tr>
        <tr >
                <td>死伤限额</td>
                <td>
                     '.$data['death_limit_coverage'].'
                </td>
        </tr>
        <tr >
                <td>医疗限额</td>
                <td>
                     '.$data['medical_limit_coverage'].'
                </td>
        </tr>
        <tr >
                <td>住院津贴</td>
                <td>
                     '.$data['hospitalization_benefit'].'
                </td>
        </tr>
        <tr >
                <td>其他需求</td>
                <td>
                     '.$data['other_requirement'].'
                </td>
        </tr>
    </tbody>
</table>
</body> 
';
        return $html;
    }

    //用户咨询后，将询价信息发送给保鸭
    static function sendEmail()
    {

        $datas = InsuranceData::findBySql(
            " WHERE  
                    `status` =  ".InsuranceData::$status_init." 
                "
        );

        foreach ($datas as $data){
            $insuranceDatas  = json_decode($data['post_params'],true);
            $tableHtml = self::getTableHtml($insuranceDatas);
            $res1 = CommonService::getInstance()->sendEmailV2(
                'tianyongshan@meirixindong.com',
                // 'minglongoc@me.com',
                '询价('.$data['id'].')',
                $tableHtml
                ,
                [
                    TEMP_FILE_PATH . 'personal.png',
                    TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' =>  " 附件:".TEMP_FILE_PATH . $res['filename'] .' 邮件结果:'.$res1.$res2.$res3.$res4.$res5,
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '赛盟发邮件',
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
        $datas = InsuranceDataHuiZhong::findBySql(
            " WHERE   `status` =  ".InsuranceDataHuiZhong::$status_init." 
            "
        );

        foreach ($datas as $data){
            $insuranceDatas  = json_decode($data['post_params'],true);
            $tableHtml = self::getTableHtmlHuiZhong($insuranceDatas);
            $res1 = CommonService::getInstance()->sendEmailV2(
                'tianyongshan@meirixindong.com',
                // 'minglongoc@me.com',
                '询价('.$data['id'].')',
                $tableHtml
                ,
                [
                    TEMP_FILE_PATH . 'personal.png',
                    TEMP_FILE_PATH . 'qianzhang2.png',
                ]
            );
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' =>  " 附件:".TEMP_FILE_PATH  .' 邮件结果:'.$res1,
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
