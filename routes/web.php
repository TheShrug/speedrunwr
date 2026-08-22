<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/api/getNewRun', [ApiController::class, 'newRun']);
Route::get('/api/findRun', [ApiController::class, 'findRun']);
Route::post('/api/easterEgg', [ApiController::class, 'easterEgg']);

Route::get('/user', [UserController::class, 'user']);
Route::get('/user/verify/{key}', [VerifyUserController::class, 'verifyUser']);
Route::post('/user/verify/resend', [VerifyUserController::class, 'resendEmail']);
Route::post('register', [RegisterController::class, 'register']);

Route::post('/user/likeRun', [UserController::class, 'likeRun']);
Route::get('/user/likesRun', [UserController::class, 'likesRun']);

Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);
Route::post('/password/reset/sendEmail', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');

Route::get('/run/{vue_capture?}', [HomeController::class, 'run'])->where('vue_capture', '[\/\w\.-]*');
