<?php

namespace App\Http\Middleware;

use Closure;

class merchant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 存储用户信息session
        if (!\Session::get('merchant')) {
            $user = \Auth::user();

            \Session::put('merchant', object2array($user));
        }

        return $next($request);
    }
}
