<?php
namespace App\Services\payApi;
/**
 * 不同支付都需要用到自己的方法，定义的方法可以写在这里
 */
class PayFunc
{
    /**
     * XPay function
     */
    public function spliceURL($data = array())
    {
        $o = "";
        foreach ($data as $k => $v) {
            $o .= "$k=" . $v . "&";
        }
        $str = substr($o, 0, -1);
        return $str;
    }
}
