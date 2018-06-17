<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\UserVerification;

class VerifyUserController extends Controller
{
	public function verifyUser($key) {
		 $userVerification = UserVerification::where('key', $key)->first();
		 if(isset($userVerification)) {
		 	$user = $userVerification->user;
		 	if(!$user->verified) {
		 		$user->verified = 1;
		 		$user->save();
		 		$status = 'Your email is now verified! You can now log in.';
		    } else {
		 		$status = 'Your e-mail has already been verified.';
		    }
		 } else {
		 	return view('home', ['message' => 'Sorry, your email could not be found.']);

		 }
		 return view('home', ['message' => $status]);
	}
}
