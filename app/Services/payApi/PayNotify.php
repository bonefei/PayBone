<?php
namespace App\Services\payApi;

use App\Services\CLogFileHandler;
use App\Services\Log;
use App\Services\payApi\IpsNew\IpsPayNotify;
use App\Services\payApi\PayCommon;
use App\Services\payApi\SD\SDFunc;
use App\Services\payApi\Sun8Pay;
use App\Services\payApi\YinLian\sdk\AcpService;
use App\Services\payApi\YinLian\sdk\SDKConfig;
use DB;

$logHandler = new CLogFileHandler(storage_path() . "/pay_logs/" . gmdate('Y-m-d', time() + 8 * 3600) . 'paynotify.log');
$log = Log::Init($logHandler, 15);
class PayNotify
{
    private $payname;
    private $zoroCode;
    public function getPayway($payname, $zoroCode, $param)
    {
        $this->payname = $payname;
        $this->zoroCode = $zoroCode;
        return self::$payname($param, $zoroCode, $payname);
    }
    public static function ips($param, $zoroCode, $payname)
    {
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $param['billno']);
        //参数
        $billno = $param['billno'];

        $amount = $param['amount'];
        $mydate = $param['date'];
        $succ = $param['succ'];
        $msg = $param['msg'];
        $attach = $param['attach'];
        $ipsbillno = $param['ipsbillno'];
        $retEncodeType = $param['retencodetype'];
        $currency_type = $param['Currency_type'];
        $signature = $param['signature'];

        $key = $GetPayApiConf['MerCert'];
        //$config = $this->getPayApiConf($this->payment_name);//获取支付接口配置

        $data['errtxt'] = $billno . "&" . $amount . "&" . $mydate . "&" . $succ . "&" . $msg . "&" . $attach . "&" . $ipsbillno . "&" . $retEncodeType . "&" . $currency_type . "&" . $signature;
        $data['time'] = gmdate("Y-m-d H:i:s", time() + 8 * 3600);

        $content = 'billno' . $billno . 'currencytype' . $currency_type . 'amount' . $amount . 'date' . $mydate . 'succ' . $succ . 'ipsbillno' . $ipsbillno . 'retencodetype' . $retEncodeType;
        //请在该字段中放置商户登陆merchant.ips.com.cn下载的证书
        $cert = $key;
        $signature_1ocal = md5($content . $cert);
        $err_type = 1; //收到报文

        if ($signature_1ocal != $signature) { //验证失败
            $data['errtxt'] .= "&" . $signature_1ocal . "&" . $signature;
            $err_type = 4;
        }

