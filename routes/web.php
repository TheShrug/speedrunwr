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

Route::get('/', 'HomeController@index')->name('home');

Route::get('/api/gameCount', 'ApiController@gameCount');
Route::get('/api/getNewRun', 'ApiController@newRun');
Route::get('/api/categories', 'ApiController@games');
Route::get('/api/runs', 'ApiController@games');
Route::get('/api/store', 'ApiController@store');

Route::get('/user', 'UserController@user');
Route::get('/user/verify/{key}', 'Auth\VerifyUserController@verifyUser');
Route::post('/user/verify/resend', 'Auth\VerifyUserController@resendEmail');



Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
