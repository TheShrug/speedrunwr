<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\LikedRun;

class UserController extends Controller
{
    public function user(Request $request) {
	    if(Auth::check()) {
		    $user      = $request->user();
		    $likedRuns = $user->likedRuns()->get();
		    return json_encode(['user' => $user, 'likedRuns' => $likedRuns]);
	    } else {
		    return json_encode(['user' => null, 'likedRuns' => null]);
	    }

    }


    public function likesRun(Request $request) {

    	if(Auth::check()) {

    		$runId = $request->runId;

    		$user = $request->user();

    		$likesRun = $user->likedRuns->where('runId', $runId);

    		$message = (count($likesRun) > 0);

			return json_encode(['message' => $message, 'likesRun' => $likesRun]);

	    } else {

    		return json_encode(['message' => false]);

	    }

    }

    public function likeRun(Request $request) {

    	if(Auth::check()) {

    		$user = $request->user();

		    $run = $request->params['activeRun'];

			unset($run['id'], $run['created_at'], $run['updated_at']);

		    $likedRun = LikedRun::firstOrCreate($run);

		    $alreadyLiked = $user->likedRuns->contains($likedRun->id);

		    if($alreadyLiked) {
			    $user->likedRuns()->detach($likedRun);
			    $message = 'unliked';
		    } else {
			    $user->likedRuns()->save($likedRun);
			    $message = 'liked';
		    }

		    $likedRuns = $user->likedRuns()->get();

		    return json_encode(['message' => $message, 'likedRuns' => $likedRuns]);

	    } else {

		    return json_encode(['message' => false]);

	    }

    }

}
