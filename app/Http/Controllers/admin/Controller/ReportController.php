<?php

namespace App\Http\Controllers\admin\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Validator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected static $successStatus = 200;

    /**
     * 商户余额查询信息
     */
    public function businessBalanceList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'a.payname' => request('payName'),
            'a.payno' => request('payNo'),
            'a.action' => request('payType'),
            'c.user_name' => request('payUsername')
        );

        $createTime = request('payCreateTime');

        if ($createTime) {
            $OP = !empty(request('payCreateTime') [0]) ? request('payCreateTime') [0] : '2018-01-01 00:00:00';
            $ED = !empty(request('payCreateTime') [1]) ? request('payCreateTime') [1] : date('Y-m-d H:i:s', time());
        } else {
            $OP = '2018-01-01 00:00:00';
            $ED = date('Y-m-d H:i:s', time());
        }

        $Rows = DB::table('zoro_trade_balance AS a')
            ->leftJoin('zoro_user_users AS b', 'a.payaisle', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.id')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('a.create_time', [$OP, $ED])
            ->select([
                'a.*',
                'b.user_no',
                'c.user_name'
            ])
            ->orderBy('a.id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 商户余额总数信息查询
     */
    public function businessBalanceCapital(Request $request)
    {
        $selectArray = array(
            'a.payname' => request('payName'),
            'a.payno' => request('payNo'),
            'c.user_name' => request('payUsername')
        );

        $createTime = request('payCreateTime');

        if ($createTime) {
            $OP = !empty(request('payCreateTime') [0]) ? request('payCreateTime') [0] : '2018-01-01 00:00:00';
            $ED = !empty(request('payCreateTime') [1]) ? request('payCreateTime') [1] : date('Y-m-d H:i:s', time());
        } else {
            $OP = '2018-01-01 00:00:00';
            $ED = date('Y-m-d H:i:s', time());
        }

        $receivedNum = DB::table('zoro_trade_balance AS a')
            ->leftJoin('zoro_user_users AS b', 'a.payname', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.id')
            ->where([
                'action' => 1
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('a.create_time', [$OP, $ED])
            ->sum('a.payamount');

        $unreceivedNum = DB::table('zoro_trade_balance AS a')
            ->leftJoin('zoro_user_users AS b', 'a.payname', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.id')
            ->where([
                'a.action' => 2
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('a.create_time', [$OP, $ED])
            ->sum('a.payamount');

        return response()->json([ 'Status' => 200, 'Content' => ['receivedNum' => sprintf('%.2f', $receivedNum), 'unreceivedNum' => sprintf('%.2f', $unreceivedNum)] ], self::$successStatus);
    }

    /**
     * 查询入金报表
     */
    public function depositReportList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'b.pay_way_code' => request('name'),
            'b.pay_way_name' => request('business'),
            'a.merchant_order_no' => request('payCode'),
            'a.status' => request('payStatus'),
            'd.user_name' => request('payUsername')
        );

        $createTime = request('payCreateTime');

        if ($createTime) {
            $OP = !empty(request('payCreateTime') [0]) ? request('payCreateTime') [0] : '2018-01-01 00:00:00';
            $ED = !empty(request('payCreateTime') [1]) ? request('payCreateTime') [1] : date('Y-m-d H:i:s', time());
        } else {
            $OP = '2018-01-01 00:00:00';
            $ED = date('Y-m-d H:i:s', time());
        }

        $Rows = DB::table('zoro_trade_payment_record as a')
            ->leftJoin('zoro_pay_way as b', 'a.merchant_no', '=', 'b.pay_way_code')
            ->leftJoin('zoro_user_users AS c', 'a.merchant_no', '=', 'c.user_no')
            ->leftJoin('zoro_user_info AS d', 'c.user_id', '=', 'd.id')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    } else if ($Value === '0') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('a.create_time', [$OP, $ED])
            ->select([
                'a.id',
                'a.status',
                'a.version',
                'a.create_time',
                'a.merchant_order_no',
                'a.trx_no',
                'a.bank_order_no',
                'a.payer_pay_amount',
                'a.payer_fee',
                'a.order_ip',
                'a.order_referer_url',
                'a.return_url',
                'a.notify_url',
                'a.complete_time',
                'a.pay_type_name',
                'a.notify_msg',
                'a.notify_counts',
                'a.notify_result',
                'a.now_balance',
                'a.pay_way_name',
                'a.fee_rate',
                'b.pay_way_code',
                'd.user_name'
            ])
            ->orderBy('a.id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 更改入金报表中状态列
     */
    public function depositReportStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MSU100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        DB::table('zoro_trade_payment_record')
            ->where([
                'id' => request('id')
            ])
            ->update([
                'status' => request('status')
            ]);
    }

    /**
     * 入金报表总数信息查询
     */
    public function depositReportCapital(Request $request)
    {
        $selectArray = array(
            'a.merchant_no' => request('name'),
            'a.merchant_order_no' => request('payCode'),
            'c.user_name' => request('payUsername')
        );

        $createTime = request('payCreateTime');

        if ($createTime) {
            $OP = !empty(request('payCreateTime') [0]) ? request('payCreateTime') [0] : '2018-01-01 00:00:00';
            $ED = !empty(request('payCreateTime') [1]) ? request('payCreateTime') [1] : date('Y-m-d H:i:s', time());
        } else {
            $OP = '2018-01-01 00:00:00';
            $ED = date('Y-m-d H:i:s', time());
        }

        $receivedNum = DB::table('zoro_trade_payment_record AS a')
            ->leftJoin('zoro_user_users AS b', 'a.merchant_no', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.id')
            ->where([
                'a.status' => 2
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('a.create_time', [$OP, $ED])
            ->sum('a.payer_pay_amount');

        $unreceivedNum = DB::table('zoro_trade_payment_record AS a')
            ->leftJoin('zoro_user_users AS b', 'a.merchant_no', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.id')
            ->where('a.status', '<', 2)
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('a.create_time', [$OP, $ED])
            ->sum('a.payer_pay_amount');

        return response()->json([ 'Status' => 200, 'Content' => ['receivedNum' => sprintf('%.2f', $receivedNum), 'unreceivedNum' => sprintf('%.2f', $unreceivedNum)] ], self::$successStatus);
    }

    /**
     * 获取申请出金的商户列表信息
     * @params：
     * @return：
    */
    public function merchantWithdrawalList(Request $request)
    {
        // 验证登录是否过期 BEGIN
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([ 'Status' => 'MWL101', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }
        // 验证登录是否过期 END

        $validator = Validator::make($request->all(), [
            'user_name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MWL102', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        // 查询商户信息
        $getPayCode = DB::table('zoro_user_info AS a')
            ->leftJoin('zoro_user_users AS b', 'a.id', '=', 'b.user_id')
            ->where([
                'a.roles' => 3,
                'a.user_name' => request('user_name')
            ])
            ->select([
                DB::raw('b.user_no as label'),
                DB::raw('b.user_no as value')
            ])
            ->get();

        if ($getPayCode) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayCode ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MWL101', 'Content' => '请创建账号' ], self::$successStatus);
        }
    }

    /**
     * 获取出金商户的通道列表信息
     * @params:
     * @return:
    */
    public function selectPayAisle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_way_code' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'SPA100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        $getPayAisle = DB::table('zoro_pay_product')
            ->where([
                'Merchants' => request('pay_way_code')
            ])
            ->select([
                DB::raw('product_name as label'),
                DB::raw('product_name as value')
            ])
            ->get();

        if ($getPayAisle) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayAisle ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'SPA101', 'Content' => '请先配置通道' ], self::$successStatus);
        }
    }

    /**
     * 申请商户出金
     * @params：
     * @return：
    */
    public function applyMerchantWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payUser' => 'required',
            'pay_way_code' => 'required',
            'payBankAcc' => 'required',
            'payBankName' => 'required',
            'payType' => 'required',
            'pay_aisle' => 'required',
            'pay_way_code' => 'required',
            'payamount' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'AMW100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        // 查询用户当前余额
        $userBalance = DB::table('zoro_user_info')
            ->where([
                'user_name' => request('payUser')
            ])
            ->value('balance');

        if ($userBalance - request('payamount') < 0) {
            return response()->json([ 'Status' => 'AMW101', 'Content' => '当前用户余额不足，禁止出金' ], self::$successStatus);
        }

        // 验证商户当前余额
        $merchantBalance = DB::table('zoro_user_users')
            ->where([
                'user_no' => request('pay_way_code')
            ])
            ->value('balance');

        if ($merchantBalance - request('payamount') < 0) {
            return response()->json([ 'Status' => 'AMW103', 'Content' => '当前商户余额不足，禁止出金' ], self::$successStatus);
        }

        DB::beginTransaction();

        // 添加出金表记录
        $orderNo = 'AGMT' . date('YmdHis') . time();

        $withdrawalId = DB::table('zoro_trade_withdrawal_record')
            ->insert([
                'create_time' => date('Y-m-d H:i:s'),
                'status' => request('status'),
                'creater' => 'ZoroPay',
                'product_name' => 'product_name',
                'merchant_order_no' => $orderNo,
                'trx_no' => $orderNo,
                'merchant_no' => request('pay_way_code'),
                'apply_amount' => -request('payamount'),
                'receiver_name' => request('payBankName'),
                'order_ip' => '127.0.0.1',
                'pay_way_name' => request('pay_aisle'),
                'now_balance' => sprintf('%.2f', $userBalance - request('payamount')),
                'pay_type_name' => request('payType'),
                'pay_type_code' => request('payBankAcc')
            ]);

        if (!$withdrawalId) {
            DB::rollback();
            
            return response()->json([ 'Status' => 'AMW102', 'Content' => '写入数据失败,请稍后再试' ], self::$successStatus);
        }

        // 添加trade_balance数据 BEGIN
        $balanceInsertId = DB::table('zoro_trade_balance')
            ->insert([
                'payname' => request('pay_aisle'),
                'action' => 2,
                'peybalance' => sprintf('%.2f', $userBalance - request('payamount')),
                'payamount' => -request('payamount'),
                'peyfee' => 0,
                'paytype' => request('payType'),
                'payaisle' => request('pay_way_code'),
                'payno' => $orderNo,
                'create_time' => date('Y-m-d H:i:s'),
                'pay_way_code' => request('pay_way_code'),
                'payBankAcc' => request('payBankAcc')
            ]);

        if (!$balanceInsertId) {
            DB::rollback();
            
            return response()->json([ 'Status' => 'AMW102', 'Content' => '写入数据失败,请稍后再试' ], self::$successStatus);
        }

        if (request('status') == 1) { // 待出金的不做余额变动
            DB::commit();
            
            return response()->json([ 'Status' => '200', 'Content' => '出金成功' ], self::$successStatus);  
        }

        if (request('status') == 2) { // 出金成功状态直接返回
            // 更新商户信息
            $userData = DB::table('zoro_user_info')
                ->where([
                    'user_name' => request('payUser')
                ])
                ->update([
                    'balance' => sprintf('%.2f', $userBalance - request('payamount'))
                ]);

            $merchantData = DB::table('zoro_user_users')
                ->where([
                    'user_no' => request('pay_way_code')
                ])
                ->update([
                    'balance' => sprintf('%.2f', $merchantBalance - request('payamount'))
                ]);

            if ($userData && $merchantData) {
                DB::commit();

                return response()->json([ 'Status' => '200', 'Content' => '出金成功' ], self::$successStatus);    
            } else {
                DB::rollback();
                
                return response()->json([ 'Status' => 'AMW103', 'Content' => '更新商户信息失败' ], self::$successStatus);
            }
        }
        // 添加trade_balance数据 END
    }

    /**
     * 获取出金汇总金额
     * @params：
     * @return：
    */
    public function withdrawalReportCapital(Request $request)
    {
        $selectArray = array(
            'merchant_no' => request('name'),
            'pay_way_name' => request('payCode'),
            'merchant_order_no' => request('payCode'),
        );

        $createTime = request('payCreateTime');

        if ($createTime) {
            $OP = !empty(request('payCreateTime') [0]) ? request('payCreateTime') [0] : '2018-01-01 00:00:00';
            $ED = !empty(request('payCreateTime') [1]) ? request('payCreateTime') [1] : date('Y-m-d H:i:s', time());
        } else {
            $OP = '2018-01-01 00:00:00';
            $ED = date('Y-m-d H:i:s', time());
        }

        $receivedNum = DB::table('zoro_trade_withdrawal_record')
            ->where([
                'status' => 2
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('create_time', [$OP, $ED])
            ->sum('apply_amount');

        $unreceivedNum = DB::table('zoro_trade_withdrawal_record')
            ->where([
                'status' => 1
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('create_time', [$OP, $ED])
            ->sum('apply_amount');

        return response()->json([ 'Status' => 200, 'Content' => ['receivedNum' => sprintf('%.2f', $receivedNum), 'unreceivedNum' => sprintf('%.2f', $unreceivedNum)] ], self::$successStatus);
    }

    /**
     * 查询出金报表
     */
    public function withdrawalReportList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'b.pay_way_code' => request('name'),
            'a.pay_way_name' => request('business'),
            'a.merchant_order_no' => request('payCode'),
            'a.status' => request('payStatus'),
            'd.user_name' => request('payUsername')
        );

        $createTime = request('payCreateTime');

        if ($createTime) {
            $OP = !empty(request('payCreateTime') [0]) ? request('payCreateTime') [0] : '2018-01-01 00:00:00';
            $ED = !empty(request('payCreateTime') [1]) ? request('payCreateTime') [1] : date('Y-m-d H:i:s', time());
        } else {
            $OP = '2018-01-01 00:00:00';
            $ED = date('Y-m-d H:i:s', time());
        }

        $Rows = DB::table('zoro_trade_withdrawal_record as a')
            ->leftJoin('zoro_pay_way as b', 'a.merchant_no', '=', 'b.pay_way_code')
            ->leftJoin('zoro_user_users AS c', 'a.merchant_no', '=', 'c.user_no')
            ->leftJoin('zoro_user_info AS d', 'c.user_id', '=', 'd.id')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    } else if ($Value === '0') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->whereBetween('a.create_time', [$OP, $ED])
            ->select([
                'a.id',
                'a.status',
                'a.create_time',
                'a.merchant_order_no',
                'a.trx_no',
                'a.bank_order_no',
                'a.payer_fee',
                'a.order_ip',
                'a.complete_time',
                'a.product_name',
                'a.merchant_no',
                'a.apply_amount',
                'a.receiver_name',
                'a.now_balance',
                'a.pay_type_name',
                'a.pay_type_code',
                'a.pay_way_name',
                'b.pay_way_code',
                'd.user_name'
            ])
            ->orderBy('a.id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 更改出金报表中状态列
     */
    public function withdrawalReportStatusUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MSU100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        $updateData = DB::table('zoro_trade_withdrawal_record')
            ->where([
                'id' => request('id')
            ])
            ->update([
                'status' => request('status')
            ]);

        if ($updateData) {
            return response()->json([ 'Status' => '200', 'Content' => '操作成功' ], self::$successStatus);    
        } else {
            return response()->json([ 'Status' => 'WRSU103', 'Content' => '操作失败' ], self::$successStatus);
        }
    }

    /**
     * 获取发送报表设置列表
    */
    public function reportSendSettingList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');

        $selectArray = array(
            'b.user_name' => request('user_name')
        );

        $Rows = DB::table('zoro_report_send AS a')
            ->leftJoin('zoro_user_info AS b', 'a.user_id', '=', 'b.id')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->select([
                'a.*',
                'b.user_name'
            ])
            ->orderBy('a.id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 获取所有商户名称
    */
    public function selectUserOption(Request $request)
    {
        $getPayUser = DB::table('zoro_user_info')
            ->where([
                'roles' => 3
            ])
            ->select([
                DB::raw('user_name as label'),
                DB::raw('id as value')
            ])
            ->get();

        if ($getPayUser) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayUser ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRSPC101', 'Content' => '没有商户' ], self::$successStatus);
        }
    }

    /**
     * 获取用户的商户号
    */
    public function selectUserNoOption(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'SUNO100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        $getPayUser = DB::table('zoro_user_info AS a')
            ->leftJoin('zoro_user_users AS b', 'a.id', '=', 'b.user_id')
            ->where([
                'a.id' => request('id')
            ])
            ->select([
                DB::raw('b.user_no as label'),
                DB::raw('b.user_no as value')
            ])
            ->get();

        if ($getPayUser) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayUser ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'SUNO101', 'Content' => '没有商户' ], self::$successStatus);
        }
    }

    /**
     * 创建用户的报表发送规则
    */
    public function reportSendSettingCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'user_no' => 'required',
            'type' => 'required',
            'cycle' => 'required',
            'zone' => 'required',
            'status' => 'required',
            'accept_email' => 'required',
            'remarks' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'RSSC100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        // 创建新的商户发送报表规则
        $insertData = DB::table('zoro_report_send')
            ->insertGetId([
                'type' => request('type'),
                'user_id' => request('user_name'),
                'user_no' => implode(',', request('user_no')),
                'cycle' => request('cycle'),
                'zone' => request('zone'),
                'status' => request('status'),
                'accept_email' => request('accept_email'),
                'remarks' => request('remarks'),
                'create_time' => date('Y-m-d H:i:s')
            ]);

        if ($insertData > 0) {
            return response()->json([ 'Status' => '200', 'Content' => '添加成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'RSSC101', 'Content' => '创建失败' ], self::$successStatus);
        }
    }

    /**
     * 更新用户的报表发送规则信息
    */
    public function updateReportSend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'user_name' => 'required',
            'user_no' => 'required',
            'type' => 'required',
            'cycle' => 'required',
            'zone' => 'required',
            'status' => 'required',
            'accept_email' => 'required',
            'remarks' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'URS100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        // 创建新的商户发送报表规则
        $updateData = DB::table('zoro_report_send')
            ->where([
                'id' => request('id')
            ])
            ->update([
                'type' => request('type'),
                'user_name' => request('user_name'),
                'user_no' => implode(',', request('user_no')),
                'cycle' => request('cycle'),
                'zone' => request('zone'),
                'status' => request('status'),
                'accept_email' => request('accept_email'),
                'remarks' => request('remarks'),
                'create_time' => date('Y-m-d H:i:s')
            ]);

        if ($updateData) {
            return response()->json([ 'Status' => '200', 'Content' => '添加成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'RSSC101', 'Content' => '创建失败' ], self::$successStatus);
        }
    }

    /**
     * 删除用户的报表发送规则
    */
    public function reportSendDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'RSD100', 'Content' => '缺失参数' ], self::$successStatus);
        }

        $deleteData = DB::table('zoro_report_send')
            ->where([
                'id' => request('id')
            ])
            ->delete();

        if ($deleteData > 0) {
            return response()->json([ 'Status' => '200', 'Content' => '删除成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'RSD101', 'Content' => '删除失败' ], self::$successStatus);
        }
    }

    /**
     * 查询选择出金通道的版本号
    */
    public function getPayAisleVersion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant' => 'required',
            'aisle' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'GPAV100', 'Content' => '缺失参数' ], self::$successStatus);
        }
        
        // 查询当前通道的版本号
        $versionData = DB::table('zoro_pay_product')
            ->where([
                'merchants' => request('merchant'),
                'product_name' => request('aisle')
            ])
            ->first(['version', 'Account']);

        if ($versionData) {
            return response()->json([ 'Status' => '200', 'Content' => $versionData ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'GPAV101', 'Content' => '获取版本号错误' ], self::$successStatus);
        }
    }

    /**
     * 上传批量excel文件
    */
    public function uploadBatchExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'excel' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'UBE100', 'Content' => '请完善信息再提交表单' ], self::$successStatus);
        }

        $excel_file_path = $_FILES['excel']['tmp_name'];
        $res = [];

        Excel::load($excel_file_path, function($reader) use( &$res ) {
            $reader = $reader->getSheet(0);
            
            $res = $reader->toArray();
        });

        $insert = [];
        
        foreach($res as $key => $value) {
            // 判断数据不足的直接unset
            if (count(array_filter($value)) != 9) {
                unset($res [$key]);
            } else {
                $arr['pay_type_code'] = $res [$key] [0];
                $arr['receiver_name'] = $res [$key] [1];
                $arr['pay_type_name'] = $res [$key] [2];
                $arr['create_time'] = $res [$key] [3];
                $arr['pay_way_name'] = $res [$key] [4];
                $arr['pay_way_code'] = $res [$key] [5];
                $arr['apply_amount'] = $res [$key] [6];
                $arr['status'] = $res [$key] [7];
                $arr['merchant_order_no'] = $res [$key] [8];
                $arr['trx_no'] = $res [$key] [8];
                $arr['creater'] = 'ZoroPay';
                
                $insert [] = $arr;
            }
        }

        if (!$res) {
            return response()->json([ 'Status' => 'UBE101', 'Content' => '数据不能为空' ], self::$successStatus);
        }

        $insertData = DB::table('zoro_trade_withdrawal_record')
            ->insert($insert);

        if ($insertData) {
            return response()->json([ 'Status' => '200', 'Content' => '上传成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'UBE102', 'Content' => '上传失败' ], self::$successStatus);
        }
    }

    /**
     * 入金报表中重发报文操作
     */
    public function depositReportRepeatMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'DRRM100', 'Content' => '参数丢失' ], self::$successStatus);
        }

        $getRecord = DB::table('zoro_trade_payment_record')
            ->where([
                'id' => request('id'),
                'status' => 2
            ])
            ->first();

        if (!$getRecord) {
            return response()->json([ 'Status' => 'DRRM101', 'Content' => '无此支付单号' ], self::$successStatus);
        }

        // 等待调用大神代码
    }
}
