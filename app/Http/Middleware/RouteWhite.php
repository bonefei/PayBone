<?php

namespace App\Http\Middleware;

use Closure;
use DB;

class RouteWhite
{
    /**
     * 管理员-路由白名单中间件
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // print_r($_SERVER);die;
        // 根据路由查询信息
        $url = DB::table('zoro_wl_info')
            ->where([
                'url' => $_SERVER ['HTTP_HOST']
            ])
            ->select([
                'url',
                'identity'
            ])
            ->first();

        if (empty($url)) {
            exit("host deny");
        }

        // 管理员和商户禁止互相访问
        if ($url->identity == 1) { // 管理员
            if (strpos($_SERVER ['REQUEST_URI'], '/admin') !== 0) {
                return redirect('/admin');
            }
        } else { // 商户
            if (strpos($_SERVER ['REQUEST_URI'], '/merchant') !== 0) {
                return redirect('/merchant');
            }
        }

        return $next($request);
    }
}
