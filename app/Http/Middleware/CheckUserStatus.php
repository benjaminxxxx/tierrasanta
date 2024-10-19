<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        //return $next($request);
  
        $user = Auth::user();

        if ($user && $user->estado != '1') {
            Auth::guard('web')->logout();
            return redirect()->route('login')->withErrors(['message' => "Tu cuenta ha sido bloqueada."]);
        }

        return $next($request);
    }
}
