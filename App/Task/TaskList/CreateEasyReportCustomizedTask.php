<?php

namespace App\Task\TaskList;

use App\Crontab\CrontabList\tool\Invoice;
use App\Csp\Service\CspService;
use App\HttpController\Models\Api\OcrQueue;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\NewGraph\NewGraphService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\Report\Tcpdf;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\ZhongWang\ZhongWangService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\ORM\DbManager;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use wanghanwanghan\someUtils\control;

class CreateEasyReportCustomizedTask extends TaskBase implements TaskInterface
{
    private $entName;
    private $reportNum;
    private $phone;
    private $type;
    private $dataIndex;
    private $ocrDataInMysql;
    private $reportType;

    private $inDetail = [];
    private $outDetail = [];

    function __construct($entName, $reportNum, $phone, $type, $dataIndex, $reportType = 99)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;
        $this->phone = $phone;
        $this->type = $type;
        $this->dataIndex = $dataIndex;
        $this->ocrDataInMysql = [];
        $this->reportType = $reportType;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $ocrDataInMysql = OcrQueue::create()->where([
            'phone' => $this->phone,
            'reportNum' => $this->reportNum,
        ])->all();

        if (!empty($ocrDataInMysql)) {
            $this->ocrDataInMysql = obj2Arr($ocrDataInMysql);
            \co::sleep(60);//等待自定义进程中ocr识别完成
        }

        $pdf = new Tcpdf();

        // 设置文档信息
        $pdf->SetCreator('王瀚');
        $pdf->SetAuthor('王瀚');
        $pdf->SetTitle($this->reportNum);
        $pdf->SetSubject('王瀚');
        $pdf->SetKeywords('TCPDF, PDF, PHP');

        $pdf->setPrintHeader(false);

        // 设置页脚信息
        $pdf->setFooterData([0, 64, 0], [0, 64, 128]);
        // 设置页脚字体
        $pdf->setFooterFont(['helvetica', '', '8']);

        // 设置默认等宽字体
        $pdf->SetDefaultMonospacedFont('courier');

        // 设置间距
        $pdf->SetMargins(15, 5, 15);//页面间隔
        $pdf->SetHeaderMargin(15);//页眉top间隔
        $pdf->SetFooterMargin(15);//页脚bottom间隔

        // 设置分页
        $pdf->SetAutoPageBreak(true, 25);

        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        //设置字体 stsongstdlight支持中文
        $pdf->SetFont('stsongstdlight', '', $this->pdf_BigTitle);

        $pdf->setJPEGQuality(100);

        $res = $this->cspHandleData($this->dataIndex);

        $this->fillData($pdf, $res);

        //输出PDF
        $pdf->Output(REPORT_PATH . "{$this->reportNum}.pdf", 'F');//I输出、D下载

        $info = ReportInfo::create()->where('phone', $this->phone)->where('filename', $this->reportNum)->get();

