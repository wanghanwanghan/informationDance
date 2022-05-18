<?php

namespace App\HttpController\Service\DianZiqian;

use App\HttpController\Models\Api\DianZiQianAuth;
use App\HttpController\Models\Api\FaDaDa\FaDaDaUserModel;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateSeal\SealService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;
use wanghanwanghan\someUtils\moudles\resp\create;

class DianZiQianService extends ServiceBase
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

        $this->url        = 'https://sandbox.letsign.com';
        $this->app_code   = 'E7094079418854802183';
        $this->app_secret = '6%T8s0h!cSx4^M$7vb0Xjr5e75r6n18NxGuK1V7$942e7*2&2G64d7#3#^8x4G44';

//        if (strtolower($type) === 'test') {
//            $this->url = 'https://testapi.fadada.com:8443/api/';
//            $this->app_id = CreateConf::getInstance()->getConf('fadada.app_id_test');
//            $this->app_secret = CreateConf::getInstance()->getConf('fadada.app_secret_test');
//        }

        $this->timestamp      = Carbon::now()->format('YmdHis');
        $this->curl_use_cache = false;

        return true;
    }

    public function getAuthFile($postData)
    {
        //创建个人签署人
        $signerPersonres    = $this->signerPerson($postData);
        $signerCodePersonal = $signerPersonres['result']['signerCode'] ?? "";
        if ($signerPersonres['code'] != 200) return $signerPersonres;

        //创建企业签署人
        $signerEnterprise  = $this->signerEnterprise($postData);
        $signerCodeEnt    = $signerEnterprise['result']['signerCode'] ?? "";
        if ($signerEnterprise['code'] != 200) return $signerEnterprise;
        //生成png
        $getPng = $this->getPng($postData);
        if ($getPng['code'] != 200) return $getPng;
        //法人照片上传
        list($personalSealCode, $errorData) = $this->personalSign($signerCodePersonal, $postData);
        if (!empty($errorData)) return $errorData;

        //企业上传印章
        list($entSealCode, $errorData) = $this->entSign($signerCodeEnt, $postData);
        if (!empty($errorData)) return $errorData;

        //上传adobe模版
        $contractFileTemplate = $this->contractFileTemplate();
        $contractTemplateCode = $contractFileTemplate['result']['contractTemplateCode'] ?? "";
        if ($contractFileTemplate['code'] != 200) return $contractFileTemplate;

        //使用模板创建合同
        $contractFileTemplateFilling = $this->contractFileTemplateFilling($postData, $contractTemplateCode);
        $contractCode                = $contractFileTemplateFilling['result']['contractCode'] ?? "";
        if ($contractFileTemplateFilling['code'] != 200) return $contractFileTemplateFilling;

        $entTransactionCode = control::getUuid();
        //自动签署企业章  signUrl
        $entContractSignUrl = $this->contractSignAuto($signerCodeEnt, $contractCode, '企业盖章处',$entSealCode,$entTransactionCode);
        if ($entContractSignUrl['code'] != 200) return $entContractSignUrl;
        $personalTransactionCode = control::getUuid();
        //自动签署企业法人章
        $personalContractSignUrl = $this->contractSignAuto($signerCodePersonal, $contractCode, '法人盖章处',$personalSealCode,$personalTransactionCode);
        if ($personalContractSignUrl['code'] != 200) return $personalContractSignUrl;
        $insertData = [
            'entName' => $postData['entName'],
            "personName"   => $postData['legalPerson'],
            "personIdCard" => $postData['idCard'],
            'socialCredit' => $postData['socialCredit'],
            'signerCodePersonal' => $signerCodePersonal,
            'signerCodeEnt' => $signerCodeEnt,
            'contractTemplateCode' => $contractTemplateCode,
            'contractCode' => $contractCode,
            'entSealCode' => $entSealCode,
            'personalSealCode' => $personalSealCode,
            'entTransactionCode' => $entTransactionCode,
            'personalTransactionCode' => $personalTransactionCode
        ];
        DianZiQianAuth::create()->data($insertData)->save();
        return $this->createReturn(200, null, [], '成功');
    }

    public function getUrl(){
        $data = DianZiQianAuth::create()->where("entUrlResultCode < 1 or personalUrlResultCode < 1")->all();
        if(empty($data)){
            return $this->createReturn(200, null, [], '没有需要查询的数据');
        }
        foreach (json_decode(json_encode($data), true) as $v){
            $contractCode = $v['contractCode'];
            $entTransactionCode = $v['entTransactionCode'];
            $personalTransactionCode = $v['personalTransactionCode'];
            $flag = true;
            //企业章签署状态查询
            if($v['entUrlResultCode'] < 1) {
                $contractSignStatus = $this->contractSignStatus($entTransactionCode);
                if ($contractSignStatus['code'] != 200) {
                    dingAlarm('企业章签署状态查询异常', ['$contractSignStatus' => json_encode($contractSignStatus)]);
                    continue;
                }
                $this->updateDianZiQianEntResultCode($v['id'], $contractSignStatus);
                if($contractSignStatus['result']['resultCode'] < 1) $flag = false;
            }
            //法人章签署状态查询
            if($v['personalUrlResultCode'] < 1) {
                $contractSignStatus = $this->contractSignStatus($personalTransactionCode);
                if ($contractSignStatus['code'] != 200) {
                    dingAlarm('法人章签署状态查询异常', ['$contractSignStatus' => json_encode($contractSignStatus)]);
                    continue;
                }
                $this->updateDianZiQianPersonalResultCode($v['id'], $contractSignStatus);
                if($contractSignStatus['result']['resultCode'] < 1) $flag = false;
            }
            $url = '';
            if($contractSignStatus['result']['resultCode'] >0){
                $url = $contractSignStatus['result']['downloadUrl'];
            }
            if($flag){
                //合同归档
                $contractOptArchive = $this->contractOptArchive($contractCode);
                if ($contractOptArchive['code'] != 200) {
                    dingAlarm('合同归档',['$contractSignStatus'=>json_encode($contractSignStatus)]);
                    continue;
                }
                //合同文件下载
                $contractFileDownload = $this->contractFileDownload($contractCode);
                $downloadUrl = $contractFileDownload['result']['downloadUrl']??'';
                if ($contractFileDownload['code'] != 200) {
                    dingAlarm('合同文件下载',['$contractFileDownload'=>json_encode($contractFileDownload)]);
                }
            }
        }

        return $this->createReturn(200, null, ['pdfUrl'=>$url,'contractFileDownload'=>$downloadUrl], '成功');
    }

    private function updateDianZiQianEntResultCode($id,$data){
        $update['entUrlResultCode'] = $data['result']['resultCode'];
        if($update['resultCode'] == 1){
            $update['entDownloadUrl'] = $data['result']['downloadUrl'];
            $update['entViewPdfUrl'] = $data['result']['viewPdfUrl'];
        }
        return DianZiQianAuth::create()->where("id={$id}")->update($update);
    }
    private function updateDianZiQianPersonalResultCode($id,$data){
        $update['personalUrlResultCode'] = $data['result']['resultCode'];
        if($update['resultCode'] == 1){
            $update['personalDownloadUrl'] = $data['result']['downloadUrl'];
            $update['personalViewPdfUrl'] = $data['result']['viewPdfUrl'];
        }
        return DianZiQianAuth::create()->where("id={$id}")->update($update);
    }

    /**
     * 签署状态查询
     */
    public function contractSignStatus($transactionCode){
        $path      = "/open-api-lite/contract/sign/status";
        $paramData = [
            'transactionCode' => $transactionCode
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $param,$resp], 'info', 'contractSignStatus');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

