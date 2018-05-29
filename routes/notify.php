<?php

use Illuminate\Http\Request;
use App\Jobs\notify\ZoroPay;

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

Route::group(['prefix' => 'v1'], function () {
    // 请求方式/notify/v1/zoroNotify
    Route::post('/zoroNotify', 'notify\Controller\NotifyController@zoroNotify');

    Route::get('zoroNotifyget', function (Request $requset) {
        // 测试插入：http://127.0.0.1:8000/notify/v1/zoroNotify?toUrl=http://club-client.agm18.com:8186/api/aaa&jsonData=%27{%E2%80%9Caa%E2%80%9D:123}%27
        
        $toUrl = request('toUrl');
        $jsonData = request('jsonData');

        $job = (new ZoroPay($toUrl, $jsonData))->onQueue('Zoro');
        dispatch($job);
    });
});
