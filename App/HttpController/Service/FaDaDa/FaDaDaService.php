<?php

namespace App\HttpController\Service\FaDaDa;

use App\HttpController\Models\Api\FaDaDa\FaDaDaUserModel;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateSeal\SealService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class FaDaDaService extends ServiceBase
{
    public $url;
    public $app_id;
    public $app_secret;
    public $account_type;
    public $timestamp;
    public $curl_use_cache;

    function __construct($type = '')
    {
        parent::__construct();

        $this->url = 'https://testapi.fadada.com:8443/api/';
        $this->app_id = '405806';
        $this->app_secret = 'UFPU9C3kfnKb6kdDyugOhqir';

        if (strtolower($type) === 'test') {
            $this->url = 'https://testapi.fadada.com:8443/api/';
            $this->app_id = '405806';
            $this->app_secret = 'UFPU9C3kfnKb6kdDyugOhqir';
        }

        $this->timestamp = Carbon::now()->format('YmdHis');
        $this->curl_use_cache = false;

        return true;
    }

    private function checkResp(array $res): array
    {
        if (isset($res['code']) && $res['code'] - 0 === 1) {
            $code = 200;
        } else {
            $code = $res['code'] - 0;
        }

        $paging = null;

        $result = $res['data'] ?? null;

        $msg = $res['msg'] ?? null;

        return $this->createReturn($code, $paging, $result, $msg);
    }

    private function getHeader(string $type = ''): array
    {
        switch (strtolower($type)) {
            case 'json':
                return ['Content-Type' => 'application/json;charset=UTF-8'];
            case 'form':
                return ['Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'];
            case 'file':
                return ['Content-Type' => 'multipart/form-data;charset=utf8'];
            default:
                return [];
        }
    }

    function setCurlUseCache(bool $type): FaDaDaService
    {
        $this->curl_use_cache = $type;
        return $this;
    }

    /**
     * 企业授权
     * @param array $arr
     * @return array
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    function getAuthFile(array $arr): array
    {
        CommonService::getInstance()->log4PHP($arr,'info','get_auth_file_param_arr');
        //企业注册
        list($ent_customer_id,$entCustomerErrorData) = $this->getEntCustomer($arr);
        if(!empty($entCustomerErrorData)) return $entCustomerErrorData;
        //法人是否注册过
        list($people_customer_id,$peopleCustomerErrorData) = $this->getPeopleCustomer($arr);
        if(!empty($peopleCustomerErrorData)) return $peopleCustomerErrorData;
        //企业哈希存证
        list($ent_hash_id,$entHashErrorData) = $this->entHash($ent_customer_id,$arr);
        if(!empty($entHashErrorData)) return $entHashErrorData;
        //法人哈希存证
        list($people_hash_id,$peopleHashErrorData) = $this->peopleHash($people_customer_id,$arr);
        if(!empty($peopleHashErrorData)) return $peopleHashErrorData;
        //企业上传印章
        list($ent_sign_id,$entSignErrorData) = $this->entSign($ent_customer_id,$arr);
        if(!empty($entSignErrorData)) return $entSignErrorData;
        //企业上传法人照片
        list($personal_sign_id,$personalSignErrorData) = $this->personalSign($people_customer_id,$arr);
        if(!empty($personalSignErrorData)) return $personalSignErrorData;
        $arr['template_id'] = control::getUuid();//模版ID 本地自增
        $arr['contract_id'] = control::getUuid();//合同编号

        //模板上传
        $uploadTemplateErrorData = $this->checkRet($this->uploadTemplate($arr));
        if(!empty($uploadTemplateErrorData)) return $uploadTemplateErrorData;
        //模板填充
        $fillTemplateErrorData = $this->checkRet($this->fillTemplate($arr));
        if(!empty($fillTemplateErrorData)) return $fillTemplateErrorData;
        //自动签署企业印章
        $ExtsignAutoErrorData = $this->checkRet($this->getExtsignAuto($arr,$ent_sign_id,510,580));
        if(!empty($ExtsignAutoErrorData)) return $ExtsignAutoErrorData;
        //自动签署法人姓名
        $ExtsignAutoErrorData = $this->checkRet($this->getExtsignAuto($arr,$personal_sign_id,550,680));
        if(!empty($ExtsignAutoErrorData)) return $ExtsignAutoErrorData;
        //合同下载
        $pdf_path = $downLoadContractErrorData = $this->checkRet($this->downLoadContract($arr));
        if(!empty($downLoadContractErrorData)) return $downLoadContractErrorData;

        //数据入库
        FaDaDaUserModel::create()->where('customer_id', $ent_customer_id)->update([
            'pdf' => $pdf_path
        ]);
        FaDaDaUserModel::create()->where('customer_id', $people_customer_id)->update([
            'pdf' => $pdf_path
        ]);
        FaDaDaUserModel::create()->where('customer_id', $ent_customer_id)->update([
            'template_id' => $arr['template_id'],'contract_id' => $arr['contract_id']
        ]);
        FaDaDaUserModel::create()->where('customer_id', $people_customer_id)->update([
            'template_id' => $arr['template_id'],'contract_id' => $arr['contract_id']
        ]);
        $result = [];
        $msg = '';
        return $this->createReturn(200, null, $result, $msg);
    }

    /**
     * 检查接口返回是否成功
     * @param $ret
     * @return array|string
     */
    private function checkRet($ret){
        if (!$ret['code'] === 200) {
            return $this->createReturn(
                $ret['code'], null, $ret['result'], $ret['msg']
            );
        }
        return '';
    }


    /**
     * 企业注册
     * @param $arr
     * @return array|mixed|string|null
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    private function getEntCustomer($arr){
        $ent_customer_info = FaDaDaUserModel::create()
            ->where("(`entName` = '{$arr['entName']}' OR `code` = '{$arr['socialCredit']}') AND `account_type` = '2'")
            ->get();

        if (empty($ent_customer_info)) {
            return $ent_customer_info->getAttr('customer_id');;
        }
        $ent_customer_id = '';
        $errorData = '';
        $this->timestamp = Carbon::now()->format('YmdHis');
        $openId = control::getUuid();
        $ent_customer_info = $this->getRegister($openId, '2');
        if ($ent_customer_info['code'] === 200) {
            $ent_customer_id = $ent_customer_info['result'];
        } else {
            $errorData = $this->createReturn(
                $ent_customer_info['code'], null, $ent_customer_info['result'], $ent_customer_info['msg']
            );
        }
        //数据入库
        FaDaDaUserModel::create()->data([
            'entName' => $arr['entName'],
            'code' => $arr['socialCredit'],
            'account_type' => '2',
            'customer_id' => $ent_customer_id . '',
            'open_id' => $openId,
        ])->save();
        return [$ent_customer_id,$errorData];
    }

    /**
     * 法人注册
     * @param $arr
     * @return array|mixed|string|null
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    private function getPeopleCustomer($arr){
        $people_customer_info = FaDaDaUserModel::create()
            ->where("(`entName` = '{$arr['legalPerson']}' OR `code` = '{$arr['idCard']}') AND `account_type` = '1'")
            ->get();
        if (empty($people_customer_info)) {
            return $people_customer_info->getAttr('customer_id');;
        }
        $people_customer_id = '';
        $errorData = '';
        $this->timestamp = Carbon::now()->format('YmdHis');
        $openId = control::getUuid();
        $people_customer_info = $this->getRegister($openId, '2');
        if ($people_customer_info['code'] === 200) {
            $people_customer_id = $people_customer_info['result'];
            //数据入库
            FaDaDaUserModel::create()->data([
                'entName' => $arr['legalPerson'],
                'code' => $arr['idCard'],
                'account_type' => '1',
                'customer_id' => $people_customer_id . '',
                'open_id' => $openId,
            ])->save();
        } else {
            $errorData =  $this->createReturn(
                $people_customer_info['code'], null, $people_customer_info['result'], $people_customer_info['msg']
            );
        }
        return [$people_customer_id,$errorData];
    }

    /**
     * 企业哈希存证
     * @param $ent_customer_id
     * @param $arr
     * @return array|mixed|string|null
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    private function entHash($ent_customer_id,$arr){
        $ent_hash_info = FaDaDaUserModel::create()
            ->where('customer_id', $ent_customer_id)
            ->get();
        if (!empty($ent_hash_info->getAttr('evidence_no'))) {
            return $ent_hash_info->getAttr('evidence_no');
        }
        $ent_hash_id = '';
        $errorData = '';
        $this->timestamp = Carbon::now()->format('YmdHis');
        $data = [
            'cert_flag' => '1',//自动申请编号证书
            'customer_id' => $ent_customer_id,
            'file_name' => control::getUuid() . '.pdf',
            'file_size' => mt_rand(1024, 4096) . '',
            'noper_time' => time() . '',
            'original_sha256' => hash('sha256', $ent_customer_id),
            'preservation_desc' => $arr['entName'],
            'preservation_name' => $arr['entName'],
            'transaction_id' => control::getUuid(),
        ];
        $ent_hash_info = $this->getHashDeposit($data);
        if ($ent_hash_info['code'] === 200) {
            $ent_hash_id = $ent_hash_info['result'];
            //数据入库
            FaDaDaUserModel::create()->where('customer_id', $ent_customer_id)->update([
                'evidence_no' => $ent_hash_id
            ]);
        } else {
            $errorData = $this->createReturn(
                $ent_hash_info['code'], null, $ent_hash_info['result'], $ent_hash_info['msg']
            );
        }
        return [$ent_hash_id,$errorData];
    }

    /**
     * 法人哈希存证
     * @param $people_customer_id
     * @param $arr
     * @return array|mixed|string|null
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    private function peopleHash($people_customer_id,$arr){
        $people_hash_info = FaDaDaUserModel::create()
            ->where('customer_id', $people_customer_id)
            ->get();
        if (!empty($people_hash_info->getAttr('evidence_no'))) {
            return $people_hash_info->getAttr('evidence_no');
        }
        $people_hash_id = '';
        $errorData = '';
        $this->timestamp = Carbon::now()->format('YmdHis');
        $data = [
            'cert_flag' => '1',//自动申请编号证书
            'customer_id' => $people_customer_id,
            'file_name' => control::getUuid() . '.pdf',
            'file_size' => mt_rand(1024, 4096) . '',
            'noper_time' => time() . '',
            'original_sha256' => hash('sha256', $people_customer_id),
            'preservation_desc' => $arr['legalPerson'],
            'preservation_name' => $arr['legalPerson'],
            'transaction_id' => control::getUuid(),
        ];
        $people_hash_info = $this->getHashDeposit($data);
        if ($people_hash_info['code'] === 200) {
            $people_hash_id = $people_hash_info['result'];
            //数据入库
            FaDaDaUserModel::create()->where('customer_id', $people_customer_id)->update([
                'evidence_no' => $people_hash_id
            ]);
        } else {
            $errorData =  $this->createReturn(
                $people_hash_info['code'], null, $people_hash_info['result'], $people_hash_info['msg']
            );
        }
        return [$people_hash_id,$errorData];
    }

    /**
     * 企业上传印章
     * @param $ent_customer_id
     * @param $arr
     * @return array|mixed|string|null
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    private function entSign($ent_customer_id,$arr){
        $ent_sign_info = FaDaDaUserModel::create()
            ->where('customer_id', $ent_customer_id)
            ->get();
        if (!empty($ent_sign_info->getAttr('signature_id'))) {
            return $ent_sign_info->getAttr('signature_id');
        }
        $ent_sign_id = '';
        $errorData = '';
        $this->timestamp = Carbon::now()->format('YmdHis');
        $ent_sign_info = $this->uploadSignature([
            'customer_id' => $ent_customer_id,
            'signature_img_base64' => $this->getEntSignBase64($arr),
        ]);
        if ($ent_sign_info['code'] === 200) {
            $ent_sign_id = $ent_sign_info['result']['signature_id'];
        } else {
            $errorData = $this->createReturn(
                $ent_sign_info['code'], null, $ent_sign_info['result'], $ent_sign_info['msg']
            );
        }
        //数据入库
        FaDaDaUserModel::create()->where('customer_id', $ent_customer_id)->update([
            'signature_id' => $ent_sign_id
        ]);
        return [$ent_sign_id,$errorData];
    }

    /**
     * 法人照片上传
     * @param $people_customer_id
     * @param $arr
     * @return array|void
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    private function personalSign($people_customer_id,$arr){
        $personal_sign_info = FaDaDaUserModel::create()
            ->where('customer_id', $people_customer_id)
            ->get();
        if(!empty($personal_sign_info->getAttr('signature_id'))){
            return $personal_sign_info->getAttr('signature_id');
        }
        $personal_sign_id = '';
        $errorData = '';
        $this->timestamp = Carbon::now()->format('YmdHis');
        $personal_sign_info = $this->uploadSignature([
            'customer_id' => $people_customer_id,
            'signature_img_base64' => $this->getPersonalSignBase64($arr),
        ]);
        //log
        //CommonService::getInstance()->log4PHP($personal_sign_info,'info','personal_sign_info');
        if ($personal_sign_info['code'] === 200) {
            $personal_sign_id = $personal_sign_info['result']['signature_id'];
        } else {
            $errorData = $this->createReturn(
                $personal_sign_info['code'], null, $personal_sign_info['result'], $personal_sign_info['msg']
            );
        }
        //数据入库
        FaDaDaUserModel::create()->where('customer_id', $people_customer_id)->update([
            'signature_id' => $personal_sign_id
        ]);

        return [$personal_sign_id,$errorData];
    }

    /**
     * 获取人图片base64数据
     * @param $arr
     * @return string
     */
    private function getPersonalSignBase64($arr){
        $cc = new SealService();
        $path = TEMP_FILE_PATH.'personal.png';
        $cc::personalSeal($path,$arr['entName']);
        return base64_encode(file_get_contents($path));
    }

    /**
     * 获取签章图片base64数据
     * @param $arr
     * @return string
     */
    private function getEntSignBase64($arr){
        $num = $arr['socialCredit'];
        $num_arr = str_split($num);
        $num = implode('', array_reverse($num_arr));
        $path = TEMP_FILE_PATH.'qianzhang.png';
        $cc = new SealService($arr['entName'], $num, 200);
        $cc->saveImg($path, "");
        //缩小图片
        $path = $cc->scaleImg($path, 170, 170);
        return base64_encode(file_get_contents($path));
    }

    /**
     * 注册账号
     * @param string $openId
     * @param string $accountType
     * @return array|mixed|string[]
     */
    private function getRegister(string $openId, string $accountType)
    {
        $url_ext = 'account_register.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $accountType . $openId));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'open_id' => $openId,
            'account_type' => $accountType,
        ];
        CommonService::getInstance()->log4PHP($post_data,'info','account_register_param_arr');
        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 实名信息哈希存证
     * @param array $arr
     * @return array|mixed|string[]
     */
    private function getHashDeposit(array $arr)
    {
        $url_ext = 'hash_deposit.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1(
            $this->app_secret .
            $arr['cert_flag'] .
            $arr['customer_id'] .
            $arr['file_name'] .
            $arr['file_size'] .
            $arr['noper_time'] .
            $arr['original_sha256'] .
            $arr['preservation_desc'] .
            $arr['preservation_name'] .
            $arr['transaction_id']
        ));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'customer_id' => $arr['customer_id'],//客户编号 注册账号时返回
            'preservation_name' => $arr['preservation_name'],//存证名称
            'preservation_desc' => $arr['preservation_desc'],//存证描述
            'file_name' => $arr['file_name'],//文件名 字符 len <= 50
            'noper_time' => $arr['noper_time'],//文件最后修改时间
            'file_size' => $arr['file_size'],//文件大小
            'original_sha256' => $arr['original_sha256'],//文件hash值 sha256算法
            'transaction_id' => $arr['transaction_id'],//交易号 字符 len <= 32
            'cert_flag' => $arr['cert_flag'],//是否认证成功后自动申请编号证书 默认参数值为0 不申请 参数值为1 自动申请
        ];
        CommonService::getInstance()->log4PHP($post_data,'info','hash_deposit_param_arr');

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 自定义印章
     * @param array $arr
     * @return array|mixed|string[]
     */
    private function getCustomSignature(array $arr)
    {
        $url_ext = 'custom_signature.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['content'] . $arr['customer_id']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'customer_id' => $arr['customer_id'],
            'content' => $arr['content'],
        ];

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 模板上传
     * @param array $arr
     * @return array|mixed|string[]
     */
    private function uploadTemplate(array $arr)
    {
        $url_ext = 'uploadtemplate.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['template_id']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'template_id' => $arr['template_id'],
            'doc_url' => "https://api.meirixindong.com/Static/AuthBookModel/fdd_template.pdf",
        ];
        CommonService::getInstance()->log4PHP($post_data,'info','uploadtemplate_param_arr');
        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);
        CommonService::getInstance()->log4PHP($resp,'info','uploadTemplate');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 印章上传
     * @param array $arr
     * @return array|mixed|string[]
     */
    private function uploadSignature(array $arr)
    {
        $url_ext = 'add_signature.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['customer_id'] . $arr['signature_img_base64']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'customer_id' => $arr['customer_id'],
            'signature_img_base64' => $arr['signature_img_base64'],
        ];
        CommonService::getInstance()->log4PHP($post_data,'info','add_signature_param_arr');

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 模板填充
     * @param array $arr
     * @return array|mixed|string[]
     */
    private function fillTemplate(array $arr)
    {
        $url_ext = 'generate_contract.api';
        $arr['parameter_map'] = [
            'entName' => $arr['entName'],
            'companyName' => $arr['entName'],
            'taxNo' => $arr['socialCredit'],
            'newTaxNo' => $arr['socialCredit'],
            'signName' => '',
            'phoneNo' => $arr['phone'],
            'region' => $arr['region'],
            'address' => $arr['address'],
            'date' => date('Y-m-d H:i:s',time())
        ];
        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        ksort($arr['parameter_map']);

        $arr['parameter_map'] = (string)jsonEncode($arr['parameter_map'], false);

        $section_2 = strtoupper(sha1(
                $this->app_secret . $arr['template_id'] . $arr['contract_id'])
        ) . $arr['parameter_map'];

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
//            'doc_title' => $arr['doc_title'],//非必填
            'template_id' => $arr['template_id'],//模版ID 本地自增
            'contract_id' => $arr['contract_id'],//合同编号
//            'font_size' => $arr['font_size'],//字体大小
//            'font_type' => $arr['font_type'],//字体类型 0-宋体；1-仿宋；2-黑体；3-楷体；4-微软雅黑
//            'fill_type' => $arr['fill_type'],//填充类型 0 pdf 模板、1 在线填充模板
            'parameter_map' => $arr['parameter_map'],//填充内容 json key val
        ];
        CommonService::getInstance()->log4PHP($post_data,'info','generate_contract_param_arr');
        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);
        CommonService::getInstance()->log4PHP($resp,'info','generate_contract');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 自动签署
     * @param array $arr
     * @return array|mixed|string[]
     */
    private function getExtsignAuto(array $arr,$signature_id,$x,$y)
    {
        $url_ext = 'extsign_auto.api';
        //交易号 每次请求视为一个交易。 只允许长度<=32 的英文或数字字符。 交易号为接入平台生成，必须保证唯一并自行记录
        $transaction_id = control::getUuid();
        $section_1 = $this->app_id . strtoupper(md5($transaction_id.$this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['customer_id']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'transaction_id' => $transaction_id,
            'contract_id' => $arr['contract_id'],
            'customer_id' => $arr['customer_id'],
            'doc_title' => $arr['doc_title'],
            'position_type' => 1,//定位类型 0-关键字（默认） 1-坐标
//            'sign_keyword' => $arr['sign_keyword'],//定位关键字 关键字为文档中的文字内容（能被ctrl+f查找功能检索到）
//            'keyword_strategy' => $arr['keyword_strategy'],//0 所有关键字签章 1 第一个关键字签章 2 最后一个关键字签章
            'signature_id' => $signature_id,
//            'signature_show_time' => $arr['signature_show_time'],//时间戳显示方式 1 显示 2 不显示
            'signature_positions'=>'[{"pagenum":0,"x":'.$x.',"y":'.$y.'}]',
        ];
        CommonService::getInstance()->log4PHP($post_data,'info','extsign_auto_param_arr');

        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $url_ext, $post_data, $this->getHeader('form'), ['enableSSL' => true]);
        CommonService::getInstance()->log4PHP($resp,'info','extsign_auto');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 合同下载
     * @param $arr
     * @return array|mixed|string[]
     */
    private function downLoadContract($arr){
        $url_ext = 'downLoadContract.api';

        $section_1 = $this->app_id . strtoupper(md5($this->timestamp));

        $section_2 = strtoupper(sha1($this->app_secret . $arr['contract_id']));

        $section_3 = strtoupper(sha1($section_1 . $section_2));

        $msg_digest = base64_encode($section_3);

        $post_data = [
            'app_id' => $this->app_id,
            'timestamp' => $this->timestamp,
            'v' => '2.0',
            'msg_digest' => $msg_digest,
            'contract_id' => $arr['contract_id'],
        ];
        CommonService::getInstance()->log4PHP($post_data,'info','downLoadContract_arr');
        $resp = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->needJsonDecode(false)
            ->send(
                $this->url . $url_ext,
                $post_data,
                $this->getHeader('form'),
                ['enableSSL' => true],
                'GET'
            );
        $path = Carbon::now()->format('Ymd') . DIRECTORY_SEPARATOR;
        is_dir(INV_AUTH_PATH . $path) || mkdir(INV_AUTH_PATH . $path, 0755);
        $filename = $arr['contract_id'];
        $path = INV_AUTH_PATH . $path . $filename;
        //储存pdf
        file_put_contents(
            $path,
            $resp,
            FILE_APPEND | LOCK_EX
        );
        CommonService::getInstance()->log4PHP($path,'info','downLoadContract');
        return $path;
    }
}