        //比对返回的令牌 以确定是环讯的返回信息
        if ($signature_1ocal != $signature) { //验证失败
            //exit("验证失败");
            LOG::ERROR('验签失败' . $payname . '报文:' . var_export($param, true));
            return (new self())->changeStatus($billno, $ipsbillno, $param['attach'], $param['bankbillno'], 1,
                $param, $amount, $currency_type, $payname, $zoroCode);
            //return false;
        }
        if ($succ != 'Y') { //交易失败
            //exit("交易失败");
            LOG::ERROR('验签失败' . $payname . '报文:' . var_export($param, true));
            return (new self())->changeStatus($billno, $ipsbillno, $GetPayApiConf['att_arr'], $param['bankbillno'], 1,
                $param, $amount, $currency_type, $payname, $zoroCode);
            //return false;
        }
        LOG::INFO('验签成功' . $payname . '报文:' . var_export($param, true));
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $billno);
        // dump($billno, $ipsbillno, $param['attach'], $param['bankbillno'], 2,
        // $param, $amount, $currency_type, $payname, $zoroCode,$GetPayApiConf);die;
        return (new self())->changeStatus($billno, $ipsbillno, $GetPayApiConf['att_arr'], $param['bankbillno'], 2,
            $param, $amount, $currency_type, $payname, $zoroCode);

        //return self::getReturnBillNo($billno);
        //一切正常 调用完成逻辑
    }
    public static function ipsv7($param, $zoroCode, $payname)
    {
        $ipsv7Config = config('payConfig.Info.' . $payname);
        $ipsv7Config['MerCode'] = PayCommon::getKey($payname, $zoroCode, 'MerCode');
        $ipsv7Config['MerCert'] = PayCommon::getKey($payname, $zoroCode, 'MerCert');
        $ipspayNotify = new IpsPayNotify($ipsv7Config);

        $verify_result = ($ipspayNotify)->verifyReturn($param, $ipsv7Config);
        $paymentResult = $param['paymentResult'];
        $xmlResult = new \SimpleXMLElement($paymentResult);
        if ($verify_result) { // 验证成功
            $status = $xmlResult->GateWayRsp->body->Status;
            $merBillNo = $xmlResult->GateWayRsp->body->MerBillNo;
            $ipsBillNo = $xmlResult->GateWayRsp->body->IpsBillNo;
            $ipsTradeNo = $xmlResult->GateWayRsp->body->IpsTradeNo;
            $bankBillNo = $xmlResult->GateWayRsp->body->BankBillNo;
            $message = "交易成功";
            LOG::INFO('验签成功,' . $message . ',' . $payname . '报文:' . var_export($xmlResult, true));
            return (new self())->changeStatus($merBillNo, $ipsTradeNo, $xmlResult->GateWayRsp->body->Attach,
                $bankBillNo, 2, $xmlResult, $xmlResult->GateWayRsp->body->Amount,
                $xmlResult->GateWayRsp->body->CurrencyType, $payname, $zoroCode);
        } else {
            $message = "验证失败";
            LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($xmlResult, true));
            return (new self())->changeStatus($xmlResult->GateWayRsp->body->MerBillNo,
                $xmlResult->GateWayRsp->body->IpsTradeNo, $xmlResult->GateWayRsp->body->Attach,
                $xmlResult->GateWayRsp->body->BankBillNo, 1, $xmlResult, $xmlResult->GateWayRsp->body->Amount,
                $xmlResult->GateWayRsp->body->CurrencyType, $payname, $zoroCode);
        }
    }
    public static function GfbPay($param, $zoroCode, $payname)
    {
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $param['merOrderNum']);
        //$Aee = json_decode($Arr,true);

        $UrlCode = 'version=[' . $param['version'] . ']tranCode=[' . $param['tranCode']
            . ']merchantID=[' . $param['merchantID'] . ']merOrderNum=[' . $param['merOrderNum']
            . ']tranAmt=[' . $param['tranAmt'] . ']feeAmt=[' . $param['feeAmt'] . ']tranDateTime=[' . $param['tranDateTime']
            . ']frontMerUrl=[' . $param['frontMerUrl'] . ']backgroundMerUrl=[' . $param['backgroundMerUrl']
            . ']orderId=[' . $param['orderId'] . ']gopayOutOrderId=[' . $param['gopayOutOrderId'] . ']tranIP=[' . $param['tranIP']
            . ']respCode=[' . $param['respCode'] . ']gopayServerTime=[]VerficationCode=[' . $GetPayApiConf['MerCert'] . ']';

        $signValue = md5($UrlCode);

        $r3_Amt = sprintf("%.2f", $param['tranAmt']);
        if ($param['respCode'] == '0000') {
            if ($signValue == $param['signValue']) {
                (new self())->changeStatus($param['merOrderNum'], $r3_Amt, $payname);
                return self::getReturnBillNo($IpsTradeNo);
            }
        } else {
            (new self())->changeStatus($param['merOrderNum'], $r3_Amt, $payname);
            echo 'error';
        }
    }
    public static function Sun8Pay($param, $zoroCode, $payname)
    {
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $param['orderNo']);
        $data = [
            'paycode' => $param['paycode'],
            'merId' => $param['merId'],
            'orderNo' => $param['orderNo'],
            'rtnCode' => $param['rtnCode'],
            'payAmt' => $param['payAmt'],
            'productName' => $param['productName'],
            'feeAmt' => $param['feeAmt'],
            'notifyType' => $param['notifyType'],
        ];

        $str = SunPay::arrayToString($data);
        $str = $str . $GetPayApiConf['MerCert'];
        $verify = md5($str);

        if ($verify == $param['signValue']) {
            $message = "交易成功";
            LOG::INFO('验签成功,' . $message . ',' . $payname . '报文:' . var_export($data, true));
            return (new self())->changeStatus($param['orderNo'], $param['rtnCode'], $GetPayApiConf['att_arr'],
                '', 2, $data, $param['payAmt'], '', $payname, $zoroCode);
        } else {
            LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($data, true));
            return (new self())->changeStatus($param['orderNo'], $param['rtnCode'], $GetPayApiConf['att_arr'],
                '', 1, $data, $param['payAmt'], '', $payname, $zoroCode);
        }
    }
    /**
     * 银联通道
     */
    public static function YinLian($param, $zoroCode, $payname)
    {
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $param['orderId']);
        //获取证书路径
        $cert_arr = PayCommon::getKey($payname, $zoroCode, 'signCertPath,middleCertPath,rootCertPath');
        foreach ($cert_arr as $k => $v) {
            //获取到的路径添加为全路径
            $cert_arr->$k = storage_path() . $v;
        }
        if (AcpService::validate($param, $cert_arr->rootCertPath, $cert_arr->middleCertPath)) {
            //TODO 验签成功,可以增加查询订单机制
            $message = "交易成功";
            LOG::INFO('验签成功,' . $message . ',' . $payname . '报文:' . var_export($param, true));
            //XXX Done
            $queryorder = (new self())->QueryOrderStatus($param, $zoroCode, $payname, $cert_arr);

            //var_dump($queryorder);die;
            if ($queryorder == 'loading') {
                //订单处理中，睡1s再次查询
                sleep(1);
                $queryorder = (new self())->QueryOrderStatus($param, $zoroCode, $payname, $cert_arr);
            }
            if ($queryorder !== true) {
                $message = "交易失败";
                LOG::ERROR('验签成功,' . $message . ',报文:' . var_export($param, true));
                return (new self())->changeStatus($param['orderId'], $param['queryId'], $GetPayApiConf['att_arr'],
                    '', 1, $param, $param['txnAmt'] / 100, '', $payname, $zoroCode);
            }
            $message = "交易成功";
            LOG::INFO('验签成功,' . $message . ',报文:' . var_export($param, true));
            return (new self())->changeStatus($param['orderId'], $param['queryId'], $GetPayApiConf['att_arr'],
                '', 2, $param, $param['txnAmt'] / 100, '', $payname, $zoroCode);
        } else {
            //验签失败
            $message = "交易成功";
            LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($param, true));
            return (new self())->changeStatus($param['orderId'], $param['queryId'], $GetPayApiConf['att_arr'],
                '', 1, $param, $param['txnAmt'] / 100, '', $payname, $zoroCode);
        }
    }
    /**
     * RPay
     */
    public static function RPay($param, $zoroCode, $payname)
    {
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $param['order_id']);
        $Parameter = array(
            "order_id" => $param['order_id'],
            "order_time" => $param['order_time'],
            "order_amount" => $param['order_amount'],
            "deal_id" => $param['deal_id'],
            "deal_time" => $param['deal_time'],
            "pay_amount" => $param['pay_amount'],
            "pay_result" => $param['pay_result'],
            "signature" => $param['signature'],
        );

        $order_id = explode('_', $Parameter['order_id']);
        $MD5key = $GetPayApiConf['MerCert'];

        $UrlDecode = 'order_id=' . $Parameter['order_id'] . '|order_time='
            . $Parameter['order_time'] . '|order_amount=' . $Parameter['order_amount'] . '|deal_id='
            . $Parameter['deal_id'] . '|deal_time=' . $Parameter['deal_time'] . '|pay_amount='
            . $Parameter['pay_amount'] . '|pay_result=' . $Parameter['pay_result'] . '|key=' . $MD5key;

        $verifySign = md5($UrlDecode);

        if ($verifySign == $Parameter['signature']) {
            $message = "交易成功";
            LOG::INFO('验签成功,' . $message . ',报文:' . var_export($param, true));
            return (new self())->changeStatus($param['order_id'], $param['deal_id'], $GetPayApiConf['att_arr'],
                '', 2, $param, $param['pay_amount'] / 100, '', $payname, $zoroCode);
        } else {
            $message = "交易失败";
            LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($param, true));
            return (new self())->changeStatus($param['order_id'], $param['deal_id'], $GetPayApiConf['att_arr'],
                '', 1, $param, $param['pay_amount'] / 100, '', $payname, $zoroCode);
        }
    }
    /**
     * ZotaPay
     */
    public static function ZotaPay($param, $zoroCode, $payname)
    {
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $param['merchant_order']);
        $Sha = sha1($param['status'] . $param['orderid'] . $param['merchant_order'] . $GetPayApiConf['MerCert']);

        if ($Sha == $param['control']) {
            if ($param['status'] == 'approved') {
                $message = "交易成功";
                LOG::INFO('验签成功,' . $message . ',报文:' . var_export($param, true));
                return (new self())->changeStatus($param['merchant_order'], $param['orderid'], $GetPayApiConf['att_arr'],
                    '', 2, $param, $param['amount'], '', $payname, $zoroCode);
            } else {
                $message = "交易失败";
                LOG::ERROR('支付失败,' . $message . ',报文:' . var_export($param, true));
                return (new self())->changeStatus($param['merchant_order'], $param['orderid'], $GetPayApiConf['att_arr'],
                    '', 1, $param, $param['amount'], '', $payname, $zoroCode);
            }
        } else {
            $message = "交易失败";
            LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($param, true));
            return (new self())->changeStatus($param['merchant_order'], $param['orderid'], $GetPayApiConf['att_arr'],
                '', 1, $param, $param['amount'], '', $payname, $zoroCode);
        }
    }
    /**
     * 杉德支付报文处理
     */
    public static function SDPay($param, $zoroCode, $payname)
    {

        $publickey = (new SDFunc())->loadX509Cert($pri_key_path = storage_path() . PayCommon::getKey($payname, $zoroCode, 'PubPath'));
        $sign = $param['sign']; //签名
        $signType = $param['signType']; //签名方式
        $data = stripslashes($param['data']); //支付数据
        $charset = $param['charset']; //支付编码
        $result = json_decode($data, true); //data数据
        $GetPayApiConf = self::getReturnUrl($param, $payname, $zoroCode, $result['body']['orderCode']);
        if ((new SDFunc())->verify($data, $sign, $publickey)) {
            //签名验证成功
            LOG::INFO(date("Y-m-d H:i:s") . "  " . "异步通知返回报文：" . var_export($data, true));
            return (new self())->changeStatus($result['body']['orderCode'], $result['body']['tradeNo'], $GetPayApiConf['att_arr'],
                '', 2, $result, $result['body']['totalAmount'], '', $payname, $zoroCode);
        } else {
            //签名验证失败
            $message = "交易失败";
            LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($param, true));
            return (new self())->changeStatus($result['body']['orderCode'], $result['body']['tradeNo'], $GetPayApiConf['att_arr'],
                '', 1, $result, $result['body']['totalAmount'], '', $payname, $zoroCode);
        }
    }
    /**
     * 翔丰支付
     */
    public static function XPay($param, $zoroCode, $payname)
    {
        //LOG::INFO(date("Y-m-d H:i:s") . "  " . "异步通知返回报文：" . var_export($param, true) . "\n" . $payname . '---' . $zoroCode);
        $Arr = $param;
        if (isset($Arr['signature'])) {
            ksort($Arr);
            $sign_ = str_replace('%2B','+',$Arr['signature']);
            unset($Arr['signature']);
            $notallowFields = ['safedog-flow-item', 'ECS', 'bdshare_firstime'];
            $md5str = '';
            foreach ($Arr as $k => $v) {
                if (in_array($k, $notallowFields)) {
                    unset($Arr[$k]);
                } else {
                    $md5str .= "$k=$v&";
                }
            }
            $n_Data = (string)rtrim($md5str, '&');
            //公钥路径
            $pub_key_path_ = storage_path() . PayCommon::getKey($payname, $zoroCode, 'PubPath');
            // $pub_key = file_get_contents($pub_key_path_);
            // $res = openssl_get_publickey($pub_key);

            // $result = openssl_verify($n_Data, base64_decode($sign_), $res);
            $pubkey = file_get_contents($pub_key_path_);
            $res = openssl_get_publickey($pubkey);
            $result = openssl_verify($n_Data, base64_decode($sign_), $res);
            openssl_free_key($res);
            $GetPayApiConf = self::getReturnUrl($Arr, $payname, $zoroCode, $Arr['orderNo']);
            if ($result) {
                // echo 111;
                // dump($Arr['orderNo'], $Arr['requestNo'], $GetPayApiConf['att_arr'],
                // '', 1, $Arr, $Arr['transAmt'] / 100, '', $payname, $zoroCode);die;
                if ($Arr['respCode'] == '0000') {
                    // 验证成功
                    LOG::INFO(date("Y-m-d H:i:s") . "  " . "异步通知返回报文：" . var_export($Arr, true) . 'return:' . var_export($GetPayApiConf, true));
                    return (new self())->changeStatus($Arr['orderNo'], $Arr['requestNo'], $GetPayApiConf['att_arr'],
                        '', 2, $Arr, $Arr['transAmt'] / 100, '', $payname, $zoroCode);
                } else {
                    //支付失败
                    $message = "交易失败-zf-error";
                    LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($Arr, true));
                    //echo 'zf-error';
                    return (new self())->changeStatus($Arr['orderNo'], $Arr['requestNo'], $GetPayApiConf['att_arr'],
                        '', 1, $Arr, $Arr['transAmt'] / 100, '', $payname, $zoroCode);
                }
            } else {
                //echo 'yq-error';
                $message = "交易失败-yq-error";
                LOG::ERROR('验签失败,' . $message . ',报文:' . var_export($Arr, true));
                return (new self())->changeStatus($Arr['orderNo'], $Arr['requestNo'], $GetPayApiConf['att_arr'],
                    '', 1, $Arr, $Arr['transAmt'] / 100, '', $payname, $zoroCode);
            }
        } else {
            $Arr['errorCode'] = 'Error-Trade';
            return $Arr;
        }
    }
    public static function UnSDMoney($amount)
    {
        return sprintf('%.2f', (int) $amount / 100);
    }
    /**
     * 修改订单状态
     * @param billno  -网关订单
     * @param payBill -支付商流水号
     * @param attach 备注（应为报文返回地址带有“-|”）
     * @param bankBillNo 银行单号
     * @param status 支付状态 1失败，2成功
     * @param notify 报文
     * @param Amount 金额
     * @param CurrencyType 币种
     * @param payname 支付通道名称
     * @param zoroCode 商户ID
     * @return OrderStatus
     */
    private function changeStatus($billno, $payBill, $attach, $bankBillNo, $status, $notify, $Amount, $CurrencyType, $payname, $zoroCode)
    {
        $att_arr = explode('-|', $attach);
        $where = ['merchant_order_no' => $billno];
        $nowDate = gmdate('Y-m-d H:i:s', time() + 8 * 3600);
        // $info = DB::table('zoro_trade_payment_record')->where($where)->first();
        $update = [
            'return_url' => $att_arr[0], 'notify_url' => $att_arr[1],
            'pay_success_time' => gmdate('Y-m-d H:i:s', time() + 8 * 3600), 'complete_time' => $nowDate,
            'status' => $status, 'bank_order_no' => $bankBillNo,
            'pay_way_order_no' => $payBill, 'notify_msg' => var_export($notify, true),
        ];
        DB::table('zoro_trade_payment_record')->where($where)->update($update);
        if ($status == 2) {
            // $recordParam = array(
            //     'payname' => $payname,
            //     'action' => 1,
            //     'peybalance' => DB::raw('peybalance + ' . $Amount),
            //     'payamount' => $Amount,
            //     'paytype' => $info->pay_type_name,
            //     'payaisle' => payCommon::getKey($payname, $zoroCode, 'Merchants'),
            //     'payno' => $billno,
            //     'create_time' => gmdate('Y-m-d H:i:s', time() + 8 * 3600),
            //     'complete_time' => gmdate('Y-m-d H:i:s', time() + 8 * 3600),
            // );
            // (new self())->daily_balance($payname, $zoroCode, $recordParam);
        }

        return $this->FormatReturn($status, $billno, $attach, $nowDate, $Amount, $CurrencyType);
    }
    protected function FormatReturn($status, $billno, $attach, $nowDate, $Amount, $CurrencyType)
    {
        return array('PayStatus' => ($status == 2 ? true : false), 'PayBill' => (string) $billno, 'PayAttach' => (string) $attach,
            'Date' => (string) $nowDate, 'Amount' => (string) $Amount, 'CurrencyType' => (string) $CurrencyType);
    }
    public static function getReturnBillNo($billno)
    {
        return $billno;
        //暂时弃用
        // $where = ['merchant_order_no' => $billno];
        // $order_no = DB::table('zoro_trade_payment_record')->where($where)->value('trx_no');
        // return $order_no;
    }
    protected function daily_balance($payname, $zoroCode, $recordParam)
    {
        $rec = DB::table('zoro_trade_balance')->where(['payno' => $recordParam['payno']])->count();
        if ($rec > 0) {
            return;
        }
        // $date = gmdate('Y-m-d', time() + 8 * 3600);
        DB::connection()->enableQueryLog(); // 开启查询日志
        //弃用
        // $daily = DB::table('zoro_trade_payment_record')
        //     ->where('pay_way_name', $payname)
        //     ->where('status', 2)
        //     ->value(DB::raw('sum(payer_pay_amount)'));
        // LOG::info('daily,' . var_export(DB::getQueryLog(), true));
        // $config_code = DB::table('zoro_pay_way as a')
        //     ->join('zoro_pay_product as b', 'a.pay_product_code', 'b.product_code')
        //     ->where('a.pay_way_code', $zoroCode)
        //     ->where('b.product_name', $payname)
        //     ->value(DB::raw('b.id'));
        // LOG::info('config_code,' . var_export(DB::getQueryLog(), true));
        // DB::table('zoro_pay_product_config')
        //     ->where('config_code', $config_code)
        //     ->update(['day_amount' => $daily, 'update_at' => gmdate('Y-m-d H:i:s', time() + 8 * 3600)]);

        DB::table('zoro_trade_balance')->insert($recordParam);
        LOG::info('insert,' . var_export(DB::getQueryLog(), true));
    }
    public static function getReturnUrl($param, $payname, $zoroCode, $billno)
    {
        $payConfing = config('payConfig.Info.' . $payname);
        $GetPayApiConf = (new PayCommon())->getPayConf($payname, $zoroCode, $payConfing);
        $GetPayApiConf['att_arr'] = $GetPayApiConf['r_url'] . '-|' . $GetPayApiConf['s_url'];

        $return_arr = ['attach', 'Attach'];
        foreach ($param as $k => $v) {
            if (in_array($k, $return_arr) && $v) {
                $GetPayApiConf['att_arr'] = $v;
            }
        }
        $record_param = DB::table('zoro_trade_payment_record')
            ->where(['merchant_order_no' => $billno])
            ->select('return_url', 'notify_url')
            ->first();
        if ($record_param && $record_param->return_url) {
            $GetPayApiConf['att_arr'] = $record_param->return_url . '-|' . $record_param->notify_url;
        }
        return $GetPayApiConf;
    }
    protected function QueryOrderStatus($param, $zoroCode, $payname, $cert_arr)
    {
        $p = PayCommon::getKey($payname, $zoroCode, 'b.version,signCertPwd');
        $params = array(
            //以下信息非特殊情况不需要改动
            'version' => $p->version, //版本号
            'encoding' => 'utf-8', //编码方式
            'signMethod' => '01', //签名方法
            'txnType' => '00', //交易类型
            'txnSubType' => '00', //交易子类
            'bizType' => '000000', //业务类型
            'accessType' => '0', //接入类型
            'channelType' => '07', //渠道类型

            //TODO 以下信息需要填写
            'orderId' => $param["orderId"], //请修改被查询的交易的订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数
            'merId' => $param["merId"], //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'txnTime' => $param["txnTime"], //请修改被查询的交易的订单发送时间，格式为YYYYMMDDhhmmss，此处默认取demo演示页面传递的参数
        );

        $cert_path = $cert_arr->signCertPath;
        $cert_pwd = $p->signCertPwd;
        $sign = AcpService::sign($params, $cert_path, $cert_pwd);
        $url = SDKConfig::getSDKConfig()->singleQueryUrl;

        $result_arr = AcpService::post($params, $url);
        if (count($result_arr) <= 0) { //没收到200应答的情况
            var_dump($url, $params, "");
            return 1;
        }
        $MiddleCertPath = $cert_arr->middleCertPath;
        $RootCertPath = $cert_arr->rootCertPath;
        if (!AcpService::validate($result_arr, $RootCertPath, $MiddleCertPath)) {
            //echo "应答报文验签失败<br>\n";
            return;
        }

        //echo "应答报文验签成功<br>\n";
        if ($result_arr["respCode"] == "00") {
            if ($result_arr["origRespCode"] == "00") {
                //交易成功
                //TODO
                //echo "交易成功。<br>\n";
                return true;
            } else if ($result_arr["origRespCode"] == "03"
                || $result_arr["origRespCode"] == "04"
                || $result_arr["origRespCode"] == "05") {
                //后续需发起交易状态查询交易确定交易状态
                //TODO
                //echo "交易处理中，请稍微查询。<br>\n";
                return 'loading';
            } else {
                //其他应答码做以失败处理
                //TODO
                //echo "交易失败：" . $result_arr["origRespMsg"] . "。<br>\n";
                return 2;
            }
        } else if ($result_arr["respCode"] == "03"
            || $result_arr["respCode"] == "04"
            || $result_arr["respCode"] == "05") {
            //后续需发起交易状态查询交易确定交易状态
            //TODO
            //echo "处理超时，请稍微查询。<br>\n";
            return 3;
        } else {
            //其他应答码做以失败处理
            //TODO
            //echo "失败：" . $result_arr["respMsg"] . "。<br>\n";
            return 4;
        }
    }
}
