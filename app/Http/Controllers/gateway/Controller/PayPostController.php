<?php

namespace App\Http\Controllers\gateway\Controller;

use App\Http\Controllers\Controller;
use App\Http\Controllers\notify\Controller\NotifyController;
use App\Services\CLogFileHandler;
use App\Services\LOG;
use App\Services\payApi\PayCommon;
use App\Services\payApi\PayNotify;
use App\Services\rsaSign\FmtSign;
use DB;
use Illuminate\Http\Request;
use Validator;

$logHandler = new CLogFileHandler(storage_path() . "/pay_logs/" . date('Y-m-d') . 'notify-sign.log');
$log = Log::Init($logHandler, 15);

class PayPostController extends Controller
{
    protected $clientIp;
    protected $fromUri;
    protected $Origin;
    public function __construct()
    {
    }
    public function index(Request $request)
    {
        $this->clientIp = $request->ip();
        $this->fromUri = $request->header('Referer');
        $this->Origin = $request->header('Host');
        //echo 'ip:'.$ip.',from:'.$formUri;
        $cdr = Validator::make($request->all(), ['CerPost' => 'required']);
        if ($cdr->fails()) {
            $data = array('status' => 'E001', 'msg' => '参数不合法', 'from' => $this->fromUri);
            //return error()->json($data, 200);
            return view('gateway.error', ['error' => $data]);
            //return response()->view('error.500', $data, 401);
        }

        $data = $olddata = $cdr = $request->all();
        //$data['pay_way_code'] = 'ipsv7';
        $payWay = $this->getPayWay($data['pay_way_code'], $olddata['Amount'], (isset($olddata['way']) ? $olddata['way'] : ''));
        //dump(DB::getQueryLog());
        // dump($payWay);die;
        if (!$payWay) {
            return view('gateway.error', ['error' => array('status' => 'Y00', 'msg' => '暂无可用支付通道', 'from' => $this->fromUri)]);
            //return json_encode(array('status' => 'Y00', 'msg' => '暂无可用支付通道'));
        }
        $payCommon = new PayCommon();
        $black = $payCommon->BlackNames($olddata['Billno'], $this->clientIp, $data['pay_way_code']);
        if ($black !== true) {
            $black['from'] = $this->fromUri;
            return view('gateway.error', ['error' => $black]);
        }
        $cerpath = storage_path() . $payWay->RsaPub;
        unset($data['CerPost']);
        ksort($data);
        $olddata = $data;
        $olddata['clientIp'] = $this->clientIp;
        $olddata['fromUri'] = $this->fromUri;
        $olddata['Account'] = $payWay->Account;
        $payname = $payWay->product_name;
        $data = implode('&', $data);
        $sign = base64_decode($cdr['CerPost']);
        //var_dump($data);
        //公钥验签
        //var_dump($cdr,$public_key);
        $pub = file_get_contents($cerpath);
        $public_key = openssl_pkey_get_public($pub);
        $FmtSign = new FmtSign();
        $verify = openssl_verify($data, $FmtSign->hex2asc($sign), $public_key, OPENSSL_ALGO_SHA256);
        //DB::connection()->enableQueryLog(); // 开启查询日志
        $zoroBankCode = '';

        $bankCode = $this->getBank($payname);
        $bankCode['0000'] = '其他银行';
        $signalBank = '';
        if (isset($olddata['bank']) && $olddata['bank'] != '' && $olddata['bank'] != null) {
            $zoroBankCode = $olddata['bank'];
            unset($olddata['bank']);
            $signalBank = $this->getSingleBank($payname, $zoroBankCode);
        }
        if (1 == $verify) {
            $olddata['BuyProduct'] = isset($olddata['BuyProduct']) ? $olddata['BuyProduct'] : 'Deposit';
            $isCredit = $payCommon::getKey($payname, $olddata['pay_way_code'], 'isCredit');
            return view('gateway.welcome', ['isCredit' => $isCredit, 'signalBank' => $signalBank, 'param' => $olddata, 'bankCode' => $bankCode, 'payment_away' => $payname, 'zoroCode' => $olddata['pay_way_code']]);
        } else {
            return view('gateway.error', ['error' => array('status' => 'E002', 'msg' => '参数不合法', 'from' => $this->fromUri)]);
            //return json_encode(array('status' => 'Y01', 'msg' => '参数不合法'));
        }
        //var_dump($cdr,$verify);
    }
    private function getBank($payname)
    {
        $bank = DB::table('zoro_pay_bank_list')
            ->whereNotNull($payname)
            ->where($payname, '<>', '')
            ->pluck('bankName', $payname)
            ->toArray();
        return $bank;
    }
    private function getSingleBank($payname, $zoroBankCode)
    {
        // echo $zoroBankCode;
        DB::connection()->enableQueryLog(); // 开启查询日志

        $bank = DB::table('zoro_pay_bank_list')
            ->whereNotNull($payname)
            ->where($payname, '<>', '')
            ->where('zoro', $zoroBankCode)
            ->value($payname);
        //var_dump(DB::getQueryLog()); // 获取查询日志

        return $bank;
    }
    private function getPayWay($PayWayCode, $Amount, $post_way = '')
    {
        DB::connection()->enableQueryLog();
        //dump(DB::getQueryLog());die;
        $day = gmdate('Y-m-d', time() + 8 * 3600);
        $dateNow = gmdate('Y-m-d H:i:s', time() + 8 * 3600);
        $timeNow = gmdate('H:i:s', time() + 8 * 3600);
        $operatorArr = ['=', '<>', '<', '<=', '>', '>='];
        $connectorArr = ['||', '&&', ''];
        $fromUri = $this->Origin;
        $dailySum = DB::table('zoro_trade_payment_record')
            ->where('status', 2)
            ->where('pay_success_time', 'like', '%' . $day . '%')
            ->pluck(DB::raw('sum(payer_pay_amount)'), 'pay_way_name')
            ->all();
        $payif = DB::table('zoro_pay_way as a')
            ->join('zoro_pay_product as b', 'a.pay_product_code', 'b.product_code')
            ->join('zoro_pay_product_group as c', function ($join) {
                $join->on('a.pay_way_code', 'c.payCode')
                    ->on('b.product_name', 'c.payName');
            })
            ->join('zoro_pay_product_group_rule_new as d', 'c.id', 'd.group_id')
            ->where(function ($query) use ($post_way, $PayWayCode) {
                $query->where('a.pay_way_code', $PayWayCode);
                if ($post_way) {
                    $query->where('b.product_name', $post_way);
                } else {
                    $query->where('b.is_live', 1)
                        ->where('b.is_on', 1)
                        ->where('c.disable', 1);
                }
            })
            ->where(function ($query) use ($fromUri) {
                $query->whereNull('from_url')
                    ->orWhere('from_url', '')
                    ->orWhere('from_url', 'like', '%' . $fromUri . '%');
            })
            ->orderBy(DB::raw('`c`.`order`'))
            ->select('a.*', 'b.*', 'd.rule', 'd.filter', 'c.order')
            ->get();
        if ($post_way) {
            //传入支付通道名称则直接返回通道，不进行规则控制
            // dump($payif,is_array($payif),isset($payif[0]));
            if (isset($payif[0])) {
                return $payif[0];
            } else {
                return false;
            }

        }
        //替换字段映射
        $rule_arr = ['`amount`' => $Amount, '`time`' => strtotime($dateNow), '`dailyamount`' => 0.00];
        // dump($payif);die;
        $payNameResult = false;
        if (count($payif) <= 0) {
            $payNameResult = false;
        } else {
            foreach ($payif as $kk => $vv) {
                if (isset($dailySum[$vv->product_name])) {
                    $urle_arr['`dailyamount`'] = $dailySum[$vv->product_name];
                } else {
                    $urle_arr['`dailyamount`'] = 0.00;
                }
                $a = '';
                $rule = $vv->rule;
                $result = array();
                $filter = DB::table('zoro_pay_product_filter')->whereIn('filter_id', explode(',', $vv->filter))->pluck('filter_en', 'filter_id');
                foreach ($filter as $k => $v) {
                    //echo $k.$v;
                    $rule = preg_replace('/' . $v . '/i', $rule_arr[$v], $rule);
                    $rule = preg_replace_callback("/\{(.*?)\}/i", function ($match) use ($day) {
                        return strtotime($day . ' ' . $match[1]);
                    }, $rule);
                }
                eval("\$a = $rule===true?1:2;");
                if ($a == 1) {
                    $payNameResult = $vv;
                    break;
                }

            }
            if ($payNameResult == false) {
                //没有找到符合条件的选择默认通道
                // foreach($payif as $kk =>$vv){
                //     if($vv->default == 1){
                //         $payNameResult = $vv;
                //     }
                // }
            }
        }
        return $payNameResult;
    }
    public function finish(Request $request, $notify, $zoroCode)
    {
        //接到商户返回报文
        //echo '支付成功---test';
        $payNotify = new PayNotify();
        $req = $request->all();
        $payResult = $payNotify->getPayway($notify, $zoroCode, $req);
        // dump($payResult);die;
        //echo '验签结果：' . ($payResult == true ? '成功' : '失败');
        //'return_url'=>'http://getway.com/api/finish',//同步通知地址
        //'server_url'=>'http://getway.com/api/finish',//异步通知地址
        //通知后台
        if (isset($payResult['errorCode'])) {
            if ($payResult['errorCode']) {
                //['error' => array('status' => 'E002', 'msg' => '参数不合法', 'from' => $this->fromUri)]
                //'订单号：' . $payResult['orderNo'] . '交易失败:' . $payResult['respDesc']
                return view('gateway.error', ['error' => array('status' => 'E002', 'msg' => '订单号：' . $payResult['orderNo'] . '交易失败:' . $payResult['respDesc'], 'from' => $this->fromUri)]);
            }
        }
        $attach = $payResult['PayAttach'];
        $att_arr = explode('-|', $attach);
        $notifyResult = [
            'Amount' => $payResult['Amount'],
            'Billno' => $payResult['PayBill'],
            'Date' => $payResult['Date'],
            'Success' => $payResult['PayStatus'],
            'Msg' => ($payResult != false ? 'Pay Success' : 'Sign Failed'),
            'Currency' => $payResult['CurrencyType'],
            'PayName' => $notify,
        ];
        //签名加密
        $p12 = DB::table('zoro_pay_way')->where('pay_way_code', $zoroCode)->select('RsaP12', 'RsaServerPass')->first();
        //$notifyResult = implode('&',$notifyResult);
        $notifyResultStr = '';
        ksort($notifyResult);
        foreach ($notifyResult as $k => $v) {
            $notifyResultStr .= $k . '=' . $v . '&';
        }
        $notifyResult = rtrim($notifyResultStr, '&');
        // dump($notifyResult);
        $SignM = new FmtSign();
        $verify_sign = $SignM->RSASign($notifyResult, storage_path() . $p12->RsaP12, $p12->RsaServerPass);

        if (!$verify_sign) {
            return view('gateway.error', ['error' => array('status' => 'E003', 'msg' => '签名错误', 'from' => $this->fromUri)]);
        }
        $header_param = base64_encode($notifyResult . '--**--' . base64_encode($verify_sign));
        Log::INFO('支付通道:' . $notify . ',生成签名报文[' . $header_param . ']【签名:' . $verify_sign . ',原文:' . $notifyResult . ',私钥路径:' . $p12->RsaP12 . ',私钥密码:' . $p12->RsaServerPass . '】');
        // dump($header_param,$payResult,$att_arr);
        // $pub = file_get_contents(public_path().'/wl_file/client/Zoro1000001/AGMZoro1000001.cer');
        // $public_key = openssl_pkey_get_public($pub);
        // $FmtSign = new FmtSign();
        // $verify = openssl_verify($notifyResult, $FmtSign->hex2asc($verify_sign), $public_key, OPENSSL_ALGO_SHA256);
        // echo $verify;die;
        //http://trade-client.agm18.com:8186,http://TradeClient.agm18.com
        (new NotifyController())->zoroNotifyCtrl($att_arr[1] . '?notify=' . $header_param, json_encode(array('testnotify' => 'success')), $payResult['PayBill']);
        // dump($header_param);
        header('Location: ' . $att_arr[0] . '?notify=' . $header_param);
    }

