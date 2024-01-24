<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;
use App\Models\User;

class SetupRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $superadmin_users = User::role('superadmin')->get();

        if ( 
            count($superadmin_users) === 0 
            && $request->method() === 'GET'
            && Route::currentRouteName() !== 'register' 
        ) {
            return redirect('/register');
        }
        
        return $next($request);
    }
}
