<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        $this->mapGatewayRoutes();

        $this->mapNotifyRoutes();

        $this->mapAdminRoutes();

        $this->mapMerchantRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "gateway" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapGatewayRoutes()
    {
        Route::prefix('gateway')
            ->middleware('gateway')
            ->namespace($this->namespace)
            ->group(base_path('routes/gateway.php'));
    }

    /**
     * Define the "notify" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapNotifyRoutes()
    {
        Route::prefix('notify')
            ->middleware('notify')
            ->namespace($this->namespace)
            ->group(base_path('routes/notify.php'));
    }

    /**
     * Define the "admin" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapAdminRoutes()
    {
        Route::prefix('admin')
            ->middleware('admin')
            ->namespace($this->namespace)
            ->group(base_path('routes/admin.php'));
    }

    /**
     * Define the "merchant" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapMerchantRoutes()
    {
        Route::prefix('merchant')
            ->middleware('merchant')
            ->namespace($this->namespace)
            ->group(base_path('routes/merchant.php'));
    }
}
