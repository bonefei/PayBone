<?php

namespace App\Http\Controllers\admin\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\rsaSign\SignMake;
use Validator;
use App\Models\User;
use DB;
use ZipArchive;
use Illuminate\Support\Facades\Auth;

class MerchantsController extends Controller
{
    protected static $successStatus = 200;

    /**
     * 商户列表查询信息
     */
    public function merchantList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'user_name' => request('username'),
            'user_no' => request('merchantNumber'),
            'name' => request('name'),
            'mobile' => request('mobile'),
            'email' => request('email'),
        );

        $Rows = User::where([
                'roles' => 3
            ])
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
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
                'baseCurrency',
                'introduction'
            ])
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 创建新商户
     */
    public function merchantCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'create_time' => 'required',
            'user_name' => 'required',
            'payname' => 'required',
            'name' => 'required',
            'mobile' => 'required',
            'email' => 'required|email',
            'baseCurrency' => 'required',
            'password' => 'required|min:6|max:32',
            'payPassword' => 'required|min:6|max:32'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        // 插入账号登录表 BEGIN
        $insertUserInfoArray = array(
            'create_time' => request('create_time'),
            'user_name' => request('user_name'),
            'payname' => request('payname'),
            'name' => request('name'),
            'mobile' => request('mobile'),
            'email' => request('email'),
            'password' => bcrypt(request('password')),
            'pay_pwd' => bcrypt(request('payPassword')),
            'introduction' => request('introduction'),
            'province' => '北京',
            'city' => '北京市',
            'county' => '大兴区',
            'baseCurrency' => request('baseCurrency')
        );

        $selectIdCount = User::where([
            'user_name' => $insertUserInfoArray ['user_name'],
            'roles' => 3
        ])->count('id');

        if ($selectIdCount > 0) {
            return response()->json([ 'Status' => 'MC101', 'Content' => '用户名已存在' ], self::$successStatus);
        }

        $getUserNumber = User::where([
            'roles' => 3
        ])->orderBy('id', 'desc')->value('user_no');

        $createUserNo = (int)str_replace('Zoro', '', $getUserNumber) + 1;

        $insertUserInfoArray ['user_no'] = 'Zoro' . $createUserNo;

        $insertUserInfoArray = array_filter($insertUserInfoArray);

        DB::beginTransaction();
        
        $insertUsersInfo = User::create($insertUserInfoArray);
        // 插入账号登录表 END

        if (!$insertUsersInfo) {
            DB::rollback();

            return response([ 'Status' => 'MC103', 'Content' => '创建数据失败请稍后再试' ], self::$successStatus);
        }

        // 更新自己的fid
        $updateFID = DB::table('zoro_user_info')
            ->where([
                'id' => $insertUsersInfo->id
            ])
            ->update([
                'fid' => $insertUsersInfo->id
            ]);

        if ($updateFID) {
            DB::commit();

            return response([ 'Status' => '200', 'Content' => '创建成功' ], self::$successStatus);
        } else {
            DB::rollback();

            return response([ 'Status' => 'MC103', 'Content' => '创建数据失败请稍后再试' ], self::$successStatus);
        }
        // 插入账号信息 END
    }

    /**
     * 更改商户信息
     */
    public function merchantUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'user_name' => 'required',
            'payname' => 'required',
            'name' => 'required',
            'mobile' => 'required',
            'email' => 'required|email',
            'baseCurrency' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MU100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $updateUserInfoArray = array(
            'user_name' => request('user_name'),
            'payname' => request('payname'),
            'name' => request('name'),
            'mobile' => request('mobile'),
            'email' => request('email'),
            'introduction' => request('introduction'),
            'baseCurrency' => request('baseCurrency')
        );

        if (!empty(request('password'))) {
            $validator = Validator::make($request->all(), [
                'password' => 'required|min:6|max:32',
            ]);

            if ($validator->fails()) {
                return response()->json([ 'Status' => 'MU100', 'Content' => '请检测密码格式是否正确' ], self::$successStatus);
            }

            $updateUserInfoArray ['password'] = bcrypt(request('password'));
        }

        if (!empty(request('payPassword'))) {
            $validator = Validator::make($request->all(), [
                'payPassword' => 'required|min:6|max:32',
            ]);

            if ($validator->fails()) {
                return response()->json([ 'Status' => 'MU100', 'Content' => '请检测密码格式是否正确' ], self::$successStatus);
            }

            $updateUserInfoArray ['pay_pwd'] = bcrypt(request('payPassword'));
        }

        $updateUserInfoArray = array_filter($updateUserInfoArray);

        $updateData = User::where([
            'id' => request('id')
        ])->update($updateUserInfoArray);

        if ($updateData) {
            return response()->json([ 'Status' => '200', 'Content' => '更改成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MU101', 'Content' => '更改失败' ], self::$successStatus);
        }
    }

    /**
     * 下载商户证书
     */
    public function merchantDownload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MD100', 'Content' => '信息不符请稍后在试' ], self::$successStatus);
        }

        $getUser = DB::table('zoro_user_users AS a')
            ->leftJoin('zoro_user_info AS b', 'a.user_id', '=', 'b.id')
            ->where([
                'a.id' => request('id'),
                'b.id' => request('user_id')
            ])
            ->select([
                'b.name',
                'b.payname',
                'b.email',
                'a.user_no'
            ])
            ->first();

        $dn = array(
            "countryName" => 'CN',
            "stateOrProvinceName" => '北京市',
            "localityName" => '大兴区',
            "organizationName" => $getUser->name,
            "organizationalUnitName" => $getUser->payname,
            "commonName" => $getUser->name,
            "emailAddress" => $getUser->email,
        );
        
        $zip = new ZipArchive();

        $zipFile = storage_path() . '/wl_file/client/' . $getUser->user_no . '/' . $getUser->user_no . '.zip';
        $folder = storage_path() . '/wl_file/client/' . $getUser->user_no . '/';

        if ($zip->open($zipFile, ZipArchive::OVERWRITE) === true) {
            $zip->close(); //关闭处理的zip文件 
        } else {
            if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
                $getPayWay = DB::table('zoro_pay_way')
                    ->where([
                        'pay_way_code' => $getUser->user_no
                    ])
                    ->select([
                        'RsaClient',
                        'RsaServer'
                    ])
                    ->first();

                if (!file_exists($folder . $getPayWay->RsaClient . '.p12') && !file_exists($folder . $getPayWay->RsaServer . '.cer')) {
                    $pass = 'AGMTrade' . mt_rand(1000, 10000);
                    $SignMake = new SignMake($dn, $pass, $getUser->user_no);
                    $sign = $SignMake->makeRsa();

                    if ($sign == true) {
                        self::addFileToZip($folder, $zip); //调用方法，对要打包的根目录进行操作，并将ZipArchive的对象传递给方法 
                        $zip->close(); //关闭处理的zip文件 
                    }
                } else {
                    self::addFileToZip($folder, $zip); //调用方法，对要打包的根目录进行操作，并将ZipArchive的对象传递给方法 
                    $zip->close(); //关闭处理的zip文件 
                }                
            }
        }

        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . basename($zipFile)); //文件名  
        header("Content-Type: application/zip"); //zip格式的  
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件  
        header('Content-Length: ' . filesize($zipFile)); //告诉浏览器，文件大小   
        @readfile($zipFile);
    }

    /**
     * 压缩文件夹下所有文件
     */
    protected static function addFileToZip($path, $zip)
    {
        $handler = opendir($path); //打开当前文件夹由$path指定。 
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {//文件夹文件名字为'.'和‘..'，不要对他们进行操作 
                if (is_dir($path . "/" . $filename)) {// 如果读取的某个对象是文件夹，则递归 
                    addFileToZip($path . "/" . $filename, $zip);
                } else { //将文件加入zip对象 
                    $zip->addFile($path . "/" . $filename, $filename);
                }
            }
        }
        @closedir($path);
    }

    /**
     * 获取商户规则列表信息
     */
    public function merchantRuleList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'a.payCode' => request('payWayCode'),
            'a.payName' => request('payProductName'),
            'c.user_name' => request('payUserName')
        );

        $Rows = DB::table('zoro_pay_product_group AS a')
            ->leftJoin('zoro_user_users AS b', 'a.payCode', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.id')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->orderBy('a.order', 'asc')
            ->orderBy('a.payCode', 'asc')
            ->select([
                'a.id',
                'a.payCode',
                'a.payName',
                'a.creator',
                'a.creator_time',
                'a.order',
                'a.default',
                'a.disable',
                'a.comment',
                'b.user_no',
                'c.user_name'
            ])
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
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
     * 获取商户规则商户号
     */
    public function merchantRuleSelectPayCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payUser' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRSP100', 'Content' => '获取失败' ], self::$successStatus);
        }

        $getPayCode = DB::table('zoro_pay_way AS a')
            ->leftJoin('zoro_user_users AS b', 'a.pay_way_code', '=', 'b.user_no')
            ->leftJoin('zoro_user_info AS c', 'b.user_id', '=', 'c.id')
            ->where([
                'c.user_name' => request('payUser')
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
     * 根据商户规则中商户号获取支付名称
     */
    public function merchantRuleSelectPayName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payCode' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRSPC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

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
     * 添加商户规则
     */
    public function merchantRuleCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payCode' => 'required',
            'payName' => 'required',
            'payOrder' => 'required'
        ]);
        
        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        DB::beginTransaction();

        $insertGroupArray = array(
            'payCode' => request('payCode'),
            'payName' => request('payName'),
            'order' => request('payOrder'),
            'creator' => Auth::user()->user_name,
            'creator_time' => date('Y-m-d H:i:s', time())
        );

        $getBusinessRules = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payCode'),
                'payName' => request('payName')
            ])
            ->count('id');

        if ($getBusinessRules > 0) {
            return response()->json([ 'Status' => 'MRC101', 'Content' => '当前通道已配置' ], self::$successStatus);
        }

        $getBusinessOrder = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payCode'),
                'order' => request('payOrder')
            ])
            ->count('id');

        if ($getBusinessOrder > 0) {
            return response()->json([ 'Status' => 'MRC102', 'Content' => '当前通道排序重复' ], self::$successStatus);
        }

        // 查询是否有默认通道
        $getBusinessOrder = DB::table('zoro_pay_product_group')
            ->where([
                'payCode' => request('payCode'),
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
                        'connector' => $value ['connector'],
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
     * 添加商户详细规则
     */
    public function merchantRuleDialogCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRDC100', 'Content' => '参数丢失' ], self::$successStatus);
        }

        $insertRuleArray = array(
            'group_id' => request('id'),
            'payType' => 1,
            'operator' => 1,
            'value' => 0,
            'connector' => 1
        );

        $Rows = DB::table('zoro_pay_product_group_rule')->insertGetId($insertRuleArray);

        return response()->json([ 'Status' => '200', 'Content' => $Rows ], self::$successStatus);
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
            'connector' => request('connector')
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
                        $ruleComment .= '`dailyamount` ' . self::operator(1, $value ['operator']) . $value ['value'] . ' ' . self::operator(3, $value ['connector']) ;

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
     * 更改商户详细规则
     */
    public function merchantRuleDialogUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'payCode' => 'required',
            'payName' => 'required',
            'payOrder' => 'required'
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
     * 删除规则列表
     */
    public function merchantRuleDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MRD100', 'Content' => '参数丢失' ], self::$successStatus);
        }

        DB::beginTransaction();

        $deleteGroupData = DB::table('zoro_pay_product_group')
            ->whereIn('id', request('id'))
            ->delete();

        $deleteRuleData = DB::table('zoro_pay_product_group_rule')
            ->whereIn('group_id', request('id'))
            ->delete();

        $deleteNewRuleData = DB::table('zoro_pay_product_group_rule_new')
            ->whereIn('group_id', request('id'))
            ->delete();

        if ($deleteGroupData && $deleteRuleData && $deleteNewRuleData) {
            DB::commit();
            return response()->json([ 'Status' => '200', 'Content' => '删除成功' ], self::$successStatus);
        } else {
            DB::rollback();
            return response()->json([ 'Status' => 'MRD101', 'Content' => '删除失败' ], self::$successStatus);
        }
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
     * 管理员修改商户密码
    */
    public function modifyMerchantPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modifyMerchant' => 'required',
            'newPayPassword' => 'required|min:6|max:32',
            'newPassword' => 'required|min:6|max:32'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MMP100', 'Content' => '参数丢失' ], self::$successStatus);
        }

        // 验证商户是否存在
        $merchantInfo = DB::table('zoro_user_info')
            ->where([
                'id' => request('modifyMerchant'),
                'roles' => 3
            ])
            ->first();

        if (empty($merchantInfo)) {
            return response()->json([ 'Status' => 'MMP101', 'Content' => '商户信息错误' ], self::$successStatus);
        }
        
        // 修改密码
        $merchantUpdate = DB::table('zoro_user_info')
            ->where([
                'id' => request('modifyMerchant'),
                'roles' => 3
            ])
            ->update([
                'password' => bcrypt(request('newPassword')),
                'pay_pwd' => bcrypt(request('newPayPassword'))
            ]);
        
        if ($merchantUpdate) {
            return response()->json([ 'Status' => '200', 'Content' => '修改密码成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MMP102', 'Content' => '修改密码失败' ], self::$successStatus);
        }
    }

    /**
     * 获取用户的下的商户列表
    */
    public function selectUserMerchant(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'SUM100', 'Content' => '请选择要查询的用户' ], self::$successStatus);
        }

        $page = request('page');
        $limit = request('limit');

        $selectArray = array(
            'b.user_no' => request('username')
        );

        $merchantData = DB::table('zoro_user_users AS b')
            ->leftJoin('zoro_user_info AS a', 'a.id', '=', 'b.user_id')
            ->where([
                'a.id' => request('id')
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

    /**
     * 用户创建新的商户号
    */
    public function merchantInfoCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'MIC100', 'Content' => '请完善信息' ], self::$successStatus);
        }

        // 查询用户是否存在
        $merchantOne = DB::table('zoro_user_info')
            ->where([
                'id' => request('id')
            ])
            ->first();

        if (empty($merchantOne)) {
            return response()->json([ 'Status' => 'MIC101', 'Content' => '用户信息不正确' ], self::$successStatus);
        }

        $userNo = DB::table('zoro_user_users')
            ->orderBy('id', 'desc')
            ->value('user_no');

        if ($userNo) {
            $userNo = (int)$userNo + 1;
        } else {
            $userNo = 80000001;
        }

        DB::beginTransaction();

        $createTime = date('Y-m-d H:i:s');

        $addMerchant = array(
            'user_id' => request('id'),
            'user_no' => $userNo,
            'remark' => request('remark'),
            'create_time' => $createTime
        );

        $merchantData = DB::table('zoro_user_users')
            ->insertGetId($addMerchant);

        if (!$merchantData) {
            DB::rollback();

            return response()->json([ 'Status' => 'MIC102', 'Content' => '添加商户信息失败' ], self::$successStatus);
        }

        // 插入账号信息 BEGIN
        $getProductCode = DB::table('zoro_pay_way')
            ->orderBy('pay_product_code', 'desc')
            ->value('pay_product_code');

        if (!$getProductCode) {
            DB::rollback();
            return response([ 'Status' => 'MIC103', 'Content' => '获取产品编号失败' ], self::$successStatus);
        }

        $insertPayWayArray = array(
            'version' => 1,
            'create_time' => $createTime,
            'edit_time' => $createTime,
            'pay_way_code' => $userNo,
            'pay_way_name' => 'ZoroPay',
            'pay_type_code' => 1,
            'pay_type_name' => 'ZoroPay',
            'pay_product_code' => $getProductCode + 1,
            'status' => 100,
            'sorts' => 1000,
            'pay_rate' => 0,
            'RsaPub' => '/wl_file/server/' . $userNo . '/' . $userNo . '.cer',
            'RsaP12' => '/wl_file/server/' . $userNo . '/' . $userNo . '.p12',
            'RsaPass' => '',
            'RsaClient' => 'AGMTrade' . $userNo,
            'RsaServer' => 'AGM' . $userNo,
            'RsaClientPass' => 123456,
            'RsaServerPass' => 123456,
            'merchart_name' => ''
        );

        $getPayWayId = DB::table('zoro_pay_way')
            ->insertGetId($insertPayWayArray);

        if ($getPayWayId) {
            DB::commit();

            return response()->json([ 'Status' => '200', 'Content' => '添加商户成功' ], self::$successStatus);
        } else {
            DB::rollback();

            return response()->json([ 'Status' => 'MIC104', 'Content' => '添加商户失败' ], self::$successStatus);
        }
    }

    /**
     * 查询所有用户信息
    */
    public function merchantRuleSelectPayUser(Request $request)
    {
        $getPayUser = DB::table('zoro_user_info')
            ->where([
                'roles' => 3
            ])
            ->select([
                DB::raw('user_name as label'),
                DB::raw('user_name as value')
            ])
            ->get();

        if ($getPayUser) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayUser ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MRSPC101', 'Content' => '请创建账号通道' ], self::$successStatus);
        }
    }
}
