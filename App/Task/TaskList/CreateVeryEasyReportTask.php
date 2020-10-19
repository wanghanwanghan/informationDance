<?php

namespace App\Task\TaskList;

use App\Csp\Service\CspService;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\OneSaid\OneSaidService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
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

    function __construct($entName, $reportNum,$phone)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;
        $this->phone = $phone;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $tmp = new TemplateProcessor(REPORT_MODEL_PATH . 'VeryEasyReportModel_1.docx');

        $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'logo.jpg', 'width' => 200, 'height' => 40]);

        $tmp->setValue('entName', $this->entName);

        $tmp->setValue('reportNum', $this->reportNum);

        $tmp->setValue('time', Carbon::now()->format('Y年m月d日'));

        $reportVal = $this->cspHandleData();

        $this->fillData($tmp, $reportVal);

        $tmp->saveAs(REPORT_PATH . $this->reportNum . '.docx');

        $info = ReportInfo::create()->where('phone',$this->phone)->where('filename',$this->reportNum)->get();

        $info->update(['status'=>2]);

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
        $docObj->setValue('ESDATE', $data['getRegisterInfo']['ESDATE']);
        //核准日期
        $docObj->setValue('APPRDATE', $data['getRegisterInfo']['APPRDATE']);
        //经营状态
        $docObj->setValue('ENTSTATUS', $data['getRegisterInfo']['ENTSTATUS']);
        //营业期限
        $docObj->setValue('OPFROM', $data['getRegisterInfo']['OPFROM']);
        $docObj->setValue('APPRDATE', $data['getRegisterInfo']['APPRDATE']);
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
            $docObj->setValue("jjyc_AddDate#" . ($i + 1), $data['GetOpException']['list'][$i]['AddDate']);
            //列入原因
            $docObj->setValue("jjyc_AddReason#" . ($i + 1), $data['GetOpException']['list'][$i]['AddReason']);
            //移除日期
            $docObj->setValue("jjyc_RemoveDate#" . ($i + 1), $data['GetOpException']['list'][$i]['RemoveDate']);
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
            $docObj->setValue("qydwtz_ESDATE#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['ESDATE']);
            //经营状态
            $docObj->setValue("qydwtz_ENTSTATUS#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['ENTSTATUS']);
            //注册资本
            $docObj->setValue("qydwtz_REGCAP#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['REGCAP']);
            //认缴出资额
            $docObj->setValue("qydwtz_SUBCONAM#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['SUBCONAM']);
            //出资币种
            $docObj->setValue("qydwtz_CONCUR#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['CONCUR']);
            //出资比例
            $docObj->setValue("qydwtz_CONRATIO#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['CONRATIO']);
            //出资时间
            $docObj->setValue("qydwtz_CONDATE#" . ($i + 1), $data['getInvestmentAbroadInfo']['list'][$i]['CONDATE']);
        }

        $oneSaid = OneSaidService::getInstance()->getOneSaid($this->phone,23,$this->entName,true);
        $docObj->setValue('qydwtz_oneSaid', $oneSaid);

        //财务总揽
        $docObj->setImageValue("caiwu_pic", [
            'path' => REPORT_IMAGE_TEMP_PATH . $data['FinanceData']['pic'],
            'width' => 440,
            'height' => 500
        ]);

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
            $docObj->setValue("zl_ApplicationDate#" . ($i + 1), $data['PatentV4Search']['list'][$i]['ApplicationDate']);
            //发布日期
            $docObj->setValue("zl_PublicationDate#" . ($i + 1), $data['PatentV4Search']['list'][$i]['PublicationDate']);
        }
        $docObj->setValue("zl_total", $data['PatentV4Search']['total']);

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
            $docObj->setValue("rjzzq_RegisterAperDate#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['RegisterAperDate']);
            //版本号
            $docObj->setValue("rjzzq_VersionNo#" . ($i + 1), $data['SearchSoftwareCr']['list'][$i]['VersionNo']);
        }
        $docObj->setValue("rjzzq_total", $data['SearchSoftwareCr']['total']);

        //行政许可
        $rows = count($data['GetAdministrativeLicenseList']['list']);
        $docObj->cloneRow('xzxk_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("xzxk_no#" . ($i + 1), $i + 1);
            //许可编号
            $docObj->setValue("xzxk_CaseNo#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['CaseNo']);
            //有效期自
            $docObj->setValue("xzxk_LianDate#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['LianDate']);
            //有效期止
            $docObj->setValue("xzxk_ExpireDate#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['ExpireDate']);
            //许可内容
            $docObj->setValue("xzxk_Content#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['Content']);
            //许可机关
            $docObj->setValue("xzxk_Province#" . ($i + 1), $data['GetAdministrativeLicenseList']['list'][$i]['detail']['Province']);
        }
        $docObj->setValue("xzxk_total", $data['GetAdministrativeLicenseList']['total']);

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
            $docObj->setValue("xzcf_LianDate#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['LianDate']);
            //内容
            $docObj->setValue("xzcf_Content#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['detail']['Content']);
            //决定机关
            $docObj->setValue("xzcf_ExecuteGov#" . ($i + 1), $data['GetAdministrativePenaltyList']['list'][$i]['detail']['ExecuteGov']);
        }
        $docObj->setValue("xzcf_total", $data['GetAdministrativePenaltyList']['total']);

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
            $docObj->setValue("cpws_sortTimeString#" . ($i + 1), $data['cpws']['list'][$i]['sortTimeString']);
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
        $docObj->setValue("cpws_total", $data['cpws']['total']);

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
            $docObj->setValue("zxgg_sortTimeString#" . ($i + 1), $data['zxgg']['list'][$i]['sortTimeString']);

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
        $docObj->setValue("zxgg_total", $data['zxgg']['total']);

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
            $docObj->setValue("sx_sortTimeString#" . ($i + 1), $data['shixin']['list'][$i]['sortTimeString']);

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
        $docObj->setValue("sx_total", $data['shixin']['total']);

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

            foreach ($res as $year => $dataArr) {
                $legend[] = $year;
                array_pop($dataArr);
                $tmp = array_map(function ($val) {
                    return (int)$val;
                }, array_values($dataArr));
                $data[] = $tmp;
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