//    private function
    /**
     * 合同文件下载
     */
    private function contractFileDownload($contractCode){
        $path  = "/open-api-lite/contract/file/download";
        $param = $this->buildParam(['contractCode' => $contractCode], $path);
        $resp  = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'signerPerson');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 合同归档
     */
    private function contractOptArchive($contractCode)
    {
        $path  = "/open-api-lite/contract/opt/archive";
        $param = $this->buildParam(['contractCode' => $contractCode], $path);
        $resp  = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'signerPerson');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 上传adobe模版
     */
    private function contractFileTemplate()
    {
        $path  = "/open-api-lite/contract/file/template";
        $file  = STATIC_PATH . "AuthBookModel/test.pdf";
        $param = $this->buildParam([], $path, ['fileName' => $file, 'key' => 'templateFile']);
        $resp  = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('file'));
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'signerPerson');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 使用模板创建合同
     */
    private function contractFileTemplateFilling($arr, $contractTemplateCode)
    {
        $path      = "/open-api-lite/contract/file/template/filling";
        $params    = [
            'entName'     => $arr['entName'] ?? '',
            'companyName' => $arr['entName'] ?? '',
            'taxNo'       => $arr['socialCredit'] ?? '',
            'newTaxNo'    => $arr['socialCredit'] ?? '',
            'signName'    => '',
            'phoneNo'     => $arr['phone'] ?? '',
            'region'      => $arr['city'] ?? '',
            'address'     => $arr['regAddress'] ?? '',
            'date'        => date('Y年m月d日', time())
        ];
        $paramData = [
            'params'               => json_encode($params),
            'contractTemplateCode' => $contractTemplateCode
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'signerPerson');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 创建企业签署人
     */
    private function signerEnterprise($postData)
    {
        $path      = '/open-api-lite/signer/enterprise';
        $paramData = array(
            "entQualificationType" => '1',
            'entName'              => $postData['entName'],
            'entQualificationNum'  => $postData['socialCredit'],
            'corporateName'        => $postData['legalPerson'],
            'personIdType'         => '0',
            "personName"           => $postData['legalPerson'],
            "personIdCard"         => $postData['idCard'],
            'roleType'             => '1'
        );
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('file'));
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'signerPerson');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 创建签署人
     */
    public function signerPerson($postData)
    {
        $path      = '/open-api-lite/signer/person';
        $paramData = array(
            "personIdType" => "0",
            "personName"   => $postData['legalPerson'],
            "personIdCard" => $postData['idCard']
        );
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('file'));
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'signerPerson');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 创建合同
     */
    public function contractFile()
    {
        $path  = '/open-api-lite/contract/file';
        $file  = STATIC_PATH . "AuthBookModel/dx_template.pdf";
        $param = $this->buildParam([], $path, ['fileName' => $file, 'key' => 'contractFile']);
        $resp  = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'contractFile');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 企业上传印章
     */
    private function entSign($signerCode, $arr)
    {
        $ent_sealCode  = '';
        $ent_sign_info = $this->sealBase64([
                                               'signerCode'      => $signerCode,
                                               'sealName'        => '企业章',
                                               'sealImageBase64' => $this->getEntSignBase64($arr),
                                           ]);
        $error_data    = '';
        if ($ent_sign_info['code'] === 200) {
            $ent_sealCode = $ent_sign_info['result']['sealCode'];
        } else {
            $error_data = $ent_sign_info;
        }
        return [$ent_sealCode, $error_data];
    }

    /**
     * 法人照片上传
     */
    private function personalSign($signerCode, $arr)
    {
        $personal_sign_info = $this->sealBase64([
            'signerCode'      => $signerCode,
            'sealName'        => '法人章',
            'sealImageBase64' => $this->getPersonalSignBase64($arr),
        ]);
        $personal_sealCode  = '';
        $error_data         = '';
        if ($personal_sign_info['code'] === 200) {
            $personal_sealCode = $personal_sign_info['result']['sealCode'];
        } else {
            $error_data = $personal_sign_info;
        }
        return [$personal_sealCode, $error_data];
    }

    /**
     * 上传自定义签章（Base64格式） #
     */
    public function sealBase64($postData)
    {
        $path      = '/open-api-lite/seal/base64';
        $paramData = [
            'signerCode'      => $postData['signerCode'],
            'sealName'        => $postData['sealName'],
            'sealImageBase64' => $postData['sealImageBase64'],
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        $rr = $this->doCurl($param,$this->url . $path);
        CommonService::getInstance()->log4PHP([$this->url . $path, $param,$rr], 'info', 'sealBase64');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 获取签章图片base64数据
     * @param $arr
     * @return string
     */
    private function getEntSignBase64($arr)
    {
        $path    = TEMP_FILE_PATH . 'dianziqian_ent.png';
        return base64_encode(file_get_contents($path));
    }


    /**
     * 获取人图片base64数据
     * @param $arr
     * @return string
     */
    private function getPersonalSignBase64($arr)
    {
        $path = TEMP_FILE_PATH . 'dianziqian_personal.png';
        return base64_encode(file_get_contents($path));
    }

    /**
     * 自动签署
     */
    public function contractSignAuto($signerCode, $contractCode, $keyWord,$sealCode,$transactionCode){
        $path            = '/open-api-lite/contract/sign/auto';
        $paramData = [
            'signerCode'      => $signerCode,
            'autoSignAuthorization' => '1',
            'contractCode'    => $contractCode,
            'sealCode' => $sealCode,
            'keyWord'         => $keyWord,
            'transactionCode' => $transactionCode
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'contractFile');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }
    /**
     * 手动签署
     */
    public function contractSignUrl($signerCode, $contractCode, $keyWord)
    {
        $path            = '/open-api-lite/contract/sign/url';
        $transactionCode = control::getUuid();;
        $paramData = [
            'signerCode'      => $signerCode,
            'contractCode'    => $contractCode,
            'keyWord'         => $keyWord,
            'signTypeLimits'  => '99',
            'signValidMethod' => '0',
            'transactionCode' => $transactionCode
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $param], 'info', 'contractFile');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 计算签名
     * @param $data
     * @param $path
     * @return String
     */
    public function makeSign($data, $path)
    {
        // 业务方密钥
        $appSecret = '6%T8s0h!cSx4^M$7vb0Xjr5e75r6n18NxGuK1V7$942e7*2&2G64d7#3#^8x4G44';
        //签名步骤一：按字典序排序参数
        ksort($data);
        $String = $this->toUrlParams($data, $path);
        //签名步骤二：拼接secret
        //签名步骤三：sha1加密
        return sha1($String . $appSecret);
    }

    /**
     * @param $data
     * @param $path
     * @return String
     */
    public function toUrlParams($data, $path)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v;
            }
        }
        $buff .= $path;
        return md5($buff);
    }

    /**
     * @param $path
     * @return array
     */
    public function buildParam($param, $path, $file = []): array
    {
        // 业务方AppCode
        $param["appCode"]   = "E7094079418854802183";
        $param["version"]   = "v1";
        $param["timestamp"] = time();
        $token              = $this->makeSign($param, $path);
        if (!empty($file)) {
            $f                   = curl_file_create($file['fileName']);
            $param[$file['key']] = $f;
        }
        $param["token"] = $token;
        return $param;
    }

    /**
     * 发送请求
     * @param $param
     * @param $header
     * @param $url
     */
    function doCurl($param, $url)
    {
        $header = [
            "Content-Type:application/json",
        ];
        $ch     = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output, true);
