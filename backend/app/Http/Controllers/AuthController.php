<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request){
        $val = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        if($val->fails()){
            return response()->json([
                'status' => 'failed',
                'errors' => $val->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Petugas',
        ]);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'data' => $user,
            'token' => $token,
        ]);
    }

    public function login(Request $request){
        $auth = Auth::attempt([
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if(!$auth){
            return response()->json([
                'status'=> "failed",
                'data' => "email or password is incorrect" 
            ], 401);
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
