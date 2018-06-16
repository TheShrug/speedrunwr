<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function user(Request $request) {
    	return json_encode(['user' => $request->user()]);
    }
}
