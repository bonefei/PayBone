<?php
error_reporting(0);
$para_temp = $_POST;

if (!$para_temp) {
    exit("未知错误！请重试。");
}
//提交参数
$Para = base64_decode($_POST['para_temp']);
//提交地址
$action = $_POST['action'];

$sHtml = "<form id='ipspaysubmit' name='ipspaysubmit' method='post' action='" . $action . "'>";

$sHtml .= "<input type='hidden' name='pGateWayReq' value='" . $Para . "'/>";

$sHtml = $sHtml . "<input type='submit' style='display:none;'></form>";

$sHtml = $sHtml . "<script>document.forms['ipspaysubmit'].submit();</script>";

echo $sHtml;
