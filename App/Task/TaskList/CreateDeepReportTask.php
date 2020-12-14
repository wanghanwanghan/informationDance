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

    //接口取发票数据
    private function getReceiptData()
    {
        $code = $this->code;

        //取20个月的进项
        for ($i=5;$i<=20;$i+=5)
        {
            $startDate = Carbon::now()->subMonths($i)->format('Y-m-d');
            $endDate = Carbon::now()->subMonths($i-5)->format('Y-m-d');

            for ($page=1;$page<=10000;$page++)
            {
                $res = (new ZhongWangService())
                    ->setCheckRespFlag(true)
                    ->getInOrOutDetailByCert($code, 1, $startDate, $endDate, $page, 200);

                if ($res['code'] !== 200 || empty($res['result'])) break;

                //数据入库
                foreach ($res['result'] as $oneInvoice)
                {
                    try
                    {
                        $info = InvoiceIn::create()->where('invoiceCode',$oneInvoice['invoiceCode'])
                            ->where('invoiceNumber',$oneInvoice['invoiceNumber'])->get();

                        if (!empty($info)) continue;

                        InvoiceIn::create()->data($oneInvoice)->save();

                    }catch (\Throwable $e)
                    {
                        CommonService::getInstance()->log4PHP($e->getMessage());
                    }
                }
            }
        }

        //取20个月的销项
        for ($i=5;$i<=20;$i+=5)
        {
            $startDate = Carbon::now()->subMonths($i)->format('Y-m-d');
            $endDate = Carbon::now()->subMonths($i-5)->format('Y-m-d');

            for ($page=1;$page<=10000;$page++)
            {
                $res = (new ZhongWangService())
                    ->setCheckRespFlag(true)
                    ->getInOrOutDetailByCert($code, 2, $startDate, $endDate, $page, 200);

                if ($res['code'] !== 200 || empty($res['result'])) break;

                //数据入库
                foreach ($res['result'] as $oneInvoice)
                {
                    try
                    {
                        $info = InvoiceOut::create()->where('invoiceCode',$oneInvoice['invoiceCode'])
                            ->where('invoiceNumber',$oneInvoice['invoiceNumber'])->get();

                        if (!empty($info)) continue;

                        InvoiceOut::create()->data($oneInvoice)->save();

                    }catch (\Throwable $e)
                    {
                        CommonService::getInstance()->log4PHP($e->getMessage());
                    }
                }
            }
        }

        $in = InvoiceIn::create()->where('purchaserTaxNo',$this->code)->all();
        $this->inDetail = obj2Arr($in);
        $out = InvoiceOut::create()->where('salesTaxNo',$this->code)->all();
        $this->outDetail = obj2Arr($out);
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
        }

        $tmp->setValue('createEnt', $userInfo->company);

        $tmp->setValue('entName', $this->entName);

        $tmp->setValue('reportNum', $this->reportNum);

        $tmp->setValue('time', Carbon::now()->format('Y年m月d日'));

