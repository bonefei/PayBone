<?php
error_reporting(0);
$para_temp = $_POST;

if (!$para_temp) {
    exit("未知错误！请重试。");
}
//提交参数

$data = array(
    'head' => array(
        'version' => $_POST['version'],
        'method' => $_POST['method'],
        'productId' => $_POST['productId'],
        'accessType' => $_POST['accessType'],
        'mid' => $_POST['mid'],
        'channelType' => $_POST['channelType'],
        'reqTime' => $_POST['reqTime'],
    ),
    'body' => array(
        'orderCode' => $_POST['orderCode'],
        'totalAmount' => $_POST['totalAmount'],
        'subject' => $_POST['subject'],
        'body' => $_POST['body'],
        'txnTimeOut' => $_POST['txnTimeOut'],
        'payMode' => $_POST['payMode'],
        'payExtra' => $_POST['payMode'] == 'sand_h5' ? json_encode(array('cardNo' => $_POST['cardNo'])) : json_encode(array('payType' => $_POST['payType'], 'bankCode' => $_POST['bankCode'])),
        'clientIp' => $_POST['clientIp'],
        'notifyUrl' => $_POST['notifyUrl'],
        'frontUrl' => $_POST['frontUrl'],
        'extend' => '',
    ),
);

$post = array(
    'charset' => 'utf-8',
    'signType' => '01',
    'data' => json_encode($data),
    'sign' => $_POST['sign'],
);

$result = http_post_json($_POST['action'], $post);

parse_str(urldecode($result), $arr);
ob_clean();
$arr['data'] = str_replace(array("  ", "\t", "\n", "\r", '\\"', '\\\"'), array('', '', '', '', '"', '\\"'), $arr['data']);

$data = json_decode($arr['data'], true);

$credential = json_decode($data['body']['credential'], true);

if (isset($credential['params']['orig']) && isset($credential['params']['sign'])) {

    $arr['data'] = mb_array_chunk($data);

} else {

    $data['body']['credential'] = json_encodes($credential);

    //使用第二参数JSON_UNESCAPED_UNICODE,阻止json_encode()转译汉字
    $arr['data'] = str_replace(array("\\\/", "\\/", "\/", " "), array("/", "/", "/", "+"), json_encodes($data));

}

$arr['sign'] = preg_replace('/\s/', '+', $arr['sign']);

//$path = dirname(__FILE__).'\public_key.cer';

//验签
//$Arr = verify($arr['data'], $arr['sign'],$_POST['publickey']);
$data = json_decode($arr['data'], 320);

if ($data['head']['respCode'] == "000000") {
    $credential = str_replace(array('"{', '}"'), array('{', '}'), stripslashes($data['body']['credential']));
} else {
    print_r($arr['data']);
}

/**
 * PHP发送Json对象数据
 *
 * @param $url 请求url
 * @param $jsonStr 发送的json字符串
 * @return string
 */
function http_post_json($url, $param)
{

    if (empty($url) || empty($param)) {
        return false;
    }
    $param = http_build_query($param);
    $ch = curl_init(); //初始化curl
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //正式环境时解开注释
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $data = curl_exec($ch); //运行curl
    curl_close($ch);

    if (!$data) {
        //throw new \Exception('请求出错');
        echo '请求出错';
    }

    return $data;

}

/**
 * 公钥验签
 *@param  [$plainText[]
 *@param  [$sign[]
 *@return [int]
 *@throws [\Exception]
 */
function verify($plainText, $sign, $path)
{
    $resource = openssl_pkey_get_public($path);
    $result = openssl_verify($plainText, base64_decode($sign), $resource);
    openssl_free_key($resource);

    if (!$result) {
        return '签名验证未通过';
        //throw new \Exception('签名验证未通过,plainText:'.$plainText.'。sign:'.$sign,'02002');

    }

    return $result;
}

/**
 * 分割字符串
 * @param String $str  要分割的字符串
 * @param int $length  指定的长度
 * @param String $end  在分割后的字符串块追加的内容
 */
function mb_chunk_split($string, $length, $end, $once = false)
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

function mb_array_chunk($arr)
{

    $credential = json_decode($arr['body']['credential'], true);
    $credential['params']['orig'] = mb_chunk_split($credential['params']['orig'], 76, '%0A');
    $credential['params']['sign'] = mb_chunk_split($credential['params']['sign'], 76, '%0A');
    $arr['body']['credential'] = str_replace(array('==', '+', '='), array('%3D%3D', '%2B', '%3D'), json_encodes($credential));

    return json_encodes($arr);

}
function match_pack($matchs)
{
    return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
}
/**
 * 对数组变量进行JSON编码，为了（本系统的PHP版本为5.3.0）解决PHP5.4.0以上才支持的JSON_UNESCAPED_UNICODE参数
 *@param mixed array 待编码的 array （除了resource 类型之外，可以为任何数据类型，改函数只能接受 UTF-8 编码的数据）
 *@return  string （返回 array 值的 JSON 形式）
 *@author
 * @d/t     2017-07-17
 */
function json_encodes($array)
{

    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        $str = json_encode($array);
        $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i",
            "match_pack", $str);
        return $str;
    } else {
        return json_encode($array, 320);
    }
}

?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="renderer" content="webkit" />
    <title>Insert title here</title>
    <script type="text/javascript" src="./scripts/paymentjs.js"></script>
    <script type="text/javascript" src="./scripts/jquery-1.7.2.min.js"></script>
</head>
<body>
<script>
    function wap_pay() {
        var responseText = $("#credential").text();
        console.log(responseText);
        paymentjs.createPayment(responseText, function(result, err) {
            console.log(result);
            console.log(err.msg);
            console.log(err.extra);
        });
    }
</script>

<div style="display: none" >
    <p id="credential"><?php echo $credential; ?></p>
</div>
</body>

<script>
    window.onload=function(){
        wap_pay();
    };
</script>
</html>