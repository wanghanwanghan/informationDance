<?php

namespace App\Task\TaskList;

use App\Crontab\CrontabList\tool\Invoice;
use App\Csp\Service\CspService;
use App\HttpController\Models\Api\InvoiceIn;
use App\HttpController\Models\Api\InvoiceOut;
use App\HttpController\Models\Api\OcrQueue;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\NewGraph\NewGraphService;
use App\HttpController\Service\OneSaid\OneSaidService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\ZhongWang\ZhongWangService;
use App\Process\Service\ProcessService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use wanghanwanghan\someUtils\control;

class CreateDeepReportTask extends TaskBase implements TaskInterface
{
    private $entName;
    private $code;
    private $reportNum;
    private $phone;
    private $type;

    private $inDetail = [];
    private $outDetail = [];

    private $fz = [];
    private $fx = [];
    private $fz_detail = [];
    private $fx_detail = [];

    function __construct($entName, $code, $reportNum, $phone, $type)
    {
        $this->entName = $entName;
        $this->code = $code;
        $this->reportNum = $reportNum;
        $this->phone = $phone;
        $this->type = $type;

        return parent::__construct();
    }

    private function getReceiptDataTest()
    {
        $in = InvoiceIn::create()->where('purchaserTaxNo',$this->code)->all();
        $this->inDetail = obj2Arr($in);
        $out = InvoiceOut::create()->where('salesTaxNo',$this->code)->all();
        $this->outDetail = obj2Arr($out);
    }

