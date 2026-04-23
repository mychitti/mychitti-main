<?php

namespace App\Http;

use App\Http\Middleware\ActivationCheckMiddleware;
use App\Http\Middleware\InstallationMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

 class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        // \App\Http\Middleware\TrustProxies::class,
        // \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \App\Http\Middleware\RedirectIfWrongSubdomain::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \App\Http\Middleware\DynamicSessionCookie::class,
            \App\Http\Middleware\SetSessionCookieName::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\Localization::class,
             \App\Http\Middleware\ResolveStoreByDomain::class,
             \App\Http\Middleware\RedirectWww::class,
            \App\Http\Middleware\TrackVendorActivity::class,

        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        
        'auth' => \App\Http\Middleware\Authenticate::class,
        'subdomain.redirect' => \App\Http\Middleware\RedirectIfWrongSubdomain::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'vendor' => \App\Http\Middleware\VendorMiddleware::class,
        'vendor.api' => \App\Http\Middleware\VendorTokenIsValid::class,
        'dm.api' => \App\Http\Middleware\DmTokenIsValid::class,
        'module' => \App\Http\Middleware\ModulePermissionMiddleware::class,
        'frontuser' => \App\Http\Middleware\FrontUserMiddleware::class,
        'loginuser' => \App\Http\Middleware\LoginUserMiddleware::class,
        'installation-check' => InstallationMiddleware::class,
        'planwise' => \App\Http\Middleware\PermissionCheck::class,
        'actch' => ActivationCheckMiddleware::class,
        'localization' => \App\Http\Middleware\LocalizationMiddleware::class,
        'module-check' => \App\Http\Middleware\ModuleCheckMiddleware::class,
        'current-module' => \App\Http\Middleware\CurrentModule::class,
        'apiGuestCheck' => \App\Http\Middleware\APIGuestMiddleware::class,
        // 'redirect.subdomain' => \App\Http\Middleware\RedirectToCorrectSubdomain::class,
        'fix-urls' => \App\Http\Middleware\FixVendorEmployeeUrls::class,
        'permission' => \App\Http\Middleware\Permission::class,
        'ai.internal' => \App\Http\Middleware\AiInternalApiKeyMiddleware::class,
    ];
}
 