<?php

namespace App\Http\Controllers\gateway\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\rsaSign\SignMake;
class RsaSignController extends Controller
{
    //
    public function SignClient(Request $request)
    {
        $dn = array(
            "countryName" => 'CN', //所在国家名称
            "stateOrProvinceName" => 'Beijing', //所在省份名称
            "localityName" => 'Beijing', //所在城市名称
            "organizationName" => 'AGMTrade', //注册人姓名
            "organizationalUnitName" => 'AGMTrade', //组织名称
            "commonName" => 'AGMTrade', //公共名称
            "emailAddress" => 'AGMTrade@domain.com', //邮箱
        );
        $pass = 'AGMTrade'.mt_rand(1000,10000);
        $zoroCode = 'Zoro1000001';
        $SignMake = new SignMake($dn,$pass,$zoroCode);
        $sign = $SignMake->makeRsa();
        var_dump($sign);
    }
    
}