    function run(int $taskId, int $workerIndex)
    {
        $tmp = new TemplateProcessor(REPORT_MODEL_PATH . 'DeepReportModel_1.docx');

        $userInfo = User::create()->where('phone',$this->phone)->get();

        switch ($this->type)
        {
            case 'xd':
                $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'xd_logo.png', 'width' => 200, 'height' => 40]);
                $tmp->setValue('selectMore', '如需更多信息登录移动端小程序 信动智调 查看');
                break;
            case 'wh':
                $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'wh_logo.png', 'width' => 200, 'height' => 40]);
                $tmp->setValue('selectMore', '如需更多信息登录移动端小程序 炜衡智调 查看');
                break;
            default:
                $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'xd_logo.png', 'width' => 200, 'height' => 40]);
                $tmp->setValue('selectMore', '如需更多信息登录移动端小程序 信动智调 查看');
        }

        $tmp->setValue('createEnt', $userInfo->company);

        $tmp->setValue('entName', $this->entName);

        $tmp->setValue('reportNum', substr($this->reportNum,0,14));

        $tmp->setValue('time', Carbon::now()->format('Y年m月d日'));

        $reportVal = [];

        //取发票数据，以后切换成api的
        $this->getReceiptDataTest();

        //发票
        $invoiceObj = (new Invoice($this->inDetail,$this->outDetail));

        //5.2主营商品分析
        $zyspfx=$invoiceObj->zyspfx();
        $reportVal['re_fpxx']['zyspfx']=$zyspfx;

        //5.4主要成本分析
        $zycbfx=$invoiceObj->zycbfx();
        $reportVal['re_fpjx']['zycbfx']=$zycbfx;
        //各种费用在统计周期内合并
        $reportVal['re_fpjx']['zycbfx_new']=$invoiceObj->zycbfx_new($zycbfx[1]);

        //6.1企业开票情况汇总
        $qykpqkhz=$invoiceObj->qykpqkhz();
        $reportVal['re_fpxx']['qykpqkhz']=$qykpqkhz;
        //统计周期从这里拿
        $reportVal['commonData']['zhouqi'] = $qykpqkhz['zhouqi']['min'].' - '.$qykpqkhz['zhouqi']['max'];

        //6.2.1年度销项发票情况汇总
        $ndxxfpqkhz=$invoiceObj->ndxxfpqkhz();
        $reportVal['re_fpxx']['ndxxfpqkhz']=$ndxxfpqkhz;

        //6.2.2月度销项发票分析
        $ydxxfpfx=$invoiceObj->ydxxfpfx();
        $reportVal['re_fpxx']['ydxxfpfx']=$ydxxfpfx;

        //6.2.5单张开票金额TOP10记录
        $dzkpjeTOP10jl_xx=$invoiceObj->dzkpjeTOP10jl_xx();
        $reportVal['re_fpxx']['dzkpjeTOP10jl_xx']=$dzkpjeTOP10jl_xx;
        empty($reportVal['re_fpxx']['dzkpjeTOP10jl_xx']) ?: $reportVal['re_fpxx']['dzkpjeTOP10jl_xx'] = control::sortArrByKey($reportVal['re_fpxx']['dzkpjeTOP10jl_xx'],'totalAmount');

        //6.2.6累计开票金额TOP10企业汇总
        $ljkpjeTOP10qyhz_xx=$invoiceObj->ljkpjeTOP10qyhz_xx();
        $reportVal['re_fpxx']['ljkpjeTOP10qyhz_xx']=$ljkpjeTOP10qyhz_xx;
        empty($reportVal['re_fpxx']['ljkpjeTOP10qyhz_xx']) ?: $reportVal['re_fpxx']['ljkpjeTOP10qyhz_xx'] = control::sortArrByKey($reportVal['re_fpxx']['ljkpjeTOP10qyhz_xx'],'total');

        //6.3.1下游客户稳定性分析
        //1，下游企业司龄分布
        $xyqyslfb=$invoiceObj->xyqyslfb();
        $reportVal['re_fpxx']['xyqyslfb']=$xyqyslfb;
        //2，下游企业合作年限分布
        $xyqyhznxfb=$invoiceObj->xyqyhznxfb();
        $reportVal['re_fpxx']['xyqyhznxfb']=$xyqyhznxfb;
        //3，下游企业更换情况
        $xyqyghqk=$invoiceObj->xyqyghqk();
        $reportVal['re_fpxx']['xyqyghqk']=$xyqyghqk;

        //6.3.2下游客户集中度
        //1，下游企业地域分布
        $xyqydyfb=$invoiceObj->xyqydyfb();
        $reportVal['re_fpxx']['xyqydyfb']=$xyqydyfb;
        //2，销售前十企业总占比
        $xsqsqyzzb=$invoiceObj->xsqsqyzzb();
        $reportVal['re_fpxx']['xsqsqyzzb']=$xsqsqyzzb;

        //6.3.3企业销售情况预测
        $qyxsqkyc=$invoiceObj->qyxsqkyc();
        $reportVal['re_fpxx']['qyxsqkyc']=$qyxsqkyc;

        //6.4.1年度进项发票情况汇总
        $ndjxfpqkhz=$invoiceObj->ndjxfpqkhz();
        $reportVal['re_fpjx']['ndjxfpqkhz']=$ndjxfpqkhz;

        //6.4.2月度进项发票分析
        $ydjxfpfx=$invoiceObj->ydjxfpfx();
        $reportVal['re_fpjx']['ydjxfpfx']=$ydjxfpfx;

        //6.4.3累计开票金额TOP10企业汇总
        $ljkpjeTOP10qyhz_jx=$invoiceObj->ljkpjeTOP10qyhz_jx();
        $reportVal['re_fpjx']['ljkpjeTOP10qyhz_jx']=$ljkpjeTOP10qyhz_jx;
        empty($reportVal['re_fpjx']['ljkpjeTOP10qyhz_jx']) ?: $reportVal['re_fpjx']['ljkpjeTOP10qyhz_jx'] = control::sortArrByKey($reportVal['re_fpjx']['ljkpjeTOP10qyhz_jx'],'total');

        //6.4.4单张开票金额TOP10企业汇总
        $dzkpjeTOP10jl_jx=$invoiceObj->dzkpjeTOP10jl_jx();
        $reportVal['re_fpjx']['dzkpjeTOP10jl_jx']=$dzkpjeTOP10jl_jx;
        empty($reportVal['re_fpjx']['dzkpjeTOP10jl_jx']) ?: $reportVal['re_fpjx']['dzkpjeTOP10jl_jx'] = control::sortArrByKey($reportVal['re_fpjx']['dzkpjeTOP10jl_jx'],'totalAmount');

        //6.5.1上游共饮上稳定性分析
        //1，上游供应商司龄分布
        $sygysslfb=$invoiceObj->sygysslfb();
        $reportVal['re_fpjx']['sygysslfb']=$sygysslfb;
        //2，上游供应商合作年限分布
        $sygyshznxfb=$invoiceObj->sygyshznxfb();
        $reportVal['re_fpjx']['sygyshznxfb']=$sygyshznxfb;
        //3，上游供应商更换情况
        $sygysghqk=$invoiceObj->sygysghqk();
        $reportVal['re_fpjx']['sygysghqk']=$sygysghqk;

        //6.5.2上游供应商集中度分析
        //1，上游企业地域分布
        $syqydyfb=$invoiceObj->syqydyfb();
        $reportVal['re_fpjx']['syqydyfb']=$syqydyfb;
        //2，采购前十企业总占比
        $cgqsqyzzb=$invoiceObj->cgqsqyzzb();
        $reportVal['re_fpjx']['cgqsqyzzb']=$cgqsqyzzb;

        //6.5.3企业采购情况预测
        $qycgqkyc=$invoiceObj->qycgqkyc();
        $reportVal['re_fpjx']['qycgqkyc']=$qycgqkyc;

        //储存信动指数-发票项
        $xdsForFaPiao=$invoiceObj->xdsForFaPiao();
        $reportVal['re_fpjx']['xdsForFaPiao']=$xdsForFaPiao;

        //储存信动指数-上下游项
        $xdsForShangxiayou=$invoiceObj->xdsForShangxiayou();
        $reportVal['re_fpjx']['xdsForShangxiayou']=$xdsForShangxiayou;

        //数据填充
        $this->fillData($tmp, $reportVal);

        $tmp->saveAs(REPORT_PATH . $this->reportNum . '.docx');

        $info = ReportInfo::create()->where('phone',$this->phone)->where('filename',$this->reportNum)->get();

        $info->update(['status'=>2]);

        $userEmail = User::create()->where('phone',$this->phone)->get();

        CommonService::getInstance()->sendEmail($userEmail->email,[REPORT_PATH . $this->reportNum . '.docx'],'03',['entName'=>$this->entName]);

        ProcessService::getInstance()->sendToProcess('docx2doc',$this->reportNum);

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        try
        {
            $info = ReportInfo::create()->where('phone',$this->phone)->where('filename',$this->reportNum)->get();

            $file = $throwable->getFile();
            $line = $throwable->getLine();
            $msg = $throwable->getMessage();

            $content = "[file => {$file}] [line => {$line}] [msg => {$msg}]";

            $info->update(['status'=>1,'errInfo' => $content]);

        }catch (\Throwable $e)
        {

        }
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

    //分数旁的一句话或几句话
    private function fz_and_fx_detail(TemplateProcessor $docObj, $data)
    {
        //专利
        $zl = (int)$data['PatentV4Search']['total'];

        //软件著作权
        $rz = (int)$data['SearchSoftwareCr']['total'];

        if ($zl===0 && $rz<2) $this->fz_detail[] = '企业需进一步增强创新研发能力';

        //乾启 财务
        if (empty($data['FinanceData'])) $this->fz_detail[] = '企业经营能力与核心竞争力方面需进一步提升';
        if (!empty($data['FinanceData']) && mt_rand(0,100) > 80) $this->fx_detail[] = '企业需进一步加强在资产负债方面的管控意识';

        //乾启 团队人数
        foreach ($data['itemInfo'] as $oneYear)
        {
            if (isset($oneYear['yoy']) && !empty($oneYear['yoy']) && is_numeric($oneYear['yoy']))
            {
                if ($oneYear['yoy'] < 0.06)
                {
                    $this->fz_detail[] = '企业团队人员管理方面需进一步加强';
                    break;
                }
            }
        }

        //企业资质证书
        if ((int)$data['SearchCertification']['total'] === 0) $this->fz_detail[] = '企业需进一步提升所在行业领域的政府资质或荣誉申领意识';

        //裁判文书
        if ((int)$data['cpws']['total'] > 5) $this->fx_detail[] = '企业的法律经营意识方面需进一步加强';

        //行政处罚+欠税公告+非正常户
        $a = (int)$data['GetAdministrativePenaltyList']['total'];
        $b = (int)$data['satparty_qs']['total'];
        $c = (int)$data['satparty_fzc']['total'];
        if ($a + $b + $c >= 2) $this->fx_detail[] = '企业在接受行政管理方面需进一步完善';

        return true;
    }

    //加入Ocr识别后的文字
    private function addOcrWords()
    {
        try
        {
            $list = OcrQueue::create()->where('reportNum',$this->reportNum)->where('phone',$this->phone)->all();

            $list = obj2Arr($list);

        }catch (\Throwable $e)
        {

        }
    }

    //月度销项发票数据
    private function ydxxfp($res)
    {
        $data=$res;

        $xiaoxiang=$data['type1'];

        //没有就给60分
        if (empty($xiaoxiang) || count($xiaoxiang) < 2) return 60;

        $tmp=[];

        foreach ($xiaoxiang as $year => $val)
        {
            array_push($tmp,array_sum($val));
        }

        $bi=sprintf('%.1f',($tmp[0] - $tmp[1]) / $tmp[1] * 100);

        if ($bi >= 21) return 100;
        if ($bi < 21 && $bi >= 11) return 90;
        if ($bi < 11 && $bi >= 6) return 80;
        if ($bi < 6 && $bi >= 0) return 70;
        if ($bi < 0 && $bi >= -10) return 60;
        if ($bi < -10 && $bi >= -20) return 50;
        if ($bi < -20) return 40;

        return 60;
    }

    //月度进项发票数据
    private function ydjxfp($res)
    {
        $data=$res;

        $xiaoxiang=$data['type1'];
        $jinxiang=$data['type2'];

        //没有就给60分
        if (empty($xiaoxiang) || empty($jinxiang)) return 60;

        //已进项发票为准，去匹配销项发票
        foreach ($jinxiang as $year => $val)
        {
            //先取到最后一个月有数据的年和月
            foreach ($val as $k => $v)
            {
                if (isset($yearMouthDay))
                {
                    continue;
                }

                if ($v > 0) $yearMouthDay=$year.'-'.$k.'-01';
            }
        }

        //往前12个月，计算数据
        $jinxiangTotal=$xiaoxiangTotal=0;
        for ($i=0;$i<12;$i++)
        {
            $format=Carbon::parse($yearMouthDay)->subMonths($i)->format('Y-m');

            $year=explode('-',$format)[0];
            $mouth=explode('-',$format)[1];

            //找进项
            if (isset($jinxiang[$year][$mouth]))
            {
                $jinxiangTotal+=$jinxiang[$year][$mouth];
            }

            //找销项
            if (isset($xiaoxiang[$year][$mouth]))
            {
                $xiaoxiangTotal+=$xiaoxiangTotal[$year][$mouth];
            }
        }

        if ($xiaoxiangTotal==0) return 60;

        $bi=sprintf('%.1f',($xiaoxiangTotal - $jinxiangTotal) / $xiaoxiangTotal * 100);

        if ($bi >= 21) return 100;
        if ($bi < 21 && $bi >= 11) return 90;
        if ($bi < 11 && $bi >= 6) return 80;
        if ($bi < 6 && $bi >= 0) return 70;
        if ($bi < 0 && $bi >= -10) return 60;
        if ($bi < -10 && $bi >= -20) return 50;
        if ($bi < -20) return 40;

        return 60;
    }

    //计算信动分
    private function exprXDS($data)
    {
        //发票销项
        $a=$this->ydxxfp($data['re_fpjx']['xdsForFaPiao']);
        //发票进项
        $b=$this->ydjxfp($data['re_fpjx']['xdsForFaPiao']);

        $this->fz['fapiao']=(0.6 * $a + 0.4 * $b) * 0.3;

        //企业性质
        $a = $this->qyxz($data['getRegisterInfo']);
        //企业对外投资
        $b = $this->qydwtz($data['getInvestmentAbroadInfo']['total']);
        //融资历史
        $c = $this->rzls($data['SearchCompanyFinancings']);
        //计算
        $this->fz['gongshang'] = (0.6 * $a + 0.2 * $b + 0.2 * $c) * 0.1;
        //==============================================================================================================
        //行政许可
        $a = $this->xzxk($data['GetAdministrativeLicenseList']['total']);
        //计算
        $this->fz['xingzheng'] = 0.05 * $a;
        //==============================================================================================================
        //专利
        $a = $this->zl($data['PatentV4Search']['total']);
        //软件著作权
        $b = $this->rjzzq($data['SearchSoftwareCr']['total']);
        //计算
        $this->fz['chuangxinyujishu'] = (0.6 * $a + 0.4 * $b) * 0.1;
        //==============================================================================================================
        //近三年团队人数
        $a = $this->tdrs($data['itemInfo'], 'fz');
        //近两年团队人数
        $b = $this->rybh($data['itemInfo'], 'fz');
        //计算
        $this->fz['tuandui'] = (0.5 * $a + 0.5 * $b) * 0.1;
        //==============================================================================================================
        //招投标
        $a = $this->ztb($data['TenderSearch']['total']);
        //计算
        $this->fz['jingyingxinxi'] = 0.05 * $a;
        //==============================================================================================================
        //财务资产
        $c = $this->cwzc($data['FinanceData']['data'], 'fz');
        //计算
        $this->fz['caiwu'] = $c * 0.35;
        //==============================================================================================================
        //行业位置
        $a = $this->hywz($data['FinanceData']['data'], $data['getRegisterInfo']);
        //计算
        $this->fz['hangyeweizhi'] = 0.02 * $a;
        //==============================================================================================================
        //企业变更信息
        $a = $this->qybgxx($data['getRegisterChangeInfo']['total']);
        //经营异常
        $b = $this->jyyc($data['GetOpException']['total']);
        //计算
        $this->fx['gongshang'] = (0.6 * $a + 0.4 * $b) * 0.05;
        //==============================================================================================================
        //财务资产
        $d = $this->cwzc($data['FinanceData']['data'], 'fx');
        //计算
        $this->fx['caiwu'] = 0.4 * $d;
        //==============================================================================================================
        //近三年团队人数
        $a = $this->tdrs($data['itemInfo'], 'fx');
        //近两年团队人数
        $b = $this->rybh($data['itemInfo'], 'fx');
        //计算
        $this->fx['tuandui'] = (0.3 * $a + 0.7 * $b) * 0.18;
        //==============================================================================================================
        //裁判文书
        $a = $this->pjws($data['cpws']['total']);
        //执行公告
        $b = $this->zxgg($data['zxgg']['total']);
        $s = ($a + $b) / 2;
        //计算
        $this->fx['sifa'] = 0.25 * $s;
        //==============================================================================================================
        //涉税处罚公示
        $a = $this->sscfgs($data['satparty_chufa']['total']);
        //税务非正常户公示
        $b = $this->swfzchgs($data['satparty_fzc']['total']);
        //欠税公告
        $c = $this->qsgg($data['satparty_qs']['total']);
        $s = ($a + $b + $c) / 3;
        //计算
        $this->fx['shuiwu'] = 0.1 * $s;
        //==============================================================================================================
        //行政处罚
        $a = $this->xzcf($data['GetAdministrativePenaltyList']['total']);
        $this->fx['xingzheng'] = 0.02 * $a;
        //==============================================================================================================
        //联合惩戒名单信息（暂无该字段接口，先以司法类中的失信公告代替）失信公告的数量
        $a = $this->sxgg($data['shixin']);
        $this->fx['gaofengxian'] = 0.4 * $a;
        //==============================================================================================================

        return true;
    }

    //失信公告
    private function sxgg($data)
    {
        //总数
        $num = (int)$data;

        if ($num >= 3) return 100;
        if ($num >= 2 && $num <= 1) return 90;
        if ($num < 1) return 0;

        return 0;
    }

    //涉税处罚公示
    private function sscfgs($data)
    {
        //总数
        $num = (int)$data;

        //总数
        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //税务非正常户公示
    private function swfzchgs($data)
    {
        //总数
        $num = (int)$data;

        //总数
        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //欠税公告
    private function qsgg($data)
    {
        //总数
        $num = (int)$data;

        //总数
        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //行政处罚
    private function xzcf($data)
    {
        //总数
        $num = (int)$data;

        //总数
        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //判决文书
    private function pjws($data)
    {
        $num = (int)$data;

        //总数
        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //执行公告
    private function zxgg($data)
    {
        $num = (int)$data;

        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //企业变更信息
    private function qybgxx($data)
    {
        $num = (int)$data;

        //算分
        if ($num > 10) return 100;
        if ($num <= 10 && $num >= 6) return 80;
        if ($num <= 5 && $num >= 3) return 70;
        if ($num <= 2 && $num >= 1) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //经营异常
    private function jyyc($data)
    {
        //总数
        $num = (int)$data;

        //算分
        if ($num > 5) return 100;
        if ($num == 4) return 80;
        if ($num == 3) return 70;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //行业位置
    private function hywz($cw, $jb)
    {
        if (!is_array($cw)) return 0;

        if (empty($cw)) return 0;

        if (!isset($cw[0])) return 0;

        //先拿到营业总收入
        $vendInc = $cw[0][2];;

        $sshy = trim($jb['INDUSTRY']);

        //2017年利润（亿）100000000
        $target = [
            '煤炭开采和洗选业' => 24870.64,
            '石油和天然气开采业' => 7560.07,
            '黑色金属矿采选业' => 4064.44,
            '有色金属矿采选业' => 5104.15,
            '非金属矿采选业' => 4239.89,
            '开采专业及辅助性活动' => 1566.71,
            '其他采矿业' => 37.53,
            '农副食品加工业' => 59894.39,
            '食品制造业' => 22140.85,
            '酒、饮料和精制茶制造业' => 17096.2,
            '烟草制品业' => 8890.91,
            '纺织业' => 36114.43,
            '纺织服装、服饰业' => 20892.12,
            '皮革、毛皮、羽毛及其制品和制鞋业' => 14105.61,
            '木材加工和木、竹、藤、棕、草制品业' => 12947.89,
            '家具制造业' => 8787.88,
            '造纸和纸制品业' => 14840.51,
            '印刷和记录媒介复制业' => 7857.66,
            '文教、工美、体育和娱乐用品制造业' => 15931.04,
            '石油、煤炭及其他燃料加工业' => 40331.5,
            '化学原料和化学制品制造业' => 81889.06,
            '医药制造业' => 27116.57,
            '化学纤维制造业' => 7916.55,
            '橡胶和塑料制品业' => 30526.72,
            '非金属矿物制品业' => 59194.51,
            '黑色金属冶炼和压延加工业' => 64571.78,
            '有色金属冶炼和压延加工业' => 54091.07,
            '金属制品业' => 35952.04,
            '通用设备制造业' => 45611.05,
            '专用设备制造业' => 35835.21,
            '汽车制造业' => 84637.11,
            '铁路、船舶、航空航天和其他运输设备制造业' => 16921.12,
            '电气机械和器材制造业' => 71683.44,
            '计算机、通信和其他电子设备制造业' => 106221.7,
            '仪器仪表制造业' => 9999.5,
            '其他制造业' => 2623.22,
            '废弃资源综合利用业' => 3898.18,
            '金属制品、机械和设备修理业' => 1183.92,
            '电力、热力生产和供应业' => 55006.77,
            '燃气生产和供应业' => 6061.34,
            '水的生产和供应业' => 2141.88
        ];

        if (array_key_exists($sshy, $target)) {
            $num = $vendInc / ($target[$sshy] * 100000000) * 100;

            if ($num > 10) return 100;
            if ($num >= 6 && $num <= 10) return 90;
            if ($num >= 1.1 && $num <= 5) return 80;
            if ($num > 0.1 && $num <= 1) return 70;
            if ($num >= 0.01 && $num <= 0.1) return 60;
            if ($num < 0.01) return 50;
        }

        return 50;
    }

    //财务资产
    private function cwzc($data, $type)
    {
        if (!is_array($data)) return 0;

        if (empty($data)) return 0;

        if (!isset($data[0])) return 0;

        switch ($type) {
            case 'fz':

                //营业收入
                $vendInc = $data[0][2];

                if ($vendInc > 20) $vendIncNum = 110;
                if ($vendInc > 10 && $vendInc <= 20) $vendIncNum = 100;
                if ($vendInc > 5 && $vendInc <= 10) $vendIncNum = 90;
                if ($vendInc >= 0 && $vendInc <= 5) $vendIncNum = 80;
                if ($vendInc >= -10 && $vendInc <= -1) $vendIncNum = 70;
                if ($vendInc >= -20 && $vendInc <= -11) $vendIncNum = 60;
                if ($vendInc <= -21) $vendIncNum = 50;

                //净利润
                $netInc = $data[0][5];

                if ($netInc > 20) $netIncNum = 110;
                if ($netInc > 10 && $netInc <= 20) $netIncNum = 100;
                if ($netInc > 5 && $netInc <= 10) $netIncNum = 90;
                if ($netInc >= 0 && $netInc <= 5) $netIncNum = 80;
                if ($netInc >= -10 && $netInc <= -1) $netIncNum = 70;
                if ($netInc >= -20 && $netInc <= -11) $netIncNum = 60;
                if ($netInc <= -21) $netIncNum = 50;

                //资产总额
                $assGro = $data[0][0];

                if ($assGro > 20) $assGroNum = 110;
                if ($assGro > 10 && $assGro <= 20) $assGroNum = 100;
                if ($assGro > 5 && $assGro <= 10) $assGroNum = 90;
                if ($assGro >= 0 && $assGro <= 5) $assGroNum = 80;
                if ($assGro >= -10 && $assGro <= -1) $assGroNum = 70;
                if ($assGro >= -20 && $assGro <= -11) $assGroNum = 60;
                if ($assGro <= -21) $assGroNum = 50;

                return ($vendIncNum + $netIncNum + $assGroNum) / 3;

                break;

            case 'fx':

                //负债总额/资产总额=资产负债率

                if (count($data) < 2) return 0;

                //今年负债总额
                $liaGro1 = $data[0][1];
                //今年资产总额
                $assGro1 = $data[0][0];

                //今年资产负债率
                if ($assGro1 == 0) {
                    $fuzhailv1 = 0;
                } else {
                    $fuzhailv1 = ($liaGro1 / $assGro1) * 100;
                }

                //去年负债总额
                $liaGro2 = $data[1][1];
                //去年资产总额
                $assGro2 = $data[1][0];

                //今年资产负债率
                if ($assGro2 == 0) {
                    $fuzhailv2 = 0;
                } else {
                    $fuzhailv2 = ($liaGro2 / $assGro2) * 100;
                }

                $num = (abs($fuzhailv1) + abs($fuzhailv2)) / 2;

                if ($num > 80) return 100;
                if ($num > 50 && $num <= 80) return 90;
                if ($num > 30 && $num <= 50) return 80;
                if ($num > 10 && $num <= 30) return 70;
                if ($num > 0 && $num <= 10) return 60;

                break;
        }

        return 0;
    }

    //招投标
    private function ztb($data)
    {
        $num = (int)$data;

        if ($num > 10) return 100;
        if ($num <= 10 && $num >= 6) return 80;
        if ($num <= 5 && $num >= 3) return 70;
        if ($num <= 2) return 60;

        return $num;
    }

    //近三年团队人数
    private function tdrs($data, $type)
    {
        return 50;
    }

    //近两年团队人数
    private function rybh($data, $type)
    {
        return 40;
    }

    //专利
    private function zl($data)
    {
        //总数
        $num = (int)$data;

        if ($num > 10) return 100;
        if ($num <= 10 && $num >= 6) return 80;
        if ($num <= 5 && $num >= 1) return 70;
        if ($num <= 0) return 60;

        return $num;
    }

    //软件著作权
    private function rjzzq($data)
    {
        //总数
        $num = (int)$data;

        if ($num > 20) return 100;
        if ($num <= 20 && $num >= 11) return 80;
        if ($num <= 10 && $num >= 3) return 70;
        if ($num <= 2) return 60;

        return $num;
    }

    //行政许可
    private function xzxk($data)
    {
        //算分
        $num = (int)$data;

        if ($num > 10) return 100;
        if ($num <= 10 && $num >= 6) return 80;
        if ($num <= 5 && $num >= 3) return 70;
        if ($num <= 2) return 60;

        //总数
        return $num;
    }

    //企业对外投资
    private function qydwtz($data)
    {
        $num = (int)$data;

        //算分
        if ($num > 20) return 100;
        if ($num <= 20 && $num >= 11) return 80;
        if ($num <= 10 && $num >= 3) return 70;
        if ($num <= 2) return 60;

        return 0;
    }

    //融资历史
    private function rzls($financing)
    {
        if (!empty($financing)) {
            $temp = [];
            foreach ($financing as $key => $val) {
                $money = $val['Amount'];

                if (strpos($money, '亿')) {
                    $money_num = preg_replace("/[\\x80-\\xff]/", "", $money);
                    if (!empty($money_num)) {
                        if (strpos($money, '美元')) {
                            $money_num = $money_num * 100000000 * 7.0068;
                            array_push($temp, $money_num);
                        } else {
                            $money_num = $money_num * 100000000;
                            array_push($temp, $money_num);
                        }
                    }
                }

                if (strpos($money, '千万')) {
                    $money_num = preg_replace("/[\\x80-\\xff]/", "", $money);

                    if (!empty($money_num)) {
                        if (strpos($money, '美元')) {
                            $money_num = $money_num * 10000000 * 7.0068;
                            array_push($temp, $money_num);
                        } else {
                            $money_num = $money_num * 10000000;
                            array_push($temp, $money_num);
                        }
                    }
                }

                if (strpos($money, '百万')) {
                    $money_num = preg_replace("/[\\x80-\\xff]/", "", $money);

                    if (!empty($money_num)) {
                        if (strpos($money, '美元')) {
                            $money_num = $money_num * 1000000 * 7.0068;
                            array_push($temp, $money_num);
                        } else {
                            $money_num = $money_num * 1000000;
                            array_push($temp, $money_num);
                        }
                    }
                }

                if (strpos($money, '万')) {
                    $money_num = preg_replace("/[\\x80-\\xff]/", "", $money);

                    if (!empty($money_num)) {
                        if (strpos($money, '美元')) {
                            $money_num = $money_num * 10000 * 7.0068;
                            array_push($temp, $money_num);
                        } else {
                            $money_num = $money_num * 10000;
                            array_push($temp, $money_num);
                        }
                    }
                }
            }

            $financing_all_num = array_sum($temp);

            //算数
            $num = 50;
            if ($financing_all_num > 500000000) $num = 100;
            if ($financing_all_num > 100000000 && $financing_all_num <= 500000000) $num = 90;
            if ($financing_all_num > 50000000 && $financing_all_num <= 100000000) $num = 80;
            if ($financing_all_num > 10000000 && $financing_all_num <= 50000000) $num = 70;
            if ($financing_all_num > 1000000 && $financing_all_num <= 10000000) $num = 60;

        } else {
            $num = 50;
        }

        return $num;
    }

    //企业性质
    private function qyxz($data)
    {
        $entType = $data['ENTTYPE'];

        $num = 50;

        if (control::hasString($entType, '全民所有')) $num = 100;
        if (control::hasString($entType, '国有')) $num = 100;
        if (control::hasString($entType, '港澳台')) $num = 100;
        if (control::hasString($entType, '外商')) $num = 100;
        if (control::hasString($entType, '集体')) $num = 100;

        //总数
        return (int)$num;
    }

    //数据填进报告
    private function fillData(TemplateProcessor $docObj, $data)
    {
        //处理发票信息
        //CommonService::getInstance()->log4PHP($data);

        //
        $docObj->setValue('common_data_zhouqi', $data['commonData']['zhouqi']);

        //主营商品分析
        $rows = count($data['re_fpxx']['zyspfx']);
        $docObj->cloneRow('fpxx_zyspfx_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpxx_zyspfx_no#' . ($i + 1), $i + 1);
            //商品类型
            $docObj->setValue('fpxx_zyspfx_type#' . ($i + 1), $data['re_fpxx']['zyspfx'][$i]['name']);
            //销售金额
            $docObj->setValue('fpxx_zyspfx_money#' . ($i + 1), $data['re_fpxx']['zyspfx'][$i]['jine']);
            //占比
            $docObj->setValue('fpxx_zyspfx_zhanbi#' . ($i + 1), $data['re_fpxx']['zyspfx'][$i]['zhanbi']);
        }

        $pieData = $labels = [];
        foreach ($data['re_fpxx']['zyspfx'] as $one)
        {
            $pieData[] = $one['jine'] - 0;
            $labels[] = "{$one['name']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels))
        {
            $docObj->setValue('fpxx_zyspfx_img','');
        }else
        {
            $imgPath = (new NewGraphService())->setTitle('主营商品分析')->setLabels($labels)->pie($pieData);

            $docObj->setImageValue('fpxx_zyspfx_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //主营成本分析
        $rows = count($data['re_fpjx']['zycbfx'][0]);
        $docObj->cloneRow('fpjx_zycbfx_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_zycbfx_no#' . ($i + 1), $i + 1);
            //成本类型
            $docObj->setValue('fpjx_zycbfx_type#' . ($i + 1), $data['re_fpjx']['zycbfx'][0][$i]['name']);
            //金额
            $docObj->setValue('fpjx_zycbfx_money#' . ($i + 1), $data['re_fpjx']['zycbfx'][0][$i]['jine']);
            //占比
            $docObj->setValue('fpjx_zycbfx_zhanbi#' . ($i + 1), $data['re_fpjx']['zycbfx'][0][$i]['zhanbi']);
        }

        //如主营商品达到6种以上则触发该逻辑，则判断前2种占全部的占比，如占比超过90%，图表下方增加一句
        //“企业两种产品或服务占了总销售额的**%，主营产品或服务对企业的营业收⼊贡献度较⾼，需重点关注该产品或服务的市场竞品、定价策略、市场销售策略等潜在可能影响该产品或服务销售情况的因素”

        if ($i > 5 && ($data['re_fpjx']['zycbfx'][0][0]['zhanbi'] + $data['re_fpjx']['zycbfx'][0][1]['zhanbi']) > 90)
        {
            $docObj->setValue('fpjx_zycbfx_sysSaid',"企业两种产品或服务占总销售额大于90%，主营产品或服务对企业的营业收⼊贡献度较⾼，需重点关注该产品或服务的市场竞品、定价策略、市场销售策略等潜在可能影响该产品或服务销售情况的因素");
        }else
        {
            $docObj->setValue('fpjx_zycbfx_sysSaid','');
        }

        $pieData = $labels = [];
        foreach ($data['re_fpjx']['zycbfx'][0] as $one)
        {
            $pieData[] = $one['jine'] - 0;
            $labels[] = "{$one['name']}(%.1f%%)";
        }

        if (empty($pieData) || empty($labels))
        {
            $docObj->setValue('fpjx_zycbfx_img','');
        }else
        {
            $imgPath = (new NewGraphService())->setTitle('主要成本分析')->setLabels($labels)->pie($pieData);

            $docObj->setImageValue('fpjx_zycbfx_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //水费
        $rows = count($data['re_fpjx']['zycbfx_new']['shuifei']);
        $docObj->cloneRow('fpjx_shuifei_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_shuifei_no#' . ($i + 1), $i + 1);
            //开票日期
            $docObj->setValue('fpjx_shuifei_date#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['shuifei'][$i]['riqi']);
            //金额
            $docObj->setValue('fpjx_shuifei_money#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['shuifei'][$i]['jine']);
            //服务商
            $docObj->setValue('fpjx_shuifei_ent#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['shuifei'][$i]['gs']);
        }

        //电费
        $rows = count($data['re_fpjx']['zycbfx_new']['dianfei']);
        $docObj->cloneRow('fpjx_dianfei_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_dianfei_no#' . ($i + 1), $i + 1);
            //开票日期
            $docObj->setValue('fpjx_dianfei_date#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['dianfei'][$i]['riqi']);
            //金额
            $docObj->setValue('fpjx_dianfei_money#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['dianfei'][$i]['jine']);
            //服务商
            $docObj->setValue('fpjx_dianfei_ent#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['dianfei'][$i]['gs']);
        }

        //燃气
        $rows = count($data['re_fpjx']['zycbfx_new']['ranqifei']);
        $docObj->cloneRow('fpjx_ranqi_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_ranqi_no#' . ($i + 1), $i + 1);
            //开票日期
            $docObj->setValue('fpjx_ranqi_date#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['ranqifei'][$i]['riqi']);
            //金额
            $docObj->setValue('fpjx_ranqi_money#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['ranqifei'][$i]['jine']);
            //服务商
            $docObj->setValue('fpjx_ranqi_ent#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['ranqifei'][$i]['gs']);
        }

        //热力
        $rows = count($data['re_fpjx']['zycbfx_new']['reli']);
        $docObj->cloneRow('fpjx_reli_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_reli_no#' . ($i + 1), $i + 1);
            //开票日期
            $docObj->setValue('fpjx_reli_date#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['reli'][$i]['riqi']);
            //金额
            $docObj->setValue('fpjx_reli_money#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['reli'][$i]['jine']);
            //服务商
            $docObj->setValue('fpjx_reli_ent#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['reli'][$i]['gs']);
        }

        //运输与仓储
        $rows = count($data['re_fpjx']['zycbfx_new']['yunshu']);
        $docObj->cloneRow('fpjx_ysycc_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_ysycc_no#' . ($i + 1), $i + 1);
            //开票日期
            $docObj->setValue('fpjx_ysycc_date#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['yunshu'][$i]['riqi']);
            //金额
            $docObj->setValue('fpjx_ysycc_money#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['yunshu'][$i]['jine']);
            //服务商
            $docObj->setValue('fpjx_ysycc_ent#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['yunshu'][$i]['gs']);
        }

        //物业
        $rows = count($data['re_fpjx']['zycbfx_new']['wuye']);
        $docObj->cloneRow('fpjx_wuye_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_wuye_no#' . ($i + 1), $i + 1);
            //开票日期
            $docObj->setValue('fpjx_wuye_date#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['wuye'][$i]['riqi']);
            //金额
            $docObj->setValue('fpjx_wuye_money#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['wuye'][$i]['jine']);
            //服务商
            $docObj->setValue('fpjx_wuye_ent#' . ($i + 1), $data['re_fpjx']['zycbfx_new']['wuye'][$i]['gs']);
        }

        //企业开票情况汇总
        $rows = count($data['re_fpxx']['qykpqkhz']['zhouqi']);
        $rows = 1;
        $docObj->cloneRow('fpxx_qykpqkhz_zq', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //统计周期
            $docObj->setValue('fpxx_qykpqkhz_zq#' . ($i + 1), $data['re_fpxx']['qykpqkhz']['zhouqi']['min'].' - '.$data['re_fpxx']['qykpqkhz']['zhouqi']['max']);
            //销项有效数
            $docObj->setValue('fpxx_qykpqkhz_xxs#' . ($i + 1), $data['re_fpxx']['qykpqkhz']['zhouqi']['xxNum']);
            //销项有效金额
            $docObj->setValue('fpxx_qykpqkhz_xxm#' . ($i + 1), $data['re_fpxx']['qykpqkhz']['zhouqi']['xxJine']);
            //进项有效数
            $docObj->setValue('fpxx_qykpqkhz_jxs#' . ($i + 1), $data['re_fpxx']['qykpqkhz']['zhouqi']['jxNum']);
            //进项有效金额
            $docObj->setValue('fpxx_qykpqkhz_jxm#' . ($i + 1), $data['re_fpxx']['qykpqkhz']['zhouqi']['jxJine']);
        }

        //企业开票情况汇总 其他
        $rows = count($data['re_fpxx']['qykpqkhz']['qita']);
        $docObj->cloneRow('fpxx_qykpqkhz_qt_nf', $rows);
        if ($rows > 0) krsort($data['re_fpxx']['qykpqkhz']['qita']);
        for ($i = 0; $i < $rows; $i++) {
            $j = $i;
            foreach ($data['re_fpxx']['qykpqkhz']['qita'] as $key => $val) {
                if ($j !== 0) {
                    $j--;
                    continue;
                }
                //统计年份
                $docObj->setValue('fpxx_qykpqkhz_qt_nf#' . ($i + 1), $key);
                //销项有效数
                $docObj->setValue('fpxx_qykpqkhz_qt_xxs#' . ($i + 1), $data['re_fpxx']['qykpqkhz']['qita'][$key]['xxNum']);
                //销项有效金额
                $docObj->setValue('fpxx_qykpqkhz_qt_xxm#' . ($i + 1), $data['re_fpxx']['qykpqkhz']['qita'][$key]['xxJine']);
                break;
            }
        }

        //年度销项发票情况汇总
        $rows = count($data['re_fpxx']['ndxxfpqkhz']);
        $docObj->cloneRow('fpxx_ndxxfpqkhz_nf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            $j = $i;
            foreach ($data['re_fpxx']['ndxxfpqkhz'] as $key => $val) {
                if ($j !== 0) {
                    $j--;
                    continue;
                }
                //统计年份
                $docObj->setValue('fpxx_ndxxfpqkhz_nf#' . ($i + 1), $key);
                //有效数
                $docObj->setValue('fpxx_ndxxfpqkhz_nn#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['normal']['normalNum']);
                //有效金额
                $docObj->setValue('fpxx_ndxxfpqkhz_nm#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['normal']['normalAmount']);
                //有效税额
                $docObj->setValue('fpxx_ndxxfpqkhz_nt#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['normal']['normalTax']);
                //红冲数
                $docObj->setValue('fpxx_ndxxfpqkhz_rn#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['red']['redNum']);
                //红冲金额
                $docObj->setValue('fpxx_ndxxfpqkhz_rm#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['red']['redAmount']);
                //红冲税额
                $docObj->setValue('fpxx_ndxxfpqkhz_rt#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['red']['redTax']);
                //作废数量
                $docObj->setValue('fpxx_ndxxfpqkhz_cn#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['cancel']['cancelNum']);
                //作废金额
                $docObj->setValue('fpxx_ndxxfpqkhz_cm#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['cancel']['cancelAmount']);
                //作废税额
                $docObj->setValue('fpxx_ndxxfpqkhz_ct#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['cancel']['cancelTax']);
                //有效发票数量占比
                $docObj->setValue('fpxx_ndxxfpqkhz_nnzb#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['normal']['numZhanbi']);
                //有效发票金额占比
                $docObj->setValue('fpxx_ndxxfpqkhz_nmzb#' . ($i + 1), $data['re_fpxx']['ndxxfpqkhz'][$key]['normal']['AmountZhanbi']);
                break;
            }
        }

        //月度销项正常发票分析
        $rows = count($data['re_fpxx']['ydxxfpfx']);
        $docObj->cloneRow('fpxx_ydxxfpfx_nf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            $j = $i;
            foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val) {
                if ($j !== 0) {
                    $j--;
                    continue;
                }
                $docObj->setValue('fpxx_ydxxfpfx_nf#' . ($i + 1), $key);
                $docObj->setValue('fpxx_ydxxfpfx_n1#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['1']);
                $docObj->setValue('fpxx_ydxxfpfx_n2#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['2']);
                $docObj->setValue('fpxx_ydxxfpfx_n3#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['3']);
                $docObj->setValue('fpxx_ydxxfpfx_n4#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['4']);
                $docObj->setValue('fpxx_ydxxfpfx_n5#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['5']);
                $docObj->setValue('fpxx_ydxxfpfx_n6#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['6']);
                $docObj->setValue('fpxx_ydxxfpfx_n7#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['7']);
                $docObj->setValue('fpxx_ydxxfpfx_n8#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['8']);
                $docObj->setValue('fpxx_ydxxfpfx_n9#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['9']);
                $docObj->setValue('fpxx_ydxxfpfx_n10#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['10']);
                $docObj->setValue('fpxx_ydxxfpfx_n11#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['11']);
                $docObj->setValue('fpxx_ydxxfpfx_n12#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['normal']['12']);
                break;
            }
        }

        $barData = $labels = $legends = [];
        foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val)
        {
            $barData[] = array_values($val['normal']);
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels) || empty($legends))
        {
            $docObj->setValue('fpxx_ydxxfpfx_n_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('月度销项正常发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,50,0,0])
                ->bar($barData);

            $docObj->setImageValue('fpxx_ydxxfpfx_n_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //月度销项红充发票分析
        $rows = count($data['re_fpxx']['ydxxfpfx']);
        $docObj->cloneRow('fpxx_ydxxfpfx_rf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            $j = $i;
            foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val) {
                if ($j !== 0) {
                    $j--;
                    continue;
                }
                $docObj->setValue('fpxx_ydxxfpfx_rf#' . ($i + 1), $key);
                $docObj->setValue('fpxx_ydxxfpfx_r1#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['1']);
                $docObj->setValue('fpxx_ydxxfpfx_r2#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['2']);
                $docObj->setValue('fpxx_ydxxfpfx_r3#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['3']);
                $docObj->setValue('fpxx_ydxxfpfx_r4#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['4']);
                $docObj->setValue('fpxx_ydxxfpfx_r5#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['5']);
                $docObj->setValue('fpxx_ydxxfpfx_r6#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['6']);
                $docObj->setValue('fpxx_ydxxfpfx_r7#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['7']);
                $docObj->setValue('fpxx_ydxxfpfx_r8#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['8']);
                $docObj->setValue('fpxx_ydxxfpfx_r9#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['9']);
                $docObj->setValue('fpxx_ydxxfpfx_r10#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['10']);
                $docObj->setValue('fpxx_ydxxfpfx_r11#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['11']);
                $docObj->setValue('fpxx_ydxxfpfx_r12#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['red']['12']);
                break;
            }
        }

        $barData = $labels = $legends = [];
        foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val)
        {
            $barData[] = array_values($val['red']);
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels) || empty($legends))
        {
            $docObj->setValue('fpxx_ydxxfpfx_r_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('月度销项红充发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,50,0,0])
                ->bar($barData);

            $docObj->setImageValue('fpxx_ydxxfpfx_r_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //月度销项作废发票分析
        $rows = count($data['re_fpxx']['ydxxfpfx']);
        $docObj->cloneRow('fpxx_ydxxfpfx_cf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            $j = $i;
            foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val) {
                if ($j !== 0) {
                    $j--;
                    continue;
                }
                $docObj->setValue('fpxx_ydxxfpfx_cf#' . ($i + 1), $key);
                $docObj->setValue('fpxx_ydxxfpfx_c1#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['1']);
                $docObj->setValue('fpxx_ydxxfpfx_c2#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['2']);
                $docObj->setValue('fpxx_ydxxfpfx_c3#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['3']);
                $docObj->setValue('fpxx_ydxxfpfx_c4#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['4']);
                $docObj->setValue('fpxx_ydxxfpfx_c5#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['5']);
                $docObj->setValue('fpxx_ydxxfpfx_c6#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['6']);
                $docObj->setValue('fpxx_ydxxfpfx_c7#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['7']);
                $docObj->setValue('fpxx_ydxxfpfx_c8#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['8']);
                $docObj->setValue('fpxx_ydxxfpfx_c9#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['9']);
                $docObj->setValue('fpxx_ydxxfpfx_c10#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['10']);
                $docObj->setValue('fpxx_ydxxfpfx_c11#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['11']);
                $docObj->setValue('fpxx_ydxxfpfx_c12#' . ($i + 1), $data['re_fpxx']['ydxxfpfx'][$key]['cancel']['12']);
                break;
            }
        }

        $barData = $labels = $legends = [];
        foreach ($data['re_fpxx']['ydxxfpfx'] as $key => $val)
        {
            $barData[] = array_values($val['cancel']);
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels) || empty($legends))
        {
            $docObj->setValue('fpxx_ydxxfpfx_c_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('月度销项作废发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,50,0,0])
                ->bar($barData);

            $docObj->setImageValue('fpxx_ydxxfpfx_c_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //单张开票金额TOP10记录 销项
        $rows = count($data['re_fpxx']['dzkpjeTOP10jl_xx']);
        $docObj->cloneRow('fpxx_dzkpjeTOP10jl_xx_nf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //开票年度
            $docObj->setValue('fpxx_dzkpjeTOP10jl_xx_nf#' . ($i + 1), substr($data['re_fpxx']['dzkpjeTOP10jl_xx'][$i]['date'],0,4));
            //交易对手名称
            $docObj->setValue('fpxx_dzkpjeTOP10jl_xx_mc#' . ($i + 1), $data['re_fpxx']['dzkpjeTOP10jl_xx'][$i]['purchaserName']);
            //交易对手税号
            $docObj->setValue('fpxx_dzkpjeTOP10jl_xx_taxNo#' . ($i + 1), $data['re_fpxx']['dzkpjeTOP10jl_xx'][$i]['purchaserTaxNo']);
            //开票金额
            $docObj->setValue('fpxx_dzkpjeTOP10jl_xx_money#' . ($i + 1), $data['re_fpxx']['dzkpjeTOP10jl_xx'][$i]['totalAmount']);
            //开票税额
            $docObj->setValue('fpxx_dzkpjeTOP10jl_xx_tax#' . ($i + 1), $data['re_fpxx']['dzkpjeTOP10jl_xx'][$i]['totalTax']);
            //总金额占比
            $docObj->setValue('fpxx_dzkpjeTOP10jl_xx_zb#' . ($i + 1), $data['re_fpxx']['dzkpjeTOP10jl_xx'][$i]['zhanbi']);
        }

        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpxx']['dzkpjeTOP10jl_xx'] as $one)
        {
            $other -= $one['zhanbi'] - 0;
            $pieData[] = $one['zhanbi'] - 0;
            $labels[] = "{$one['purchaserName']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels))
        {
            $docObj->setValue('fpxx_dzkpjeTOP10jl_xx_img','');
        }else
        {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }

            $imgPath = (new NewGraphService())->setTitle('单张开票金额TOP10记录')->setLabels($labels)->pie($pieData);

            $docObj->setImageValue('fpxx_dzkpjeTOP10jl_xx_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //累计开票金额TOP10企业汇总 销项
        $rows = count($data['re_fpxx']['ljkpjeTOP10qyhz_xx']);
        $docObj->cloneRow('fpxx_ljkpjeTOP10qyhz_xx_nf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            $temp = array_values($data['re_fpxx']['ljkpjeTOP10qyhz_xx']);
            //开票年度
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_nf#' . ($i + 1), $temp[$i]['date']);
            //交易对手名称
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_mc#' . ($i + 1), $temp[$i]['name']);
            //交易对手税号
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_taxNo#' . ($i + 1), $temp[$i]['purchaserTaxNo']);
            //开票金额
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_money#' . ($i + 1), $temp[$i]['total']);
            //开票数
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_num#' . ($i + 1), $temp[$i]['num']);
            //总金额占比
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_zb1#' . ($i + 1), $temp[$i]['totalZhanbi']);
            //总金额占比
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_zb2#' . ($i + 1), $temp[$i]['numZhanbi']);
        }

        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpxx']['ljkpjeTOP10qyhz_xx'] as $one)
        {
            $other -= $one['totalZhanbi'] - 0;
            $pieData[] = $one['totalZhanbi'] - 0;
            $labels[] = "{$one['name']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels))
        {
            $docObj->setValue('fpxx_ljkpjeTOP10qyhz_xx_img','');
        }else
        {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }

            $imgPath = (new NewGraphService())->setTitle('累计开票金额TOP10企业汇总')->setLabels($labels)->pie($pieData);

            $docObj->setImageValue('fpxx_ljkpjeTOP10qyhz_xx_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //下游企业司龄分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpxx']['xyqyslfb'])];
        $labels = ['1年以下','2-3年','4-5年','6-9年','10年以上'];

        if (empty($barData) || empty($labels))
        {
            $docObj->setValue('fpxx_xyqyslfb_img','');
        }else
        {
            if (!empty($data['re_fpxx']['xyqyslfb']))
            {
                $imgPath = (new NewGraphService())
                    ->setTitle('下游企业司龄分布（个）')
                    ->setXLabels($labels)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $docObj->setImageValue('fpxx_xyqyslfb_img', [
                    'path' => $imgPath,
                    'width' => 410,
                    'height' => 300
                ]);
            }else
            {
                $docObj->setValue('fpxx_xyqyslfb_img','');
            }
        }

        //下游企业合作年限分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpxx']['xyqyhznxfb'])];
        $labels = ['1年','2年','3年以上'];

        if (empty($barData) || empty($labels))
        {
            $docObj->setValue('fpxx_xyqyhznxfb_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('下游企业合作年限分布（个）')
                ->setXLabels($labels)
                ->setMargin([60,50,0,40])
                ->bar($barData);

            $docObj->setImageValue('fpxx_xyqyhznxfb_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //下游企业更换情况（个）
        $barData = $labels = $legends = [];

        foreach ($data['re_fpxx']['xyqyghqk'] as $key => $val)
        {
            $labels = ['新增','退出'];
            $barData[] = $val;
            $legends[] = $key;
        }

        if (empty($barData) || empty($legends))
        {
            $docObj->setValue('fpxx_xyqyghqk_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('下游企业更换情况（个）')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,50,0,40])
                ->bar($barData);

            $docObj->setImageValue('fpxx_xyqyghqk_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //下游企业稳定性评估  稳定性指数
        $xywdx = $this->xywdx($data['re_fpjx']['xdsForShangxiayou']);
        $xywdx = 0.35 * $xywdx[0] + 0.65 * $xywdx[1] + 0.2 > 1 ? 1 : 0.35 * $xywdx[0] + 0.65 * $xywdx[1] + 0.2;
        $docObj->setValue('xywdx',sprintf('%.1f',$xywdx));

        //下游企业地域分布（个）
        $barData = $labels = $legends = [];

        foreach ($data['re_fpxx']['xyqydyfb'] as $key => $val)
        {
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($barData) || empty($legends) || empty($labels))
        {
            $docObj->setValue('fpxx_xyqydyfb_img','');
        }else
        {
            if (!empty($data['re_fpxx']['xyqydyfb']))
            {
                $imgPath = (new NewGraphService())
                    ->setTitle('下游企业地域分布（个）')
                    ->setXLabels($labels)
                    ->setXLabelAngle(15)
                    ->setLegends($legends)
                    ->setMargin([60,50,0,40])
                    ->bar($barData);

                $docObj->setImageValue('fpxx_xyqydyfb_img', [
                    'path' => $imgPath,
                    'width' => 410,
                    'height' => 300
                ]);
            }else
            {
                $docObj->setValue('fpxx_xyqydyfb_img','');
            }
        }

        //销售前十企业总占比（%）
        $temp = [];

        foreach ($data['re_fpxx']['xsqsqyzzb'] as $key => $val)
        {
            $barData = $labels = $legends = [];
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;

            $temp[] = (new NewGraphService())
                ->setTitle('销售前十企业总占比（%）')
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
                    $docObj->setImageValue("fpxx_xsqsqyzzb_img{$i}", [
                        'path' => $temp[$i-1],
                        'width' => 410,
                        'height' => 300
                    ]);
                }else
                {
                    $docObj->setValue("fpxx_xsqsqyzzb_img{$i}",'');
                }
            }
        }else
        {
            $docObj->setValue('fpxx_xsqsqyzzb_img1','');
            $docObj->setValue('fpxx_xsqsqyzzb_img2','');
            $docObj->setValue('fpxx_xsqsqyzzb_img3','');
        }

        //下游集中度情况评估  集中度指数
        $xyjzd = $this->xyjzd($data['re_fpjx']['xdsForShangxiayou']);
        $xyjzd = 0.35 * $xyjzd[0] + 0.65 * $xyjzd[1] + 0.2 > 1 ? 1 : 0.35 * $xyjzd[0] + 0.65 * $xyjzd[1] + 0.2;
        $docObj->setValue('xyjzd',sprintf('%.1f',$xyjzd));

        //7.3企业销售情况分布（万元）
        $lineData = $legends = [];
        foreach ($data['re_fpxx']['qyxsqkyc'] as $key => $val)
        {
            $lineData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($lineData) || empty($legends))
        {
            $docObj->setValue('fpxx_qyxsqkyc_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('企业销售情况分布')
                ->setLegends($legends)
                ->setXLabels(['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'])
                ->line($lineData);

            $docObj->setImageValue('fpxx_qyxsqkyc_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //年度进项发票情况汇总
        $rows = count($data['re_fpjx']['ndjxfpqkhz']);
        $rows = 1;
        $docObj->cloneRow('fpjx_ndjxfpqkhz_zq', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //统计年份
            $docObj->setValue('fpjx_ndjxfpqkhz_zq#' . ($i + 1), $data['re_fpjx']['ndjxfpqkhz']['min'].' - '.$data['re_fpjx']['ndjxfpqkhz']['max']);
            //销项有效数
            $docObj->setValue('fpjx_ndjxfpqkhz_num#' . ($i + 1), $data['re_fpjx']['ndjxfpqkhz']['normalNum']);
            //销项有效金额
            $docObj->setValue('fpjx_ndjxfpqkhz_money#' . ($i + 1), $data['re_fpjx']['ndjxfpqkhz']['normal']);
        }

        //月度进项发票分析
        $rows = count($data['re_fpjx']['ydjxfpfx']);
        $docObj->cloneRow('fpjx_ydjxfpfx_zq', $rows);
        for ($i = 0; $i < $rows; $i++) {
            $j = $i;
            foreach ($data['re_fpjx']['ydjxfpfx'] as $key => $val) {
                if ($j !== 0) {
                    $j--;
                    continue;
                }
                $docObj->setValue('fpjx_ydjxfpfx_zq#' . ($i + 1), $key);
                $docObj->setValue('fpjx_ydjxfpfx_n1#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['1']);
                $docObj->setValue('fpjx_ydjxfpfx_n2#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['2']);
                $docObj->setValue('fpjx_ydjxfpfx_n3#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['3']);
                $docObj->setValue('fpjx_ydjxfpfx_n4#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['4']);
                $docObj->setValue('fpjx_ydjxfpfx_n5#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['5']);
                $docObj->setValue('fpjx_ydjxfpfx_n6#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['6']);
                $docObj->setValue('fpjx_ydjxfpfx_n7#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['7']);
                $docObj->setValue('fpjx_ydjxfpfx_n8#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['8']);
                $docObj->setValue('fpjx_ydjxfpfx_n9#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['9']);
                $docObj->setValue('fpjx_ydjxfpfx_n10#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['10']);
                $docObj->setValue('fpjx_ydjxfpfx_n11#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['11']);
                $docObj->setValue('fpjx_ydjxfpfx_n12#' . ($i + 1), $data['re_fpjx']['ydjxfpfx'][$key]['12']);
                break;
            }
        }

        $barData = $labels = $legends = [];
        foreach ($data['re_fpjx']['ydjxfpfx'] as $key => $val)
        {
            $labels = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
            $barData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($barData) || empty($legends))
        {
            $docObj->setValue('fpjx_ydjxfpfx_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('月度进项发票分析')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setMargin([60,0,0,0])
                ->bar($barData);

            $docObj->setImageValue('fpjx_ydjxfpfx_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //单张开票金额TOP10企业汇总 进项
        $rows = count($data['re_fpjx']['dzkpjeTOP10jl_jx']);
        $docObj->cloneRow('fpjx_dzkpjeTOP10jl_jx_nf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //开票年度
            $docObj->setValue('fpjx_dzkpjeTOP10jl_jx_nf#' . ($i + 1), $data['re_fpjx']['dzkpjeTOP10jl_jx'][$i]['date']);
            //交易对手名称
            $docObj->setValue('fpjx_dzkpjeTOP10jl_jx_mc#' . ($i + 1), $data['re_fpjx']['dzkpjeTOP10jl_jx'][$i]['salesTaxName']);
            //交易对手税号
            $docObj->setValue('fpjx_dzkpjeTOP10jl_jx_taxNo#' . ($i + 1), $data['re_fpjx']['dzkpjeTOP10jl_jx'][$i]['salesTaxNo']);
            //开票金额
            $docObj->setValue('fpjx_dzkpjeTOP10jl_jx_money#' . ($i + 1), $data['re_fpjx']['dzkpjeTOP10jl_jx'][$i]['totalAmount']);
            //开票税额
            $docObj->setValue('fpjx_dzkpjeTOP10jl_jx_tax#' . ($i + 1), $data['re_fpjx']['dzkpjeTOP10jl_jx'][$i]['totalTax']);
            //总金额占比
            $docObj->setValue('fpjx_dzkpjeTOP10jl_jx_zb1#' . ($i + 1), $data['re_fpjx']['dzkpjeTOP10jl_jx'][$i]['zhanbi']);
        }

        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpjx']['dzkpjeTOP10jl_jx'] as $one)
        {
            $other -= $one['zhanbi'] - 0;
            $pieData[] = $one['zhanbi'] - 0;
            $labels[] = "{$one['salesTaxName']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels))
        {
            $docObj->setValue('fpjx_dzkpjeTOP10jl_jx_img','');
        }else
        {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }

            $imgPath = (new NewGraphService())->setTitle('单张开票金额TOP10企业汇总')->setLabels($labels)->pie($pieData);

            $docObj->setImageValue('fpjx_dzkpjeTOP10jl_jx_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //累计开票金额TOP10企业汇总 进项
        $rows = count($data['re_fpjx']['ljkpjeTOP10qyhz_jx']);
        $docObj->cloneRow('fpjx_ljkpjeTOP10qyhz_jx_nf', $rows);
        for ($i = 0; $i < $rows; $i++) {
            $temp = array_values($data['re_fpjx']['ljkpjeTOP10qyhz_jx']);
            //开票年度
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_nf#' . ($i + 1), $temp[$i]['date']);
            //交易对手名称
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_mc#' . ($i + 1), $temp[$i]['name']);
            //交易对手税号
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_taxNo#' . ($i + 1), $temp[$i]['salesTaxNo']);
            //开票金额
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_money#' . ($i + 1), $temp[$i]['total']);
            //开票数
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_num#' . ($i + 1), $temp[$i]['num']);
            //总金额占比
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_zb1#' . ($i + 1), $temp[$i]['totalZhanbi']);
            //总金额占比
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_zb2#' . ($i + 1), $temp[$i]['numZhanbi']);
        }

        $pieData = $labels = [];
        $other = 100;
        foreach ($data['re_fpjx']['ljkpjeTOP10qyhz_jx'] as $one)
        {
            $other -= $one['totalZhanbi'] - 0;
            $pieData[] = $one['totalZhanbi'] - 0;
            $labels[] = "{$one['name']} (%.1f%%)";
        }

        if (empty($pieData) || empty($labels))
        {
            $docObj->setValue('fpjx_ljkpjeTOP10qyhz_jx_img','');
        }else
        {
            if ($other > 0) {
                array_push($pieData,$other);
                array_push($labels,"其他 (%.1f%%)");
            }

            $imgPath = (new NewGraphService())->setTitle('累计开票金额TOP10企业汇总')->setLabels($labels)->pie($pieData);

            $docObj->setImageValue('fpjx_ljkpjeTOP10qyhz_jx_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //上游供应商司龄分布（个）
        $barData = $labels = [];
        $barData = [array_values($data['re_fpjx']['sygysslfb'])];
        $labels = ['1年以下','2-3年','4-5年','6-9年','10年以上'];

        if (empty($barData) || empty($labels))
        {
            $docObj->setValue('fpjx_sygysslfb_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('上游供应商司龄分布（个）')
                ->setXLabels($labels)
                ->setMargin([60,50,0,40])
                ->bar($barData);

            $docObj->setImageValue('fpjx_sygysslfb_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //上游供应商地域分布（个）
        $barData = $labels = $legends = [];
        foreach ($data['re_fpjx']['syqydyfb'] as $key => $val)
        {
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;
        }

        if (empty($barData) || empty($labels))
        {
            $docObj->setValue('fpjx_syqydyfb_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('上游供应商地域分布（个）')
                ->setXLabels($labels)
                ->setLegends($legends)
                ->setXLabelAngle(15)
                ->setMargin([60,50,0,40])
                ->bar($barData);

            $docObj->setImageValue('fpjx_syqydyfb_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }

        //采购前十供应商总占比（%）
        $temp = [];
        foreach ($data['re_fpjx']['cgqsqyzzb'] as $key => $val)
        {
            $barData = $labels = $legends = [];
            $labels = array_keys($val);
            $barData[] = array_values($val);
            $legends[] = $key;

            $temp[] = (new NewGraphService())
                ->setTitle('采购前十供应商总占比（%）')
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
                    $docObj->setImageValue("fpjx_cgqsqyzzb_img{$i}", [
                        'path' => $temp[$i-1],
                        'width' => 410,
                        'height' => 300
                    ]);
                }else
                {
                    $docObj->setValue("fpjx_cgqsqyzzb_img{$i}",'');
                }
            }
        }else
        {
            $docObj->setValue('fpjx_cgqsqyzzb_img1','');
            $docObj->setValue('fpjx_cgqsqyzzb_img2','');
            $docObj->setValue('fpjx_cgqsqyzzb_img3','');
        }

        //上游集中度情况评估  集中度指数
        $syjzd = $this->syjzd($data['re_fpjx']['xdsForShangxiayou']);
        $syjzd = 0.35 * $syjzd[0] + 0.65 * $syjzd[1] + 0.2 > 1 ? 1 : 0.35 * $syjzd[0] + 0.65 * $syjzd[1] + 0.2;
        $docObj->setValue('syjzd',sprintf('%.1f',$syjzd));

        //7.9企业采购情况分布（万元）
        $lineData = $legends = $xLabels = [];
        $legends = [$data['re_fpjx']['qycgqkyc']['label']];
        $xLabels = $data['re_fpjx']['qycgqkyc']['xAxes'];
        $lineData = [$data['re_fpjx']['qycgqkyc']['data']];

        if (empty($legends) || empty($xLabels) || empty($lineData))
        {
            $docObj->setValue('fpjx_qycgqkyc_img','');
        }else
        {
            $imgPath = (new NewGraphService())
                ->setTitle('企业采购情况分布（万元）')
                ->setLegends($legends)
                ->setXLabels($xLabels)
                ->line($lineData);

            $docObj->setImageValue('fpjx_qycgqkyc_img', [
                'path' => $imgPath,
                'width' => 410,
                'height' => 300
            ]);
        }
    }


}
