<?php
namespace App\Services\payApi\SD;

class SDFunc
{
    protected $pub_key_path;
    protected $pri_key_path;
    protected $cert_pwd;
    public function __construct($pub_key_path = '', $pri_key_path = '', $cert_pwd = '')
    {
        $this->pub_key_path = $pub_key_path;
        $this->pri_key_path = $pri_key_path;
        $this->cert_pwd = $cert_pwd;
    }
    /**
     *获取公钥
     *@param  [$path]
     *@return [mixed]
     *@throws [\Exception]
     */
    public function loadX509Cert($path = '')
    {
        $path = $path ? $path : $this->pub_key_path;
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadx509Cert::file_get_contents ERROR');
            }

            $cert = chunk_split(base64_encode($file), 64, "\n");
            $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";

            $res = openssl_pkey_get_public($cert);
            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);

            if (!$detail) {
                throw new \Exception('loadX509Cert::openssl_pkey_get_details ERROR');
            }

            return $detail['key'];
        } catch (\Exception $e) {
            throw $e;
        }
    }
    /**
     * 获取私钥
     * @param  [$path]
     * @param  [$pwd]
     * @return [mixed]
     * @throws [\Exception]
     */
    public function loadPk12Cert($path = '', $pwd = '')
    {
        $path = $path ? $path : $this->pri_key_path;
        $pwd = $pwd ? $pwd : $this->cert_pwd;
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadPk12Cert::file
					_get_contents');
            }

            if (!openssl_pkcs12_read($file, $cert, $pwd)) {
                throw new \Exception('loadPk12Cert::openssl_pkcs12_read ERROR');
            }
            return $cert['pkey'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 私钥签名
     * @param [$plainText]
     * @param [$path]
     * @return [string]
     * @throws [\Exception]
     */
    public function sign($plainText, $path = '')
    {
        $path = $path ? $path : $this->loadPk12Cert();
        $plainText = json_encode($plainText);
        try {
            $resource = openssl_pkey_get_private($path);
            $result = openssl_sign($plainText, $sign, $resource);
            openssl_free_key($resource);

            if (!$result) {
                throw new \Exception('签名出错' . $plainText);
            }

            return base64_encode($sign);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 公钥验签
     * @param  [$plainText[]
     * @param  [$sign[]
     * @return [int]
     * @throws [\Exception]
     */
    public function verify($plainText, $sign, $path = '')
    {
        $path = $path ? $path : $this->loadX509Cert();
        $resource = openssl_pkey_get_public($path);
        $result = openssl_verify($plainText, base64_decode($sign), $resource);
        openssl_free_key($resource);

        if (!$result) {

            throw new \Exception('签名验证未通过,plainText:' . $plainText . '。sign:' . $sign, '02002');

        }

        return $result;
    }

    /**
     * 对数组变量进行JSON编码，为了（本系统的PHP版本为5.3.0）解决PHP5.4.0以上才支持的JSON_UNESCAPED_UNICODE参数
     * @param mixed array 待编码的 array （除了resource 类型之外，可以为任何数据类型，改函数只能接受 UTF-8 编码的数据）
     * @return string （返回 array 值的 JSON 形式）
     * @author
     * @d/t     2017-07-17
     */
    public function json_encodes($array)
    {

        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $str = json_encode($array);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function ($matchs) {
                return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
            }, $str);
            return $str;
        } else {
            return json_encode($array, 320);
        }
    }

    /**
     * 分割字符串
     * @param String $str  要分割的字符串
     * @param int $length  指定的长度
     * @param String $end  在分割后的字符串块追加的内容
     */
    public function mb_chunk_split($string, $length, $end, $once = false)
    {
        $string = iconv('gb2312', 'utf-8//ignore', $string);
        $array = array();
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, $length, "utf-8");
            if ($once) {
                return $array[0] . $end;
            }

            $string = mb_substr($string, $length, $strlen, "utf-8");
            $strlen = mb_strlen($string);
        }
        $str = implode($end, $array);
        return $str . '%0A';
    }

    public function mb_array_chunk($arr)
    {

        $credential = json_decode($arr['body']['credential'], true);
        $credential['params']['orig'] = mb_chunk_split($credential['params']['orig'], 76, '%0A');
        $credential['params']['sign'] = mb_chunk_split($credential['params']['sign'], 76, '%0A');
        $arr['body']['credential'] = str_replace(array('==', '+', '='), array('%3D%3D', '%2B', '%3D'), json_encodes($credential));

        return json_encodes($arr);

    }
}
