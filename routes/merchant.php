<?php

use Illuminate\Http\Request;

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

// 访问方式/merchant/v1/xxx
Route::group([ 'namespace' => 'merchant', 'middleware' => ['web', 'routeWhite'] ], function () {
    // 访问方式/merchant/v1/xxx
    Route::group(['prefix' => 'v1', 'middleware' => []], function () {
        // 商户登录方法
        Route::post('login', 'Controller\MerchantController@login');
        // 用户退出
        Route::post('merchantLogout', 'Controller\MerchantController@merchantLogout');

        Route::group([ 'middleware' => ['auth:api', 'ability.admin']], function () {
            // 获取商户信息
            Route::get('merchantInfo', 'Controller\MerchantController@merchantInfo')->name('merchant.merchant.show');
            // 获取商户入金量信息
            Route::get('merchantCapitalInfo', 'Controller\MerchantController@merchantCapitalInfo')->name('merchant.merchant.show');
            // 获取商户首页当月曲线图
            Route::get('merchantLineChartInfo', 'Controller\MerchantController@merchantLineChartInfo')->name('merchant.merchant.show');
            // 获取商户详情列表信息
            Route::get('merchantList', 'Controller\MerchantController@merchantList')->name('merchant.merchant.show');
            // 获取商户规则列表信息
            Route::get('merchantRuleList', 'Controller\MerchantController@merchantRuleList')->name('merchant.rule.show');
            // 禁用商户规则
            Route::put('merchantRuleForbidden', 'Controller\MerchantController@merchantRuleForbidden')->name('merchant.rule.edit');
            // 获取商户规则商户号
            Route::get('merchantRuleSelectPayCode', 'Controller\MerchantController@merchantRuleSelectPayCode')->name('merchant.rule.show');
            // 根据商户规则中商户号获取支付名称
            Route::get('merchantRuleSelectPayName', 'Controller\MerchantController@merchantRuleSelectPayName')->name('merchant.rule.show');
            // 获取商户规则详细列表信息
            Route::get('merchantRuleDialogList', 'Controller\MerchantController@merchantRuleDialogList')->name('merchant.rule.show');
            // 删除商户详细规则
            Route::delete('merchantRuleDialogDelete', 'Controller\MerchantController@merchantRuleDialogDelete')->name('merchant.rule.edit');
            // 更改商户详细规则
            Route::put('merchantRuleDialogUpdate', 'Controller\MerchantController@merchantRuleDialogUpdate')->name('merchant.rule.edit');
            // 更改商户规则
            Route::put('merchantRuleUpdate', 'Controller\MerchantController@merchantRuleUpdate')->name('merchant.rule.edit');
            // 添加商户规则
            Route::post('merchantRuleCreate', 'Controller\MerchantController@merchantRuleCreate')->name('merchant.rule.edit');
            // 获取用户下的商户号信息
            Route::get('selectUserMerchant', 'Controller\MerchantController@selectUserMerchant')->name('merchant.merchant.show');

            // 获取商户通道列表信息
            Route::get('aisleList', 'Controller\AisleController@aisleList')->name('merchant.entry.show');
            // 获取推荐通道模板信息
            Route::get('aisleTemplateList', 'Controller\AisleController@aisleTemplateList')->name('merchant.entry.show');
            // 获取推荐通道模板信息
            Route::post('updateMerchantRemark', 'Controller\AisleController@updateMerchantRemark')->name('merchant.entry.edit');

            // 查询商户余额
            Route::get('businessBalanceList', 'Controller\ReportController@businessBalanceList')->name('merchant.balance.show');
            // 获取商户余额页面中统计信息
            Route::get('businessBalanceCapital', 'Controller\ReportController@businessBalanceCapital')->name('merchant.balance.show');
            // 查询入金报表
            Route::get('depositReportList', 'Controller\ReportController@depositReportList')->name('merchant.deposit.show');
            // 修改入金报表中状态字段
            Route::put('depositReportStatusUpdate', 'Controller\ReportController@depositReportStatusUpdate')->name('merchant.deposit.edit');
            // 获取入金报表页面中统计信息
            Route::get('depositReportCapital', 'Controller\ReportController@depositReportCapital')->name('merchant.deposit.show');
            // 获取出金商户的通道信息
            Route::get('selectPayAisle', 'Controller\ReportController@selectPayAisle')->name('merchant.withdral.submit');
            // 商户申请出金提交
            Route::post('applyMerchantWithdrawal', 'Controller\ReportController@applyMerchantWithdrawal')->name('merchant.withdral.submit');
            // 获取出金报表汇总金额
            Route::get('withdrawalReportCapital', 'Controller\ReportController@withdrawalReportCapital')->name('merchant.withdral.show');
            // 获取出金报表汇总金额
            Route::get('withdrawalReportList', 'Controller\ReportController@withdrawalReportList')->name('merchant.withdral.show');
            // 修改出金报表中状态字段
            Route::put('withdrawalReportStatusUpdate', 'Controller\ReportController@withdrawalReportStatusUpdate')->name('merchant.withdral.edit');
            // 获取商户的通道
            Route::get('selectPayWayCode', 'Controller\ReportController@selectPayWayCode')->name('merchant.balance.show');
            // 获取报表发送设置列表
            Route::get('reportSendSettingList', 'Controller\ReportController@reportSendSettingList')->name('merchant.reportsend.show');
            // 创建用户的报表发送规则
            Route::put('reportSendSettingCreate', 'Controller\ReportController@reportSendSettingCreate')->name('merchant.reportsend.edit');
            // 更新用户的报表发送规则
            Route::put('updateReportSend', 'Controller\ReportController@updateReportSend')->name('merchant.reportsend.edit');
            // 删除用户的报表发送规则
            Route::post('reportSendDelete', 'Controller\ReportController@reportSendDelete')->name('merchant.reportsend.edit');
            // 批量上传出金信息
            Route::post('uploadBatchExcel', 'Controller\ReportController@uploadBatchExcel')->name('merchant.reportsend.edit');

            // 获取网关黑名单列表信息
            Route::get('gatewayBlacklistList', 'Controller\SystemController@gatewayBlacklistList')->name('merchant.balcklist.show');
            // 获取网关黑名单商户号
            Route::get('gatewayBlacklistSelectPayCode', 'Controller\SystemController@gatewayBlacklistSelectPayCode')->name('merchant.balcklist.show');
            // 添加网关黑名单
            Route::post('gatewayBlacklistCreate', 'Controller\SystemController@gatewayBlacklistCreate')->name('merchant.balcklist.edit');
            // 修改网关黑名单
            Route::put('gatewayBlacklistUpdate', 'Controller\SystemController@gatewayBlacklistUpdate')->name('merchant.balcklist.edit');
            // 删除网关黑名单
            Route::post('gatewayBlacklistDelete', 'Controller\SystemController@gatewayBlacklistDelete')->name('merchant.balcklist.edit');
            // 获取网关白名单列表信息
            Route::get('gatewayWhitelistList', 'Controller\SystemController@gatewayWhitelistList')->name('merchant.whitelist.show');
            // 获取网关白名单商户号
            Route::get('gatewayWhitelistSelectPayCode', 'Controller\SystemController@gatewayWhitelistSelectPayCode')->name('merchant.whitelist.show');
            // 添加网关白名单
            Route::post('gatewayWhitelistCreate', 'Controller\SystemController@gatewayWhitelistCreate')->name('merchant.whitelist.edit');
            // 修改网关白名单
            Route::put('gatewayWhitelistUpdate', 'Controller\SystemController@gatewayWhitelistUpdate')->name('merchant.whitelist.edit');
            // 删除网关白名单
            Route::post('gatewayWhitelistDelete', 'Controller\SystemController@gatewayWhitelistDelete')->name('merchant.whitelist.edit');

            // 获取平台角色列表
            Route::get('SelectRoleList', 'Controller\AuthController@SelectRoleList')->name('merchant.auth.show');
            // 创建平台角色
            Route::put('createRole', 'Controller\AuthController@createRole')->name('merchant.auth.edit');
            // 删除平台角色
            Route::post('deleteRole', 'Controller\AuthController@deleteRole')->name('merchant.auth.edit');
            // 修改平台角色
            Route::put('modifyRole', 'Controller\AuthController@modifyRole')->name('merchant.auth.edit');
            // 查询角色的菜单权限
            Route::post('selectRoleAuth', 'Controller\AuthController@selectRoleAuth')->name('merchant.auth.show');
            // 修改角色的权限
            Route::post('roleAuthModify', 'Controller\AuthController@roleAuthModify')->name('merchant.auth.edit');
            // 获取所有子商户用户列表
            Route::post('selectUserList', 'Controller\AuthController@selectUserList')->name('merchant.auth.edit');
            // 获取成员可更改角色id列表
            Route::get('selectRoleId', 'Controller\AuthController@selectRoleId')->name('merchant.auth.edit');
            // 修改用户的角色权限
            Route::put('userRoleAuthModify', 'Controller\AuthController@userRoleAuthModify')->name('merchant.auth.edit');
            // 创建新的子商户信息
            Route::put('userDataCreate', 'Controller\AuthController@userDataCreate')->name('merchant.auth.edit');
        });
        
        // 获取商户的基本信息
        Route::post('getMerchantInfo', 'Controller\MerchantController@getMerchantInfo');
    });

    // 访问方式/merchant/v1/xxx
    Route::group(['prefix' => 'v1', 'middleware' => ['auth:api']], function () {
        Route::post('XXX', 'Controller\MerchantController@XX');
    });

    Route::view('/{path1?}/{path2?}/{path3?}/{path4?}/{path5?}', 'merchant.index');
});