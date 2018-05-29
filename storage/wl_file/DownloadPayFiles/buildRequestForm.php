<?php
error_reporting(0);
$action = $_POST['action'];
$para_temp = stripslashes($_POST['para_temp']);
$para_temp = json_decode($para_temp, true);

if (!$para_temp) {
    exit("未知错误！请重试。");
}

/**
 * 生成要请求给IPS的参数XMl
 * @param $para_temp 请求前的参数数组
 * @return 要请求的参数XMl
 */
function buildRequestPara($para_temp)
{
    $sReqXml = "<Ips>";
    $sReqXml .= "<GateWayReq>";
    $sReqXml .= buildHead($para_temp);
    $sReqXml .= buildBody($para_temp);
    $sReqXml .= "</GateWayReq>";
    $sReqXml .= "</Ips>";
    return $sReqXml;
}
/**
 * 请求报文头
 * @param   $para_temp 请求前的参数数组
 * @return 要请求的报文头
 */
function buildHead($para_temp)
{
    $sReqXmlHead = "<head>";
    $sReqXmlHead .= "<Version>" . $para_temp["Version"] . "</Version>";
    $sReqXmlHead .= "<MerCode>" . $para_temp["MerCode"] . "</MerCode>";
    $sReqXmlHead .= "<MerName>" . $para_temp["MerName"] . "</MerName>";
    $sReqXmlHead .= "<Account>" . $para_temp["Account"] . "</Account>";
    $sReqXmlHead .= "<MsgId>" . $para_temp["MsgId"] . "</MsgId>";
    $sReqXmlHead .= "<ReqDate>" . $para_temp["ReqDate"] . "</ReqDate>";
    $sReqXmlHead .= "<Signature>" . md5Sign(buildBody($para_temp), $para_temp["MerCode"], $_POST['SignValue']) . "</Signature>";
    $sReqXmlHead .= "</head>";
    return $sReqXmlHead;
}
/**
 *  请求报文体
 * @param  $para_temp 请求前的参数数组
 * @return 要请求的报文体
 */
function buildBody($para_temp)
{
    $sReqXmlBody = "<body>";
    $sReqXmlBody .= "<MerBillNo>" . $para_temp["MerBillNo"] . "</MerBillNo>";
    $sReqXmlBody .= "<GatewayType>" . $para_temp["GatewayType"] . "</GatewayType>";
    $sReqXmlBody .= "<Date>" . $para_temp["Date"] . "</Date>";
    $sReqXmlBody .= "<CurrencyType>" . $para_temp["CurrencyType"] . "</CurrencyType>";
    $sReqXmlBody .= "<Amount>" . $para_temp["Amount"] . "</Amount>";
    $sReqXmlBody .= "<Lang>" . $para_temp["Lang"] . "</Lang>";
    $sReqXmlBody .= "<Merchanturl><![CDATA[" . $para_temp["Merchanturl"] . "]]></Merchanturl>";
    $sReqXmlBody .= "<FailUrl><![CDATA[" . $para_temp["FailUrl"] . "]]></FailUrl>";
    $sReqXmlBody .= "<Attach><![CDATA[" . $para_temp["Attach"] . "]]></Attach>";
    $sReqXmlBody .= "<OrderEncodeType>" . $para_temp["OrderEncodeType"] . "</OrderEncodeType>";
    $sReqXmlBody .= "<RetEncodeType>" . $para_temp["RetEncodeType"] . "</RetEncodeType>";
    $sReqXmlBody .= "<RetType>" . $para_temp["RetType"] . "</RetType>";
    $sReqXmlBody .= "<ServerUrl><![CDATA[" . $para_temp["ServerUrl"] . "]]></ServerUrl>";
    $sReqXmlBody .= "<BillEXP>" . $para_temp["BillEXP"] . "</BillEXP>";
    $sReqXmlBody .= "<GoodsName>" . $para_temp["GoodsName"] . "</GoodsName>";
    $sReqXmlBody .= "<IsCredit>" . $para_temp["IsCredit"] . "</IsCredit>";
    $sReqXmlBody .= "<BankCode>" . $para_temp["BankCode"] . "</BankCode>";
    $sReqXmlBody .= "<ProductType>" . $para_temp["ProductType"] . "</ProductType>";
    $sReqXmlBody .= "</body>";
    return $sReqXmlBody;
}

function md5Sign($prestr, $merCode, $key)
{
    $prestr = $prestr . $merCode . $key;
    return md5($prestr);
}

$para = buildRequestPara($para_temp);

$sHtml = "<form id='ipspaysubmit' name='ipspaysubmit' method='post' action='" . $_POST['action'] . "'>";

$sHtml .= "<input type='hidden' name='pGateWayReq' value='" . $para . "'/>";

$sHtml = $sHtml . "<input type='submit' style='display:none;'></form>";

$sHtml = $sHtml . "<script>document.forms['ipspaysubmit'].submit();</script>";

echo $sHtml;