//        $reportVal = $this->cspHandleData();

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

        //6.1企业开票情况汇总
        $qykpqkhz=$invoiceObj->qykpqkhz();
        $reportVal['re_fpxx']['qykpqkhz']=$qykpqkhz;

        //6.2.1年度销项发票情况汇总
        $ndxxfpqkhz=$invoiceObj->ndxxfpqkhz();
        $reportVal['re_fpxx']['ndxxfpqkhz']=$ndxxfpqkhz;

        //6.2.2月度销项发票分析
        $ydxxfpfx=$invoiceObj->ydxxfpfx();
        $reportVal['re_fpxx']['ydxxfpfx']=$ydxxfpfx;

        //6.2.5单张开票金额TOP10记录
        $dzkpjeTOP10jl_xx=$invoiceObj->dzkpjeTOP10jl_xx();
        $reportVal['re_fpxx']['dzkpjeTOP10jl_xx']=$dzkpjeTOP10jl_xx;

        //6.2.6累计开票金额TOP10企业汇总
        $ljkpjeTOP10qyhz_xx=$invoiceObj->ljkpjeTOP10qyhz_xx();
        $reportVal['re_fpxx']['ljkpjeTOP10qyhz_xx']=$ljkpjeTOP10qyhz_xx;

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

        //6.4.4单张金额TOP10企业汇总
        $dzkpjeTOP10jl_jx=$invoiceObj->dzkpjeTOP10jl_jx();
        $reportVal['re_fpjx']['dzkpjeTOP10jl_jx']=$dzkpjeTOP10jl_jx;

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
//
//        $this->exprXDS($reportVal);
//
//        $this->fz_and_fx_detail($tmp, $reportVal);
//
//        $tmp->setValue('fz_score', sprintf('%.2f', array_sum($this->fz)));
//        $tmp->setValue('fz_detail', implode(',',$this->fz_detail));
//
//        $tmp->setValue('fx_score', sprintf('%.2f', array_sum($this->fx)));
//        $tmp->setValue('fx_detail', implode(',',$this->fx_detail));

        $this->addOcrWords();

        $tmp->saveAs(REPORT_PATH . $this->reportNum . '.docx');

        $info = ReportInfo::create()->where('phone',$this->phone)->where('filename',$this->reportNum)->get();

        $info->update(['status'=>2]);

        $userEmail = User::create()->where('phone',$this->phone)->get();

        CommonService::getInstance()->sendEmail($userEmail->email,[REPORT_PATH . $this->reportNum . '.docx'],'02',['entName'=>$this->entName]);

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

    //计算信动分
    private function exprXDS($data)
    {
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
        $this->fz['caiwu'] = $c * 0.6;
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

        //水费
        $rows = count($data['re_fpjx']['zycbfx'][1]['shuifei']);
        $docObj->cloneRow('fpjx_zycbfx_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue('fpjx_shuifei_no#' . ($i + 1), $i + 1);
            //开票日期
            $docObj->setValue('fpjx_shuifei_date#' . ($i + 1), $data['re_fpjx']['zycbfx'][1]['shuifei'][$i]['riqi']);
            //金额
            $docObj->setValue('fpjx_shuifei_money#' . ($i + 1), $data['re_fpjx']['zycbfx'][1]['shuifei'][$i]['jine']);
            //服务商
            $docObj->setValue('fpjx_shuifei_ent#' . ($i + 1), $data['re_fpjx']['zycbfx'][1]['shuifei'][$i]['gs']);
        }



//        //基本信息
//        //企业类型
//        $docObj->setValue('ENTTYPE', $data['getRegisterInfo']['ENTTYPE']);
//        //注册资本
//        $docObj->setValue('REGCAP', $data['getRegisterInfo']['REGCAP']);
//        //注册地址
//        $docObj->setValue('DOM', $data['getRegisterInfo']['DOM']);
//        //法人
//        $docObj->setValue('FRDB', $data['getRegisterInfo']['FRDB']);
//        //统一代码
//        $docObj->setValue('SHXYDM', $data['getRegisterInfo']['SHXYDM']);
//        //成立日期
//        $docObj->setValue('ESDATE', $this->formatDate($data['getRegisterInfo']['ESDATE']));
//        //核准日期
//        $docObj->setValue('APPRDATE', $this->formatDate($data['getRegisterInfo']['APPRDATE']));
//        //经营状态
//        $docObj->setValue('ENTSTATUS', $data['getRegisterInfo']['ENTSTATUS']);
//        //营业期限
//        $docObj->setValue('OPFROM', $this->formatDate($data['getRegisterInfo']['OPFROM']));
//        $docObj->setValue('APPRDATE', $this->formatDate($data['getRegisterInfo']['APPRDATE']));
//        //所属行业
//        $docObj->setValue('INDUSTRY', $data['getRegisterInfo']['INDUSTRY']);
//        //经营范围
//        $docObj->setValue('OPSCOPE', $data['getRegisterInfo']['OPSCOPE']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,14,$this->entName,true);
//        $docObj->setValue('jbxx_oneSaid', $oneSaid);
//
//        //股东信息
//        $rows = count($data['getShareHolderInfo']);
//        $docObj->cloneRow('gd_INV', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //股东名称
//            $docObj->setValue("gd_INV#" . ($i + 1), $data['getShareHolderInfo'][$i]['INV']);
//            //统一代码
//            $docObj->setValue("gd_SHXYDM#" . ($i + 1), $data['getShareHolderInfo'][$i]['SHXYDM']);
//            //股东类型
//            $docObj->setValue("gd_INVTYPE#" . ($i + 1), $data['getShareHolderInfo'][$i]['INVTYPE']);
//            //认缴出资额
//            $docObj->setValue("gd_SUBCONAM#" . ($i + 1), $data['getShareHolderInfo'][$i]['SUBCONAM']);
//            //出资币种
//            $docObj->setValue("gd_CONCUR#" . ($i + 1), $data['getShareHolderInfo'][$i]['CONCUR']);
//            //出资比例
//            $docObj->setValue("gd_CONRATIO#" . ($i + 1), $this->formatPercent($data['getShareHolderInfo'][$i]['CONRATIO']));
//            //出资时间
//            $docObj->setValue("gd_CONDATE#" . ($i + 1), $this->formatDate($data['getShareHolderInfo'][$i]['CONDATE']));
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,15,$this->entName,true);
//        $docObj->setValue('gudongxx_oneSaid', $oneSaid);
//
//        //高管信息
//        $rows = count($data['getMainManagerInfo']);
//        $docObj->cloneRow('gg_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("gg_no#" . ($i + 1), $i + 1);
//            //姓名
//            $docObj->setValue("gg_NAME#" . ($i + 1), $data['getMainManagerInfo'][$i]['NAME']);
//            //职位
//            $docObj->setValue("gg_POSITION#" . ($i + 1), $data['getMainManagerInfo'][$i]['POSITION']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,15,$this->entName,true);
//        $docObj->setValue('ggxx_oneSaid', $oneSaid);
//
//        //变更信息
//        $rows = count($data['getRegisterChangeInfo']['list']);
//        $docObj->cloneRow('bg_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("bg_no#" . ($i + 1), $i + 1);
//            //变更日期
//            $docObj->setValue("bg_ALTDATE#" . ($i + 1), $this->formatDate($data['getRegisterChangeInfo']['list'][$i]['ALTDATE']));
//            //变更项目
//            $docObj->setValue("bg_ALTITEM#" . ($i + 1), $data['getRegisterChangeInfo']['list'][$i]['ALTITEM']);
//            //变更前
//            $docObj->setValue("bg_ALTBE#" . ($i + 1), $data['getRegisterChangeInfo']['list'][$i]['ALTBE']);
//            //变更后
//            $docObj->setValue("bg_ALTAF#" . ($i + 1), $data['getRegisterChangeInfo']['list'][$i]['ALTAF']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,19,$this->entName,true);
//        $docObj->setValue('bgxx_oneSaid', $oneSaid);
//
//        //经营异常
//        $rows = count($data['GetOpException']['list']);
//        $docObj->cloneRow('jjyc_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("jjyc_no#" . ($i + 1), $i + 1);
//            //列入一日
//            $docObj->setValue("jjyc_AddDate#" . ($i + 1), $this->formatDate($data['GetOpException']['list'][$i]['AddDate']));
//            //列入原因
//            $docObj->setValue("jjyc_AddReason#" . ($i + 1), $data['GetOpException']['list'][$i]['AddReason']);
//            //移除日期
//            $docObj->setValue("jjyc_RemoveDate#" . ($i + 1), $this->formatDate($data['GetOpException']['list'][$i]['RemoveDate']));
//            //移除原因
//            $docObj->setValue("jjyc_RomoveReason#" . ($i + 1), $data['GetOpException']['list'][$i]['RomoveReason']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,21,$this->entName,true);
//        $docObj->setValue('jyycxx_oneSaid', $oneSaid);
//
//        //实际控制人
//        if (!empty($data['Beneficiary'])) {
//            //姓名
//            $docObj->setValue("sjkzr_Name", $data['Beneficiary']['Name']);
//            //持股比例
//            $docObj->setValue("sjkzr_TotalStockPercent", $this->formatPercent($data['Beneficiary']['TotalStockPercent']));
//            //股权链
//            $path = '';
//            foreach ($data['Beneficiary']['DetailInfoList'] as $no => $onePath) {
//                $path .= '<w:br/>' . ($no + 1) . $onePath['Path'] . '<w:br/>';
//            }
//            $docObj->setValue("sjkzr_Path", $path);
//        } else {
//            //姓名
//            $docObj->setValue("sjkzr_Name", '');
//            //持股比例
//            $docObj->setValue("sjkzr_TotalStockPercent", '因穿透股东中有政府部门或国资单位等特殊机构，故不予显示');
//            //股权链
//            $docObj->setValue("sjkzr_Path", '');
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,16,$this->entName,true);
//        $docObj->setValue('sjkzr_oneSaid', $oneSaid);
//
//        //历史沿革
//        $rows = count($data['getHistoricalEvolution']);
//        $docObj->cloneRow('lsyg_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("lsyg_no#" . ($i + 1), $i + 1);
//            //内容
//            $docObj->setValue("lsyg_content#" . ($i + 1), $data['getHistoricalEvolution'][$i]);
//        }
//
//        //法人对外投资
//        $rows = count($data['lawPersonInvestmentInfo']);
//        $docObj->cloneRow('frdwtz_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("frdwtz_no#" . ($i + 1), $i + 1);
//            //法人
//            $docObj->setValue("frdwtz_NAME#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['NAME']);
//            //企业名称
//            $docObj->setValue("frdwtz_ENTNAME#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['ENTNAME']);
//            //持股比例
//            $docObj->setValue("frdwtz_CONRATIO#" . ($i + 1), $this->formatPercent($data['lawPersonInvestmentInfo'][$i]['CONRATIO']));
//            //注册资本
//            $docObj->setValue("frdwtz_REGCAP#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['REGCAP']);
//            //统一社会信用代码
//            $docObj->setValue("frdwtz_SHXYDM#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['SHXYDM']);
//            //认缴出资额
//            $docObj->setValue("frdwtz_SUBCONAM#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['SUBCONAM']);
//            //状态
//            $docObj->setValue("frdwtz_ENTSTATUS#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['ENTSTATUS']);
//            //认缴出资时间
//            $docObj->setValue("frdwtz_CONDATE#" . ($i + 1), $this->formatDate($data['lawPersonInvestmentInfo'][$i]['CONDATE']));
//        }
//
//        //法人对外任职
//        $rows = count($data['getLawPersontoOtherInfo']);
//        $docObj->cloneRow('frdwrz_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("frdwrz_no#" . ($i + 1), $i + 1);
//            //姓名
//            $docObj->setValue("frdwrz_NAME#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['NAME']);
//            //任职企业名称
//            $docObj->setValue("frdwrz_ENTNAME#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['ENTNAME']);
//            //统一社会信用代码
//            $docObj->setValue("frdwrz_SHXYDM#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['SHXYDM']);
//            //成立日期
//            $docObj->setValue("frdwrz_ESDATE#" . ($i + 1), $this->formatDate($data['getLawPersontoOtherInfo'][$i]['ESDATE']));
//            //注册资本
//            $docObj->setValue("frdwrz_REGCAP#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['REGCAP']);
//            //经营状态
//            $docObj->setValue("frdwrz_ENTSTATUS#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['ENTSTATUS']);
//            //职务
//            $docObj->setValue("frdwrz_POSITION#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['POSITION']);
//            //是否法人
//            $docObj->setValue("frdwrz_ISFRDB#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['ISFRDB']);
//        }
//
//        //企业对外投资
//        $rows = count($data['getInvestmentAbroadInfo']['list']);
//        $docObj->cloneRow('qydwtz_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("qydwtz_no#" . ($i + 1), $i + 1);
//            //被投资企业名称
//            $docObj->setValue("qydwtz_ENTNAME#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['ENTNAME']);
//            //成立日期
//            $docObj->setValue("qydwtz_ESDATE#" . ($i + 1), $this->formatDate($data['getInvestmentAbroadInfo']['list'][$i]['ESDATE']));
//            //经营状态
//            $docObj->setValue("qydwtz_ENTSTATUS#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['ENTSTATUS']);
//            //注册资本
//            $docObj->setValue("qydwtz_REGCAP#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['REGCAP']);
//            //认缴出资额
//            $docObj->setValue("qydwtz_SUBCONAM#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['SUBCONAM']);
//            //出资币种
//            $docObj->setValue("qydwtz_CONCUR#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['CONCUR']);
//            //出资比例
//            $docObj->setValue("qydwtz_CONRATIO#" . ($i + 1), $this->formatPercent($data['getInvestmentAbroadInfo']['list'][$i]['CONRATIO']));
//            //出资时间
//            $docObj->setValue("qydwtz_CONDATE#" . ($i + 1), $this->formatDate($data['getInvestmentAbroadInfo']['list'][$i]['CONDATE']));
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,23,$this->entName,true);
//        $docObj->setValue('qydwtz_oneSaid', $oneSaid);
//
//        //主要分支机构
//        $rows = count($data['getBranchInfo']);
//        $docObj->cloneRow('fzjg_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("fzjg_no#" . ($i + 1), $i + 1);
//            //机构名称
//            $docObj->setValue("fzjg_ENTNAME#" . ($i + 1), $data['getBranchInfo'][$i]['ENTNAME']);
//            //负责人
//            $docObj->setValue("fzjg_FRDB#" . ($i + 1), $data['getBranchInfo'][$i]['FRDB']);
//            //成立日期
//            $docObj->setValue("fzjg_ESDATE#" . ($i + 1), $this->formatDate($data['getBranchInfo'][$i]['ESDATE']));
//            //经营状态
//            $docObj->setValue("fzjg_ENTSTATUS#" . ($i + 1), $data['getBranchInfo'][$i]['ENTSTATUS']);
//            //登记地省份
//            $docObj->setValue("fzjg_PROVINCE#" . ($i + 1), $data['getBranchInfo'][$i]['PROVINCE']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,18,$this->entName,true);
//        $docObj->setValue('zyfzjg_oneSaid', $oneSaid);
//
//        //银行信息
//        $docObj->cloneRow('yhxx_no', 1);
//        for ($i = 0; $i < 1; $i++) {
//            //序号
//            $docObj->setValue("yhxx_no#" . ($i + 1), $i + 1);
//            //开户行地址
//            $docObj->setValue("yhxx_khh#" . ($i + 1), $data['GetCreditCodeNew']['Bank']);
//            //开户行号码
//            $docObj->setValue("yhxx_hm#" . ($i + 1), $data['GetCreditCodeNew']['BankAccount']);
//        }
//
//        //公司概况
//        $rows = count($data['SearchCompanyFinancings']);
//        $docObj->cloneRow('gsgk_rzjd', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //融资阶段
//            $docObj->setValue("gsgk_rzjd#" . ($i + 1), $data['SearchCompanyFinancings'][$i]['Round']);
//            //融资
//            $docObj->setValue("gsgk_rz#" . ($i + 1), $data['SearchCompanyFinancings'][$i]['Investment'] . '，' . $data['SearchCompanyFinancings'][$i]['Amount']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,22,$this->entName,true);
//        $docObj->setValue('gsgk_oneSaid', $oneSaid);
//
//        //招投标
//        $rows = count($data['TenderSearch']['list']);
//        $docObj->cloneRow('ztb_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("ztb_no#" . ($i + 1), $i + 1);
//            //描述
//            $docObj->setValue("ztb_Title#" . ($i + 1), $data['TenderSearch']['list'][$i]['Title']);
//            //发布日期
//            $docObj->setValue("ztb_Pubdate#" . ($i + 1), $this->formatDate($data['TenderSearch']['list'][$i]['Pubdate']));
//            //所属地区
//            $docObj->setValue("ztb_ProvinceName#" . ($i + 1), $data['TenderSearch']['list'][$i]['ProvinceName']);
//            //项目分类
//            $docObj->setValue("ztb_ChannelName#" . ($i + 1), $data['TenderSearch']['list'][$i]['ChannelName']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,24,$this->entName,true);
//        $docObj->setValue('ztb_oneSaid', $oneSaid);
//
//        //购地信息
//        $rows = count($data['LandPurchaseList']);
//        $docObj->cloneRow('gdxx_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("gdxx_no#" . ($i + 1), $i + 1);
//            //项目位置
//            $docObj->setValue("gdxx_Address#" . ($i + 1), $data['LandPurchaseList'][$i]['Address']);
//            //土地用途
//            $docObj->setValue("gdxx_LandUse#" . ($i + 1), $data['LandPurchaseList'][$i]['LandUse']);
//            //面积
//            $docObj->setValue("gdxx_Area#" . ($i + 1), $data['LandPurchaseList'][$i]['Area']);
//            //行政区
//            $docObj->setValue("gdxx_AdminArea#" . ($i + 1), $data['LandPurchaseList'][$i]['AdminArea']);
//            //供地方式
//            $docObj->setValue("gdxx_SupplyWay#" . ($i + 1), $data['LandPurchaseList'][$i]['SupplyWay']);
//            //签订日期
//            $docObj->setValue("gdxx_SignTime#" . ($i + 1), $this->formatDate($data['LandPurchaseList'][$i]['SignTime']));
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,25,$this->entName,true);
//        $docObj->setValue('gdxx_oneSaid', $oneSaid);
//
//        //土地公示
//        $rows = count($data['LandPublishList']);
//        $docObj->cloneRow('tdgs_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("tdgs_no#" . ($i + 1), $i + 1);
//            //地块位置
//            $docObj->setValue("tdgs_Address#" . ($i + 1), $data['LandPublishList'][$i]['Address']);
//            //发布机关
//            $docObj->setValue("tdgs_PublishGov#" . ($i + 1), $data['LandPublishList'][$i]['PublishGov']);
//            //行政区
//            $docObj->setValue("tdgs_AdminArea#" . ($i + 1), $data['LandPublishList'][$i]['AdminArea']);
//            //发布日期
//            $docObj->setValue("tdgs_PublishDate#" . ($i + 1), $this->formatDate($data['LandPublishList'][$i]['PublishDate']));
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,26,$this->entName,true);
//        $docObj->setValue('tdgs_oneSaid', $oneSaid);
//
//        //土地转让
//        $rows = count($data['LandTransferList']);
//        $docObj->cloneRow('tdzr_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("tdzr_no#" . ($i + 1), $i + 1);
//            //土地坐落
//            $docObj->setValue("tdzr_Address#" . ($i + 1), $data['LandTransferList'][$i]['Address']);
//            //行政区
//            $docObj->setValue("tdzr_AdminArea#" . ($i + 1), $data['LandTransferList'][$i]['AdminArea']);
//            //原土地使用权人
//            $docObj->setValue("tdzr_OldOwner#" . ($i + 1), $data['LandTransferList'][$i]['OldOwner']['Name']);
//            //现土地使用权人
//            $docObj->setValue("tdzr_NewOwner#" . ($i + 1), $data['LandTransferList'][$i]['NewOwner']['Name']);
//            //成交额
//            $docObj->setValue("tdzr_TransAmt#" . ($i + 1), $data['LandTransferList'][$i]['detail']['TransAmt']);
//            //面积
//            $docObj->setValue("tdzr_Acreage#" . ($i + 1), $data['LandTransferList'][$i]['detail']['Acreage']);
//            //成交日期
//            $docObj->setValue("tdzr_TransTime#" . ($i + 1), $this->formatDate($data['LandTransferList'][$i]['detail']['TransTime']));
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,27,$this->entName,true);
//        $docObj->setValue('tdzr_oneSaid', $oneSaid);
//
//        //建筑资质
//        $rows = count($data['Qualification']);
//        $docObj->cloneRow('jzzz_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("jzzz_no#" . ($i + 1), $i + 1);
//            //资质类别
//            $docObj->setValue("jzzz_Category#" . ($i + 1), $data['Qualification'][$i]['Category']);
//            //资质证书号
//            $docObj->setValue("jzzz_CertNo#" . ($i + 1), $data['Qualification'][$i]['CertNo']);
//            //资质名称
//            $docObj->setValue("jzzz_CertName#" . ($i + 1), $data['Qualification'][$i]['CertName']);
//            //发证日期
//            $docObj->setValue("jzzz_SignDate#" . ($i + 1), $this->formatDate($data['Qualification'][$i]['SignDate']));
//            //证书有效期
//            $docObj->setValue("jzzz_ValidPeriod#" . ($i + 1), $this->formatDate($data['Qualification'][$i]['ValidPeriod']));
//            //发证机关
//            $docObj->setValue("jzzz_SignDept#" . ($i + 1), $data['Qualification'][$i]['SignDept']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,29,$this->entName,true);
//        $docObj->setValue('jzzz_oneSaid', $oneSaid);
//
//        //建筑工程项目
//        $rows = count($data['BuildingProject']);
//        $docObj->cloneRow('jzgc_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("jzgc_no#" . ($i + 1), $i + 1);
//            //项目编码
//            $docObj->setValue("jzgc_No#" . ($i + 1), $data['BuildingProject'][$i]['No']);
//            //项目名称
//            $docObj->setValue("jzgc_ProjectName#" . ($i + 1), $data['BuildingProject'][$i]['ProjectName']);
//            //项目属地
//            $docObj->setValue("jzgc_Name#" . ($i + 1), $data['BuildingProject'][$i]['ConsCoyList'][0]['Name']);
//            //项目类别
//            $docObj->setValue("jzgc_Category#" . ($i + 1), $data['BuildingProject'][$i]['Category']);
//            //建设单位
//            $docObj->setValue("jzgc_Region#" . ($i + 1), $data['BuildingProject'][$i]['Region']);
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,30,$this->entName,true);
//        $docObj->setValue('jzgc_oneSaid', $oneSaid);
//
//        //债券
//        $rows = count($data['BondList']);
//        $docObj->cloneRow('zq_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zq_no#" . ($i + 1), $i + 1);
//            //债券简称
//            $docObj->setValue("zq_ShortName#" . ($i + 1), $data['BondList'][$i]['ShortName']);
//            //债券代码
//            $docObj->setValue("zq_BondCode#" . ($i + 1), $data['BondList'][$i]['BondCode']);
//            //债券类型
//            $docObj->setValue("zq_BondType#" . ($i + 1), $data['BondList'][$i]['BondType']);
//            //发行日期
//            $docObj->setValue("zq_ReleaseDate#" . ($i + 1), $this->formatDate($data['BondList'][$i]['ReleaseDate']));
//            //上市日期
//            $docObj->setValue("zq_LaunchDate#" . ($i + 1), $this->formatDate($data['BondList'][$i]['LaunchDate']));
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,31,$this->entName,true);
//        $docObj->setValue('zqxx_oneSaid', $oneSaid);
//
//        //网站信息
//        $rows = count($data['GetCompanyWebSite']);
//        $docObj->cloneRow('web_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("web_no#" . ($i + 1), $i + 1);
//            //网站名称
//            $docObj->setValue("web_Title#" . ($i + 1), $data['GetCompanyWebSite'][$i]['Title']);
//            //网址
//            $docObj->setValue("web_HomeSite#" . ($i + 1), $data['GetCompanyWebSite'][$i]['HomeSite']);
//            //域名
//            $docObj->setValue("web_YuMing#" . ($i + 1), $data['GetCompanyWebSite'][$i]['YuMing']);
//            //网站备案/许可证号
//            $docObj->setValue("web_BeiAn#" . ($i + 1), $data['GetCompanyWebSite'][$i]['BeiAn']);
//            //审核日期
//            $docObj->setValue("web_SDate#" . ($i + 1), $this->formatDate($data['GetCompanyWebSite'][$i]['SDate']));
//        }
//
//        //微博
//        $rows = count($data['Microblog']);
//        $docObj->cloneRow('weibo_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("weibo_no#" . ($i + 1), $i + 1);
//            //微博昵称
//            $docObj->setValue("weibo_Name#" . ($i + 1), $data['Microblog'][$i]['Name']);
//            //行业类别
//            $docObj->setValue("weibo_Tags#" . ($i + 1), $data['Microblog'][$i]['Tags']);
//            //简介
//            $docObj->setValue("weibo_Description#" . ($i + 1), $data['Microblog'][$i]['Description']);
//        }
//
//        //新闻舆情
//        $rows = count($data['CompanyNews']);
//        $docObj->cloneRow('xwyq_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("xwyq_no#" . ($i + 1), $i + 1);
//            //内容
//            $docObj->setValue("xwyq_Title#" . ($i + 1), $data['CompanyNews'][$i]['Title']);
//            //来源
//            $docObj->setValue("xwyq_Source#" . ($i + 1), $data['CompanyNews'][$i]['Source']);
//            //时间
//            $docObj->setValue("xwyq_PublishTime#" . ($i + 1), $this->formatDate($data['CompanyNews'][$i]['PublishTime']));
//        }
//
//        //团队人数
//        $rows = count($data['itemInfo']);
//        $docObj->cloneRow('tdrs_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("tdrs_no#" . ($i + 1), $i + 1);
//            //年份
//            $docObj->setValue("tdrs_year#" . ($i + 1), $data['itemInfo'][$i]['year']);
//            //人数
//            $docObj->setValue("tdrs_yoy#" . ($i + 1), (int)$data['itemInfo'][$i]['num']);
//        }
//
//        //建筑企业-专业注册人员
//        $rows = count($data['BuildingRegistrar']);
//        $docObj->cloneRow('zyry_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zyry_no#" . ($i + 1), $i + 1);
//            //姓名
//            $docObj->setValue("zyry_Name#" . ($i + 1), $data['BuildingRegistrar'][$i]['Name']);
//            //注册类别
//            $docObj->setValue("zyry_Category#" . ($i + 1), $data['BuildingRegistrar'][$i]['Category']);
//            //注册号
//            $docObj->setValue("zyry_RegNo#" . ($i + 1), $data['BuildingRegistrar'][$i]['RegNo']);
//            //注册专业
//            $docObj->setValue("zyry_Specialty#" . ($i + 1), $data['BuildingRegistrar'][$i]['Specialty']);
//        }
//
//        //招聘信息
//        $rows = count($data['Recruitment']);
//        $docObj->cloneRow('zp_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zp_no#" . ($i + 1), $i + 1);
//            //职位名称
//            $docObj->setValue("zp_Title#" . ($i + 1), $data['Recruitment'][$i]['Title']);
//            //工作地点
//            $docObj->setValue("zp_ProvinceDesc#" . ($i + 1), $data['Recruitment'][$i]['ProvinceDesc']);
//            //月薪
//            $docObj->setValue("zp_Salary#" . ($i + 1), $data['Recruitment'][$i]['Salary']);
//            //经验
//            $docObj->setValue("zp_Experience#" . ($i + 1), $data['Recruitment'][$i]['Experience']);
//            //学历
//            $docObj->setValue("zp_Education#" . ($i + 1), $data['Recruitment'][$i]['Education']);
//            //发布日期
//            $docObj->setValue("zp_PublishDate#" . ($i + 1), $this->formatDate($data['Recruitment'][$i]['PublishDate']));
//        }
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,28,$this->entName,true);
//        $docObj->setValue('zpxx_oneSaid', $oneSaid);
//
//        //财务总揽
//        if (empty($data['FinanceData']['pic']))
//        {
//            $docObj->setValue("caiwu_pic", '无财务数据或企业类型错误');
//
//        }else
//        {
//            $docObj->setImageValue("caiwu_pic", [
//                'path' => REPORT_IMAGE_TEMP_PATH . $data['FinanceData']['pic'],
//                'width' => 440,
//                'height' => 500
//            ]);
//        }
//
//        $caiwu_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,0,$this->entName,true);
//        $docObj->setValue("caiwu_oneSaid", $caiwu_oneSaid);
//
//        //业务概况
//        $rows = count($data['SearchCompanyCompanyProducts']);
//        $docObj->cloneRow('ywgk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("ywgk_no#" . ($i + 1), $i + 1);
//            //产品名称
//            $docObj->setValue("ywgk_Name#" . ($i + 1), $data['SearchCompanyCompanyProducts'][$i]['Name']);
//            //产品领域
//            $docObj->setValue("ywgk_Domain#" . ($i + 1), $data['SearchCompanyCompanyProducts'][$i]['Domain']);
//            //产品描述
//            $docObj->setValue("ywgk_Description#" . ($i + 1), $data['SearchCompanyCompanyProducts'][$i]['Description']);
//        }
//
//        //专利
//        $rows = count($data['PatentV4Search']['list']);
//        $docObj->cloneRow('zl_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zl_no#" . ($i + 1), $i + 1);
//            //名称
//            $docObj->setValue("zl_Title#" . ($i + 1), $data['PatentV4Search']['list'][$i]['Title']);
//            //专利类型
//            $docObj->setValue("zl_IPCDesc#" . ($i + 1), implode(',', $data['PatentV4Search']['list'][$i]['IPCDesc']));
//            //公开号
//            $docObj->setValue("zl_PublicationNumber#" . ($i + 1), $data['PatentV4Search']['list'][$i]['PublicationNumber']);
//            //法律状态
//            $docObj->setValue("zl_LegalStatusDesc#" . ($i + 1), $data['PatentV4Search']['list'][$i]['LegalStatusDesc']);
//            //申请日期
//            $docObj->setValue("zl_ApplicationDate#" . ($i + 1), $this->formatDate($data['PatentV4Search']['list'][$i]['ApplicationDate']));
//            //发布日期
//            $docObj->setValue("zl_PublicationDate#" . ($i + 1), $this->formatDate($data['PatentV4Search']['list'][$i]['PublicationDate']));
//        }
//        $docObj->setValue("zl_total", (int)$data['PatentV4Search']['total']);
//
//        //软件著作权
//        $rows = count($data['SearchSoftwareCr']['list']);
//        $docObj->cloneRow('rjzzq_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("rjzzq_no#" . ($i + 1), $i + 1);
//            //软件名称
//            $docObj->setValue("rjzzq_Name#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['Name']);
//            //登记号
//            $docObj->setValue("rjzzq_RegisterNo#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['RegisterNo']);
//            //登记批准日期
//            $docObj->setValue("rjzzq_RegisterAperDate#" . ($i + 1), $this->formatDate($data['SearchSoftwareCr']['list'][$i]['RegisterAperDate']));
//            //版本号
//            $docObj->setValue("rjzzq_VersionNo#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['VersionNo']);
//        }
//        $docObj->setValue("rjzzq_total", (int)$data['SearchSoftwareCr']['total']);
//
//        //商标
//        $rows = count($data['tmSearch']['list']);
//        $docObj->cloneRow('sb_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("sb_no#" . ($i + 1), $i + 1);
//            //商标
//            $docObj->setValue("sb_Name#" . ($i + 1), $data['tmSearch']['list'][$i]['Name']);
//            //图标
//            $docObj->setImageValue("sb_img#" . ($i + 1), ['path' => $data['tmSearch']['list'][$i]['ImageUrl'], 'width' => 50, 'height' => 50]);
//            //商标分类
//            $docObj->setValue("sb_FlowStatus#" . ($i + 1), $data['tmSearch']['list'][$i]['FlowStatus']);
//            //注册号
//            $docObj->setValue("sb_RegNo#" . ($i + 1), $data['tmSearch']['list'][$i]['RegNo']);
//            //流程状态
//            $docObj->setValue("sb_FlowStatusDesc#" . ($i + 1), $data['tmSearch']['list'][$i]['FlowStatusDesc']);
//            //申请日期
//            $docObj->setValue("sb_AppDate#" . ($i + 1), $this->formatDate($data['tmSearch']['list'][$i]['AppDate']));
//        }
//        $docObj->setValue("sb_total", (int)$data['tmSearch']['total']);
//
//        //作品著作权
//        $rows = count($data['SearchCopyRight']['list']);
//        $docObj->cloneRow('zpzzq_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zpzzq_no#" . ($i + 1), $i + 1);
//            //登记号
//            $docObj->setValue("zpzzq_RegisterNo#" . ($i + 1), $data['SearchCopyRight']['list'][$i]['RegisterNo']);
//            //作品名称
//            $docObj->setValue("zpzzq_Name#" . ($i + 1), $data['SearchCopyRight']['list'][$i]['Name']);
//            //创作完成日期
//            $docObj->setValue("zpzzq_FinishDate#" . ($i + 1), $this->formatDate($data['SearchCopyRight']['list'][$i]['FinishDate']));
//            //登记日期
//            $docObj->setValue("zpzzq_RegisterDate#" . ($i + 1), $this->formatDate($data['SearchCopyRight']['list'][$i]['RegisterDate']));
//        }
//        $docObj->setValue("zpzzq_total", (int)$data['SearchCopyRight']['total']);
//
//        //证书资质
//        $rows = count($data['SearchCertification']['list']);
//        $docObj->cloneRow('zzzs_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zzzs_no#" . ($i + 1), $i + 1);
//            //证书名称
//            $docObj->setValue("zzzs_Name#" . ($i + 1), $data['SearchCertification']['list'][$i]['Name']);
//            //证书类型
//            $docObj->setValue("zzzs_Type#" . ($i + 1), $data['SearchCertification']['list'][$i]['Type']);
//            //证书生效时间
//            $docObj->setValue("zzzs_StartDate#" . ($i + 1), $this->formatDate($data['SearchCertification']['list'][$i]['StartDate']));
//            //证书截止日期
//            $docObj->setValue("zzzs_EndDate#" . ($i + 1), $this->formatDate($data['SearchCertification']['list'][$i]['EndDate']));
//            //证书编号
//            $docObj->setValue("zzzs_No#" . ($i + 1), $data['SearchCertification']['list'][$i]['No']);
//        }
//        $docObj->setValue("zzzs_total", (int)$data['SearchCertification']['total']);
//
//        //纳税信用等级
//        $rows = count($data['satparty_xin']['list']);
//        $docObj->cloneRow('nsxydj_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("nsxydj_no#" . ($i + 1), $i + 1);
//            //评定时间
//            $docObj->setValue("nsxydj_sortTimeString#" . ($i + 1), $this->formatDate($data['satparty_xin']['list'][$i]['sortTimeString']));
//            //税务登记号
//            $docObj->setValue("SHXYDM#" . ($i + 1), $data['getRegisterInfo']['SHXYDM']);
//            //纳税信用等级
//            $docObj->setValue("nsxydj_eventResult#" . ($i + 1), $data['satparty_xin']['list'][$i]['detail']['eventResult']);
//            //评定单位
//            $docObj->setValue("nsxydj_authority#" . ($i + 1), $data['satparty_xin']['list'][$i]['detail']['authority']);
//        }
//        $docObj->setValue("nsxydj_total", (int)$data['satparty_xin']['total']);
//
//        $nsxydj_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,11,$this->entName,true);
//        $docObj->setValue("nsxydj_oneSaid", $nsxydj_oneSaid);
//
//        //税务许可信息
//        $rows = count($data['satparty_xuke']['list']);
//        $docObj->cloneRow('swxk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("swxk_no#" . ($i + 1), $i + 1);
//            //税务登记号
//            $docObj->setValue("SHXYDM#" . ($i + 1), $data['getRegisterInfo']['SHXYDM']);
//            //评定时间
//            $docObj->setValue("swxk_sortTimeString#" . ($i + 1), $this->formatDate($data['satparty_xuke']['list'][$i]['sortTimeString']));
//            //发布时间
//            $docObj->setValue("swxk_postTime#" . ($i + 1), $this->formatDate($data['satparty_xuke']['list'][$i]['detail']['postTime']));
//            //事件名称
//            $docObj->setValue("swxk_eventName#" . ($i + 1), $data['satparty_xuke']['list'][$i]['detail']['eventName']);
//            //管理机关
//            $docObj->setValue("swxk_authority#" . ($i + 1), $data['satparty_xuke']['list'][$i]['detail']['authority']);
//        }
//        $docObj->setValue("swxk_total", (int)$data['satparty_xuke']['total']);
//
//        $swxk_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,13,$this->entName,true);
//        $docObj->setValue("swxk_oneSaid", $swxk_oneSaid);
//
//        //税务登记信息
//        $rows = count($data['satparty_reg']['list']);
//        $docObj->cloneRow('swdj_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("swdj_no#" . ($i + 1), $i + 1);
//            //税务登记号
//            $docObj->setValue("SHXYDM#" . ($i + 1), $data['getRegisterInfo']['SHXYDM']);
//            //评定时间
//            $docObj->setValue("swdj_sortTimeString#" . ($i + 1), $this->formatDate($data['satparty_reg']['list'][$i]['sortTimeString']));
//            //事件名称
//            $docObj->setValue("swdj_eventName#" . ($i + 1), $data['satparty_reg']['list'][$i]['detail']['eventName']);
//            //事件结果
//            $docObj->setValue("swdj_eventResult#" . ($i + 1), $data['satparty_reg']['list'][$i]['detail']['eventResult']);
//            //管理机关
//            $docObj->setValue("swdj_authority#" . ($i + 1), $data['satparty_reg']['list'][$i]['detail']['authority']);
//        }
//        $docObj->setValue("swdj_total", (int)$data['satparty_reg']['total']);
//
//        $swdj_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,12,$this->entName,true);
//        $docObj->setValue("swdj_oneSaid", $swdj_oneSaid);
//
//        //税务非正常户
//        $rows = count($data['satparty_fzc']['list']);
//        $docObj->cloneRow('fzc_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("fzc_no#" . ($i + 1), $i + 1);
//            //税务登记号
//            $docObj->setValue("SHXYDM#" . ($i + 1), $data['getRegisterInfo']['SHXYDM']);
//            //认定时间
//            $docObj->setValue("fzc_sortTimeString#" . ($i + 1), $this->formatDate($data['satparty_fzc']['list'][$i]['sortTimeString']));
//            //事件名称
//            $docObj->setValue("fzc_eventName#" . ($i + 1), $data['satparty_fzc']['list'][$i]['detail']['eventName']);
//            //事件结果
//            $docObj->setValue("fzc_eventResult#" . ($i + 1), $data['satparty_fzc']['list'][$i]['detail']['eventResult']);
//            //管理机关
//            $docObj->setValue("fzc_authority#" . ($i + 1), $data['satparty_fzc']['list'][$i]['detail']['authority']);
//        }
//        $docObj->setValue("fzc_total", (int)$data['satparty_fzc']['total']);
//
//        $fzc_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,10,$this->entName,true);
//        $docObj->setValue("fzc_oneSaid", $fzc_oneSaid);
//
//        //欠税信息
//        $rows = count($data['satparty_qs']['list']);
//        $docObj->cloneRow('qs_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("qs_no#" . ($i + 1), $i + 1);
//            //税务登记号
//            $docObj->setValue("SHXYDM#" . ($i + 1), $data['getRegisterInfo']['SHXYDM']);
//            //认定时间
//            $docObj->setValue("qs_sortTimeString#" . ($i + 1), $this->formatDate($data['satparty_qs']['list'][$i]['sortTimeString']));
//            //事件名称
//            $docObj->setValue("qs_eventName#" . ($i + 1), $data['satparty_qs']['list'][$i]['detail']['eventName']);
//            //税种
//            $docObj->setValue("qs_taxCategory#" . ($i + 1), $data['satparty_qs']['list'][$i]['detail']['taxCategory']);
//            //管理机关
//            $docObj->setValue("qs_authority#" . ($i + 1), $data['satparty_qs']['list'][$i]['detail']['authority']);
//        }
//        $docObj->setValue("qs_total", (int)$data['satparty_qs']['total']);
//
//        $qs_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,8,$this->entName,true);
//        $docObj->setValue("qs_oneSaid", $qs_oneSaid);
//
//        //涉税处罚公示
//        $rows = count($data['satparty_chufa']['list']);
//        $docObj->cloneRow('sswf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("sswf_no#" . ($i + 1), $i + 1);
//            //税务登记号
//            $docObj->setValue("SHXYDM#" . ($i + 1), $data['getRegisterInfo']['SHXYDM']);
//            //处罚时间
//            $docObj->setValue("sswf_sortTimeString#" . ($i + 1), $this->formatDate($data['satparty_chufa']['list'][$i]['sortTimeString']));
//            //处罚金额
//            $docObj->setValue("sswf_money#" . ($i + 1), $data['satparty_chufa']['list'][$i]['detail']['money']);
//            //事件名称
//            $docObj->setValue("sswf_eventName#" . ($i + 1), $data['satparty_chufa']['list'][$i]['detail']['eventName']);
//            //事件结果
//            $docObj->setValue("sswf_eventResult#" . ($i + 1), $data['satparty_chufa']['list'][$i]['detail']['eventResult']);
//            //管理机关
//            $docObj->setValue("sswf_authority#" . ($i + 1), $data['satparty_chufa']['list'][$i]['detail']['authority']);
//        }
//        $docObj->setValue("sswf_total", (int)$data['satparty_chufa']['total']);
//
//        $sswf_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,9,$this->entName,true);
//        $docObj->setValue("sswf_oneSaid", $sswf_oneSaid);
//
//        //行政许可
//        $rows = count($data['GetAdministrativeLicenseList']['list']);
//        $docObj->cloneRow('xzxk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("xzxk_no#" . ($i + 1), $i + 1);
//            //许可编号
//            $docObj->setValue("xzxk_CaseNo#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['CaseNo']);
//            //有效期自
//            $docObj->setValue("xzxk_LianDate#" . ($i + 1), $this->formatDate($data['GetAdministrativeLicenseList']['list'][$i]['detail']['LianDate']));
//            //有效期止
//            $docObj->setValue("xzxk_ExpireDate#" . ($i + 1), $this->formatDate($data['GetAdministrativeLicenseList']['list'][$i]['detail']['ExpireDate']));
//            //许可内容
//            $docObj->setValue("xzxk_Content#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['Content']);
//            //许可机关
//            $docObj->setValue("xzxk_Province#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['Province']);
//        }
//        $docObj->setValue("xzxk_total", (int)$data['GetAdministrativeLicenseList']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,32,$this->entName,true);
//        $docObj->setValue('xzxk_oneSaid', $oneSaid);
//
//        //行政处罚
//        $rows = count($data['GetAdministrativePenaltyList']['list']);
//        $docObj->cloneRow('xzcf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("xzcf_no#" . ($i + 1), $i + 1);
//            //文书号
//            $docObj->setValue("xzcf_CaseNo#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['CaseNo']);
//            //决定日期
//            $docObj->setValue("xzcf_LianDate#" . ($i + 1), $this->formatDate($data['GetAdministrativePenaltyList']['list'][$i]['LianDate']));
//            //内容
//            $docObj->setValue("xzcf_Content#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['detail']['Content']);
//            //决定机关
//            $docObj->setValue("xzcf_ExecuteGov#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['detail']['ExecuteGov']);
//        }
//        $docObj->setValue("xzcf_total", (int)$data['GetAdministrativePenaltyList']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,33,$this->entName,true);
//        $docObj->setValue('xzcf_oneSaid', $oneSaid);
//
//        //环保处罚
//        $rows = count($data['epbparty']['list']);
//        $docObj->cloneRow('hbcf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("hbcf_no#" . ($i + 1), $i + 1);
//            //案号
//            $docObj->setValue("hbcf_caseNo#" . ($i + 1), $data['epbparty']['list'][$i]['detail']['caseNo']);
//            //事件名称(类型)
//            $docObj->setValue("hbcf_eventName#" . ($i + 1), $data['epbparty']['list'][$i]['detail']['eventName']);
//            //处罚金额
//            $docObj->setValue("hbcf_money#" . ($i + 1), $data['epbparty']['list'][$i]['detail']['money']);
//            //处罚机关
//            $docObj->setValue("hbcf_authority#" . ($i + 1), $data['epbparty']['list'][$i]['detail']['authority']);
//        }
//        $docObj->setValue("hbcf_total", (int)$data['epbparty']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,34,$this->entName,true);
//        $docObj->setValue('hbcf_oneSaid', $oneSaid);
//
//        //重点监控企业名单
//        $rows = count($data['epbparty_jkqy']['list']);
//        $docObj->cloneRow('zdjkqy_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zdjkqy_no#" . ($i + 1), $i + 1);
//            //监控名称
//            $docObj->setValue("zdjkqy_eventName#" . ($i + 1), $data['epbparty_jkqy']['list'][$i]['detail']['eventName']);
//            //涉事企业
//            $docObj->setValue("zdjkqy_pname#" . ($i + 1), $data['epbparty_jkqy']['list'][$i]['detail']['pname']);
//        }
//        $docObj->setValue("zdjkqy_total", (int)$data['epbparty_jkqy']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,35,$this->entName,true);
//        $docObj->setValue('zdjkqy_oneSaid', $oneSaid);
//
//        //环保企业自行监测结果
//        $rows = count($data['epbparty_zxjc']['list']);
//        $docObj->cloneRow('zxjc_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zxjc_no#" . ($i + 1), $i + 1);
//            //监测指标/污染项目
//            $docObj->setValue("zxjc_pollutant#" . ($i + 1), $data['epbparty_zxjc']['list'][$i]['detail']['pollutant']);
//            //监测结果
//            $docObj->setValue("zxjc_density#" . ($i + 1), $data['epbparty_zxjc']['list'][$i]['detail']['density']);
//            //事件结果
//            $docObj->setValue("zxjc_eventResult#" . ($i + 1), $data['epbparty_zxjc']['list'][$i]['detail']['eventResult']);
//            //监测时间
//            $docObj->setValue("zxjc_sortTimeString#" . ($i + 1), $this->formatDate($data['epbparty_zxjc']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("zxjc_total", (int)$data['epbparty_zxjc']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,36,$this->entName,true);
//        $docObj->setValue('zxjc_oneSaid', $oneSaid);
//
//        //环评公示数据
//        $rows = count($data['epbparty_huanping']['list']);
//        $docObj->cloneRow('hpgs_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("hpgs_no#" . ($i + 1), $i + 1);
//            //公告类型
//            $docObj->setValue("hpgs_eventName#" . ($i + 1), $data['epbparty_huanping']['list'][$i]['detail']['eventName']);
//            //建设单位
//            $docObj->setValue("hpgs_pname#" . ($i + 1), $data['epbparty_huanping']['list'][$i]['detail']['pname']);
//            //发生时间
//            $docObj->setValue("hpgs_sortTimeString#" . ($i + 1), $this->formatDate($data['epbparty_huanping']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("hpgs_total", (int)$data['epbparty_huanping']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,37,$this->entName,true);
//        $docObj->setValue('hpgs_oneSaid', $oneSaid);
//
//        //海关许可
//        $rows = count($data['custom_qy']['list']);
//        $docObj->cloneRow('hgxx_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("hgxx_no#" . ($i + 1), $i + 1);
//            //海关注册码
//            $docObj->setValue("hgxx_regNo#" . ($i + 1), $data['custom_qy']['list'][$i]['detail']['regNo']);
//            //注册海关
//            $docObj->setValue("hgxx_custom#" . ($i + 1), $data['custom_qy']['list'][$i]['detail']['custom']);
//            //经营类别
//            $docObj->setValue("hgxx_category#" . ($i + 1), $data['custom_qy']['list'][$i]['detail']['category']);
//            //注册时间
//            $docObj->setValue("hgxx_sortTimeString#" . ($i + 1), $this->formatDate($data['custom_qy']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("hgxx_total", (int)$data['custom_qy']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,38,$this->entName,true);
//        $docObj->setValue('hgxx_oneSaid', $oneSaid);
//
//        //海关许可
//        $rows = count($data['custom_xuke']['list']);
//        $docObj->cloneRow('hgxk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("hgxk_no#" . ($i + 1), $i + 1);
//            //许可文书号
//            $docObj->setValue("hgxk_xkNo#" . ($i + 1), $data['custom_xuke']['list'][$i]['detail']['xkNo']);
//            //标题
//            $docObj->setValue("hgxk_title#" . ($i + 1), $data['custom_xuke']['list'][$i]['detail']['title']);
//            //许可机关
//            $docObj->setValue("hgxk_authority#" . ($i + 1), $data['custom_xuke']['list'][$i]['detail']['authority']);
//            //注册时间
//            $docObj->setValue("hgxk_sortTimeString#" . ($i + 1), $this->formatDate($data['custom_xuke']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("hgxk_total", (int)$data['custom_xuke']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,39,$this->entName,true);
//        $docObj->setValue('hgxk_oneSaid', $oneSaid);
//
//        //海关信用
//        $rows = count($data['custom_credit']['list']);
//        $docObj->cloneRow('hgxy_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("hgxy_no#" . ($i + 1), $i + 1);
//            //所属海关
//            $docObj->setValue("hgxy_authority#" . ($i + 1), $data['custom_credit']['list'][$i]['detail']['authority']);
//            //信用等级
//            $docObj->setValue("hgxy_creditRank#" . ($i + 1), $data['custom_credit']['list'][$i]['detail']['creditRank']);
//            //认定年份
//            $docObj->setValue("hgxy_sortTimeString#" . ($i + 1), $this->formatDate($data['custom_credit']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("hgxy_total", (int)$data['custom_credit']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,40,$this->entName,true);
//        $docObj->setValue('hgxy_oneSaid', $oneSaid);
//
//        //海关处罚
//        $rows = count($data['custom_punish']['list']);
//        $docObj->cloneRow('hgcf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("hgcf_no#" . ($i + 1), $i + 1);
//            //公告类型
//            $docObj->setValue("hgcf_ggType#" . ($i + 1), $data['custom_punish']['list'][$i]['detail']['ggType']);
//            //处罚类别/案件性质
//            $docObj->setValue("hgcf_eventType#" . ($i + 1), $data['custom_punish']['list'][$i]['detail']['eventType']);
//            //处罚日期
//            $docObj->setValue("hgcf_sortTimeString#" . ($i + 1), $this->formatDate($data['custom_punish']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("hgcf_total", (int)$data['custom_punish']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,41,$this->entName,true);
//        $docObj->setValue('hgcf_oneSaid', $oneSaid);
//
//        //央行行政处罚
//        $rows = count($data['pbcparty']['list']);
//        $docObj->cloneRow('yhxzcf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("yhxzcf_no#" . ($i + 1), $i + 1);
//            //标题
//            $docObj->setValue("yhxzcf_title#" . ($i + 1), $data['pbcparty']['list'][$i]['detail']['title']);
//            //事件名称
//            $docObj->setValue("yhxzcf_eventName#" . ($i + 1), $data['pbcparty']['list'][$i]['detail']['eventName']);
//            //事件结果
//            $docObj->setValue("yhxzcf_eventResult#" . ($i + 1), $data['pbcparty']['list'][$i]['detail']['eventResult']);
//            //管理机关
//            $docObj->setValue("yhxzcf_authority#" . ($i + 1), $data['pbcparty']['list'][$i]['detail']['authority']);
//            //处罚时间
//            $docObj->setValue("yhxzcf_sortTimeString#" . ($i + 1), $this->formatDate($data['pbcparty']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("yhxzcf_total", (int)$data['pbcparty']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,46,$this->entName,true);
//        $docObj->setValue('yhxzcf_oneSaid', $oneSaid);
//
//        //银保监会处罚公示
//        $rows = count($data['pbcparty_cbrc']['list']);
//        $docObj->cloneRow('ybjcf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("ybjcf_no#" . ($i + 1), $i + 1);
//            //公告编号
//            $docObj->setValue("ybjcf_caseNo#" . ($i + 1), $data['pbcparty_cbrc']['list'][$i]['detail']['caseNo']);
//            //事件名称
//            $docObj->setValue("ybjcf_eventName#" . ($i + 1), $data['pbcparty_cbrc']['list'][$i]['detail']['eventName']);
//            //事件结果
//            $docObj->setValue("ybjcf_eventResult#" . ($i + 1), $data['pbcparty_cbrc']['list'][$i]['detail']['eventResult']);
//            //管理机关
//            $docObj->setValue("ybjcf_authority#" . ($i + 1), $data['pbcparty_cbrc']['list'][$i]['detail']['authority']);
//            //处罚时间
//            $docObj->setValue("ybjcf_sortTimeString#" . ($i + 1), $this->formatDate($data['pbcparty_cbrc']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("ybjcf_total", (int)$data['pbcparty_cbrc']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,47,$this->entName,true);
//        $docObj->setValue('ybjcf_oneSaid', $oneSaid);
//
//        //证监处罚公示
//        $rows = count($data['pbcparty_csrc_chufa']['list']);
//        $docObj->cloneRow('zjcf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zjcf_no#" . ($i + 1), $i + 1);
//            //决定书文号
//            $docObj->setValue("zjcf_caseNo#" . ($i + 1), $data['pbcparty_csrc_chufa']['list'][$i]['detail']['caseNo']);
//            //公告类型
//            $docObj->setValue("zjcf_eventName#" . ($i + 1), $data['pbcparty_csrc_chufa']['list'][$i]['detail']['eventName']);
//            //处罚结果
//            $docObj->setValue("zjcf_eventResult#" . ($i + 1), $data['pbcparty_csrc_chufa']['list'][$i]['detail']['eventResult']);
//            //处罚机关
//            $docObj->setValue("zjcf_authority#" . ($i + 1), $data['pbcparty_csrc_chufa']['list'][$i]['detail']['authority']);
//            //处罚时间
//            $docObj->setValue("zjcf_sortTimeString#" . ($i + 1), $this->formatDate($data['pbcparty_csrc_chufa']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("zjcf_total", (int)$data['pbcparty_csrc_chufa']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,48,$this->entName,true);
//        $docObj->setValue('zjcf_oneSaid', $oneSaid);
//
//        //证监会许可信息
//        $rows = count($data['pbcparty_csrc_xkpf']['list']);
//        $docObj->cloneRow('zjxk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zjxk_no#" . ($i + 1), $i + 1);
//            //文书号
//            $docObj->setValue("zjxk_caseNo#" . ($i + 1), $data['pbcparty_csrc_xkpf']['list'][$i]['detail']['caseNo']);
//            //许可事项
//            $docObj->setValue("zjxk_title#" . ($i + 1), $data['pbcparty_csrc_xkpf']['list'][$i]['detail']['title']);
//            //管理机关
//            $docObj->setValue("zjxk_authority#" . ($i + 1), $data['pbcparty_csrc_xkpf']['list'][$i]['detail']['authority']);
//            //许可时间
//            $docObj->setValue("zjxk_sortTimeString#" . ($i + 1), $this->formatDate($data['pbcparty_csrc_xkpf']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("zjxk_total", (int)$data['pbcparty_csrc_xkpf']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,49,$this->entName,true);
//        $docObj->setValue('zjxk_oneSaid', $oneSaid);
//
//        //外汇局处罚
//        $rows = count($data['safe_chufa']['list']);
//        $docObj->cloneRow('whjcf_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("whjcf_no#" . ($i + 1), $i + 1);
//            //文书号
//            $docObj->setValue("whjcf_caseNo#" . ($i + 1), $data['safe_chufa']['list'][$i]['detail']['caseNo']);
//            //违规行为
//            $docObj->setValue("whjcf_caseCause#" . ($i + 1), $data['safe_chufa']['list'][$i]['detail']['caseCause']);
//            //罚款结果
//            $docObj->setValue("whjcf_eventResult#" . ($i + 1), $data['safe_chufa']['list'][$i]['detail']['eventResult']);
//            //罚款金额
//            $docObj->setValue("whjcf_money#" . ($i + 1), $data['safe_chufa']['list'][$i]['detail']['money']);
//            //执行机关
//            $docObj->setValue("whjcf_authority#" . ($i + 1), $data['safe_chufa']['list'][$i]['detail']['authority']);
//            //处罚时间
//            $docObj->setValue("whjcf_sortTimeString#" . ($i + 1), $this->formatDate($data['safe_chufa']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("whjcf_total", (int)$data['safe_chufa']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,50,$this->entName,true);
//        $docObj->setValue('whjcf_oneSaid', $oneSaid);
//
//        //外汇局许可
//        $rows = count($data['safe_xuke']['list']);
//        $docObj->cloneRow('whjxk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("whjxk_no#" . ($i + 1), $i + 1);
//            //许可文书号
//            $docObj->setValue("whjxk_caseNo#" . ($i + 1), $data['safe_xuke']['list'][$i]['detail']['caseNo']);
//            //项目名称
//            $docObj->setValue("whjxk_eventName#" . ($i + 1), $data['safe_xuke']['list'][$i]['detail']['eventName']);
//            //许可事项
//            $docObj->setValue("whjxk_eventType#" . ($i + 1), $data['safe_xuke']['list'][$i]['detail']['eventType']);
//            //许可机关
//            $docObj->setValue("whjxk_authority#" . ($i + 1), $data['safe_xuke']['list'][$i]['detail']['authority']);
//            //处罚时间
//            $docObj->setValue("whjxk_sortTimeString#" . ($i + 1), $this->formatDate($data['safe_xuke']['list'][$i]['sortTimeString']));
//        }
//        $docObj->setValue("whjxk_total", (int)$data['safe_xuke']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,51,$this->entName,true);
//        $docObj->setValue('whjxk_oneSaid', $oneSaid);
//
//        //法院公告
//        $rows = count($data['fygg']['list']);
//        $docObj->cloneRow('fygg_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("fygg_no#" . ($i + 1), $i + 1);
//            //案号
//            $docObj->setValue("fygg_caseNo#" . ($i + 1), $data['fygg']['list'][$i]['detail']['caseNo']);
//            //公告法院
//            $docObj->setValue("fygg_court#" . ($i + 1), $data['fygg']['list'][$i]['detail']['court']);
//            //立案时间
//            $docObj->setValue("fygg_sortTimeString#" . ($i + 1), $this->formatDate($data['fygg']['list'][$i]['sortTimeString']));
//
//            $content = '';
//            foreach ($data['fygg']['list'][$i]['detail']['partys'] as $no => $arr) {
//                $content .= '<w:br/>';
//                $content .= ($no + 1) . ':';
//                $content .= $arr['caseCauseT'] . ' - ';
//                $content .= $arr['pname'] . ' - ';
//                $content .= $arr['partyTitleT'] . ' - ';
//                switch ($arr['partyPositionT']) {
//                    case 'p':
//                        $content .= '原告';
//                        break;
//                    case 'd':
//                        $content .= '被告';
//                        break;
//                    case 't':
//                        $content .= '第三人';
//                        break;
//                    case 'u':
//                        $content .= '当事人';
//                        break;
//                    default:
//                        $content .= '';
//                        break;
//                }
//                $content .= '<w:br/>';
//            }
//            //案由-当事人-称号-诉讼地位(原审)
//            $docObj->setValue("fygg_content#" . ($i + 1), $content);
//        }
//        $docObj->setValue("fygg_total", (int)$data['fygg']['total']);
//
//        //oneSaid
//        $fygg_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,3,$this->entName,true);
//        $docObj->setValue("fygg_oneSaid", $fygg_oneSaid);
//
//        //开庭公告
//        $rows = count($data['ktgg']['list']);
//        $docObj->cloneRow('ktgg_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("ktgg_no#" . ($i + 1), $i + 1);
//            //案号
//            $docObj->setValue("ktgg_caseNo#" . ($i + 1), $data['ktgg']['list'][$i]['detail']['caseNo']);
//            //法院名称
//            $docObj->setValue("ktgg_court#" . ($i + 1), $data['ktgg']['list'][$i]['detail']['court']);
//            //立案时间
//            $docObj->setValue("ktgg_sortTimeString#" . ($i + 1), $this->formatDate($data['ktgg']['list'][$i]['sortTimeString']));
//
//            $content = '';
//            foreach ($data['ktgg']['list'][$i]['detail']['partys'] as $no => $arr) {
//                $content .= '<w:br/>';
//                $content .= ($no + 1) . ':';
//                $content .= $arr['courtTypeT'] . ' - ';
//                $content .= $arr['caseCauseT'] . ' - ';
//                $content .= $arr['pname'] . ' - ';
//                $content .= $arr['partyTitleT'] . ' - ';
//                switch ($arr['partyPositionT']) {
//                    case 'p':
//                        $content .= '原告';
//                        break;
//                    case 'd':
//                        $content .= '被告';
//                        break;
//                    case 't':
//                        $content .= '第三人';
//                        break;
//                    case 'u':
//                        $content .= '当事人';
//                        break;
//                    default:
//                        $content .= '';
//                        break;
//                }
//                $content .= '<w:br/>';
//            }
//            //法院类型-案由-当事人-称号-诉讼地位(原审)
//            $docObj->setValue("ktgg_content#" . ($i + 1), $content);
//        }
//        $docObj->setValue("ktgg_total", (int)$data['ktgg']['total']);
//
//        $ktgg_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,1,$this->entName,true);
//        $docObj->setValue("ktgg_oneSaid", $ktgg_oneSaid);
//
//        //裁判文书
//        $rows = count($data['cpws']['list']);
//        $docObj->cloneRow('cpws_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("cpws_no#" . ($i + 1), $i + 1);
//            //案号
//            $docObj->setValue("cpws_caseNo#" . ($i + 1), $data['cpws']['list'][$i]['detail']['caseNo']);
//            //法院名称
//            $docObj->setValue("cpws_court#" . ($i + 1), $data['cpws']['list'][$i]['detail']['court']);
//            //审结时间
//            $docObj->setValue("cpws_sortTimeString#" . ($i + 1), $this->formatDate($data['cpws']['list'][$i]['sortTimeString']));
//            //审理状态
//            $docObj->setValue("cpws_trialProcedure#" . ($i + 1), $data['cpws']['list'][$i]['detail']['trialProcedure']);
//
//            $content = '';
//            foreach ($data['cpws']['list'][$i]['detail']['partys'] as $no => $arr) {
//                $content .= '<w:br/>';
//                $content .= ($no + 1) . ':';
//                $content .= $arr['caseCauseT'] . ' - ';
//                $content .= $arr['pname'] . ' - ';
//                $content .= $arr['partyTitleT'] . ' - ';
//                switch ($arr['partyPositionT']) {
//                    case 'p':
//                        $content .= '原告';
//                        break;
//                    case 'd':
//                        $content .= '被告';
//                        break;
//                    case 't':
//                        $content .= '第三人';
//                        break;
//                    case 'u':
//                        $content .= '当事人';
//                        break;
//                    default:
//                        $content .= '';
//                        break;
//                }
//                $content .= '<w:br/>';
//            }
//            //法院类型-案由-当事人-称号-诉讼地位(原审)
//            $docObj->setValue("cpws_content#" . ($i + 1), $content);
//        }
//        $docObj->setValue("cpws_total", (int)$data['cpws']['total']);
//
//        //oneSaid
//        $cpws_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,2,$this->entName,true);
//        $docObj->setValue("cpws_oneSaid", $cpws_oneSaid);
//
//        //执行公告
//        $rows = count($data['zxgg']['list']);
//        $docObj->cloneRow('zxgg_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zxgg_no#" . ($i + 1), $i + 1);
//            //案号
//            $docObj->setValue("zxgg_caseNo#" . ($i + 1), $data['zxgg']['list'][$i]['detail']['caseNo']);
//            //法院名称
//            $docObj->setValue("zxgg_court#" . ($i + 1), $data['zxgg']['list'][$i]['detail']['court']);
//            //立案日期
//            $docObj->setValue("zxgg_sortTimeString#" . ($i + 1), $this->formatDate($data['zxgg']['list'][$i]['sortTimeString']));
//
//            $content = '';
//            foreach ($data['zxgg']['list'][$i]['detail']['partys'] as $no => $arr) {
//                $content .= '<w:br/>';
//                $content .= ($no + 1) . ':';
//                $content .= $arr['caseStateT'] . ' - ';
//                $content .= $arr['execMoney'] . ' - ';
//                $content .= $arr['pname'];
//                $content .= '<w:br/>';
//            }
//            //案件状态-执行金额-当事人
//            $docObj->setValue("zxgg_content#" . ($i + 1), $content);
//        }
//        $docObj->setValue("zxgg_total", (int)$data['zxgg']['total']);
//
//        //oneSaid
//        $zxgg_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,4,$this->entName,true);
//        $docObj->setValue("zxgg_oneSaid", $zxgg_oneSaid);
//
//        //失信公告
//        $rows = count($data['shixin']['list']);
//        $docObj->cloneRow('sx_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("sx_no#" . ($i + 1), $i + 1);
//            //案号
//            $docObj->setValue("sx_caseNo#" . ($i + 1), $data['shixin']['list'][$i]['detail']['caseNo']);
//            //法院名称
//            $docObj->setValue("sx_court#" . ($i + 1), $data['shixin']['list'][$i]['detail']['court']);
//            //立案日期
//            $docObj->setValue("sx_sortTimeString#" . ($i + 1), $this->formatDate($data['shixin']['list'][$i]['sortTimeString']));
//
//            $content = '';
//            foreach ($data['shixin']['list'][$i]['detail']['partys'] as $no => $arr) {
//                $content .= '<w:br/>';
//                $content .= ($no + 1) . ':';
//                $content .= $arr['lxqkT'] . ' - ';
//                $content .= $arr['jtqx'] . ' - ';
//                $content .= $arr['money'] . ' - ';
//                $content .= $arr['pname'];
//                $content .= '<w:br/>';
//            }
//            //履行情况-具体情形-涉案金额-当事人
//            $docObj->setValue("sx_content#" . ($i + 1), $content);
//        }
//        $docObj->setValue("sx_total", (int)$data['shixin']['total']);
//
//        //oneSaid
//        $sx_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,5,$this->entName,true);
//        $docObj->setValue("sx_oneSaid", $sx_oneSaid);
//
//        //被执行人
//        $rows = count($data['SearchZhiXing']['list']);
//        $docObj->cloneRow('bzxr_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("bzxr_no#" . ($i + 1), $i + 1);
//            //案号
//            $docObj->setValue("bzxr_Anno#" . ($i + 1), $data['SearchZhiXing']['list'][$i]['Anno']);
//            //执行法院
//            $docObj->setValue("bzxr_ExecuteGov#" . ($i + 1), $data['SearchZhiXing']['list'][$i]['ExecuteGov']);
//            //立案时间
//            $docObj->setValue("bzxr_Liandate#" . ($i + 1), $this->formatDate($data['SearchZhiXing']['list'][$i]['Liandate']));
//            //执行标的
//            $docObj->setValue("bzxr_Biaodi#" . ($i + 1), $data['SearchZhiXing']['list'][$i]['Biaodi']);
//            //案件状态
//            $docObj->setValue("bzxr_Status#" . ($i + 1), $data['SearchZhiXing']['list'][$i]['Status']);
//        }
//        $docObj->setValue("bzxr_total", (int)$data['SearchZhiXing']['total']);
//
//        //oneSaid
//        $bzxr_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,5,$this->entName,true);
//        $docObj->setValue("bzxr_oneSaid", $bzxr_oneSaid);
//
//        //查封冻结扣押
//        $rows = count($data['sifacdk']['list']);
//        $docObj->cloneRow('cdk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("cdk_no#" . ($i + 1), $i + 1);
//            //案件编号
//            $docObj->setValue("cdk_caseNo#" . ($i + 1), $data['sifacdk']['list'][$i]['detail']['caseNo']);
//            //标的名称
//            $docObj->setValue("cdk_objectName#" . ($i + 1), $data['sifacdk']['list'][$i]['detail']['objectName']);
//            //标的类型
//            $docObj->setValue("cdk_objectType#" . ($i + 1), $data['sifacdk']['list'][$i]['detail']['objectType']);
//            //审理法院
//            $docObj->setValue("cdk_court#" . ($i + 1), $data['sifacdk']['list'][$i]['detail']['court']);
//            //审结时间
//            $docObj->setValue("cdk_postTime#" . ($i + 1), $this->formatDate($data['sifacdk']['list'][$i]['detail']['postTime']));
//            //事件时间
//            $docObj->setValue("cdk_sortTimeString#" . ($i + 1), $this->formatDate($data['sifacdk']['list'][$i]['sortTimeString']));
//            //涉及金额
//            $docObj->setValue("cdk_money#" . ($i + 1), $data['sifacdk']['list'][$i]['detail']['money']);
//        }
//        $docObj->setValue("cdk_total", (int)$data['sifacdk']['total']);
//
//        //oneSaid
//        $cdk_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,6,$this->entName,true);
//        $docObj->setValue("cdk_oneSaid", $cdk_oneSaid);
//
//        //动产抵押
//        $rows = count($data['getChattelMortgageInfo']['list']);
//        $docObj->cloneRow('dcdy_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("dcdy_no#" . ($i + 1), $i + 1);
//            //登记编号
//            $docObj->setValue("dcdy_DJBH#" . ($i + 1), $data['getChattelMortgageInfo']['list'][$i]['DJBH']);
//            //公示日期
//            $docObj->setValue("dcdy_GSRQ#" . ($i + 1), $this->formatDate($data['getChattelMortgageInfo']['list'][$i]['GSRQ']));
//            //登记日期
//            $docObj->setValue("dcdy_DJRQ#" . ($i + 1), $this->formatDate($data['getChattelMortgageInfo']['list'][$i]['DJRQ']));
//            //登记机关
//            $docObj->setValue("dcdy_DJJG#" . ($i + 1), $data['getChattelMortgageInfo']['list'][$i]['DJJG']);
//            //被担保债权数额
//            $docObj->setValue("dcdy_BDBZQSE#" . ($i + 1), $data['getChattelMortgageInfo']['list'][$i]['BDBZQSE']);
//            //状态
//            $docObj->setValue("dcdy_ZT#" . ($i + 1), $data['getChattelMortgageInfo']['list'][$i]['ZT']);
//        }
//        $docObj->setValue("dcdy_total", (int)$data['getChattelMortgageInfo']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,43,$this->entName,true);
//        $docObj->setValue('dcdy_oneSaid', $oneSaid);
//
//        //股权出质
//        $rows = count($data['getEquityPledgedInfo']['list']);
//        $docObj->cloneRow('gqcz_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("gqcz_no#" . ($i + 1), $i + 1);
//            //登记编号
//            $docObj->setValue("gqcz_DJBH#" . ($i + 1), $data['getEquityPledgedInfo']['list'][$i]['DJBH']);
//            //股权出质设立登记日期
//            $docObj->setValue("gqcz_GQCZSLDJRQ#" . ($i + 1), $this->formatDate($data['getEquityPledgedInfo']['list'][$i]['GQCZSLDJRQ']));
//            //质权人
//            $docObj->setValue("gqcz_ZQR#" . ($i + 1), $data['getEquityPledgedInfo']['list'][$i]['ZQR']);
//            //出质人
//            $docObj->setValue("gqcz_CZR#" . ($i + 1), $data['getEquityPledgedInfo']['list'][$i]['CZR']);
//            //出质股权数额
//            $docObj->setValue("gqcz_CZGQSE#" . ($i + 1), $data['getEquityPledgedInfo']['list'][$i]['CZGQSE']);
//            //状态
//            $docObj->setValue("gqcz_ZT#" . ($i + 1), $data['getEquityPledgedInfo']['list'][$i]['ZT']);
//        }
//        $docObj->setValue("gqcz_total", (int)$data['getEquityPledgedInfo']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,42,$this->entName,true);
//        $docObj->setValue('gqcz_oneSaid', $oneSaid);
//
//        //对外担保
//        $rows = count($data['GetAnnualReport']['list']);
//        $docObj->cloneRow('dwdb_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("dwdb_no#" . ($i + 1), $i + 1);
//            //担保方
//            $docObj->setValue("dwdb_Debtor#" . ($i + 1), $data['GetAnnualReport']['list'][$i]['Debtor']);
//            //被担保方
//            $docObj->setValue("dwdb_Creditor#" . ($i + 1), $data['GetAnnualReport']['list'][$i]['Creditor']);
//            //担保金额(万元)
//            $docObj->setValue("dwdb_CreditorAmount#" . ($i + 1), $data['GetAnnualReport']['list'][$i]['CreditorAmount']);
//            //保证方式
//            $docObj->setValue("dwdb_AssuranceType#" . ($i + 1), $data['GetAnnualReport']['list'][$i]['AssuranceType']);
//            //担保期起 担保期止
//            $docObj->setValue("dwdb_FulfillObligation#" . ($i + 1), $data['GetAnnualReport']['list'][$i]['FulfillObligation']);
//        }
//        $docObj->setValue("dwdb_total", (int)$data['GetAnnualReport']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,45,$this->entName,true);
//        $docObj->setValue('dwdb_oneSaid', $oneSaid);
//
//        //土地抵押
//        $rows = count($data['GetLandMortgageList']['list']);
//        $docObj->cloneRow('tddy_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("tddy_no#" . ($i + 1), $i + 1);
//            //开始日期
//            $docObj->setValue("tddy_StartDate#" . ($i + 1), $this->formatDate($data['GetLandMortgageList']['list'][$i]['StartDate']));
//            //结束日期
//            $docObj->setValue("tddy_EndDate#" . ($i + 1), $this->formatDate($data['GetLandMortgageList']['list'][$i]['EndDate']));
//            //抵押面积(公顷)
//            $docObj->setValue("tddy_MortgageAcreage#" . ($i + 1), $data['GetLandMortgageList']['list'][$i]['MortgageAcreage']);
//            //抵押用途
//            $docObj->setValue("tddy_MortgagePurpose#" . ($i + 1), $data['GetLandMortgageList']['list'][$i]['MortgagePurpose']);
//            //行政区地址
//            $docObj->setValue("tddy_Address#" . ($i + 1), $data['GetLandMortgageList']['list'][$i]['Address']);
//        }
//        $docObj->setValue("tddy_total", (int)$data['GetLandMortgageList']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,44,$this->entName,true);
//        $docObj->setValue('tddy_oneSaid', $oneSaid);
//
//        //应收帐款
//        $rows = count($data['company_zdw_yszkdsr']['list']);
//        $docObj->cloneRow('yszk_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("yszk_no#" . ($i + 1), $i + 1);
//            //质押财产/转让财产描述
//            $docObj->setValue("yszk_transPro_desc#" . ($i + 1), $data['company_zdw_yszkdsr']['list'][$i]['detail']['transPro_desc']);
//            //登记时间
//            $docObj->setValue("yszk_sortTime#" . ($i + 1), $this->formatDate($data['company_zdw_yszkdsr']['list'][$i]['detail']['sortTime']));
//            //登记到期日
//            $docObj->setValue("yszk_endTime#" . ($i + 1), $this->formatDate($data['company_zdw_yszkdsr']['list'][$i]['detail']['endTime']));
//            //转让财产价值
//            $docObj->setValue("yszk_transPro_value#" . ($i + 1), $data['company_zdw_yszkdsr']['list'][$i]['detail']['transPro_value']);
//        }
//        $docObj->setValue("yszk_total", (int)$data['company_zdw_yszkdsr']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,52,$this->entName,true);
//        $docObj->setValue('yszk_oneSaid', $oneSaid);
//
//        //租赁登记
//        $rows = count($data['company_zdw_zldjdsr']['list']);
//        $docObj->cloneRow('zldj_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("zldj_no#" . ($i + 1), $i + 1);
//            //租赁财产描述
//            $docObj->setValue("zldj_leaseMes_desc#" . ($i + 1), $data['company_zdw_zldjdsr']['list'][$i]['detail']['leaseMes_desc']);
//            //登记期限
//            $docObj->setValue("zldj_basic_date#" . ($i + 1), $this->formatDate($data['company_zdw_zldjdsr']['list'][$i]['detail']['basic_date']));
//            //登记到期日
//            $docObj->setValue("zldj_endTime#" . ($i + 1), $this->formatDate($data['company_zdw_zldjdsr']['list'][$i]['detail']['endTime']));
//            //登记日期
//            $docObj->setValue("zldj_sortTime#" . ($i + 1), $this->formatDate($data['company_zdw_zldjdsr']['list'][$i]['detail']['sortTime']));
//        }
//        $docObj->setValue("zldj_total", (int)$data['company_zdw_zldjdsr']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,56,$this->entName,true);
//        $docObj->setValue('zldj_oneSaid', $oneSaid);
//
//        //保证金质押
//        $rows = count($data['company_zdw_bzjzydsr']['list']);
//        $docObj->cloneRow('bzjzy_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("bzjzy_no#" . ($i + 1), $i + 1);
//            //主合同金额
//            $docObj->setValue("bzjzy_pledgePro_proMoney#" . ($i + 1), $data['company_zdw_bzjzydsr']['list'][$i]['detail']['pledgePro_proMoney']);
//            //保证金金额
//            $docObj->setValue("bzjzy_pledgePro_depMoney#" . ($i + 1), $data['company_zdw_bzjzydsr']['list'][$i]['detail']['pledgePro_depMoney']);
//            //登记种类
//            $docObj->setValue("bzjzy_basic_type#" . ($i + 1), $data['company_zdw_bzjzydsr']['list'][$i]['detail']['basic_type']);
//            //登记期限
//            $docObj->setValue("bzjzy_basic_date#" . ($i + 1), $this->formatDate($data['company_zdw_bzjzydsr']['list'][$i]['detail']['basic_date']));
//            //登记到期日
//            $docObj->setValue("bzjzy_endTime#" . ($i + 1), $this->formatDate($data['company_zdw_bzjzydsr']['list'][$i]['detail']['endTime']));
//            //登记日期
//            $docObj->setValue("bzjzy_sortTime#" . ($i + 1), $this->formatDate($data['company_zdw_bzjzydsr']['list'][$i]['detail']['sortTime']));
//        }
//        $docObj->setValue("bzjzy_total", (int)$data['company_zdw_bzjzydsr']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,54,$this->entName,true);
//        $docObj->setValue('bzjzy_oneSaid', $oneSaid);
//
//        //仓单质押
//        $rows = count($data['company_zdw_cdzydsr']['list']);
//        $docObj->cloneRow('cdzy_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("cdzy_no#" . ($i + 1), $i + 1);
//            //仓储物名称或品种
//            $docObj->setValue("cdzy_pledgorFin_type#" . ($i + 1), $data['company_zdw_cdzydsr']['list'][$i]['detail']['pledgorFin_type']);
//            //主合同金额
//            $docObj->setValue("cdzy_pledgorFin_masterConMoney#" . ($i + 1), $data['company_zdw_cdzydsr']['list'][$i]['detail']['pledgorFin_masterConMoney']);
//            //登记期限
//            $docObj->setValue("cdzy_basic_date#" . ($i + 1), $this->formatDate($data['company_zdw_cdzydsr']['list'][$i]['detail']['basic_date']));
//            //登记到期日
//            $docObj->setValue("cdzy_endTime#" . ($i + 1), $this->formatDate($data['company_zdw_cdzydsr']['list'][$i]['detail']['endTime']));
//            //登记日期
//            $docObj->setValue("cdzy_sortTime#" . ($i + 1), $this->formatDate($data['company_zdw_cdzydsr']['list'][$i]['detail']['sortTime']));
//        }
//        $docObj->setValue("cdzy_total", (int)$data['company_zdw_cdzydsr']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,55,$this->entName,true);
//        $docObj->setValue('cdzy_oneSaid', $oneSaid);
//
//        //所有权保留
//        $rows = count($data['company_zdw_syqbldsr']['list']);
//        $docObj->cloneRow('syqbl_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("syqbl_no#" . ($i + 1), $i + 1);
//            //登记种类
//            $docObj->setValue("syqbl_basic_type#" . ($i + 1), $data['company_zdw_syqbldsr']['list'][$i]['detail']['basic_type']);
//            //所有权标的物类型
//            $docObj->setValue("syqbl_syqType#" . ($i + 1), $data['company_zdw_syqbldsr']['list'][$i]['detail']['syqType']);
//            //登记期限
//            $docObj->setValue("syqbl_basic_date#" . ($i + 1), $this->formatDate($data['company_zdw_syqbldsr']['list'][$i]['detail']['basic_date']));
//            //登记到期日
//            $docObj->setValue("syqbl_endTime#" . ($i + 1), $this->formatDate($data['company_zdw_syqbldsr']['list'][$i]['detail']['endTime']));
//            //登记日期
//            $docObj->setValue("syqbl_sortTime#" . ($i + 1), $this->formatDate($data['company_zdw_syqbldsr']['list'][$i]['detail']['sortTime']));
//        }
//        $docObj->setValue("syqbl_total", (int)$data['company_zdw_syqbldsr']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,53,$this->entName,true);
//        $docObj->setValue('syqbl_oneSaid', $oneSaid);
//
//        //其他动产融资
//        $rows = count($data['company_zdw_qtdcdsr']['list']);
//        $docObj->cloneRow('qtdcrz_no', $rows);
//        for ($i = 0; $i < $rows; $i++) {
//            //序号
//            $docObj->setValue("qtdcrz_no#" . ($i + 1), $i + 1);
//            //抵押物类型
//            $docObj->setValue("qtdcrz_basic_typeT#" . ($i + 1), $data['company_zdw_qtdcdsr']['list'][$i]['detail']['basic_typeT']);
//            //主合同金额
//            $docObj->setValue("qtdcrz_bdwMes_conMoney#" . ($i + 1), $data['company_zdw_qtdcdsr']['list'][$i]['detail']['bdwMes_conMoney']);
//            //登记期限
//            $docObj->setValue("qtdcrz_basic_date#" . ($i + 1), $this->formatDate($data['company_zdw_qtdcdsr']['list'][$i]['detail']['basic_date']));
//            //登记到期日
//            $docObj->setValue("qtdcrz_endTime#" . ($i + 1), $this->formatDate($data['company_zdw_qtdcdsr']['list'][$i]['detail']['endTime']));
//            //登记日期
//            $docObj->setValue("qtdcrz_sortTime#" . ($i + 1), $this->formatDate($data['company_zdw_qtdcdsr']['list'][$i]['detail']['sortTime']));
//        }
//        $docObj->setValue("qtdcrz_total", (int)$data['company_zdw_qtdcdsr']['total']);
//
//        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,57,$this->entName,true);
//        $docObj->setValue('qtdcrz_oneSaid', $oneSaid);
    }

    //并发请求数据
    private function cspHandleData()
    {
        //创建csp对象
        $csp = CspService::getInstance()->create();

        //淘数 基本信息 工商信息
        $csp->add('getRegisterInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $this->entName], 'getRegisterInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

            return $res;
        });

        //淘数 基本信息 股东信息
        $csp->add('getShareHolderInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getShareHolderInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 高管信息
        $csp->add('getMainManagerInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getMainManagerInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 基本信息 变更信息
        $csp->add('getRegisterChangeInfo', function () {

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
        $csp->add('GetOpException', function () {

            $postData = ['keyNo' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIException/GetOpException', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //企查查 实际控制人
        $csp->add('Beneficiary', function () {

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
        $csp->add('getHistoricalEvolution', function () {

            $res = XinDongService::getInstance()->getHistoricalEvolution($this->entName);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外投资
        $csp->add('lawPersonInvestmentInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'lawPersonInvestmentInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 法人对外任职
        $csp->add('getLawPersontoOtherInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getLawPersontoOtherInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 企业对外投资
        $csp->add('getInvestmentAbroadInfo', function () {

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
        $csp->add('getBranchInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 10,
            ], 'getBranchInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 银行信息
        $csp->add('GetCreditCodeNew', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICreditCode/GetCreditCodeNew', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 公司概况
        $csp->add('SearchCompanyFinancings', function () {

            $postData = ['searchKey' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'BusinessStateV4/SearchCompanyFinancings', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 招投标
        $csp->add('TenderSearch', function () {

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
        $csp->add('LandPurchaseList', function () {

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
        $csp->add('LandPublishList', function () {

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
        $csp->add('LandTransferList', function () {

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
        $csp->add('Qualification', function () {

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
        $csp->add('BuildingProject', function () {

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
        $csp->add('BondList', function () {

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
        $csp->add('GetCompanyWebSite', function () {

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
        $csp->add('Microblog', function () {

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
        $csp->add('CompanyNews', function () {

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
        $csp->add('itemInfo', function () {

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
                        $res[] = ['year' => $yearArr[$i], 'yoy' => ($SOCNUM_1 - $SOCNUM_2) / $SOCNUM_2 ,'num'=>$SOCNUM_1];
                    } else {
                        $res[] = ['year' => $yearArr[$i], 'yoy' => null,'num'=>$SOCNUM_1];
                    }
                }

            } else {
                $res = null;
            }

            return $res;
        });

        //企查查 建筑企业-专业注册人员
        $csp->add('BuildingRegistrar', function () {

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
        $csp->add('Recruitment', function () {

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
        $csp->add('FinanceData', function () {

            $postData = ['entName' => $this->entName];

            $res = (new QianQiService())->setCheckRespFlag(true)->getThreeYearsData($postData);

            if ($res['code'] === 200 && !empty($res['result'])) {
                $res = (new QianQiService())->toPercent($res['result']);
            } else {
                $res = null;
            }

            if ($res === null) return $res;

            $count1=0;

            foreach ($res as $year => $dataArr) {
                $legend[] = $year;
                array_pop($dataArr);
                $tmp = array_map(function ($val) {
                    return is_numeric($val) ? round((int)$val) : null;//四舍五入
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
        $csp->add('SearchCompanyCompanyProducts', function () {

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
        $csp->add('PatentV4Search', function () {

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
        $csp->add('SearchSoftwareCr', function () {

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
        $csp->add('tmSearch', function () {

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
        $csp->add('SearchCopyRight', function () {

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
        $csp->add('SearchCertification', function () {

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
        $csp->add('satparty_xin', function () {

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
        $csp->add('satparty_xuke', function () {

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
        $csp->add('satparty_reg', function () {

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
        $csp->add('satparty_fzc', function () {

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
        $csp->add('satparty_qs', function () {

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
        $csp->add('satparty_chufa', function () {

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
        $csp->add('GetAdministrativeLicenseList', function () {

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
        $csp->add('GetAdministrativePenaltyList', function () {

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
        $csp->add('epbparty', function () {

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
        $csp->add('epbparty_jkqy', function () {

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
        $csp->add('epbparty_zxjc', function () {

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
        $csp->add('epbparty_huanping', function () {

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
        $csp->add('custom_qy', function () {

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
        $csp->add('custom_xuke', function () {

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
        $csp->add('custom_credit', function () {

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
        $csp->add('custom_punish', function () {

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
        $csp->add('pbcparty', function () {

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
        $csp->add('pbcparty_cbrc', function () {

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

        //法海 一行两会 证监处罚公示
        $csp->add('pbcparty_csrc_chufa', function () {

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
        $csp->add('pbcparty_csrc_xkpf', function () {

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
        $csp->add('safe_chufa', function () {

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
        $csp->add('safe_xuke', function () {

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
        $csp->add('fygg', function () {

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
        $csp->add('ktgg', function () {

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
        $csp->add('cpws', function () {

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
        $csp->add('zxgg', function () {

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
        $csp->add('shixin', function () {

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
        $csp->add('SearchZhiXing', function () {

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
        $csp->add('sifacdk', function () {

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
        $csp->add('getChattelMortgageInfo', function () {

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
        $csp->add('getEquityPledgedInfo', function () {

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
        $csp->add('GetAnnualReport', function () {

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
        $csp->add('GetLandMortgageList', function () {

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
        $csp->add('company_zdw_yszkdsr', function () {

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
        $csp->add('company_zdw_zldjdsr', function () {

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
        $csp->add('company_zdw_bzjzydsr', function () {

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
        $csp->add('company_zdw_cdzydsr', function () {

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
        $csp->add('company_zdw_syqbldsr', function () {

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
        $csp->add('company_zdw_qtdcdsr', function () {

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

        return CspService::getInstance()->exec($csp, 120);
    }


}
