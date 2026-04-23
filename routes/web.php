<?php

use App\Http\Controllers\Admin\Zone\ZoneController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaytmController;
use App\Http\Controllers\LiqPayController;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\PaytabsController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\RazorPayController;
use App\Http\Controllers\SenangPayController;
use App\Http\Controllers\MercadoPagoController; 
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\FlutterwaveV3Controller;
use App\Http\Controllers\Front\CartController; 
use App\Http\Controllers\PaypalPaymentController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SslCommerzPaymentController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\Front\ServiceController;
use App\Http\Controllers\Front\GoogleController;
use App\Http\Controllers\Front\McVendorController;
use App\Http\Controllers\Front\ModuleInfoController;
use App\Http\Controllers\Front\UserController as FrontUserController;
use App\Http\Controllers\Front\WishlistController;
use App\Http\Controllers\Front\AIChatController as FrontAIChatController;
use App\Http\Controllers\Front\AppointmentController as FrontAppointmentController;
use App\Http\Controllers\PaymentGateway;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\VendorController as ControllersVendorController;
use App\Jobs\PunchInReminder;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
Route::get('/opcache-reset', function() {
    opcache_reset();
    return 'OPcache cleared';
});
// Route::get('/print-receipt', function () {
//     try {
//         // Printer IP and port
//         $connector = new NetworkPrintConnector("192.168.1.100", 9100);

//         $printer = new Printer($connector);

//         // Print text
//         $printer->setJustification(Printer::JUSTIFY_CENTER);
//         $printer->text("My Store\n");
//         $printer->text("123 Street Name\n");
//         $printer->feed();

//         $printer->setJustification(Printer::JUSTIFY_LEFT);
//         $printer->text("Item 1   2 x 50  = 100\n");
//         $printer->text("Item 2   1 x 75  = 75\n");
//         $printer->feed();

//         $printer->setJustification(Printer::JUSTIFY_RIGHT);
//         $printer->text("Total: 175\n");

//         $printer->cut();
//         $printer->close();

//         return "Printed successfully";
//     } catch (\Exception $e) {
//         return "Could not print: " . $e->getMessage();
//     }
// });
Route::get('/test-attendance-job', function () {
    (new PunchInReminder())->handle();
    return 'Attendance reminder job executed!';
}); 
Route::get('/debug-scheduled-notifications', function () {
    try {
        (new \App\Jobs\SendScheduledNotifications())->handle();

        return "✅ Job executed successfully";

    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);
    }
});

Route::group(['prefix' => 'mc-vendor', 'as' => 'mc-vendor.'], function () {
    Route::get('contact', [McVendorController::class, 'contact'])->name('contact');
    Route::post('send-message', [McVendorController::class, 'send_message'])->name('send-message');
}); 
// Route::get('/', [UserController::class,'index']);
// Route::get('expire', [UserController::class,'expire']);
Route::get('/generate-sitemap', [SitemapController::class, 'generate'])->name('generate-sitemap');
Route::get('/sitemap.xml', [SitemapController::class, 'show'])->name('sitemap');
Route::get('/health-check', fn() => response('OK'));
Route::get('icons', 'Front\FrontController@icons_view');
Route::post('track-banner-click', 'Front\FrontController@trackBannerClick')->name('track.banner.click');
Route::post('track-ad-click', 'Front\FrontController@trackAdClick')->name('track.ad.click');
Route::get('ads', 'Front\FrontController@allAds')->name('front.ads.index');
Route::get('ads/load', 'Front\FrontController@loadAds')->name('front.ads.load');
Route::get('ads/{id}', 'Front\FrontController@adDetail')->name('front.ads.detail');
Route::get('app-config', [FrontController::class, 'app_config'])->name('app-config');
Route::post('app-config/update', [FrontController::class, 'app_config_update'])->name('app-config.update');
 
 
// Route::get('/', 'FrontController@index')->name('home');
Route::get('check_depreciation', [CronController::class, 'check_depreciation']);
Route::get('closing-balance', [CronController::class, 'bank_account_closing_balance']);
Route::get('employee-attendance', [CronController::class, 'employee_attendance'])->name('employee-attendance');
Route::get('maintenance-reminder', [CronController::class, 'monthly_maintenance_reminder'])->name('maintenance-reminder');
Route::get('approved', 'Front\FrontController@approve_success')->name('approve.success');
Route::get('approve/receivable-receipt/{id}', 'Vendor\DocumentsController@recievable_reciepts_approve')->name('receivable-receipt.approve');
Route::post('approve/rr-submit', 'Vendor\DocumentsController@rr_approve_submit')->name('receivable-receipt.approve-submit');
Route::get('approve/job-card/{id}', 'Vendor\DocumentsController@job_card_approve')->name('job-card.approve');

