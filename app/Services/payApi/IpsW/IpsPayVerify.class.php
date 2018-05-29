<?php
namespace App\Services\payApi\IpsW;

ini_set('date.timezone', 'Asia/Shanghai');
use App\Services\CLogFileHandler;
use App\Services\Log;
use App\Services\payApi\IpsW\IpsWFun;

// 初始化日志
$logHandler = new CLogFileHandler("./pay_logs/verify" . date('Y-m-d') . '.log');
$log = Log::Init($logHandler, 15);

class IpsPayVerify
{

    public $ipspay_config;
    public $IpsFun;

    public function __construct($ipspay_config)
    {
        $this->ipspay_config = $ipspay_config;
        $this->IpsFun = new IpsWFun();
    }

    public function IpsPayVerify($ipspay_config)
    {
        $this->__construct($ipspay_config);
    }

    public function verifyReturn($param)
    {
        try {
            $xmlResult = new SimpleXMLElement($param);
            $strSignature = $xmlResult->Head->Signature;
            $strBody = $this->IpsFun->subStrXml("<Body>", "</Body>", $param);
            if ($this->IpsFun->md5Verify($strBody, $strSignature, $this->ipspay_config["MerCert"])) {
                return true;
            } else {
                Log::DEBUG("委托付款报文验签失败:" . $param);
                return false;
            }
        } catch (Exception $e) {
            Log::ERROR("异常:" . $e);
        }
        return false;
    }
}
