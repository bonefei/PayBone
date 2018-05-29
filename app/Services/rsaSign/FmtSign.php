<?php
namespace App\Services\rsaSign;
class FmtSign{
    /**
     * 生成RSA算法的MAC值
     *
     * @param string $source 生成MAC值原文
     * @param string $pkcs12path 商户证书
     * @param string $password 证书私钥
     * @return string 消息摘要
     */
    public static function RSASign($source, $pkcs12path, $password)
    {
        $certs = array();
        $fd = fopen($pkcs12path, 'r');
        $p12buf = fread($fd, filesize($pkcs12path));
        fclose($fd);
        if (openssl_pkcs12_read($p12buf, $certs, $password)) {
            $pkeyid = openssl_pkey_get_private($certs['pkey']);
            $signature = "";
            openssl_sign($source, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
            openssl_free_key($pkeyid);
            return self::asc2hex($signature);
        }
        return null; //TODO: RSA加验签的异常处理
    }
    public static function asc2hex($str)
    {
        return chunk_split(bin2hex($str), 2, '');
    }

    public static function hex2asc($str)
    {
        $len = strlen($str);
        $data = "";
        for ($i = 0; $i < $len; $i += 2) {
            $data .= chr(hexdec(substr($str, $i, 2)));
        }

        return $data;
    }
    public static function pem2der($pem_data)
    {
        $begin = "CERTIFICATE-----";
        $end = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin) + strlen($begin));
        $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    }

    public static function der2pem($der_data)
    {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }
}