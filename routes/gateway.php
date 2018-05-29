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

Route::any('/', 'gateway\Controller\PayPostController@index');

Route::get('/pay_post', 'gateway\Controller\PayPostController@pay_post');
Route::post('/makePost', 'gateway\Controller\PayPostController@makePost');
Route::any('/finish/notify/{notify}/zoroCode/{zoroCode}', 'gateway\Controller\PayPostController@finish');
Route::any('/finishPay/payname/{payname}', 'gateway\Controller\PayPostController@finishPay');
Route::get('/SignClient','gateway\Controller\RsaSignController@SignClient');