Route::post('phone-save', [FrontUserController::class, 'phone_save'])->name('phone-save');
Route::post('verify-phone-otp', [FrontUserController::class, 'verify_otp'])->name('verify-phone-otp');
Route::post('send-login-otp', [FrontUserController::class, 'send_login_otp'])->name('user.send-login-otp');
Route::post('verify-login-otp', [FrontUserController::class, 'verify_login_otp'])->name('user.verify-login-otp');


 
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('registration-successfull', [FrontController::class, 'registration_success'])->name('registration-successfull');
Route::get('testing', [FrontController::class, 'testing'])->name('testing');
Route::get('test-push', [FrontController::class, 'test_push_view'])->name('test-push');
Route::post('test-push', [FrontController::class, 'test_push_send'])->name('test-push.send');
Route::get('signup', [FrontUserController::class, 'signup'])->name('user-signup');

// only if domain is mychitti.net or staging.mychitti.net
Route::domain('mychitti.net')
    ->group(function () {
        Route::get('login', [FrontUserController::class, 'login'])->name('user-login');
        Route::post('login', [FrontUserController::class, 'login_post'])->name('login.post');
    });

Route::domain('admin.mychitti.net')
    ->group(function () {
        Route::get('login', [FrontUserController::class, 'login'])->name('admin.user-login');
        Route::post('login', [FrontUserController::class, 'login_post'])->name('admin.login.post');
    });

Route::domain('staging.mychitti.net')
    ->group(function () {
        Route::get('login', [FrontUserController::class, 'login'])->name('staging.user-login');
        Route::post('login', [FrontUserController::class, 'login_post'])->name('staging.login.post');
    });



Route::get('/test-mail', function () {
    Mail::raw('Test mail body', function ($message) {
        $message->to('imafreenansari1998@gmail.com')
            ->subject('Laravel Mail Test');
    });

    return 'Mail sent';
});

// Other domains

Route::post('signup', [FrontUserController::class, 'signup_post'])->name('signup.post');
Route::get('user-logout', [FrontUserController::class, 'logout'])->name('user.logout');
Route::get('forgot-password', [FrontUserController::class, 'forgot_password'])->name('forgot-password');
Route::post('forgot-password', [FrontUserController::class, 'reset_password_request'])->name('forgot-password.post');
Route::get('update-password', [FrontUserController::class, 'update_password'])->name('update-password');
Route::post('reset-password', [FrontUserController::class, 'reset_password_submit'])->name('reset-password');
Route::post('send-opt', [FrontController::class, 'send_otp'])->name('send-otp');
Route::post('verify-vendor-otp', [FrontController::class, 'verify_otp'])->name('verify-vendor-otp');
Route::post('send-vendor-otp', [FrontController::class, 'send_vendor_otp'])->name('send-vendor-otp');
Route::post('save-recent-search', [FrontUserController::class, 'save_recent_search'])->name('save-recent-search');
Route::get('/recent-searches', [FrontUserController::class, 'getRecent'])->name('get-recent-searches');
Route::post('/recent-searches/clear', [FrontUserController::class, 'clear'])->name('clear-recent-searches');
Route::get('invoice-correction', [FrontController::class, 'invoice_correction'])->name('invoice-correction');
Route::get('gatepass-details/{id}', [FrontUserController::class, 'gatepass_details'])->name('gatepass-details');
Route::get('quotation-details/{id}', [FrontUserController::class, 'quotation_details'])->name('quotation-details');
Route::get('unavailable-provider-check', [CronController::class, 'unavailable_provider']);

