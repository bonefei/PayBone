<?php
namespace App\Services\payApi\IpsW;

ini_set('date.timezone', 'Asia/Shanghai');
use App\Services\CLogFileHandler;
use App\Services\Log;
use App\Services\payApi\IpsW\Crypt3Des;
use App\Services\payApi\IpsW\IpsWFun;

//初始化日志
$logHandler = new CLogFileHandler("./pay_logs/request" . date('Y-m-d') . '.log');
$log = Log::Init($logHandler, 15);

class IpsPayRequest
{
    public $ipspay_config;
    public $IpsFun;

    public function __construct($ipspay_config)
    {
        $this->ipspay_config = $ipspay_config;
        $this->IpsFun = new IpsWFun();
    }
    public function IpsPayRequest($ipspay_config)
    {

        $this->__construct($ipspay_config);
    }
    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    public function buildRequest($para_temp)
    {
        try {
            $para = $this->buildRequestPara($para_temp);

            $wsdl = $this->ipspay_config['PostUrl'];
            $client = new SoapClient($wsdl);
            $param = array("arg0" => $para);
            $sReqXml = $client->issued($param);
            $paraArray = $this->IpsFun->object_array($sReqXml);

            Log::DEBUG("委托付款请求返回报文:" . $paraArray['return']);
            return $paraArray['return'];
        } catch (Exception $e) {
            Log::ERROR("委托付款请求异常:" . $e);
        }
        return null;
    }

    /**
     * 生成要请求给IPS的参数XMl
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数XMl
     */
    public function buildRequestPara($para_temp)
    {
        $sReqXml = "<Req>";
        $sReqXml .= $this->buildHead($para_temp);
        $sReqXml .= $this->buildBody($para_temp);
        $sReqXml .= "</Req>";
        Log::DEBUG("委托付款请求报文:" . $sReqXml);
        return $sReqXml;
    }
    /**
     * 请求报文头
     * @param   $para_temp 请求前的参数数组
     * @return 要请求的报文头
     */
    public function buildHead($para_temp)
    {
        $sReqXmlHead = "<Head>";
        $sReqXmlHead .= "<Version>" . $this->ipspay_config["Version"] . "</Version>";
        $sReqXmlHead .= "<MerCode>" . $this->ipspay_config["MerCode"] . "</MerCode>";
        $sReqXmlHead .= "<MerName>" . $para_temp["MerName"] . "</MerName>";
        $sReqXmlHead .= "<Account>" . $this->ipspay_config["Account"] . "</Account>";
        $sReqXmlHead .= "<MsgId>" . $this->ipspay_config["MsgId"] . "</MsgId>";
        $sReqXmlHead .= "<ReqDate>" . $para_temp["ReqDate"] . "</ReqDate>";
        $sReqXmlHead .= "<Signature>" . md5Sign($this->buildBody($para_temp), $this->ipspay_config['MerCert']) . "</Signature>";
        $sReqXmlHead .= "</Head>";
        return $sReqXmlHead;
    }

    /**
     *  请求报文体
     * @param  $para_temp 请求前的参数数组
     * @return 要请求的报文体
     */
    public function buildBody($para_temp)
    {

        $sReqXmlDetail = "<MerBillNo>" . $para_temp["MerBillNo"] . "</MerBillNo>";
        $sReqXmlDetail .= "<AccountName><![CDATA[" . $para_temp["AccountName"] . "]]></AccountName>";
        $sReqXmlDetail .= "<AccountNumber>" . $para_temp["AccountNumber"] . "</AccountNumber>";
        $sReqXmlDetail .= "<BankName><![CDATA[" . $para_temp["BankName"] . "]]></BankName>";
        $sReqXmlDetail .= "<BranchBankName><![CDATA[" . $para_temp["BranchBankName"] . "]]></BranchBankName>";
        $sReqXmlDetail .= "<BankCity><![CDATA[" . $para_temp["BankCity"] . "]]></BankCity>";
        $sReqXmlDetail .= "<BankProvince><![CDATA[" . $para_temp["BankProvince"] . "]]></BankProvince>";
        $sReqXmlDetail .= "<BillAmount>" . $para_temp["BillAmount"] . "</BillAmount>";
        $sReqXmlDetail .= "<IdCard>" . $para_temp["IdCard"] . "</IdCard>";
        $sReqXmlDetail .= "<MobilePhone>" . $para_temp["MobilePhone"] . "</MobilePhone>";

        $sReqXmlBody = "<Body>";
        $sReqXmlBody .= "<BizId>" . $para_temp["BizId"] . "</BizId>";
        $sReqXmlBody .= "<ChannelId>" . $para_temp["ChannelId"] . "</ChannelId>";
        $sReqXmlBody .= "<Currency>" . $para_temp["Currency"] . "</Currency>";
        $sReqXmlBody .= "<Date>" . $para_temp["Date"] . "</Date>";
        $sReqXmlBody .= "<Attach><![CDATA[" . $para_temp["Attach"] . "]]></Attach>";
        $sReqXmlBody .= "<IssuedDetails>";
        $sReqXmlBody .= "<Detail>";
        $rep = new Crypt3Des($this->ipspay_config['DES_KEY'], $this->ipspay_config['DES_IV']);
        $sReqXmlBody .= $rep->encrypt($sReqXmlDetail);
        $sReqXmlBody .= "</Detail>";
        $sReqXmlBody .= "</IssuedDetails>";
        $sReqXmlBody .= "</Body>";
        return $sReqXmlBody;
    }
}
