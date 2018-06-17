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
Route::get('/api/getNewRun', 'ApiController@newRun');


Route::get('/user', 'UserController@user');
Route::get('/user/verify/{key}', 'Auth\VerifyUserController@verifyUser');
Route::post('/user/verify/resend', 'Auth\VerifyUserController@resendEmail');
Route::post('register', 'Auth\RegisterController@register');

Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout');
Route::post('password/reset/sendEmail', 'Auth\ForgotPasswordController@sendResetLinkEmail');
Route::get('password/reset/{token}', 'Auth\ForgotPasswordController@showResetForm');
Route::post('password/reset', 'Auth\ResetPasswordController@reset');