Route::post('check-business', [ControllersVendorController::class, 'check_business'])->name('check-business');

// modul pages for vendor reg 
Route::get('mc-module/{module}', [ModuleInfoController::class, 'module_info'])->name('mc-module');


Route::get('mc-vendor-hub-tnc', [FrontController::class, 'mc_vendor_hub_tnc'])->name('mc-vendor-hub-tnc');
Route::get('mc-vendor-hub-privacy-policy', [FrontController::class, 'mc_vendor_hub_pp'])->name('mc-vendor-hub-pp');
Route::get('FAQs', [FrontController::class, 'faq'])->name('faq');
Route::get('about-us', [FrontController::class, 'about'])->name('about-us');
Route::get('contact-us', [FrontController::class, 'contact'])->name('contact-us');
Route::get('privacy-policy', [FrontController::class, 'privacy_policy'])->name('privacy-policy');
Route::get('terms-and-conditions', [FrontController::class, 'terms_n_conditions'])->name('user-terms-and-conditions');
Route::get('vendor-terms-conditions/{v_id}', [FrontController::class, 'vendor_terms_conditions'])->name('vendor-terms-conditions');
Route::get('store-terms-and-conditions/{type}', [FrontController::class, 'store_terms_and_conditions'])->name('store-terms-and-conditions');
Route::get('refund-policy', [FrontController::class, 'refund_policy'])->name('refund-policy');
Route::get('cancellation-policy', [FrontController::class, 'cancellation_policy'])->name('cancellation-policy');
Route::get('shipping-policy', [FrontController::class, 'shipping_policy'])->name('shipping-policy');
Route::get('disclaimer', [FrontController::class, 'disclaimer'])->name('disclaimer');

Route::get('searchbar', [FrontController::class, 'search'])->name('searchbar-web');
Route::post('update-location', [FrontController::class, 'update_location'])->name('update-location');
Route::post('change-module', [FrontController::class, 'change_module'])->name('change-module');
Route::get('all-stores/{type?}', [FrontController::class, 'all_stores'])->name('all-stores');
Route::post('missing-zone-request', [FrontController::class, 'missing_zone_request'])->name('missing-zone-request');
Route::post('test-notification/{type?}', [FrontController::class, 'send_test_notification'])->name('send-test-notification');

Route::get('testing', [FrontController::class, 'testing'])->name('testing');
Route::get('blog-mc-vendor-hub', [FrontController::class, 'blog_mc_vendor'])->name('blog-mc-vendor-hub');
Route::get('blog', [FrontController::class, 'blog'])->name('blog');
Route::get('blog/{slug}', [FrontController::class, 'blog_post'])->name('blog.post');
Route::get('blog/category/{slug}', [FrontController::class, 'blog_by_category'])->name('blog.category');

Route::get('payment-gateways', [PaymentGateway::class, 'index'])->name('payment-gateways');
Route::get('monthly-billing', 'CronController@monthly_billing')->name('monthly-billing');
Route::get('payment-reminder', 'CronController@payment_reminder')->name('payment-reminder');
Route::get('cancel-order', 'CronController@cancel_order')->name('cancel-order');
Route::get('test_dbbackup', 'CronController@test_dbbackup')->name('test_dbbackup');
Route::get('unavailable-provider', 'CronController@unavailable_provider')->name('unavailable-provider');
Route::get('fetch-subcategory', [FrontController::class, 'fetch_subcategory'])->name('fetch-subcategory');
Route::get('fetch-categories', [FrontController::class, 'fetch_categories'])->name('fetch-categories');
Route::get('zone/get-coordinates/{id}', [ZoneController::class, 'getCoordinates'])->name('zone.get-coordinates');

Route::get('change-variation', [FrontController::class, 'change_variation'])->name('change-variation');

