<?php
namespace App\Services\payApi;

use App\Services\payApi\PaySubmit;
use DB;

class PayCommon
{
    public function __construct()
    {

    }

    public function getPayApiConf($name, $zoroCode)
    {
        $config = config('payConfig.conf.' . $name);
        return $config;
    }

    public function getPayApiInfo($name)
    {
        $conf = config('payConfig.Info.' . $name);
        return $conf;
    }
    /**
     * @param name[string] the name of payway
     * @param param[array] the parameters of paysubmit
     * @param notksort[string] true=>ksort,false=>!ksort
     * @return newArray
     */
    public function param_merge($name, $param, $notksort = false)
    {
        $conf = $this->getPayApiInfo($name);
        $param_arr = array_merge($conf, $param);
        if (!$notksort) {
            ksort($param_arr);
        }

        return $param_arr;
    }
    /**
     * 建立请求，以表单HTML形式构造
     *
     * @param $para_temp 请求参数数组
     * @param $action 提交地址
     * @param $method 提交方式。两个值可选：post、get
     *            默认post
     * @return 提交表单HTML文本
     */
    public function buildRequestForm($para_temp, $action, $method = 'post', $redirect_url = '', $type = '')
    {
        if (empty($redirect_url) && $type != 'QfbPay') {
            $sHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
            $sHtml .= "<form id='paysubmit' name='paysubmit'  action='" . $action . "' method='" . $method . "'>";
            foreach ($para_temp as $key => $value) {
                $sHtml .= "<input type='hidden' name='" . $key . "' id='" . $key . "' value='" . $value . "'/>";
            }
            // submit按钮控件请不要含有name属性
            $sHtml = $sHtml . "<input type='submit' value='' style='display:none;'></form>";
            $sHtml = $sHtml . "loading ..";

            //$sHtml = $sHtml . "<script>document.forms['paysubmit'].submit();</script>";
            // echo var_export($sHtml,true);
            // exit;
            return $sHtml;
        } else {
            if ($type == 'QfbPay') {
                $postdata = $para_temp;
                $opts = array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => 'Content-type:text/plain;charset=utf-8',
                        'content' => $postdata,
                    ),
                );
                $context = stream_context_create($opts);
                $result = file_get_contents($action, false, $context);
                if (isset(explode('=', explode('&', $result)[1])[1])) {
                    $Base = base64_decode(explode('=', explode('&', $result)[1])[1]);
                    $Form = json_decode($Base, true);
                    $Html = '<html>' . $Form['rescontext'] . '</html>';
                } else {
                    $Html = array('Status' => 'E003', 'content' => $result);
                }

                return $Html;
            } else if ($type == 'SDPay' || $type == 'XPay') {
                $sHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
                $sHtml .= "<form id='paysubmit' name='paysubmit' action='" . $redirect_url . "' method='" . $method . "'>";
                $sHtml .= "<input type='text' name='action' id='action' value='" . $action . "' style='display:none;' />";
                foreach ($para_temp as $k => $v) {
                    $sHtml .= "<input type='hidden' name='" . $k . "' id='" . $k . "' value='" . $v . "'/>";
                }
                $sHtml .= "<input type='submit' value='' style='display:none;'></form>";
                // $sHtml .= "loading ..";
                $sHtml .= "<script>document.forms['paysubmit'].submit();</script>";
                return $sHtml;
            } else {
                // 中转方式
                $sHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
                $sHtml .= "<form id='paysubmit' name='paysubmit'   action='" . $redirect_url . "' method='" . $method . "'>";
                $sHtml .= "<input type='hidden' name='action' id='action' value='" . $action . "'/>";
                $sHtml .= "<input type='hidden' name='para_temp' id='para_temp' value='" . json_encode($para_temp) . "'/>";
                $sHtml = $sHtml . "<input type='submit' value='' style='display:none;'></form>";
                $sHtml = $sHtml . "loading ..";
                $sHtml = $sHtml . "<script>document.forms['paysubmit'].submit();</script>";
                return $sHtml;
            }

        }
    }

    // 智付使用的加密函数
    // 字符串转换成十六进制
    public function StrToHex($string)
    {
        $hex = "";
        for ($i = 0; $i < strlen($string); $i++) {
            $hex .= dechex(ord($string[$i]));
        }

        $hex = strtoupper($hex);
        return $hex;
    }

    // 十六进制转换成字符串
    public function HexToStr($hex)
    {
        $string = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }

        return $string;
    }

    // 字符串混合
    public function MixStr($paramString1, $paramString2)
    {
        $i = 0;
        $str3 = "";
        for ($j = 0; $j < floor(strlen($paramString1) / 2); $j++) {
            $str1 = substr($paramString1, $j * 2, 2);
            if ($i >= floor(strlen($paramString2) / 2)) {
                $i = 0;
            }

            $str2 = substr($paramString2, $i * 2, 2);
            $str3 = $str3 . $str1 . $str2;
            $i++;
        }
        return $str3;
    }

    // 字符串反转
    public function RevStr($paramString)
    {
        $str1 = "";
        for ($i = 0; $i < floor(strlen($paramString) / 2); $i++) {
            $str2 = substr($paramString, $i * 2, 2);
            $str3 = substr($str2, 0, 1);
            $str4 = substr($str2, 1, 1);
            $str1 = $str1 . $str4 . $str3;
        }
        return $str1;
    }

    // 取出加密密钥
    public function ExtKey($paramString)
    {
        $i = 1;
        $str = "";
        for ($j = 0; $j <= floor(strlen($paramString) / 4); $j++) {
            if (($i * 2 + 2) > strlen($paramString)) {
                break;
            }

            $str = $str . substr($paramString, $i * 2, 2);
            $i += 2;
        }
        return $str;
    }

    // 取出加密内容
    public function ExtStr($paramString)
    {
        $i = 0;
        $str = "";
        for ($j = 0; $j <= floor(strlen($paramString) / 4); $j++) {
            if (($i * 2 + 2) > strlen($paramString)) {
                break;
            }

            $str = $str . substr($paramString, $i * 2, 2);
            $i += 2;
        }
        return $str;
    }

    // 加密主函数
    public function StrEncrypt($paramString1, $paramString2)
    {
        $str1 = $this->StrToHex($paramString1);
        $str2 = strtoupper(md5($paramString2));
        return $this->RevStr($this->MixStr($str1, $str2));
    }

    // 解密主函数
    public function StrDecrypt($paramString1, $paramString2)
    {
        $str1 = strtoupper($this->ExtKey($this->RevStr($paramString1)));
        $str2 = strtoupper(md5($paramString2));
        $pla = strpos($str1, $str2);
        if (!($pla === false)) {
            if ($pla == 0) {
                return $this->HexToStr($this->ExtStr($this->RevStr($paramString1)));
            }
        }
        return "";
    }
    /**
     * 获取某些字段信息
     * @param payname 通道名称
     * @param zoroCode 商户ID
     * @param fields 想要获取的字段
     * @return key
     */
    public static function getKey($payname, $zoroCode, $fields)
    {
        $is_arr = false;
        if ($fields == 'version') {
            $fields = 'b.version';
        }

        if (strpos($fields, ',')) {
            $is_arr = true;
        }

        $keySql = DB::table('zoro_pay_way as a')->join('zoro_pay_product as b', 'a.pay_product_code', 'b.product_code')
            ->where('a.pay_way_code', $zoroCode)
            ->where('b.product_name', $payname);

        if (!$is_arr) {
            $key = $keySql->value($fields);
        } else {
            $key = $keySql->select(DB::raw($fields))->first();
        }
        if ($key == null || $key == 'null') {
            $key = '';
        }

        return $key;
    }
    /**
     * 获取配置信息
     * @param payname 支付通道名称
     * @param zoroCode 商户ID
     * @param payConfig 基本配置信息
     * @return NewPayConfig
     */
    public function getPayConf($payname, $zoroCode, $payConfig)
    {
        $payconf = DB::table('zoro_pay_way as a')->join('zoro_pay_product as b', 'a.pay_product_code', 'b.product_code')
            ->where('a.pay_way_code', $zoroCode)
            ->where('b.product_name', $payname)
            ->select(DB::Raw('b.MerCert,b.MerCode,b.Account,b.PostUrl,b.FailUrl,b.FailUrl,b.ErrorUrl,b.ServerUrl,b.remark,b.RedirectUrl,b.s_url,b.r_url,b.order_pfx'))
        //->select('b.MerCert','b.MerCode','b.Account','b.PostUrl','b.FailUrl','b.FailUrl','b.ErrorUrl','b.ServerUrl','b.remark','b.RedirectUrl')
            ->first();
        $config = is_set_arr($payConfig, $payconf);
        $config = $this->param_merge($payname, object2array($payconf));
        return $config;
    }
    /**
     * 白名单
     * @param ip
     * @param mechart
     * @return boll
     */
    public function WhiteNames($ip = '', $merchant = '')
    {

        $white = DB::table('zoro_enable_rule')->where('Type', 1)->first();
        if ($white && !empty($white)) {
            if (checkExcept($ip, $white->Ips)) {
                return true;
            }
            if (checkExcept($merchant, $white->zoroCode)) {
                return true;
            }
        }
        return false;
    }
    /**
     * 黑名单过滤
     * @param keywords 敏感词
     * @param ip ip地址
     * @param mechart 商户
     * @return bool
     */
    public function BlackNames($keywords, $ip = '', $merchant = '')
    {

        $keywords = strtolower($keywords);
        $blackNames = DB::table('zoro_disabled_rule')
            ->where('Type', 1)
            ->first();
        if ($blackNames && !empty($blackNames)) {
            if (checkExcept($keywords, $blackNames->KeyWords, true)) {
                return array('status' => 'K01', 'msg' => 'keywords');
            }

        }

        $white = $this->WhiteNames($ip, $merchant);
        if ($white === true) {
            //敏感词过滤通过后检查ip和商户白名单
            return true;
        }
        if ($blackNames && !empty($blackNames)) {
            if (checkExcept($ip, $blackNames->Ips)) {
                return array('status' => 'K02', 'msg' => 'ip');
            }
            if (checkExcept($merchant, $blackNames->zoroCode)) {
                return array('status' => 'K03', 'msg' => 'merchant');
            }
        }
        return true;
    }
    /**
     * Executes request
     *
     * @param       string      $url                Url for payment method
     * @param       array       $requestFields      Request data fields
     *
     * @return      array                           Host response fields
     *
     * @throws      RuntimeException                Error while executing request
     */
    public function sendRequest($url, array $requestFields)
    {
        $curl = curl_init($url);

        curl_setopt_array($curl, array(
            CURLOPT_HEADER => 0,
            CURLOPT_USERAGENT => 'Zotapay-Client/1.0',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_RETURNTRANSFER => 1,
        ));

        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($requestFields));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            $error_message = 'Error occurred: ' . curl_error($curl);
            $error_code = curl_errno($curl);
        } elseif (curl_getinfo($curl, CURLINFO_HTTP_CODE) != 200) {
            $error_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error_message = "Error occurred. HTTP code: '{$error_code}'";
        }

        curl_close($curl);

        if (!empty($error_message)) {
            exit($error_message);
            //throw new RuntimeException($error_message, $error_code);
        }

        if (empty($response)) {
            exit($response);
            //throw new RuntimeException('Host response is empty');
        }

        $responseFields = array();

        parse_str($response, $responseFields);

        return $responseFields;
    }
    /**
     * 订单记录插入数据库
     * @param array
     * @return
     */
    public static function insertRecord($param)
    {
        $data = $param;
        // dump($data);die;
        //     //order-num 订单号规定不大于30位
        $time = gmdate('Y-m-d', time() + 8 * 3600);
        $date = gmdate('YmdHis', time() + 8 * 3600);
        $BillDate = gmdate('Ymd', time() + 8 * 3600);
        // $Billno = 'ips_' . $date . mt_rand(100000, 999999);
        $order_num = '';
        $payname = $data['payname'];
        $bankName = $data['bankName'];
        $clientIp = $data['clientIp'];
        $fromUri = $data['fromUri'];
        unset($data['payname']);
        unset($data['bankName']);
        $action = (new self())->getKey($payname, $data['zoroCode'], 'PostUrl');
        //构造要请求的参数数组
        //unset($data['clientIp']);
        unset($data['fromUri']);
        $paySubmit = new PaySubmit($payname, $action, $BillDate, $date, $data);
        $html_text = ($paySubmit)->getPayForm();
        $insertParam = [
            'version' => '1',
            'create_time' => gmdate('Y-m-d H:i:s', time() + 8 * 3600),
            'merchant_order_no' => $data['Billno'],
            'merchant_no' => $data['zoroCode'],
            'creater' => 'ZoroPay',
            'trx_no' => ($paySubmit)->getTrxBillNo(),
            'pay_way_name' => $payname,
            'payer_fee' => 0,
            'payer_pay_amount' => sprintf('%.2f', $data['Amount']),
            'status' => 0,
            'product_name' => 'Deposit',
            'merchant_name' => '商家名称',
            'payer_name' => '付款人名称',
            'payer_user_no' => 0,
            'payer_pay_amount' => sprintf('%.2f', $data['Amount']),
            'payer_fee' => 0.00,
            'order_ip' => $clientIp,
            'order_referer_url' => $fromUri,
            'pay_type_name' => $bankName,
            'return_url' => (isset($data['r_url']) && $data['r_url']) ? $data['r_url'] : '',
            'notify_url' => (isset($data['s_url']) && $data['s_url']) ? $data['s_url'] : '',
            'fee_rate' => isset($data['f_rate']) ? ($data['f_rate'] ? $data['f_rate'] : 1) : 1,
            'order_amount' => isset($data['o_amount']) ? ($data['o_amount'] ? $data['o_amount'] : 0.00) : 0.00,
        ];
        // DB::table('zoro_trade_payment_record')->insert($param);
        $id = DB::table('zoro_trade_payment_record')->where('merchant_order_no', $data['Billno'])
            ->count();
        if ($id > 0) {
            return false;
        }
        $now_balance = DB::table('zoro_trade_payment_record')
            ->where(['pay_way_name' => $payname, 'status' => 2])
            ->value(DB::raw('sum(payer_pay_amount)'));
        //var_dump($now_balance);die;
        if (!$now_balance) {
            $now_balance = 0.00;
        }

        $insertParam['now_balance'] = $now_balance;
        $insert = DB::table('zoro_trade_payment_record')->insertGetId($insertParam);
        if ($insert > 0) {
            return $html_text;
        }
        return false;
    }
}
