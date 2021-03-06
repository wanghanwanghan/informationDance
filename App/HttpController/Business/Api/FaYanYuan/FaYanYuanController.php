<?php

namespace App\HttpController\Business\Api\FaYanYuan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;
use App\HttpController\Service\Pay\ChargeService;

class FaYanYuanController extends FaYanYuanBase
{
    private $listBaseUrl;//企业的
    private $listBaseUrlForPerson;//个人的
    private $detailBaseUrl;

    public $moduleNum;//扣费的id
    public $entName;//扣费用的entName

    function onRequest(?string $action): ?bool
    {
        $this->listBaseUrl = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $this->listBaseUrlForPerson = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrlForPerson');
        $this->detailBaseUrl = CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl');

        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验法研院返回值，并给客户计费
    private function checkResponse($res, $docType, $type, $writeJson = true)
    {
        $type = ucfirst($type);

        if (isset($res['pageNo']) && isset($res['range']) && isset($res['totalCount']) && isset($res['totalPageNum'])) {
            $res['Paging'] = [
                'page' => $res['pageNo'],
                'pageSize' => $res['range'],
                'total' => $res['totalCount'],
                'totalPage' => $res['totalPageNum'],
            ];

        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        $res['code'] === 's' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        if ($type === 'List') {
            isset($res[$docType . $type]) ? $res['Result'] = $res[$docType . $type] : $res['Result'] = [];
        } elseif ($type === 'Detail') {
            isset($res[$docType]) ? $res['Result'] = $res[$docType] : $res['Result'] = [];
        } elseif ($type === 'Person') {
            isset($res[$docType . 'List']) ? $res['Result'] = $res[$docType . 'List'] : $res['Result'] = $res['allList'];
        } else {
            $res['Result'] = null;
        }

        if ($type === 'Detail') {
            //详情要扣费
            $charge = ChargeService::getInstance()->FaYanYuan($this->request(), $this->moduleNum, $this->entName);
            if ($charge['code'] != 200 && $charge['code'] != 999) {
                $res['code'] = $charge['code'];
                $res['Paging'] = $res['Result'] = null;
                $res['msg'] = $charge['msg'];
            }
        }

        return $writeJson !== true ? [
            'code' => $res['code'],
            'paging' => $res['Paging'],
            'result' => $res['Result'],
            'msg' => $res['msg']
        ] : $this->writeJson($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    //环保处罚
    function getEpbparty()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'epbparty';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'epb', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //环保处罚详情
    function getEpbpartyDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'epbparty';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //重点监控企业名单
    function getEpbpartyJkqy()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'epbparty_jkqy';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'epb', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //重点监控企业名单详情
    function getEpbpartyJkqyDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'epbparty_jkqy';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //环保企业自行监测结果
    function getEpbpartyZxjc()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'epbparty_zxjc';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'epb', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //环保企业自行监测结果详情
    function getEpbpartyZxjcDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'epbparty_zxjc';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //环评公示数据
    function getEpbpartyHuanping()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'epbparty_huanping';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'epb', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //环评公示数据详情
    function getEpbpartyHuanpingDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'epbparty_huanping';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //海关企业
    function getCustomQy()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'custom_qy';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'custom', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //海关企业详情
    function getCustomQyDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'custom_qy';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //海关许可
    function getCustomXuke()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'custom_xuke';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'custom', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //海关许可详情
    function getCustomXukeDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'custom_xuke';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //海关信用
    function getCustomCredit()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'custom_credit';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'custom', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //海关信用详情
    function getCustomCreditDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'custom_credit';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //海关处罚
    function getCustomPunish()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'custom_punish';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'custom', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //海关处罚详情
    function getCustomPunishDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'custom_punish';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //开庭公告
    function getKtgg()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'ktgg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sifa', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //开庭公告详情
    function getKtggDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        $this->moduleNum = 1;

        $postData = ['id' => $id];

        $docType = 'ktgg';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //裁判文书
    function getCpws()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'cpws';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sifa', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //裁判文书详情
    function getCpwsDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        $this->moduleNum = 2;

        $postData = ['id' => $id];

        $docType = 'cpws';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //法院公告
    function getFygg()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'fygg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sifa', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //法院公告详情
    function getFyggDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        $this->moduleNum = 3;

        $postData = ['id' => $id];

        $docType = 'fygg';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //执行公告
    function getZxgg()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'zxgg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sifa', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //执行公告详情
    function getZxggDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        $this->moduleNum = 4;

        $postData = ['id' => $id];

        $docType = 'zxgg';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //失信公告
    function getShixin()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'shixin';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sifa', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //失信公告详情
    function getShixinDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        $this->moduleNum = 5;

        $postData = ['id' => $id];

        $docType = 'shixin';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //司法查封冻结扣押
    function getSifacdk()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'sifacdk';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sifa', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //司法查封冻结扣押详情
    function getSifacdkDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        $this->moduleNum = 6;

        $postData = ['id' => $id];

        $docType = 'sifacdk';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //欠税公告
    function getSatpartyQs()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'satparty_qs';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sat', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //欠税公告详情
    function getSatpartyQsDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        //$this->moduleNum = 8;

        $postData = ['id' => $id];

        $docType = 'satparty_qs';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //涉税处罚公示
    function getSatpartyChufa()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'satparty_chufa';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sat', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //涉税处罚公示详情
    function getSatpartyChufaDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        //$this->moduleNum = 9;

        $postData = ['id' => $id];

        $docType = 'satparty_chufa';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //税务非正常户公示
    function getSatpartyFzc()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'satparty_fzc';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sat', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //税务非正常户公示详情
    function getSatpartyFzcDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        //$this->moduleNum = 10;

        $postData = ['id' => $id];

        $docType = 'satparty_fzc';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //纳税信用等级
    function getSatpartyXin()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'satparty_xin';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sat', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //纳税信用等级详情
    function getSatpartyXinDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        //$this->moduleNum = 11;

        $postData = ['id' => $id];

        $docType = 'satparty_xin';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //税务登记
    function getSatpartyReg()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'satparty_reg';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sat', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //税务登记详情
    function getSatpartyRegDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        //$this->moduleNum = 12;

        $postData = ['id' => $id];

        $docType = 'satparty_reg';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //税务许可
    function getSatpartyXuke()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'satparty_xuke';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'sat', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //税务许可详情
    function getSatpartyXukeDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $this->entName = $this->request()->getRequestParam('entName') ?? '';

        //$this->moduleNum = 13;

        $postData = ['id' => $id];

        $docType = 'satparty_xuke';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //央行行政处罚
    function getPbcparty()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'pbcparty';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'pbc', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //央行行政处罚详情
    function getPbcpartyDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'pbcparty';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //银保监会处罚公示
    function getPbcpartyCbrc()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'pbcparty_cbrc';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'pbc', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //银保监会处罚公示详情
    function getPbcpartyCbrcDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'pbcparty_cbrc';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //证监处罚公示
    function getPbcpartyCsrcChufa()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'pbcparty_csrc_chufa';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'pbc', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //证监处罚公示详情
    function getPbcpartyCsrcChufaDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'pbcparty_csrc_chufa';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //证监会许可批复等级
    function getPbcpartyCsrcXkpf()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'pbcparty_csrc_xkpf';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'pbc', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //证监会许可批复等级详情
    function getPbcpartyCsrcXkpfDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'pbcparty_csrc_xkpf';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //外汇局处罚
    function getSafeChufa()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'safe_chufa';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'pbc', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //外汇局处罚详情
    function getSafeChufaDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'safe_chufa';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //外汇局许可
    function getSafeXuke()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'safe_xuke';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'pbc', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //外汇局许可详情
    function getSafeXukeDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'safe_xuke';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //应收账款
    function getCompanyZdwYszkdsr()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_zdw_yszkdsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zdw', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //应收账款详情
    function getCompanyZdwYszkdsrDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'company_zdw_yszkdsr';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //租赁登记
    function getCompanyZdwZldjdsr()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_zdw_zldjdsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zdw', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //租赁登记详情
    function getCompanyZdwZldjdsrDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'company_zdw_zldjdsr';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //保证金质押登记
    function getCompanyZdwBzjzydsr()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_zdw_bzjzydsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zdw', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //保证金质押登记详情
    function getCompanyZdwBzjzydsrDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'company_zdw_bzjzydsr';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //仓单质押
    function getCompanyZdwCdzydsr()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_zdw_cdzydsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zdw', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //仓单质押详情
    function getCompanyZdwCdzydsrDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'company_zdw_cdzydsr';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //所有权保留
    function getCompanyZdwSyqbldsr()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_zdw_syqbldsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zdw', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //所有权保留详情
    function getCompanyZdwSyqbldsrDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'company_zdw_syqbldsr';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //其他动产融资
    function getCompanyZdwQtdcdsr()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_zdw_qtdcdsr';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zdw', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //其他动产融资详情
    function getCompanyZdwQtdcdsrDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = 'company_zdw_qtdcdsr';

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //个人涉诉
    function getPersonSifa()
    {
        $name = $this->request()->getRequestParam('name');
        $idcardNo = $this->request()->getRequestParam('idcardNo');
        $docType = $this->request()->getRequestParam('docType') ?? 'ktgg';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'doc_type' => $docType,
            'name' => $name,
            'idcardNo' => $idcardNo,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getListForPerson($this->listBaseUrlForPerson . 'sifa', $postData);

        return $this->checkResponse($res, $docType, 'person');
    }

    //个人涉诉详情
    function getPersonSifaDetail()
    {
        $id = $this->request()->getRequestParam('id') ?? '';

        $postData = ['id' => $id];

        $docType = $this->request()->getRequestParam('docType') ?? '';

        if (empty($docType)) return $this->writeJson(201, null, null, 'docType不能是空');

        $res = (new FaYanYuanService())->getDetail($this->detailBaseUrl . $docType, $postData);

        return $this->checkResponse($res, $docType, 'detail');
    }

    //上市公司-抵押解除
    function getCompanyDcrzDiyajc()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_dcrz_diyajc';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zyzb', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //上市公司-解质押数据
    function getCompanyDcrzZhiyajc()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_dcrz_zhiyajc';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zyzb', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }

    //上市公司-担保数据
    function getCompanyDcrzDanbao()
    {
        $entName = $this->request()->getRequestParam('entName');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $docType = 'company_dcrz_danbao';

        $postData = [
            'doc_type' => $docType,
            'keyword' => $entName,
            'pageno' => $page,
            'range' => $pageSize,
        ];

        $res = (new FaYanYuanService())->getList($this->listBaseUrl . 'zyzb', $postData);

        $res = $this->checkResponse($res, $docType, 'list', false);

        if (!is_array($res)) return $res;

        if ($res['code'] == 200 && !empty($res['result'])) {
            foreach ($res['result'] as &$one) {
                if (!isset($one['body'])) continue;
                $one['body'] = preg_replace('/[a-z]/i', '', trim($one['body']));
            }
            unset($one);
        }

        return $this->writeJson($res['code'], $res['paging'], $res['result'], $res['msg']);
    }


}