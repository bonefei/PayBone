<?php
namespace App\Services\payApi\Sun8Pay;

class SunPay
{
    /**
     * AES-128-CBC加密
     * @param $params
     * @param $key
     * @return string
     */
    public function aesEncrypt($params,$key){
        $str = $this->arrayToString($params);
        $method = 'AES-128-CBC';
        $base64 = openssl_encrypt($str, $method, $key);
        return $base64;
    }

    /**
     * AES-128-CBC解密
     * @param $string
     * @param $key
     * @return array
     */
    public function aesDecrypt($string,$key){
        $method = 'AES-128-CBC';
        $base64 = openssl_decrypt($string, $method, $key);
        $params = $this->stringToArray($base64);
        return $params;
    }

    /**
     * AES-256-CBC加密，需要用到iv
     * @param $params
     * @param $key
     * @return string
     */
    public function aesEncryptWithIV($params, $key){
        $str = $this->arrayToString($params);
        $method = 'aes-256-cbc';
        $iv = substr($key,0,16);
        $base64 = openssl_encrypt($str, $method, $key,0,$iv);
        return $base64;
    }

    /**
     * AES-256-CBC解密，需要用到iv
     * @param $string
     * @param $key
     * @return array
     */
    public function aesDecryptWithIV($string, $key){
        $method = 'aes-256-cbc';
        $iv = substr($key,0,16);
        $base64 = openssl_decrypt($string,$method,$key,0,$iv);
        $params = $this->stringToArray($base64);
        return $params;
    }

    /**
     * 拼接url参数形式字符串
     * @param $params
     * @return string
     */
    public function arrayToString($params){
        $string = '';
        ksort($params);
        foreach ($params as $k => $v) {
            $string .= $k.'='.$v.'&';
        }
        $string = rtrim($string,'&');
        return $string;
    }

    /**
     * url字符转化成array
     * @param $string
     * @return array
     */
    public function stringToArray($string){
        $array = explode('&',$string);
        $params = [];
        foreach ($array as $v){
            $temp = explode('=',$v);
            $params[$temp[0]] = $temp[1];
        }
        return $params;
    }
}