Route::group(['middleware' => ['loginuser']], function () {
    Route::post('book-service', [FrontUserController::class, 'book_service'])->name('book-service');
    Route::get('checkout', [FrontController::class, 'checkout'])->name('checkout');
    Route::get('coupon-list', [FrontController::class, 'coupon_list'])->name('coupon-list');
    Route::post('apply-coupon', [FrontController::class, 'apply_coupon'])->name('apply-coupon');
    Route::post('place-order', [FrontController::class, 'place_order'])->name('place-order');
    Route::get('order-success/{id}', [FrontController::class, 'order_success'])->name('order-success');
    Route::get('success', [FrontController::class, 'order_success']);
    Route::post('submit-review', [FrontUserController::class, 'submit_review'])->name('submit-review');
    Route::post('submit-service-review', [FrontUserController::class, 'add_service_review'])->name('submit-service-review');
    Route::post('submit-store-review', [FrontUserController::class, 'submit_service_review'])->middleware('loginuser')->name('submit-store-review');

});
// AI Chat — open to all (guests use session-based pseudo-ID)
Route::post('ai-chat/send',    [FrontAIChatController::class, 'chat'])->name('ai-chat.send');
Route::get('ai-chat/history',  [FrontAIChatController::class, 'history'])->name('ai-chat.history');
Route::post('ai-chat/clear',   [FrontAIChatController::class, 'clearMemory'])->name('ai-chat.clear');
Route::post('ai-chat/tts',     [FrontAIChatController::class, 'tts'])->name('ai-chat.tts');
Route::get('delete-account/{id}', [FrontUserController::class, 'delete_account'])->name('delete-account');

Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
    Route::post('confirm', [ServiceController::class, 'confirm'])->name('confirm');
    Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
        Route::post('approval', [ServiceController::class, 'gatepass_approval'])->name('approval');
        Route::post('return-approval', [ServiceController::class, 'gatepass_return_approval'])->name('return-approval');
        Route::get('details', [ServiceController::class, 'gatepass_details'])->name('details');
    });
    Route::group(['prefix' => 'quotation', 'as' => 'quotation.'], function () {
        Route::post('approval', [ServiceController::class, 'quotation_approval'])->name('approval');
        Route::post('return-approval', [ServiceController::class, 'quotation_return_approval'])->name('return-approval');
        Route::get('details', [ServiceController::class, 'quotation_details'])->name('details');
    });
});
Route::group(['middleware' => ['frontuser']], function () {
    Route::get('/', [FrontController::class, 'index'])->name('home'); 
    Route::get('/shop', [FrontController::class, 'index'])->name('home.shop');
    Route::get('contact', [FrontController::class, 'contact'])->name('contact');
    Route::get('cart', [FrontController::class, 'cart'])->name('cart'); 
    Route::get('store-reviews/{slug}', [FrontController::class, 'store_reviews'])->name('store.reviews');
    Route::post('store-removal-request', [FrontController::class, 'store_removal_request'])->name('store.removal-request');
    Route::get('{city}/store/{slug}', [FrontController::class, 'store_details'])->name('store.details')->where('city', '^(?!remove-from-wishlist|delete-address|edit-address|add-new-address|store-reviews|gallery|category|dashboard|cart|contact)[a-z0-9-]+$');
    Route::get('{city}/store/{slug}/appointment', [FrontAppointmentController::class, 'show'])->name('front.appointment.book');
    Route::post('{city}/store/{slug}/appointment', [FrontAppointmentController::class, 'book'])->name('front.appointment.store');
    Route::get('{city}/store/{slug}/appointment/{id}/confirm', [FrontAppointmentController::class, 'confirm'])->name('front.appointment.confirm');
    Route::get('appointment/slots', [FrontAppointmentController::class, 'slots'])->name('front.appointment.slots');
    Route::get('gallery/{slug}', [FrontController::class, 'store_gallery'])->name('store.gallery');
    // Route::get('category/{slug}', [FrontController::class, 'category_listing'])->name('category.listing'); // needs to be first
    Route::post('check-cart', [CartController::class, 'check_cart'])->name('check-cart');
    Route::post('add-to-cart', [CartController::class, 'add_to_cart'])->name('add-to-cart');
    Route::post('remove-from-cart', [CartController::class, 'remove_from_cart'])->name('remove-from-cart');
    Route::post('get-delivery-charges', [CartController::class, 'get_delivery_charges'])->name('get-delivery-charges');
    Route::post('change-cart-quantity', [CartController::class, 'change_cart_quantity'])->name('change-cart-quantity');
    Route::post('update-wishlist', [WishlistController::class, 'update_wishlist'])->name('update-wishlist');
    Route::get('dashboard/{tab?}', [FrontUserController::class, 'dashboard'])->name('dashboard');
    Route::post('update-profile', [FrontUserController::class, 'update_profile'])->name('update-profile');
    Route::post('update-address/{id}', [FrontUserController::class, 'update_address'])->name('update-address');
    Route::get('delete-address/{id}', [FrontUserController::class, 'delete_address'])->name('delete-address');
    Route::get('edit-address/{id}', [FrontUserController::class, 'edit_address'])->name('edit-address');
    Route::get('remove-from-wishlist/{type}/{id}', [FrontUserController::class, 'remove_wishlist'])->name('remove-from-wishlist');
    Route::post('add-new-address', [FrontUserController::class, 'add_new_address'])->name('add-new-address');
    Route::get('add-new-address', [FrontUserController::class, 'add_address'])->name('add-address');
});