        empty($info) ?: $info->update(['status' => 2]);

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        try {
            $info = ReportInfo::create()->where('phone', $this->phone)->where('filename', $this->reportNum)->get();

            $file = $throwable->getFile();
            $line = $throwable->getLine();
            $msg = $throwable->getMessage();

            $content = "[file => {$file}] [line => {$line}] [msg => {$msg}]";

            $info->update(['status' => 1, 'errInfo' => $content]);

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
        }
    }

    //取ocr识别出来的数据
    private function getOcrData($catalogueNum, $colspan): string
    {
        $ocrData = DbManager::getInstance()->invoke(function ($cli) use ($catalogueNum) {
            return OcrQueue::invoke($cli)->where([
                'phone' => $this->phone,
                'reportNum' => $this->reportNum,
                'catalogueNum' => $catalogueNum,
            ])->get();
        }, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        if (empty($ocrData)) return '';

        $data = explode('|||', $ocrData->content);

        $tr = '';

        foreach ($data as $one) {
            $tr .= "<p>".$one."</p>";
        }

        $data = $tr;

        return <<<TEMP
<tr>
   <td colspan="{$colspan}" style="text-align: center">
       {$data}
   </td>
</tr>
TEMP;
    }

    //填充数据
    private function fillData(Tcpdf $pdf, $cspData)
    {
        // CommonService::getInstance()->log4PHP($cspData);

        $pdf->AddPage();

        //logo
        $pdf->Image(REPORT_IMAGE_PATH . 'logo.jpg', '', '', 55, 20, '', '', 'T');

        //换行
        $pdf->ln(95);

        //entName
        $pdf->SetFont('stsongstdlight', '', $this->pdf_BigTitle);
        $pdf->writeHTML("<div>{$this->entName}</div>", true, false, false, false, 'C');

        //换行
        $pdf->ln(55);

        $createUserInfo = User::create()->where('phone',$this->phone)->get();

        $pdf->SetFont('stsongstdlight', '', $this->pdf_Text);
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse">
    <tr>
        <td width="25%">
            报告编号
        </td>
        <td width="75%">
            {$this->reportNum}
        </td>
    </tr>
    <tr>
        <td width="25%">
            查询单位
        </td>
        <td width="75%">
            {$createUserInfo->company}
        </td>
    </tr>
</table>
TEMP;

        $pdf->writeHTML($html, true, false, true, false, 'C');

        $pdf->AddPage();

        $date = Carbon::now()->format('Y年m月d日');

        //声明
        $html = <<<TEMP
<div style="width: 100%;font-size: 15px">
    <div style="text-align: center">声明</div>
    <div style="text-indent: 20px">
        <p>一、报告由北京每日信动科技有限公司出具，且郑重声明本公司与受评主体不存在任何影响评价行为独立、客观、公正的关联关系。</p>
        <p>二、本报告根据与该企业有关的国家企业信用信息公示系统、信用中国、裁判文书网、新闻媒体、行业数据等公开互联网网站等相关数据信息生成。以及在报告所涉主体授权同意的基础上，根据企业发票、涉税、年报等相关数据综合生成。</p>
        <p>三、本报告版权归北京每日信动科技有限公司所有，未经我公司书面授权，任何企业、机构或个人不得发表、修改、转发、传播本报告部分或全部内容，不得利用该报告数据进行二次加工或其他经营活动。</p>
        <p>四、本公司秉承独立、客观、公正的原则，为报告所涉及主体提供专业评估报告，本公司力争但不保证其真实性、准确性和时效性。在任何情况下，本公司不对因使用本报告而产生的任何后果承担法律责任，不承担由于其非控因素和疏忽而引起的相关损失和损害。</p>
        <p>五、北京每日信动科技有限公司保留对其信用状态的跟踪观察并根据实际情况及时调整与公布评估报告内容之权力。</p>
    </div>
    <div style="text-align: right">
        <p>北京每日信动科技有限公司</p>
        <p>{$date}</p>
    </div>
</div>
TEMP;

        $pdf->writeHTML($html, true, false, false, false, '');

        $pdf->AddPage();

        $cata = $this->pdf_Catalog($this->dataIndex);

        //一个一个func执行
        foreach ($cata as $catalogKey) {
            $this->$catalogKey($pdf, $cspData);
        }

        //如果是深度，填入发票数据
        if ($this->reportType === 51 || $this->reportType === '51') {
            $code = (new ZhongWangService())->getReceiptDataTest($this->entName,'getCode');
            $this->inDetail = (new ZhongWangService())->getReceiptDataTest($code,'in');
            $this->outDetail = (new ZhongWangService())->getReceiptDataTest($code,'out');

            //发票
            $invoiceObj = (new Invoice($this->inDetail,$this->outDetail));

            //5.2主营商品分析
            $zyspfx=$invoiceObj->zyspfx();
            $cspData['re_fpxx']['zyspfx']=$zyspfx;

            //5.4主要成本分析
            $zycbfx=$invoiceObj->zycbfx();
            $cspData['re_fpjx']['zycbfx']=$zycbfx;
            //各种费用在统计周期内合并
            $cspData['re_fpjx']['zycbfx_new']=$invoiceObj->zycbfx_new($zycbfx[1]);

            //6.1企业开票情况汇总
            $qykpqkhz=$invoiceObj->qykpqkhz();
            $cspData['re_fpxx']['qykpqkhz']=$qykpqkhz;
            //统计周期从这里拿
            $cspData['commonData']['zhouqi'] = $qykpqkhz['zhouqi']['min'].' - '.$qykpqkhz['zhouqi']['max'];

            //6.2.1年度销项发票情况汇总
            $ndxxfpqkhz=$invoiceObj->ndxxfpqkhz();
            $cspData['re_fpxx']['ndxxfpqkhz']=$ndxxfpqkhz;

            //6.2.2月度销项发票分析
            $ydxxfpfx=$invoiceObj->ydxxfpfx();
            $cspData['re_fpxx']['ydxxfpfx']=$ydxxfpfx;

            //6.2.5单张开票金额TOP10记录
            $dzkpjeTOP10jl_xx=$invoiceObj->dzkpjeTOP10jl_xx();
            $cspData['re_fpxx']['dzkpjeTOP10jl_xx']=$dzkpjeTOP10jl_xx;
            empty($cspData['re_fpxx']['dzkpjeTOP10jl_xx']) ?: $cspData['re_fpxx']['dzkpjeTOP10jl_xx'] = control::sortArrByKey($cspData['re_fpxx']['dzkpjeTOP10jl_xx'],'totalAmount',true);

            //6.2.6累计开票金额TOP10企业汇总
            $ljkpjeTOP10qyhz_xx=$invoiceObj->ljkpjeTOP10qyhz_xx();
            $cspData['re_fpxx']['ljkpjeTOP10qyhz_xx']=$ljkpjeTOP10qyhz_xx;
            empty($cspData['re_fpxx']['ljkpjeTOP10qyhz_xx']) ?: $cspData['re_fpxx']['ljkpjeTOP10qyhz_xx'] = control::sortArrByKey($cspData['re_fpxx']['ljkpjeTOP10qyhz_xx'],'total',true);

            //6.3.1下游客户稳定性分析
            //1，下游企业司龄分布
            $xyqyslfb=$invoiceObj->xyqyslfb();
            $cspData['re_fpxx']['xyqyslfb']=$xyqyslfb;
            //2，下游企业合作年限分布
            $xyqyhznxfb=$invoiceObj->xyqyhznxfb();
            $cspData['re_fpxx']['xyqyhznxfb']=$xyqyhznxfb;
            //3，下游企业更换情况
            $xyqyghqk=$invoiceObj->xyqyghqk();
            $cspData['re_fpxx']['xyqyghqk']=$xyqyghqk;

            //6.3.2下游客户集中度
            //1，下游企业地域分布
            $xyqydyfb=$invoiceObj->xyqydyfb();
            $cspData['re_fpxx']['xyqydyfb']=$xyqydyfb;
            //2，销售前十企业总占比
            $xsqsqyzzb=$invoiceObj->xsqsqyzzb();
            $cspData['re_fpxx']['xsqsqyzzb']=$xsqsqyzzb;

            //6.3.3企业销售情况预测
            $qyxsqkyc=$invoiceObj->qyxsqkyc();
            $cspData['re_fpxx']['qyxsqkyc']=$qyxsqkyc;

            //6.4.1年度进项发票情况汇总
            $ndjxfpqkhz=$invoiceObj->ndjxfpqkhz();
            $cspData['re_fpjx']['ndjxfpqkhz']=$ndjxfpqkhz;

            //6.4.2月度进项发票分析
            $ydjxfpfx=$invoiceObj->ydjxfpfx();
            $cspData['re_fpjx']['ydjxfpfx']=$ydjxfpfx;

            //6.4.3累计开票金额TOP10企业汇总
            $ljkpjeTOP10qyhz_jx=$invoiceObj->ljkpjeTOP10qyhz_jx();
            $cspData['re_fpjx']['ljkpjeTOP10qyhz_jx']=$ljkpjeTOP10qyhz_jx;
            empty($cspData['re_fpjx']['ljkpjeTOP10qyhz_jx']) ?: $cspData['re_fpjx']['ljkpjeTOP10qyhz_jx'] = control::sortArrByKey($cspData['re_fpjx']['ljkpjeTOP10qyhz_jx'],'total',true);

            //6.4.4单张开票金额TOP10企业汇总
            $dzkpjeTOP10jl_jx=$invoiceObj->dzkpjeTOP10jl_jx();
            $cspData['re_fpjx']['dzkpjeTOP10jl_jx']=$dzkpjeTOP10jl_jx;
            empty($cspData['re_fpjx']['dzkpjeTOP10jl_jx']) ?: $cspData['re_fpjx']['dzkpjeTOP10jl_jx'] = control::sortArrByKey($cspData['re_fpjx']['dzkpjeTOP10jl_jx'],'totalAmount',true);

            //6.5.1上游共饮上稳定性分析
            //1，上游供应商司龄分布
            $sygysslfb=$invoiceObj->sygysslfb();
            $cspData['re_fpjx']['sygysslfb']=$sygysslfb;
            //2，上游供应商合作年限分布
            $sygyshznxfb=$invoiceObj->sygyshznxfb();
            $cspData['re_fpjx']['sygyshznxfb']=$sygyshznxfb;
            //3，上游供应商更换情况
            $sygysghqk=$invoiceObj->sygysghqk();
            $cspData['re_fpjx']['sygysghqk']=$sygysghqk;

            //6.5.2上游供应商集中度分析
            //1，上游企业地域分布
            $syqydyfb=$invoiceObj->syqydyfb();
            $cspData['re_fpjx']['syqydyfb']=$syqydyfb;
            //2，采购前十企业总占比
            $cgqsqyzzb=$invoiceObj->cgqsqyzzb();
            $cspData['re_fpjx']['cgqsqyzzb']=$cgqsqyzzb;

            //6.5.3企业采购情况预测
            $qycgqkyc=$invoiceObj->qycgqkyc();
            $cspData['re_fpjx']['qycgqkyc']=$qycgqkyc;

            //储存信动指数-发票项
            $xdsForFaPiao=$invoiceObj->xdsForFaPiao();
            $cspData['re_fpjx']['xdsForFaPiao']=$xdsForFaPiao;

            //储存信动指数-上下游项
            $xdsForShangxiayou=$invoiceObj->xdsForShangxiayou();
            $cspData['re_fpjx']['xdsForShangxiayou']=$xdsForShangxiayou;

            //填入pdf
            $this->ProductStandardInfo($pdf);
            $this->zyspfx($pdf,$cspData);
            $this->zycbfx($pdf,$cspData);
            $this->shuifei($pdf,$cspData);
            $this->dianfei($pdf,$cspData);
            $this->ranqifei($pdf,$cspData);
            $this->reli($pdf,$cspData);
            $this->yunshu($pdf,$cspData);
            $this->wuye($pdf,$cspData);

            $this->qykpqkhz($pdf,$cspData);
            $this->ndxxfpqkhz($pdf,$cspData);
            $this->ydxxfpfx($pdf,$cspData);
            $this->ydxxfpfx_red($pdf,$cspData);
            $this->ydxxfpfx_cancel($pdf,$cspData);
            $this->dzkpjeTOP10jl_xx($pdf,$cspData);
            $this->ljkpjeTOP10qyhz_xx($pdf,$cspData);
            $this->xykhwdxfx($pdf,$cspData);
            $this->xykfjzdfx($pdf,$cspData);
            $this->qyxsqkfb($pdf,$cspData);
            $this->ndjxfpqkhz($pdf,$cspData);
            $this->ydjxfpfx($pdf,$cspData);
            $this->ljkpjeTOP10qyhz_jx($pdf,$cspData);
            $this->dzkpjeTOP10jl_jx($pdf,$cspData);
            $this->sygysslfb($pdf,$cspData);
            $this->qycgqkfb($pdf,$cspData);
        }
    }

    //基本信息 工商信息
    private function getRegisterInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData) && !empty($cspData[__FUNCTION__]))
        {
            $ocrData = $this->getOcrData('0-0', 4);

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">
            工商信息
        </td>
    </tr>
    <tr>
        <td width="25%">
            企业名称
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['ENTNAME']}
        </td>
        <td width="25%">
            企业类型
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['ENTTYPE']}
        </td>
    </tr>
    <tr>
        <td width="25%">
            注册资本(万元)
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['REGCAP']}
        </td>
        <td width="25%">
            注册地址
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['DOM']}
        </td>
    </tr>
    <tr>
        <td width="25%">
            法定代表人
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['FRDB']}
        </td>
        <td width="25%">
            统一社会信用代码
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['SHXYDM']}
        </td>
    </tr>
    <tr>
        <td width="25%">
            成立日期
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['ESDATE']}
        </td>
        <td width="25%">
            核准日期
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['APPRDATE']}
        </td>
    </tr>
    <tr>
        <td width="25%">
            经营状态
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['ENTSTATUS']}
        </td>
        <td width="25%">
            营业期限
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['OPTO']}
        </td>
    </tr>
    <tr>
        <td width="25%">
            公司类型
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['ENTTYPE']}
        </td>
        <td width="25%">
            所属行业
        </td>
        <td width="25%">
            {$cspData['getRegisterInfo']['INDUSTRY']}
        </td>
    </tr>
    <tr>
        <td width="25%">
            经营范围
        </td>
        <td colspan="3" width="75%">
            {$cspData['getRegisterInfo']['OPSCOPE']}
        </td>
    </tr>
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 股东信息
    private function getShareHolderInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            if (!empty($cspData[__FUNCTION__]))
            {
                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$one['INV']}</td>";
                    $temp .= "<td>{$one['SHXYDM']}</td>";
                    $temp .= "<td>{$one['INVTYPE']}</td>";
                    $temp .= "<td>{$one['SUBCONAM']}</td>";
                    $temp .= "<td>{$one['CONCUR']}</td>";
                    $temp .= "<td>{$this->formatPercent($one['CONRATIO'])}</td>";
                    $temp .= "<td>{$one['CONDATE']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">股东信息</td>
    </tr>
    <tr>
        <td>股东信息</td>
        <td>统一社会信用代码</td>
        <td>股东类型</td>
        <td>认缴出资额(万元)</td>
        <td>出资币种</td>
        <td>出资比例</td>
        <td>出资时间</td>
    </tr>
    {$insert}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 高管信息
    private function getMainManagerInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['NAME']}</td>";
                    $temp .= "<td>{$one['POSITION']}</td>";
                    $temp .= "<td>{$one['ISFRDB']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">高管信息</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>姓名</td>
        <td>职务</td>
        <td>是否法人</td>
    </tr>
    {$insert}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 变更信息
    private function getRegisterChangeInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['ALTDATE']}</td>";
                    $temp .= "<td>{$one['ALTITEM']}</td>";
                    $temp .= "<td>{$one['ALTBE']}</td>";
                    $temp .= "<td>{$one['ALTAF']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">变更信息</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="12%">变更日期</td>
        <td width="11%">变更项目</td>
        <td width="35%">变更前</td>
        <td width="35%">变更后</td>
    </tr>
    {$insert}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 经营异常
    private function GetOpException(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$this->formatDate($one['AddDate'])}</td>";
                    $temp .= "<td>{$one['AddReason']}</td>";
                    $temp .= "<td>{$this->formatDate($one['RemoveDate'])}</td>";
                    $temp .= "<td>{$one['RomoveReason']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">经营异常</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="12%">列入日期</td>
        <td width="35%">列入原因</td>
        <td width="12%">移出日期</td>
        <td width="34%">移出原因</td>
    </tr>
    {$insert}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 实际控制人
    private function Beneficiary(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $name = $stock = '';

            $ocrData = $this->getOcrData('0-1',2);

            if (!empty($cspData[__FUNCTION__]))
            {
                $name = $cspData[__FUNCTION__]['Name'];
                $stock = $cspData[__FUNCTION__]['TotalStockPercent'];

                foreach ($cspData[__FUNCTION__]['DetailInfoList'] as $one)
                {
                    $insert .= '<tr><td colspan="2">'.$one['Path'].'</td></tr>';
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="2" style="text-align: center;background-color: #d3d3d3">实际控制人</td>
    </tr>
    <tr>
        <td width="50%">实际控制人名称</td>
        <td width="50%">总持股比例</td>
    </tr>
    <tr>
        <td>{$name}</td>
        <td>{$stock}</td>
    </tr>
    <tr>
        <td colspan="2" style="text-align: center;background-color: #d3d3d3">股权链</td>
    </tr>
    {$insert}
    {$ocrData}
    <tr><td colspan="2">备注 : 总股权比例 = 持股人股权比例 + 其关联企业所占股权折算后比例</td></tr>
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 历史沿革及重大事项
    private function getHistoricalEvolution(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $ocrData = $this->getOcrData('0-2',2);

            $insert = '';

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $one = str_replace(['，具体登录小程序查看'],'',$one);
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="2" style="text-align: center;background-color: #d3d3d3">历史沿革及重大事项</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="93%">内容</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 法人对外投资
    private function lawPersonInvestmentInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('0-3',9);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['NAME']}</td>";
                    $temp .= "<td>{$one['ENTNAME']}</td>";
                    $temp .= "<td>{$this->formatPercent($one['CONRATIO'])}</td>";
                    $temp .= "<td>{$one['REGCAP']}</td>";
                    $temp .= "<td>{$one['SHXYDM']}</td>";
                    $temp .= "<td>{$one['SUBCONAM']}</td>";
                    $temp .= "<td>{$one['ENTSTATUS']}</td>";
                    $temp .= "<td>{$one['CONDATE']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="9" style="text-align: center;background-color: #d3d3d3">法人对外投资</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>法人</td>
        <td>企业名称</td>
        <td>持股比例</td>
        <td>注册资本(万元)</td>
        <td>统一社会信用代码</td>
        <td>认缴出资额(万元)</td>
        <td>状态</td>
        <td>认缴出资时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 法人对外任职
    private function getLawPersontoOtherInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('0-4',9);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['NAME']}</td>";
                    $temp .= "<td>{$one['ENTNAME']}</td>";
                    $temp .= "<td>{$one['SHXYDM']}</td>";
                    $temp .= "<td>{$one['ESDATE']}</td>";
                    $temp .= "<td>{$one['REGCAP']}</td>";
                    $temp .= "<td>{$one['ENTSTATUS']}</td>";
                    $temp .= "<td>{$one['POSITION']}</td>";
                    $temp .= "<td>{$one['ISFRDB']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="9" style="text-align: center;background-color: #d3d3d3">法人对外任职</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>姓名</td>
        <td>任职企业名称</td>
        <td>统一社会信用代码</td>
        <td>成立日期</td>
        <td>注册资本(万元)</td>
        <td>经营状态</td>
        <td>职务</td>
        <td>是否法人</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 企业对外投资
    private function getInvestmentAbroadInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('0-5',9);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['ENTNAME']}</td>";
                    $temp .= "<td>{$one['ESDATE']}</td>";
                    $temp .= "<td>{$one['ENTSTATUS']}</td>";
                    $temp .= "<td>{$one['REGCAP']}</td>";
                    $temp .= "<td>{$one['SUBCONAM']}</td>";
                    $temp .= "<td>{$one['CONCUR']}</td>";
                    $temp .= "<td>{$this->formatPercent($one['CONRATIO'])}</td>";
                    $temp .= "<td>{$one['CONDATE']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="9" style="text-align: center;background-color: #d3d3d3">企业对外投资</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>被投资企业名称</td>
        <td>成立日期</td>
        <td>经营状态</td>
        <td>注册资本(万元)</td>
        <td>认缴出资额(万元)</td>
        <td>出资币种</td>
        <td>出资比例</td>
        <td>出资时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 分支机构
    private function getBranchInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('0-6',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['ENTNAME']}</td>";
                    $temp .= "<td>{$one['FRDB']}</td>";
                    $temp .= "<td>{$one['ESDATE']}</td>";
                    $temp .= "<td>{$one['ENTSTATUS']}</td>";
                    $temp .= "<td>{$one['PROVINCE']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">分支机构</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>机构名称</td>
        <td>负责人</td>
        <td>成立日期</td>
        <td>经营状态</td>
        <td>登记地省份</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 银行信息
    private function GetCreditCodeNew(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('0-7',2);

            if (!empty($cspData[__FUNCTION__]))
            {
                $temp = '<tr>';
                $temp .= "<td>{$cspData[__FUNCTION__]['Bank']}</td>";
                $temp .= "<td>{$cspData[__FUNCTION__]['BankAccount']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="2" style="text-align: center;background-color: #d3d3d3">银行信息</td>
    </tr>
    <tr>
        <td width="50%">基本账户开户行</td>
        <td width="50%">基本账户号码</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 融资信息
    private function SearchCompanyFinancings(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-0',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Round']}</td>";
                    $temp .= "<td>{$one['ProductName']}</td>";
                    $temp .= "<td>{$one['Amount']}</td>";
                    $temp .= "<td>{$one['Investment']}</td>";
                    $temp .= "<td>{$one['Date']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">融资信息</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">阶段</td>
        <td width="15%">产品</td>
        <td width="15%">金额</td>
        <td width="37%">投资方</td>
        <td width="13%">日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 招投标
    private function TenderSearch(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-1',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Title']}</td>";
                    $temp .= "<td>{$one['Pubdate']}</td>";
                    $temp .= "<td>{$one['ProvinceName']}</td>";
                    $temp .= "<td>{$one['ChannelName']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">招投标信息</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="48%">描述</td>
        <td width="15%">发布日期</td>
        <td width="15%">所属地区</td>
        <td width="15%">项目分类</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 购地信息
    private function LandPurchaseList(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-2',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Address']}</td>";
                    $temp .= "<td>{$one['LandUse']}</td>";
                    $temp .= "<td>{$one['Area']}</td>";
                    $temp .= "<td>{$one['AdminArea']}</td>";
                    $temp .= "<td>{$one['SupplyWay']}</td>";
                    $temp .= "<td>{$one['SignTime']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">购地信息</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="28%">项目位置</td>
        <td width="13%">土地用途</td>
        <td width="13%">面积(公顷)</td>
        <td width="13%">行政区</td>
        <td width="13%">供地方式</td>
        <td width="13%">签订日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 土地公示
    private function LandPublishList(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-3',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Address']}</td>";
                    $temp .= "<td>{$one['PublishGov']}</td>";
                    $temp .= "<td>{$one['AdminArea']}</td>";
                    $temp .= "<td>{$one['PublishDate']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">土地公示</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="40%">地块位置</td>
        <td width="20%">发布机关</td>
        <td width="20%">行政区</td>
        <td width="13%">发布日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 土地转让
    private function LandTransferList(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-4',8);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Address']}</td>";
                    $temp .= "<td>{$one['AdminArea']}</td>";
                    $temp .= "<td>{$one['OldOwner']['Name']}</td>";
                    $temp .= "<td>{$one['NewOwner']['Name']}</td>";
                    $temp .= "<td>{$one['detail']['TransAmt']}</td>";
                    $temp .= "<td>{$one['detail']['Acreage']}</td>";
                    $temp .= "<td>{$one['detail']['TransTime']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="8" style="text-align: center;background-color: #d3d3d3">土地转让</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>土地坐落</td>
        <td>行政区</td>
        <td>原土地使用权人</td>
        <td>现土地使用权人</td>
        <td>成交额(万元)</td>
        <td>面积(公顷)</td>
        <td>成交日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 建筑资质证书
    private function Qualification(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-5',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Category']}</td>";
                    $temp .= "<td>{$one['CertNo']}</td>";
                    $temp .= "<td>{$one['CertName']}</td>";
                    $temp .= "<td>{$one['SignDate']}</td>";
                    $temp .= "<td>{$one['ValidPeriod']}</td>";
                    $temp .= "<td>{$one['SignDept']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">建筑资质证书</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">资质类别</td>
        <td width="13%">资质证书号</td>
        <td width="28%">资质名称</td>
        <td width="13%">发证日期</td>
        <td width="13%">证书有效期</td>
        <td width="13%">发证机关</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 建筑工程项目
    private function BuildingProject(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-6',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['No']}</td>";
                    $temp .= "<td>{$one['ProjectName']}</td>";
                    $temp .= "<td>{$one['Region']}</td>";
                    $temp .= "<td>{$one['Category']}</td>";
                    $ent = '';
                    foreach ($one['ConsCoyList'] as $oneEnt)
                    {
                        $ent .= $oneEnt['Name'].'<br />';
                    }
                    $temp .= "<td>{$ent}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">建筑工程项目</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="20%">项目编码</td>
        <td width="24%">项目名称</td>
        <td width="13%">项目属地</td>
        <td width="10%">项目类别</td>
        <td width="26%">建设单位</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 债券信息
    private function BondList(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-7',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['ShortName']}</td>";
                    $temp .= "<td>{$one['BondCode']}</td>";
                    $temp .= "<td>{$one['BondType']}</td>";
                    $temp .= "<td>{$one['ReleaseDate']}</td>";
                    $temp .= "<td>{$one['LaunchDate']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">债券信息</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>债券简称</td>
        <td>债券代码</td>
        <td>债券类型</td>
        <td>发行日期</td>
        <td>上市日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 网站信息
    private function GetCompanyWebSite(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-8',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Title']}</td>";
                    $temp .= "<td>{$one['HomeSite']}</td>";
                    $temp .= "<td>{$one['YuMing']}</td>";
                    $temp .= "<td>{$one['BeiAn']}</td>";
                    $temp .= "<td>{$one['SDate']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">网站信息</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>网站名称</td>
        <td>网址</td>
        <td>域名</td>
        <td>网站备案/许可证号</td>
        <td>审核日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 微博
    private function Microblog(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-9',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Name']}</td>";
                    $temp .= '<td><img src="'.$one['ImageUrl'].'" /></td>';
                    $temp .= "<td>{$one['Tags']}</td>";
                    $temp .= "<td>{$one['Description']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">微博</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">名称</td>
        <td width="10%">头像</td>
        <td width="35%">类别</td>
        <td width="35%">简介</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //公司概况 新闻舆情
    private function CompanyNews(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('1-10',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Title']}</td>";
                    $temp .= "<td>{$one['Source']}</td>";
                    $temp .= "<td>{$this->formatDate($one['PublishTime'])}</td>";
                    $temp .= "<td>{$one['NewsTags']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">新闻舆情</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="35%">标题</td>
        <td width="20%">来源</td>
        <td width="13%">时间</td>
        <td width="25%">标签</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //团队招聘 近三年团队人数变化率
    private function itemInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('2-0',3);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$one['year']}</td>";
                    $temp .= "<td>{$one['num']}</td>";
                    $temp .= "<td>{$this->formatPercent($one['yoy'])}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="3" style="text-align: center;background-color: #d3d3d3">近三年团队人数</td>
    </tr>
    <tr>
        <td width="10%">年份</td>
        <td width="45%">缴纳社保人数</td>
        <td width="45%">变化率</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //团队招聘 建筑企业-专业注册人员
    private function BuildingRegistrar(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('2-1',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Name']}</td>";
                    $temp .= "<td>{$one['Category']}</td>";
                    $temp .= "<td>{$one['RegNo']}</td>";
                    $temp .= "<td>{$one['Specialty']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">建筑企业-专业注册人员</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="20%">姓名</td>
        <td width="20%">注册类别</td>
        <td width="20%">注册号</td>
        <td width="33%">注册专业</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //团队招聘 招聘信息
    private function Recruitment(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('2-2',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Title']}</td>";
                    $temp .= "<td>{$one['ProvinceDesc']}</td>";
                    $temp .= "<td>{$one['Salary']}</td>";
                    $temp .= "<td>{$one['Experience']}</td>";
                    $temp .= "<td>{$one['Education']}</td>";
                    $temp .= "<td>{$one['PublishDate']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">招聘信息</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="20%">职位名称</td>
        <td width="13%">工作地点</td>
        <td width="20%">月薪</td>
        <td width="13%">经验</td>
        <td width="14%">学历</td>
        <td width="13%">发布日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //财务总揽 财务总揽
    private function FinanceData(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('3-0',1);

            if (!empty($cspData[__FUNCTION__]))
            {
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/Static/Image/ReportImage/Temp/{$cspData[__FUNCTION__]['pic']}" />    
    </td>
</tr>
PIC;
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">财务总揽</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //业务概况 业务概况
    private function SearchCompanyCompanyProducts(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = '';

            $ocrData = $this->getOcrData('4-0',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                foreach ($cspData[__FUNCTION__] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Name']}</td>";
                    $temp .= "<td>{$one['Domain']}</td>";
                    $temp .= "<td>{$one['Tags']}</td>";
                    $temp .= "<td>{$one['Description']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">业务概况</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="15%">产品名称</td>
        <td width="15%">产品领域</td>
        <td width="15%">产品标签</td>
        <td width="48%">产品描述</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //创新能力 专利
    private function PatentV4Search(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('5-0',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Title']}</td>";
                    $temp .= "<td>".implode(';',$one['IPCDesc'])."</td>";
                    $temp .= "<td>{$one['PublicationNumber']}</td>";
                    $temp .= "<td>{$one['LegalStatusDesc']}</td>";
                    $temp .= "<td>{$this->formatDate($one['ApplicationDate'])}</td>";
                    $temp .= "<td>{$this->formatDate($one['PublicationDate'])}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">专利</td>
    </tr>
    <tr>
        <td colspan="7">专利 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="23%">名称</td>
        <td width="15%">专利类型</td>
        <td width="16%">公开号</td>
        <td width="13%">法律状态</td>
        <td width="13%">申请日期</td>
        <td width="13%">发布日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //创新能力 软件著作权
    private function SearchSoftwareCr(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('5-1',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Name']}</td>";
                    $temp .= "<td>{$one['ShortName']}</td>";
                    $temp .= "<td>{$one['RegisterNo']}</td>";
                    $temp .= "<td>{$this->formatDate($one['RegisterAperDate'])}</td>";
                    $temp .= "<td>{$this->formatDate($one['PublishDate'])}</td>";
                    $temp .= "<td>{$one['VersionNo']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">软件著作权</td>
    </tr>
    <tr>
        <td colspan="7">软件著作权 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="24%">软件名称</td>
        <td width="23%">简称</td>
        <td width="13%">登记号</td>
        <td width="13%">登记批准日期</td>
        <td width="13%">发布日期</td>
        <td width="7%">版本号</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //创新能力 商标
    private function tmSearch(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('5-2',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Name']}</td>";
                    $temp .= '<td><img src="'.$one['ImageUrl'].'" /></td>';
                    if (isset($this->sblb[$one['IntCls']]))
                    {
                        $temp .= "<td>{$this->sblb[$one['IntCls']]}</td>";
                    }else
                    {
                        $temp .= "<td> - </td>";
                    }
                    $temp .= "<td>{$one['RegNo']}</td>";
                    $temp .= "<td>{$one['FlowStatusDesc']}</td>";
                    $temp .= "<td>{$one['AppDate']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">商标</td>
    </tr>
    <tr>
        <td colspan="7">商标 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>商标</td>
        <td>图标</td>
        <td>商标分类</td>
        <td>注册号</td>
        <td>流程状态</td>
        <td>申请日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //创新能力 作品著作权
    private function SearchCopyRight(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('5-3',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['RegisterNo']}</td>";
                    $temp .= "<td>{$one['Name']}</td>";
                    $temp .= "<td>{$one['Category']}</td>";
                    $temp .= "<td>{$one['FinishDate']}</td>";
                    $temp .= "<td>{$one['PublishDate']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">作品著作权</td>
    </tr>
    <tr>
        <td colspan="6">作品著作权 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="27%">登记号</td>
        <td width="25%">作品名称</td>
        <td width="15%">作品分类</td>
        <td width="13%">完成日期</td>
        <td width="13%">登记日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //创新能力 证书资质
    private function SearchCertification(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('5-4',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Name']}</td>";
                    if (isset($this->zzzs[$one['Type']]))
                    {
                        $temp .= "<td>{$this->zzzs[$one['Type']]}</td>";
                    }else
                    {
                        $temp .= "<td> - </td>";
                    }
                    $temp .= "<td>{$this->formatDate($one['StartDate'])}</td>";
                    $temp .= "<td>{$this->formatDate($one['EndDate'])}</td>";
                    $temp .= "<td>{$one['No']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">证书资质</td>
    </tr>
    <tr>
        <td colspan="6">证书资质 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="25%">证书名称</td>
        <td width="25%">证书类型</td>
        <td width="13%">生效时间</td>
        <td width="13%">截止日期</td>
        <td width="17%">证书编号</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //税务信息 纳税信用等级
    private function satparty_xin(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('6-0',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['detail']['body']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">纳税信用等级</td>
    </tr>
    <tr>
        <td colspan="6">纳税信用等级 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="30%">标题</td>
        <td width="13%">评定时间</td>
        <td width="7%">纳税信用等级</td>
        <td width="13%">评定单位</td>
        <td width="30%">摘要</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //税务信息 税务许可信息
    private function satparty_xuke(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('6-1',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['sortTime'])}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['postTime'])}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['detail']['body']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">税务许可信息</td>
    </tr>
    <tr>
        <td colspan="6">税务许可信息 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">评定时间</td>
        <td width="13%">申请时间</td>
        <td width="13%">事件名称</td>
        <td width="13%">管理机关</td>
        <td width="41%">摘要</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //税务信息 税务登记信息
    private function satparty_reg(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('6-2',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['detail']['body']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">税务登记信息</td>
    </tr>
    <tr>
        <td colspan="6">税务登记信息 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">评定时间</td>
        <td width="13%">事件名称</td>
        <td width="13%">事件结果</td>
        <td width="13%">管理机关</td>
        <td width="41%">摘要</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //税务信息 税务非正常户
    private function satparty_fzc(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('6-3',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['detail']['body']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">税务非正常户</td>
    </tr>
    <tr>
        <td colspan="7">税务非正常户 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="15%">标题</td>
        <td width="13%">认定时间</td>
        <td width="13%">事件名称</td>
        <td width="13%">事件结果</td>
        <td width="13%">认定机关</td>
        <td width="26%">摘要</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //税务信息 欠税信息
    private function satparty_qs(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('6-4',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['taxCategory']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['detail']['body']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">欠税信息</td>
    </tr>
    <tr>
        <td colspan="7">欠税信息 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">标题</td>
        <td width="13%">欠税时间</td>
        <td width="13%">事件名称</td>
        <td width="11%">税种</td>
        <td width="13%">管理机关</td>
        <td width="30%">摘要</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //税务信息 涉税处罚公示
    private function satparty_chufa(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('6-5',4);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['detail']['body']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">涉税处罚公示</td>
    </tr>
    <tr>
        <td colspan="4">涉税处罚公示 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">标题</td>
        <td width="13%">管理机关</td>
        <td width="67%">摘要</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //行政管理信息 行政许可
    private function GetAdministrativeLicenseList(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('7-0',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['CaseNo']}</td>";
                    $temp .= "<td>{$one['detail']['LianDate']}</td>";
                    $temp .= "<td>{$one['detail']['ExpireDate']}</td>";
                    $temp .= "<td>{$one['detail']['Content']}</td>";
                    $temp .= "<td>{$one['detail']['ExecuteGov']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">行政许可</td>
    </tr>
    <tr>
        <td colspan="6">行政许可 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="18%">许可编号</td>
        <td width="13%">有效期自</td>
        <td width="13%">有效期止</td>
        <td width="29%">许可内容</td>
        <td width="20%">许可机关</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //行政管理信息 行政处罚
    private function GetAdministrativePenaltyList(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('7-1',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['CaseNo']}</td>";
                    $temp .= "<td>{$one['detail']['DecideDate']}</td>";
                    $temp .= "<td>{$one['detail']['CaseReason']}</td>";
                    $temp .= "<td>{$one['detail']['According']}</td>";
                    $temp .= "<td>{$one['detail']['Content']}</td>";
                    $temp .= "<td>{$one['detail']['ExecuteGov']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">行政处罚</td>
    </tr>
    <tr>
        <td colspan="7">行政处罚 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">文书号</td>
        <td width="13%">决定日期</td>
        <td width="24%">原因</td>
        <td width="15%">依据</td>
        <td width="15%">内容</td>
        <td width="13%">决定机关</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //环保信息 环保处罚
    private function epbparty(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('8-0',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['money']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">环保处罚</td>
    </tr>
    <tr>
        <td colspan="7">环保处罚 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>案号</td>
        <td>处罚时间</td>
        <td>事件名称(类型)</td>
        <td>处罚金额</td>
        <td>处罚结果</td>
        <td>处罚机关</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //环保信息 重点监控企业名单
    private function epbparty_jkqy(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('8-1',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['eventType']}</td>";
                    $temp .= "<td>{$one['detail']['pname']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">重点监控企业名单</td>
    </tr>
    <tr>
        <td colspan="6">重点监控企业名单 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="37%">标题</td>
        <td width="13%">时间</td>
        <td width="13%">事件名称</td>
        <td width="13%">事件类型</td>
        <td width="17%">涉事企业</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //环保信息 环保企业自行监测结果
    private function epbparty_zxjc(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('8-2',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['pollutant']}</td>";
                    $temp .= "<td>{$one['detail']['standard']}</td>";
                    $temp .= "<td>{$one['detail']['density']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">环保企业自行监测结果</td>
    </tr>
    <tr>
        <td colspan="6">环保企业自行监测结果 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>监测指标/污染项目</td>
        <td>标准值</td>
        <td>监测值</td>
        <td>监测结果</td>
        <td>监测时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //环保信息 环评公示数据
    private function epbparty_huanping(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('8-3',4);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['detail']['eventType']}</td>";
                    $temp .= "<td>{$one['detail']['body']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">环评公示数据</td>
    </tr>
    <tr>
        <td colspan="4">环评公示数据 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">标题</td>
        <td width="13%">公示类型</td>
        <td width="67%">摘要</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //海关信息 海关企业
    private function custom_qy(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('9-0',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['regNo']}</td>";
                    $temp .= "<td>{$one['detail']['custom']}</td>";
                    $temp .= "<td>{$one['detail']['category']}</td>";
                    $temp .= "<td>{$one['detail']['jjqh']}</td>";
                    $temp .= "<td>{$one['detail']['industry']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">海关企业</td>
    </tr>
    <tr>
        <td colspan="7">海关企业 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>海关注册码</td>
        <td>注册海关</td>
        <td>经营类别</td>
        <td>经济区划</td>
        <td>行业种类</td>
        <td>注册时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //海关信息 海关许可
    private function custom_xuke(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('9-1',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['xkNo']}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">海关许可</td>
    </tr>
    <tr>
        <td colspan="5">海关许可 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">许可文书号</td>
        <td width="46%">标题</td>
        <td width="20%">许可机关</td>
        <td width="14%">发布日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //海关信息 海关信用
    private function custom_credit(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('9-2',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['detail']['regNo']}</td>";
                    $temp .= "<td>{$one['detail']['creditRank']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">海关信用</td>
    </tr>
    <tr>
        <td colspan="5">海关信用 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>所属海关</td>
        <td>注册号</td>
        <td>信用等级</td>
        <td>认定年份</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //海关信息 海关处罚
    private function custom_punish(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('9-3',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['ggType']}</td>";
                    $temp .= "<td>{$one['detail']['eventType']}</td>";
                    $temp .= "<td>{$one['detail']['yj']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">海关处罚</td>
    </tr>
    <tr>
        <td colspan="5">海关处罚 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>公告类型</td>
        <td>处罚类别/案件性质</td>
        <td>依据</td>
        <td>处罚日期</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //一行两会信息 央行行政处罚
    private function pbcparty(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('10-0',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">央行行政处罚</td>
    </tr>
    <tr>
        <td colspan="7">央行行政处罚 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>标题</td>
        <td>书文号</td>
        <td>事件名称</td>
        <td>事件结果</td>
        <td>管理机关</td>
        <td>处罚时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //一行两会信息 银保监会处罚公示
    private function pbcparty_cbrc(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('10-1',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">银保监会处罚公示</td>
    </tr>
    <tr>
        <td colspan="7">银保监会处罚公示 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>标题</td>
        <td>公告编号</td>
        <td>事件名称</td>
        <td>事件结果</td>
        <td>管理机关</td>
        <td>处罚时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //一行两会信息 证监会处罚公示
    private function pbcparty_csrc_chufa(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('10-2',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['postTime'])}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">证监会处罚公示</td>
    </tr>
    <tr>
        <td colspan="7">证监会处罚公示 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>标题</td>
        <td>文书号</td>
        <td>公告类型</td>
        <td>处罚结果</td>
        <td>处罚机关</td>
        <td>处罚时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //一行两会信息 证监会许可信息
    private function pbcparty_csrc_xkpf(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('10-3',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['title']}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">证监会许可信息</td>
    </tr>
    <tr>
        <td colspan="6">证监会许可信息 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">标题</td>
        <td width="13%">文书号</td>
        <td width="41%">许可事项</td>
        <td width="13%">管理机关</td>
        <td width="13%">许可时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //一行两会信息 外汇局处罚
    private function safe_chufa(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('10-4',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['detail']['caseCause']}</td>";
                    $temp .= "<td>{$one['detail']['eventResult']}</td>";
                    $temp .= "<td>{$one['detail']['money']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">外汇局处罚</td>
    </tr>
    <tr>
        <td colspan="7">外汇局处罚 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">文书号</td>
        <td width="23%">违规行为</td>
        <td width="18%">罚款结果</td>
        <td width="13%">罚款金额</td>
        <td width="13%">执行机关</td>
        <td width="13%">处罚时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //一行两会信息 外汇局许可
    private function safe_xuke(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('10-5',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['detail']['eventName']}</td>";
                    $temp .= "<td>{$one['detail']['eventType']}</td>";
                    $temp .= "<td>{$one['detail']['yiju']}</td>";
                    $temp .= "<td>{$one['detail']['authority']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">外汇局许可</td>
    </tr>
    <tr>
        <td colspan="7">外汇局许可 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">文书号</td>
        <td width="18%">项目名称</td>
        <td width="18%">许可事项</td>
        <td width="18%">依据</td>
        <td width="13%">许可机关</td>
        <td width="13%">许可时间</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 法院公告
    private function fygg(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-0',8);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $rowspan = count($one['detail']['partys']) === 0 ? 1 : count($one['detail']['partys']);
                    $temp = '<tr>';
                    $temp .= "<td rowspan=\"{$rowspan}\">{$i}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['caseNo']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['court']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['sortTimeString']}</td>";

                    $first = true;

                    if (!empty($one['detail']['partys']))
                    {
                        foreach ($one['detail']['partys'] as $party)
                        {
                            if ($first)
                            {
                                $temp .= "<td>{$party['caseCauseT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['partyTitleT']}</td>";
                                switch ($party['partyPositionT'])
                                {
                                    case 'p':
                                        $temp .= "<td>原告</td>";
                                        break;
                                    case 'd':
                                        $temp .= "<td>被告</td>";
                                        break;
                                    case 't':
                                        $temp .= "<td>第三人</td>";
                                        break;
                                    case 'u':
                                        $temp .= "<td>当事人</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                                $first = false;
                            }else
                            {
                                $temp .= '<tr>';
                                $temp .= "<td>{$party['caseCauseT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['partyTitleT']}</td>";
                                switch ($party['partyPositionT'])
                                {
                                    case 'p':
                                        $temp .= "<td>原告</td>";
                                        break;
                                    case 'd':
                                        $temp .= "<td>被告</td>";
                                        break;
                                    case 't':
                                        $temp .= "<td>第三人</td>";
                                        break;
                                    case 'u':
                                        $temp .= "<td>当事人</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                            }
                        }
                    }else
                    {
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "</tr>";
                    }

                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="8" style="text-align: center;background-color: #d3d3d3">法院公告</td>
    </tr>
    <tr>
        <td colspan="8">法院公告 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">案号</td>
        <td width="13%">公告法院</td>
        <td width="13%">立案时间</td>
        <td width="13%">案由</td>
        <td width="21%">当事人</td>
        <td width="13%">称号</td>
        <td width="7%">诉讼地位</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 开庭公告
    private function ktgg(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-1',8);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $rowspan = count($one['detail']['partys']) === 0 ? 1 : count($one['detail']['partys']);
                    $temp = '<tr>';
                    $temp .= "<td rowspan=\"{$rowspan}\">{$i}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['caseNo']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['court']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['sortTimeString']}</td>";

                    $first = true;

                    if (!empty($one['detail']['partys']))
                    {
                        foreach ($one['detail']['partys'] as $party)
                        {
                            if ($first)
                            {
                                $temp .= "<td>{$party['caseCauseT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['partyTitleT']}</td>";
                                switch ($party['partyPositionT'])
                                {
                                    case 'p':
                                        $temp .= "<td>原告</td>";
                                        break;
                                    case 'd':
                                        $temp .= "<td>被告</td>";
                                        break;
                                    case 't':
                                        $temp .= "<td>第三人</td>";
                                        break;
                                    case 'u':
                                        $temp .= "<td>当事人</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                                $first = false;
                            }else
                            {
                                $temp .= '<tr>';
                                $temp .= "<td>{$party['caseCauseT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['partyTitleT']}</td>";
                                switch ($party['partyPositionT'])
                                {
                                    case 'p':
                                        $temp .= "<td>原告</td>";
                                        break;
                                    case 'd':
                                        $temp .= "<td>被告</td>";
                                        break;
                                    case 't':
                                        $temp .= "<td>第三人</td>";
                                        break;
                                    case 'u':
                                        $temp .= "<td>当事人</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                            }
                        }
                    }else
                    {
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "</tr>";
                    }

                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="8" style="text-align: center;background-color: #d3d3d3">开庭公告</td>
    </tr>
    <tr>
        <td colspan="8">开庭公告 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">案号</td>
        <td width="13%">法院名称</td>
        <td width="13%">立案时间</td>
        <td width="13%">案由</td>
        <td width="21%">当事人</td>
        <td width="13%">称号</td>
        <td width="7%">诉讼地位</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 裁判文书
    private function cpws(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-2',9);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $rowspan = count($one['detail']['partys']) === 0 ? 1 : count($one['detail']['partys']);
                    $temp = '<tr>';
                    $temp .= "<td rowspan=\"{$rowspan}\">{$i}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['caseNo']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['court']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['sortTimeString']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['trialProcedure']}</td>";

                    $first = true;

                    if (!empty($one['detail']['partys']))
                    {
                        foreach ($one['detail']['partys'] as $party)
                        {
                            if ($first)
                            {
                                $temp .= "<td>{$party['caseCauseT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['partyTitleT']}</td>";
                                switch ($party['partyPositionT'])
                                {
                                    case 'p':
                                        $temp .= "<td>原告</td>";
                                        break;
                                    case 'd':
                                        $temp .= "<td>被告</td>";
                                        break;
                                    case 't':
                                        $temp .= "<td>第三人</td>";
                                        break;
                                    case 'u':
                                        $temp .= "<td>当事人</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                                $first = false;
                            }else
                            {
                                $temp .= '<tr>';
                                $temp .= "<td>{$party['caseCauseT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['partyTitleT']}</td>";
                                switch ($party['partyPositionT'])
                                {
                                    case 'p':
                                        $temp .= "<td>原告</td>";
                                        break;
                                    case 'd':
                                        $temp .= "<td>被告</td>";
                                        break;
                                    case 't':
                                        $temp .= "<td>第三人</td>";
                                        break;
                                    case 'u':
                                        $temp .= "<td>当事人</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                            }
                        }
                    }else
                    {
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "</tr>";
                    }

                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="9" style="text-align: center;background-color: #d3d3d3">裁判文书</td>
    </tr>
    <tr>
        <td colspan="9">裁判文书 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">案号</td>
        <td width="13%">法院名称</td>
        <td width="13%">立案时间</td>
        <td width="7%">审理状态</td>
        <td width="13%">案由</td>
        <td width="14%">当事人</td>
        <td width="13%">称号</td>
        <td width="7%">诉讼地位</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 执行公告
    private function zxgg(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-3',8);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $rowspan = count($one['detail']['partys']) === 0 ? 1 : count($one['detail']['partys']);
                    $temp = '<tr>';
                    $temp .= "<td rowspan=\"{$rowspan}\">{$i}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['caseNo']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['court']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['sortTimeString']}</td>";

                    $first = true;

                    if (!empty($one['detail']['partys']))
                    {
                        foreach ($one['detail']['partys'] as $party)
                        {
                            if ($first)
                            {
                                $temp .= "<td>{$party['caseStateT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['execMoney']}</td>";
                                switch ($party['partyType'])
                                {
                                    case 'P':
                                        $temp .= "<td>自然人</td>";
                                        break;
                                    case 'C':
                                        $temp .= "<td>公司</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                                $first = false;
                            }else
                            {
                                $temp .= '<tr>';
                                $temp .= "<td>{$party['caseStateT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['execMoney']}</td>";
                                switch ($party['partyType'])
                                {
                                    case 'P':
                                        $temp .= "<td>自然人</td>";
                                        break;
                                    case 'C':
                                        $temp .= "<td>公司</td>";
                                        break;
                                    default:
                                        $temp .= "<td> -- </td>";
                                }
                                $temp .= '</tr>';
                            }
                        }
                    }else
                    {
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "</tr>";
                    }

                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="8" style="text-align: center;background-color: #d3d3d3">执行公告</td>
    </tr>
    <tr>
        <td colspan="8">执行公告 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">案号</td>
        <td width="13%">法院名称</td>
        <td width="13%">立案时间</td>
        <td width="13%">案件状态</td>
        <td width="21%">当事人</td>
        <td width="13%">执行金额</td>
        <td width="7%">主体</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 失信公告
    private function shixin(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-4',8);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $rowspan = count($one['detail']['partys']) === 0 ? 1 : count($one['detail']['partys']);
                    $temp = '<tr>';
                    $temp .= "<td rowspan=\"{$rowspan}\">{$i}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['caseNo']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['detail']['court']}</td>";
                    $temp .= "<td rowspan=\"{$rowspan}\">{$one['sortTimeString']}</td>";

                    $first = true;

                    if (!empty($one['detail']['partys']))
                    {
                        foreach ($one['detail']['partys'] as $party)
                        {
                            if ($first)
                            {
                                $temp .= "<td>{$party['lxqkT']}</td>";
                                $temp .= "<td>{$party['jtqx']}</td>";
                                $temp .= "<td>{$party['money']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= '</tr>';
                                $first = false;
                            }else
                            {
                                $temp .= '<tr>';
                                $temp .= "<td>{$party['lxqkT']}</td>";
                                $temp .= "<td>{$party['jtqx']}</td>";
                                $temp .= "<td>{$party['money']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= '</tr>';
                            }
                        }
                    }else
                    {
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "<td> -- </td>";
                        $temp .= "</tr>";
                    }

                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="8" style="text-align: center;background-color: #d3d3d3">失信公告</td>
    </tr>
    <tr>
        <td colspan="8">失信公告 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">案号</td>
        <td width="13%">法院名称</td>
        <td width="13%">立案时间</td>
        <td width="13%">履行情况</td>
        <td width="21%">具体情形</td>
        <td width="13%">涉案金额</td>
        <td width="7%">当事人</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 被执行人
    private function SearchZhiXing(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-5',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Anno']}</td>";
                    $temp .= "<td>{$one['ExecuteGov']}</td>";
                    $temp .= "<td>{$this->formatDate($one['Liandate'])}</td>";
                    $temp .= "<td>{$one['Biaodi']}</td>";
                    $temp .= "<td>{$one['Status']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">被执行人</td>
    </tr>
    <tr>
        <td colspan="6">被执行人 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>案号</td>
        <td>执行法院</td>
        <td>立案时间</td>
        <td>执行标的</td>
        <td>案件状态</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 司法查冻扣
    private function sifacdk(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-6',8);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['caseNo']}</td>";
                    $temp .= "<td>{$one['detail']['objectName']}</td>";
                    $temp .= "<td>{$one['detail']['objectType']}</td>";
                    $temp .= "<td>{$one['detail']['court']}</td>";
                    $temp .= "<td>{$one['sortTimeString']}</td>";
                    $temp .= "<td>{$one['detail']['eventDate']}</td>";
                    $temp .= "<td>{$one['detail']['money']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="8" style="text-align: center;background-color: #d3d3d3">司法查冻扣</td>
    </tr>
    <tr>
        <td colspan="8">司法查冻扣 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td>序号</td>
        <td>案件编号</td>
        <td>标的名称</td>
        <td>标的类型</td>
        <td>审理法院</td>
        <td>审结时间</td>
        <td>事件时间</td>
        <td>涉及金额</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 动产抵押
    private function getChattelMortgageInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-7',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['DJBH']}</td>";
                    $temp .= "<td>{$one['GSRQ']}</td>";
                    $temp .= "<td>{$one['DJRQ']}</td>";
                    $temp .= "<td>{$one['DJJG']}</td>";
                    $temp .= "<td>{$one['BDBZQSE']}</td>";
                    $temp .= "<td>{$one['ZT']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">动产抵押</td>
    </tr>
    <tr>
        <td colspan="7">动产抵押 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">登记编号</td>
        <td width="13%">公示日期</td>
        <td width="13%">登记日期</td>
        <td width="28%">登记机关</td>
        <td width="13%">被担保债权数额</td>
        <td width="13%">状态</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 股权出质
    private function getEquityPledgedInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-8',7);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['DJBH']}</td>";
                    $temp .= "<td>{$one['GQCZSLDJRQ']}</td>";
                    $temp .= "<td>{$one['ZQR']}</td>";
                    $temp .= "<td>{$one['CZR']}</td>";
                    $temp .= "<td>{$one['CZGQSE']}</td>";
                    $temp .= "<td>{$one['ZT']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">股权出质</td>
    </tr>
    <tr>
        <td colspan="7">股权出质 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="13%">登记编号</td>
        <td width="13%">登记日期</td>
        <td width="21%">质权人</td>
        <td width="20%">出质人</td>
        <td width="13%">出质股权数额</td>
        <td width="13%">状态</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 对外担保
    private function GetAnnualReport(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-9',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['Creditor']}</td>";
                    $temp .= "<td>{$one['Debtor']}</td>";
                    $temp .= "<td>{$one['CreditorAmount']}</td>";
                    $temp .= "<td>{$one['AssuranceType']}</td>";
                    $temp .= "<td>{$one['FulfillObligation']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">对外担保</td>
    </tr>
    <tr>
        <td colspan="6">对外担保 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="16%">债权人</td>
        <td width="15%">债务人</td>
        <td width="18%">担保金额(万元)</td>
        <td width="18%">保证方式</td>
        <td width="26%">担保期起止</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //司法涉诉与抵质押信息 土地抵押
    private function GetLandMortgageList(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('11-10',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['StartDate']}</td>";
                    $temp .= "<td>{$one['EndDate']}</td>";
                    $temp .= "<td>{$one['MortgageAcreage']}</td>";
                    $temp .= "<td>{$one['MortgagePurpose']}</td>";
                    $temp .= "<td>{$one['Address']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">土地抵押</td>
    </tr>
    <tr>
        <td colspan="6">土地抵押 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="16%">开始日期</td>
        <td width="15%">结束日期</td>
        <td width="18%">抵押面积(公顷)</td>
        <td width="18%">抵押用途</td>
        <td width="26%">行政区地址</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //债权信息 应收账款
    private function company_zdw_yszkdsr(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('12-0',4);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['transPro_desc']}</td>";
                    $temp .= "<td>{$one['detail']['transPro_limit']}</td>";
                    $temp .= "<td>{$one['detail']['transPro_conMoney']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">应收账款</td>
    </tr>
    <tr>
        <td colspan="4">应收账款 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="50%">质押财产/转让财产描述</td>
        <td width="26%">登记到期起止</td>
        <td width="17%">转让财产价值</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //债权信息 所有权保留
    private function company_zdw_syqbldsr(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('12-1',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['regClass']}</td>";
                    $temp .= "<td>{$one['detail']['basic_date']}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['endTime'])}</td>";
                    $temp .= "<td>{$one['detail']['syqType']}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">所有权保留</td>
    </tr>
    <tr>
        <td colspan="5">所有权保留 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="23%">登记种类</td>
        <td width="23%">登记期限</td>
        <td width="23%">登记到期日</td>
        <td width="24%">所有权标的物类型</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //债权信息 租赁登记
    private function company_zdw_zldjdsr(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('13-0',4);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['leaseMes_desc']}</td>";
                    $temp .= "<td>{$one['detail']['basic_date']}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['endTime'])}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">租赁登记</td>
    </tr>
    <tr>
        <td colspan="4">租赁登记 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="67%">租赁财产描述</td>
        <td width="13%">登记期限</td>
        <td width="13%">登记到期日</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //债权信息 保证金质押登记
    private function company_zdw_bzjzydsr(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('13-1',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['pledgePro_proMoney']}</td>";
                    $temp .= "<td>{$one['detail']['pledgePro_depMoney']}</td>";
                    $temp .= "<td>{$one['detail']['basic_type']}</td>";
                    $temp .= "<td>{$one['detail']['basic_date']}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['endTime'])}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">保证金质押登记</td>
    </tr>
    <tr>
        <td colspan="6">保证金质押登记 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="18%">主合同金额</td>
        <td width="18%">保证金金额</td>
        <td width="19%">登记种类</td>
        <td width="19%">登记期限</td>
        <td width="19%">登记到期日</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //债权信息 仓单质押
    private function company_zdw_cdzydsr(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('13-2',6);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['pledgorFin_type']}</td>";
                    $temp .= "<td>{$one['detail']['pledgorFin_desc']}</td>";
                    $temp .= "<td>{$one['detail']['pledgorFin_masterConMoney']}</td>";
                    $temp .= "<td>{$one['detail']['basic_date']}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['endTime'])}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">仓单质押</td>
    </tr>
    <tr>
        <td colspan="6">仓单质押 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="14%">仓储物名称或品种</td>
        <td width="30%">质押财产描述</td>
        <td width="23%">主合同金额</td>
        <td width="13%">登记期限</td>
        <td width="13%">登记到期日</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //债权信息 其他动产融资
    private function company_zdw_qtdcdsr(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists(__FUNCTION__,$cspData))
        {
            $insert = $num = '';

            $ocrData = $this->getOcrData('13-3',5);

            if (!empty($cspData[__FUNCTION__]))
            {
                $i = 1;

                $num = $cspData[__FUNCTION__]['total'];

                foreach ($cspData[__FUNCTION__]['list'] as $one)
                {
                    $temp = '<tr>';
                    $temp .= "<td>{$i}</td>";
                    $temp .= "<td>{$one['detail']['basic_typeT']}</td>";
                    $temp .= "<td>{$one['detail']['bdwMes_conMoney']}</td>";
                    $temp .= "<td>{$one['detail']['basic_date']}</td>";
                    $temp .= "<td>{$this->formatDate($one['detail']['endTime'])}</td>";
                    $temp .= '</tr>';
                    $insert .= $temp;
                    $i++;
                }
            }

            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">其他动产融资</td>
    </tr>
    <tr>
        <td colspan="5">其他动产融资 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="24%">抵押物类型</td>
        <td width="23%">主合同金额</td>
        <td width="23%">登记期限</td>
        <td width="23%">登记到期日</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //深度报告字段 必执行的 备案主营产品
    private function ProductStandardInfo(Tcpdf $pdf)
    {
        $insert = $num = '';
        $ocrData = $this->getOcrData('4-1',4);
        $res = (new XinDongService())->setCheckRespFlag(true)->getProductStandard($this->entName,1,50);
        if ($res['code'] === 200 && !empty($res['result'])) {
            $tmp['list'] = $res['result'];
            $tmp['total'] = $res['paging']['total'];
        } else {
            $tmp['list'] = null;
            $tmp['total'] = 0;
        }
        if (!empty($tmp['list'])) {
            $i = 1;
            $num = $tmp['total'];
            foreach ($tmp['list'] as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['PRODUCT_NAME']}</td>";
                $temp .= "<td>{$one['STANDARD_NAME']}</td>";
                $temp .= "<td>{$one['STANDARD_CODE']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">备案主营产品</td>
    </tr>
    <tr>
        <td colspan="4">备案主营产品 {$num} 项，报告中提供最新的 20 条记录</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">产品名称</td>
        <td width="33%">标准名称</td>
        <td width="26%">标准编号</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 主营商品分析
    private function zyspfx(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-2',4);
        $res = $data['re_fpxx']['zyspfx'];
        $zhouqi = "自 {$data['commonData']['zhouqi']} 的销项发票";;
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['name']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['zhanbi']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">主营商品分析</td>
    </tr>
    <tr>
        <td colspan="4">{$zhouqi}</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">商品类型</td>
        <td width="33%">销售金额（万元）</td>
        <td width="26%">占比（%）</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 主要成本分析
    private function zycbfx(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-3',4);
        $res = $data['re_fpjx']['zycbfx'][0];
        $zhouqi = "自 {$data['commonData']['zhouqi']} 的进项发票";;
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['name']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['zhanbi']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">主要成本分析</td>
    </tr>
    <tr>
        <td colspan="4">{$zhouqi}</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">成本类型</td>
        <td width="33%">金额（万元）</td>
        <td width="26%">占比（%）</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 水费支出
    private function shuifei(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-4',4);
        $res = $data['re_fpjx']['zycbfx_new']['shuifei'];
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['riqi']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['gs']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">水费支出</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">发票统计周期</td>
        <td width="33%">金额（万元）</td>
        <td width="26%">服务商</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 电费支出
    private function dianfei(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-5',4);
        $res = $data['re_fpjx']['zycbfx_new']['dianfei'];
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['riqi']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['gs']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">电费支出</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">发票统计周期</td>
        <td width="33%">金额（万元）</td>
        <td width="26%">服务商</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 燃气支出
    private function ranqifei(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-6',4);
        $res = $data['re_fpjx']['zycbfx_new']['ranqifei'];
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['riqi']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['gs']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">燃气支出</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">发票统计周期</td>
        <td width="33%">金额（万元）</td>
        <td width="26%">服务商</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 热力支出
    private function reli(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-7',4);
        $res = $data['re_fpjx']['zycbfx_new']['reli'];
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['riqi']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['gs']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">热力支出</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">发票统计周期</td>
        <td width="33%">金额（万元）</td>
        <td width="26%">服务商</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 运输与仓储支出
    private function yunshu(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-8',4);
        $res = $data['re_fpjx']['zycbfx_new']['yunshu'];
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['riqi']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['gs']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">运输与仓储支出</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">发票统计周期</td>
        <td width="33%">金额（万元）</td>
        <td width="26%">服务商</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 物业支出
    private function wuye(Tcpdf $pdf, $data)
    {
        $insert = '';
        $ocrData = $this->getOcrData('4-9',4);
        $res = $data['re_fpjx']['zycbfx_new']['wuye'];
        if (!empty($res)) {
            $i = 1;
            foreach ($res as $one) {
                $temp = '<tr>';
                $temp .= "<td>{$i}</td>";
                $temp .= "<td>{$one['riqi']}</td>";
                $temp .= "<td>{$one['jine']}</td>";
                $temp .= "<td>{$one['gs']}</td>";
                $temp .= '</tr>';
                $insert .= $temp;
                $i++;
            }
        }
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="4" style="text-align: center;background-color: #d3d3d3">物业支出</td>
    </tr>
    <tr>
        <td width="7%">序号</td>
        <td width="34%">发票统计周期</td>
        <td width="33%">金额（万元）</td>
        <td width="26%">服务商</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 企业开票情况汇总
    private function qykpqkhz(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-1',3);
        $res = $data['re_fpxx']['qykpqkhz'];
        $insert = '<tr>';
        $insert .= '<td>'.$res['zhouqi']['min'].' - '.$res['zhouqi']['max'].'</td>';
        $insert .= '<td>'.$res['zhouqi']['xxNum'].'</td>';
        $insert .= '<td>'.$res['zhouqi']['xxJine'].'</td>';
        $insert .= '<td>'.$res['zhouqi']['jxNum'].'</td>';
        $insert .= '<td>'.$res['zhouqi']['jxJine'].'</td>';
        $insert .= '</tr>';
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="5" style="text-align: center;background-color: #d3d3d3">企业开票情况汇总</td>
    </tr>
    <tr>
        <td width="15%">统计周期</td>
        <td width="22%">销项有效数</td>
        <td width="21%">销项有效金额</td>
        <td width="24%">进项有效数</td>
        <td width="18%">进项有效金额</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //其他
        $res = $data['re_fpxx']['qykpqkhz'];
        krsort($res['qita']);
        $tmp = '';
        foreach ($res['qita'] as $year => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$year.'</td>';
            $insert .= '<td>'.$val['xxNum'].'</td>';
            $insert .= '<td>'.$val['xxJine'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td width="15%">统计年份</td>
        <td width="46%">销项有效数</td>
        <td width="39%">销项有效金额</td>
    </tr>
    {$tmp}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 年度销项发票情况汇总
    private function ndxxfpqkhz(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-3',12);
        $res = $data['re_fpxx']['ndxxfpqkhz'];
        $tmp = '';
        foreach ($res as $year => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$year.'</td>';
            $insert .= '<td>'.$val['normal']['normalNum'].'</td>';
            $insert .= '<td>'.$val['normal']['normalAmount'].'</td>';
            $insert .= '<td>'.$val['normal']['normalTax'].'</td>';
            $insert .= '<td>'.$val['red']['redNum'].'</td>';
            $insert .= '<td>'.$val['red']['redAmount'].'</td>';
            $insert .= '<td>'.$val['red']['redTax'].'</td>';
            $insert .= '<td>'.$val['cancel']['cancelNum'].'</td>';
            $insert .= '<td>'.$val['cancel']['cancelAmount'].'</td>';
            $insert .= '<td>'.$val['cancel']['cancelTax'].'</td>';
            $insert .= '<td>'.$val['normal']['numZhanbi'].'</td>';
            $insert .= '<td>'.$val['normal']['AmountZhanbi'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="12" style="text-align: center;background-color: #d3d3d3">年度销项发票情况汇总</td>
    </tr>
    <tr>
        <td width="8%">统计年份</td>
        <td width="8%">有效数</td>
        <td width="8%">有效金额</td>
        <td width="8%">有效税额</td>
        <td width="8%">红冲数</td>
        <td width="8%">红冲金额</td>
        <td width="8%">红冲税额</td>
        <td width="8%">作废数量</td>
        <td width="8%">作废金额</td>
        <td width="8%">作废税额</td>
        <td width="10%">有效发票数量占比</td>
        <td width="10%">有效发票金额占比</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 月度销项正常发票分析
    private function ydxxfpfx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-4',13);
        $res = $data['re_fpxx']['ydxxfpfx'];
        $tmp = '';
        foreach ($res as $year => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$year.'</td>';
            $insert .= '<td>'.$val['normal']['1'].'</td>';
            $insert .= '<td>'.$val['normal']['2'].'</td>';
            $insert .= '<td>'.$val['normal']['3'].'</td>';
            $insert .= '<td>'.$val['normal']['4'].'</td>';
            $insert .= '<td>'.$val['normal']['5'].'</td>';
            $insert .= '<td>'.$val['normal']['6'].'</td>';
            $insert .= '<td>'.$val['normal']['7'].'</td>';
            $insert .= '<td>'.$val['normal']['8'].'</td>';
            $insert .= '<td>'.$val['normal']['9'].'</td>';
            $insert .= '<td>'.$val['normal']['10'].'</td>';
            $insert .= '<td>'.$val['normal']['11'].'</td>';
            $insert .= '<td>'.$val['normal']['12'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="13" style="text-align: center;background-color: #d3d3d3">月度销项正常发票分析</td>
    </tr>
    <tr>
        <td width="10%">年份</td>
        <td width="7%">1月</td>
        <td width="7%">2月</td>
        <td width="7%">3月</td>
        <td width="7%">4月</td>
        <td width="7%">5月</td>
        <td width="7%">6月</td>
        <td width="7%">7月</td>
        <td width="7%">8月</td>
        <td width="7%">9月</td>
        <td width="9%">10月</td>
        <td width="9%">11月</td>
        <td width="9%">12月</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $barData = $labels = $legends = [];
        foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val)
        {
            $barData[] = array_values($val['normal']);
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels) || empty($legends)) {
            $insert = '';
        } else {
            $imgPath = (new NewGraphService())
                ->setTitle('月度销项正常发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,50,0,0])
                ->bar($barData);

            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 月度销项红充发票分析
    private function ydxxfpfx_red(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-5',13);
        $res = $data['re_fpxx']['ydxxfpfx'];
        $tmp = '';
        foreach ($res as $year => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$year.'</td>';
            $insert .= '<td>'.$val['red']['1'].'</td>';
            $insert .= '<td>'.$val['red']['2'].'</td>';
            $insert .= '<td>'.$val['red']['3'].'</td>';
            $insert .= '<td>'.$val['red']['4'].'</td>';
            $insert .= '<td>'.$val['red']['5'].'</td>';
            $insert .= '<td>'.$val['red']['6'].'</td>';
            $insert .= '<td>'.$val['red']['7'].'</td>';
            $insert .= '<td>'.$val['red']['8'].'</td>';
            $insert .= '<td>'.$val['red']['9'].'</td>';
            $insert .= '<td>'.$val['red']['10'].'</td>';
            $insert .= '<td>'.$val['red']['11'].'</td>';
            $insert .= '<td>'.$val['red']['12'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="13" style="text-align: center;background-color: #d3d3d3">月度销项红充发票分析</td>
    </tr>
    <tr>
        <td width="10%">年份</td>
        <td width="7%">1月</td>
        <td width="7%">2月</td>
        <td width="7%">3月</td>
        <td width="7%">4月</td>
        <td width="7%">5月</td>
        <td width="7%">6月</td>
        <td width="7%">7月</td>
        <td width="7%">8月</td>
        <td width="7%">9月</td>
        <td width="9%">10月</td>
        <td width="9%">11月</td>
        <td width="9%">12月</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $barData = $labels = $legends = [];
        foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val)
        {
            $barData[] = array_values($val['red']);
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels) || empty($legends)) {
            $insert = '';
        } else {
            $imgPath = (new NewGraphService())
                ->setTitle('月度销项红充发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,50,0,0])
                ->bar($barData);

            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 月度销项作废发票分析
    private function ydxxfpfx_cancel(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-6',13);
        $res = $data['re_fpxx']['ydxxfpfx'];
        $tmp = '';
        foreach ($res as $year => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$year.'</td>';
            $insert .= '<td>'.$val['cancel']['1'].'</td>';
            $insert .= '<td>'.$val['cancel']['2'].'</td>';
            $insert .= '<td>'.$val['cancel']['3'].'</td>';
            $insert .= '<td>'.$val['cancel']['4'].'</td>';
            $insert .= '<td>'.$val['cancel']['5'].'</td>';
            $insert .= '<td>'.$val['cancel']['6'].'</td>';
            $insert .= '<td>'.$val['cancel']['7'].'</td>';
            $insert .= '<td>'.$val['cancel']['8'].'</td>';
            $insert .= '<td>'.$val['cancel']['9'].'</td>';
            $insert .= '<td>'.$val['cancel']['10'].'</td>';
            $insert .= '<td>'.$val['cancel']['11'].'</td>';
            $insert .= '<td>'.$val['cancel']['12'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="13" style="text-align: center;background-color: #d3d3d3">月度销项作废发票分析</td>
    </tr>
    <tr>
        <td width="10%">年份</td>
        <td width="7%">1月</td>
        <td width="7%">2月</td>
        <td width="7%">3月</td>
        <td width="7%">4月</td>
        <td width="7%">5月</td>
        <td width="7%">6月</td>
        <td width="7%">7月</td>
        <td width="7%">8月</td>
        <td width="7%">9月</td>
        <td width="9%">10月</td>
        <td width="9%">11月</td>
        <td width="9%">12月</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $barData = $labels = $legends = [];
        foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val)
        {
            $barData[] = array_values($val['cancel']);
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels) || empty($legends)) {
            $insert = '';
        } else {
            $imgPath = (new NewGraphService())
                ->setTitle('月度销项作废发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,50,0,0])
                ->bar($barData);

            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 单张开票金额TOP10记录 xx
    private function dzkpjeTOP10jl_xx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-7',6);
        $res = control::sortArrByKey($data['re_fpxx']['dzkpjeTOP10jl_xx'],'totalAmount','desc',true);
        $tmp = '';
        foreach ($res as $key => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.substr($val['date'],0,4).'</td>';
            $insert .= '<td>'.$val['purchaserName'].'</td>';
            $insert .= '<td>'.$val['purchaserTaxNo'].'</td>';
            $insert .= '<td>'.$val['totalAmount'].'</td>';
            $insert .= '<td>'.$val['totalTax'].'</td>';
            $insert .= '<td>'.$val['zhanbi'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">单张开票金额TOP10记录</td>
    </tr>
    <tr>
        <td width="16%">开票年度</td>
        <td width="18%">交易对手名称</td>
        <td width="18%">交易对手税号</td>
        <td width="16%">开票金额</td>
        <td width="16%">开票税额</td>
        <td width="16%">总金额占比(%)</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpxx']['dzkpjeTOP10jl_xx'] as $one)
        {
            $other -= $one['zhanbi'] - 0;
            $pieData[] = $one['zhanbi'] - 0;
            $labels[] = "{$one['purchaserName']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels)) {
            $insert = '';
        } else {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }
            $imgPath = (new NewGraphService())->setTitle('单张开票金额TOP10记录')->setLabels($labels)->pie($pieData);
            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 累计开票金额TOP10企业汇总 xx
    private function ljkpjeTOP10qyhz_xx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-8',7);
        $temp = array_values($data['re_fpxx']['ljkpjeTOP10qyhz_xx']);
        $res = control::sortArrByKey($temp,'total','desc',true);
        $tmp = '';
        foreach ($res as $key => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$val['date'].'</td>';
            $insert .= '<td>'.$val['name'].'</td>';
            $insert .= '<td>'.$val['purchaserTaxNo'].'</td>';
            $insert .= '<td>'.$val['total'].'</td>';
            $insert .= '<td>'.$val['num'].'</td>';
            $insert .= '<td>'.$val['totalZhanbi'].'</td>';
            $insert .= '<td>'.$val['numZhanbi'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">累计开票金额TOP10企业汇总</td>
    </tr>
    <tr>
        <td width="14%">开票年度</td>
        <td width="14%">交易对手名称</td>
        <td width="14%">交易对手税号</td>
        <td width="14%">开票金额</td>
        <td width="14%">开票数</td>
        <td width="15%">总金额占比(%)</td>
        <td width="15%">总笔数占比(%)</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpxx']['ljkpjeTOP10qyhz_xx'] as $one)
        {
            $other -= $one['totalZhanbi'] - 0;
            $pieData[] = $one['totalZhanbi'] - 0;
            $labels[] = "{$one['name']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels)) {
            $insert = '';
        } else {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }
            $imgPath = (new NewGraphService())->setTitle('累计开票金额TOP10企业汇总')->setLabels($labels)->pie($pieData);
            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 下游客户稳定性分析
    private function xykhwdxfx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-10',7);

        //下游企业司龄分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpxx']['xyqyslfb'])];
        $labels = ['1年以下','2-3年','4-5年','6-9年','10年以上'];

        if (empty($barData) || empty($labels)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqyslfb'])) {
                $imgPath = (new NewGraphService())
                    ->setXLabels($labels)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">下游企业司龄分布（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //下游企业合作年限分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpxx']['xyqyhznxfb'])];
        $labels = ['1年','2年','3年以上'];

        if (empty($barData) || empty($labels)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqyhznxfb'])) {
                $imgPath = (new NewGraphService())
                    ->setXLabels($labels)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">下游企业合作年限分布（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //下游企业更换情况（个）
        $barData = $labels = $legends = [];

        foreach ($data['re_fpxx']['xyqyghqk'] as $key => $val)
        {
            $labels = ['新增','退出'];
            $barData[] = $val;
            $legends[] = $key;
        }

        if (empty($barData) || empty($legends)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqyghqk'])) {
                $imgPath = (new NewGraphService())
                    ->setXLabels($labels)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">下游企业更换情况（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //下游企业稳定性评估  稳定性指数
        $xywdx = $this->xywdx($data['re_fpjx']['xdsForShangxiayou']);
        $xywdx = 0.35 * $xywdx[0] + 0.65 * $xywdx[1] + 0.2 > 1 ? 1 : 0.35 * $xywdx[0] + 0.65 * $xywdx[1] + 0.2;
        $xywdx = sprintf('%.1f',$xywdx);

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="2" style="text-align: center;background-color: #d3d3d3">稳定性指数</td>
    </tr>
    <tr>
        <td width="30%">{$xywdx}</td>
        <td width="70%"></td>
    </tr>
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td width="30%" style="text-align: center;background-color: #d3d3d3">稳定性评分</td>
        <td width="70%" style="text-align: center;background-color: #d3d3d3">评分维度，评分越高稳定性越好</td>
    </tr>
    <tr>
        <td width="30%">1.0 - 0.8</td>
        <td width="70%">
            <p>下游与企业关系高度稳定</p>
            <p>1，下游企业自身稳定好，经营年限和经营状况良好</p>
            <p>2，下游企业与企业合作年限长，合作粘性好，双方互补或依存度高</p>
            <p>3，下游企业更换频率低，大部分合作关系稳定</p>
            <p>4，下游企业采购频率和金额良性增长，分布良好</p>
            <p>5，核心经销商的变化情况，尤其是TOP10或TOP20，如变化不大则稳定性好</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.8 - 0.6</td>
        <td width="70%">
            <p>下游与企业关系稳定良好</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.6 - 0.4</td>
        <td width="70%">
            <p>下游与企业关系稳定一般</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.4以下</td>
        <td width="70%">
            <p>下游与企业关系稳定较差</p>
        </td>
    </tr>
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 下游客户集中度分析
    private function xykfjzdfx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-11',7);

        //下游企业地域分布（个）
        $barData = $labels = $legends = [];

        foreach ($data['re_fpxx']['xyqydyfb'] as $key => $val) {
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($barData) || empty($legends) || empty($labels)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqydyfb'])) {
                $imgPath = (new NewGraphService())
                    ->setTitle('下游企业地域分布（个）')
                    ->setXLabels($labels)
                    ->setXLabelAngle(15)
                    ->setLegends($legends)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">下游企业地域分布（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //下游企业合作年限分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpxx']['xyqyhznxfb'])];
        $labels = ['1年','2年','3年以上'];

        if (empty($barData) || empty($labels)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqyhznxfb'])) {
                $imgPath = (new NewGraphService())
                    ->setXLabels($labels)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">下游企业合作年限分布（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //销售前十企业总占比（%）
        $temp = [];

        foreach ($data['re_fpxx']['xsqsqyzzb'] as $key => $val)
        {
            $barData = $labels = $legends = [];
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;

            $temp[] = (new NewGraphService())
                ->setXLabels($labels)
                ->setXLabelAngle(15)
                ->setLegends($legends)
                ->setMargin([130,50,0,40])
                ->bar($barData);
        }

        if (!empty($temp))
        {
            for ($i=1;$i<=3;$i++)
            {
                if (isset($temp[$i-1]))
                {
                    $temp[$i-1] = str_replace(ROOT_PATH,'',$temp[$i-1]);
                    $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$temp[$i-1]}" />    
    </td>
</tr>
PIC;
                    $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">销售前十企业总占比（%）</td>
    </tr>
    {$insert}
</table>
TEMP;
                    $pdf->writeHTML($html, true, false, false, false, '');

                }else
                {

                }
            }
        }else
        {
            $insert = '';
            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">销售前十企业总占比（%）</td>
    </tr>
    {$insert}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }

        //下游集中度情况评估  集中度指数
        $xyjzd = $this->xyjzd($data['re_fpjx']['xdsForShangxiayou']);
        $xyjzd = 0.35 * $xyjzd[0] + 0.65 * $xyjzd[1] + 0.2 > 1 ? 1 : 0.35 * $xyjzd[0] + 0.65 * $xyjzd[1] + 0.2;
        $xyjzd = sprintf('%.1f',$xyjzd);

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="2" style="text-align: center;background-color: #d3d3d3">集中度指数</td>
    </tr>
    <tr>
        <td width="30%">{$xyjzd}</td>
        <td width="70%"></td>
    </tr>
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td width="30%" style="text-align: center;background-color: #d3d3d3">集中度评分</td>
        <td width="70%" style="text-align: center;background-color: #d3d3d3">评分维度，评分越高集中度越高，企业蕴藏风险越大，易受区域行业和金融政策、交通运输、资源分布、商业风险等因素影响</td>
    </tr>
    <tr>
        <td width="30%">1.0 - 0.8</td>
        <td width="70%">
            <p>下游企业集中度很高</p>
            <p>1，下游企业区域分布集中</p>
            <p>2，下游企业业务集中度高，少部分下游企业交易额总量占比高</p>
            <p>3，下游较少部分企业在企业主要商品的销售中占比高</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.8 - 0.6</td>
        <td width="70%">
            <p>下游企业集中度较高</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.6 - 0.4</td>
        <td width="70%">
            <p>下游企业集中度一般，较分散</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.4以下</td>
        <td width="70%">
            <p>下游企业集中度低，高度分散</p>
        </td>
    </tr>
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 企业销售情况分布
    private function qyxsqkfb(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-12',7);

        //企业销售情况分布（万元）
        $lineData = $legends = [];
        foreach ($data['re_fpxx']['qyxsqkyc'] as $key => $val) {
            $lineData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($lineData) || empty($legends)) {
            $insert = '';
        } else {
            $imgPath = (new NewGraphService())
                ->setLegends($legends)
                ->setXLabels(['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'])
                ->line($lineData);

            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">企业销售情况分布</td>
    </tr>
   {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 年度进项发票情况汇总
    private function ndjxfpqkhz(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-14',7);

        $insert = '<tr>';
        $insert .= '<td>'.$data['re_fpjx']['ndjxfpqkhz']['min'].' - '.$data['re_fpjx']['ndjxfpqkhz']['max'].'</td>';
        $insert .= '<td>'.$data['re_fpjx']['ndjxfpqkhz']['normalNum'].'</td>';
        $insert .= '<td>'.$data['re_fpjx']['ndjxfpqkhz']['normal'].'</td>';
        $insert .= '</tr>';

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="13" style="text-align: center;background-color: #d3d3d3">年度进项发票情况汇总</td>
    </tr>
    <tr>
        <td width="34%">统计周期</td>
        <td width="33%">有效数量</td>
        <td width="33%">有效金额</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 月度进项发票分析
    private function ydjxfpfx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-15',7);
        //月度进项发票分析
        $res = $data['re_fpjx']['ydjxfpfx'];
        $tmp = '';
        foreach ($res as $year => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$year.'</td>';
            $insert .= '<td>'.$val['1'].'</td>';
            $insert .= '<td>'.$val['2'].'</td>';
            $insert .= '<td>'.$val['3'].'</td>';
            $insert .= '<td>'.$val['4'].'</td>';
            $insert .= '<td>'.$val['5'].'</td>';
            $insert .= '<td>'.$val['6'].'</td>';
            $insert .= '<td>'.$val['7'].'</td>';
            $insert .= '<td>'.$val['8'].'</td>';
            $insert .= '<td>'.$val['9'].'</td>';
            $insert .= '<td>'.$val['10'].'</td>';
            $insert .= '<td>'.$val['11'].'</td>';
            $insert .= '<td>'.$val['12'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="13" style="text-align: center;background-color: #d3d3d3">月度进项发票分析</td>
    </tr>
    <tr>
        <td width="10%">年份</td>
        <td width="7%">1月</td>
        <td width="7%">2月</td>
        <td width="7%">3月</td>
        <td width="7%">4月</td>
        <td width="7%">5月</td>
        <td width="7%">6月</td>
        <td width="7%">7月</td>
        <td width="7%">8月</td>
        <td width="7%">9月</td>
        <td width="9%">10月</td>
        <td width="9%">11月</td>
        <td width="9%">12月</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $barData = $labels = $legends = [];
        foreach ($data['re_fpjx']['ydjxfpfx'] as $key => $val)
        {
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $barData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($barData) || empty($legends)) {
            $insert = '';
        } else {
            $imgPath = (new NewGraphService())
                ->setTitle('月度进项发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,0,0,0])
                ->bar($barData);

            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 累计开票金额TOP10企业汇总 jx
    private function ljkpjeTOP10qyhz_jx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-16',7);
        $temp = array_values($data['re_fpjx']['ljkpjeTOP10qyhz_jx']);
        $res = control::sortArrByKey($temp,'total','desc',true);
        $tmp = '';
        foreach ($res as $key => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.$val['date'].'</td>';
            $insert .= '<td>'.$val['name'].'</td>';
            $insert .= '<td>'.$val['salesTaxNo'].'</td>';
            $insert .= '<td>'.$val['total'].'</td>';
            $insert .= '<td>'.$val['num'].'</td>';
            $insert .= '<td>'.$val['totalZhanbi'].'</td>';
            $insert .= '<td>'.$val['numZhanbi'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="7" style="text-align: center;background-color: #d3d3d3">累计开票金额TOP10企业汇总</td>
    </tr>
    <tr>
        <td width="14%">开票年度</td>
        <td width="14%">交易对手名称</td>
        <td width="14%">交易对手税号</td>
        <td width="14%">开票金额</td>
        <td width="14%">开票数</td>
        <td width="15%">总金额占比(%)</td>
        <td width="15%">总笔数占比(%)</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpjx']['ljkpjeTOP10qyhz_jx'] as $one)
        {
            $other -= $one['totalZhanbi'] - 0;
            $pieData[] = $one['totalZhanbi'] - 0;
            $labels[] = "{$one['name']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels)) {
            $insert = '';
        } else {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }
            $imgPath = (new NewGraphService())->setTitle('累计开票金额TOP10企业汇总')->setLabels($labels)->pie($pieData);
            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 单张开票金额TOP10企业汇总 jx
    private function dzkpjeTOP10jl_jx(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-17',6);
        $res = control::sortArrByKey($data['re_fpjx']['dzkpjeTOP10jl_jx'],'totalAmount','desc',true);
        $tmp = '';
        foreach ($res as $key => $val) {
            $insert = '<tr>';
            $insert .= '<td>'.substr($val['date'],0,4).'</td>';
            $insert .= '<td>'.$val['salesTaxName'].'</td>';
            $insert .= '<td>'.$val['salesTaxNo'].'</td>';
            $insert .= '<td>'.$val['totalAmount'].'</td>';
            $insert .= '<td>'.$val['totalTax'].'</td>';
            $insert .= '<td>'.$val['zhanbi'].'</td>';
            $insert .= '</tr>';
            $tmp .= $insert;
        }
        $insert = $tmp;
        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="6" style="text-align: center;background-color: #d3d3d3">单张开票金额TOP10记录</td>
    </tr>
    <tr>
        <td width="16%">开票年度</td>
        <td width="18%">交易对手名称</td>
        <td width="18%">交易对手税号</td>
        <td width="16%">开票金额</td>
        <td width="16%">开票税额</td>
        <td width="16%">总金额占比(%)</td>
    </tr>
    {$insert}
    {$ocrData}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //图
        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpjx']['dzkpjeTOP10jl_jx'] as $one)
        {
            $other -= $one['zhanbi'] - 0;
            $pieData[] = $one['zhanbi'] - 0;
            $labels[] = "{$one['salesTaxName']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels)) {
            $insert = '';
        } else {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }
            $imgPath = (new NewGraphService())->setTitle('单张开票金额TOP10企业汇总')->setLabels($labels)->pie($pieData);
            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 上游供应商稳定性分析
    private function sygysslfb(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-19',7);

        //上游供应商司龄分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpjx']['sygysslfb'])];
        $labels = ['1年以下','2-3年','4-5年','6-9年','10年以上'];

        if (empty($barData) || empty($labels)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqyslfb'])) {
                $imgPath = (new NewGraphService())
                    ->setXLabels($labels)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">上游供应商司龄分布（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //下游企业合作年限分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpxx']['xyqyhznxfb'])];
        $labels = ['1年','2年','3年以上'];

        if (empty($barData) || empty($labels)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqyhznxfb'])) {
                $imgPath = (new NewGraphService())
                    ->setXLabels($labels)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">下游企业合作年限分布（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //上游供应商地域分布（个）
        $barData = $labels = $legends = [];
        foreach ($data['re_fpjx']['syqydyfb'] as $key => $val)
        {
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels)) {
            $insert = '';
        } else {
            if (!empty($data['re_fpxx']['xyqyghqk'])) {
                $imgPath = (new NewGraphService())
                    ->setXLabels($labels)
                    ->setLegends($legends)
                    ->setXLabelAngle(15)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $imgPath = str_replace(ROOT_PATH,'',$imgPath);
                $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
            } else {
                $insert = '';
            }
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">上游供应商地域分布（个）</td>
    </tr>
    {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        //采购前十供应商总占比（%）
        $temp = [];
        foreach ($data['re_fpjx']['cgqsqyzzb'] as $key => $val)
        {
            $barData = $labels = $legends = [];
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;

            $temp[] = (new NewGraphService())
                ->setXLabels($labels)
                ->setXLabelAngle(15)
                ->setLegends($legends)
                ->setMargin([130,50,0,40])
                ->bar($barData);
        }

        if (!empty($temp))
        {
            for ($i=1;$i<=3;$i++)
            {
                if (isset($temp[$i-1]))
                {
                    $temp[$i-1] = str_replace(ROOT_PATH,'',$temp[$i-1]);
                    $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$temp[$i-1]}" />    
    </td>
</tr>
PIC;
                    $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">采购前十供应商总占比（%）</td>
    </tr>
    {$insert}
</table>
TEMP;
                    $pdf->writeHTML($html, true, false, false, false, '');
                }else
                {

                }
            }
        }else
        {
            $insert = '';
            $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">采购前十供应商总占比（%）</td>
    </tr>
    {$insert}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }

        //上游集中度情况评估  集中度指数
        $syjzd = $this->syjzd($data['re_fpjx']['xdsForShangxiayou']);
        $syjzd = 0.35 * $syjzd[0] + 0.65 * $syjzd[1] + 0.2 > 1 ? 1 : 0.35 * $syjzd[0] + 0.65 * $syjzd[1] + 0.2;
        $syjzd = sprintf('%.1f',$syjzd);

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td colspan="2" style="text-align: center;background-color: #d3d3d3">集中度指数</td>
    </tr>
    <tr>
        <td width="30%">{$syjzd}</td>
        <td width="70%"></td>
    </tr>
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td width="30%" style="text-align: center;background-color: #d3d3d3">集中度评分</td>
        <td width="70%" style="text-align: center;background-color: #d3d3d3">评分维度，评分越高集中度越高，企业蕴藏风险越大，易受区域行业和金融政策、交通运输、资源分布、商业风险等因素影响</td>
    </tr>
    <tr>
        <td width="30%">1.0 - 0.8</td>
        <td width="70%">
            <p>上游企业集中度很高</p>
            <p>1，上游企业区域分布集中</p>
            <p>2，上游企业业务集中度高，少部分上游企业交易额总量占比高</p>
            <p>3，上游较少部分企业在企业主要商品的销售中占比高</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.8 - 0.6</td>
        <td width="70%">
            <p>上游企业集中度较高</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.6 - 0.4</td>
        <td width="70%">
            <p>上游企业集中度一般，较分散</p>
        </td>
    </tr>
    <tr>
        <td width="30%">0.4以下</td>
        <td width="70%">
            <p>上游企业集中度低，高度分散</p>
        </td>
    </tr>
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //深度报告字段 必执行的 企业采购情况分布
    private function qycgqkfb(Tcpdf $pdf, $data)
    {
        $ocrData = $this->getOcrData('14-20',7);

        $lineData = $legends = $xLabels = [];
        $legends = [$data['re_fpjx']['qycgqkyc']['label']];
        $xLabels = $data['re_fpjx']['qycgqkyc']['xAxes'];
        $lineData = [$data['re_fpjx']['qycgqkyc']['data']];

        if (empty($legends) || empty($xLabels) || empty($lineData)) {
            $insert = '';
        } else {
            $imgPath = (new NewGraphService())
                ->setLegends($legends)
                ->setXLabels($xLabels)
                ->line($lineData);

            $imgPath = str_replace(ROOT_PATH,'',$imgPath);
            $insert = <<<PIC
<tr>
    <td>
        <img src="https://api.meirixindong.com/{$imgPath}" />    
    </td>
</tr>
PIC;
        }

        $html = <<<TEMP
<table border="1" cellpadding="5" style="border-collapse: collapse;width: 100%;text-align: center">
    <tr>
        <td style="text-align: center;background-color: #d3d3d3">企业采购情况分布</td>
    </tr>
   {$insert}
</table>
TEMP;
        $pdf->writeHTML($html, true, false, false, false, '');
    }

    //下游稳定性
    private function xywdx($data)
    {
        $siling=$data['下游司龄'];
        $hezuo=$data['下游合作年限'];

        //计算A
        $type5=$siling['type5'] ?? 0;
        $total=array_sum($siling);
        if ($total == 0)
        {
            $A=0;
        }else
        {
            $A=sprintf('%.1f',$type5/$total);

            if ($A >= 0.6)
            {
                $A=1;
            }elseif ($A >= 0.4)
            {
                $A=0.9;
            }else
            {
                $A=0.8;
            }
        }

        //计算B
        if (isset($hezuo['type3']))
        {
            $type3=$hezuo['type3'];
            $total=array_sum($hezuo);
            if ($total == 0)
            {
                $B=0;
            }else
            {
                $B=sprintf('%.1f',$type3/$total);

                if ($B >= 0.6)
                {
                    $B=1;
                }elseif ($B >= 0.4)
                {
                    $B=0.9;
                }else
                {
                    $B=0.8;
                }
            }
        }else
        {
            $B=0;
        }

        return [$A,$B];
    }

    //下游集中度
    private function xyjzd($data)
    {
        $dyfb=$data['下游地域分布'];
        $xsqs=$data['下游销售前十'];

        //计算A
        if (empty($dyfb))
        {
            $A=0;
        }else
        {
            $dyfb=current($dyfb);

            //找出最大的数
            $max=max($dyfb);

            $total=array_sum($dyfb);

            $A=sprintf('%.1f',$max/$total);

            if ($A >= 0.6)
            {
                $A=1;
            }elseif ($A >= 0.4)
            {
                $A=0.9;
            }else
            {
                $A=0.8;
            }
        }

        //计算B
        if (empty($xsqs))
        {
            $B=0;
        }else
        {
            $xsqs=current($xsqs);

            $B=0;
            foreach ($xsqs as $key => $one)
            {
                $B+=$one;
            }

            if ($B >= 60)
            {
                $B=1;
            }elseif ($B >= 40)
            {
                $B=0.9;
            }else
            {
                $B=0.8;
            }
        }

        return [$A,$B];
    }

    //上游集中度
    private function syjzd($data)
    {
        $dyfb=$data['上游地域分布'];
        $xsqs=$data['上游销售前十'];

        //计算A
        if (empty($dyfb))
        {
            $A=0;
        }else
        {
            $dyfb=current($dyfb);

            //找出最大的数
            $max=max($dyfb);

            $total=array_sum($dyfb);

            $A=sprintf('%.1f',$max/$total);

            if ($A >= 0.6)
            {
                $A=1;
            }elseif ($A >= 0.4)
            {
                $A=0.9;
            }else
            {
                $A=0.8;
            }
        }

        //计算B
        if (empty($xsqs))
        {
            $B=0;
        }else
        {
            $xsqs=current($xsqs);

            $B=0;
            foreach ($xsqs as $key => $one)
            {
                $B+=$one;
            }

            if ($B >= 60)
            {
                $B=1;
            }elseif ($B >= 40)
            {
                $B=0.9;
            }else
            {
                $B=0.8;
            }
        }

        return [$A,$B];
    }

    //并发请求数据
    private function cspHandleData($indexStr = '')
    {
        $catalog = $this->pdf_Catalog($indexStr);

        //创建csp对象
        $csp = CspService::getInstance()->create();

        //淘数 基本信息 工商信息
        array_search('getRegisterInfo', $catalog) === false ?: $csp->add('getRegisterInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $this->entName], 'getRegisterInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

            return $res;
        });

        //企查查 基本信息 工商信息
        array_search('GetBasicDetailsByName', $catalog) === false ?: $csp->add('GetBasicDetailsByName', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIV4/GetBasicDetailsByName', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 股东信息
        array_search('getRegisterInfo', $catalog) === false ?: $csp->add('getShareHolderInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getShareHolderInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 高管信息
        array_search('getRegisterInfo', $catalog) === false ?: $csp->add('getMainManagerInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getMainManagerInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 变更信息
        array_search('getRegisterInfo', $catalog) === false ?: $csp->add('getRegisterChangeInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getRegisterChangeInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 经营异常
        array_search('getRegisterInfo', $catalog) === false ?: $csp->add('GetOpException', function () {

            $postData = ['keyNo' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIException/GetOpException', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 实际控制人
        array_search('Beneficiary', $catalog) === false ?: $csp->add('Beneficiary', function () {

            $postData = [
                'companyName' => $this->entName,
                'percent' => 0,
                'mode' => 0,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Beneficiary/GetBeneficiary', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            $tmp = [];

            if (count($res['BreakThroughList']) > 0) {
                $total = current($res['BreakThroughList']);
                $total = substr($total['TotalStockPercent'], 0, -1);

                if ($total >= 50) {
                    //如果第一个人就是大股东了，就直接返回
                    $tmp = current($res['BreakThroughList']);

                } else {
                    //把返回的所有人加起来和100做减法，求出坑
                    $hole = 100;
                    foreach ($res['BreakThroughList'] as $key => $val) {
                        $hole -= substr($val['TotalStockPercent'], 0, -1);
                    }

                    //求出坑的比例，如果比第一个人大，那就是特殊机构，如果没第一个人大，那第一个人就是控制人
                    if ($total > $hole) $tmp = current($res['BreakThroughList']);
                }
            } else {
                $tmp = null;
            }

            return $tmp;
        });

        //淘数 企查查 历史沿革
        array_search('getHistoricalEvolution', $catalog) === false ?: $csp->add('getHistoricalEvolution', function () {

            $res = XinDongService::getInstance()->getHistoricalEvolution($this->entName);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外投资
        array_search('lawPersonInvestmentInfo', $catalog) === false ?: $csp->add('lawPersonInvestmentInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'lawPersonInvestmentInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外任职
        array_search('getLawPersontoOtherInfo', $catalog) === false ?: $csp->add('getLawPersontoOtherInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getLawPersontoOtherInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 企业对外投资
        array_search('getInvestmentAbroadInfo', $catalog) === false ?: $csp->add('getInvestmentAbroadInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getInvestmentAbroadInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //淘数 分支机构
        array_search('getBranchInfo', $catalog) === false ?: $csp->add('getBranchInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getBranchInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 银行信息
        array_search('GetCreditCodeNew', $catalog) === false ?: $csp->add('GetCreditCodeNew', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICreditCode/GetCreditCodeNew', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 公司概况
        array_search('SearchCompanyFinancings', $catalog) === false ?: $csp->add('SearchCompanyFinancings', function () {

            $postData = ['searchKey' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BusinessStateV4/SearchCompanyFinancings', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 招投标
        array_search('TenderSearch', $catalog) === false ?: $csp->add('TenderSearch', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Tender/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 购地信息
        array_search('LandPurchaseList', $catalog) === false ?: $csp->add('LandPurchaseList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandPurchase/LandPurchaseList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 土地公示
        array_search('LandPublishList', $catalog) === false ?: $csp->add('LandPublishList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandPublish/LandPublishList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 土地转让
        array_search('LandTransferList', $catalog) === false ?: $csp->add('LandTransferList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $post = ['id' => $one['Id']];
                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferDetail', $post);
                    ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = $detail['result'] : $detail = null;
                    $one['detail'] = $detail;
                }
                unset($one);
            }

            return $res;
        });

        //企查查 建筑资质证书
        array_search('Qualification', $catalog) === false ?: $csp->add('Qualification', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Qualification/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 建筑工程项目
        array_search('BuildingProject', $catalog) === false ?: $csp->add('BuildingProject', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BuildingProject/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 债券信息
        array_search('BondList', $catalog) === false ?: $csp->add('BondList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Bond/BondList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 网站信息
        array_search('GetCompanyWebSite', $catalog) === false ?: $csp->add('GetCompanyWebSite', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'WebSiteV4/GetCompanyWebSite', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 微博
        array_search('Microblog', $catalog) === false ?: $csp->add('Microblog', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Microblog/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 新闻舆情
        array_search('CompanyNews', $catalog) === false ?: $csp->add('CompanyNews', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CompanyNews/SearchNews', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //乾启 近三年团队人数变化率
        array_search('itemInfo', $catalog) === false ?: $csp->add('itemInfo', function () {

            $postData = ['entName' => $this->entName];

            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);

            if ($res['code'] === 200 && !empty($res['result'])) {

                $yearArr = array_keys($res['result']);
                $dataArr = array_values($res['result']);
                $res = [];

                for ($i = 0; $i < 3; $i++) {

                    if (isset($dataArr[$i]['SOCNUM']) && is_numeric($dataArr[$i]['SOCNUM'])) {
                        $SOCNUM_1 = (int)$dataArr[$i]['SOCNUM'];
                    } else {
                        $SOCNUM_1 = null;
                    }

                    if (isset($dataArr[$i + 1]['SOCNUM']) && is_numeric($dataArr[$i + 1]['SOCNUM'])) {
                        $SOCNUM_2 = (int)$dataArr[$i + 1]['SOCNUM'];
                    } else {
                        $SOCNUM_2 = null;
                    }

                    if ($SOCNUM_1 !== null && $SOCNUM_2 !== null && $SOCNUM_2 !== 0) {
                        $res[] = ['year' => $yearArr[$i], 'yoy' => ($SOCNUM_1 - $SOCNUM_2) / $SOCNUM_2, 'num' => $SOCNUM_1];
                    } else {
                        $res[] = ['year' => $yearArr[$i], 'yoy' => null, 'num' => $SOCNUM_1];
                    }
                }

            } else {
                $res = null;
            }

            return $res;
        });

        //企查查 建筑企业-专业注册人员
        array_search('BuildingRegistrar', $catalog) === false ?: $csp->add('BuildingRegistrar', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BuildingRegistrar/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 招聘信息
        array_search('Recruitment', $catalog) === false ?: $csp->add('Recruitment', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'Recruitment/GetList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //龙信 财务
        array_search('FinanceData', $catalog) === false ?: $csp->add('FinanceData', function () {

            $postData = [
                'entName' => $this->entName,
                'code' => '',
                'beginYear' => date('Y') - 1,
                'dataCount' => 5,//取最近几年的
            ];

            $res = (new LongXinService())->setCheckRespFlag(true)->getFinanceData($postData,false);

            if ($res['code'] !== 200) return '';

            ksort($res['result']);
            CommonService::getInstance()->log4PHP($res);

            if (!empty($res['result'])) {
                $tmp = $legend = [];
                foreach ($res['result'] as $year => $val) {
                    $legend[] = $year;
                    $tmp[] = [
                        round($val['ASSGRO_yoy'] * 100,3),
                        round($val['LIAGRO_yoy'] * 100,3),
                        round($val['VENDINC_yoy'] * 100,3),
                        round($val['MAIBUSINC_yoy'] * 100,3),
                        round($val['PROGRO_yoy'] * 100,3),
                        round($val['NETINC_yoy'] * 100,3),
                        round($val['RATGRO_yoy'] * 100,3),
                        round($val['TOTEQU_yoy'] * 100,3),
                    ];
                }
                $res['result'] = $tmp;
            }

            $labels = ['资产总额', '负债总额', '营业总收入', '主营业务收入', '利润总额', '净利润', '纳税总额', '所有者权益'];

            $extension = [
                'width' => 1200,
                'height' => 700,
                'title' => $this->entName . ' - 财务非授权 - 同比',
                'xTitle' => '此图为概况信息',
                //'yTitle'=>$this->entName,
                'titleSize' => 14,
                'legend' => $legend
            ];

            $tmp = [];
            $tmp['pic'] = CommonService::getInstance()->createBarPic($res['data'], $labels, $extension);
            $tmp['data'] = $res['result'];

            return $tmp;
        });

        //企查查 业务概况
        array_search('SearchCompanyCompanyProducts', $catalog) === false ?: $csp->add('SearchCompanyCompanyProducts', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CompanyProductV4/SearchCompanyCompanyProducts', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 专利
        array_search('PatentV4Search', $catalog) === false ?: $csp->add('PatentV4Search', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'PatentV4/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 软件著作权
        array_search('SearchSoftwareCr', $catalog) === false ?: $csp->add('SearchSoftwareCr', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchSoftwareCr', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 商标
        array_search('tmSearch', $catalog) === false ?: $csp->add('tmSearch', function () {

            $postData = [
                'keyword' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'tm/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 作品著作权
        array_search('SearchCopyRight', $catalog) === false ?: $csp->add('SearchCopyRight', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchCopyRight', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 证书资质
        array_search('SearchCertification', $catalog) === false ?: $csp->add('SearchCertification', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICertification/SearchCertification', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 纳税信用等级
        array_search('satparty_xin', $catalog) === false ?: $csp->add('satparty_xin', function () {

            $doc_type = 'satparty_xin';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 税务许可信息
        array_search('satparty_xuke', $catalog) === false ?: $csp->add('satparty_xuke', function () {

            $doc_type = 'satparty_xuke';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 税务登记信息
        array_search('satparty_reg', $catalog) === false ?: $csp->add('satparty_reg', function () {

            $doc_type = 'satparty_reg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 税务非正常户
        array_search('satparty_fzc', $catalog) === false ?: $csp->add('satparty_fzc', function () {

            $doc_type = 'satparty_fzc';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 欠税信息
        array_search('satparty_qs', $catalog) === false ?: $csp->add('satparty_qs', function () {

            $doc_type = 'satparty_qs';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 涉税处罚公示
        array_search('satparty_chufa', $catalog) === false ?: $csp->add('satparty_chufa', function () {

            $doc_type = 'satparty_chufa';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 行政许可
        array_search('GetAdministrativeLicenseList', $catalog) === false ?: $csp->add('GetAdministrativeLicenseList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = ['id' => $one['Id']];

                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseDetail', $postData);

                    if ($detail['code'] == 200 && !empty($detail['result'])) {
                        $one['detail'] = $detail['result'];
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 行政处罚
        array_search('GetAdministrativePenaltyList', $catalog) === false ?: $csp->add('GetAdministrativePenaltyList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = ['id' => $one['Id']];

                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyDetail', $postData);

                    if ($detail['code'] == 200 && !empty($detail['result'])) {
                        $one['detail'] = $detail['result'];
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 环保处罚
        array_search('epbparty', $catalog) === false ?: $csp->add('epbparty', function () {

            $doc_type = 'epbparty';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 重点监控企业名单
        array_search('epbparty_jkqy', $catalog) === false ?: $csp->add('epbparty_jkqy', function () {

            $doc_type = 'epbparty_jkqy';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 环保企业自行监测结果
        array_search('epbparty_zxjc', $catalog) === false ?: $csp->add('epbparty_zxjc', function () {

            $doc_type = 'epbparty_zxjc';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 环保 环评公示数据
        array_search('epbparty_huanping', $catalog) === false ?: $csp->add('epbparty_huanping', function () {

            $doc_type = 'epbparty_huanping';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关企业
        array_search('custom_qy', $catalog) === false ?: $csp->add('custom_qy', function () {

            $doc_type = 'custom_qy';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关许可
        array_search('custom_xuke', $catalog) === false ?: $csp->add('custom_xuke', function () {

            $doc_type = 'custom_xuke';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关信用
        array_search('custom_credit', $catalog) === false ?: $csp->add('custom_credit', function () {

            $doc_type = 'custom_credit';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 海关 海关处罚
        array_search('custom_punish', $catalog) === false ?: $csp->add('custom_punish', function () {

            $doc_type = 'custom_punish';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 央行行政处罚
        array_search('pbcparty', $catalog) === false ?: $csp->add('pbcparty', function () {

            $doc_type = 'pbcparty';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 银保监会处罚公示
        array_search('pbcparty_cbrc', $catalog) === false ?: $csp->add('pbcparty_cbrc', function () {

            $doc_type = 'pbcparty_cbrc';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 证监会处罚公示
        array_search('pbcparty_csrc_chufa', $catalog) === false ?: $csp->add('pbcparty_csrc_chufa', function () {

            $doc_type = 'pbcparty_csrc_chufa';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 证监会许可信息
        array_search('pbcparty_csrc_xkpf', $catalog) === false ?: $csp->add('pbcparty_csrc_xkpf', function () {

            $doc_type = 'pbcparty_csrc_xkpf';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 外汇局处罚
        array_search('safe_chufa', $catalog) === false ?: $csp->add('safe_chufa', function () {

            $doc_type = 'safe_chufa';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 一行两会 外汇局许可
        array_search('safe_xuke', $catalog) === false ?: $csp->add('safe_xuke', function () {

            $doc_type = 'safe_xuke';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 法院公告
        array_search('fygg', $catalog) === false ?: $csp->add('fygg', function () {

            $doc_type = 'fygg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 开庭公告
        array_search('ktgg', $catalog) === false ?: $csp->add('ktgg', function () {

            $doc_type = 'ktgg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 裁判文书
        array_search('cpws', $catalog) === false ?: $csp->add('cpws', function () {

            $doc_type = 'cpws';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 执行公告
        array_search('zxgg', $catalog) === false ?: $csp->add('zxgg', function () {

            $doc_type = 'zxgg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 失信公告
        array_search('shixin', $catalog) === false ?: $csp->add('shixin', function () {

            $doc_type = 'shixin';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 被执行人
        array_search('SearchZhiXing', $catalog) === false ?: $csp->add('SearchZhiXing', function () {

            $postData = [
                'searchKey' => $this->entName,
                'isExactlySame' => true,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CourtV4/SearchZhiXing', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 司法查冻扣
        array_search('sifacdk', $catalog) === false ?: $csp->add('sifacdk', function () {

            $doc_type = 'sifacdk';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //淘数 动产抵押
        array_search('getChattelMortgageInfo', $catalog) === false ?: $csp->add('getChattelMortgageInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 20,
            ], 'getChattelMortgageInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //淘数 股权出质
        array_search('getEquityPledgedInfo', $catalog) === false ?: $csp->add('getEquityPledgedInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 20,
            ], 'getEquityPledgedInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 企业年报 其中有对外担保 这个字段ProvideAssuranceList
        array_search('GetAnnualReport', $catalog) === false ?: $csp->add('GetAnnualReport', function () {

            $postData = [
                'keyNo' => $this->entName,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AR/GetAnnualReport', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            $total = null;

            if (!empty($res)) {
                $list = [];

                //不是空就找出来有没有对外担保
                foreach ($res as $arr) {
                    if (!isset($arr['ProvideAssuranceList']) || empty($arr['ProvideAssuranceList'])) continue;

                    //如果有对外担保数据
                    foreach ($arr['ProvideAssuranceList'] as $one) {
                        if (count($list) < 20) {
                            $list[] = $one;
                        }

                        $total = (int)$total + 1;
                    }
                }
            }

            $tmp['list'] = empty($list) ? null : $list;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 土地抵押
        array_search('GetLandMortgageList', $catalog) === false ?: $csp->add('GetLandMortgageList', function () {

            $postData = [
                'keyWord' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandMortgage/GetLandMortgageList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 应收账款
        array_search('company_zdw_yszkdsr', $catalog) === false ?: $csp->add('company_zdw_yszkdsr', function () {

            $doc_type = 'company_zdw_yszkdsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 租赁登记
        array_search('company_zdw_zldjdsr', $catalog) === false ?: $csp->add('company_zdw_zldjdsr', function () {

            $doc_type = 'company_zdw_zldjdsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 保证金质押登记
        array_search('company_zdw_bzjzydsr', $catalog) === false ?: $csp->add('company_zdw_bzjzydsr', function () {

            $doc_type = 'company_zdw_bzjzydsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 仓单质押
        array_search('company_zdw_cdzydsr', $catalog) === false ?: $csp->add('company_zdw_cdzydsr', function () {

            $doc_type = 'company_zdw_cdzydsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 所有权保留
        array_search('company_zdw_syqbldsr', $catalog) === false ?: $csp->add('company_zdw_syqbldsr', function () {

            $doc_type = 'company_zdw_syqbldsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //法海 中登动产融资 其他动产融资
        array_search('company_zdw_qtdcdsr', $catalog) === false ?: $csp->add('company_zdw_qtdcdsr', function () {

            $doc_type = 'company_zdw_qtdcdsr';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaHaiService())->setCheckRespFlag(true)->getDetail($this->fahaiDetail . $doc_type, $postData);

                    if ($detail['code'] === 200 && !empty($detail['result'])) {
                        $one['detail'] = current($detail['result']);
                    } else {
                        $one['detail'] = null;
                    }
                }
                unset($one);
            }

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        return CspService::getInstance()->exec($csp, 30);
    }


}
