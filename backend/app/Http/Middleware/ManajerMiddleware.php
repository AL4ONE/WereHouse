<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class ManajerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {        $user = Auth::guard("sanctum")->user();
        if($user->role != "Manajer"){
            return response()->json([
                'status' => "forbiden access",
                "message" => "hanya untuk role manajer"
            ]);
        }
        return $next($request);
    }
}
