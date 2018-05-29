<?php
namespace App\Services\payApi\IpsNew;

use App\Services\CLogFileHandler;
use App\Services\Log;

// 初始化日志
$logHandler = new CLogFileHandler(storage_path() . "/pay_logs/" . date('Y-m-d') . '.log');
$log = Log::Init($logHandler, 15);
class IpsFun
{

    /**
     * 签名字符串
     *
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * @param $merCode 商戶號
     *            return 签名结果
     */
    public static function md5Sign($prestr, $merCode, $key)
    {
        $prestr = $prestr . $merCode . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     *
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $merCode 商戶號
     * @param $key 私钥
     *            return 签名结果
     */
    public static function md5Verify($prestr, $sign, $merCode, $key)
    {
        $prestr = $prestr . $merCode . $key;
        $mysgin = md5($prestr);

        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * 验证签名
     *
     * @param $prestr 需要签名的字符串
     *
     * @param $sign 签名结果
     *            return 签名结果
     *
     *
     */
    public static function rsaVerify($prestr, $sign, $rsaPubKey)
    {
        try {

            $signBase64 = base64_decode($sign);
            Log::INFO("=========1111111=========:" . $signBase64);
            $public_key = file_get_contents('rsa_public_key.pem');

            $pkeyid = openssl_get_publickey($public_key);
            if ($pkeyid) {

                $verify = openssl_verify($prestr, $signBase64, $pkeyid);

                openssl_free_key($pkeyid);
            }
            Log::INFO("==================:" . openssl_error_string());
            if ($verify == 1) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            Log::ERROR("rsaVerify异常:" . $e);
        }
        return false;
    }
    /**
     * php截取<body>和</body>之間字符串
     * @param string $begin 开始字符串
     * @param string $end 结束字符串
     * @param string $str 需要截取的字符串
     * @return string
     */
    public static function subStrXml($begin, $end, $str)
    {
        $b = (strpos($str, $begin));
        $c = (strpos($str, $end));

        return substr($str, $b, $c - $b + 7);
    }
}
