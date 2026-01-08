<?php

use App\Events\LocationUpdated;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Api\V1\Auth\CustomerAuthController;
use App\Http\Controllers\Api\V1\CustomerController;
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
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Front\FrontController;
use App\Http\Controllers\Front\GoogleController;
use App\Http\Controllers\Front\McVendorController;
use App\Http\Controllers\Front\ModuleInfoController;
use App\Http\Controllers\Front\UserController as FrontUserController;
use App\Http\Controllers\Front\WishlistController;
use App\Http\Controllers\PaymentGateway;
use App\Http\Controllers\Front\SitemapController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\VendorController as ControllersVendorController;
use App\Jobs\PunchInReminder;
use Pusher\Pusher;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;

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

Route::group(['prefix' => 'mc-vendor', 'as' => 'mc-vendor.'], function () {
    Route::get('contact', [McVendorController::class, 'contact'])->name('contact');
    Route::post('send-message', [McVendorController::class, 'send_message'])->name('send-message');
});
// Route::get('/', [UserController::class,'index']);
// Route::get('expire', [UserController::class,'expire']);
Route::get('/generate-sitemap', [SitemapController::class, 'generate'])->name('generate-sitemap');
Route::get('/health-check', fn() => response('OK'));
Route::get('icons', 'Front\FrontController@icons_view');


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



Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('registration-successfull', [FrontController::class, 'registration_success'])->name('registration-successfull');
Route::get('testing', [FrontController::class, 'testing'])->name('testing');
Route::get('signup', [FrontUserController::class, 'signup'])->name('user-signup');

// only if domain is mychitti.net or staging.mychitti.net
Route::domain('{subdomain}.mychitti.net')->group(function () {

    Route::where('subdomain', '^(staging)?$');

    Route::get('login', [FrontUserController::class, 'login'])
        ->name('user-login');

    Route::post('login', [FrontUserController::class, 'login_post'])
        ->name('login.post');
});
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

Route::get('searchbar', [FrontController::class, 'search'])->name('searchbar-web');
Route::post('update-location', [FrontController::class, 'update_location'])->name('update-location');
Route::post('change-module', [FrontController::class, 'change_module'])->name('change-module');
Route::get('all-stores/{type?}', [FrontController::class, 'all_stores'])->name('all-stores');
Route::post('missing-zone-request', [FrontController::class, 'missing_zone_request'])->name('missing-zone-request');

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
    Route::post('submit-service-review', [FrontUserController::class, 'submit_service_review'])->name('submit-service-review');
});
Route::get('delete-account/{id}', [FrontUserController::class, 'delete_account'])->name('delete-account');

Route::group(['middleware' => ['frontuser']], function () {
    Route::get('/', [FrontController::class, 'index'])->name('home');
    Route::get('/service', [FrontController::class, 'service_home'])->name('home.service');
    Route::get('/shop', [FrontController::class, 'index'])->name('home.shop');
    Route::get('contact', [FrontController::class, 'contact'])->name('contact');
    Route::get('cart', [FrontController::class, 'cart'])->name('cart');
    Route::get('store-reviews/{slug}', [FrontController::class, 'store_reviews'])->name('store.reviews');
    Route::get('store/{slug}', [FrontController::class, 'store_details'])->name('store.details');
    Route::get('gallery/{slug}', [FrontController::class, 'store_gallery'])->name('store.gallery');
    Route::get('category/{slug}', [FrontController::class, 'category_listing'])->name('category.listing'); // needs to be first
    Route::post('check-cart', [CartController::class, 'check_cart'])->name('check-cart');
    Route::post('add-to-cart', [CartController::class, 'add_to_cart'])->name('add-to-cart');
    Route::post('remove-from-cart', [CartController::class, 'remove_from_cart'])->name('remove-from-cart');
    Route::post('get-delivery-charges', [CartController::class, 'get_delivery_charges'])->name('get-delivery-charges');
    Route::post('change-cart-quantity', [CartController::class, 'change_cart_quantity'])->name('change-cart-quantity');
    Route::post('update-wishlist', [WishlistController::class, 'update_wishlist'])->name('update-wishlist');
    Route::get('dashboard', [FrontUserController::class, 'dashboard'])->name('dashboard');
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


Route::get('login-new/{tab}', 'LoginController@login_new')->name('login-new');
Route::get('login/{tab}', 'LoginController@login')->name('login');
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


Route::get('{category_slug}/{slug}', [FrontController::class, 'product_details'])->name('product.details');// needs to be later
