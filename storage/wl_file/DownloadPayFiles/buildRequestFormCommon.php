<?php
error_reporting(0);
$action = $_POST['action'];
$para_temp = stripslashes($_POST['para_temp']);
$para_temp = json_decode($para_temp);
if (!$para_temp) {
    exit("未知错误！请重试。");
}
$sHtml = "<form id='paysubmit' name='paysubmit' action='" . $action . "' method='post'>";

foreach ($para_temp as $key => $value) {
    $sHtml .= "<input type='hidden' name='" . $key . "' id='" . $key . "' value='" . $value . "'/>";
}

//submit按钮控件请不要含有name属性
$sHtml = $sHtml . "<input type='submit' value='' style='display:none;'></form>";
$sHtml = $sHtml . "loading...";

$sHtml = $sHtml . "<script>document.forms['paysubmit'].submit();</script>";

echo $sHtml;
