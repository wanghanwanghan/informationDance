<?php

namespace App\Task\TaskList;

use App\Csp\Service\CspService;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\OneSaid\OneSaidService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\Process\Service\ProcessService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use wanghanwanghan\someUtils\control;

class CreateVeryEasyReportTask extends TaskBase implements TaskInterface
{
    private $entName;
    private $reportNum;
    private $phone;
    private $type;

    private $fz = [];
    private $fx = [];
    private $fz_detail = [];
    private $fx_detail = [];

    function __construct($entName, $reportNum,$phone,$type)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;
        $this->phone = $phone;
        $this->type = $type;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $tmp = new TemplateProcessor(REPORT_MODEL_PATH . 'VeryEasyReportModel_1.docx');

        $userInfo = User::create()->where('phone',$this->phone)->get();

        switch ($this->type)
        {
            case 'xd':
                $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'logo.jpg', 'width' => 200, 'height' => 40]);
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

        $reportVal = $this->cspHandleData();

        $this->fillData($tmp, $reportVal);

        $this->exprXDS($reportVal);

        $this->fz_and_fx_detail($tmp, $reportVal);

        $tmp->setValue('fz_score', sprintf('%.2f', array_sum($this->fz)));
        $tmp->setValue('fz_detail', implode(',',$this->fz_detail));

        $tmp->setValue('fx_score', sprintf('%.2f', array_sum($this->fx)));
        $tmp->setValue('fx_detail', implode(',',$this->fx_detail));

        $tmp->saveAs(REPORT_PATH . $this->reportNum . '.docx');

        $info = ReportInfo::create()->where('phone',$this->phone)->where('filename',$this->reportNum)->get();

        $info->update(['status'=>2]);

        $userEmail = User::create()->where('phone',$this->phone)->get();

        CommonService::getInstance()->sendEmail($userEmail->email,[REPORT_PATH . $this->reportNum . '.docx'],'01',['entName'=>$this->entName]);

        ProcessService::getInstance()->sendToProcess('docx2doc',$this->reportNum);

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        try
        {
            $info = ReportInfo::create()->where('phone',$this->phone)->where('filename',$this->reportNum)->get();

            $info->update(['status'=>1,'errInfo'=>$throwable->getMessage()]);

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
        //基本信息
        //企业类型
        $docObj->setValue('ENTTYPE', $data['getRegisterInfo']['ENTTYPE']);
        //注册资本
        $docObj->setValue('REGCAP', $data['getRegisterInfo']['REGCAP']);
        //注册地址
        $docObj->setValue('DOM', $data['getRegisterInfo']['DOM']);
        //法人
        $docObj->setValue('FRDB', $data['getRegisterInfo']['FRDB']);
        //统一代码
        $docObj->setValue('SHXYDM', $data['getRegisterInfo']['SHXYDM']);
        //成立日期
        $docObj->setValue('ESDATE', $this->formatDate($data['getRegisterInfo']['ESDATE']));
        //核准日期
        $docObj->setValue('APPRDATE', $this->formatDate($data['getRegisterInfo']['APPRDATE']));
        //经营状态
        $docObj->setValue('ENTSTATUS', $data['getRegisterInfo']['ENTSTATUS']);
        //营业期限
        $docObj->setValue('OPFROM', $this->formatDate($data['getRegisterInfo']['OPFROM']));
        $docObj->setValue('APPRDATE', $this->formatDate($data['getRegisterInfo']['APPRDATE']));
        //所属行业
        $docObj->setValue('INDUSTRY', $data['getRegisterInfo']['INDUSTRY']);
        //经营范围
        $docObj->setValue('OPSCOPE', $data['getRegisterInfo']['OPSCOPE']);

        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,14,$this->entName,true);
        $docObj->setValue('jbxx_oneSaid', $oneSaid);

        //经营异常
        $rows = count($data['GetOpException']['list']);
        $docObj->cloneRow('jjyc_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("jjyc_no#" . ($i + 1), $i + 1);
            //列入一日
            $docObj->setValue("jjyc_AddDate#" . ($i + 1), $this->formatDate($data['GetOpException']['list'][$i]['AddDate']));
            //列入原因
            $docObj->setValue("jjyc_AddReason#" . ($i + 1), $data['GetOpException']['list'][$i]['AddReason']);
            //移除日期
            $docObj->setValue("jjyc_RemoveDate#" . ($i + 1), $this->formatDate($data['GetOpException']['list'][$i]['RemoveDate']));
            //移除原因
            $docObj->setValue("jjyc_RomoveReason#" . ($i + 1), $data['GetOpException']['list'][$i]['RomoveReason']);
        }

        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,21,$this->entName,true);
        $docObj->setValue('jyycxx_oneSaid', $oneSaid);

        //企业对外投资
        $rows = count($data['getInvestmentAbroadInfo']['list']);
        $docObj->cloneRow('qydwtz_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("qydwtz_no#" . ($i + 1), $i + 1);
            //被投资企业名称
            $docObj->setValue("qydwtz_ENTNAME#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['ENTNAME']);
            //成立日期
            $docObj->setValue("qydwtz_ESDATE#" . ($i + 1), $this->formatDate($data['getInvestmentAbroadInfo']['list'][$i]['ESDATE']));
            //经营状态
            $docObj->setValue("qydwtz_ENTSTATUS#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['ENTSTATUS']);
            //注册资本
            $docObj->setValue("qydwtz_REGCAP#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['REGCAP']);
            //认缴出资额
            $docObj->setValue("qydwtz_SUBCONAM#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['SUBCONAM']);
            //出资币种
            $docObj->setValue("qydwtz_CONCUR#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['CONCUR']);
            //出资比例
            $docObj->setValue("qydwtz_CONRATIO#" . ($i + 1), $this->formatPercent($data['getInvestmentAbroadInfo']['list'][$i]['CONRATIO']));
            //出资时间
            $docObj->setValue("qydwtz_CONDATE#" . ($i + 1), $this->formatDate($data['getInvestmentAbroadInfo']['list'][$i]['CONDATE']));
        }

        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,23,$this->entName,true);
        $docObj->setValue('qydwtz_oneSaid', $oneSaid);

        //财务总揽
        if (empty($data['FinanceData']['pic']))
        {
            $docObj->setValue("caiwu_pic", '无财务数据或企业类型错误');

        }else
        {
            $docObj->setImageValue("caiwu_pic", [
                'path' => REPORT_IMAGE_TEMP_PATH . $data['FinanceData']['pic'],
                'width' => 440,
                'height' => 500
            ]);
        }

        $caiwu_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,0,$this->entName,true);
        $docObj->setValue("caiwu_oneSaid", $caiwu_oneSaid);

        //专利
        $rows = count($data['PatentV4Search']['list']);
        $docObj->cloneRow('zl_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("zl_no#" . ($i + 1), $i + 1);
            //名称
            $docObj->setValue("zl_Title#" . ($i + 1), $data['PatentV4Search']['list'][$i]['Title']);
            //专利类型
            $docObj->setValue("zl_IPCDesc#" . ($i + 1), implode(',', $data['PatentV4Search']['list'][$i]['IPCDesc']));
            //公开号
            $docObj->setValue("zl_PublicationNumber#" . ($i + 1), $data['PatentV4Search']['list'][$i]['PublicationNumber']);
            //法律状态
            $docObj->setValue("zl_LegalStatusDesc#" . ($i + 1), $data['PatentV4Search']['list'][$i]['LegalStatusDesc']);
            //申请日期
            $docObj->setValue("zl_ApplicationDate#" . ($i + 1), $this->formatDate($data['PatentV4Search']['list'][$i]['ApplicationDate']));
            //发布日期
            $docObj->setValue("zl_PublicationDate#" . ($i + 1), $this->formatDate($data['PatentV4Search']['list'][$i]['PublicationDate']));
        }
        $docObj->setValue("zl_total", (int)$data['PatentV4Search']['total']);

        //软件著作权
        $rows = count($data['SearchSoftwareCr']['list']);
        $docObj->cloneRow('rjzzq_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("rjzzq_no#" . ($i + 1), $i + 1);
            //软件名称
            $docObj->setValue("rjzzq_Name#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['Name']);
            //登记号
            $docObj->setValue("rjzzq_RegisterNo#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['RegisterNo']);
            //登记批准日期
            $docObj->setValue("rjzzq_RegisterAperDate#" . ($i + 1), $this->formatDate($data['SearchSoftwareCr']['list'][$i]['RegisterAperDate']));
            //版本号
            $docObj->setValue("rjzzq_VersionNo#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['VersionNo']);
        }
        $docObj->setValue("rjzzq_total", (int)$data['SearchSoftwareCr']['total']);

        //行政许可
        $rows = count($data['GetAdministrativeLicenseList']['list']);
        $docObj->cloneRow('xzxk_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("xzxk_no#" . ($i + 1), $i + 1);
            //许可编号
            $docObj->setValue("xzxk_CaseNo#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['CaseNo']);
            //有效期自
            $docObj->setValue("xzxk_LianDate#" . ($i + 1), $this->formatDate($data['GetAdministrativeLicenseList']['list'][$i]['detail']['LianDate']));
            //有效期止
            $docObj->setValue("xzxk_ExpireDate#" . ($i + 1), $this->formatDate($data['GetAdministrativeLicenseList']['list'][$i]['detail']['ExpireDate']));
            //许可内容
            $docObj->setValue("xzxk_Content#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['Content']);
            //许可机关
            $docObj->setValue("xzxk_Province#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['Province']);
        }
        $docObj->setValue("xzxk_total", (int)$data['GetAdministrativeLicenseList']['total']);

        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,32,$this->entName,true);
        $docObj->setValue('xzxk_oneSaid', $oneSaid);

        //行政处罚
        $rows = count($data['GetAdministrativePenaltyList']['list']);
        $docObj->cloneRow('xzcf_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("xzcf_no#" . ($i + 1), $i + 1);
            //文书号
            $docObj->setValue("xzcf_CaseNo#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['CaseNo']);
            //决定日期
            $docObj->setValue("xzcf_LianDate#" . ($i + 1), $this->formatDate($data['GetAdministrativePenaltyList']['list'][$i]['LianDate']));
            //内容
            $docObj->setValue("xzcf_Content#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['detail']['Content']);
            //决定机关
            $docObj->setValue("xzcf_ExecuteGov#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['detail']['ExecuteGov']);
        }
        $docObj->setValue("xzcf_total", (int)$data['GetAdministrativePenaltyList']['total']);

        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,33,$this->entName,true);
        $docObj->setValue('xzcf_oneSaid', $oneSaid);

        //裁判文书
        $rows = count($data['cpws']['list']);
        $docObj->cloneRow('cpws_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("cpws_no#" . ($i + 1), $i + 1);
            //案号
            $docObj->setValue("cpws_caseNo#" . ($i + 1), $data['cpws']['list'][$i]['detail']['caseNo']);
            //法院名称
            $docObj->setValue("cpws_court#" . ($i + 1), $data['cpws']['list'][$i]['detail']['court']);
            //审结时间
            $docObj->setValue("cpws_sortTimeString#" . ($i + 1), $this->formatDate($data['cpws']['list'][$i]['sortTimeString']));
            //审理状态
            $docObj->setValue("cpws_trialProcedure#" . ($i + 1), $data['cpws']['list'][$i]['detail']['trialProcedure']);

            $content = '';
            foreach ($data['cpws']['list'][$i]['detail']['partys'] as $no => $arr) {
                $content .= '<w:br/>';
                $content .= ($no + 1) . ':';
                $content .= $arr['caseCauseT'] . ' - ';
                $content .= $arr['pname'] . ' - ';
                $content .= $arr['partyTitleT'] . ' - ';
                switch ($arr['partyPositionT']) {
                    case 'p':
                        $content .= '原告';
                        break;
                    case 'd':
                        $content .= '被告';
                        break;
                    case 't':
                        $content .= '第三人';
                        break;
                    case 'u':
                        $content .= '当事人';
                        break;
                    default:
                        $content .= '';
                        break;
                }
                $content .= '<w:br/>';
            }
            //法院类型-案由-当事人-称号-诉讼地位(原审)
            $docObj->setValue("cpws_content#" . ($i + 1), $content);
        }
        $docObj->setValue("cpws_total", (int)$data['cpws']['total']);

        //oneSaid
        $cpws_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,2,$this->entName,true);
        $docObj->setValue("cpws_oneSaid", $cpws_oneSaid);

        //执行公告
        $rows = count($data['zxgg']['list']);
        $docObj->cloneRow('zxgg_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("zxgg_no#" . ($i + 1), $i + 1);
            //案号
            $docObj->setValue("zxgg_caseNo#" . ($i + 1), $data['zxgg']['list'][$i]['detail']['caseNo']);
            //法院名称
            $docObj->setValue("zxgg_court#" . ($i + 1), $data['zxgg']['list'][$i]['detail']['court']);
            //立案日期
            $docObj->setValue("zxgg_sortTimeString#" . ($i + 1), $this->formatDate($data['zxgg']['list'][$i]['sortTimeString']));

            $content = '';
            foreach ($data['zxgg']['list'][$i]['detail']['partys'] as $no => $arr) {
                $content .= '<w:br/>';
                $content .= ($no + 1) . ':';
                $content .= $arr['caseStateT'] . ' - ';
                $content .= $arr['execMoney'] . ' - ';
                $content .= $arr['pname'];
                $content .= '<w:br/>';
            }
            //案件状态-执行金额-当事人
            $docObj->setValue("zxgg_content#" . ($i + 1), $content);
        }
        $docObj->setValue("zxgg_total", (int)$data['zxgg']['total']);

        //oneSaid
        $zxgg_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,4,$this->entName,true);
        $docObj->setValue("zxgg_oneSaid", $zxgg_oneSaid);

        //失信公告
        $rows = count($data['shixin']['list']);
        $docObj->cloneRow('sx_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("sx_no#" . ($i + 1), $i + 1);
            //案号
            $docObj->setValue("sx_caseNo#" . ($i + 1), $data['shixin']['list'][$i]['detail']['caseNo']);
            //法院名称
            $docObj->setValue("sx_court#" . ($i + 1), $data['shixin']['list'][$i]['detail']['court']);
            //立案日期
            $docObj->setValue("sx_sortTimeString#" . ($i + 1), $this->formatDate($data['shixin']['list'][$i]['sortTimeString']));

            $content = '';
            foreach ($data['shixin']['list'][$i]['detail']['partys'] as $no => $arr) {
                $content .= '<w:br/>';
                $content .= ($no + 1) . ':';
                $content .= $arr['lxqkT'] . ' - ';
                $content .= $arr['jtqx'] . ' - ';
                $content .= $arr['money'] . ' - ';
                $content .= $arr['pname'];
                $content .= '<w:br/>';
            }
            //履行情况-具体情形-涉案金额-当事人
            $docObj->setValue("sx_content#" . ($i + 1), $content);
        }
        $docObj->setValue("sx_total", (int)$data['shixin']['total']);

        //oneSaid
        $sx_oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,5,$this->entName,true);
        $docObj->setValue("sx_oneSaid", $sx_oneSaid);
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

        //企查查 经营异常
        $csp->add('GetOpException', function () {

            $postData = ['keyNo' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIException/GetOpException', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
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
                    return is_numeric($val) ? round((int)$val) : null;
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

        return CspService::getInstance()->exec($csp, 10);
    }
}
