<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {

            // Admin Panel Routes: admin.mychitti.net
            Route::domain('admin.mychitti.net')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin.php'));

            // Store Panel Routes: store.mychitti.net
            Route::domain('vendor.mychitti.net')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/vendor.php'));

            // Admin Sectional Routes (if needed separately)
            Route::domain('admin.mychitti.net')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin/routes.php'));

            // API v1 Routes (remain unchanged)
            Route::prefix('api/v1')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/v1/api.php'));

            // API v2 Routes (remain unchanged)
            Route::prefix('api/v2')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/v2/api.php'));

            // Frontend Routes: mychitti.net
            Route::domain('mychitti.net')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(600)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
