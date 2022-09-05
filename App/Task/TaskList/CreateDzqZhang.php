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

    function __construct($data)
    {
        $this->data = $data;

        return parent::__construct();
    }

    function run(int $taskId, int $workerIndex)
    {
        $zhuData = AntAuthList::create()->where(['authDate'=>0,'dianZiQian_status'=>0]);
        if(!empty($zhuData)){
            foreach ( $zhuData as $val ){
                $detail = AntAuthSealDetail::create()->where(['dianZiQian_id'=>$val->getAttr('id')]);
                if(!empty($detail)){
                    if($detail->getAttr('isSeal')){
                        $gaizhangParam = [
                            'entName'      => $val->getAttr('entName'),
                            'legalPerson'  => $val->getAttr('legalPerson'),
                            'idCard'       => $val->getAttr('idCard'),
                            'socialCredit' => $val->getAttr('socialCredit'),
                        ];
                        $path = Carbon::now()->format('Ymd') . DIRECTORY_SEPARATOR;
                        is_dir(INV_AUTH_PATH . $path) || mkdir(INV_AUTH_PATH . $path, 0755);
                        $path = $path .$val->getAttr('orderNo').Carbon::now()->format('YmdHis').'.pdf';
                        //储存pdf
                        file_put_contents( INV_AUTH_PATH .$path,file_get_contents($detail->getAttr('fileAddress')),FILE_APPEND | LOCK_EX);
                        $gaizhangParam['file'] = $path;

                        $dianziqian_id = '';
                        try{
                            $dianziqian_id = (new DianZiQianService())->gaiZhang($gaizhangParam);
                            if(is_array($dianziqian_id)){
                                dingAlarmUser('获取电子牵盖章ID',['dianZiQian_id'=>$val->getAttr('id'),'res'=>$dianziqian_id],[18511881968]);
                                CommonService::getInstance()->log4PHP([$dianziqian_id], 'info', 'getAuthFileId');
                            }
                            CommonService::getInstance()->log4PHP([$dianziqian_id], 'gaiZhang_res', 'mayilog');
                        } catch (\Throwable $e){
                            CommonService::getInstance()->log4PHP([$e], 'gaiZhang$e', 'mayilog');
                        }
                        if($dianziqian_id != ''){
                            AntAuthSealDetail::create()->get($detail->getAttr('id'))->update(['dianZiQian_id'=>$dianziqian_id]);
                        }
                    }
                }else{
                    $gaizhangParam = [
                        'entName'      => $val->getAttr('entName'),
                        'legalPerson'  => $val->getAttr('legalPerson'),
                        'idCard'       => $val->getAttr('idCard'),
                        'socialCredit' => $val->getAttr('socialCredit'),
                        'file'         => 'dianziqian_jcsk_shouquanshu.pdf',
                        'phone'        => $val->getAttr('phone'),
                        'regAddress'   => $val->getAttr('regAddress'),
                        'city'         => $val->getAttr('city'),
                    ];
                    $dianziqian_id = (new DianZiQianService())->getAuthFileId($gaizhangParam);
                    if(is_array($dianziqian_id)){
                        dingAlarmUser('获取电子牵盖章ID',['id'=>$val->getAttr('id'),'res'=>$dianziqian_id],[18511881968]);
                        CommonService::getInstance()->log4PHP([$dianziqian_id], 'info', 'getAuthFileId');
                    }else{
                        AntAuthList::create()->where('id=' . $val->getAttr('id'))->update(['dianZiQian_id' => $dianziqian_id,'dianZiQian_status'=>0]);
                    }
                }
            }

        }

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }
}
