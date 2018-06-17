<?php

namespace App\Http\Controllers\Auth;

use App\Mail\VerifyEmail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\UserVerification;
use App\User;
use Illuminate\Support\Facades\Mail;

class VerifyUserController extends Controller
{
	public function verifyUser($key) {
		 $userVerification = UserVerification::where('key', $key)->first();
		 if(isset($userVerification)) {
		 	$user = $userVerification->user;
		 	if(!$user->verified) {
		 		$user->verified = 1;
		 		$user->save();
			    $userVerification->delete();
		 		$status = 'Your email is now verified! You can now log in.';
		    } else {
		 		$status = 'Your e-mail has already been verified.';
		    }
		 } else {
		 	return view('home', ['message' => 'Sorry, your email could not be found.']);

		 }
		 return view('home', ['message' => $status]);
	}

	public function resendEmail(Request $request) {

		$user = User::where('email', $request->email)->first();

		if($user) {
			if($user->verified) {
				return json_encode(['messageType' => 'info', 'message' => 'Account already verified.']);
			}
			Mail::to($user->email)->send(new VerifyEmail($user));
			return json_encode(['messageType' => 'success', 'message' => 'Resent verification email to ' . $request->email . ' .']);
		} else {
			return json_encode(['messageType' => 'warning', 'message' => 'The email address ' . $request->email . ' was not found.']);
		}

	}

}