Route::get('lang/{locale}', 'HomeController@lang')->name('lang');
// Route::get('terms-and-conditions', 'HomeController@terms_and_conditions')->name('terms-and-conditions');
// Route::get('about-us', 'HomeController@about_us')->name('about-us');
// Route::get('contact-us', 'HomeController@contact_us')->name('contact-us');
Route::post('send-message', 'HomeController@send_message')->name('send-message');
// Route::get('privacy-policy', 'HomeController@privacy_policy')->name('privacy-policy');
// Route::get('cancelation', 'HomeController@cancelation')->name('cancelation');
// Route::get('refund', 'HomeController@refund_policy')->name('refund');
// Route::get('shipping-policy', 'HomeController@shipping_policy')->name('shipping-policy');
Route::post('newsletter/subscribe', 'NewsletterController@newsLetterSubscribe')->name('newsletter.subscribe');


// Route storage files through Laravel — enforce auth per domain
Route::get('storage/{path}', function ($path) {
    $host = request()->getHost();

    // Normalize legacy storage/app/public/... URLs
    if (str_starts_with($path, 'app/public/')) {
        $path = substr($path, strlen('app/public/'));
    }

    // Always public paths regardless of extension — no auth required on any server
    $alwaysPublic = ['vendor_login/'];
    foreach ($alwaysPublic as $prefix) {
        if (str_starts_with($path, $prefix)) {
            if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                abort(404);
            }
            return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
        }
    }

    if ($host === 'admin.mychitti.net') {
        // store/ images (logos, cover photos, etc.) are public — but sensitive subdirs still require auth
        $storeSensitive = [
            'store/docs/', 'store/documents/', 'store/banking/', 'store/signature/',
            'store/branch/', 'store/service_reports/', 'store/jobcards/', 'store/recivable-receipts/',
        ];
        $isPublicStorePath = str_starts_with($path, 'store/');
        foreach ($storeSensitive as $sensitive) {
            if (str_starts_with($path, $sensitive)) {
                $isPublicStorePath = false;
                break;
            }
        }
        if (!$isPublicStorePath && !\Illuminate\Support\Facades\Auth::guard('admin')->check()) {
            return redirect('/login/admin');
        }
    } elseif (in_array($host, ['vendor.mcvendorhub.com', 'vendor-staff.mcvendorhub.com', 'vendor.mychitti.net', 'vendor.mychitti.shop', 'vendor-employee.mychitti.shop'])) {
        $vendorAuth = \Illuminate\Support\Facades\Auth::guard('vendor')->check()
            || \Illuminate\Support\Facades\Auth::guard('vendor_employee')->check();
        if (!$vendorAuth) {
            return redirect('/login');
        }
    } elseif (in_array($host, ['mychitti.net', 'www.mychitti.net'])) {
        // Invoices require customer login
        if (str_starts_with($path, 'invoice/')) {
            if (!\Illuminate\Support\Facades\Auth::guard('web')->check()) {
                return redirect('/login');
            }
        }
        // All other sensitive business/document paths are blocked entirely
        $sensitivePrefixes = [
            'store/docs/', 'store/documents/', 'store/banking/', 'store/signature/',
            'store/branch/docs/', 'store/service_reports/', 'store/jobcards/',
            'store/recivable-receipts/', 'store_reports/',
            'customer/documents/', 'vendor/documents/', 'admin/emp_documents/',
            'temp/',
        ];
        foreach ($sensitivePrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                abort(403);
            }
        }
    }

    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
        abort(404);
    }
    return \Illuminate\Support\Facades\Storage::disk('public')->response($path);
})->where('path', '.*')->name('admin.storage.file');

