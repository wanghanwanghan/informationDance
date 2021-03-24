<?php

namespace App\HttpController\Service\User;

use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\control;

class UserService extends ServiceBase
{
    use Singleton;

    //创建一个api请求token
    function createAccessToken($phone, $password): string
    {
        $str = "{$phone}-{$password}-" . time();

        return control::aesEncode($str, CreateConf::getInstance()->getConf('env.salt'));
    }

    //解token
    function decodeAccessToken($token): array
    {
        $str = control::aesDecode($token, CreateConf::getInstance()->getConf('env.salt'));

        return explode('-', $str);
    }

    //获取用户信息
    function getUserInfo($phone = ''): ?array
    {
        try {

            $userInfo = User::create()->alias('user')
                ->join('information_dance_wallet as wallet', 'wallet.phone = user.phone');

            (!empty($phone) && is_numeric($phone)) ? $userInfo->where('user.phone', $phone) : null;

            $userInfo = obj2Arr($userInfo->all());

            empty($userInfo) ?: $userInfo = current($userInfo);

            unset($userInfo['token']);
            unset($userInfo['password']);

        } catch (\Throwable $e) {
            $userInfo = null;
            $this->writeErr($e, __FUNCTION__);
        }

        return empty($userInfo) ? null : $userInfo;
    }

    //获取用户关注企业列表
    function getUserSupervisorEnt($phone = ''): ?array
    {
        if (!is_numeric($phone) || strlen($phone) != 11) return null;

        $info = null;

        try {
            $info = SupervisorPhoneEntName::create()->where('phone', $phone)->all();
            $info = obj2Arr($info);
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
        }

        return $info;
    }

}
