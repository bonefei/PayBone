<?php
namespace App\Services\payApi;

use App\Services\payApi\IpsNew\IpsPaySubmit;
use App\Services\payApi\PayCommon;
use App\Services\payApi\SD\SDFunc;
use App\Services\payApi\Sun8Pay\SunPay;
use App\Services\payApi\YinLian\sdk\AcpService;
use DB;
use Illuminate\Http\Request;
use App\Services\payApi\PayFunc;

class PaySubmit
{
    /**
     * payname 支付接口名称 如ips ipsv7 GfbPay 对应payConfig中的键值
     */
    private $payname;
    /**
     * payname对应的配置参数 数组形式
     */
    private $para_temp;
    /**
     * 老接口需要的参数 form提交地址
     */
    private $action;
    /**
     * data 商户提交的数据
     */
    private $data;
    /**
     * 订单日期
     */
    private $BillDate;
    /**
     * 日期
     */
    private $date;
    /**
     * 环迅最新接口配置 与老接口配置区分
     */
    // private $ipspay_config;
    /**
     * 通用接口配置
     */
    private $payConfig;
    private $paycommon;
    private $zoroCode;
    private $bill;
    public function __construct($payname, $action = '', $BillDate, $date, $data)
    {
        $this->BillDate = $BillDate;
        $this->date = $date;
        $this->payname = $payname;
        $this->action = $action;

        // $this->ipspay_config = config('ipsConfig.Mer');
        $this->payConfig = config('payConfig.Info.' . $payname);
        $this->paycommon = new PayCommon();
        $this->zoroCode = $data['zoroCode'];
        $this->data = $data;
        unset($data['zoroCode']);
        self::getPayParam();
    }
    /**
     * 生成提交表单
     */
    public function getPayForm()
    {
        $paycommon = $this->paycommon;
        $this->payConfig['RedirectUrl'] = PayCommon::getKey($this->payname, $this->zoroCode, 'RedirectUrl');
        // $parameter = $paycommon->param_merge($this->payname, $this->para_temp);
        if ($this->payname != 'ipsv7') {
            // dump($this->para_temp, $this->action, 'post', $this->payConfig['RedirectUrl'], $this->payname);die;
            return $paycommon->buildRequestForm($this->para_temp, $this->action, 'post', $this->payConfig['RedirectUrl'], $this->payname);
        } else {
            $this->paycommon = $paycommon = new IpsPaySubmit($this->payConfig);
            return $paycommon->buildRequestFormRedirect($this->para_temp);
        }
    }
    /**
     * 获取对应支付接口名称的配置参数
     */
    protected function getPayParam()
    {
        $this->payConfig = $this->paycommon->getPayConf($this->payname, $this->zoroCode, $this->payConfig);
        $billno = $this->makeBill();
        $this->bill = $billno;
        $payFnName = $this->payname;
        $parameter = self::$payFnName($billno);
        $this->para_temp = $parameter;
    }
    protected function makeBill()
    {
        $isOrder = PayCommon::getKey($this->payname, $this->zoroCode, 'isOrder');
        $bill = '';
        if (1 == $isOrder) {
            //使用客户传过来的订单号
            $bill = $this->data['Billno'];
        } else if (2 == $isOrder) {
            //生成新的订单号
            $bill = $this->payConfig['order_pfx'] . '.' . $this->date . mt_rand(100000, 999999);
        }
        //查重
        $count = DB::table('zoro_trade_payment_record')->where(['merchant_order_no' => $bill])->count();
        if ($count > 0) {
            $this->makeBill();
        }
        return $bill;
    }
    public function getBillNo()
    {
        return $this->bill;
    }
    public function getTrxBillNo()
    {
        return $this->payConfig['order_pfx'] . '.' . $this->date . mt_rand(100000, 999999);
    }
    private function ips($billno)
    {
        $parameter = [
            "Mer_key" => $this->payConfig['MerCert'],
            "Mer_code" => $this->payConfig['MerCode'],
            "Billno" => $billno, //订单号
            "Amount" => '' . sprintf('%.2f', $this->data['Amount']), //金额
            "Date" => $this->BillDate, //订单日期
            "Attach" => $this->data['r_url'] . '-|' . $this->data['s_url'], //spilit(-|) 0 -return_url 1- server_url
            "DispAmount" => '' . sprintf('%.2f', $this->data['Amount']), //显示金额
            'DoCredit' => $this->isCredit(),
            'Bankco' => $this->data['bankCode'] == '0000' ? '' : $this->data['bankCode'],
            'SignMD5' => '',
        ];
        $parameter = $this->paycommon->param_merge($this->payname, $parameter);
        $parameter = $this->FormatParam($parameter);
        $orge = 'billno' . $parameter['Billno'] . 'currencytype' . $parameter['Currency_Type'] . 'amount' . $parameter['Amount'] . 'date' . $parameter['Date'] . 'orderencodetype' . $parameter['OrderEncodeType'] . $parameter['Mer_key'];
        $parameter['SignMD5'] = md5($orge);
        // dump($parameter);die;
        return $parameter;
    }
    private function ipsv7($billno)
    {
        $parameter = array(
            "MerCert" => $this->payConfig['MerCert'],
            "MerCode" => $this->payConfig['MerCode'],
            "MerBillNo" => $billno,
            'Account' => '' . $this->data['Account'],
            "Date" => $this->BillDate,
            "ReqDate" => $this->date,
            "Amount" => '' . sprintf('%.2f', $this->data['Amount']),
            "Attach" => $this->data['r_url'] . '-|' . $this->data['s_url'], //spilit(-|) 0 -return_url 1- server_url
            "BillEXP" => 2,
            "GoodsName" => 'Deposit',
            "IsCredit" => $this->isCredit(),
            "BankCode" => $this->data['bankCode'] == '0000' ? '' : $this->data['bankCode'],
        );
        $parameter = $this->paycommon->param_merge($this->payname, $parameter);
        $parameter = $this->FormatParam($parameter);
        return $parameter;
    }
    private function GfbPay($billno)
    {
        $parameter = [
            'merchantID' => $this->payConfig['MerCode'], //签约国付宝商户唯一用户 ID
            'merOrderNum' => $billno, //订单号
            'tranAmt' => '' . sprintf('%.2f', $this->data['Amount']), //交易金额
            'tranDateTime' => $this->date, //交易时间
            'virCardNoIn' => '' . $this->data['Account'], //国付宝转入账户
            'merRemark1' => $this->data['r_url'] . '-|' . $this->data['s_url'], //商户备用信息字段
            'gopayServerTime' => file_get_contents('https://gateway.gopay.com.cn/time.do'), //服务器时间
            'bankCode' => $this->data['bankCode'] == '0000' ? '' : $this->data['bankCode'], //银行
            'userType' => '1', //用户类型 1 个人网银 2 企业网银
        ];
        $parameter = $this->paycommon->param_merge($this->payname, $parameter);
        $parameter = $this->FormatParam($parameter);
        $signStr = 'version=[' . $parameter['version'] . ']tranCode=[' . $parameter['tranCode'] . ']merchantID=['
            . $parameter['merchantID'] . ']merOrderNum=[' . $parameter['merOrderNum'] . ']tranAmt=['
            . $parameter['tranAmt'] . ']feeAmt=[' . $parameter['feeAmt'] . ']tranDateTime=[' . $parameter['tranDateTime']
            . ']frontMerUrl=[' . $parameter['frontMerUrl'] . ']backgroundMerUrl=[' . $parameter['backgroundMerUrl']
            . ']orderId=[]gopayOutOrderId=[]tranIP=[' . $parameter['tranIP'] . ']respCode=[]gopayServerTime=['
            . $parameter['gopayServerTime'] . ']VerficationCode=[pdm123456789]';
        $parameter['signValue'] = md5($signStr);
        return $parameter;
    }
    private function QfbPay($billno)
    {
        $parameter = [
            "orderNo" => $billno, //orderNo 合作方订单号
            "bizcode" => isset($this->data['bizcode']) ? $this->data['bizcode'] : '3103', //业务编码
            "memberNo" => $this->payConfig['MerCode'], //商户号
            "transAmt" => '' . sprintf('%.2f', $this->data['Amount']), //订单金额
            "bankcode" => $this->data['bankCode'] == '0000' ? '' : $this->data['bankCode'], //银行简称
            "cardTyp" => '1', //银行卡类型0:贷记卡，1：借记卡
            "showUrl" => '', //跳转地址
        ];
        $parameter = $this->paycommon->param_merge($this->payname, $parameter);
        $parameter = $this->FormatParam($parameter);
        $MD5key = $this->payConfig['MerCert'];
        $jsonData = json_encode($parameter);

        $dt['partnerCode'] = $this->payConfig['remark']; //合作机构编码
        $dt['encryptData'] = base64_encode($jsonData);
        $dt['signData'] = md5($jsonData . $MD5key);

        $postdata = http_build_query($dt);
        return $postdata;
    }
    private function Sun8Pay($billno)
    {
        $parameter = array(
            'paycode' => isset($this->data['bizcode']) ? $this->data['bizcode'] : '301', //业务编码
            'merId' => $this->payConfig['MerCode'], //商户号
            'orderNo' => $billno,
            'payAmt' => '' . sprintf('%.2f', $this->data['Amount']), //订单金额
            'productName' => 'Deposit',
            'bankCode' => $this->data['bankCode'] == '0000' ? '' : $this->data['bankCode'], //银行简称
        );
        $parameter = $this->paycommon->param_merge($this->payname, $parameter);
        $parameter = $this->FormatParam($parameter);
        $Key = new SunPay();
        $res = $Key->aesEncryptWithIV($parameter, $this->payConfig['MerCert']);
        $base64_mid = base64_encode($parameter['merId']);
        $data['version'] = $this->payConfig['version'];
        $data['data'] = $base64_mid . '||' . $res;
        return $data;
    }
    /**
     * 银联支付
     * @param billno
     * @return parameter
     */
    private function YinLian($billno)
    {
        $parameter = array(
            'version' => PayCommon::getKey($this->payname, $this->zoroCode, 'version'), //版本号
            'frontUrl' => '', //前台通知地址
            'backUrl' => '', //后台通知地址
            'signMethod' => '01', //签名方法
            //TODO 以下信息需要填写
            'merId' => $this->payConfig['MerCode'], //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId' => $billno, //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime' => $this->date, //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => sprintf('%.2f', $this->data['Amount']) * 100, //交易金额，单位分，此处默认取demo演示页面传递的参数
            // 订单超时时间。
            // 超过此时间后，除网银交易外，其他交易银联系统会拒绝受理，提示超时。 跳转银行网银交易如果超时后交易成功，会自动退款，大约5个工作日金额返还到持卡人账户。
            // 此时间建议取支付时的北京时间加15分钟。
            // 超过超时时间调查询接口应答origRespCode不是A6或者00的就可以判断为失败。
            'payTimeout' => gmdate('YmdHis', time() + 8 * 3600 + 15 * 60),
        );
        $cert_path = storage_path() . PayCommon::getKey($this->payname, $this->zoroCode, 'signCertPath');
        $cert_pwd = PayCommon::getKey($this->payname, $this->zoroCode, 'signCertPwd');

        $parameter = $this->paycommon->param_merge($this->payname, $parameter);
        $parameter = $this->FormatParam($parameter);
        $sign = AcpService::sign($parameter, $cert_path, $cert_pwd);

        if ($sign !== false) {
            // var_dump($sign,$parameter);die;
            return $parameter;
        }
        var_dump($sign, $parameter);die;
        //$uri = SDKConfig::getSDKConfig()->frontTransUrl;
        //$html_form = AcpService::createAutoFormHtml( $params, $uri );
    }
    /**
     * RPNPay
     */
    private function RPay($billno)
    {
        $parameter = array(
            "version" => PayCommon::getKey($this->payname, $this->zoroCode, 'version'), //版本号
            "sign_type" => 'MD5',
            "mid" => $this->payConfig['MerCode'],
            "return_url" => '',
            "notify_url" => '',
            "order_id" => $billno,
            "order_amount" => (int) (sprintf('%.2f', $this->data['Amount']) * 100),
            "order_time" => gmdate('YmdHis', time() + 8 * 3600 + 15 * 60),
            "bank_id" => $this->data['bankCode'] == '0000' ? '' : $this->data['bankCode'],
        );
        $parameter = $this->paycommon->param_merge($this->payname, $parameter, true);
        $parameter = $this->FormatParam($parameter);
        $TheTail = '';
        if (!empty($parameter['bank_id'])) {
            $TheTail = '|bank_id=' . $parameter['bank_id'] . '|key=' . $this->payConfig['MerCert'];
        } else {
            $TheTail = '|key=' . $this->payConfig['MerCert'];
        }

        $UrlDecode = 'version=' . $parameter['version'] . '|sign_type='
            . $parameter['sign_type'] . '|mid=' . $parameter['mid'] . '|return_url='
            . $parameter['return_url'] . '|notify_url=' . $parameter['notify_url'] . '|order_id='
            . $parameter['order_id'] . '|order_amount=' . $parameter['order_amount'] . '|order_time='
            . $parameter['order_time'] . $TheTail;
        $parameter['signature'] = md5($UrlDecode);
        // echo $UrlDecode;
        // dump($parameter);die;
        return $parameter;
    }
    private function ZotaPay($billno)
    {
        $parameter = array(
            'client_orderid' => $billno,
            'amount' => sprintf('%.2f', $this->data['Amount']),
            'ipaddress' => Request::server('REMOTE_ADDR'),
            'site_url' => Request::server('HTTP_HOST'),
        );
        $parameter = $this->paycommon->param_merge($this->payname, $parameter, true);
        $parameter = $this->FormatParam($parameter);
        $Rsa = sha1($this->payConfig['remark'] . $parameter['client_orderid'] . $parameter['amount'] * 100 . $parameter['email'] . $this->payConfig['MerCert']);

        $parameter['control'] = $Rsa;
        $responseArr = $this->paycommon->sendRequest(PayCommon::getKey($this->payname, $this->zoroCode, 'PostUrl') . $this->payConfig['MerCode'], $parameter);
        if (isset($responseArr['error-code'])) {
            //TODO 输出报错信息
        }
        return $responseArr;
    }
    /**
     * 杉德支付
     */
    private function SDPay($billno)
    {
        $parameter = array(
            'mid' => PayCommon::getKey($this->payname, $this->zoroCode, 'MerCode'),
            'reqTime' => gmdate('YmdHis', time() + 8 * 3600),
            'orderCode' => $billno,
            'productId' => $this->data['bankCode'] == '0000' ? '00000008' : '00000007',
            'totalAmount' => $this->SDMoney(sprintf('%.2f', $this->data['Amount'])),
            'subject' => 'test',
            'body' => 'testBody',
            'txnTimeOut' => gmdate('YmdHis', time() + 8 * 3600 + 15 * 60),
            'payMode' => $this->data['bankCode'] == '0000' ? 'sand_h5' : 'bank_pc',
            'payExtra' => [],
            'payType' => '1',
            'bankCode' => $this->data['bankCode'] == '0000' ? '' : $this->data['bankCode'],
            'cardNo' => '',
            'clientIp' => $this->data['clientIp'],
        );
        $parameter = $this->paycommon->param_merge($this->payname, $parameter, true);
        $parameter = $this->FormatParam($parameter);
        $this->action = PayCommon::getKey($this->payname, $this->zoroCode, 'PostUrl') . '/order/pay';
        $parameter['sign'] = $this->SDSign($parameter)['sign'];
        $parameter['publickey'] = $this->SDSign($parameter)['publickey'];
        unset($parameter['payExtra']);
        return $parameter;
    }
    /**
     * 翔丰支付
     */
    private function XPay($billno)
    {
        $time = time() + 8 * 3600 + 15 * 60;
        $parameter = [
            'requestNo' => md5($time),
            'version' => 'V2.0',
            'productId' => '1004',
            'transId' => '20',
            'merNo' => PayCommon::getKey($this->payname, $this->zoroCode, 'MerCode'),
            'orderDate' => $time,
            'orderNo' => $billno,
            'notifyUrl' => '',
            'returnUrl' => '',
            'transAmt' => (int) (sprintf('%.2f', $this->data['Amount']) * 100),
            //'userId' => gmdate('YmdHis', time() + 8 * 3600 + 15 * 60),
            'commodityName' => 'Product',
            'commodityDesc' => 'Product',
        ];
        $parameter = $this->paycommon->param_merge($this->payname, $parameter, true);
        //TODO 重新定义$this->FormatParam()方法，读取数据库
        $parameter = $this->FormatParam($parameter);
        $parameter['notifyUrl'] = PayCommon::getKey($this->payname, $this->zoroCode, 'ServerUrl');
        $parameter['returnUrl'] = PayCommon::getKey($this->payname, $this->zoroCode, 'ServerUrl');
        //$parameter['action'] = PayCommon::getKey($this->payname, $this->zoroCode, 'PostUrl');
        ksort($parameter);
        $param = (new PayFunc())->spliceUrl($parameter);
        $sign = $this->XSign($param);
        $post = array_replace($parameter, ['signature' => $sign]);
        //$parameter['signature'] = $sign;
        return $post;
    }
    protected function XSign($param)
    {
        $pri_key_path = storage_path() . PayCommon::getKey($this->payname, $this->zoroCode, 'PriPath');
        $privateKey = openssl_pkey_get_private(file_get_contents($pri_key_path));
        $encrypted = "";
        openssl_sign($param, $encrypted, $privateKey);
        $signature = base64_encode($encrypted); //返回加密串
        $signature = str_replace("+", "%2B", $signature);
        return $signature;
    }
    /**
     * 杉德支付金额特殊化
     */
    protected function SDMoney(float $amount)
    {
        //000000000101 12位
        $amountCount = 12;
        $amount = (int) ($amount * 100);
        $amountRes = '';
        for ($i = 1; $i <= ($amountCount - strlen($amount)); $i++) {
            $amountRes .= '0';
        }
        $amountRes .= $amount;
        return $amountRes;
    }
    protected function SDSign($parameter)
    {
        //$pub_key_path = storage_path() . '/wl_file/certs/test/public_key.cer';
        $pub_key_path = storage_path() . PayCommon::getKey($this->payname, $this->zoroCode, 'PubPath');
        $pri_key_path = storage_path() . PayCommon::getKey($this->payname, $this->zoroCode, 'PriPath');
        $cert_pwd = PayCommon::getKey($this->payname, $this->zoroCode, 'signCertPwd');

        $arr1 = ['version', 'method', 'productId', 'accessType', 'mid', 'channelType', 'reqTime'];
        $arr2 = ['orderCode', 'totalAmount', 'subject', 'body', 'txnTimeOut', 'payMode', 'payExtra', 'clientIp'
            , 'notifyUrl', 'frontUrl', 'extend'];
        $arr3 = ['payType', 'bankCode'];
        $arr4 = ['cardNo'];
        $param = array('head' => [], 'body' => []);
        foreach ($parameter as $k => $v) {
            if (in_array($k, $arr1)) {
                $param['head'][$k] = $v;
            }
            if (in_array($k, $arr2)) {
                $param['body'][$k] = $v;
            }
            if ($parameter['payMode'] == 'bank_pc') {
                if (in_array($k, $arr3)) {
                    $param['body']['payExtra'][$k] = $v;
                }
            }
            if ($parameter['payMode'] == 'sand_h5') {
                if (in_array($k, $arr4)) {
                    $param['body']['payExtra'][$k] = $v;
                }
            }
        }
        $param['body']['payExtra'] = json_encode($param['body']['payExtra']);
        $Func = new SDFunc($pub_key_path, $pri_key_path, $cert_pwd);
        $sign = $Func->sign($param);
        $publicKey = $Func->loadX509Cert();
        return array('sign' => $sign, 'publickey' => $publicKey);
    }
    /**
     * @param parameter 原参数
     * @return parameter 整理后的参数
     * 设置跳转url unset自定义字段
     */
    private function FormatParam($parameter)
    {
        $url = url()->previous();
        $set_arr = ['Merchanturl', 'FailUrl', 'ErrorUrl', 'ServerUrl', 'S2Snotify_url', 'Return_url',
            'return_url', 'server_url', 'backgroundMerUrl', 'frontMerUrl', 'notifyUrl', 'showUrl',
            'frontUrl', 'backUrl', 'notify_url', 'destination', 'server_callback_url', 'redirect_url', 'returnUrl'];
        $parameter = is_set_val($parameter, $set_arr, $url . '/finish/notify/' . $this->payname . '/zoroCode/' . $this->zoroCode, '');
        $unset_arr = ['id', 'url', 'res_url', 'key', 'remark', 's_url', 'r_url', 'order_pfx'];
        $parameter = un_set($unset_arr, $parameter);
        return $parameter;
    }
    private function QfbPost($url = '', $param = '')
    {
        if (empty($url)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); // 初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
        if ($param != '') {
            curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch); // 运行curl
        curl_close($ch);
        return $data;
    }

    /**
     * 检查是否银行直连
     */
    private function isCredit()
    {
        if ($this->data['bankCode'] == '' || $this->data['bankCode'] == '0000') {
            return '';
        } else {
            $paycommon = $this->paycommon;
            $re = PayCommon::getKey($this->payname, $this->zoroCode, 'isCredit');
            return $re != 1 ? '' : $re;
        }
        return '';
    }
    private function checkPayProduct($payWayCode)
    {
        //TODO pay code chose
    }
}