Route::get('login/{tab?}', 'LoginController@login')->name('login');
Route::get('business-verification', 'LoginController@business_verification')->name('business-verification');
Route::post('business-verify', 'LoginController@business_verify')->name('business-verify');
Route::get('vendor/auth/google', [GoogleController::class, 'vendorRedirectToGoogle'])->name('vendor.google.login');

Route::post('login_submit', 'LoginController@submit')->name('login_post');
Route::post('login_otp', 'LoginController@login_otp')->name('login_otp');
Route::post('login_otp_ajax', 'LoginController@login_otp_ajax')->name('login_otp_ajax');
Route::get('logout', 'LoginController@logout')->name('logout');
Route::get('vendor_logout', 'LoginController@vendor_logout')->name('vendor_logout');
Route::get('/reload-captcha', 'LoginController@reloadCaptcha')->name('reload-captcha');
Route::get('/reset-password', 'LoginController@reset_password_request')->name('reset-password');
Route::post('/vendor-reset-password', 'LoginController@vendor_reset_password_request')->name('vendor-reset-password');
Route::get('/password-reset', 'LoginController@reset_password')->name('change-password');
Route::post('verify-otp', 'LoginController@verify_token')->name('verify-otp');
Route::post('reset-password-submit', 'LoginController@reset_password_submit')->name('reset-password-submit');
Route::get('otp-resent', 'LoginController@otp_resent')->name('otp_resent');

Route::get('authentication-failed', function () {
    $errors = [];
    array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthenticated.']);
    return response()->json([
        'errors' => $errors,
    ], 401);
})->name('authentication-failed');

Route::group(['prefix' => 'payment-mobile'], function () {
    Route::get('/', 'PaymentController@payment')->name('payment-mobile');
    Route::get('set-payment-method/{name}', 'PaymentController@set_payment_method')->name('set-payment-method');
});

Route::get('payment-success', 'PaymentController@success')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');
Route::get('payment-cancel', 'PaymentController@cancel')->name('payment-cancel');

$is_published = 0;
try {
    $full_data = include('Modules/Gateways/Addon/info.php');
    $is_published = $full_data['is_published'] == 1 ? 1 : 0;
} catch (\Exception $exception) {
}


