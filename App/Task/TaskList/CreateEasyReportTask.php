<?php

namespace App\Task\TaskList;

use App\Csp\Service\CspService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaHai\FaHaiService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;
use PhpOffice\PhpWord\TemplateProcessor;

class CreateEasyReportTask extends TaskBase implements TaskInterface
{
    private $entName;
    private $reportNum;

    function __construct($entName, $reportNum)
    {
        $this->entName = $entName;
        $this->reportNum = $reportNum;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $tmp = new TemplateProcessor(REPORT_MODEL_PATH . 'EasyReportModel_1.docx');

        $tmp->setImageValue('Logo', ['path' => REPORT_IMAGE_PATH . 'logo.jpg', 'width' => 200, 'height' => 40]);

        $tmp->setValue('entName', $this->entName);

        $tmp->setValue('reportNum', $this->reportNum);

        $tmp->setValue('time', Carbon::now()->format('Y年m月d日'));

        $reportVal = $this->cspHandleData();

        $this->fillData($tmp, $reportVal);

        $tmp->saveAs(REPORT_PATH . $this->reportNum . '.docx');

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

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


        //股东信息
        $rows = count($data['getShareHolderInfo']);
        $docObj->cloneRow('gd_INV', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //股东名称
            $docObj->setValue("gd_INV#" . ($i + 1), $data['getShareHolderInfo'][$i]['INV']);
            //统一代码
            $docObj->setValue("gd_SHXYDM#" . ($i + 1), $data['getShareHolderInfo'][$i]['SHXYDM']);
            //股东类型
            $docObj->setValue("gd_INVTYPE#" . ($i + 1), $data['getShareHolderInfo'][$i]['INVTYPE']);
            //认缴出资额
            $docObj->setValue("gd_SUBCONAM#" . ($i + 1), $data['getShareHolderInfo'][$i]['SUBCONAM']);
            //出资币种
            $docObj->setValue("gd_CONCUR#" . ($i + 1), $data['getShareHolderInfo'][$i]['CONCUR']);
            //出资比例
            $docObj->setValue("gd_CONRATIO#" . ($i + 1), $data['getShareHolderInfo'][$i]['CONRATIO']);
            //出资时间
            $docObj->setValue("gd_CONDATE#" . ($i + 1), $data['getShareHolderInfo'][$i]['CONDATE']);
        }

        //高管信息
        $rows = count($data['getMainManagerInfo']);
        $docObj->cloneRow('gg_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("gg_no#" . ($i + 1), $i + 1);
            //姓名
            $docObj->setValue("gg_NAME#" . ($i + 1), $data['getMainManagerInfo'][$i]['NAME']);
            //职位
            $docObj->setValue("gg_POSITION#" . ($i + 1), $data['getMainManagerInfo'][$i]['POSITION']);
        }

        //变更信息
        $rows = count($data['getRegisterChangeInfo']);
        $docObj->cloneRow('bg_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("bg_no#" . ($i + 1), $i + 1);
            //变更日期
            $docObj->setValue("bg_ALTDATE#" . ($i + 1), $data['getRegisterChangeInfo'][$i]['ALTDATE']);
            //变更项目
            $docObj->setValue("bg_ALTITEM#" . ($i + 1), $data['getRegisterChangeInfo'][$i]['ALTITEM']);
            //变更前
            $docObj->setValue("bg_ALTBE#" . ($i + 1), $data['getRegisterChangeInfo'][$i]['ALTBE']);
            //变更后
            $docObj->setValue("bg_ALTAF#" . ($i + 1), $data['getRegisterChangeInfo'][$i]['ALTAF']);
        }

        //经营异常
        $rows = count($data['GetOpException']);
        $docObj->cloneRow('jjyc_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("jjyc_no#" . ($i + 1), $i + 1);
            //列入一日
            $docObj->setValue("jjyc_AddDate#" . ($i + 1), $data['GetOpException'][$i]['AddDate']);
            //列入原因
            $docObj->setValue("jjyc_AddReason#" . ($i + 1), $data['GetOpException'][$i]['AddReason']);
            //移除日期
            $docObj->setValue("jjyc_RemoveDate#" . ($i + 1), $data['GetOpException'][$i]['RemoveDate']);
            //移除原因
            $docObj->setValue("jjyc_RomoveReason#" . ($i + 1), $data['GetOpException'][$i]['RomoveReason']);
        }

        //实际控制人
        //姓名
        $docObj->setValue("sjkzr_Name", $data['Beneficiary']['Name']);
        //持股比例
        $docObj->setValue("sjkzr_TotalStockPercent", $data['Beneficiary']['TotalStockPercent']);
        //股权链
        $path = '';
        foreach ($data['Beneficiary']['DetailInfoList'] as $no => $onePath) {
            $path .= '<w:br/>' . ($no + 1) . $onePath['Path'] . '<w:br/>';
        }
        $docObj->setValue("sjkzr_Path", $path);

        //历史沿革
        $rows = count($data['getHistoricalEvolution']);
        $docObj->cloneRow('lsyg_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("lsyg_no#" . ($i + 1), $i + 1);
            //内容
            $docObj->setValue("lsyg_content#" . ($i + 1), $data['getHistoricalEvolution'][$i]);
        }

        //法人对外投资
        $rows = count($data['lawPersonInvestmentInfo']);
        $docObj->cloneRow('frdwtz_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("frdwtz_no#" . ($i + 1), $i + 1);
            //法人
            $docObj->setValue("frdwtz_NAME#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['NAME']);
            //企业名称
            $docObj->setValue("frdwtz_ENTNAME#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['ENTNAME']);
            //持股比例
            $docObj->setValue("frdwtz_CONRATIO#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['CONRATIO']);
            //注册资本
            $docObj->setValue("frdwtz_REGCAP#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['REGCAP']);
            //统一社会信用代码
            $docObj->setValue("frdwtz_SHXYDM#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['SHXYDM']);
            //认缴出资额
            $docObj->setValue("frdwtz_SUBCONAM#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['SUBCONAM']);
            //状态
            $docObj->setValue("frdwtz_ENTSTATUS#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['ENTSTATUS']);
            //认缴出资时间
            $docObj->setValue("frdwtz_CONDATE#" . ($i + 1), $data['lawPersonInvestmentInfo'][$i]['CONDATE']);
        }

        //法人对外任职
        $rows = count($data['getLawPersontoOtherInfo']);
        $docObj->cloneRow('frdwrz_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("frdwrz_no#" . ($i + 1), $i + 1);
            //姓名
            $docObj->setValue("frdwrz_NAME#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['NAME']);
            //任职企业名称
            $docObj->setValue("frdwrz_ENTNAME#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['ENTNAME']);
            //统一社会信用代码
            $docObj->setValue("frdwrz_SHXYDM#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['SHXYDM']);
            //成立日期
            $docObj->setValue("frdwrz_ESDATE#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['ESDATE']);
            //注册资本
            $docObj->setValue("frdwrz_REGCAP#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['REGCAP']);
            //经营状态
            $docObj->setValue("frdwrz_ENTSTATUS#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['ENTSTATUS']);
            //职务
            $docObj->setValue("frdwrz_POSITION#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['POSITION']);
            //是否法人
            $docObj->setValue("frdwrz_ISFRDB#" . ($i + 1), $data['getLawPersontoOtherInfo'][$i]['ISFRDB']);
        }

        //企业对外投资
        $rows = count($data['getInvestmentAbroadInfo']);
        $docObj->cloneRow('qydwtz_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("qydwtz_no#" . ($i + 1), $i + 1);
            //被投资企业名称
            $docObj->setValue("qydwtz_ENTNAME#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['ENTNAME']);
            //成立日期
            $docObj->setValue("qydwtz_ESDATE#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['ESDATE']);
            //经营状态
            $docObj->setValue("qydwtz_ENTSTATUS#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['ENTSTATUS']);
            //注册资本
            $docObj->setValue("qydwtz_REGCAP#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['REGCAP']);
            //认缴出资额
            $docObj->setValue("qydwtz_SUBCONAM#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['SUBCONAM']);
            //出资币种
            $docObj->setValue("qydwtz_CONCUR#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['CONCUR']);
            //出资比例
            $docObj->setValue("qydwtz_CONRATIO#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['CONRATIO']);
            //出资时间
            $docObj->setValue("qydwtz_CONDATE#" . ($i + 1), $data['getInvestmentAbroadInfo'][$i]['CONDATE']);
        }

        //主要分支机构
        $rows = count($data['getBranchInfo']);
        $docObj->cloneRow('fzjg_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("fzjg_no#" . ($i + 1), $i + 1);
            //机构名称
            $docObj->setValue("fzjg_ENTNAME#" . ($i + 1), $data['getBranchInfo'][$i]['ENTNAME']);
            //负责人
            $docObj->setValue("fzjg_FRDB#" . ($i + 1), $data['getBranchInfo'][$i]['FRDB']);
            //成立日期
            $docObj->setValue("fzjg_ESDATE#" . ($i + 1), $data['getBranchInfo'][$i]['ESDATE']);
            //经营状态
            $docObj->setValue("fzjg_ENTSTATUS#" . ($i + 1), $data['getBranchInfo'][$i]['ENTSTATUS']);
            //登记地省份
            $docObj->setValue("fzjg_PROVINCE#" . ($i + 1), $data['getBranchInfo'][$i]['PROVINCE']);
        }

        //银行信息
        $docObj->cloneRow('yhxx_no', 1);
        for ($i = 0; $i < 1; $i++) {
            //序号
            $docObj->setValue("yhxx_no#" . ($i + 1), $i + 1);
            //开户行地址
            $docObj->setValue("yhxx_khh#" . ($i + 1), $data['GetCreditCodeNew']['Bank']);
            //开户行号码
            $docObj->setValue("yhxx_hm#" . ($i + 1), $data['GetCreditCodeNew']['BankAccount']);
        }

        //公司概况
        $rows = count($data['SearchCompanyFinancings']);
        $docObj->cloneRow('gsgk_rzjd', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //融资阶段
            $docObj->setValue("gsgk_rzjd#" . ($i + 1), $data['SearchCompanyFinancings'][$i]['Round']);
            //融资
            $docObj->setValue("gsgk_rz#" . ($i + 1), $data['SearchCompanyFinancings'][$i]['Investment'].'，'.$data['SearchCompanyFinancings'][$i]['Amount']);
        }

        //招投标
        $rows = count($data['TenderSearch']);
        $docObj->cloneRow('ztb_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("ztb_no#" . ($i + 1), $i+1);
            //描述
            $docObj->setValue("ztb_Title#" . ($i + 1), $data['TenderSearch'][$i]['Title']);
            //发布日期
            $docObj->setValue("ztb_Pubdate#" . ($i + 1), $data['TenderSearch'][$i]['Pubdate']);
            //所属地区
            $docObj->setValue("ztb_ProvinceName#" . ($i + 1), $data['TenderSearch'][$i]['ProvinceName']);
            //项目分类
            $docObj->setValue("ztb_ChannelName#" . ($i + 1), $data['TenderSearch'][$i]['ChannelName']);
        }

        //购地信息
        $rows = count($data['LandPurchaseList']);
        $docObj->cloneRow('gdxx_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("gdxx_no#" . ($i + 1), $i+1);
            //项目位置
            $docObj->setValue("gdxx_Address#" . ($i + 1), $data['LandPurchaseList'][$i]['Address']);
            //土地用途
            $docObj->setValue("gdxx_LandUse#" . ($i + 1), $data['LandPurchaseList'][$i]['LandUse']);
            //面积
            $docObj->setValue("gdxx_Area#" . ($i + 1), $data['LandPurchaseList'][$i]['Area']);
            //行政区
            $docObj->setValue("gdxx_AdminArea#" . ($i + 1), $data['LandPurchaseList'][$i]['AdminArea']);
            //供地方式
            $docObj->setValue("gdxx_SupplyWay#" . ($i + 1), $data['LandPurchaseList'][$i]['SupplyWay']);
            //签订日期
            $docObj->setValue("gdxx_SignTime#" . ($i + 1), $data['LandPurchaseList'][$i]['SignTime']);
        }

        //土地公示
        $rows = count($data['LandPublishList']);
        $docObj->cloneRow('tdgs_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("tdgs_no#" . ($i + 1), $i+1);
            //地块位置
            $docObj->setValue("tdgs_Address#" . ($i + 1), $data['LandPublishList'][$i]['Address']);
            //发布机关
            $docObj->setValue("tdgs_PublishGov#" . ($i + 1), $data['LandPublishList'][$i]['PublishGov']);
            //行政区
            $docObj->setValue("tdgs_AdminArea#" . ($i + 1), $data['LandPublishList'][$i]['AdminArea']);
            //发布日期
            $docObj->setValue("tdgs_PublishDate#" . ($i + 1), $data['LandPublishList'][$i]['PublishDate']);
        }

        //土地转让
        $rows = count($data['LandTransferList']);
        $docObj->cloneRow('tdzr_no', $rows);
        for ($i = 0; $i < $rows; $i++) {
            //序号
            $docObj->setValue("tdzr_no#" . ($i + 1), $i+1);
            //土地坐落
            $docObj->setValue("tdzr_no#" . ($i + 1), $data['LandTransferList'][$i]['Address']);
            //行政区
            $docObj->setValue("tdzr_no#" . ($i + 1), $data['LandTransferList'][$i]['PublishGov']);
            //原土地使用权人
            $docObj->setValue("tdzr_no#" . ($i + 1), $data['LandTransferList'][$i]['AdminArea']);
            //现土地使用权人
            $docObj->setValue("tdzr_no#" . ($i + 1), $data['LandTransferList'][$i]['PublishDate']);
            //成交额
            $docObj->setValue("tdzr_no#" . ($i + 1), $data['LandTransferList'][$i]['PublishDate']);
            //面积
            $docObj->setValue("tdzr_no#" . ($i + 1), $data['LandTransferList'][$i]['PublishDate']);
            //成交日期
            $docObj->setValue("tdzr_no#" . ($i + 1), $data['LandTransferList'][$i]['PublishDate']);
        }





        var_dump($data['LandTransferList']);
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

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 经营异常
        $csp->add('GetOpException', function () {

            $postData = ['keyNo' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECIException/GetOpException', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
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

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
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

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 购地信息
        $csp->add('LandPurchaseList', function () {

            $postData = [
                //'searchKey' => $this->entName,
                'searchKey' => '万科企业股份有限公司',
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
                //'searchKey' => $this->entName,
                'searchKey' => '万科企业股份有限公司',
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
                //'searchKey' => $this->entName,
                'searchKey' => '华夏幸福基业股份有限公司',
                'pageIndex' => 1,
                'pageSize' => 10,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandTransfer/LandTransferList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            if (!empty($res))
            {
                foreach ($res as &$one)
                {
                    //取详情
                    $post=['id'=>$one['Id']];
                    $detail = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl.'LandTransfer/LandTransferDetail',$post);
                    ($detail['code'] == 200 && !empty($detail['result'])) ? $detail = $detail['result'] : $detail = null;
                    $one['detail']=$detail;
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
                        $res[] = ['year' => $yearArr[$i], 'yoy' => ($SOCNUM_1 - $SOCNUM_2) / $SOCNUM_2];
                    } else {
                        $res[] = ['year' => $yearArr[$i], 'yoy' => null];
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
                'height' => 550,
                'title' => '财务非授权 - 同比',
                'xTitle' => '此图为概况信息',
                //'yTitle'=>'不错不错',
                'titleSize' => 14,
                'legend' => $legend
            ];

            return CommonService::getInstance()->createBarPic($data, $labels, $extension);
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

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 软件著作权
        $csp->add('SearchSoftwareCr', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchSoftwareCr', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 商标
        $csp->add('tmSearch', function () {

            $postData = [
                'keyword' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'tm/Search', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 作品著作权
        $csp->add('SearchCopyRight', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CopyRight/SearchCopyRight', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 证书资质
        $csp->add('SearchCertification', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ECICertification/SearchCertification', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 纳税信用等级
        $csp->add('satparty_xin', function () {

            $postData = [
                'doc_type' => 'satparty_xin',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 税务许可信息
        $csp->add('satparty_xuke', function () {

            $postData = [
                'doc_type' => 'satparty_xuke',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 税务登记信息
        $csp->add('satparty_reg', function () {

            $postData = [
                'doc_type' => 'satparty_reg',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 税务非正常户
        $csp->add('satparty_fzc', function () {

            $postData = [
                'doc_type' => 'satparty_fzc',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 欠税信息
        $csp->add('satparty_qs', function () {

            $postData = [
                'doc_type' => 'satparty_qs',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 涉税处罚公示
        $csp->add('satparty_chufa', function () {

            $postData = [
                'doc_type' => 'satparty_chufa',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sat', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 行政许可
        $csp->add('GetAdministrativeLicenseList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'ADSTLicense/GetAdministrativeLicenseList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 行政处罚
        $csp->add('GetAdministrativePenaltyList', function () {

            $postData = [
                'searchKey' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AdministrativePenalty/GetAdministrativePenaltyList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 环保 环保处罚
        $csp->add('epbparty', function () {

            $postData = [
                'doc_type' => 'epbparty',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 环保 重点监控企业名单
        $csp->add('epbparty_jkqy', function () {

            $postData = [
                'doc_type' => 'epbparty_jkqy',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 环保 环保企业自行监测结果
        $csp->add('epbparty_zxjc', function () {

            $postData = [
                'doc_type' => 'epbparty_zxjc',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 环保 环评公示数据
        $csp->add('epbparty_huanping', function () {

            $postData = [
                'doc_type' => 'epbparty_huanping',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'epb', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 海关 海关企业
        $csp->add('custom_qy', function () {

            $postData = [
                'doc_type' => 'custom_qy',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 海关 海关许可
        $csp->add('custom_xuke', function () {

            $postData = [
                'doc_type' => 'custom_xuke',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 海关 海关信用
        $csp->add('custom_credit', function () {

            $postData = [
                'doc_type' => 'custom_credit',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 海关 海关处罚
        $csp->add('custom_punish', function () {

            $postData = [
                'doc_type' => 'custom_punish',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'custom', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 一行两会 央行行政处罚
        $csp->add('pbcparty', function () {

            $postData = [
                'doc_type' => 'pbcparty',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 一行两会 银保监会处罚公示
        $csp->add('pbcparty_cbrc', function () {

            $postData = [
                'doc_type' => 'pbcparty_cbrc',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 一行两会 证监处罚公示
        $csp->add('pbcparty_csrc_chufa', function () {

            $postData = [
                'doc_type' => 'pbcparty_csrc_chufa',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 一行两会 外汇局处罚
        $csp->add('safe_chufa', function () {

            $postData = [
                'doc_type' => 'safe_chufa',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 一行两会 外汇局许可
        $csp->add('safe_xuke', function () {

            $postData = [
                'doc_type' => 'safe_xuke',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'pbc', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 法院公告
        $csp->add('fygg', function () {

            $postData = [
                'doc_type' => 'fygg',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 开庭公告
        $csp->add('ktgg', function () {

            $postData = [
                'doc_type' => 'ktgg',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 裁判文书
        $csp->add('cpws', function () {

            $postData = [
                'doc_type' => 'cpws',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 执行公告
        $csp->add('zxgg', function () {

            $postData = [
                'doc_type' => 'zxgg',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 失信公告
        $csp->add('shixin', function () {

            $postData = [
                'doc_type' => 'shixin',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 被执行人
        $csp->add('SearchZhiXing', function () {

            $postData = [
                'searchKey' => $this->entName,
                'isExactlySame' => true,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'CourtV4/SearchZhiXing', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 司法查冻扣
        $csp->add('sifacdk', function () {

            $postData = [
                'doc_type' => 'sifacdk',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'sifa', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 动产抵押
        $csp->add('getChattelMortgageInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 20,
            ], 'getChattelMortgageInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //淘数 股权出质
        $csp->add('getEquityPledgedInfo', function () {

            $res = (new TaoShuService())->setCheckRespFlag(true)->post([
                'entName' => $this->entName,
                'pageNo' => 1,
                'pageSize' => 20,
            ], 'getEquityPledgedInfo');

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 企业年报 其中有对外担保 这个字段ProvideAssuranceList
        $csp->add('GetAnnualReport', function () {

            $postData = ['keyNo' => $this->entName];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'AR/GetAnnualReport', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //企查查 土地抵押
        $csp->add('GetLandMortgageList', function () {

            $postData = [
                'keyWord' => $this->entName,
                'pageIndex' => 1,
                'pageSize' => 20,
            ];

            $res = (new QiChaChaService())->setCheckRespFlag(true)->get($this->qccUrl . 'LandMortgage/GetLandMortgageList', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 中登动产融资 应收账款
        $csp->add('company_zdw_yszkdsr', function () {

            $postData = [
                'doc_type' => 'company_zdw_yszkdsr',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 中登动产融资 租赁登记
        $csp->add('company_zdw_zldjdsr', function () {

            $postData = [
                'doc_type' => 'company_zdw_zldjdsr',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 中登动产融资 保证金质押登记
        $csp->add('company_zdw_bzjzydsr', function () {

            $postData = [
                'doc_type' => 'company_zdw_bzjzydsr',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 中登动产融资 仓单质押
        $csp->add('company_zdw_cdzydsr', function () {

            $postData = [
                'doc_type' => 'company_zdw_cdzydsr',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 中登动产融资 所有权保留
        $csp->add('company_zdw_syqbldsr', function () {

            $postData = [
                'doc_type' => 'company_zdw_syqbldsr',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        //法海 中登动产融资 其他动产融资
        $csp->add('company_zdw_qtdcdsr', function () {

            $postData = [
                'doc_type' => 'company_zdw_qtdcdsr',
                'keyword' => $this->entName,
                'pageno' => 1,
                'range' => 20,
            ];

            $res = (new FaHaiService())->setCheckRespFlag(true)->getList($this->fahaiList . 'zdw', $postData);

            ($res['code'] === 200 && !empty($res['result'])) ? $res = $res['result'] : $res = null;

            return $res;
        });

        return CspService::getInstance()->exec($csp);
    }


}