    public function finishPay(Request $request, $payname)
    {
        echo $payname;
        dump($request);
        //(new NotifyController())->zoroNotifyCtrl('http://trade-client.agm18.com:8186/api/aaa', json_encode(array('testnotify' => 'success')));
    }

    /**
     * makePost 选择银行提交到第三方支付
     * @param PostParam
     * @return redrict
     */
    public function makePost(Request $request)
    {
        $crd = Validator::make($request->all(), []);
        if ($crd->fails()) {
            $data = array('status' => 'E001', 'msg' => '参数不合法1');
            //return error()->json($data, 200);
            return response()->view('errors.401', $data, 401);
        }

        $cdr = $request->all();
        $html_text = PayCommon::insertRecord($cdr);
        if ($html_text) {
            if (isset($html_text['Status'])) {
                return json_encode(array('status' => 'E003', 'msg' => $html_text['content']));
            }
            return json_encode(array('status' => 200, 'msg' => $html_text));
        } else {
            return json_encode(array('status' => 'E002', 'msg' => '失败，请重试'));
        }

    }

    public static function asc2hex($str)
    {
        return chunk_split(bin2hex($str), 2, '');
    }

    public static function hex2asc($str)
    {
        $len = strlen($str);
        $data = "";
        for ($i = 0; $i < $len; $i += 2) {
            $data .= chr(hexdec(substr($str, $i, 2)));
        }

        return $data;
    }
    public static function pem2der($pem_data)
    {
        $begin = "CERTIFICATE-----";
        $end = "-----END";
        $pem_data = substr($pem_data, strpos($pem_data, $begin) + strlen($begin));
        $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
        $der = base64_decode($pem_data);
        return $der;
    }

    public static function der2pem($der_data)
    {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }
}