if (!$is_published) {
    Route::group(['prefix' => 'payment'], function () {

        //SSLCOMMERZ
        Route::group(['prefix' => 'sslcommerz', 'as' => 'sslcommerz.'], function () {
            Route::get('pay', [SslCommerzPaymentController::class, 'index'])->name('pay');
            Route::post('success', [SslCommerzPaymentController::class, 'success'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('failed', [SslCommerzPaymentController::class, 'failed'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
            Route::post('canceled', [SslCommerzPaymentController::class, 'canceled'])
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //STRIPE
        Route::group(['prefix' => 'stripe', 'as' => 'stripe.'], function () {
            Route::get('pay', [StripePaymentController::class, 'index'])->name('pay');
            Route::get('token', [StripePaymentController::class, 'payment_process_3d'])->name('token');
            Route::get('success', [StripePaymentController::class, 'success'])->name('success');
        });

        //RAZOR-PAY
        Route::group(['prefix' => 'razor-pay', 'as' => 'razor-pay.'], function () {
            Route::get('pay', [RazorPayController::class, 'index']);
            Route::post('payment', [RazorPayController::class, 'payment'])->name('payment')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //PAYPAL
        Route::group(['prefix' => 'paypal', 'as' => 'paypal.'], function () {
            Route::get('pay', [PaypalPaymentController::class, 'payment']);
            Route::any('success', [PaypalPaymentController::class, 'success'])->name('success')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
            Route::any('cancel', [PaypalPaymentController::class, 'cancel'])->name('cancel')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);;
        });

        //SENANG-PAY
        Route::group(['prefix' => 'senang-pay', 'as' => 'senang-pay.'], function () {
            Route::get('pay', [SenangPayController::class, 'index']);
            Route::any('callback', [SenangPayController::class, 'return_senang_pay']);
        });

        //PAYTM
        Route::group(['prefix' => 'paytm', 'as' => 'paytm.'], function () {
            Route::get('pay', [PaytmController::class, 'payment']);
            Route::any('response', [PaytmController::class, 'callback'])->name('response')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
        });

        //FLUTTERWAVE
        Route::group(['prefix' => 'flutterwave-v3', 'as' => 'flutterwave-v3.'], function () {
            Route::get('pay', [FlutterwaveV3Controller::class, 'initialize'])->name('pay');
            Route::get('callback', [FlutterwaveV3Controller::class, 'callback'])->name('callback');
        });

        //PAYSTACK
        Route::group(['prefix' => 'paystack', 'as' => 'paystack.'], function () {
            Route::get('pay', [PaystackController::class, 'index'])->name('pay');
            Route::post('payment', [PaystackController::class, 'redirectToGateway'])->name('payment');
            Route::get('callback', [PaystackController::class, 'handleGatewayCallback'])->name('callback');
        });

        //BKASH

        Route::group(['prefix' => 'bkash', 'as' => 'bkash.'], function () {
            // Payment Routes for bKash
            Route::get('make-payment', [BkashPaymentController::class, 'make_tokenize_payment'])->name('make-payment');
            Route::any('callback', [BkashPaymentController::class, 'callback'])->name('callback');

            // Refund Routes for bKash
            // Route::get('refund', 'BkashRefundController@index')->name('bkash-refund');
            // Route::post('refund', 'BkashRefundController@refund')->name('bkash-refund');
        });

        //Liqpay
        Route::group(['prefix' => 'liqpay', 'as' => 'liqpay.'], function () {
            Route::get('payment', [LiqPayController::class, 'payment'])->name('payment');
            Route::any('callback', [LiqPayController::class, 'callback'])->name('callback');
        });

        //MERCADOPAGO
        Route::group(['prefix' => 'mercadopago', 'as' => 'mercadopago.'], function () {
            Route::get('pay', [MercadoPagoController::class, 'index'])->name('index');
            Route::post('make-payment', [MercadoPagoController::class, 'make_payment'])->name('make_payment');
            Route::get('success', [MercadoPagoController::class, 'success'])->name('success');
            Route::get('failed', [MercadoPagoController::class, 'failed'])->name('failed');
        });

        //PAYMOB
        Route::group(['prefix' => 'paymob', 'as' => 'paymob.'], function () {
            Route::any('pay', [PaymobController::class, 'credit'])->name('pay');
            Route::any('callback', [PaymobController::class, 'callback'])->name('callback');
        });

        //PAYTABS
        Route::group(['prefix' => 'paytabs', 'as' => 'paytabs.'], function () {
            Route::any('pay', [PaytabsController::class, 'payment'])->name('pay');
            Route::any('callback', [PaytabsController::class, 'callback'])->name('callback');
            Route::any('response', [PaytabsController::class, 'response'])->name('response');
        });
    });
}


Route::get('module-test', function () {});

Route::get('list-your-business', 'VendorController@create')->name('new-store.create');
Route::get('send_confirmation_sms', 'VendorController@send_confirmation_sms')->name('send_confirmation_sms');

//Restaurant Registration 
Route::group(['prefix' => 'store', 'as' => 'restaurant.'], function () {
    Route::get('apply', 'VendorController@create')->name('create');
    Route::post('apply', 'VendorController@store')->name('store');
    Route::post('store_ajax', 'VendorController@store_ajax')->name('store_ajax');
    Route::get('get-all-modules', 'VendorController@get_all_modules')->name('get-all-modules');
});

//Deliveryman Registration
Route::group(['prefix' => 'deliveryman', 'as' => 'deliveryman.'], function () {
    Route::get('apply', 'DeliveryManController@create')->name('create');
    Route::post('apply', 'DeliveryManController@store')->name('store');
});


Route::get('user-view', [LocationController::class, 'user_view']);
Route::get('location-view', [LocationController::class, 'location_view']);
Route::get('update-live-location/user/{id}', [LocationController::class, 'update']);


Route::get('category/{category_slug}/{city}', [FrontController::class, 'category_listing'])->name('category.listing'); // needs to be later
Route::get('{category_slug}/{slug}', [FrontController::class, 'product_details'])->name('product.details');// needs to be later

Route::get('/heartbeat', function () {

    if (auth()->check() && session()->has('login_log_id')) {

        $lastUpdate = session('last_activity_update');
        $now = now();

        if (!$lastUpdate || $now->diffInSeconds($lastUpdate) > 120) {

            \App\Models\VendorLoginLog::where('id', session('login_log_id'))
                ->update([
                    'last_activity_at' => $now
                ]);

            session(['last_activity_update' => $now]);
        }
    }

    return response()->json(['status' => 'ok']);
});
// WEBSITE SETTINGS ROUTE (VENDOR)

// Route::middleware(['auth'])->group(function () {
    
//     Route::prefix('vendor/settings')->name('vendor.settings.')->group(function () {
        
//         // Website Settings Main Page
//         Route::get('/website', [WebsiteSettingsController::class, 'index'])->name('website');
        
//         // Domain Configuration
//         Route::post('/update-domain', [WebsiteSettingsController::class, 'updateDomain'])->name('update-domain');
//         Route::post('/check-domain', [WebsiteSettingsController::class, 'checkDomainAvailability'])->name('check-domain');
//         Route::get('/dns-instructions', [WebsiteSettingsController::class, 'getDnsInstructions'])->name('dns-instructions');
        
//         // Layout Settings
//         Route::post('/update-layout', [WebsiteSettingsController::class, 'updateLayout'])->name('update-layout');
        
//         // Contact Information
//         Route::post('/webpage-update', [WebsiteSettingsController::class, 'webpageUpdate'])->name('webpage-update');
        
//         // Location
//         Route::post('/update-location', [WebsiteSettingsController::class, 'updateLocation'])->name('update-location');
        
//         // Branding
//         Route::post('/update-branding', [WebsiteSettingsController::class, 'updateBranding'])->name('update-branding');
//     });
// });

// // Customer Store Routes - for subdomain access
// Route::middleware(['subdomain'])->group(function () {
//     Route::get('/', function (Illuminate\Http\Request $request) {
//         $store = $request->attributes->get('vendor_store');
//         $storeConfig = $request->attributes->get('store_config');
        
//         if (!$store) {
//             abort(404, 'Store not found');
//         }
        
//         return view('customer-views.customer-store-page', compact('store', 'storeConfig'));
//     })->name('customer.store.home');
    
//     // Add more customer-facing routes as needed
//     Route::get('/products', function (Illuminate\Http\Request $request) {
//         $store = $request->attributes->get('vendor_store');
//         // Load products logic
//     })->name('customer.store.products');
// });