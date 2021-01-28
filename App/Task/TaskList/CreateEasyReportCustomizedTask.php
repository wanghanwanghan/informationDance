<?php

namespace App\Task\TaskList;

use App\Csp\Service\CspService;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\Report\Tcpdf;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class CreateEasyReportCustomizedTask extends TaskBase implements TaskInterface
{
    private $entName;
    private $reportNum;
    private $phone;
    private $type;
    private $dataIndex;

    private $currentHeight = 0;

    //计算翻页不翻页
    function exprAddPage(Tcpdf $pdf, $height = 0, $immediately = false, $pageMaxHeight = 300)
    {
        if ($immediately === true) {
            $this->currentHeight = $height = 0;
            $pdf->AddPage();
        }

        if ($height > $pageMaxHeight && $immediately === false) {
            $this->currentHeight = 0;
            $pdf->AddPage();
        }

        if ($height + $this->currentHeight > $pageMaxHeight && $immediately === false) {
            $this->currentHeight = 0;
            $pdf->AddPage();
        }

        $this->currentHeight += $height;

        return true;
    }

    function __construct($entName, $reportNum, $phone, $type, $dataIndex)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;
        $this->phone = $phone;
        $this->type = $type;
        $this->dataIndex = $dataIndex;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $pdf = new Tcpdf();

        // 设置文档信息
        $pdf->SetCreator('王瀚');
        $pdf->SetAuthor('王瀚');
        $pdf->SetTitle('王瀚');
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
        $pdf->Output(REPORT_PATH . 't.pdf', 'F');//I输出、D下载

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

        }
    }

    //填充数据
    private function fillData(Tcpdf $pdf, $cspData)
    {
        CommonService::getInstance()->log4PHP($cspData);

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
    }

    //基本信息 工商信息
    private function getRegisterInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists('getRegisterInfo',$cspData) && !empty($cspData['getRegisterInfo']))
        {
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
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 股东信息
    private function getShareHolderInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists('getShareHolderInfo',$cspData))
        {
            $insert = '';

            if (!empty($cspData['getShareHolderInfo']))
            {
                foreach ($cspData['getShareHolderInfo'] as $one)
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
        if (array_key_exists('getMainManagerInfo',$cspData))
        {
            $insert = '';

            if (!empty($cspData['getMainManagerInfo']))
            {
                $i = 1;

                foreach ($cspData['getMainManagerInfo'] as $one)
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
        if (array_key_exists('getRegisterChangeInfo',$cspData))
        {
            $insert = '';

            if (!empty($cspData['getRegisterChangeInfo']))
            {
                $i = 1;

                foreach ($cspData['getRegisterChangeInfo']['list'] as $one)
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
        if (array_key_exists('GetOpException',$cspData))
        {
            $insert = '';

            if (!empty($cspData['GetOpException']))
            {
                $i = 1;

                foreach ($cspData['GetOpException']['list'] as $one)
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
        if (array_key_exists('Beneficiary',$cspData))
        {
            $insert = $name = $stock = '';

            if (!empty($cspData['Beneficiary']))
            {
                $name = $cspData['Beneficiary']['Name'];
                $stock = $cspData['Beneficiary']['TotalStockPercent'];

                foreach ($cspData['Beneficiary']['DetailInfoList'] as $one)
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
    <tr><td colspan="2">备注 : 总股权比例 = 持股人股权比例 + 其关联企业所占股权折算后比例</td></tr>
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 历史沿革及重大事项
    private function getHistoricalEvolution(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists('getHistoricalEvolution',$cspData))
        {
            $insert = '';

            if (!empty($cspData['getHistoricalEvolution']))
            {
                $i = 1;

                foreach ($cspData['getHistoricalEvolution'] as $one)
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
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 法人对外投资
    private function lawPersonInvestmentInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists('lawPersonInvestmentInfo',$cspData))
        {
            $insert = '';

            if (!empty($cspData['lawPersonInvestmentInfo']))
            {
                $i = 1;

                foreach ($cspData['lawPersonInvestmentInfo'] as $one)
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
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 法人对外任职
    private function getLawPersontoOtherInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists('getLawPersontoOtherInfo',$cspData))
        {
            $insert = '';

            if (!empty($cspData['getLawPersontoOtherInfo']))
            {
                $i = 1;

                foreach ($cspData['getLawPersontoOtherInfo'] as $one)
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
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 企业对外投资
    private function getInvestmentAbroadInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists('getInvestmentAbroadInfo',$cspData))
        {
            $insert = '';

            if (!empty($cspData['getInvestmentAbroadInfo']))
            {
                $i = 1;

                foreach ($cspData['getInvestmentAbroadInfo']['list'] as $one)
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
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
    }

    //基本信息 分支机构
    private function getBranchInfo(Tcpdf $pdf, $cspData)
    {
        if (array_key_exists('getBranchInfo',$cspData))
        {
            $insert = '';

            if (!empty($cspData['getBranchInfo']))
            {
                $i = 1;

                foreach ($cspData['getBranchInfo'] as $one)
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
                                $temp .= "<td>{$party['caseStateT']}</td>";
                                $temp .= "<td>{$party['pname']}</td>";
                                $temp .= "<td>{$party['execMoney']}</td>";
                                switch ($party['partyType'])
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
        <td width="7%">诉讼地位</td>
    </tr>
    {$insert}
</table>
TEMP;
            $pdf->writeHTML($html, true, false, false, false, '');
        }
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

        //乾启 财务
        array_search('FinanceData', $catalog) === false ?: $csp->add('FinanceData', function () {

            $postData = ['entName' => $this->entName];

            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);

            if ($res['code'] === 200 && !empty($res['result'])) {
                $res = (new QianQiService())->toPercent($res['result']);
            } else {
                $res = null;
            }

            if ($res === null) return $res;

            $count1 = 0;

            ksort($res);

            foreach ($res as $year => $dataArr) {
                $legend[] = $year;
                array_pop($dataArr);
                $tmp = array_map(function ($val) {
                    return is_numeric($val) ? (int)round($val) : null;//四舍五入
                }, array_values($dataArr));
                $data[] = $tmp;
                !empty(array_filter($tmp)) ?: $count1++;
            }

            $labels = ['资产总额', '负债总额', '营业总收入', '主营业务收入', '利润总额', '净利润', '纳税总额', '所有者权益'];

            $extension = [
                'width' => 1200,
                'height' => 700,
                'title' => $count1 == 2 ? '缺少上一年财务数据，财务图表未生成' : $this->entName . ' - 财务非授权 - 同比',
                'xTitle' => '此图为概况信息',
                //'yTitle'=>$this->entName,
                'titleSize' => 14,
                'legend' => $legend
            ];

            $tmp = [];
            $tmp['pic'] = CommonService::getInstance()->createBarPic($data, $labels, $extension);
            $tmp['data'] = $data;

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

        return CspService::getInstance()->exec($csp, 15);
    }


}
