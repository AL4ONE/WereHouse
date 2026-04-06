<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage di route: Route::middleware('role:Admin,Petugas')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = Auth::guard("sanctum")->user();
        if (!in_array($user->role, $roles)) {
            return response()->json([
                'status' => "forbidden",
                "message" => "Akses ditolak untuk role: " . $user->role
            ], 403);
        }
        return $next($request);
    }
}
