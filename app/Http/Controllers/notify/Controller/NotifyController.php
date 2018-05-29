<?php

namespace App\Http\Controllers\notify\Controller;

use App\Http\Controllers\Controller;
use App\Jobs\notify\ZoroPay;
use Illuminate\Http\Request;
use Validator;

class NotifyController extends Controller
{
    /**
     * 外部调用插入队列内容请求一次
     * @param $toUrl 异步发送通知
     * @param $jsonData 异步通知内容
     * @param $bankOrder 所需更改的订单号(表中:zoro_trade_payment_record中trx_no字段)
     */
    public function zoroNotify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'toUrl' => 'required',
            'jsonData' => 'required',
            'bankOrder' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['Status' => 'N100', 'Content' => '缺失参数'], 200);
        }

        $toUrl = request('toUrl');
        $jsonData = request('jsonData');
        $bankOrder = request('bankOrder');

        $job = (new ZoroPay($toUrl, $jsonData, $bankOrder))->onQueue('Zoro');
        $this->dispatch($job);
    }

    /**
     * 内部调用插入队列内容请求一次
     * @param $toUrl 异步发送通知
     * @param $jsonData 异步通知内容
     * @param $bankOrder 所需更改的订单号(表中:zoro_trade_payment_record中trx_no字段)
     */
    public function zoroNotifyCtrl($toUrl, $jsonData, $bankOrder)
    {
        if (!$toUrl || !$jsonData || !$bankOrder) {
            return response()->json(['Status' => 'N100', 'Content' => '缺失参数'], 200);
        }

        $job = (new ZoroPay($toUrl, $jsonData, $bankOrder))->onQueue('Zoro');
        $this->dispatch($job);
    }
}
