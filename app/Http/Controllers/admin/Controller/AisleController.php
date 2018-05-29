<?php

namespace App\Http\Controllers\admin\Controller;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Validator;

class AisleController extends Controller
{
    protected static $successStatus = 200;

    /**
     * 通道列表查询信息
     */
    public function aisleList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'b.pay_way_code' => request('payCode'),
            'a.Merchants' => request('payName'),
            'e.user_name' => request('PayUsername')
        );

        $Rows = DB::table('zoro_pay_product as a')
            ->leftJoin('zoro_user_users AS d', 'a.merchants', '=', 'd.user_no')
            ->leftJoin('zoro_user_info AS e', 'd.user_id', '=', 'e.id')
            ->join('zoro_pay_way as b', 'a.product_code', '=', 'b.pay_product_code')
            ->join('zoro_pay_product_config as c', 'a.id', '=', 'c.config_code')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->select([
                'a.id',
                'a.create_time',
                'a.version',
                'a.product_name',
                'a.MerCode',
                'a.Account',
                'a.PostUrl',
                'a.RedirectUrl',
                'a.is_on',
                'a.Merchants',
                'a.is_live',
                'a.isCredit',
                'a.isOrder',
                'a.MerCert',
                'a.s_url',
                'a.r_url',
                'a.order_pfx',
                'a.remark',
                'a.from_url',
                'b.pay_way_code',
                'e.user_name'
            ])
            ->orderBy('a.id', 'desc')
            ->paginate($limit);

        $Rows = object2array($Rows);

        if ($Rows ['total'] > 0) {
            foreach($Rows ['data'] as $key => $value) {
                $daily =  DB::table('zoro_trade_payment_record')
                    ->where([
                        'merchant_no' => $value ['pay_way_code'],
                        'pay_way_name' => $value ['product_name'],
                        'status' => 2
                    ])
                    ->whereBetween('create_time', [date('Y-m-d') . ' 00:00:00', date('Y-m-d') . ' 23:59:59'])
                    ->value(DB::raw('sum(payer_pay_amount)'));
                
                $Rows ['data'] [$key] ['dailyBalance'] = $daily == '' ? 0.00 : $daily;
            }
        }

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 添加通道列表
     */
    public function aisleCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_way_code' => 'required',
            'MerCode' => 'required',
            'product_name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'AC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        // 查询通道模版验证必填信息 BEGIN
        $payAisleValidator = DB::table('zoro_pay_product_template')
            ->where([
                'payname' => request('product_name')
            ])
            ->first();

        if (empty($payAisleValidator)) {
            return response()->json([ 'Status' => 'AC101', 'Content' => '请检查支付编号是否正确' ], self::$successStatus);
        }
        // 查询通道模版验证必填信息 END

        // 依据模版必填信息进行验证 BEGIN
        $VTValidator = [];
        $payAisleValidator = object2array($payAisleValidator);

        foreach($payAisleValidator as $key => $value) {
            if ($payAisleValidator [$key] == 1 && in_array($key, ['PostUrl', 'RedirectUrl', 's_url', 'r_url'])) {
                $VTValidator [$key] = 'required|url';
            } else if ($payAisleValidator [$key] == 1) {
                $VTValidator [$key] = 'required';
            }
        }

        $validatorTemplate = Validator::make($request->all(), $VTValidator);

        if ($validatorTemplate->fails()) {
            return response()->json([ 'Status' => 'AC102', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }
        // 依据模版必填信息进行验证 END

        // 插入通道表 BEGIN
        $getPayCode = DB::table('zoro_pay_way')
            ->where([
                'pay_way_code' => request('pay_way_code')
            ])
            ->value('pay_product_code');

        if (!$getPayCode) {
            return response()->json([ 'Status' => 'AC103', 'Content' => '所填写商户号不存在' ], self::$successStatus);
        }

        // 查询商户相同通道是否存在
        $addUnique = DB::table('zoro_pay_product')
            ->where([
                'product_name' => request('product_name'),
                'product_code' => $getPayCode,
            ])
            ->first();

        if (!empty($addUnique)) {
            return response()->json([ 'Status' => 'AC106', 'Content' => '通道不能重复' ], self::$successStatus);
        }

        DB::beginTransaction();

        $insertParoductArray = array(
            'create_time' => date('Y-m-d H:i:s', time()),
            'product_name' => request('product_name'),
            'product_code' => $getPayCode,
            'version' => request('version'),
            'Merchants' => request('pay_way_code'),
            'MerCode' => request('MerCode'),
            'MerCert' => request('MerCert'),
            'Account' => request('Account'),
            'PostUrl' => request('PostUrl'),
            'RedirectUrl' => request('RedirectUrl'),
            'order_pfx' => request('order'),
            'r_url' => request('r_url'),
            's_url' => request('s_url'),
            'remark' => request('remark')
        );

        $insertParoduct = DB::table('zoro_pay_product')->insertGetId($insertParoductArray);
        // 插入通道表 END

        if ($insertParoduct) {
            $insertConfigArray = array(
                'config_code' => $insertParoduct
            );

            $insertConfig = DB::table('zoro_pay_product_config')->insertGetId($insertConfigArray);

            if ($insertParoduct && $insertConfig) {
                DB::commit();
                return response([ 'Status' => '200', 'Content' => '创建成功' ], self::$successStatus);
            } else {
                DB::rollback();
                return response([ 'Status' => 'AC105', 'Content' => '创建配置失败' ], self::$successStatus);
            }
        } else {
            DB::rollback();
            return response(['Status' => 'AC104', 'Content' => '创建数据失败请稍后再试'], self::$successStatus);
        }
    }

    /**
     * 更改通道列表信息
     */
    public function aisleUpdate(Request $request)
    {
        // 更改基本配置 BEGIN
        $updateProductArray = array(
            'is_on' => request('is_on'),
            'is_live' => request('is_live'),
            'isCredit' => request('isCredit'),
            'isOrder' => request('isOrder')
        );

        $updateData = DB::table('zoro_pay_product')
            ->where([
                'id' => request('id')
            ])
            ->update($updateProductArray);
        // 更改基本配置 END

        // 更改使用配置 BEGIN
        // $updateProductConfigArray = array(
        //     'order' => request('order'),
        //     'capped' => request('capped')
        // );

        // $updateConfigData = DB::table('zoro_pay_product_config')
        //     ->where([
        //         'config_code' => request('id')
        //     ])
        //     ->update($updateProductConfigArray);
        // 更改使用配置 END

        if ($updateData) {
            return response()->json([ 'Status' => '200', 'Content' => '更改成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'AU100', 'Content' => '更改失败' ], self::$successStatus);
        }
    }

    /**
     * 获取通道模板信息
     */
    public function aisleTemplateList(Request $request)
    {
        $page = request('page');
        $limit = request('limit');
        $selectArray = array(
            'payname' => request('payName')
        );

        $Rows = DB::table('zoro_pay_product_template')
            ->where(function ($Query) use ($selectArray) {
                foreach ($selectArray as $Key => $Value) {
                    if ($Value && $Value != 'undefined') {
                        $Query->where($Key, '=', $Value);
                    }
                }
            })
            ->orderBy('id', 'desc')
            ->paginate($limit);

        return response()->json([ 'Status' => 200, 'Content' => $Rows ], self::$successStatus);
    }

    /**
     * 添加通道模板信息
     */
    public function aisleTemplateCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payname' => 'required',
            'MerCode' => 'required',
            'MerCert' => 'required',
            'Account' => 'required',
            'PostUrl' => 'required|url',
            'remark' => 'required',
            'RedirectUrl' => 'required',
            'version' => 'required',
            'surl' => 'required',
            'rurl' => 'required',
            'order' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'ATC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $insertTemplateArray = array(
            'payname' => request('payname'),
            'MerCode' => request('MerCode'),
            'MerCert' => request('MerCert'),
            'Account' => request('Account'),
            'PostUrl' => request('PostUrl'),
            'remark' => request('remark'),
            'RedirectUrl' => request('RedirectUrl'),
            'version' => request('version'),
            's_url' => request('surl'),
            'r_url' => request('rurl'),
            'order' => request('order')
        );

        $insertTemplate = DB::table('zoro_pay_product_template')->insert($insertTemplateArray);

        if ($insertTemplate) {
            return response([ 'Status' => '200', 'Content' => '创建成功' ], self::$successStatus);
        } else {
            return response([ 'Status' => 'ATC101', 'Content' => '创建模板失败' ], self::$successStatus);
        }
    }

    /**
     * 更改通道模板信息
     */
    public function aisleTemplateUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'MerCode' => 'required',
            'MerCert' => 'required',
            'Account' => 'required',
            'PostUrl' => 'required',
            'remark' => 'required',
            'RedirectUrl' => 'required',
            'version' => 'required',
            's_url' => 'required',
            'r_url' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'ATC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $insertTemplateArray = array(
            'MerCode' => request('MerCode'),
            'MerCert' => request('MerCert'),
            'Account' => request('Account'),
            'PostUrl' => request('PostUrl'),
            'remark' => request('remark'),
            'RedirectUrl' => request('RedirectUrl'),
            'version' => request('version'),
            's_url' => request('s_url'),
            'r_url' => request('r_url'),
            'order' => request('order')
        );

        $updateTemplateData = DB::table('zoro_pay_product_template')
            ->where([
                'id' => request('id')
            ])
            ->update($insertTemplateArray);

        if ($updateTemplateData) {
            return response([ 'Status' => '200', 'Content' => '修改成功' ], self::$successStatus);
        } else {
            return response([ 'Status' => 'ATC101', 'Content' => '修改模板失败' ], self::$successStatus);
        }
    }

    /**
     * 获取通道商户号列表
     * 
     * @params：
     * @return：
    */
    public function aisleSelectPayCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'ASP100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
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
            return response()->json([ 'Status' => 'MRSPC101', 'Content' => '请创建账号' ], self::$successStatus);
        }
    }

    /**
     * 获取通道支付编号
     * 
     * @params:
     * @return:
    */
    public function aisleSelectPayMerCode(Request $request)
    {
        // 查询通道支付编号列表
        $getPayMerCode = DB::table('zoro_pay_product_template')
            ->get();
        
        if ($getPayMerCode) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayMerCode ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'ASPV101', 'Content' => '请创建通道' ], self::$successStatus);
        }
    }

    /**
     * 查询商户下的通道信息
    */
    public function selectCertificateAisle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_way_code' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'SCA100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $getPayMerCode = DB::table('zoro_pay_product AS a')
            ->leftJoin('zoro_pay_way AS b', 'a.product_code', '=', 'b.pay_product_code')
            ->where([
                'b.pay_way_code' => request('pay_way_code')
            ])
            ->select([
                DB::raw('product_name as label'),
                DB::raw('product_name as value')
            ])
            ->get();
        
        if ($getPayMerCode) {
            return response()->json([ 'Status' => '200', 'Content' => $getPayMerCode ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'SCA101', 'Content' => '请创建通道' ], self::$successStatus);
        }
    }

    /**
     * 上传商户证书
    */
    public function uploadMerchantCertificate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required',
            'pay_way_code' => 'required',
            'product_name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'UMC100', 'Content' => '请完善信息再提交表单' ], self::$successStatus);
        }

        // 验证商户通道信息是否正确
        $merchantInfo = DB::table('zoro_pay_product AS a')
            ->leftJoin('zoro_pay_way AS b', 'a.product_code', '=', 'b.pay_product_code')
            ->where([
                'b.pay_way_code' => request('pay_way_code'),
                'a.product_name' => request('product_name')
            ])
            ->first();

        if (empty($merchantInfo)) {
            return response()->json([ 'Status' => 'UMC103', 'Content' => '请检查商户信息是否正确' ], self::$successStatus);
        }

        // 验证上传信息是否正确
        $cerType = array('x-pkcs12', 'x-x509-ca-cert', 'octet-stream');

        $signCerPath = ''; // 签名证书路径
        $middleCerPath = ''; // 中级证书路径
        $rootCerPath = ''; // 根证书路径
        $publicPath = ''; // 公钥证书
        $privatePath = ''; // 私钥证书

        $aisleCerData = [];

        if ($_FILES) {
            foreach ($_FILES as $key => $value) {
                $cerFileType = explode('/', $value ['type']);

                // 证书类型是否正确
                if (!in_array($cerFileType [1], $cerType)) {
                    return response()->json([ 'Status' => 'UMC101', 'Content' => '请上传正确的证书类型' ], self::$successStatus);
                }

                // 验证是否含有php标签
                $fileContent = file_get_contents($value ['tmp_name']);
                
                if(stripos($fileContent, '<?php')){
                    return response()->json([ 'Status' => 'UMC102', 'Content' => '请确认证书是否正确' ], self::$successStatus);
                }
                
                // 当前文件是否存在-不存在创建文件夹
                if (!is_dir(base_path() . '/storage/wl_file/certs/' . request('pay_way_code'))) {
                    mkdir(base_path() . '/storage/wl_file/certs/' . request('pay_way_code'));
                }

                if ($key == 'signCertPath') { // 签名证书
                    $signCerPath = base_path() . '/storage/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];

                    $aisleCerData ['signCertPath'] = '/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];

                    move_uploaded_file($value ["tmp_name"], $signCerPath);
                } else if ($key == 'middleCertPath') { // 中级证书
                    $middleCerPath = base_path() . '/storage/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];
                    
                    $aisleCerData ['middleCertPath'] = '/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];

                    move_uploaded_file($value ["tmp_name"], $middleCerPath);
                } else if ($key == 'rootCertPath') { // 根证书
                    $rootCerPath = base_path() . '/storage/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];

                    $aisleCerData ['rootCertPath'] = '/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];
                    
                    move_uploaded_file($value ["tmp_name"], $rootCerPath);
                } else if ($key == 'publicPath') { // 公钥证书
                    $publicPath = base_path() . '/storage/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];

                    $aisleCerData ['PubPath'] = '/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];
                    
                    move_uploaded_file($value ["tmp_name"], $publicPath);
                } else if ($key == 'privatePath') { // 私钥证书
                    $privatePath = base_path() . '/storage/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];

                    $aisleCerData ['PriPath'] = '/wl_file/certs/' . request('pay_way_code') . '/' . $value ['name'];
                    
                    move_uploaded_file($value ["tmp_name"], $privatePath);
                }
            }
        }

        if ($aisleCerData == '') {
            return response()->json([ 'Status' => 'UMC104', 'Content' => '请选择证书后再进行上传' ], self::$successStatus);
        }

        $getPayCode = DB::table('zoro_pay_way')
            ->where([
                'pay_way_code' => request('pay_way_code')
            ])
            ->value('pay_product_code');

        // 更新商户通道证书信息
        $updateMerchantData = DB::table('zoro_pay_product')
            ->where([
                'product_name' => request('product_name'),
                'product_code' => $getPayCode
            ])
            ->update($aisleCerData);

        if ($updateMerchantData) {
            return response()->json([ 'Status' => '200', 'Content' => '上传成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'UMC105', 'Content' => '上传失败' ], self::$successStatus);
        }
    }

    /**
     * 修改通道的来源网站
     * @params:
     * @return:
    */
    public function aisleFromUrlModify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'AFUM100', 'Content' => '请完善信息再提交表单' ], self::$successStatus);
        }

        // 修改通道信息
        $modifyAisleFromUrl = DB::table('zoro_pay_product')
            ->where([
                'id' => request('id')
            ])
            ->update([
                'from_url' => request('from_url')
            ]);

        if ($modifyAisleFromUrl) {
            return response()->json([ 'Status' => '200', 'Content' => '修改成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'AFUM101', 'Content' => '修改失败' ], self::$successStatus);
        }
    }

    /**
     * 修改商户的通道信息
    */
    public function modifyAisle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_way_code' => 'required',
            'MerCode' => 'required',
            'product_name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([ 'Status' => 'AC100', 'Content' => '请按照提示将表单填写完整' ], self::$successStatus);
        }

        $productArray = array(
            'create_time' => date('Y-m-d H:i:s', time()),
            'version' => request('version'),
            'MerCode' => request('MerCode'),
            'MerCert' => request('MerCert'),
            'Account' => request('Account'),
            'PostUrl' => request('PostUrl'),
            'RedirectUrl' => request('RedirectUrl'),
            'order_pfx' => request('order'),
            'r_url' => request('r_url'),
            's_url' => request('s_url'),
            'remark' => request('remark')
        );

        $updateProduct = DB::table('zoro_pay_product')
            ->where([
                'id' => request('id')
            ])
            ->update($productArray);

        if ($updateProduct !== false) {
            return response()->json([ 'Status' => '200', 'Content' => '修改成功' ], self::$successStatus);
        } else {
            return response()->json([ 'Status' => 'MA101', 'Content' => '修改失败' ], self::$successStatus);
        }
    }
}
