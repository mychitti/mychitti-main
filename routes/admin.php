<?php

use App\Http\Controllers\Admin\Item\CategoryController;
use Illuminate\Support\Facades\Route;


Route::group(['namespace' => 'Admin', 'as' => 'admin.'], function () {

    Route::post('send-otp', 'SystemController@send_otp')->name('send-otp');
    Route::post('verify-otp', 'SystemController@verify_otp')->name('verify-otp');
    Route::get('secure-download/{file}', 'ProtectedFileController@download_file')
        ->name('secure.download')->middleware('signed');

    Route::group(['prefix' => 'pr-file', 'as' => 'pr-file.', 'middleware' => ['module:protected_file']], function () {
        Route::post('upload', 'ProtectedFileController@upload_file')->name('upload');
    });

    Route::post('send-vendor-otp', 'VendorController@send_vendor_otp')->name('send-vendor-otp');
    Route::post('mark-notif-read', 'DashboardController@mark_notif_read')->name('mark-notif-read');
    // Route::get('common-dashboard/google-ads', 'DashboardController@google_ads')->name('common-dashboard.google-ads')->middleware('module:google_ads');

    Route::group(['prefix' => 'common-dashboard', 'as' => 'common-dashboard.', 'middleware' => ['module:google_ads']], function () {
        Route::get('google-ads', 'DashboardController@google_ads')->name('google-ads');
        Route::get('google-ads-save', 'DashboardController@google_ads_save')->name('google-ads.save');
        Route::get('google-ads-update', 'DashboardController@google_ads_update')->name('google-ads.update');
        Route::get('google-ads-delete/{id}', 'DashboardController@google_ads_delete')->name('google-ads.delete');
    });
    Route::group(['middleware' => ['admin', 'current-module']], function () {

        Route::group(['prefix' => 'logs', 'as' => 'logs.'], function () {
            Route::get('action-logs', 'DashboardController@action_logs')->name('action-logs');
        });

     
        Route::group(['prefix' => 'requirement', 'as' => 'requirement.'], function () {
            Route::get('delete/{id}', 'RequirementController@delete')->name('delete');
        });

        Route::group(['prefix' => 'services-billing', 'as' => 'modules-billing.', 'middleware' => ['module:service_billing']], function () {
            Route::get('/', 'SubmoduleController@index')->name('index');
            Route::post('update-info', 'SubmoduleController@update_info')->name('update-info');
            Route::get('store-billing', 'SubmoduleController@store_billing')->name('store-billing');
            Route::post('renew-free-trial', 'SubmoduleController@renew_free_trial')->name('renew-free-trial');
            Route::get('history/{id?}', 'SubmoduleController@history')->name('history');
        });

        Route::group(['prefix' => 'category', 'as' => 'category.', 'middleware' => ['module:category']], function () {
            Route::get('fee', [CategoryController::class, 'category_fee'])->name('category-fee');
            Route::post('store-fee', [CategoryController::class, 'store_fee'])->name('store-fee');
            Route::post('get-fee-values', [CategoryController::class, 'get_fee_values'])->name('get-fee-values');
            Route::post('update-fee', [CategoryController::class, 'update_fee'])->name('update-fee');
            Route::get('delete-fee/{id}', [CategoryController::class, 'delete_fee'])->name('delete-fee');
        });

        Route::group(['prefix' => 'service', 'as' => 'service.', 'middleware' => ['module:item']], function () {
            Route::get('config', 'ServiceController@config')->name('config');
            Route::post('common-issue-save', 'ServiceController@common_issue_save')->name('common-issue.save');
            Route::get('common-issue-delete/{id}', 'ServiceController@common_issue_delete')->name('common-issue.delete');
            Route::post('config-update', 'ServiceController@config_update')->name('config.update');
            Route::get('status', 'ItemController@request_status')->name('request-status');
            Route::get('new-status-request', 'ServiceController@new_status_request')->name('new-status-request');
            Route::get('approve-status-request/{id}', 'ServiceController@approve_status_request')->name('approve-status-request');
            Route::get('delete-status/{id}', 'ItemController@delete_status')->name('delete-status');
            Route::post('status-save', 'ItemController@status_save')->name('status-save');
            Route::post('lead-charge-save', 'ItemController@lead_charge_save')->name('lead-charge-save');
            Route::post('lead-charge-update', 'ItemController@lead_charge_update')->name('lead-charge-update');
            Route::get('lead-charge', 'ItemController@lead_charge')->name('lead-charge');
            Route::get('lead-charges', 'ItemController@lead_charge_list')->name('lead-charge-list');
            Route::get('edit-charges/{id}', 'ItemController@edit_charges')->name('edit-charges');
            Route::get('list', 'ServiceController@lead_list')->name('lead-list');
            Route::get('detail/{id}', 'ServiceController@lead_detail')->name('lead-detail');
            Route::get('lead-timeline/{id}', 'ServiceController@lead_timeline')->name('lead-timeline');
        });

        Route::get('keywords', 'KeywordsController@index')->name('keywords');

        Route::group(['prefix' => 'lead', 'as' => 'lead.', 'middleware' => ['planwise:leads_manage']], function () {
            Route::post('save-comment', 'LeadController@save_comment')->name('save-comment');
            Route::get('list', 'LeadController@index')->name('list');
            Route::get('add', 'LeadController@add')->name('add');
            Route::post('status-change', 'LeadController@status_change')->name('status-change');
            Route::post('save-info', 'LeadController@save_info')->name('save-info');
            Route::post('lead_approval', 'LeadController@lead_approval')->name('lead_approval');
            Route::get('delete/{id}', 'LeadController@delete')->name('delete');
            Route::get('manage/{id}', 'LeadController@manage')->name('manage');
        });
        Route::group(['prefix' => 'quotation', 'as' => 'quotation.', 'middleware' => ['module:quotaiton_manage']], function () {
            Route::get('list', 'QuoteController@index')->name('list');
            Route::get('new', 'QuoteController@new')->name('new');
            Route::get('accepted', 'QuoteController@accepted')->name('accepted');
            Route::get('declined', 'QuoteController@declined')->name('declined');
            Route::get('send-quote/{id}', 'QuoteController@send_quote')->name('send-quote');
            Route::get('add', 'QuoteController@add')->name('add');
            Route::post('status-change', 'QuoteController@status_change')->name('status-change');
            Route::post('save-info', 'QuoteController@save_info')->name('save-info');
            Route::post('lead_approval', 'QuoteController@lead_approval')->name('lead_approval');
            Route::get('delete/{id}', 'QuoteController@delete')->name('delete');
            Route::get('manage/{id}', 'QuoteController@manage')->name('manage');
        });

        Route::group(['prefix' => 'project', 'as' => 'project.', 'middleware' => ['module:projects_manage']], function () {
            Route::get('list', 'ProjectController@index')->name('all');
            Route::get('add', 'ProjectController@add')->name('add');
            Route::get('status-change/{id}/{status}', 'ProjectController@status_change')->name('status-change');
            Route::post('save-info', 'ProjectController@save_info')->name('save-info');
            Route::get('delete/{id}', 'ProjectController@delete')->name('delete');
            Route::get('manage/{id}', 'ProjectController@manage')->name('manage');
        });

        Route::group(['prefix' => 'plan', 'as' => 'plan.', 'middleware' => ['module:subscription_plan']], function () {
            Route::get('edit/{id}', 'PlanController@edit')->name('edit');
            Route::post('update/{id}', 'PlanController@update')->name('update');
            Route::delete('delete/{id}', 'PlanController@delete')->name('delete');

            // ===============================
            Route::post('buy-plan', 'VendorController@buyPlan')->name('buy-plan');
            Route::get('store-modules/{store_id}', 'VendorController@store_enabled_modules')->name('store-modules');
            Route::post('buy-module', 'VendorController@buyModule')->name('buy-module');
            Route::post('buy-plan-store', 'VendorController@buy_plan_for_store')->name('buy-plan-store');
            Route::get('buy-module-store', 'VendorController@buy_module_for_store')->name('module-store');
            Route::get('all-plans', 'VendorController@allPlans')->name('all-plans');
            // ===============================

            Route::get('add-new/{type?}/{req_id?}', 'PlanController@index')->name('add-new');
            Route::get('status/{id}/{status}', 'PlanController@status')->name('status');
            Route::get('list', 'PlanController@list')->name('list');
            Route::get('requests', 'PlanController@plan_requests')->name('requests');
            Route::get('module-pricing', 'PlanController@module_pricing')->name('module-pricing');
            Route::get('stores', 'PlanController@stores')->name('stores');
            Route::post('store', 'PlanController@store')->name('store');
        });
        Route::group(['prefix' => 'account', 'as' => 'account.', 'middleware' => ['module:account']], function () {
            Route::get('report', 'AccountController@report')->name('report');
            Route::get('list', 'AccountController@index')->name('list');
            Route::get('add', 'AccountController@add')->name('add');
            Route::post('save-info', 'AccountController@save_info')->name('save');
            Route::get('delete/{id}', 'AccountController@delete')->name('delete');
            Route::get('edit/{id}', 'AccountController@edit')->name('edit');
        });

        Route::get('get-all-stores', 'VendorController@get_all_stores')->name('get_all_stores');
        Route::get('lang/{locale}', 'LanguageController@lang')->name('lang');
        Route::get('settings', 'SystemController@settings')->name('settings');
        Route::post('settings', 'SystemController@settings_update');
        Route::post('settings-password', 'SystemController@settings_password_update')->name('settings-password');
        Route::get('/get-store-data', 'SystemController@store_data')->name('get-store-data');
        Route::post('remove_image', 'BusinessSettingsController@remove_image')->name('remove_image');
        //dashboard
        Route::get('/', 'DashboardController@dashboard')->name('dashboard');
        Route::get('common-dashboard', 'DashboardController@common_dashboard')->name('common-dashboard');

        Route::group(['prefix' => 'billing', 'as' => 'billing.', 'middleware' => ['module:billing']], function () {
            Route::get('/', 'BillingController@billing')->name('index');
            Route::post('save-manual-invoice', 'BillingController@save_invoice')->name('save-manual-invoice');
            Route::get('test-invoice', 'BillingController@test_invoice')->name('test-invoice');
            Route::get('invoice-view/{id}', 'BillingController@invoice_view')->name('invoice-view');
            Route::get('invoice-delete/{type}/{id}', 'BillingController@invoice_delete')->name('invoice-delete');
            Route::post('invoice-bulk-delete', 'BillingController@invoice_bulk_delete')->name('invoice-bulk-delete');
            Route::post('invoice-correction', 'BillingController@invoice_correction')->name('invoice-correction');
        });

        Route::get('maintenance-mode', 'SystemController@maintenance_mode')->name('maintenance-mode');
        Route::get('landing-page', 'SystemController@landing_page')->name('landing-page');


        Route::group(['prefix' => 'employee', 'as' => 'employee.', 'middleware' => ['module:employee']], function () {
            Route::get('add-new', 'EmployeeController@add_new')->name('add-new');
            Route::post('add-new', 'EmployeeController@store');
            Route::get('list', 'EmployeeController@list')->name('list');
            Route::get('edit/{id}', 'EmployeeController@edit')->name('edit');
            Route::delete('delete/{id}', 'EmployeeController@distroy')->name('delete');
            Route::post('update/{id}', 'EmployeeController@update')->name('update');
            Route::post('search', 'EmployeeController@search')->name('search');
            Route::get('list-export', 'EmployeeController@list_export')->name('export-employee');
        });


        Route::group(['prefix' => 'parcel', 'as' => 'parcel.', 'middleware' => ['module:parcel']], function () {
            Route::get('category/status/{id}/{status}', 'ParcelCategoryController@status')->name('category.status');
            Route::resource('category', 'ParcelCategoryController');
            Route::get('orders/{status}', 'ParcelController@orders')->name('orders');
            Route::get('orders/export/{status}/{file_type}', 'ParcelController@parcel_orders_export')->name('parcel_orders_export');
            Route::get('details/{id}', 'ParcelController@order_details')->name('order.details');
            Route::get('settings', 'ParcelController@settings')->name('settings');
            Route::post('settings', 'ParcelController@update_settings')->name('update.settings');
            Route::get('dispatch/{status}', 'ParcelController@dispatch_list')->name('list');
            Route::post('instruction', 'ParcelController@instruction')->name('instruction');
            Route::get('/instruction/{id}/{status}', 'ParcelController@instruction_status')->name('instruction_status');
            Route::put('instruction_edit/', 'ParcelController@instruction_edit')->name('instruction_edit');
            Route::delete('instruction_delete/{id}', 'ParcelController@instruction_delete')->name('instruction_delete');
        });

        Route::group(['prefix' => 'dashboard-stats', 'as' => 'dashboard-stats.'], function () {
            Route::post('order', 'DashboardController@order')->name('order');
            Route::post('zone', 'DashboardController@zone')->name('zone');
            Route::post('user-overview', 'DashboardController@user_overview')->name('user-overview');
            Route::post('commission-overview', 'DashboardController@commission_overview')->name('commission-overview');
            Route::post('business-overview', 'DashboardController@business_overview')->name('business-overview');
        });

        Route::post('item/variant-price', 'ItemController@variant_price')->name('item.variant-price');

        Route::group(['prefix' => 'item', 'as' => 'item.', 'middleware' => ['module:item']], function () {


            Route::post('get-areas-in-zone', 'ItemController@get_areas_in_zone')->name('get-areas-in-zone');
            Route::post('update-homepage-item', 'ItemController@update_homepage_item')->name('update-homepage-item');
            Route::get('location-keywords', 'ItemController@location_keywords')->name('location-keywords');
            Route::get('keywords', 'ItemController@keywords')->name('keywords');
            Route::post('keyword-save', 'ItemController@keyword_save')->name('keyword-save');
            Route::post('location-keyword-save', 'ItemController@location_keyword_save')->name('location-keyword-save');
            Route::get('delete-location-keyword/{id}', 'ItemController@delete_location_keyword')->name('delete-location-keyword');
            Route::get('delete-keyword/{id}', 'ItemController@delete_keyword')->name('delete-keyword');
            Route::get('add-new', 'ItemController@index')->name('add-new');
            Route::post('variant-combination', 'ItemController@variant_combination')->name('variant-combination');
            Route::post('update-variant-combination', 'ItemController@update_variant_combination')->name('update-variant-combination');
            Route::post('store', 'ItemController@store')->name('store');
            Route::get('edit/{id}', 'ItemController@edit')->name('edit');
            Route::post('update/{id}', 'ItemController@update')->name('update');
            Route::get('list', 'ItemController@list')->name('list');
            Route::delete('delete/{id}', 'ItemController@delete')->name('delete');
            Route::post('bulk_delete', 'ItemController@bulk_delete')->name('bulk_delete');
            Route::get('status/{id}/{status}', 'ItemController@status')->name('status');
            Route::get('review-status/{id}/{status}', 'ItemController@reviews_status')->name('reviews.status');
            Route::post('search', 'ItemController@search')->name('search');
            Route::post('store/{store_id}/search', 'ItemController@search_store')->name('store-search');
            Route::get('reviews', 'ItemController@review_list')->name('reviews');
            // Route::post('reviews/search', 'ItemController@review_search')->name('reviews.search');
            Route::get('remove-image', 'ItemController@remove_image')->name('remove-image');
            Route::get('view/{id}', 'ItemController@view')->name('view');
            Route::get('store-item-export', 'ItemController@store_item_export')->name('store-item-export');
            Route::get('reviews-export', 'ItemController@reviews_export')->name('reviews_export');
            Route::get('item-wise-reviews-export', 'ItemController@item_wise_reviews_export')->name('item_wise_reviews_export');

            Route::get('new/item/list', 'ItemController@approval_list')->name('approval_list');
            Route::get('approved', 'ItemController@approved')->name('approved');
            Route::get('product_denied', 'ItemController@deny')->name('deny');
            Route::get('requested/item/view/{id}', 'ItemController@requested_item_view')->name('requested_item_view');
            Route::get('product-gallery', 'ItemController@product_gallery')->name('product_gallery');

            //ajax request
            Route::get('get-categories', 'ItemController@get_categories')->name('get-categories');
            Route::get('get-items', 'ItemController@get_items')->name('getitems');
            Route::get('get-items-flashsale', 'ItemController@get_items_flashsale')->name('getitems-flashsale');
            Route::post('food-variation-generate', 'ItemController@food_variation_generator')->name('food-variation-generate');
            Route::post('variation-generate', 'ItemController@variation_generator')->name('variation-generate');


            Route::get('export', 'ItemController@export')->name('export');

            //Mainul
            Route::get('get-variations', 'ItemController@get_variations')->name('get-variations');
            Route::post('stock-update', 'ItemController@stock_update')->name('stock-update');

            //Import and export
            Route::get('bulk-import', 'ItemController@bulk_import_index')->name('bulk-import');
            Route::post('bulk-import', 'ItemController@bulk_import_data');
            Route::get('bulk-export', 'ItemController@bulk_export_index')->name('bulk-export-index');
            Route::post('bulk-export', 'ItemController@bulk_export_data')->name('bulk-export');

            Route::get('trash', 'ItemController@trash')->name('trash.view');
            Route::group(['prefix' => 'trash', 'as' => 'trash.', 'middleware' => ['module:item']], function () {
                Route::get('restore-item/{id}', 'ItemController@restore_item')->name('restore-item');
                Route::get('delete-item/{id}', 'ItemController@permanent_delete_item')->name('delete-item');
                Route::get('restore-category/{id}', 'ItemController@restore_category')->name('restore-category');
                Route::get('delete-category/{id}', 'ItemController@permanent_delete_category')->name('delete-category');
                Route::get('view-category/{id}', 'ItemController@view_trashed_category')->name('view-category');
                Route::get('view-item/{id}', 'ItemController@view_trashed_item')->name('view-item');
            });
        });

        // blog
        Route::group(['prefix' => 'blog', 'as' => 'blog.', 'middleware' => ['module:blog']], function () {
            // blog category 
            Route::get('category', 'BlogController@category')->name('category');
            Route::get('category-edit/{id}', 'BlogController@category_edit')->name('category-edit');
            Route::post('category-update/{id}', 'BlogController@category_update')->name('category-update');
            Route::delete('category-delete/{id}', 'BlogController@category_delete')->name('category-delete');
            Route::post('category', 'BlogController@category_store')->name('category-store');
            Route::get('category-select_type', 'BlogController@category_select_type')->name('category-select_type');

            // blog post
            Route::post('/upload-image', 'BlogController@uploadImage')
                ->name('image-upload');

            Route::get('edit/{id}', 'BlogController@edit')->name('edit');
            Route::delete('delete/{id}', 'BlogController@delete')->name('delete');
            Route::get('list', 'BlogController@index')->name('list');
            Route::get('add-new', 'BlogController@add_new')->name('add-new');
            Route::post('save', 'BlogController@save')->name('save');
            Route::post('update', 'BlogController@update')->name('update');
        });

        Route::group(['prefix' => 'banner', 'as' => 'banner.', 'middleware' => ['module:banner']], function () {
            Route::post('store-offer-banner', 'OtherBannerController@store_offer_banner')->name('store-offer-banner');
            Route::get('offer', 'OtherBannerController@offer_banner')->name('offer');
            Route::get('offer-status/{id}/{status}', 'OtherBannerController@offer_banner_status')->name('offer.status');
            Route::get('offer-approve/{id}', 'OtherBannerController@offer_banner_approve')->name('offer.approve');
            Route::get('offer-delete/{id}', 'OtherBannerController@offer_banner_delete')->name('offer.delete');
        });
        Route::group(['prefix' => 'promotional-banner', 'as' => 'promotional-banner.', 'middleware' => ['module:banner']], function () {
            Route::get('add-new', 'OtherBannerController@promotional_index')->name('add-new');
            Route::get('add-video', 'OtherBannerController@promotional_video')->name('add-video');
            Route::post('store', 'OtherBannerController@promotional_store')->name('store');
            Route::get('edit/{id}', 'OtherBannerController@promotional_edit')->name('edit');
            Route::post('update/{id}', 'OtherBannerController@promotional_update')->name('update');
            Route::get('update-status/{id}/{status}', 'OtherBannerController@promotional_status')->name('update-status');
            Route::delete('delete/{banner}', 'OtherBannerController@promotional_destroy')->name('delete');
            Route::get('add-why-choose', 'OtherBannerController@promotional_why_choose')->name('add-why-choose');
            Route::post('why-choose/store', 'OtherBannerController@why_choose_store')->name('why-choose-store');
            Route::get('why-choose/edit/{id}', 'OtherBannerController@why_choose_edit')->name('why-choose-edit');
            Route::post('why-choose/update/{id}', 'OtherBannerController@why_choose_update')->name('why-choose-update');
            Route::get('why-choose/update-status/{id}/{status}', 'OtherBannerController@why_choose_status')->name('why-choose-status-update');
            Route::delete('why-choose/delete/{banner}', 'OtherBannerController@why_choose_destroy')->name('why-choose-delete');
            Route::post('video-content/store', 'OtherBannerController@video_content_store')->name('video-content-store');
            Route::post('video-image/store', 'OtherBannerController@video_image_store')->name('video-image-store');
        });

        Route::group(['prefix' => 'campaign', 'as' => 'campaign.', 'middleware' => ['module:campaign']], function () {
            Route::get('{type}/add-new', 'CampaignController@index')->name('add-new');
            Route::post('store/basic', 'CampaignController@storeBasic')->name('store-basic');
            Route::post('store/item', 'CampaignController@storeItem')->name('store-item');
            Route::get('{type}/edit/{campaign}', 'CampaignController@edit')->name('edit');
            Route::get('{type}/view/{campaign}', 'CampaignController@view')->name('view');
            Route::post('basic/update/{campaign}', 'CampaignController@update')->name('update-basic');
            Route::post('item/update/{campaign}', 'CampaignController@updateItem')->name('update-item');
            Route::get('remove-store/{campaign}/{store}', 'CampaignController@remove_store')->name('remove-store');
            Route::post('add-store/{campaign}', 'CampaignController@addstore')->name('addstore');
            Route::get('{type}/list', 'CampaignController@list')->name('list');
            Route::get('status/{type}/{id}/{status}', 'CampaignController@status')->name('status');
            Route::delete('delete/{campaign}', 'CampaignController@delete')->name('delete');
            Route::delete('item/delete/{campaign}', 'CampaignController@delete_item')->name('delete-item');
            Route::post('basic-search', 'CampaignController@searchBasic')->name('searchBasic');
            Route::post('item-search', 'CampaignController@searchItem')->name('searchItem');
            Route::get('store-confirmation/{campaign}/{id}/{status}', 'CampaignController@store_confirmation')->name('store_confirmation');
            Route::get('basic-campaign-export', 'CampaignController@basic_campaign_export')->name('basic_campaign_export');
            Route::get('item-campaign-export', 'CampaignController@item_campaign_export')->name('item_campaign_export');
        });


        Route::group(['prefix' => 'flash-sale', 'as' => 'flash-sale.'], function () {
            Route::get('add-new', 'FlashSaleController@index')->name('add-new');
            Route::post('store', 'FlashSaleController@store')->name('store');
            Route::get('edit/{id}', 'FlashSaleController@edit')->name('edit');
            Route::post('update/{id}', 'FlashSaleController@update')->name('update');
            Route::get('publish/{id}/{publish}', 'FlashSaleController@publish')->name('publish');
            Route::delete('delete/{id}', 'FlashSaleController@delete')->name('delete');
            Route::get('add-product/{id}', 'FlashSaleController@add_product')->name('add-product');
            Route::post('store-product', 'FlashSaleController@store_product')->name('store-product');
            Route::delete('delete-product/{id}', 'FlashSaleController@delete_product')->name('delete-product');
            Route::get('status/{id}/{status}', 'FlashSaleController@status_product')->name('status-product');
        });

        Route::group(['prefix' => 'message', 'as' => 'message.', 'middleware' => ['module:customer_management']], function () {
            Route::get('list', 'ConversationController@list')->name('list');
            Route::post('store/{user_id}', 'ConversationController@store')->name('store');
            Route::get('view/{conversation_id}/{user_id}', 'ConversationController@view')->name('view');
        });


        Route::get('report', 'VendorController@report')->name('store.report');

        Route::group(['prefix' => 'store', 'as' => 'store.'], function () {

            // WALLET ================
            Route::group(['prefix' => 'wallet', 'as' => 'wallet.'], function () {
                Route::get('/', 'VendorWalletController@index')->name('index');
                Route::post('recharge', 'VendorWalletController@recharge')->name('recharge');
            });

            Route::get('get-matches', 'VendorController@get_matches')->name('get-matches');
            Route::get('verify-doc/{id}', 'VendorController@verify_doc')->name('verify-doc');


            Route::post('update_id', 'VendorController@update_id')->name('update_id');
            Route::get('terms-and-conditions', 'VendorController@terms_and_conditions')->name('terms-and-conditions');
            Route::post('terms-and-conditions', 'VendorController@terms_and_conditions_store')->name('terms-and-conditions.store');
            Route::post('suspend-account', 'VendorController@suspend_account')->name('suspend-account');
            Route::get('get-stores-data/{store}', 'VendorController@get_store_data')->name('get-stores-data');
            Route::get('store-filter/{id}', 'VendorController@store_filter')->name('store-filter');
            Route::get('get-account-data/{store}', 'VendorController@get_account_data')->name('store-filters');
            Route::get('get-stores', 'VendorController@get_stores')->name('get-stores');
            Route::get('get-addons', 'VendorController@get_addons')->name('get_addons');


            //  IF HAS EITHER store OR store_add_edit PERMISSION
            Route::group(['middleware' => ['module:store,store_add_edit']], function () {
                Route::post('import', 'VendorController@import')->name('import');

                Route::get('add', 'VendorController@index')->name('add');
                Route::post('store', 'VendorController@store')->name('store');
                Route::get('edit/{id}', 'VendorController@edit')->name('edit');
                Route::post('update/{store}', 'VendorController@update')->name('update');
                Route::get('list', 'VendorController@list')->name('list');
            });

            //  IF HAS store PERMISSION
            Route::group(['middleware' => ['module:store']], function () {
                Route::get('update-application/{id}/{status}', 'VendorController@update_application')->name('application');
                Route::post('deny-application', 'VendorController@deny_application')->name('deny-application');
                Route::get('types', 'VendorController@types')->name('types');
                Route::post('type_store', 'VendorController@type_store')->name('type_store');
                Route::post('save_modules', 'VendorController@save_modules')->name('save_modules');
                Route::post('update_modules', 'VendorController@update_modules')->name('update_modules');
                Route::get('types_delete/{id}', 'VendorController@types_delete')->name('types_delete');

                Route::post('discount/{store}', 'VendorController@discountSetup')->name('discount');
                Route::post('update-settings/{store}', 'VendorController@updateStoreSettings')->name('update-settings');
                Route::post('update-meta-data/{store}', 'VendorController@updateStoreMetaData')->name('update-meta-data');
                Route::delete('delete/{store}', 'VendorController@destroy')->name('delete');
                Route::delete('clear-discount/{store}', 'VendorController@cleardiscount')->name('clear-discount');
                // Route::get('view/{store}', 'VendorController@view')->name('view_tab');
                Route::get('disbursement-export/{id}/{type}', 'VendorController@disbursement_export')->name('disbursement-export');
                Route::get('view/{store}/{tab?}/{sub_tab?}', 'VendorController@view')->name('view');
                Route::get('pending-requests', 'VendorController@pending_requests')->name('pending-requests');
                Route::get('deny-requests', 'VendorController@deny_requests')->name('deny-requests');
                Route::post('search', 'VendorController@search')->name('search');
                Route::get('export', 'VendorController@export')->name('export');
                Route::get('store-wise-reviwe-export', 'VendorController@store_wise_reviwe_export')->name('store_wise_reviwe_export');
                Route::get('export/cash/{type}/{store_id}', 'VendorController@cash_export')->name('cash_export');
                Route::get('export/order/{type}/{store_id}', 'VendorController@order_export')->name('order_export');
                Route::get('export/withdraw/{type}/{store_id}', 'VendorController@withdraw_trans_export')->name('withdraw_trans_export');
                Route::get('status/{store}/{status}', 'VendorController@status')->name('status');
                Route::get('featured/{store}/{status}', 'VendorController@featured')->name('featured');
                Route::get('toggle-settings-status/{store}/{status}/{menu}', 'VendorController@store_status')->name('toggle-settings');
                Route::post('status-filter', 'VendorController@status_filter')->name('status-filter');


                Route::get('recommended-store', 'VendorController@recommended_store')->name('recommended_store');
                Route::get('recommended-store-add', 'VendorController@recommended_store_add')->name('recommended_store_add');
                Route::get('recommended-store-status/{id}/{status}', 'VendorController@recommended_store_status')->name('recommended_store_status');
                Route::delete('recommended-store-remove/{id}', 'VendorController@recommended_store_remove')->name('recommended_store_remove');
                Route::get('shuffle-recommended-store/{status}', 'VendorController@shuffle_recommended_store')->name('shuffle_recommended_store');

                Route::get('selected-stores', 'VendorController@selected_stores')->name('selected_stores');


                //Import and export
                Route::get('bulk-import', 'VendorController@bulk_import_index')->name('bulk-import');
                Route::post('bulk-import', 'VendorController@bulk_import_data');
                Route::get('bulk-export', 'VendorController@bulk_export_index')->name('bulk-export-index');
                Route::post('bulk-export', 'VendorController@bulk_export_data')->name('bulk-export');
                //Store shcedule
                Route::post('add-schedule', 'VendorController@add_schedule')->name('add-schedule');
                Route::get('remove-schedule/{store_schedule}', 'VendorController@remove_schedule')->name('remove-schedule');
            });

            Route::group(['middleware' => ['module:withdraw_list']], function () {
                Route::post('withdraw-status/{id}', 'VendorController@withdrawStatus')->name('withdraw_status');
                Route::get('withdraw_list', 'VendorController@withdraw')->name('withdraw_list');
                Route::post('withdraw_search', 'VendorController@withdraw_search')->name('withdraw_search');
                Route::get('withdraw_export', 'VendorController@withdraw_export')->name('withdraw_export');
                Route::get('withdraw-view/{withdraw_id}/{seller_id}', 'VendorController@withdraw_view')->name('withdraw_view');
            });

            // message
            Route::get('message/{conversation_id}/{user_id}', 'VendorController@conversation_view')->name('message-view');
            Route::get('message/list', 'VendorController@conversation_list')->name('message-list');
        });

        Route::get('addon/system-addons', function () {
            return to_route('admin.system-addon.index');
        })->name('addon.index');
        Route::get('order/generate-order-invoice/{id}', 'OrderController@generate_order_invoice')->name('order.generate-order-invoice');
        Route::get('order/generate-invoice/{id}', 'OrderController@generate_invoice')->name('order.generate-invoice');
        Route::get('order/print-invoice/{id}', 'OrderController@print_invoice')->name('order.print-invoice');
        Route::get('order/status', 'OrderController@status')->name('order.status');
        Route::get('order/offline-payment', 'OrderController@offline_payment')->name('order.offline_payment');
        Route::group(['prefix' => 'order', 'as' => 'order.', 'middleware' => ['module:order']], function () {
            Route::get('list/{status}', 'OrderController@list')->name('list');
            Route::get('details/{id}', 'OrderController@details')->name('details');
            Route::get('all-details/{id}', 'OrderController@all_details')->name('all-details');

            // Route::put('status-update/{id}', 'OrderController@status')->name('status-update');
            Route::get('view/{id}', 'OrderController@view')->name('view');
            Route::post('update-shipping/{order}', 'OrderController@update_shipping')->name('update-shipping');
            Route::delete('delete/{id}', 'OrderController@delete')->name('delete');

            Route::get('add-delivery-man/{order_id}/{delivery_man_id}', 'OrderController@add_delivery_man')->name('add-delivery-man');
            Route::get('payment-status', 'OrderController@payment_status')->name('payment-status');

            Route::post('add-payment-ref-code/{id}', 'OrderController@add_payment_ref_code')->name('add-payment-ref-code');
            Route::post('add-order-proof/{id}', 'OrderController@add_order_proof')->name('add-order-proof');
            Route::get('remove-proof-image', 'OrderController@remove_proof_image')->name('remove-proof-image');
            Route::get('store-filter/{store_id}', 'OrderController@restaurnt_filter')->name('store-filter');
            Route::get('filter/reset', 'OrderController@filter_reset');
            Route::post('filter', 'OrderController@filter')->name('filter');
            Route::get('search', 'OrderController@search')->name('search');
            Route::post('store/search', 'OrderController@store_order_search')->name('store-search');
            Route::get('store/export', 'OrderController@store_order_export')->name('store-export');
            //order update
            Route::post('add-to-cart', 'OrderController@add_to_cart')->name('add-to-cart');
            Route::post('remove-from-cart', 'OrderController@remove_from_cart')->name('remove-from-cart');
            Route::get('update/{order}', 'OrderController@update')->name('update');
            Route::get('edit-order/{order}', 'OrderController@edit')->name('edit');
            Route::get('quick-view', 'OrderController@quick_view')->name('quick-view');
            Route::get('quick-view-cart-item', 'OrderController@quick_view_cart_item')->name('quick-view-cart-item');
            Route::get('export-orders/{file_type}/{status}/{type}', 'OrderController@export_orders')->name('export');

            Route::get('offline/payment/list/{status}', 'OrderController@offline_verification_list')->name('offline_verification_list');
        });
        // Refund
        Route::group(['prefix' => 'refund', 'as' => 'refund.', 'middleware' => ['module:order']], function () {
            Route::get('settings', 'OrderController@refund_settings')->name('refund_settings');
            Route::get('refund_mode', 'OrderController@refund_mode')->name('refund_mode');
            Route::post('refund_reason', 'OrderController@refund_reason')->name('refund_reason');
            Route::get('/status/{id}/{status}', 'OrderController@reason_status')->name('reason_status');
            Route::put('reason_edit/', 'OrderController@reason_edit')->name('reason_edit');
            Route::delete('reason_delete/{id}', 'OrderController@reason_delete')->name('reason_delete');
            Route::put('order_refund_rejection/', 'OrderController@order_refund_rejection')->name('order_refund_rejection');
            Route::get('/{status}', 'OrderController@list')->name('refund_attr');
        });

        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.'], function () {
            Route::get('homepage-config', 'BusinessSettingsController@homepage_config')->name('homepage-config');
            Route::post('homepage-config-update', 'BusinessSettingsController@homepage_config_update')->name('homepage-config.update');
            Route::get('vendor-homepage-config', 'BusinessSettingsController@vendor_homepage_config')->name('vendor-homepage-config');
            Route::post('vendor-homepage-config-update', 'BusinessSettingsController@vendor_homepage_config_update')->name('vendor-homepage-config.update');
            Route::get('vendor-modules', 'BusinessSettingsController@vendor_modules')->name('vendor-modules');
            Route::get('vendor-module/{module}', 'BusinessSettingsController@vendor_module_edit')->name('vendor-module.edit');
            Route::post('vendor-module/update', 'BusinessSettingsController@vendor_module_update')->name('vendor-module.update');
            Route::post('vendor-module/store', 'BusinessSettingsController@vendor_module_store')->name('vendor-module.store');
            Route::group(['prefix' => 'tax-rate', 'as' => 'tax-rate.'], function () {
                Route::get('delete/{id}', 'BusinessSettingsController@tax_rate_delete')->name('delete');
                Route::get('details/{id}', 'BusinessSettingsController@tax_rate_details')->name('details');
                Route::post('update', 'BusinessSettingsController@tax_rate_update')->name('update');
                Route::post('save', 'BusinessSettingsController@tax_rate_save')->name('save');
            });

            Route::get('business-setup/{tab?}', 'BusinessSettingsController@business_index')->name('business-setup');
            Route::get('mcvendor-setup/{tab?}', 'McvendorSettingsController@mcvendor_index')->name('mcvendor-setup');
            Route::post('mcvendor-setup-update', 'McvendorSettingsController@mcvendor_setup')->name('mcvendor-setup-update');
            Route::get('app-setup', 'BusinessSettingsController@app_setup')->name('app-setup');
            Route::get('react-setup', 'BusinessSettingsController@react_setup')->name('react-setup');
            Route::post('react-update', 'BusinessSettingsController@react_update')->name('react-update');
            Route::post('update-setup', 'BusinessSettingsController@business_setup')->name('update-setup');
            Route::post('update-setup2', 'BusinessSettingsController@business_setup2')->name('update-setup2');
            Route::post('update-landing-setup', 'BusinessSettingsController@landing_page_settings_update')->name('update-landing-setup');
            Route::delete('delete-custom-landing-page', 'BusinessSettingsController@delete_custom_landing_page')->name('delete-custom-landing-page');
            Route::post('update-dm', 'BusinessSettingsController@update_dm')->name('update-dm');
            Route::post('update-disbursement', 'BusinessSettingsController@update_disbursement')->name('update-disbursement');
            Route::post('update-websocket', 'BusinessSettingsController@update_websocket')->name('update-websocket');
            Route::post('update-store', 'BusinessSettingsController@update_store')->name('update-store');
            Route::post('update-order', 'BusinessSettingsController@update_order')->name('update-order');
            Route::get('app-settings', 'BusinessSettingsController@app_settings')->name('app-settings.get');
            Route::POST('app-settings', 'BusinessSettingsController@update_app_settings')->name('app-settings');
            Route::get('pages/admin-landing-page-settings/{tab?}', 'BusinessSettingsController@admin_landing_page_settings')->name('admin-landing-page-settings.get');
            Route::POST('pages/admin-landing-page-settings/{tab}', 'BusinessSettingsController@update_admin_landing_page_settings')->name('admin-landing-page-settings');
            Route::get('promotional-status/{id}/{status}', 'BusinessSettingsController@promotional_status')->name('promotional-status');
            Route::get('pages/admin-landing-page-settings/promotional-section/edit/{id}', 'BusinessSettingsController@promotional_edit')->name('promotional-edit');
            Route::post('promotional-section/update/{id}', 'BusinessSettingsController@promotional_update')->name('promotional-update');
            Route::delete('banner/delete/{banner}', 'BusinessSettingsController@promotional_destroy')->name('promotional-delete');
            Route::get('feature-status/{id}/{status}', 'BusinessSettingsController@feature_status')->name('feature-status');
            Route::get('pages/admin-landing-page-settings/feature-list/edit/{id}', 'BusinessSettingsController@feature_edit')->name('feature-edit');
            Route::post('feature-section/update/{id}', 'BusinessSettingsController@feature_update')->name('feature-update');
            Route::delete('feature/delete/{feature}', 'BusinessSettingsController@feature_destroy')->name('feature-delete');
            Route::get('criteria-status/{id}/{status}', 'BusinessSettingsController@criteria_status')->name('criteria-status');
            Route::get('pages/admin-landing-page-settings/why-choose-us/criteria-list/edit/{id}', 'BusinessSettingsController@criteria_edit')->name('criteria-edit');
            Route::post('criteria-section/update/{id}', 'BusinessSettingsController@criteria_update')->name('criteria-update');
            Route::delete('admin/criteria/delete/{criteria}', 'BusinessSettingsController@criteria_destroy')->name('criteria-delete');
            Route::get('review-status/{id}/{status}', 'BusinessSettingsController@review_status')->name('review-status');
            Route::get('pages/admin-landing-page-settings/testimonials/review-list/edit/{id}', 'BusinessSettingsController@review_edit')->name('review-edit');
            Route::post('review-section/update/{id}', 'BusinessSettingsController@review_update')->name('review-update');
            Route::delete('review/delete/{review}', 'BusinessSettingsController@review_destroy')->name('review-delete');
            Route::get('pages/react-landing-page-settings/{tab?}', 'BusinessSettingsController@react_landing_page_settings')->name('react-landing-page-settings.get');
            Route::POST(
                'pages/react-landing-page-settings/{tab?}',
                'BusinessSettingsController@update_react_landing_page_settings'
            )->name('react-landing-page-settings');
            Route::DELETE('react-landing-page-settings/{tab}/{key}', 'BusinessSettingsController@delete_react_landing_page_settings')->name('react-landing-page-settings-delete');
            Route::get('review-react-status/{id}/{status}', 'BusinessSettingsController@review_react_status')->name('review-react-status');
            Route::get('pages/react-landing-page-settings/testimonials/review-react-list/edit/{id}', 'BusinessSettingsController@review_react_edit')->name('review-react-edit');
            Route::post('review-react-section/update/{id}', 'BusinessSettingsController@review_react_update')->name('review-react-update');
            Route::delete('review-react/delete/{review}', 'BusinessSettingsController@review_react_destroy')->name('review-react-delete');
            Route::get('pages/flutter-landing-page-settings/{tab?}', 'BusinessSettingsController@flutter_landing_page_settings')->name('flutter-landing-page-settings.get');
            Route::POST('pages/flutter-landing-page-settings/{tab}', 'BusinessSettingsController@update_flutter_landing_page_settings')->name('flutter-landing-page-settings');
            Route::get('flutter-criteria-status/{id}/{status}', 'BusinessSettingsController@flutter_criteria_status')->name('flutter-criteria-status');
            Route::get('pages/flutter-landing-page-settings/special-criteria/edit/{id}', 'BusinessSettingsController@flutter_criteria_edit')->name('flutter-criteria-edit');
            Route::post('flutter-criteria-section/update/{id}', 'BusinessSettingsController@flutter_criteria_update')->name('flutter-criteria-update');
            Route::delete('flutter/criteria/delete/{criteria}', 'BusinessSettingsController@flutter_criteria_destroy')->name('flutter-criteria-delete');
            Route::get('landing-page-settings/{tab?}', 'BusinessSettingsController@landing_page_settings')->name('landing-page-settings.get');
            Route::POST('landing-page-settings/{tab}', 'BusinessSettingsController@update_landing_page_settings')->name('landing-page-settings');
            Route::DELETE('landing-page-settings/{tab}/{key}', 'BusinessSettingsController@delete_landing_page_settings')->name('landing-page-settings-delete');

            Route::get('login-url-setup', 'BusinessSettingsController@login_url_page')->name('login_url_page');
            Route::post('login-url-setup/update', 'BusinessSettingsController@login_url_page_update')->name('login_url_update');

            Route::get('email-setup/{type}/{tab?}', 'BusinessSettingsController@email_index')->name('email-setup.get');
            Route::POST('email-setup/{type}/{tab?}', 'BusinessSettingsController@update_email_index')->name('email-setup');
            Route::get('email-status/{type}/{tab}/{status}', 'BusinessSettingsController@update_email_status')->name('email-status');

            Route::get('toggle-settings/{key}/{value}', 'BusinessSettingsController@toggle_settings')->name('toggle-settings');
            Route::get('site_direction', 'BusinessSettingsController@site_direction')->name('site_direction');


            Route::get('fcm-index', 'BusinessSettingsController@fcm_index')->name('fcm-index');
            Route::get('fcm-config', 'BusinessSettingsController@fcm_config')->name('fcm-config');
            Route::post('update-fcm', 'BusinessSettingsController@update_fcm')->name('update-fcm');

            Route::post('update-fcm-messages', 'BusinessSettingsController@update_fcm_messages')->name('update-fcm-messages');

            Route::get('currency-add', 'BusinessSettingsController@currency_index')->name('currency-add');
            Route::post('currency-add', 'BusinessSettingsController@currency_store');
            Route::get('currency-update/{id}', 'BusinessSettingsController@currency_edit')->name('currency-update');
            Route::put('currency-update/{id}', 'BusinessSettingsController@currency_update');
            Route::delete('currency-delete/{id}', 'BusinessSettingsController@currency_delete')->name('currency-delete');

            Route::get('pages/business-page/faq', 'BusinessSettingsController@faq')->name('faq');
            Route::post('pages/business-page/faq', 'BusinessSettingsController@faq_update')->name('faq.post');

            Route::post('pages/business-page/vendorhub-terms-and-conditions', 'BusinessSettingsController@vendorhub_terms_and_conditions')->name('vendorhub-terms-and-conditions');
            Route::post('pages/business-page/del-terms-and-conditions', 'BusinessSettingsController@del_terms_and_conditions')->name('del-terms-and-conditions');
            Route::get('pages/business-page/terms-and-conditions', 'BusinessSettingsController@terms_and_conditions')->name('terms-and-conditions');
            Route::post('pages/business-page/terms-and-conditions', 'BusinessSettingsController@terms_and_conditions_update');

            Route::get('pages/business-page/privacy-policy', 'BusinessSettingsController@privacy_policy')->name('privacy-policy');
            Route::post('pages/business-page/privacy-policy', 'BusinessSettingsController@privacy_policy_update');

            Route::post('pages/business-page/terms-and-conditions', 'BusinessSettingsController@pages_update')->name('terms-and-conditions');

            Route::get('pages/business-page/about-us', 'BusinessSettingsController@about_us')->name('about-us');
            Route::post('pages/business-page/about-us', 'BusinessSettingsController@about_us_update');

            Route::get('pages/business-page/refund', 'BusinessSettingsController@refund_policy')->name('refund');
            Route::post('pages/business-page/refund', 'BusinessSettingsController@refund_update');
            Route::get('pages/refund-policy/{status}', 'BusinessSettingsController@refund_policy_status')->name('refund-policy-status');

            Route::get('pages/business-page/cancelation', 'BusinessSettingsController@cancellation_policy')->name('cancelation');
            Route::post('pages/business-page/cancelation', 'BusinessSettingsController@cancellation_policy_update');
            Route::get('pages/cancellation-policy/{status}', 'BusinessSettingsController@cancellation_policy_status')->name('cancellation-policy-status');

            Route::get('pages/business-page/disclaimer', 'BusinessSettingsController@disclaimer')->name('disclaimer');
            Route::post('pages/business-page/disclaimer', 'BusinessSettingsController@disclaimer_update');
            Route::get('pages/business-page/shipping-policy', 'BusinessSettingsController@shipping_policy')->name('shipping-policy');
            Route::post('pages/business-page/shipping-policy', 'BusinessSettingsController@shipping_policy_update');
            Route::get('pages/shipping-policy/{status}', 'BusinessSettingsController@shipping_policy_status')->name('shipping-policy-status');
            // Social media
            Route::get('social-media/fetch', 'SocialMediaController@fetch')->name('social-media.fetch');
            Route::get('social-media/status-update', 'SocialMediaController@social_media_status_update')->name('social-media.status-update');
            Route::resource('pages/social-media', 'SocialMediaController');

            Route::group(['prefix' => 'file-manager', 'as' => 'file-manager.'], function () {
                Route::get('/download/{file_name}', 'FileManagerController@download')->name('download');
                Route::get('/index/{folder_path?}', 'FileManagerController@index')->name('index');
                Route::post('/image-upload', 'FileManagerController@upload')->name('image-upload');
                Route::delete('/delete/{file_path}', 'FileManagerController@destroy')->name('destroy');
            });

            Route::group(['prefix' => 'third-party', 'as' => 'third-party.'], function () {
                Route::get('sms-module', 'SMSModuleController@sms_index')->name('sms-module');
                Route::post('sms-module-update/{sms_module}', 'SMSModuleController@sms_update')->name('sms-module-update');
                Route::get('payment-method', 'BusinessSettingsController@payment_index')->name('payment-method');
                // Route::post('payment-method-update/{payment_method}', 'BusinessSettingsController@payment_update')->name('payment-method-update');
                Route::post('payment-method-update', 'BusinessSettingsController@payment_config_update')->name('payment-method-update');
                Route::get('config-setup', 'BusinessSettingsController@config_setup')->name('config-setup');
                Route::post('config-update', 'BusinessSettingsController@config_update')->name('config-update');
                Route::get('mail-config', 'BusinessSettingsController@mail_index')->name('mail-config');
                Route::get('test-mail', 'BusinessSettingsController@test_mail')->name('test');
                Route::post('mail-config', 'BusinessSettingsController@mail_config');
                Route::post('mail-config-status', 'BusinessSettingsController@mail_config_status')->name('mail-config-status');
                Route::get('send-mail', 'BusinessSettingsController@send_mail')->name('mail.send');
                // social media login
                Route::group(['prefix' => 'social-login', 'as' => 'social-login.'], function () {
                    Route::get('view', 'BusinessSettingsController@viewSocialLogin')->name('view');
                    Route::post('update/{service}', 'BusinessSettingsController@updateSocialLogin')->name('update');
                });
                //recaptcha
                Route::get('recaptcha', 'BusinessSettingsController@recaptcha_index')->name('recaptcha_index');
                Route::post('recaptcha-update', 'BusinessSettingsController@recaptcha_update')->name('recaptcha_update');
            });
            // Offline payment Methods
            Route::get('/offline-payment', 'OfflinePaymentMethodController@index')->name('offline');
            Route::get('/offline-payment/new', 'OfflinePaymentMethodController@create')->name('offline.new');
            Route::post('/offline-payment/store', 'OfflinePaymentMethodController@store')->name('offline.store');
            Route::get('/offline-payment/edit/{id}', 'OfflinePaymentMethodController@edit')->name('offline.edit');
            Route::post('/offline-payment/update', 'OfflinePaymentMethodController@update')->name('offline.update');
            Route::post('/offline-payment/delete', 'OfflinePaymentMethodController@delete')->name('offline.delete');
            Route::get('/offline-payment/status/{id}', 'OfflinePaymentMethodController@status')->name('offline.status');



            //db clean
            Route::get('db-index', 'DatabaseSettingController@db_index')->name('db-index');
            Route::post('db-clean', 'DatabaseSettingController@clean_db')->name('clean-db');

            Route::group(['prefix' => 'language', 'as' => 'language.'], function () {
                Route::get('', 'LanguageController@index')->name('index');
                Route::post('add-new', 'LanguageController@store')->name('add-new');
                Route::get('update-status', 'LanguageController@update_status')->name('update-status');
                Route::get('update-default-status', 'LanguageController@update_default_status')->name('update-default-status');
                Route::post('update', 'LanguageController@update')->name('update');
                Route::get('translate/{lang}', 'LanguageController@translate')->name('translate');
                Route::post('translate-submit/{lang}', 'LanguageController@translate_submit')->name('translate-submit');
                Route::post('remove-key/{lang}', 'LanguageController@translate_key_remove')->name('remove-key');
                Route::get('delete/{lang}', 'LanguageController@delete')->name('delete');
                Route::any('auto-translate/{lang}', 'LanguageController@auto_translate')->name('auto-translate');
            });

            Route::get('order-cancel-reasons/status/{id}/{status}', 'OrderCancelReasonController@status')->name('order-cancel-reasons.status');
            Route::get('order-cancel-reasons', 'OrderCancelReasonController@index')->name('order-cancel-reasons.index');
            Route::post('order-cancel-reasons/store', 'OrderCancelReasonController@store')->name('order-cancel-reasons.store');
            Route::put('order-cancel-reasons/update', 'OrderCancelReasonController@update')->name('order-cancel-reasons.update');
            Route::delete('order-cancel-reasons/destroy/{id}', 'OrderCancelReasonController@destroy')->name('order-cancel-reasons.destroy');

            Route::group(['namespace' => 'System', 'prefix' => 'system-addon', 'as' => 'system-addon.', 'middleware' => ['module:user_management']], function () {
                Route::get('/', 'AddonController@index')->name('index');
                Route::post('publish', 'AddonController@publish')->name('publish');
                Route::post('activation', 'AddonController@activation')->name('activation');
                Route::post('upload', 'AddonController@upload')->name('upload');
                Route::post('delete', 'AddonController@delete_theme')->name('delete');
            });
        });

        // Subscribed customer Routes
        Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {



            Route::group(['prefix' => 'wallet', 'as' => 'wallet.', 'middleware' => ['module:customer_wallet']], function () {
                Route::get('add-fund', 'CustomerWalletController@add_fund_view')->name('add-fund');
                Route::post('add-fund', 'CustomerWalletController@add_fund');
                Route::get('report', 'CustomerWalletController@report')->name('report');
            });
            Route::group(['middleware' => ['module:customer_management']], function () {

                // Subscribed customer Routes
                Route::get('subscribed', 'CustomerController@subscribedCustomers')->name('subscribed');
                Route::post('subscriber-search', 'CustomerController@subscriberMailSearch')->name('subscriberMailSearch');
                Route::get('subscriber-search', 'CustomerController@subscribed_customer_export')->name('subscriber-export');

                Route::get('loyalty-point/report', 'LoyaltyPointController@report')->name('loyalty-point.report');
                Route::get('settings', 'CustomerController@settings')->name('settings');
                Route::post('update-settings', 'CustomerController@update_settings')->name('update-settings');
                Route::get('export', 'CustomerController@export')->name('export');
                Route::get('order-export', 'CustomerController@customer_order_export')->name('order-export');
            });
        });
        //Pos system
        Route::group(['prefix' => 'pos', 'as' => 'pos.'], function () {
            Route::post('variant_price', 'POSController@variant_price')->name('variant_price');
            Route::group(['middleware' => ['module:pos']], function () {
                Route::get('/', 'POSController@index')->name('index');
                Route::get('quick-view', 'POSController@quick_view')->name('quick-view');
                Route::post('item-stock-view', 'POSController@item_stock_view')->name('item_stock_view');
                Route::post('item-stock-view-update', 'POSController@item_stock_view_update')->name('item_stock_view_update');
                Route::get('quick-view-cart-item', 'POSController@quick_view_card_item')->name('quick-view-cart-item');
                Route::post('add-to-cart', 'POSController@addToCart')->name('add-to-cart');
                Route::post('remove-from-cart', 'POSController@removeFromCart')->name('remove-from-cart');
                Route::post('cart-items', 'POSController@cart_items')->name('cart_items');
                Route::post('single-items', 'POSController@single_items')->name('single_items');
                Route::post('update-quantity', 'POSController@updateQuantity')->name('updateQuantity');
                Route::post('empty-cart', 'POSController@emptyCart')->name('emptyCart');
                Route::post('tax', 'POSController@update_tax')->name('tax');
                Route::post('discount', 'POSController@update_discount')->name('discount');
                Route::get('customers', 'POSController@get_customers')->name('customers');
                Route::post('order', 'POSController@place_order')->name('order');
                Route::get('invoice/{id}', 'POSController@generate_invoice');
                Route::post('customer-store', 'POSController@customer_store')->name('customer-store');
                Route::post('add-delivery-address', 'POSController@addDeliveryInfo')->name('add-delivery-address');
                Route::get('data', 'POSController@extra_charge')->name('extra_charge');
            });
        });

        Route::group(['prefix' => 'reviews', 'as' => 'reviews.', 'middleware' => ['module:customer_management']], function () {
            Route::get('list', 'ReviewsController@list')->name('list');
            Route::post('search', 'ReviewsController@search')->name('search');
        });

        Route::group(['prefix' => 'report', 'as' => 'report.', 'middleware' => ['module:report']], function () {
            Route::get('order', 'ReportController@order_index')->name('order');
            Route::get('transaction-report', 'ReportController@day_wise_report')->name('transaction-report');
            Route::get('item-wise-report', 'ReportController@item_wise_report')->name('item-wise-report');
            Route::get('item-wise-export', 'ReportController@item_wise_export')->name('item-wise-export');
            Route::post('item-wise-report-search', 'ReportController@item_search')->name('item-wise-report-search');
            Route::post('day-wise-report-search', 'ReportController@day_search')->name('day-wise-report-search');
            Route::get('day-wise-report-export', 'ReportController@day_wise_export')->name('day-wise-report-export');
            Route::get('order-transactions', 'ReportController@order_transaction')->name('order-transaction');
            Route::get('earning', 'ReportController@earning_index')->name('earning');
            Route::post('set-date', 'ReportController@set_date')->name('set-date');
            Route::get('stock-report', 'ReportController@stock_report')->name('stock-report');
            Route::post('stock-report', 'ReportController@stock_search')->name('stock-search');
            Route::get('stock-wise-report-search', 'ReportController@stock_wise_export')->name('stock-wise-report-export');
            Route::get('order-report', 'ReportController@order_report')->name('order-report');
            Route::post('order-report-search', 'ReportController@search_order_report')->name('search_order_report');
            Route::get('order-report-export', 'ReportController@order_report_export')->name('order-report-export');
            Route::get('store-wise-report', 'ReportController@store_summary_report')->name('store-summary-report');
            Route::post('store-summary-report-search', 'ReportController@store_summary_search')->name('store-summary-report-search');
            Route::get('store-summary-report-export', 'ReportController@store_summary_export')->name('store-summary-report-export');
            Route::get('store-wise-sales-report', 'ReportController@store_sales_report')->name('store-sales-report');
            Route::post('store-wise-sales-report-search', 'ReportController@store_sales_search')->name('store-sales-report-search');
            Route::get('store-wise-sales-report-export', 'ReportController@store_sales_export')->name('store-sales-report-export');
            Route::get('store-wise-order-report', 'ReportController@store_order_report')->name('store-order-report');
            Route::post('store-wise-order-report-search', 'ReportController@store_order_search')->name('store-order-report-search');
            Route::get('store-wise-order-report-export', 'ReportController@store_order_export')->name('store-order-report-export');
            Route::get('expense-report', 'ReportController@expense_report')->name('expense-report');
            Route::get('expense-export', 'ReportController@expense_export')->name('expense-export');
            Route::post('expense-report-search', 'ReportController@expense_search')->name('expense-report-search');
            Route::get('generate-statement/{id}', 'ReportController@generate_statement')->name('generate-statement');
        });

        Route::get('customer/select-list', 'CustomerController@get_customers')->name('customer.select-list');


        Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => ['module:customer_management']], function () {
            Route::get('list', 'CustomerController@customer_list')->name('list');

            Route::get('view/{user_id}', 'CustomerController@view')->name('view');
            Route::post('search', 'CustomerController@search')->name('search');
            Route::get('status/{customer}/{status}', 'CustomerController@status')->name('status');
        });


        Route::group(['prefix' => 'file-manager', 'as' => 'file-manager.'], function () {
            Route::get('/download/{file_name}', 'FileManagerController@download')->name('download');
            Route::get('/index/{folder_path?}', 'FileManagerController@index')->name('index');
            Route::post('/image-upload', 'FileManagerController@upload')->name('image-upload');
            Route::delete('/delete/{file_path}', 'FileManagerController@destroy')->name('destroy');
        });

        // social media login
        Route::group(['prefix' => 'social-login', 'as' => 'social-login.', 'middleware' => ['module:business_settings']], function () {
            Route::get('view', 'BusinessSettingsController@viewSocialLogin')->name('view');
            Route::post('update/{service}', 'BusinessSettingsController@updateSocialLogin')->name('update');
        });
        Route::group(['prefix' => 'apple-login', 'as' => 'apple-login.'], function () {
            Route::post('update/{service}', 'BusinessSettingsController@updateAppleLogin')->name('update');
        });
        Route::get('store/report', function () {
            return view('store_report');
        });

        Route::group(['prefix' => 'dispatch', 'as' => 'dispatch.'], function () {
            Route::get('/', 'DashboardController@dispatch_dashboard')->name('dashboard');
            Route::group(['middleware' => ['module:order']], function () {
                Route::get('list/{module?}/{status?}', 'OrderController@dispatch_list')->name('list');
                Route::get('parcel/list/{module?}/{status?}', 'ParcelController@parcel_dispatch_list')->name('parcel.list');
                Route::get('order/details/{id}', 'OrderController@details')->name('order.details');
                Route::get('order/generate-invoice/{id}', 'OrderController@generate_invoice')->name('order.generate-invoice');
            });
        });



        Route::get('users', 'DashboardController@user_dashboard')->name('users.user-dashboard');
        Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
            Route::get('disbursement-export/{id}/{type}', 'DeliveryManController@disbursement_export')->name('disbursement-export');
            Route::get('export', 'DeliveryManController@export')->name('export');


            // ADDONS =============

            Route::group(['prefix' => 'attendance', 'as' => 'attendance.', 'middleware' => ['module:attendance', 'planwise:att_manage']], function () {
                Route::get('list', 'AttendanceController@index')->name('all');
                Route::get('report', 'AttendanceController@report')->name('report');
                Route::get('export', 'AttendanceController@export')->name('export');
                Route::get('add', 'AttendanceController@add')->name('add-new');
                Route::get('status/{id}/{status}', 'AttendanceController@status')->name('status');
                Route::post('save-info', 'AttendanceController@save_info')->name('save-info');
                Route::post('save', 'AttendanceController@save_att')->name('save');

                Route::get('delete/{id}', 'AttendanceController@delete')->name('delete');
                Route::get('manage/{id}', 'AttendanceController@manage')->name('manage');
            });

            Route::group(['prefix' => 'leave', 'as' => 'leave.', 'middleware' => ['planwise:leave_manage', 'module:leave']], function () {
                Route::get('list', 'LeaveController@index')->name('all');
                Route::get('add', 'LeaveController@add')->name('add-new');
                Route::get('status/{id}/{status}', 'LeaveController@status')->name('status');
                Route::post('save-info', 'LeaveController@save_info')->name('save-info');
                Route::post('save', 'LeaveController@save_leave')->name('save');

                Route::get('delete/{id}', 'AttendanceController@delete')->name('delete');
                Route::get('manage/{id}', 'LeaveController@manage')->name('manage');
            });
            Route::group(['prefix' => 'salary', 'as' => 'salary.', 'middleware' => ['planwise:salary_manage', 'module:salary']], function () {
                Route::get('list', 'SalaryController@index')->name('list');
                Route::post('get-info', 'SalaryController@get_info')->name('get-info');

                Route::get('add', 'SalaryController@add')->name('add-new');
                Route::get('status/{id}/{status}', 'SalaryController@status')->name('status');
                Route::post('save-info', 'SalaryController@save_info')->name('save');
                Route::get('delete/{id}', 'SalaryController@delete')->name('delete');
                Route::get('edit/{id}', 'SalaryController@edit')->name('edit');
            });




            // Subscribed customer Routes
            Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
                Route::post('check-addr', 'CustomerController@check_addr')->name('check-addr');
                Route::post('save-pincode', 'CustomerController@save_pincode')->name('save-pincode');

                Route::group(['prefix' => 'wallet', 'as' => 'wallet.', 'middleware' => ['module:customer_management']], function () {
                    Route::get('add-fund', 'CustomerWalletController@add_fund_view')->name('add-fund');
                    Route::post('add-fund', 'CustomerWalletController@add_fund');
                    Route::post('set-date', 'CustomerWalletController@set_date')->name('set-date');
                    Route::get('report', 'CustomerWalletController@report')->name('report');
                    Route::get('export', 'CustomerWalletController@export')->name('export');
                });

                Route::group(['middleware' => ['module:customer_management']], function () {

                    // Subscribed customer Routes
                    Route::get('subscribed', 'CustomerController@subscribedCustomers')->name('subscribed');
                    Route::post('subscriber-search', 'CustomerController@subscriberMailSearch')->name('subscriberMailSearch');
                    Route::get('subscriber-search', 'CustomerController@subscribed_customer_export')->name('subscriber-export');

                    Route::get('loyalty-point/report', 'LoyaltyPointController@report')->name('loyalty-point.report');
                    Route::get('loyalty-point/export', 'LoyaltyPointController@export')->name('loyalty-point.export');
                    Route::post('loyalty-point/set-date', 'LoyaltyPointController@set_date')->name('loyalty-point.set-date');
                    Route::get('settings', 'CustomerController@settings')->name('settings');
                    Route::post('update-settings', 'CustomerController@update_settings')->name('update-settings');
                    Route::get('export', 'CustomerController@export')->name('export');
                    Route::post('upload-excel', 'CustomerController@upload_excel')->name('upload-excel');
                    Route::get('order-export', 'CustomerController@customer_order_export')->name('order-export');
                });
            });
            Route::get('customer/select-list', 'CustomerController@get_customers')->name('customer.select-list');

            Route::group(['prefix' => 'customer', 'as' => 'customer.', 'middleware' => ['module:customer_management']], function () {
                Route::get('list', 'CustomerController@customer_list')->name('list');
                Route::get('add-new', 'CustomerController@add_new')->name('add-new');
                Route::post('save', 'CustomerController@store')->name('save');
                Route::get('cart', 'CustomerController@customer_cart')->name('cart');
                Route::get('view/{user_id}', 'CustomerController@view')->name('view');
                Route::post('search', 'CustomerController@search')->name('search');
                Route::get('status/{customer}/{status}file-manager', 'CustomerController@status')->name('status');
            });
            Route::group(['prefix' => 'contact', 'as' => 'contact.', 'middleware' => ['module:customer_management']], function () {
                Route::get('contact-list', 'ContactController@list')->name('contact-list');
                Route::get('contact-list-export', 'ContactController@exportList')->name('exportList');
                Route::delete('contact-delete/{id}', 'ContactController@destroy')->name('contact-delete');
                Route::get('contact-view/{id}', 'ContactController@view')->name('contact-view');
                Route::post('contact-update/{id}', 'ContactController@update')->name('contact-update');
                Route::post('contact-send-mail/{id}', 'ContactController@send_mail')->name('contact-send-mail');
                Route::post('contact-search', 'ContactController@search')->name('contact-search');
            });
        });
        Route::group(['prefix' => 'transactions', 'as' => 'transactions.'], function () {
            Route::get('/', 'DashboardController@transaction_dashboard')->name('dashboard');
            Route::get('order/details/{id}', 'OrderController@details')->name('order.details');
            Route::get('parcel/order/details/{id}', 'ParcelController@order_details')->name('parcel.order.details');
            Route::get('order/generate-invoice/{id}', 'OrderController@generate_invoice')->name('order.generate-invoice');
            Route::get('customer/view/{user_id}', 'CustomerController@view')->name('customer.view');
            Route::get('item/view/{id}', 'ItemController@view')->name('item.view');
            Route::group(['prefix' => 'report', 'as' => 'report.', 'middleware' => ['module:report']], function () {
                Route::get('order', 'ReportController@order_index')->name('order');
                Route::get('day-wise-report', 'ReportController@day_wise_report')->name('day-wise-report');
                Route::get('item-wise-report', 'ReportController@item_wise_report')->name('item-wise-report');
                Route::get('item-wise-export', 'ReportController@item_wise_export')->name('item-wise-export');
                Route::post('item-wise-report-search', 'ReportController@item_search')->name('item-wise-report-search');
                Route::post('day-wise-report-search', 'ReportController@day_search')->name('day-wise-report-search');
                Route::get('day-wise-report-export', 'ReportController@day_wise_export')->name('day-wise-report-export');
                Route::get('order-transactions', 'ReportController@order_transaction')->name('order-transaction');
                Route::get('earning', 'ReportController@earning_index')->name('earning');
                Route::post('set-date', 'ReportController@set_date')->name('set-date');
                Route::get('stock-report', 'ReportController@stock_report')->name('stock-report');
                Route::post('stock-report', 'ReportController@stock_search')->name('stock-search');
                Route::get('stock-wise-report-search', 'ReportController@stock_wise_export')->name('stock-wise-report-export');
                Route::get('order-report', 'ReportController@order_report')->name('order-report');
                Route::post('order-report-search', 'ReportController@search_order_report')->name('search_order_report');
                Route::get('order-report-export', 'ReportController@order_report_export')->name('order-report-export');
                Route::get('store-wise-report', 'ReportController@store_summary_report')->name('store-summary-report');
                Route::post('store-summary-report-search', 'ReportController@store_summary_search')->name('store-summary-report-search');
                Route::get('store-summary-report-export', 'ReportController@store_summary_export')->name('store-summary-report-export');
                Route::get('store-wise-sales-report', 'ReportController@store_sales_report')->name('store-sales-report');
                Route::post('store-wise-sales-report-search', 'ReportController@store_sales_search')->name('store-sales-report-search');
                Route::get('store-wise-sales-report-export', 'ReportController@store_sales_export')->name('store-sales-report-export');
                Route::get('store-wise-order-report', 'ReportController@store_order_report')->name('store-order-report');
                Route::post('store-wise-order-report-search', 'ReportController@store_order_search')->name('store-order-report-search');
                Route::get('store-wise-order-report-export', 'ReportController@store_order_export')->name('store-order-report-export');
                Route::get('expense-report', 'ReportController@expense_report')->name('expense-report');
                Route::get('expense-export', 'ReportController@expense_export')->name('expense-export');
                Route::post('expense-report-search', 'ReportController@expense_search')->name('expense-report-search');
                Route::get('low-stock-report', 'ReportController@low_stock_report')->name('low-stock-report');
                Route::post('low-stock-report', 'ReportController@low_stock_search')->name('low-stock-search');
                Route::get('low-stock-wise-report-search', 'ReportController@low_stock_wise_export')->name('low-stock-wise-report-export');
                Route::get('disbursement-report/{tab?}', 'ReportController@disbursement_report')->name('disbursement_report');
                Route::get('disbursement-report-export/{type}/{tab?}', 'ReportController@disbursement_report_export')->name('disbursement_report_export');
            });

            Route::group(['prefix' => 'account-transaction', 'as' => 'account-transaction.', 'middleware' => ['module:collect_cash']], function () {
                Route::get('list', 'AccountTransactionController@index')->name('index');
                Route::post('store', 'AccountTransactionController@store')->name('store');
                Route::get('details/{id}', 'AccountTransactionController@show')->name('view');
                Route::delete('delete/{id}', 'AccountTransactionController@distroy')->name('delete');
                Route::post('search', 'EmployeeController@search')->name('search');
                Route::get('export', 'AccountTransactionController@export_account_transaction')->name('export');
                Route::post('search', 'AccountTransactionController@search_account_transaction')->name('search');
            });

            Route::resource('provide-deliveryman-earnings', 'ProvideDMEarningController')->middleware('module:provide_dm_earning');
            Route::get('export-deliveryman-earnings', 'ProvideDMEarningController@dm_earning_list_export')->name('export-deliveryman-earning');
            Route::post('deliveryman-earnings-search', 'ProvideDMEarningController@search_deliveryman_earning')->name('search-deliveryman-earning');

            Route::group(['prefix' => 'store', 'as' => 'store.'], function () {
                Route::post('assign-manual-trial', 'VendorController@assignManualTrial')->name('assignManualTrial');
                Route::get('view/{store}/{tab?}/{sub_tab?}', 'VendorController@view')->name('view');
                Route::post('status-filter', 'VendorController@status_filter')->name('status-filter');
                Route::post('withdraw-status/{id}', 'VendorController@withdrawStatus')->name('withdraw_status');
                Route::get('withdraw_list', 'VendorController@withdraw')->name('withdraw_list');
                Route::post('withdraw_search', 'VendorController@withdraw_search')->name('withdraw_search');
                Route::get('withdraw_export', 'VendorController@withdraw_export')->name('withdraw_export');
                Route::get('withdraw-view/{withdraw_id}/{seller_id}', 'VendorController@withdraw_view')->name('withdraw_view');
                Route::get('get-Withdraw-Details', 'VendorController@getWithdrawDetails')->name('getWithdrawDetails');
            });

            Route::group(['prefix' => 'withdraw-method', 'as' => 'withdraw-method.'], function () {
                Route::get('list', 'WithdrawalMethodController@list')->name('list');
                Route::get('create', 'WithdrawalMethodController@create')->name('create');
                Route::post('store', 'WithdrawalMethodController@store')->name('store');
                Route::get('edit/{id}', 'WithdrawalMethodController@edit')->name('edit');
                Route::put('update', 'WithdrawalMethodController@update')->name('update');
                Route::delete('delete/{id}', 'WithdrawalMethodController@delete')->name('delete');
                Route::post('status-update', 'WithdrawalMethodController@status_update')->name('status-update');
                Route::post('default-status-update', 'WithdrawalMethodController@default_status_update')->name('default-status-update');
                Route::get('get-method-info', 'WithdrawalMethodController@getMethodInfo')->name('getMethodInfo');
            });

            Route::group(['prefix' => 'store-disbursement', 'as' => 'store-disbursement.', 'middleware' => ['module:account']], function () {
                Route::get('list', 'StoreDisbursementController@list')->name('list');
                Route::get('details/{id}', 'StoreDisbursementController@view')->name('view');
                Route::get('status', 'StoreDisbursementController@status')->name('status');
                Route::get('change-status/{id}/{status}', 'StoreDisbursementController@statusById')->name('change-status');
                Route::get('export/{id}/{type?}', 'StoreDisbursementController@export')->name('export');
            });
            Route::group(['prefix' => 'dm-disbursement', 'as' => 'dm-disbursement.', 'middleware' => ['module:account']], function () {
                Route::get('list', 'DeliveryManDisbursementController@list')->name('list');
                Route::get('details/{id}', 'DeliveryManDisbursementController@view')->name('view');
                Route::get('export/{id}/{type?}', 'DeliveryManDisbursementController@export')->name('export');
                Route::get('status', 'DeliveryManDisbursementController@status')->name('status');
                Route::get('change-status/{id}/{status}', 'DeliveryManDisbursementController@statusById')->name('change-status');
                Route::get('export/{id}/{type?}', 'DeliveryManDisbursementController@export')->name('export');
            });
        });
    });
});
