<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;

use App\Csp\Service\CspService;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Ocr\OcrService;
use App\HttpController\Service\OneSaid\OneSaidService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\Process\Service\ProcessService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use wanghanwanghan\someUtils\control;
use function Sodium\add;
class CompanyRiskService extends ServiceBase
{
    use Singleton;

    public $ldUrl;
    public $fyyList;
    public $fyyDetail;

    private $entName;
    private $reportNum;
    private $phone;
    private $type;

    private $fz = [];
    private $fx = [];
    private $fz_detail = [];
    private $fx_detail = [];

    function __construct($entName)
    {
        $this->entName = $entName;
        $this->ldUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');
        $this->fyyList = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $this->fyyDetail = CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl');

        return parent::__construct();
    }

    function run()
    {
        $reportVal = $this->cspHandleData();
        $this->exprXDS($reportVal);

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        try {
            $info = ReportInfo::create()->where('phone', $this->phone)->where('filename', $this->reportNum)->get();

            $info->update(['status' => 1, 'errInfo' => $throwable->getMessage()]);

        } catch (\Throwable $e) {

        }
    }

    function setFzDetail($fz_detail){
        $this->fx_detail = $fz_detail;
    }

    function getFzDetail(){
        return $this->fx_detail;
    }

    /**
    {
    "企业性质": 50,
    "企业对外投资": 60,
    "融资历史": 50,
    "行政许可": 60,
    "专利": 70,
    "软件著作权": 80,
    "近三年团队人数": 50,
    "近两年团队人数": 40,
    "招投标": 60,
    "财务资产": [
    80,
    97
    ],
    "行业位置": 0,
    "企业变更信息": 0,
    "经营异常": 0,
    "裁判文书": 0,
    "执行公告": 0,
    "涉税处罚公示": 0,
    "税务非正常户公示": 0,
    "欠税公告": 0,
    "行政处罚": 0,
    "联合惩戒名单信息": 0
    }
     */
    static function getDatas($companys){
        $return = [];
        foreach ($companys as $company){
            $model = new CompanyRiskService(
                $company
            );
            $model->run();
            $tmpRes = $model->getFzDetail();
            $return[] = $tmpRes;
        }
        return $return;
    }

