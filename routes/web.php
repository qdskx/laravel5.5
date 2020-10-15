<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//Route::get('/', function () {
//    return view('welcome');
//});

Route::get('login' , 'UserController@login');
Route::post('dologin' , 'UserController@dologin');
Route::get('detail' , 'GoodsController@detail');

//Route::get('xs' , 'XsController@index');
//Route::get('xs_add' , 'XsController@addDoc');

//Route::namespace('miaosha')->prefix('miaosha')->group('UserLogin',function(){
//    Route::get('index' , 'MsController@index');
//    Route::get('startMS' , 'MsController@startms');
//});

//Route::group(['middleware' => ['userloginDF']], function () {
//    Route::get('startMS' , 'miaosha\MsController@startms');
//    Route::get('startMS' , 'MsController@startms');
//});


//Route::get('startMS' , 'MsController@startms')->middleware('userlogin:param,fgjg');
//Route::get('startMS45' , 'MsController@startms_uio')->middleware(['userloginDF']);
//Route::get('startMS' , 'MsController@startms');







/*
 * 秒杀
 */
Route::group(['middleware' => ['login']] , function(){
    Route::get('startms' , 'MsController@startms');
});











