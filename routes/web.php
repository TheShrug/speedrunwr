<?php

Route::get('/', 'HomeController@index')->name('home');

Route::get('/api/getNewRun', 'ApiController@newRun');
Route::get('/api/findRun', 'ApiController@findRun');
Route::post('/api/easterEgg', 'ApiController@easterEgg');

Route::get('/user', 'UserController@user');
Route::get('/user/verify/{key}', 'Auth\VerifyUserController@verifyUser');
Route::post('/user/verify/resend', 'Auth\VerifyUserController@resendEmail');
Route::post('register', 'Auth\RegisterController@register');

Route::post('/user/likeRun', 'UserController@likeRun');
Route::get('/user/likesRun', 'UserController@likesRun');

Route::post('/login', 'Auth\LoginController@login');
Route::post('/logout', 'Auth\LoginController@logout');
Route::post('/password/reset/sendEmail', 'Auth\ForgotPasswordController@sendResetLinkEmail');
Route::post('/password/reset', 'Auth\ResetPasswordController@reset');
Route::get('/password/reset/{token}', 'Auth\ResetPasswordController@showResetForm')->name('password.reset');

Route::get('/run/{vue_capture?}', 'HomeController@run')->where('vue_capture', '[\/\w\.-]*');
