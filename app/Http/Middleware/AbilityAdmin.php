<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AbilityAdmin
{
    /**
     * 管理员-权限控制中间件
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 管理员或子管理员
        if (Auth::user()->roles == 1 || Auth::user()->roles == 2) {
            //大管理员拥有全部管理员权限
            if (Auth::user()->roles == 1) {
                return $next($request);
            }
    
            //子管理员 并且 需要拥有的权限名称等于路由名称
            if(Auth::user()->roles <> 2 || !Auth::user()->can(Route::currentRouteName())) {
                return response()->json([
                    'Status' => '403',
                    'Content' => '403 Forbidden 您没有权限执行此操作'
                ]);
            }
        } else {
            // 商户权限全部可以反问
            if (Auth::user()->roles == 3) {
                return $next($request);
            }
            
            //子管理员 并且 需要拥有的权限名称等于路由名称
            if(Auth::user()->roles <> 4 || !Auth::user()->can(Route::currentRouteName())) {
                return response()->json([
                    'Status' => '403',
                    'Content' => '403 Forbidden 您没有权限执行此操作'
                ]);
            }
        }
        
        return $next($request);
    }
}
