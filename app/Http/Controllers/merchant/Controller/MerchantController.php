<?php

namespace App\Http\Controllers\merchant\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Services\rsaSign\SignMake;
use App\Models\User;
use ZipArchive;

class MerchantController extends Controller
{
    // success status
    protected static $successStatus = 200;

    /**
     * 登录商户后台方法
     * @params：username：用户名
     *          password：密码 
     *          captcha：验证码
     * @return：array
    */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'L100', 'Content' => '请勿修改表单' ], self::$successStatus);
        }

        // 查询用户的信息
        $merchant = User::where([
                'user_name' => request('username')
            ])
            ->whereIn('roles', [3, 4])
            ->first();

        if (!$merchant) {
            return response()->json([ 'Status' => 'L101', 'Content' => '用户不存在' ], self::$successStatus);
        }

        $username = request('username');
        $password = request('password');

        if (Auth::attempt(['user_name' => $username, 'password' => $password, 'roles' => $merchant->roles])) {
            $user = Auth::user();

            $newToken = self::checkEmptyToken($user);

            // 存储用户session
            \Session::put('merchant', $user);
            
            return response()->json([ 'Status' => '200', 'Content' => '登录成功', 'token' => $newToken ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'L103', 'Content' => '账号或密码错误' ], self::$successStatus);
        }
    }

    /**
     * 检查token是否是空 如果为空则增加token，并返回新token如果有token则返回旧token
     */
    private static function checkEmptyToken($user)
    {
        if ($user->token == '') {
            $newToken = $user->createToken('zoroPayMerchant')->accessToken;

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
     * 根据Token请求获取内容信息
     */
    public function merchantInfo(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([ 'Status' => 'AUI100', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }

        $responseArray = array();

        $responseArray = array();
        
        if ($user->roles == 3) {
            $responseArray ['roles'] = ['merchant'];

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
     * 退出登录
     * @params：
     * @return:
    */
    public function merchantLogout(Request $request)
    {
        // 清空session和cache
        \Cache::flush();
        \Session::flush();

        return response()->json([ 'Status' => '200', 'Content' => '退出成功' ], self::$successStatus);
    }

    /**
     * 查询当天和当月的入金量信息
     *
     * @params：
     * @return：
    */
    public function merchantCapitalInfo(Request $request)
    {
        // 验证登录是否过期 BEGIN
        $user = Auth::user();

        if (!$user) {
            return response()->json([ 'Status' => 'ACI101', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }
        // 验证登录是否过期 END

        // 查询当天信息
        $today = DB::table('zoro_trade_payment_record AS a')
            ->leftJoin('zoro_user_users AS b', 'a.merchant_no', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.fid')
            ->where([
                'a.status' => 2,
                'c.id' => Auth::user()->id
            ])
            ->whereBetween('a.create_time', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])
            ->first([DB::raw('sum(a.payer_pay_amount) AS todayDeposit'), DB::raw('count(a.id) AS todayTicket')]);

        // 查询当月信息
        $month = DB::table('zoro_trade_payment_record AS a')
            ->leftJoin('zoro_user_users AS b', 'a.merchant_no', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.fid')
            ->where([
                'a.status' => 2,
                'c.id' => Auth::user()->id
            ])
            ->whereBetween('a.create_time', [date('Y-m') . '-1 00:00:00', date('Y-m') . '-' . date('t') . ' 23:59:59'])
            ->first([DB::raw('sum(a.payer_pay_amount) AS monthDeposit'), DB::raw('count(a.id) monthTicket')]);

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
    public function merchantLineChartInfo(Request $request)
    {
        // 验证登录是否过期 BEGIN
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([ 'Status' => 'ACI101', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }
        // 验证登录是否过期 END

        // 查询当月每天入金量曲线信息
        $lineDepositField = array(
            'a.create_time',
            DB::raw('sum(a.payer_pay_amount) AS payer_pay_amount')
        );

        $lineDepositData = DB::table('zoro_trade_payment_record AS a')
            ->leftJoin('zoro_user_users AS b', 'a.merchant_no', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.fid')
            ->where([
                'a.status' => 2,
                'c.id' => Auth::user()->id
            ])
            ->where('a.create_time', '>', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00')
            ->select($lineDepositField)
            ->groupBy(DB::raw('left(a.create_time, 10)'))
            ->orderBy('a.create_time', 'asc')
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

    /**
     * 获取商户规则列表信息
     */
    public function merchantRuleList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'a.payName' => request('payProductName'),
            'a.payCode' => request('payWayCode')
        );

        $Rows = DB::table('zoro_pay_product_group AS a')
            ->leftJoin('zoro_user_users AS b', 'a.payCode', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.fid')
            ->where([
                'c.id' => Auth::user()->id
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->orderBy('a.order', 'asc')
            ->select([
                'a.*',
                'c.user_name'
            ])
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 禁用规则详细列表
     */
    public function merchantRuleForbidden(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRF100', 'Content' => '参数丢失' ], self::$successStatus);
        }

        $explodeId = explode(',', trim(request('id'), ','));

        $forbiddenGroupData = DB::table('zoro_pay_product_group')
            ->whereIn('id', $explodeId)
            ->pluck('disable', 'id');
    
        if (!$forbiddenGroupData) {
            return response()->json([ 'Status' => 'MRF101', 'Content' => '获取信息失败' ], self::$successStatus);
        }

        foreach ($forbiddenGroupData as $key => $value) {
            if ($key) {
                switch ($value) {
                    case 0:
                        DB::table('zoro_pay_product_group')->where([
                            'id' => $key
                        ])
                        ->update([
                            'disable' => 1
                        ]);
                        break;
                    case 1:
                        DB::table('zoro_pay_product_group')->where([
                            'id' => $key
                        ])
                        ->update([
                            'disable' => 0
                        ]);
                        break;
                }
            }
        }

        return response()->json([ 'Status' => '200', 'Content' => '更新成功' ], self::$successStatus);
    }

    /**
     * 根据商户规则中商户号获取支付名称
     */
    public function merchantRuleSelectPayName(Request $request)
    {
        $getOption = DB::table('zoro_pay_way as a')
            ->leftJoin('zoro_pay_product as b', 'a.pay_product_code', '=', 'b.product_code')
            ->where([
                'a.pay_way_code' => request('payCode')
            ])
            ->select([
                DB::raw('product_name as label'),
                DB::raw('product_name as value')
            ])
            ->get();

        if ($getOption) {
            return response()->json([ 'Status' => '200', 'Content' => $getOption ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRSPC101', 'Content' => '请创建账号通道' ], self::$successStatus);
        }
    }

    /**
     * 获取商户规则详细列表信息
     */
    public function merchantRuleDialogList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRDL100', 'Content' => '获取失败' ], self::$successStatus);
        }

        $Rows = DB::table('zoro_pay_product_group_rule')
            ->where([
                'group_id' => request('id')
            ])
            ->paginate(100);

        return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 删除规则详细列表
     */
    public function merchantRuleDialogDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRDD100', 'Content' => '参数丢失' ], self::$successStatus);
        }

        // 查询删除的组id进行更新规则
        $groupId = DB::table('zoro_pay_product_group_rule')
            ->where([
                'id' => request('id')[0]
            ])
            ->value('group_id');
 
        $deleteData = DB::table('zoro_pay_product_group_rule')
            ->whereIn('id', request('id'))
            ->delete();

        if ($deleteData) {
            self::modifyDialogRule($groupId);

            return response()->json([ 'Status' => '200', 'Content' => '删除成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRDD101', 'Content' => '删除失败' ], self::$successStatus);
        }
    }

    /**
     *  更新商户规则信息
     */
    protected static function modifyDialogRule($ID)
    {
        $rules = DB::table('zoro_pay_product_group_rule')
            ->where([
                'group_id' => $ID
            ])
            ->get();

        $rules = object2array($rules);

        $countRules = count($rules);
        $comment = '';
        $timeComment = '(';
        $ruleComment = '';
        $ruleTimeComment = '(';
        $filter = [];

        foreach ($rules as $key => $value) {
            if (($key + 1) == $countRules) {
                switch ($value ['payType']) {
                    case 1:
                        $comment .= '(单笔金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']) . '单笔金额 ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ';
                        $ruleComment .= '(`amount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']) . '`amount` ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ';
                        
                        $filter [] = 1;
                        break;
                    case 2:
                        $comment .= '(当日金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']) . '当日金额 ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ';
                        $ruleComment .= '(`dailyamount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']) . '`dailyamount` ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ';

                        $filter [] = 2;
                        break;
                    case 3:
                        $timeComment .= '(时间段 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']) . '时间段 ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ';
                        $ruleTimeComment .= '(`time` ' . self::operator(1, $value ['operator']) . '{' . $value ['value'] . '} ' . self::operator(3, $value ['connector']) . '`time` ' . self::operator(1, $value ['link_operator']) . '{' . $value ['link_value'] . '}) ';

                        $filter [] = 3;
                        break;
                    case 4:
                        $comment .= ' 代理 ' . self::operator(1, $value ['operator']) . $value ['value'];
                        break;
                }
            } else {
                switch ($value ['payType']) {
                    case 1:
                        $comment .= '(单笔金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']) . '单笔金额 ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ' . self::operator(2, $value ['link_connector']) ;
                        $ruleComment .= '(`amount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']) . '`amount` ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ' . self::operator(3, $value ['link_connector']) ;

                        $filter [] = 1;
                        break;
                    case 2:
                        $comment .= '(当日金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']) . '当日金额 ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ' . self::operator(2, $value ['link_connector']);
                        $ruleComment .= '(`dailyamount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']) . '`dailyamount` ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ' . self::operator(3, $value ['link_connector']);

                        $filter [] = 2;
                        break;
                    case 3:
                        $timeComment .= '(时间段 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']) . '时间段 ' . self::operator(1, $value ['link_operator']) . $value ['link_value'] . ') ' . self::operator(2, $value ['link_connector']);
                        $ruleTimeComment .= '(`time` ' . self::operator(1, $value ['operator']) . '{' . $value ['value'] . '} ' . self::operator(3, $value ['connector']) . '`time` ' . self::operator(1, $value ['link_operator']) . '{' . $value ['link_value'] . '}) ' . self::operator(3, $value ['link_connector']);

                        $filter [] = 3;
                        break;
                    case 4:
                        $comment .= '代理 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        break;
                }
            }
        }

        $comment = $comment . $timeComment . ')';
        $ruleComment = $ruleComment . $ruleTimeComment . ')';
        
        $comment = str_replace('()', '', $comment);
        $ruleComment = str_replace('()', '', $ruleComment);

        $filter = join(',', array_unique($filter));

        $updateGroupData = DB::table('zoro_pay_product_group')
            ->where([
                'id' => $ID
            ])
            ->update([
                'comment' => $comment,
                'modifier' => Auth::user()->user_name,
                'modifier_time' => date('Y-m-d H:i:s', time())
            ]);

        $updateNewRuleData = DB::table('zoro_pay_product_group_rule_new')
            ->where([
                'group_id' => $ID
            ])
            ->update([
                'filter' => $filter,
                'rule' => $ruleComment
            ]);
    }

    /**
     * 更改商户详细规则
     */
    public function merchantRuleDialogUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'payName' => 'required',
            'payOrder' => 'required',
            'payCode' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRDU100', 'Content' => '参数丢失' ], self::$successStatus);
        }

        $getBusinessRules = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payCode'),
                'payName' => request('payName')
            ])
            ->where('id', '<>', request('id'))
            ->count('id');

        if ($getBusinessRules > 0) {
            return response()->json([ 'Status' => 'MRDU101', 'Content' => '当前通道已配置' ], self::$successStatus);
        }

        $getBusinessOrder = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payCode'),
                'order' => request('payOrder')
            ])
            ->where('id', '<>', request('id'))
            ->count('id');

        if ($getBusinessOrder > 0) {
            return response()->json([ 'Status' => 'MRDU102', 'Content' => '当前通道排序重复' ], self::$successStatus);
        }

        $updateGroupArray = array(
            'payCode' => request('payCode'),
            'payName' => request('payName'),
            'order' => request('payOrder'),
            'modifier' => Auth::user()->user_name,
            'modifier_time' => date('Y-m-d H:i:s', time())
        );

        $updateData = DB::table('zoro_pay_product_group')
            ->where([
                'id' => request('id')
            ])
            ->update($updateGroupArray);

        if ($updateData) {
            return response()->json([ 'Status' => '200', 'Content' => '更新成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRDU103', 'Content' => '更新失败' ], self::$successStatus);
        }
    }

    /**
     * 更改商户规则
     */
    public function merchantRuleUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'payType' => 'required',
            'operator' => 'required',
            'value' => 'required',
            'connector' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRU100', 'Content' => '请将表单填写完整' ], self::$successStatus);
        }

        $updateRuleArray = array(
            'payType' => request('payType'),
            'operator' => request('operator'),
            'value' => request('value'),
            'connector' => request('connector'),
            'link_operator' => request('link_operator'),
            'link_value' => request('link_value'),
            'link_connector' => request('link_connector'),
        );

        $updateRuleArray = array_filter($updateRuleArray);

        if (!empty(request('id')) && request('id') == 'add' && !empty(request('group_id'))) {
            $updateRuleArray ['group_id'] = request('group_id');

            $groupId = request('group_id');

            $updateRuleData = DB::table('zoro_pay_product_group_rule')
                ->insertGetId($updateRuleArray);
        } else {
            $updateRuleData = DB::table('zoro_pay_product_group_rule')
                ->where([
                    'id' => request('id')
                ])
                ->update($updateRuleArray);

            $groupId = DB::table('zoro_pay_product_group_rule')
                ->where([
                    'id' => request('id')
                ])
                ->value('group_id');
        }

        $rules = DB::table('zoro_pay_product_group_rule')
            ->where([
                'group_id' => $groupId
            ])
            ->get();

        $rules = object2array($rules);

        $countRules = count($rules);
        $comment = '(';
        $ruleComment = '(';
        $filter = [];

        foreach ($rules as $key => $value) {
            if (($key + 1) == $countRules) {
                switch ($value ['payType']) {
                    case 1:
                        $comment .= '单笔金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        $ruleComment .= '`amount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);
                        
                        $filter [] = 1;
                        break;
                    case 2:
                        $comment .= '当日金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        $ruleComment .= '`dailyamount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);

                        $filter [] = 2;
                        break;
                    case 3:
                        $comment .= '时间段 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        $ruleComment .= '`time` ' . self::operator(1, $value ['operator']) . '{' . $value ['value'] . '} ' . self::operator(3, $value ['connector']);

                        $filter [] = 3;
                        break;
                    case 4:
                        $comment .= ' 代理 ' . self::operator(1, $value ['operator']) . $value ['value'];
                        break;
                }
            } else {
                switch ($value ['payType']) {
                    case 1:
                        $comment .= '单笔金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        $ruleComment .= '`amount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);

                        $filter [] = 1;
                        break;
                    case 2:
                        $comment .= '当日金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        $ruleComment .= '`dailyamount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);

                        $filter [] = 2;
                        break;
                    case 3:
                        $comment .= '时间段 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        $ruleComment .= '`time` ' . self::operator(1, $value ['operator']) . '{' . $value ['value'] . '} ' . self::operator(3, $value ['connector']);

                        $filter [] = 3;
                        break;
                    case 4:
                        $comment .= '代理 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                        break;
                }
            }
        }

        $comment = $comment . ')';
        $ruleComment = $ruleComment . ')';
        
        $comment = str_replace('()', '', $comment);
        $ruleComment = str_replace('()', '', $ruleComment);

        $filter = join(',', array_unique($filter));

        $updateGroupData = DB::table('zoro_pay_product_group')
            ->where([
                'id' => $groupId
            ])
            ->update([
                'comment' => $comment,
                'modifier' => Auth::user()->user_name,
                'modifier_time' => date('Y-m-d H:i:s', time())
            ]);

        $updateNewRuleData = DB::table('zoro_pay_product_group_rule_new')
            ->where([
                'group_id' => $groupId
            ])
            ->update([
                'filter' => $filter,
                'rule' => $ruleComment
            ]);

        if ($updateRuleData && $updateGroupData && $updateNewRuleData) {
            return response()->json([ 'Status' => '200', 'Content' => '更改成功', 'RuleId' => $updateRuleData ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRU101', 'Content' => '更改失败' ], self::$successStatus);
        }
    }

    /**
     * 计算运算符与且或关系
     */
    protected static function operator($type, $value)
    {
        switch ($type) {
            case 1:
                switch ($value) {
                    case 1:
                        return ' = ';
                        break;
                    case 2:
                        return ' <> ';
                        break;
                    case 3:
                        return ' < ';
                        break;
                    case 4:
                        return ' <= ';
                        break;
                    case 5:
                        return ' > ';
                        break;
                    case 6:
                        return ' >= ';
                        break;
                    default:
                        break;
                }
                break;
            case 2:
                switch ($value) {
                    case 1:
                        return ' <span style="color: red;">或者</span> ';
                        break;
                    case 2:
                        return ' <span style="color: red;">并且</span> ';
                        break;
                    default:
                        break;
                }
                break;
            case 3:
                switch ($value) {
                    case 1:
                        return ' || ';
                        break;
                    case 2:
                        return ' && ';
                        break;
                    default:
                        break;
                }
                break;
        }
    }

    /**
     * 添加商户规则
     */
    public function merchantRuleCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payName' => 'required',
            'payOrder' => 'required',
            'payWayCode' => 'required'
        ]);
        
        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        DB::beginTransaction();

        $insertGroupArray = array(
            'payCode' => request('payWayCode'),
            'payName' => request('payName'),
            'order' => request('payOrder'),
            'creator' => Auth::user()->user_name,
            'creator_time' => date('Y-m-d H:i:s', time())
        );

        $getBusinessRules = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payWayCode'),
                'payName' => request('payName')
            ])
            ->count('id');

        if ($getBusinessRules > 0) {
            return response()->json([ 'Status' => 'MRC101', 'Content' => '当前通道已配置' ], self::$successStatus);
        }

        $getBusinessOrder = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payWayCode'),
                'order' => request('payOrder')
            ])
            ->count('id');

        if ($getBusinessOrder > 0) {
            return response()->json([ 'Status' => 'MRC102', 'Content' => '当前通道排序重复' ], self::$successStatus);
        }

        // 查询是否有默认通道
        $getBusinessOrder = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payWayCode'),
                'default' => 1
            ])
            ->count('id');

        if ($getBusinessOrder > 0) {
            $insertGroupArray ['default'] = 0;
        } else {
            $insertGroupArray ['default'] = 1;
        }
        
        $getGroupId = DB::table('zoro_pay_product_group')->insertGetId($insertGroupArray);

        if ($getGroupId) {
            $comment = '(';
            $ruleComment = '(';
            $filter = [];

            $rules = request('rule');

            if ($rules) {
                $countRules = count($rules);

                foreach ($rules as $key => $value) {
                    $insertRuleArray [$key] = array(
                        'group_id' => $getGroupId,
                        'payType' => $value ['payType'],
                        'operator' => $value ['operator'],
                        'value' => $value ['value'],
                        'connector' => $value ['connector']
                    );

                    if (($key + 1) == $countRules) {
                        switch ($value ['payType']) {
                            case 1:
                                $comment .= '单笔金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                                $ruleComment .= '`amount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);
                                $filter [] = 1;
                                break;
                            case 2:
                                $comment .= '当日金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                                $ruleComment .= '`dailyamount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);

                                $filter [] = 2;
                                break;
                            case 3:
                                $comment .= '时间段 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                                $ruleComment .= '`time` ' . self::operator(1, $value ['operator']) . '{' . $value ['value'] . '} ' . self::operator(3, $value ['connector']);

                                $filter [] = 3;
                                break;
                            case 4:
                                $comment .= ' 代理 ' . self::operator(1, $value ['operator']) . $value ['value'];
                                break;
                        }
                    } else {
                        switch ($value ['payType']) {
                            case 1:
                                $comment .= '单笔金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                                $ruleComment .= '`amount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);

                                $filter [] = 1;
                                break;
                            case 2:
                                $comment .= '当日金额 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                                $ruleComment .= '`dailyamount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']);

                                $filter [] = 2;
                                break;
                            case 3:
                                $comment .= '时间段 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                                $ruleComment .= '`time` ' . self::operator(1, $value ['operator']) . '{' . $value ['value'] . '} ' . self::operator(3, $value ['connector']);

                                $filter [] = 3;
                                break;
                            case 4:
                                $comment .= '代理 ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(2, $value ['connector']);
                                break;
                        }
                    }
                }

                $comment = $comment . ')';
                $ruleComment = $ruleComment . ')';
                
                $comment = str_replace('()', '', $comment);
                $ruleComment = str_replace('()', '', $ruleComment);
                
                $filter = join(',', array_unique($filter));

                $insertData = DB::table('zoro_pay_product_group_rule')->insert($insertRuleArray);

                $updateData = DB::table('zoro_pay_product_group')
                    ->where([
                        'id' => $getGroupId
                    ])
                    ->update([
                        'comment' => $comment
                    ]);

                $insertNewRuleArray = array(
                    'group_id' => $getGroupId,
                    'filter' => $filter,
                    'rule' => $ruleComment
                );
                
                $insertRuleNew = DB::table('zoro_pay_product_group_rule_new')->insert($insertNewRuleArray);

                if ($getGroupId && $insertData && $updateData && $insertRuleNew) {
                    DB::commit();
                    return response()->json([ 'Status' => '200', 'Content' => '创建成功' ], self::$successStatus);
                } else {
                    DB::rollback();
                    return response()->json([ 'Status' => 'MRC103', 'Content' => '创建规则失败请重试' ], self::$successStatus);
                }
            }
        } else {
            DB::rollback();
            return response()->json([ 'Status' => 'MRC104', 'Content' => '创建栏目失败请重试' ], self::$successStatus);
        }
    }

    /**
     *  数组排序
    */
    protected static function my_sort($arrays,$sort_key,$sort_order,$sort_type=SORT_NUMERIC )
    {
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }

    /**
     * 获取商户规则商户号
     */
    public function merchantRuleSelectPayCode(Request $request)
    {
        $getPayCode = DB::table('zoro_pay_way AS a')
            ->leftJoin('zoro_user_users AS b', 'a.pay_way_code', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.fid')
            ->where([
                'c.id' => Auth::user()->id
            ])
            ->select([
                DB::raw('a.pay_way_code as label'),
                DB::raw('a.pay_way_code as value')
            ])
            ->get();

        if ($getPayCode) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayCode ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRSPC101', 'Content' => '请创建账号通道' ], self::$successStatus);
        }
    }

    /**
     * 获取用户的列表信息
    */
    public function merchantList(Request $request)
    {
        // 验证登录是否过期 BEGIN
        $user = Auth::user();

        if (!$user) {
            return response()->json([ 'Status' => 'ACI101', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }
        // 验证登录是否过期 END

        $page = request('page');
        $limit = request('limit');

        $Rows = User::where([
                'roles' => 3,
                'id' => Auth::user()->fid
            ])
            ->select([
                'id',
                'create_time',
                'user_name',
                'payname',
                'user_no',
                'mobile',
                'name',
                'email',
                'balance',
                'baseCurrency'
            ])
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 获取用户的商户号信息
    */
    public function selectUserMerchant(Request $request)
    {
        // 验证登录是否过期 BEGIN
        $user = Auth::user();

        if (!$user) {
            return response()->json([ 'Status' => 'ACI101', 'Content' => '登录超时请重新登录' ], self::$successStatus);
        }
        // 验证登录是否过期 END
        $page = request('page');
        $limit = request('limit');

        $selectArray = array(
            'b.user_no' => request('username')
        );

        $merchantData = DB::table('zoro_user_users AS b')
            ->leftJoin('zoro_user_info AS a', 'a.fid', '=', 'b.user_id')
            ->where([
                'a.id' => Auth::user()->id
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->select([
                'b.id',
                'a.user_name',
                'a.email',
                'a.baseCurrency',
                'b.user_id',
                'b.user_no',
                'b.create_time',
                'b.balance',
                'b.remark',
                'b.status'
            ])
            ->paginate($limit);

        if (!empty($merchantData)) {
            return response()->json([ 'Status' => '200', 'Content' => $merchantData ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'SUM101', 'Content' => '当前用户暂时没有商户' ], self::$successStatus);
        }
    }
}
