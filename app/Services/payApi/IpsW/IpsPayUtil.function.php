<?php
namespace App\Services\payApi\IpsW;

use App\Services\CLogFileHandler;
use App\Services\Log;

// 初始化日志
$logHandler = new CLogFileHandler(storage_path() . "/pay_logs/func" . date('Y-m-d') . '.log');
$log = Log::Init($logHandler, 15);
class IpsWFun
{
    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * @param $merCode 商戶號
     * return 签名结果
     */
    public function md5Sign($prestr, $key)
    {
        $prestr = $prestr . $key;
        return md5($prestr);
    }
    /**
     * 验证签名
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $merCode 商戶號
     * @param $key 私钥
     * return 签名结果
     */
    public function md5Verify($prestr, $sign, $key)
    {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * php截取<body>和</body>之間字符串
     * @param string $begin 开始字符串
     * @param string $end 结束字符串
     * @param string $str 需要截取的字符串
     * @return string
     */
    public function subStrXml($begin, $end, $str)
    {
        $b = (strpos($str, $begin));
        $c = (strpos($str, $end));

        return substr($str, $b, $c - $b + strlen($end));
    }
    /**
     * 对象转数组
     * @param unknown $array
     * @return array
     */
    public function object_array($array)
    {
        if (is_object($array)) {
            $array = (array) $array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = object_array($value);
            }
        }
        return $array;
    }
}
