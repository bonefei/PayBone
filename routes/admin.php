<?php

use Illuminate\Http\Request;
use Gregwar\Captcha\CaptchaBuilder;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::group(['namespace' => 'admin', 'middleware' => ['web', 'routeWhite']], function () {
    // 登录验证码  
    Route::group(['prefix' => 'v1', 'middleware' => []], function () {
        Route::get('getVerification', function () {
            ob_clean();

            //生成验证码图片的Builder对象，配置相应属性
            $builder = new CaptchaBuilder;
            //可以设置图片宽高及字体
            $builder->build($width = 250, $height = 70, $font = null);
            //获取验证码的内容
            $phrase = $builder->getPhrase();
            //把内容存入session
            \Session::flash('milkcaptcha', $phrase);
            //生成图片
            header("Cache-Control: no-cache, must-revalidate");
            header('Content-Type: image/jpeg');
            return $builder->inline(100);
        });

        // 管理员登录
        Route::any('adminLogin', 'Controller\AdminController@adminLogin');

        // 管理员退出
        Route::post('adminLogout', 'Controller\AdminController@adminLogin');
        // 下载商户证书
        Route::get('merchantDownload', 'Controller\MerchantsController@merchantDownload')->name('admin.merchant.down');

        //ability.admin 分管理员权限控制。通过路由名称控制权限。相同类型的权限可以使用相同的名称。一般查询用show,增删改用edit
        Route::group([ 'middleware' =>[ 'auth:api','ability.admin'] ], function () {

            //##################### 获取管理员用户信息 查看 admin.info.show   
            // 获取管理员用户信息
            Route::get('adminUserInfo', 'Controller\AdminController@adminUserInfo')->name('admin.merchant.show');

            //##################### 获取首页入金的统计信息 查看 admin.dashboard.show   
            // 获取首页入金的统计信息
            Route::get('adminCapitalInfo', 'Controller\AdminController@adminCapitalInfo')->name('admin.dashboard.show');
            // 获取管理员首页当月曲线图
            Route::get('adminLineChartInfo', 'Controller\AdminController@adminLineChartInfo')->name('admin.dashboard.show');

            //##################### 获取商户详情列表信息 查看 admin.merchant.show  修改 admin.merchant.edit
            // 获取商户详情列表信息
            Route::get('merchantList', 'Controller\MerchantsController@merchantList')->name('admin.merchant.show');
            // 创建新商户信息
            Route::post('merchantCreate', 'Controller\MerchantsController@merchantCreate')->name('admin.merchant.edit');
            // 更改商户信息
            Route::post('merchantUpdate', 'Controller\MerchantsController@merchantUpdate')->name('admin.merchant.edit');


            //##################### 获取商户规则列表信息 查看 admin.rule.show  修改 admin.rule.edit
            // 获取商户规则列表信息
            Route::get('merchantRuleList', 'Controller\MerchantsController@merchantRuleList')->name('admin.rule.show');
            // 获取商户规则详细列表信息
            Route::get('merchantRuleDialogList', 'Controller\MerchantsController@merchantRuleDialogList')->name('admin.rule.show');
            // 获取商户规则商户号
            Route::get('merchantRuleSelectPayCode', 'Controller\MerchantsController@merchantRuleSelectPayCode')->name('admin.rule.show');
            // 根据商户规则中商户号获取支付名称
            Route::get('merchantRuleSelectPayName', 'Controller\MerchantsController@merchantRuleSelectPayName')->name('admin.rule.show');

            // 添加商户规则
            Route::post('merchantRuleCreate', 'Controller\MerchantsController@merchantRuleCreate')->name('admin.rule.edit');
            // 添加商户详细规则
            Route::post('merchantRuleDialogCreate', 'Controller\MerchantsController@merchantRuleDialogCreate')->name('admin.rule.edit');
            // 更改商户规则
            Route::put('merchantRuleUpdate', 'Controller\MerchantsController@merchantRuleUpdate')->name('admin.rule.edit');
            // 更改商户详细规则
            Route::put('merchantRuleDialogUpdate', 'Controller\MerchantsController@merchantRuleDialogUpdate')->name('admin.rule.edit');
            // 删除商户规则
            Route::delete('merchantRuleDelete', 'Controller\MerchantsController@merchantRuleDelete')->name('admin.rule.edit');
            // 删除商户详细规则
            Route::delete('merchantRuleDialogDelete', 'Controller\MerchantsController@merchantRuleDialogDelete')->name('admin.rule.edit');
            // 禁用商户规则
            Route::put('merchantRuleForbidden', 'Controller\MerchantsController@merchantRuleForbidden')->name('admin.rule.edit');
            // 修改商户密码
            Route::post('modifyMerchantPassword', 'Controller\MerchantsController@modifyMerchantPassword')->name('admin.rule.edit');
            // 获取用户下的商户列表
            Route::get('selectUserMerchant', 'Controller\MerchantsController@selectUserMerchant')->name('admin.rule.edit');
            // 创建用户的新商户号
            Route::put('merchantInfoCreate', 'Controller\MerchantsController@merchantInfoCreate')->name('admin.rule.edit');
            // 创建用户的新商户号
            Route::get('merchantRuleSelectPayUser', 'Controller\MerchantsController@merchantRuleSelectPayUser')->name('admin.rule.show');


            //##################### 查询商户余额 查看 admin.balance.show  
            // 查询商户余额
            Route::get('businessBalanceList', 'Controller\ReportController@businessBalanceList')->name('admin.balance.show');
            // 获取商户余额页面中统计信息
            Route::get('businessBalanceCapital', 'Controller\ReportController@businessBalanceCapital')->name('admin.balance.show');

            //##################### 查询入金报表 查看 admin.deposit.show  审核 admin.deposit.edit
            // 查询入金报表
            Route::get('depositReportList', 'Controller\ReportController@depositReportList')->name('admin.deposit.show');
            // 获取入金报表页面中统计信息
            Route::get('depositReportCapital', 'Controller\ReportController@depositReportCapital')->name('admin.deposit.show');
            // 修改入金报表中状态字段
            Route::put('depositReportStatusUpdate', 'Controller\ReportController@depositReportStatusUpdate')->name('admin.deposit.edit');
            // 入金报表中重发按钮操作
            Route::post('depositReportRepeatMessage', 'Controller\ReportController@depositReportRepeatMessage')->name('admin.deposit.edit');
            
            //##################### 获取通道列表信息 查看 admin.withdral.show  申请 admin.withdral.submit  审核 admin.withdral.edit
            // 获取申请出金的商户列表
            Route::get('merchantWithdrawalList', 'Controller\ReportController@merchantWithdrawalList')->name('admin.withdral.show');
            // 获取出金商户的通道信息
            Route::get('selectPayAisle', 'Controller\ReportController@selectPayAisle')->name('admin.withdral.show');
            // 获取出金报表汇总金额
            Route::get('withdrawalReportCapital', 'Controller\ReportController@withdrawalReportCapital')->name('admin.withdral.show');
            // 获取出金报表汇总金额
            Route::get('withdrawalReportList', 'Controller\ReportController@withdrawalReportList')->name('admin.withdral.show');
            // 商户申请出金提交
            Route::post('applyMerchantWithdrawal', 'Controller\ReportController@applyMerchantWithdrawal')->name('admin.withdral.submit');
            // 修改出金报表中状态字段
            Route::put('withdrawalReportStatusUpdate', 'Controller\ReportController@withdrawalReportStatusUpdate')->name('admin.withdral.edit');
            // 获取报表发送设置列表
            Route::get('reportSendSettingList', 'Controller\ReportController@reportSendSettingList')->name('admin.withdral.show');
            // 获取所有商户名称
            Route::get('selectUserOption', 'Controller\ReportController@selectUserOption')->name('admin.withdral.show');
            // 获取用户下的商户号
            Route::get('selectUserNoOption', 'Controller\ReportController@selectUserNoOption')->name('admin.withdral.show');
            // 创建用户的报表发送规则
            Route::put('reportSendSettingCreate', 'Controller\ReportController@reportSendSettingCreate')->name('admin.withdral.edit');
            // 更新用户的报表发送规则
            Route::put('updateReportSend', 'Controller\ReportController@updateReportSend')->name('admin.withdral.edit');
            // 删除用户的报表发送规则
            Route::post('reportSendDelete', 'Controller\ReportController@reportSendDelete')->name('admin.withdral.edit');
            // 删除用户的报表发送规则
            Route::get('getPayAisleVersion', 'Controller\ReportController@getPayAisleVersion')->name('admin.withdral.show');
             // 上传批量的excel文件
             Route::post('uploadBatchExcel', 'Controller\ReportController@uploadBatchExcel')->name('admin.withdral.edit');

            //##################### 获取通道列表信息 查看 admin.entry.show 修改 admin.entry.edit
            // 获取通道列表信息
            Route::get('aisleList', 'Controller\AisleController@aisleList')->name('admin.entry.show');
            // 添加通道列表信息
            Route::post('aisleCreate', 'Controller\AisleController@aisleCreate')->name('admin.entry.edit');
            // 更改通道列表信息
            Route::put('aisleUpdate', 'Controller\AisleController@aisleUpdate')->name('admin.entry.edit');
            // 获取通道模板信息
            Route::get('aisleTemplateList', 'Controller\AisleController@aisleTemplateList')->name('admin.entry.show');
            // 添加通道模板信息
            Route::post('aisleTemplateCreate', 'Controller\AisleController@aisleTemplateCreate')->name('admin.entry.edit');
            // 更改通道模板信息
            Route::put('aisleTemplateUpdate', 'Controller\AisleController@aisleTemplateUpdate')->name('admin.entry.edit');
            // 获取通道商户号列表
            Route::get('aisleSelectPayCode', 'Controller\AisleController@aisleSelectPayCode')->name('admin.entry.show');
            // 获取通道支付编号列表
            Route::get('aisleSelectPayMerCode', 'Controller\AisleController@aisleSelectPayMerCode')->name('admin.entry.show');
            // 获取通道支付编号列表
            Route::post('uploadMerchantCertificate', 'Controller\AisleController@uploadMerchantCertificate')->name('admin.entry.show');
            // 获取当前商户的通道信息
            Route::post('selectCertificateAisle', 'Controller\AisleController@selectCertificateAisle')->name('admin.entry.show');
            // 修改通道的来源网站
            Route::post('aisleFromUrlModify', 'Controller\AisleController@aisleFromUrlModify')->name('admin.entry.show');
            // 修改通道的信息
            Route::post('modifyAisle', 'Controller\AisleController@modifyAisle')->name('admin.entry.edit');
            
            //##################### 获取网关黑名 查看 admin.whitelist.show 修改 admin.whitelist.edit
            // 获取网关黑名单列表信息
            Route::get('gatewayBlacklistList', 'Controller\SystemController@gatewayBlacklistList')->name('admin.balcklist.show');
            // 获取网关黑名单商户号
            Route::get('gatewayBlacklistSelectPayCode', 'Controller\SystemController@gatewayBlacklistSelectPayCode')->name('admin.balcklist.show');
            // 添加网关黑名单
            Route::post('gatewayBlacklistCreate', 'Controller\SystemController@gatewayBlacklistCreate')->name('admin.balcklist.edit');
            // 修改网关黑名单
            Route::put('gatewayBlacklistUpdate', 'Controller\SystemController@gatewayBlacklistUpdate')->name('admin.balcklist.edit');
            // 删除网关黑名单
            Route::post('gatewayBlacklistDelete', 'Controller\SystemController@gatewayBlacklistDelete')->name('admin.balcklist.edit');
            // 获取网关白名单列表信息
            Route::get('gatewayWhitelistList', 'Controller\SystemController@gatewayWhitelistList')->name('admin.whitelist.show');
            // 获取网关白名单商户号
            Route::get('gatewayWhitelistSelectPayCode', 'Controller\SystemController@gatewayWhitelistSelectPayCode')->name('admin.whitelist.show');
            // 添加网关白名单
            Route::post('gatewayWhitelistCreate', 'Controller\SystemController@gatewayWhitelistCreate')->name('admin.whitelist.edit');
            // 修改网关白名单
            Route::put('gatewayWhitelistUpdate', 'Controller\SystemController@gatewayWhitelistUpdate')->name('admin.whitelist.edit');
            // 删除网关白名单
            Route::post('gatewayWhitelistDelete', 'Controller\SystemController@gatewayWhitelistDelete')->name('admin.whitelist.edit');

            // 获取平台角色列表
            Route::get('SelectRoleList', 'Controller\AuthController@SelectRoleList')->name('admin.auth.show');
            // 创建平台角色
            Route::put('createRole', 'Controller\AuthController@createRole')->name('admin.auth.edit');
            // 删除平台角色
            Route::post('deleteRole', 'Controller\AuthController@deleteRole')->name('admin.auth.edit');
            // 修改平台角色
            Route::put('modifyRole', 'Controller\AuthController@modifyRole')->name('admin.auth.edit');
            // 查询角色的菜单权限
            Route::post('selectRoleAuth', 'Controller\AuthController@selectRoleAuth')->name('admin.auth.show');
            // 修改角色的权限
            Route::post('roleAuthModify', 'Controller\AuthController@roleAuthModify')->name('admin.auth.edit');
            // 获取所有子商户用户列表
            Route::post('selectUserList', 'Controller\AuthController@selectUserList')->name('admin.auth.show');
            // 获取成员可更改角色id列表
            Route::get('selectRoleId', 'Controller\AuthController@selectRoleId')->name('admin.auth.show');
            // 修改用户的角色权限
            Route::put('userRoleAuthModify', 'Controller\AuthController@userRoleAuthModify')->name('admin.auth.edit');
            // 创建新的子管理员信息
            Route::put('userDataCreate', 'Controller\AuthController@userDataCreate')->name('admin.auth.edit');
        });
    });
 
    // 防止VUE刷新页面后报空白页面
    Route::view('/{path1?}/{path2?}/{path3?}/{path4?}/{path5?}', 'admin.index');
});