    //计算信动分
     function exprXDS($data)
    {

        $res = [];
        //企业性质
        $res['企业性质'] = $this->qyxz($data['getRegisterInfo']);

        //企业对外投资
        $res['企业对外投资'] = $this->qydwtz($data['getInvestmentAbroadInfo']['total']);

        //融资历史
        $res['融资历史'] = $this->rzls($data['SearchCompanyFinancings']);


        //==============================================================================================================
        //行政许可
        $res['行政许可'] = $this->xzxk($data['GetAdministrativeLicenseList']['total']);

        //==============================================================================================================
        //专利
        $res['专利'] = $this->zl($data['PatentV4Search']['total']);
        //软件著作权
        $res['软件著作权'] = $this->rjzzq($data['SearchSoftwareCr']['total']);

        //==============================================================================================================
        //近三年团队人数
        $res['近三年团队人数'] = $this->tdrs($data['itemInfo'], 'fz');
        //近两年团队人数
        $res['近两年团队人数'] = $this->rybh($data['itemInfo'], 'fz');

        //==============================================================================================================
        //招投标
        $res['招投标'] = $this->ztb($data['TenderSearch']['total']);

        //==============================================================================================================
        //财务资产
        $res['财务资产'] = $this->cwzc($data['FinanceData']['data'], 'fz');

        //==============================================================================================================
        //行业位置
        $res['行业位置']  = $this->hywz($data['FinanceData']['data'], $data['getRegisterInfo']);

        //==============================================================================================================
        //企业变更信息
        $res['企业变更信息'] = $this->qybgxx($data['getRegisterChangeInfo']['total']);
        //经营异常
        $res['经营异常']  = $this->jyyc($data['GetOpException']['total']);

        //==============================================================================================================
        //财务资产
        $res['财务资产'] = $this->cwzc($data['FinanceData']['data'], 'fx');

        //==============================================================================================================
        //近三年团队人数
        $res['近三年团队人数'] = $this->tdrs($data['itemInfo'], 'fx');
        //近两年团队人数
        $res['近两年团队人数'] = $this->rybh($data['itemInfo'], 'fx');

        //==============================================================================================================
        //裁判文书
        $res['裁判文书']  = $this->pjws($data['cpws']['total']);
        //执行公告
        $res['执行公告'] = $this->zxgg($data['zxgg']['total']);

        //==============================================================================================================
        //涉税处罚公示
        $res['涉税处罚公示'] = $this->sscfgs($data['satparty_chufa']['total']);
        //税务非正常户公示
        $res['税务非正常户公示'] = $this->swfzchgs($data['satparty_fzc']['total']);
        //欠税公告
        $res['欠税公告'] = $this->qsgg($data['satparty_qs']['total']);

        //==============================================================================================================
        //行政处罚
        $res['行政处罚'] = $this->xzcf($data['GetAdministrativePenaltyList']['total']);

        //==============================================================================================================
        //联合惩戒名单信息（暂无该字段接口，先以司法类中的失信公告代替）失信公告的数量
        $res['联合惩戒名单信息'] = $this->sxgg($data['shixin']);

        //==============================================================================================================
        $this->setFzDetail($res);
        return $res;
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
        $vendInc = $cw[0][2];

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
    private function cwzc($data, $type): array
    {
        if (!is_array($data)) return [0, 0];

        if (empty($data)) return [0, 0];

        $data = array_values($data);

        if (!isset($data[0])) return [0, 0];

        if ($type === 'fz') {
            if (is_numeric($data[0]['NETINC']) && is_numeric($data[0]['A_ASSGROL'])) {
                $data[0]['A_ASSGROL'] == 0 ? $now = false : $now = round($data[0]['NETINC'] / $data[0]['A_ASSGROL'], 6);
            } else {
                $now = false;
            }
            if (is_numeric($data[1]['NETINC']) && is_numeric($data[1]['A_ASSGROL'])) {
                $data[1]['A_ASSGROL'] == 0 ? $last = false : $last = round($data[1]['NETINC'] / $data[1]['A_ASSGROL'], 6);
            } else {
                $last = false;
            }
            if ($now === false || $last === false) {
                $score = 4;
            } else {
                $val = round((($now - $last) / abs($last)) * 100);
                if ($val <= -10) {
                    $score = 4;
                } elseif ($val >= -10 && $val <= -6) {
                    $score = 8;
                } elseif ($val >= -5 && $val <= -1) {
                    $score = 11;
                } elseif ($val >= -1 && $val <= 0) {
                    $score = 16;
                } elseif ($val >= 0 && $val <= 1.2) {
                    $score = 26;
                } elseif ($val >= 1.21 && $val <= 2.2) {
                    $score = 31;
                } elseif ($val >= 2.21 && $val <= 3.3) {
                    $score = 35;
                } elseif ($val >= 3.31 && $val <= 5.5) {
                    $score = 42;
                } elseif ($val >= 5.51 && $val <= 8.3) {
                    $score = 56;
                } elseif ($val >= 8.31 && $val <= 10.5) {
                    $score = 72;
                } elseif ($val >= 10.51 && $val <= 20) {
                    $score = 85;
                } elseif ($val >= 20.1 && $val <= 30) {
                    $score = 92;
                } elseif ($val >= 30.1 && $val <= 50) {
                    $score = 93;
                } elseif ($val >= 50.1 && $val <= 100) {
                    $score = 94.5;
                } elseif ($val >= 100.1 && $val <= 300) {
                    $score = 97.5;
                } elseif ($val >= 300) {
                    $score = 99;
                } else {
                    $score = 4;
                }
            }
        } else {
            if (is_numeric($data[0]['PROGRO_yoy'])) {
                $val = round($data[0]['PROGRO_yoy'] * 100);
                if ($val <= -50) {
                    $score = 97;
                } elseif ($val >= -50 && $val <= -21) {
                    $score = 94;
                } elseif ($val >= -20 && $val <= -11) {
                    $score = 92;
                } elseif ($val >= -10 && $val <= -6) {
                    $score = 85;
                } elseif ($val >= -5 && $val <= 0) {
                    $score = 72;
                } elseif ($val >= 0 && $val <= 5) {
                    $score = 56;
                } elseif ($val >= 6 && $val <= 10) {
                    $score = 42;
                } elseif ($val >= 11 && $val <= 25) {
                    $score = 35;
                } elseif ($val >= 26 && $val <= 30) {
                    $score = 31;
                } elseif ($val >= 31 && $val <= 50) {
                    $score = 26;
                } elseif ($val >= 51 && $val <= 70) {
                    $score = 21;
                } elseif ($val >= 71 && $val <= 100) {
                    $score = 16;
                } elseif ($val >= 101 && $val <= 200) {
                    $score = 11;
                } elseif ($val >= 201 && $val <= 500) {
                    $score = 8;
                } elseif ($val >= 500) {
                    $score = 4;
                } else {
                    $score = 97;
                }
            } else {
                $score = 97;
            }
        }

        switch ($type) {
            case 'fz':
                //营业收入
                $vendInc = $data[0]['VENDINC'];
                if ($vendInc > 20) $vendIncNum = 110;
                if ($vendInc > 10 && $vendInc <= 20) $vendIncNum = 100;
                if ($vendInc > 5 && $vendInc <= 10) $vendIncNum = 90;
                if ($vendInc >= 0 && $vendInc <= 5) $vendIncNum = 80;
                if ($vendInc >= -10 && $vendInc <= -1) $vendIncNum = 70;
                if ($vendInc >= -20 && $vendInc <= -11) $vendIncNum = 60;
                if ($vendInc <= -21) $vendIncNum = 50;
                //净利润
                $netInc = $data[0]['NETINC'];
                if ($netInc > 20) $netIncNum = 110;
                if ($netInc > 10 && $netInc <= 20) $netIncNum = 100;
                if ($netInc > 5 && $netInc <= 10) $netIncNum = 90;
                if ($netInc >= 0 && $netInc <= 5) $netIncNum = 80;
                if ($netInc >= -10 && $netInc <= -1) $netIncNum = 70;
                if ($netInc >= -20 && $netInc <= -11) $netIncNum = 60;
                if ($netInc <= -21) $netIncNum = 50;
                //资产总额
                $assGro = $data[0]['ASSGRO'];
                if ($assGro > 20) $assGroNum = 110;
                if ($assGro > 10 && $assGro <= 20) $assGroNum = 100;
                if ($assGro > 5 && $assGro <= 10) $assGroNum = 90;
                if ($assGro >= 0 && $assGro <= 5) $assGroNum = 80;
                if ($assGro >= -10 && $assGro <= -1) $assGroNum = 70;
                if ($assGro >= -20 && $assGro <= -11) $assGroNum = 60;
                if ($assGro <= -21) $assGroNum = 50;
                return [($vendIncNum + $netIncNum + $assGroNum) / 3, $score];
            case 'fx':
                //负债总额/资产总额=资产负债率
                if (count($data) < 2) return [0, 0];
                //今年负债总额
                $liaGro1 = $data[0]['LIAGRO'];
                //今年资产总额
                $assGro1 = $data[0]['ASSGRO'];
                //今年资产负债率
                if ($assGro1 == 0) {
                    $fuzhailv1 = 0;
                } else {
                    $fuzhailv1 = ($liaGro1 / $assGro1) * 100;
                }
                //去年负债总额
                $liaGro2 = $data[1]['LIAGRO'];
                //去年资产总额
                $assGro2 = $data[1]['ASSGRO'];
                //今年资产负债率
                if ($assGro2 == 0) {
                    $fuzhailv2 = 0;
                } else {
                    $fuzhailv2 = ($liaGro2 / $assGro2) * 100;
                }
                $num = (abs($fuzhailv1) + abs($fuzhailv2)) / 2;
                if ($num > 80) return [100, $score];
                if ($num > 50 && $num <= 80) return [90, $score];
                if ($num > 30 && $num <= 50) return [80, $score];
                if ($num > 10 && $num <= 30) return [70, $score];
                if ($num > 0 && $num <= 10) return [60, $score];
                break;
        }

        return [0, 0];
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

        //龙盾 基本信息 工商信息
        $csp->add('GetBasicDetailsByName', function () {

            $postData = ['keyWord' => $this->entName];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'ECIV4/GetBasicDetailsByName', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //龙盾 经营异常
        $csp->add('GetOpException', function () {

            $postData = ['keyNo' => $this->entName];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'ECIException/GetOpException', $postData);

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

        //龙信 财务
        $csp->add('FinanceData', function () {

            $postData = [
                'entName' => $this->entName,
                'code' => '',
                'beginYear' => date('Y') - 1,
                'dataCount' => 4,//取最近几年的
            ];

            $res = (new LongXinService())->setCheckRespFlag(true)->getFinanceData($postData, false);

            if ($res['code'] !== 200) return '';

            ksort($res['result']);

            if (!empty($res['result'])) {
                $tmp = $legend = [];
                foreach ($res['result'] as $year => $val) {
                    $legend[] = $year;
                    $tmp[] = [
                        sRound($val['ASSGRO_yoy'] * 100),
                        sRound($val['LIAGRO_yoy'] * 100),
                        sRound($val['VENDINC_yoy'] * 100),
                        sRound($val['MAIBUSINC_yoy'] * 100),
                        sRound($val['PROGRO_yoy'] * 100),
                        sRound($val['NETINC_yoy'] * 100),
                        sRound($val['RATGRO_yoy'] * 100),
                        sRound($val['TOTEQU_yoy'] * 100),
                    ];
                }
                $res['data'] = $res['result'];
                $res['result'] = $tmp;
            }

            $labels = ['资产总额', '负债总额', '营业总收入', '主营业务收入', '利润总额', '净利润', '纳税总额', '所有者权益'];

            $extension = [
                'width' => 1200,
                'height' => 700,
                'title' => $this->entName . ' - 同比',
                'xTitle' => '此图为概况信息',
                //'yTitle'=>$this->entName,
                'titleSize' => 14,
                'legend' => $legend
            ];

            $tmp = [];
            $tmp['pic'] = CommonService::getInstance()->createBarPic($res['result'], $labels, $extension);
            $tmp['data'] = $res['data'];

            return $tmp;
        });

        //龙盾 专利
        $csp->add('PatentV4Search', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'PatentV4/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //龙盾 软件著作权
        $csp->add('SearchSoftwareCr', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'CopyRight/SearchSoftwareCr', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            $tmp['list'] = $res;
            $tmp['total'] = $total;

            return $tmp;
        });

        //龙盾 行政许可
        $csp->add('GetAdministrativeLicenseList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = ['id' => $one['Id']];

                    $detail = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'ADSTLicense/GetAdministrativeLicenseDetail', $postData);

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

        //龙盾 行政处罚
        $csp->add('GetAdministrativePenaltyList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = ['id' => $one['Id']];

                    $detail = (new LongDunService())->setCheckRespFlag(true)->get($this->ldUrl . 'AdministrativePenalty/GetAdministrativePenaltyDetail', $postData);

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

        //法研院 裁判文书
        $csp->add('cpws', function () {

            $doc_type = 'cpws';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaYanYuanService())->setCheckRespFlag(true)->getDetail($this->fyyDetail . $doc_type, $postData);

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

        //法研院 执行公告
        $csp->add('zxgg', function () {

            $doc_type = 'zxgg';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaYanYuanService())->setCheckRespFlag(true)->getDetail($this->fyyDetail . $doc_type, $postData);

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

        //法研院 失信公告
        $csp->add('shixin', function () {

            $doc_type = 'shixin';

            $postData = [
                'doc_type' => $doc_type,
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaYanYuanService())->setCheckRespFlag(true)->getList($this->fyyList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

            if (!empty($res)) {
                foreach ($res as &$one) {
                    //取详情
                    $postData = [
                        'id' => $one['entryId'],
                        'doc_type' => $doc_type
                    ];

                    $detail = (new FaYanYuanService())->setCheckRespFlag(true)->getDetail($this->fyyDetail . $doc_type, $postData);

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

        //二次特征
        $csp->add('features', function () {
            $res = (new XinDongService())->setCheckRespFlag(true)->getFeatures($this->entName);
            if ($res['code'] === 200 && !empty($res['result'])) {
                return $res['result'];
            } else {
                return [];
            }
        });

        return CspService::getInstance()->exec($csp, 10);
    }
}
