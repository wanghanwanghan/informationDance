<?php

namespace App\Task\TaskList;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntAuthSealDetail;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DianZiqian\DianZiQianService;
use App\Task\TaskBase;
use Carbon\Carbon;
use EasySwoole\Task\AbstractInterface\TaskInterface;

class CreateDzqZhang extends TaskBase implements TaskInterface
{
    private $data;
    private $id;

    function __construct($id, $data)
    {
        $this->data = $data;
        $this->id   = $id;
        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $data = $this->data;
        if (!empty($data['fileData'])) {
            foreach ($data['fileData'] as $datum) {
                $d = AntAuthSealDetail::create()->where(['fileId' => $datum['fileId']])->get();
                if ($d->getAttr('isSeal')) {
                    $gaizhangParam = [
                        'entName'      => $data['entName'],
                        'legalPerson'  => $data['legalPerson'],
                        'idCard'       => $data['idCard'],
                        'socialCredit' => $data['socialCredit'],
                    ];
                    $path          = Carbon::now()->format('Ymd') . DIRECTORY_SEPARATOR;
                    is_dir(INV_AUTH_PATH . $path) || mkdir(INV_AUTH_PATH . $path, 0755);
                    $path = $path . $data['orderNo'] . Carbon::now()->format('YmdHis') . '.pdf';
                    //储存pdf
                    file_put_contents(INV_AUTH_PATH . $path, file_get_contents($datum['fileAddress']), FILE_APPEND | LOCK_EX);
                    $gaizhangParam['file'] = $path;

                    try {
                        $dianziqian_id = (new DianZiQianService())->gaiZhang($gaizhangParam);
                        if (is_array($dianziqian_id)) {
                            dingAlarmUser('获取电子牵盖章ID', ['id' => $this->id, 'fileId' => $datum['fileId'], 'res' => json_encode($dianziqian_id), 'msg' => $dianziqian_id['msg'] ?? ''], [18511881968]);
                            CommonService::getInstance()->log4PHP([$dianziqian_id], 'gaiZhang_res', 'mayilog');
                        } else {
                            AntAuthSealDetail::create()->where(['fileId' => $datum['fileId'], 'antAuthId' => $this->id])->update(['dianZiQian_id' => $dianziqian_id ?? '']);
                        }
                    } catch (\Throwable $e) {
                        CommonService::getInstance()->log4PHP([$e], 'gaiZhang$e', 'mayilog');
                    }
                }
            }
        } else {
            try {
                $check2        = AntAuthList::create()->where([
                                                                  'entName'      => $data['entName'],
                                                                  'socialCredit' => $data['socialCredit'],
                                                              ])->get();
                $gaizhangParam = [
                    'entName'      => $data['entName'],
                    'legalPerson'  => $data['legalPerson'],
                    'idCard'       => $data['idCard'],
                    'socialCredit' => $data['socialCredit'],
                    'file'         => 'dianziqian_jcsk_shouquanshu.pdf',
                    'phone'        => $data['phone'],
                    'regAddress'   => $check2->getAttr('regAddress'),
                    'city'         => $check2->getAttr('city'),
                ];
                $dianziqian_id = (new DianZiQianService())->getAuthFileId($gaizhangParam);
                if (is_array($dianziqian_id)) {
                    dingAlarmUser('获取电子牵盖章ID', ['id' => $this->id, 'res' => json_encode($dianziqian_id), 'msg' => $dianziqian_id['msg'] ?? ''], [18511881968]);
                    CommonService::getInstance()->log4PHP([$dianziqian_id], 'info', 'getAuthFileId');
                } else {
                    AntAuthList::create()->where('id=' . $this->id)->update(['dianZiQian_id' => $dianziqian_id, 'dianZiQian_status' => 0]);
                }
            } catch (\Throwable $e) {
                CommonService::getInstance()->log4PHP([$e], 'info', 'mayilog');
            }
        }

        $list = AntAuthSealDetail::create()->where(['dianZiQian_status' => 0, 'dianZiQian_id' => 0, 'isSeal' => 'true'])->all();
        if (!empty($list)) {
            $actionData = [];
            $list       = json_decode(json_encode($list), true);
            foreach ($list as $val) {
                $actionData[$val['antAuthId']]['detail'] = $val;
            }
            foreach ($actionData as $id => $v) {
                $info                    = AntAuthList::create()->get($id);
                $actionData[$id]['info'] = json_decode(json_encode($info), true);
            }
            foreach ($actionData as $value) {
                $gaizhangParam = [
                    'entName'      => $value['info']['entName'],
                    'legalPerson'  => $value['info']['legalPerson'],
                    'idCard'       => $value['info']['idCard'],
                    'socialCredit' => $value['info']['socialCredit'],
                ];
                $path          = Carbon::now()->format('Ymd') . DIRECTORY_SEPARATOR;
                is_dir(INV_AUTH_PATH . $path) || mkdir(INV_AUTH_PATH . $path, 0755);
                $path = $path . $value['info']['orderNo'] . Carbon::now()->format('YmdHis') . '.pdf';
                //储存pdf
                file_put_contents(INV_AUTH_PATH . $path, file_get_contents($value['detail']['fileAddress']), FILE_APPEND | LOCK_EX);
                $gaizhangParam['file'] = $path;

                try {
                    $dianziqian_id = (new DianZiQianService())->gaiZhang($gaizhangParam);
                    if (is_array($dianziqian_id)) {
                        dingAlarmUser('获取电子牵盖章ID', ['fileId' => $value['detail']['fileId'], 'res' => json_encode($dianziqian_id), 'msg' => $dianziqian_id['msg'] ?? ''], [18511881968]);
                        CommonService::getInstance()->log4PHP([$dianziqian_id], 'gaiZhang_res', 'mayilog');
                    } else {
                        AntAuthSealDetail::create()->where(['fileId' => $value['detail']['fileId']])->update(['dianZiQian_id' => $dianziqian_id ?? '']);
                    }
                } catch (\Throwable $e) {
                    CommonService::getInstance()->log4PHP([$e], 'gaiZhang$e', 'mayilog');
                }
            }
        }
        $iList = AntAuthList::create()->where([
                                                  'authDate'          => 0,
                                                  'filePath'          => '',
                                                  'dianZiQian_id'     => 0,
                                                  'dianZiQian_status' => 0
                                              ])->all();
        if (!empty($iList)) {
            foreach ($iList as $item) {
                $i             = json_decode(json_encode($item), true);
                $gaizhangParam = [
                    'entName'      => $i['entName'],
                    'legalPerson'  => $i['legalPerson'],
                    'idCard'       => $i['idCard'],
                    'socialCredit' => $i['socialCredit'],
                    'file'         => 'dianziqian_jcsk_shouquanshu.pdf',
                    'phone'        => $i['phone'],
                    'regAddress'   => $i['regAddress'],
                    'city'         => $i['city'],
                ];
                $dianziqian_id = (new DianZiQianService())->getAuthFileId($gaizhangParam);
                if (is_array($dianziqian_id)) {
                    dingAlarmUser('获取电子牵盖章ID', ['id' => $i['id'], 'res' => json_encode($dianziqian_id), 'msg' => $dianziqian_id['msg'] ?? ''], [18511881968]);
                    CommonService::getInstance()->log4PHP([$dianziqian_id], 'info', 'getAuthFileId');
                } else {
                    AntAuthList::create()->where('id=' . $i['id'])->update(['dianZiQian_id' => $dianziqian_id, 'dianZiQian_status' => 0]);
                }
            }
        }

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
