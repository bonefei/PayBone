<?php
namespace App\Services\payApi\IpsNew;

ini_set('date.timezone', 'Asia/Shanghai');
use App\Services\payApi\IpsNew\IpsFun;
use App\Services\CLogFileHandler;
use App\Services\Log;

//初始化日志
$logHandler = new CLogFileHandler(storage_path() . "/logs/" . date('Y-m-d') . '=.log');
$log = Log::Init($logHandler, 15);

class IpsPayNotify
{
    public $ipspay_config;

    public function __construct($ipspay_config)
    {
        $this->ipspay_config = $ipspay_config;
    }
    public function IpsPayNotify($ipspay_config)
    {
        $this->__construct($ipspay_config);
    }

    public function verifyReturn($param)
    {
        try {
            if (empty($param)) {
                return false;
            } else {
                $paymentResult = $param['paymentResult'];
                Log::DEBUG("支付返回报文:" . $paymentResult);

                $xmlResult = new \SimpleXMLElement($paymentResult);
                $strSignature = $xmlResult->GateWayRsp->head->Signature;

                $retEncodeType = $xmlResult->GateWayRsp->body->RetEncodeType;
                $strBody = IpsFun::subStrXml("<body>", "</body>", $paymentResult);
                $rspCode = $xmlResult->GateWayRsp->head->RspCode;
                if ($rspCode == "000000") {
                    if (IpsFun::md5Verify($strBody, $strSignature, $this->ipspay_config["MerCode"], $this->ipspay_config["MerCert"])) {
                        return true;
                    } else {
                        Log::DEBUG("支付返回报文验签失败:" . $paymentResult);
                        return false;
                    }
                }

            }
        } catch (Exception $e) {
            Log::ERROR("异常:" . $e);
        }
        return false;
    }
}
