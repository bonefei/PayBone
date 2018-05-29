<?php

namespace App\Http\Controllers\admin\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use DB;
use Validator;
use App\Models\User;

class AdminController extends Controller
{
    protected static $successStatus = 200;
    protected static $errorStatus = 500;

    /**
     * 登录
     */
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            // 'verification' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'AL100', 'Content' => '请勿修改表单' ], self::$successStatus);
        }

        $username = request('username');
        $password = request('password');
        /* $verification = request('verification');

        if ($request->session()->get('milkcaptcha') != $verification) {
            return response()->json([ 'Status' => 'AL101', 'Content' => '验证码错误' ], self::$successStatus);
        } */

        // 商户禁止登录管理端
        $merchant = User::where([
                'user_name' => $username,
                'roles' => 3
            ])
            ->count('id');

        if ($merchant > 0) {
            return response()->json([ 'Status' => 'AL103', 'Content' => '商户禁止登录管理端' ], self::$successStatus);
        }

        if (Auth::attempt(['user_name' => $username, 'password' => $password])) {
            $user = Auth::user();

            $newToken = self::checkEmptyToken($user);

            return response()->json([ 'Status' => '200', 'Content' => '登录成功', 'token' => $newToken ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'AL102', 'Content' => '账号或密码错误' ], self::$successStatus);
        }
    }
    
    /**
     * 退出
     */
    public function adminLogout(Request $request)
    {
        return response()->json([ 'Status' => '200', 'Content' => '退出成功' ], self::$successStatus);
    }

    /**
     * 根据Token请求获取内容信息
     */
    public function adminUserInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([ 'Status' => 'AUI100', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }

        $responseArray = array();

        if ($user->roles == 1) {
            $responseArray ['roles'] = ['admin'];

            $responseArray ['menu'] = [];
        } else {
            $responseArray ['roles'] = ['editor'];

            // 子管理员查询菜单信息
            $menu = DB::table('permission AS a')
                ->leftJoin('permission_role AS b', 'a.id', '=', 'b.permission_id')
                ->leftJoin('role_user AS c', 'b.role_id', '=', 'c.role_id')
                ->where([
                    'a.is_menu' => 1,
                    'c.user_id' => $user->id
                ])
                ->select([
                    'a.id',
                    'a.name',
                    'a.fid',
                    'a.is_menu',
                    'a.icon',
                    'a.display_name'
                ])
                ->get();

            $responseArray ['menu'] = $menu;
        }

        $responseArray ['name'] = $user->name;
        $responseArray ['avatar'] = $user->avatar;
        $responseArray ['introduction'] = $user->introduction;

        return response()->json([ 'Status' => '200', 'Content' => $responseArray ], self::$successStatus);
    }

    /**
     * 检查token是否是空 如果为空则增加token，并返回新token如果有token则返回旧token
     */
    private static function checkEmptyToken($user)
    {
        if ($user->token == '') {
            $newToken = $user->createToken('zoroPayAdmin')->accessToken;

            DB::table('zoro_user_info')
                ->where('id', $user->id)
                ->update([
                    'token' => $newToken
                ]);

            return $newToken;
        } else {
            return $user->token;
        }
    }

    /**
     * 查询当天和当月的入金量信息
     *
     * @params：
     * @return：
    */
    public function adminCapitalInfo(Request $request)
    {
        // 验证登录是否过期 BEGIN
        $user = Auth::user();

        if (!$user) {
            return response()->json([ 'Status' => 'ACI101', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }
        // 验证登录是否过期 END

        // 查询当天信息
        $today = DB::table('zoro_trade_payment_record')
            ->where([
                'status' => 2
            ])
            ->whereBetween('create_time', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])
            ->first([DB::raw('sum(payer_pay_amount) AS todayDeposit'), DB::raw('count(id) AS todayTicket')]);

        // 查询当月信息
        $month = DB::table('zoro_trade_payment_record')
            ->where([
                'status' => 2
            ])
            ->whereBetween('create_time', [date('Y-m') . '-1 00:00:00', date('Y-m') . '-' . date('t') . ' 23:59:59'])
            ->first([DB::raw('sum(payer_pay_amount) AS monthDeposit'), DB::raw('count(id) monthTicket')]);

        $result ['todayDeposit'] = $today->todayDeposit;
        $result ['todayTicket'] = $today->todayTicket;
        $result ['monthDeposit'] = $month->monthDeposit;
        $result ['monthTicket'] = $month->monthTicket;

        return response()->json([ 'Status' => '200', 'Content' => $result ], self::$successStatus);
    }

    /**
     * 查询首页曲线图
     * 
     * @params：
     * @return：
    */
    public function adminLineChartInfo(Request $request)
    {
        // 验证登录是否过期 BEGIN
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([ 'Status' => 'ACI101', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }
        // 验证登录是否过期 END

        // 查询当月每天入金量曲线信息
        $lineDepositField = array(
            'create_time',
            DB::raw('sum(payer_pay_amount) AS payer_pay_amount')
        );

        $lineDepositData = DB::table('zoro_trade_payment_record')
            ->where([
                'status' => 2
            ])
            ->where('create_time', '>', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00')
            ->select($lineDepositField)
            ->groupBy(DB::raw('left(create_time, 10)'))
            ->orderBy('create_time', 'asc')
            ->get();

        $lineDepositData = object2array($lineDepositData);
        
        $data = [];
        $time = [];

        if ($lineDepositData) {
            foreach ($lineDepositData as $key => $value) {
                $data [] = $value ['payer_pay_amount'];
                $time [] = date('y/m/d', strtotime($value ['create_time']));
            }
        }

        $result ['data'] = $data;
        $result ['time'] = $time;
        
        return response()->json([ 'Status' => '200', 'Content' => $result ], self::$successStatus);
    }
}
