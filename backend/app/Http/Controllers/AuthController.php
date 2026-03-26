<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request){
        $auth = Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if(!$auth){
            return response()->json([
                'status'=> "failed",
                'data' => "email or password is incorrect" 
            ]);
        }
        $user = Auth::guard("sanctum")->user();
        $token = $user->createToken("token")->plainTextToken;

        return response()->json([
            'status' => "success",
            "data" => $user,
            "token" => $token
        ]);
    }


    public function logout(){
        $user = Auth::guard('sanctum')->user();
        $user->tokens()->delete();

        return response()->json([
            'status' => "success",
        ]);
    }
}