return $output;
//        dd($output);
    }

    private function getHeader(string $type = ''): array
    {
        switch (strtolower($type)) {
            case 'json':
                return ['Content-Type' => 'application/json;'];
            case 'file':
                return ['Content-Type' => 'multipart/form-data;'];
            default:
                return [];
        }
    }

    private function checkResp(array $res): array
    {
        if (isset($res['description']) && $res['description'] === 'success') {
            $code = 200;
        } else {
            $code = $res['code'] - 0;
        }

        $paging = null;

        $result = $res['data'] ?? null;

        $msg = $res['description'] ?? null;

        return $this->createReturn($code, $paging, $result, $msg);
    }

    /**
     * 制作企业印章图片
     */
    private function sealEntDraw($postData){
        $path      = '/open-api/seal/ent/draw';
        $paramData = [
            'nonTransparentPercent'      => 0,
            'sealName'        => $postData['sealName'],
            'downText' => $postData['socialCredit'],
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        $rr = $this->doCurl($param,$this->url . $path);
        CommonService::getInstance()->log4PHP([$this->url . $path, $param,$rr], 'info', 'sealEntDraw');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 制作个人签名图片
     */
    private function sealPersonDraw($postData){
        $path      = '/open-api/seal/person/draw';
        $paramData = [
            'nonTransparentPercent'      => 0,
            'sealName'        => $postData['legalPerson'],
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        $rr = $this->doCurl($param,$this->url . $path);
        CommonService::getInstance()->log4PHP([$this->url . $path, $param,$rr], 'info', 'sealEntDraw');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }
    /*
    * 通过fileCode获取文件
    */
    private function fileQuery($fileCode){
        $path      = '/open-api/file/query';
        $paramData = [
            'fileCode'      => $fileCode,
        ];
        $param     = $this->buildParam($paramData, $path);
        $resp      = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path, $param, '', '', 'GET');
        $rr = $this->doCurl($param,$this->url . $path);
        CommonService::getInstance()->log4PHP([$this->url . $path, $param,$rr], 'info', 'fileQuery');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 生成png
     */
    private function getPng($postData){
        $sealEntDraw = $this->sealEntDraw($postData);
        if ($sealEntDraw['code'] != 200) return $sealEntDraw;
        $fileCodeEnt = $sealEntDraw['result']['sealFileCode'];
        $fileQueryEnt = $this->fileQuery($fileCodeEnt);
        if ($fileQueryEnt['code'] != 200) return $fileQueryEnt;
        $entPngPath = TEMP_FILE_PATH . 'dianziqian_ent.png';
        file_put_contents($entPngPath,$fileQueryEnt['result']['contentType'],true);
        $sealPersonDraw = $this->sealPersonDraw($postData);
        if ($sealPersonDraw['code'] != 200) return $sealPersonDraw;
        $fileCodePerson = $sealPersonDraw['result']['sealFileCode'];
        $fileQueryPerson = $this->fileQuery($fileCodePerson);
        if ($fileQueryPerson['code'] != 200) return $fileQueryPerson;
        $personalPngPath = TEMP_FILE_PATH . 'dianziqian_personal.png';
        file_put_contents($personalPngPath,$fileQueryEnt['result']['contentType'],true);
        return $this->createReturn(200, null, [], '成功');
    }

}