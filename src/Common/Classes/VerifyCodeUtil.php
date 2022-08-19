<?php

namespace Kyzone\EsUtility\Common\Classes;

use EasySwoole\Utility\Random;

class VerifyCodeUtil
{
    const VERIFY_CODE_TTL = 300;
    const VERIFY_CODE_LENGTH = 4;


    public static function getVerifyCode()
    {

        // 配置验证码
        $config = new  \EasySwoole\VerifyCode\Conf();
        $code = new \EasySwoole\VerifyCode\VerifyCode($config);

        // 获取随机数(即验证码的具体值)
        $random = Random::character(self::VERIFY_CODE_LENGTH, '1234567890abcdefghijklmnopqrstuvwxyz');

        // 绘制验证码
        $code = $code->DrawCode($random);

        // 获取验证码的 base64 编码及设置验证码有效时间
        $time = time();
        return [
            'code' => $code->getImageBase64(), // 得到绘制验证码的 base64 编码字符串
            'time' => $time,
            'random' => $random,
            'expire' => $time + self::VERIFY_CODE_TTL,
        ];
    }

    // 校验验证码
    public static function checkVerifyCode($code, $time, $hash)
    {
        if ($time + self::VERIFY_CODE_TTL < time()) {
            return false;
        }
        $code = strtolower($code);
        return self::getVerifyCodeHash($code, $time) == $hash;
    }

    // 生成验证码 hash 字符串
    public static function getVerifyCodeHash($code, $time)
    {
        return md5($code . $time);
    }
}