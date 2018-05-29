<?php
/**
 * 代付/提现接口
 * init payname,zoroCode
 */
namespace App\Services\payApi;

use App\Services\payApi\IpsW\IpsPayRequest;
use App\Services\payApi\IpsW\IpsPayVerify;
use App\Services\payApi\PayCommon;

class WithdrawalInterface
{
    //商户渠道名称
    private $payname;
    //商户ID
    private $zoroCode;
    //配置信息
    private $payConfig;
    //提交参数
    private $param;

    public function __construct($payname, $zoroCode, $data)
    {
        $this->payname = $payname;
        $this->zoroCode = $zoroCode;
        $this->param = $data;
        $this->payConfig = PayCommon::getPayConf($payname, $zoroCode, config('payConfig.withdrawal' . $payname));
        $this->payConfig['PostUrl'] = PayCommon::getKey($payname, $zoroCode, 'WUrl');
    }
    public function WSubmit()
    {
        $parameter = $this->InterfacePay();
        $subFun = $this->payname.'Submit';
        return self::$subFun($parameter);

    }
    private function InterfacePay()
    {
        $payname = $this->payname;
        $bill = $this->makeWBill();
        return self::$payname($bill);
    }
    private function makeWBill()
    {
        $bill = 'W' . gmdate('YmdHis', time() + 8 * 3600) . mt_rand(1000, 9999);
        //TODO 验证重复订单号
        /**
         *
         */
        return $bill;
    }
    public static function YinLian()
    {
        return false;
        // $parameter = array(
        //     //以下信息非特殊情况不需要改动
        //     'version' => (self::payConfig)->version, //版本号
        //     'signMethod' => '01', //签名方法
        //     'encoding' => 'utf-8', //编码方式
        //     'txnType' => '12', //交易类型
        //     'txnSubType' => '01', //交易子类
        //     'bizType' => '000401', //业务类型
        //     'accessType' => '0', //接入类型
        //     'channelType' => '07', //渠道类型
        //     'currencyCode' => '156', //交易币种，境内商户勿改
        //     'encryptCertId' => com\unionpay\acp\sdk\AcpService::getEncryptCertId(), //验签证书序列号
        //     'backUrl' => com\unionpay\acp\sdk\SDKConfig::getSDKConfig()->backUrl, //后台通知地址

        //     //TODO 以下信息需要填写
        //     'merId' => $_POST["merId"], //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
        //     'orderId' => $_POST["orderId"], //商户订单号，如上送短信验证码，请填写获取验证码时一样的orderId，此处默认取demo演示页面传递的参数
        //     'txnTime' => $_POST["txnTime"], //订单发送时间，如上送短信验证码，请填写获取验证码时一样的txnTime，此处默认取demo演示页面传递的参数
        //     'txnAmt' => $_POST["txnAmt"], //交易金额，单位分，如上送短信验证码，请填写获取验证码时一样的txnAmt，此处默认取demo演示页面传递的参数

        //     //         'accNo' => $accNo,     //卡号，旧规范请按此方式填写
        //     //         'customerInfo' => com\unionpay\acp\sdk\AcpService::getCustomerInfo($customerInfo), //持卡人身份信息，旧规范请按此方式填写
        //     'accNo' => com\unionpay\acp\sdk\AcpService::encryptData($accNo), //卡号，新规范请按此方式填写
        //     'customerInfo' => com\unionpay\acp\sdk\AcpService::getCustomerInfoWithEncrypt($customerInfo), //持卡人身份信息，新规范请按此方式填写

        // );
    }
    protected function ipsv7($bill)
    {
        //构造要请求的参数数组
        $parameter = array(
            "ReqDate" => gmdate("YmdHis", time() + 8 * 3600),
            "MerName" => PayCommon::getKey($this->payname, $this->zoroCode, 'MerName'),
            "BizId" => $this->data['BizId'],
            "ChannelId" => $this->data['ChannelId'],
            "Currency" => '156',
            "Date" => gmdate("YmdHis", time() + 8 * 3600),
            "Attach" => $this->data['Attach'], //批次备注信息
            "MerBillNo" => $bill,
            "AccountName" => $this->data['AccountName'],
            "AccountNumber" => $this->data['AccountNumber'],
            "BankName" => $this->data['BankName'],
            "BranchBankName" => $this->data['BranchBankName'],
            "BankCity" => $this->data['BankCity'],
            "BankProvince" => $this->data['BankProvince'],
            "BillAmount" => sprintf('%.2f', $this->data['Amount']),
            "IdCard" => $this->data['IdCard'],
            "MobilePhone" => $this->data['MobilePhone'],
            'Remark' => $this->data['Remark'], //明细备注
        );
        return $parameter;
    }
    protected function ipsv7Submit($parameter)
    {
        //建立请求
        $ipspayRequest = new IpsPayRequest($this->payConfig);
        $html_text = $ipspayRequest->buildRequest($parameter);

        $xmlResult = new \SimpleXMLElement($html_text);
        $strRspCode = $xmlResult->Head->RspCode;

        if ($strRspCode == "000000") {
            //返回报文验签
            $ipspayVerify = new IpsPayVerify($this->payConfig);
            $verify_result = $ipspayVerify->verifyReturn($html_text);

            if ($verify_result) { // 验证成功
                $message = "委托付款请求成功";
                $BatchBillno = $xmlResult->Body->BatchBillno;
            } else {
                $message = "验签失败";
            }
        } else {
            $message = $xmlResult->Head->RspMsg;
            $errMsg = $xmlResult->Body->BatchErrorMsg;
            if (empty($errMsg)) {
                $errMsg = $xmlResult->Body->Details->Detail->ErrorMsg;
            }
        }
    }
}
