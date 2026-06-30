<?php

use App\Http\Controllers\Front\ModuleInfoController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Vendor\TaskSalaryCategoryController;
use Illuminate\Support\Facades\Route;
// use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;
// use App\WebSockets\Handler\ServiceReqSocketHandler;
Route::get('mc-module/{module}', [ModuleInfoController::class, 'module_info'])->name('mc-module');


Route::group(['namespace' => 'Vendor', 'as' => 'vendor.'], function () {

    // mc vendorhub routes  
    Route::group(['prefix' => '', 'as' => 'mc-vendor.'], function () {
        Route::get('/', 'MCVendorController@index')->name('home');
        Route::get('mc-module/{module}', 'MCVendorController@module_info')->name('mc-module');
        Route::get('blog-mc-vendor-hub', 'MCVendorController@blog_mc_vendor')->name('blog-mc-vendor-hub');
        Route::get('blog-mc-vendor-hub/category/{slug}', 'MCVendorController@blog_mc_vendor_category')->name('blog-mc-vendor-hub.category');
        Route::get('blog-mc-vendor-hub/{slug}', 'MCVendorController@blog_mc_vendor_post')->name('blog-mc-vendor-hub.post');
        Route::get('tnc', 'MCVendorController@mc_vendor_hub_tnc')->name('mc-vendor-hub-tnc');
        Route::get('privacy-policy', 'MCVendorController@mc_vendor_hub_pp')->name('mc-vendor-hub-pp');
        Route::get('return-policy', 'MCVendorController@mc_vendor_hub_return_policy')->name('mc-vendor-hub-return-policy');
        Route::get('contact', 'MCVendorController@contact')->name('contact');
        Route::post('send-message', 'MCVendorController@send_message')->name('send-message');
        // Route::post('send-vendor-otp', 'MCVendorController@send_vendor_otp')->name('send-vendor-otp');
        Route::post('request-subscription-plan', 'MCVendorController@request_subscription_plan')->name('request-subscription-plan');
        Route::get('price-calculator', 'MCVendorController@price_calculator')->name('price-calculator');
    });

    // Impersonation — no auth middleware, token is the auth
    Route::get('impersonate', 'ImpersonateController@start')->name('impersonate.start');
    Route::post('impersonate/stop', 'ImpersonateController@stop')->name('impersonate.stop');

    Route::group(['middleware' => ['vendor', 'module.resolve', 'module.resolve.views']], function () {
        Route::middleware('throttle:60,1')->get('last-notification', 'DashboardController@lastNotification')->name('last-notification');
        Route::post('mark-inactive-read', 'DashboardController@markInactiveRead')->name('mark-inactive-read');
        Route::post('request-subscription-plan', 'DashboardController@request_subscription_plan')->name('request-subscription-plan');

        // WhatsApp Embedded Signup (vendor connects their own number — DoubleTick-style).
        Route::get('whatsapp/connect', 'WhatsAppController@connect')->name('whatsapp.connect');
        Route::post('whatsapp/connect/finish', 'WhatsAppController@finish')->name('whatsapp.connect.finish');
        Route::post('whatsapp/disconnect', 'WhatsAppController@disconnect')->name('whatsapp.disconnect');
        Route::get('whatsapp/templates', 'WhatsAppController@templates')->name('whatsapp.templates');
        Route::post('whatsapp/templates/create', 'WhatsAppController@templateCreate')->name('whatsapp.templates.create');
        Route::post('whatsapp/templates/update', 'WhatsAppController@templateUpdate')->name('whatsapp.templates.update');
        Route::post('whatsapp/templates/delete', 'WhatsAppController@templateDelete')->name('whatsapp.templates.delete');
        Route::post('whatsapp/features/subscribe', 'WhatsAppController@featureSubscribe')->name('whatsapp.features.subscribe');
        Route::post('whatsapp/features/toggle', 'WhatsAppController@featureToggle')->name('whatsapp.features.toggle');

        Route::get('terms-and-conditions', 'DashboardController@view_terms_and_conditions')->name('terms-and-conditions.view');
        Route::get('notifications', 'DashboardController@notifications')->name('notifications')->middleware('module:notifications');
        Route::get('/clock-in', 'VendorEmployeeController@clock_in')->name('clockin');
        Route::get('/clock-out', 'VendorEmployeeController@clock_out')->name('clockout');
        Route::get('/attendance', 'VendorEmployeeController@attendance')->name('employee-attendance');
        Route::get('/salary-history', 'VendorEmployeeController@my_salary_history')->name('salary-history');
        Route::get('/leaves', 'VendorEmployeeController@leaves')->name('employee-leave');
        Route::post('/leave-save', 'VendorEmployeeController@leave_save')->name('leave-save');
        Route::get('/approve-leave/{id}', 'VendorEmployeeController@leave_approve')->name('approve-leave');
        Route::get('/reject-leave/{id}', 'VendorEmployeeController@leave_reject')->name('reject-leave');

        Route::get('lang/{locale}', 'LanguageController@lang')->name('lang');
        Route::get('/master-dashboard', 'DashboardController@master_dashboard')->name('master-dashboard');
        Route::get('/dashboard', 'DashboardController@dashboard')->name('dashboard');
        // Route::get('/', 'DashboardController@dashboard')->name('dashboard'); 
        Route::get('/get-store-data', 'DashboardController@store_data')->name('get-store-data');
        Route::post('/store-token', 'DashboardController@updateDeviceToken')->name('store.token');
        Route::get('/reviews', 'ReviewController@index')->name('reviews')->middleware('module:reviews');
        Route::post('submit-reply', 'ReviewController@submit_reply')->name('submit-reply');
        Route::get('site_direction', 'BusinessSettingsController@site_direction_vendor')->name('site_direction');

        Route::group(['prefix' => 'performance-analytics', 'as' => 'performance-analytics.'], function () {
            Route::get('/', 'AnalyticsController@index')->name('index');
            Route::get('chart-data', 'AnalyticsController@chartData')->name('chart-data');
            Route::post('claim-trial', 'AnalyticsController@claimTrial')->name('claim-trial');
        });

        Route::group(['prefix' => 'notification', 'as' => 'notification.', 'middleware' => ['module:notifications']], function () {
            Route::get('/', 'NotificationController@index')->name('add-new');
            Route::post('store', 'NotificationController@store')->name('store');
            Route::post('schedule', 'NotificationController@storeScheduled')->name('schedule');
            Route::get('edit/{id}', 'NotificationController@edit')->name('edit');
            Route::post('update/{id}', 'NotificationController@update')->name('update');
            Route::post('reschedule/{id}', 'NotificationController@reschedule')->name('reschedule');
            Route::delete('/delete{id}', 'NotificationController@delete')->name('delete');
            Route::get('status/{id}/{status}', 'NotificationController@updateStatus')->name('status');
            Route::get('export', 'NotificationController@exportList')->name('export');
        });
        Route::group(['prefix' => 'task', 'as' => 'task.'], function () {
            Route::get('assigned-tasks', 'TaskController@assigned_tasks')->name('assigned_tasks');
            Route::post('otp-send', 'TaskController@task_otp_send')->name('job-otp-verify');
            Route::get('accept/{id}', 'TaskController@accept')->name('accept');
            Route::get('reject/{id}', 'TaskController@reject')->name('reject');
        });
        Route::get('my-performance', 'ReportController@staff_combined_report')->name('report.staff-combined');
        Route::group(['prefix' => 'form-builder', 'as' => 'form-builder.'], function () {

            Route::post('save-form', 'FormBuilderController@saveFields')->name('save-form');
            Route::get('get-form/{form_type?}', 'FormBuilderController@getForm')->name('get-form');
        });

        Route::group(['prefix' => 'task', 'as' => 'task.', 'middleware' => ['planwise:task_manage']], function () { // add middleware
            Route::get('add/{project_id?}', 'TaskController@add')->name('add')->middleware('permission:task,add');
            Route::post('store', 'TaskController@store')->name('store')->middleware('permission:task,add');
            Route::get('edit/{id}', 'TaskController@edit')->name('edit')->middleware('permission:task,edit');
            Route::post('update', 'TaskController@update')->name('update')->middleware('permission:task,edit');
            Route::post('reassign', 'TaskController@reassign')->name('reassign')->middleware('permission:task,edit');
            Route::get('list/{id?}', 'TaskController@list')->name('list');
            Route::get('detail/{id}', 'TaskController@detail')->name('detail')->middleware('permission:task,view');
            Route::post('delete/{id}', 'TaskController@delete')->name('delete')->middleware('permission:task,delete');
            Route::post('save-progress', 'TaskController@save_progress')->name('save-progress');
            Route::post('status-update', 'TaskController@status_update')->name('status.update')->middleware('permission:task,status_change');
            Route::post('status-new', 'TaskController@status_new_save')->name('status.save-new');
            Route::get('export', 'TaskController@export')->name('export')->middleware('permission:task,export');
            Route::get('setting', 'TaskController@setting')->name('setting')->middleware('permission:task,settings');

            // Route::get('workflow-form', 'TaskController@workflow_form')->name('setting.workflow-form');
            Route::post('setting-update', 'TaskController@setting_update')->name('setting.update')->middleware('permission:task,settings');
            Route::group(['prefix' => 'comment', 'as' => 'comment.'], function () {
                Route::post('add', 'TaskController@comment_add')->name('add')->middleware('permission:task_update,add');
                Route::get('delete/{id}', 'TaskController@comment_delete')->name('delete')->middleware('permission:task_update,delete');
                Route::get('edit/{id}', 'TaskController@comment_edit')->name('edit')->middleware('permission:task_update,edit');
                Route::post('update', 'TaskController@comment_update')->name('update')->middleware('permission:task_update,edit');;
                Route::get('pic-delete/{comment_id}/{name}', 'TaskController@pic_delete')->name('pic-delete');
            });
            Route::group(['prefix' => 'subtask-update', 'as' => 'subtask-update.'], function () {
                Route::post('add', 'TaskController@comment_add')->name('add')->middleware('permission:subtask_update,add');;
                Route::get('delete/{id}', 'TaskController@comment_delete')->name('delete')->middleware('permission:subtask_update,delete');;
                Route::get('edit/{id}', 'TaskController@comment_edit')->name('edit')->middleware('permission:subtask_update,edit');
                Route::post('update', 'TaskController@comment_update')->name('update')->middleware('permission:subtask_update,edit');;
                Route::get('pic-delete/{comment_id}/{name}', 'TaskController@pic_delete')->name('pic-delete');
            });
            Route::group(['prefix' => 'subtask', 'as' => 'subtask.'], function () {
                Route::post('add', 'TaskController@subtask_add')->name('add')->middleware('permission:subtask,add');;
                Route::get('tasks/{id?}', 'TaskController@getTasks')->name('getTasks');
                Route::get('delete/{id}', 'TaskController@delete_subtask')->name('delete')->middleware('permission:subtask,delete');;
                Route::get('update-level', 'TaskController@update_level')->name('update-level');

                Route::get('edit/{id}', 'TaskController@edit')->name('edit')->middleware('permission:subtask,edit'); //done
                Route::post('update', 'TaskController@update')->name('update')->middleware('permission:subtask,edit');
                Route::get('detail/{id}', 'TaskController@detail')->name('detail')->middleware('permission:subtask,view');
                Route::post('status-update', 'TaskController@status_update')->name('status.update')->middleware('permission:subtask,status_change');
            });
        });

        Route::get('task/add/{id}', 'TaskController@add')->name('task.add-lead'); // for project details page without planwise:task_manage and permission:task,add check
        Route::post('applyCoupon', 'ServiceController@applyCoupon')->name('applyCoupon');
        Route::post('verifyCoupon', 'ServiceController@verifyCoupon')->name('verifyCoupon');

        Route::group(['prefix' => 'billing', 'as' => 'invoice.',], function () {
            Route::get('manual-bill', 'ServiceController@manual_bill')->name('manual-bill')->middleware('permission:billing,add_basic');
            Route::get('settings', 'SettingsController@invoice_settings')->name('settings');
            Route::post('update-serial', 'SettingsController@update_serial_number')->name('update-serial');
            Route::post('save-invoice-template', 'SettingsController@save_invoice_template')->name('save-invoice-template');
            Route::post('get-invoices-by-vendor', 'BillingController@get_invoices_by_vendor')->name('get-invoices-by-vendor')->middleware('permission:billing,list'); // only for manual invoices
            Route::get('veiw-invoice/{invoice_id}', 'BillingController@view_invoice')->name('view-invoice')->middleware('permission:billing,view'); // only for manual invoices
            Route::get('create-invoice', 'BillingController@create_invoice')->name('create-invoice')->middleware('permission:billing,add_advanced'); // only for manual invoices
            Route::post('validate-invoicenum', 'BillingController@validate_invoicenum')->name('validate-invoicenum'); // only for manual invoices
            Route::get('get-invoice-id-for-date', 'BillingController@getInvoiceIdForDate')->name('get-invoice-id-for-date');

            Route::get('edit/{id}', 'BillingController@edit')->name('edit')->middleware('permission:billing,edit'); // only for manual invoices
            Route::get('purchase-bills', 'AccountController@my_bills')->name('my-bills')->middleware('permission:purchase_bill,list');; //ddd
            Route::get('purchase-bill/delete/{id}', 'BillingController@purchase_bill_delete')->name('purchase-bill.delete')->middleware('permission:purchase_bill,delete');; //ddd


            Route::post('update-invoice', 'BillingController@update_invoice')->name('update-invoice')->middleware('permission:billing,edit');
            Route::get('reminder/{type}/{status}/{id}', 'ServiceController@reminder_status')->name('reminder')->middleware('permission:billing,reminder_update');
            Route::post('save-manual-invoice', 'ServiceController@save_manual_invoice')->name('save-manual-invoice')->middleware('permission:billing,add_basic');
            Route::post('save-new-manual-invoice', 'BillingController@save_new_manual_invoice')->name('save-new-manual-invoice')->middleware('permission:billing,add_advanced');
            Route::post('delete-row', 'ServiceController@delete_row')->name('delete-row');
            Route::get('invoice-view/{type}/{id}', 'ServiceController@invoice_view')->name('invoice-view')->middleware('permission:billing,view');
            Route::get('view-invoice/{type}/{invoice_id}', 'ServiceController@manual_invoice_view')->name('manual-invoice-view')->middleware('permission:billing,view');
            Route::get('list', 'ServiceController@invoice_list')->name('list')->middleware('permission:billing,list');
            Route::get('mark-paid/{type}/{id}', 'ServiceController@mark_paid')->name('mark-paid')->middleware('permission:billing,mark_paid');
            Route::post('mark-paid2', 'ServiceController@mark_paid2')->name('mark-paid2')->middleware('permission:billing,mark_paid');
            Route::get('pay-bill/{invoice_id}', 'BillingController@pay_bill')->name('pay-bill')->middleware('permission:billing,pay');
            Route::get('make-payment/{invoice_id}', 'BillingController@make_payment')->name('make-payment')->middleware('permission:billing,pay');
            Route::get('delete/{type}/{invoice_id}', 'BillingController@delete')->name('delete')->middleware('permission:billing,delete');
            Route::post('import-sheet', 'BillingController@importInvoiceSheet')->name('import-sheet')->middleware('permission:billing,import');
            Route::post('export', 'BillingController@exportInvoiceSheet')->name('export')->middleware('permission:billing,export');
            Route::group(['prefix' => 'purchase-invoice', 'as' => 'purchase-invoice.'], function () {
                Route::post('save', 'BillingController@save_purchase_invoice')->name('save')->middleware('permission:purchase_bill,add');
                Route::post('import', 'BillingController@importPurchaseInvoices')->name('import')->middleware('permission:purchase_bill,import');
            });

            //SERVICE BILLS 
            Route::post('service-update-invoice', 'BillingController@service_update_invoice')->name('service-update-invoice')->middleware('permission:service_bill,edt');
            Route::post('save-invoice', 'ServiceController@save_invoice')->name('save-invoice')->middleware('permission:service_bill,add');
            Route::get('edit-service-invoice/{id}', 'BillingController@edit_service_invoice')->name('edit.service.invoice')->middleware('permission:service_bill,edit'); // only for manual invoices
        });
        Route::post('billing/save-manual-invoice/{id}', 'ServiceController@save_manual_invoice')->name('invoice.save-manual-invoice-lead'); // for task invoice save without permission check because it is used in task details page and we have given access to view task details for some roles who don't have permission to manage billing
        Route::get('invoices', 'DashboardController@invoices')->name('invoices');
        Route::get('invoice-list', 'DashboardController@order_invoices')->name('order.invoices');


        // addons 
        Route::group(['prefix' => 'attendance', 'as' => 'attendance.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('report', 'AttendanceController@report')->name('report');
            Route::get('export', 'AttendanceController@export')->name('export')->middleware('permission:attendance_report,export,attendance_manage,export');
            Route::get('list', 'AttendanceController@index')->name('all')->middleware('permission:attendance_manage,list');
            Route::post('save', 'AttendanceController@save_att')->name('save')->middleware('permission:attendance_manage,edit');
            Route::get('manage/{id}', 'AttendanceController@manage')->name('manage')->middleware('permission:attendance_manage,view');
            // Route::get('add', 'AttendanceController@add')->name('add-new');
            // Route::get('status/{id}/{status}', 'AttendanceController@status')->name('status');
            // Route::post('save-info', 'AttendanceController@save_info')->name('save-info');
            // Route::get('delete/{id}', 'AttendanceController@delete')->name('delete');

        });
        Route::group(['prefix' => 'leave', 'as' => 'leave.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('list', 'LeaveController@index')->name('all');
            Route::get('add', 'LeaveController@add')->name('add-new')->middleware('permission:leave_manage,add,attendance_manage,add');
            Route::get('status/{id}/{status}', 'LeaveController@status')->name('status')->middleware('permission:leave_manage,status_change,attendance_manage,status_change');
            Route::post('save-info', 'LeaveController@save_info')->name('save-info')->middleware('permission:leave_manage,add,attendance_manage,add');
            Route::post('save', 'LeaveController@save_leave')->name('save')->middleware('permission:leave_manage,add,attendance_manage,add');
            Route::get('manage/{id}', 'LeaveController@manage')->name('manage');
        });
        Route::group(['prefix' => 'salary', 'as' => 'salary.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('generate-monthly/{month}', 'SalaryController@generate_monthly')->name('generate-monthly')->middleware('permission:salary_manage,generate');
            Route::get('mark-paid/{month}', 'SalaryController@mark_paid')->name('mark-paid')->middleware('permission:salary_manage,mark_paid');
            Route::get('report', 'SalaryController@report')->name('report');
            Route::get('export-salaries', 'SalaryController@export_salaries')->name('export-salaries')->middleware('permission:salary_manage,export');
            Route::get('list', 'SalaryController@index')->name('list');
            Route::get('export', 'SalaryController@export')->name('export')->middleware('permission:salary_manage,export');
            Route::post('get-info', 'SalaryController@get_info')->name('get-info');
            Route::post('salary-history', 'SalaryController@my_salary_history')->name('salary-history');

            Route::post('pay', 'SalaryController@pay')->name('pay')->middleware('permission:salary_manage,mark_paid');
            Route::get('add', 'SalaryController@add')->name('add-new')->middleware('permission:salary_manage,add');
            Route::get('status/{id}/{status}', 'SalaryController@status')->name('status')->middleware('permission:salary_manage,status_change');
            Route::post('save-info', 'SalaryController@save_info')->name('save')->middleware('permission:salary_manage,edit');
            Route::get('delete/{id}', 'SalaryController@delete')->name('delete')->middleware('permission:salary_manage,delete');
            Route::get('edit/{id}', 'SalaryController@edit')->name('edit')->middleware('permission:salary_manage,edit');

            Route::get('all-advance-requests', 'SalaryController@all_advance_requests')->name('all-advance-requests')->middleware('permission:advance_requests,list');
            Route::get('approve-advance/{id}', 'SalaryController@approve_advance_payment')->name('approve-advance')->middleware('permission:advance_requests,approve');
            Route::get('reject-advance/{id}', 'SalaryController@reject_advance_payment')->name('reject-advance')->middleware('permission:advance_requests,reject');
        });
        Route::get('advance-payment', 'SalaryController@advance_payment')->name('advance-payment');

        Route::post('salary/advance-request/store', 'SalaryController@advance_request_store')->name('salary.advance-request.store');


        Route::post('inventory/get-item-info', 'InventoryController@get_item_info')->name('inventory.get-item-info');
        Route::group(['prefix' => 'inventory', 'as' => 'inventory.', 'middleware' => ['planwise:inventory_manage', 'retail.inventory.basic']], function () {
            Route::get('settings', 'InventoryController@settings')->name('settings')->middleware('permission:inventory,settings');
            Route::post('settings-save', 'InventoryController@settings_save')->name('settings-save')->middleware('permission:inventory,settings_save');

            // Label format designer (drag/drop) — multiple formats per store, one default.
            Route::get('label-formats',                'InventoryController@labelFormats')->name('label-formats')->middleware('permission:inventory,settings');
            Route::post('label-formats/save',          'InventoryController@labelFormatSave')->name('label-formats.save')->middleware('permission:inventory,settings');
            Route::post('label-formats/{id}/default',  'InventoryController@labelFormatDefault')->name('label-formats.default')->middleware('permission:inventory,settings');
            Route::post('label-formats/{id}/delete',   'InventoryController@labelFormatDelete')->name('label-formats.delete')->middleware('permission:inventory,settings');
            Route::get('dashboard', 'InventoryController@dashboard')->name('dashboard')->middleware('permission:inventory,dashboard');
            Route::get('/', 'InventoryController@inventory_management')->name('index');
            Route::post('get_my_fee_amount', 'InventoryController@get_my_fee_amount')->name('get_my_fee_amount');
            Route::post('get_item_details', 'InventoryController@get_item_details')->name('get_item_details');
            Route::post('get_variation_details', 'InventoryController@get_variation_details')->name('get_variation_details');
            Route::get('entries', 'InventoryController@entries')->name('entries');
            Route::post('save-item', 'InventoryController@save_item')->name('item.save')->middleware('permission:inventory_item,add');
            Route::post('update-item', 'InventoryController@update_item')->name('item.update')->middleware('permission:inventory_item,edit');;
            Route::get('edit-item/{id}', 'InventoryController@edit_item')->name('edit-item')->middleware('permission:inventory_item,edit');;
            Route::post('variation-store', 'InventoryController@variation_store')->name('item.variation-store');
            Route::post('save-entry', 'InventoryController@save_entry')->name('entry.save')->middleware('permission:inventory_item_entry,add');;
            Route::post('save-entry-pdf', 'InventoryController@save_entry_pdf')->name('entry.save-pdf');
            Route::get('item-images', 'InventoryController@item_images')->name('item-images');
            Route::post('item-images-store', 'InventoryController@item_images_store')->name('item-images-store');
            Route::post('import', 'InventoryController@items_import')->name('import')->middleware('permission:inventory_item,import');
            Route::get('storage-spaces', 'InventoryController@storage_spaces')->name('storage-spaces');
            Route::group(['prefix' => 'entry', 'as' => 'entry.'], function () {
                Route::get('export-selected', 'InventoryController@entry_export_selected')->name('export-selected')->middleware('permission:inventory_item_entry,export');
                Route::post('bulk-delete', 'InventoryController@entry_bulk_delete')->name('bulk-delete')->middleware('permission:inventory_item_entry,delete');
                Route::post('import', 'InventoryController@entry_import')->name('import')->middleware('permission:inventory_item_entry,import');
                Route::get('export', 'InventoryController@entry_export')->name('export')->middleware('permission:inventory_item_entry,export');
            });

            Route::group(['prefix' => 'item', 'as' => 'item.'], function () {
                Route::post('assign-storage-unit', 'InventoryController@assign_storage_unit')->name('assign-storage-unit')->middleware('permission:inventory_item,edit');
                Route::get('scan-barcode', 'InventoryController@scan_barcode')->name('scan-barcode');
                Route::post('fetch-by-sku', 'InventoryController@fetch_item_by_sku')->name('fetch-by-sku');
                Route::post('update-variant-combination', 'InventoryController@update_variant_combination')->name('update-variant-combination');
                Route::post('quick-price', 'InventoryController@quick_update_price')->name('quick-price')->middleware('permission:inventory_item,edit');
                Route::post('variation-prices', 'InventoryController@update_variation_prices')->name('variation-prices')->middleware('permission:inventory_item,edit');
                Route::get('remove-image/{item_id}/{photo}', 'InventoryController@remove_item_image')->name('remove-image')->middleware('permission:inventory_item,edit');
                Route::get('show_on_website/{id}/{status}', 'InventoryController@show_on_website')->name('show_on_website')->middleware('permission:inventory_item,show_on_website');
                Route::get('delete/{id}', 'InventoryController@item_delete')->name('delete')->middleware('permission:inventory_item,delete');
                Route::post('bulk-delete', 'InventoryController@item_bulk_delete')->name('bulk-delete')->middleware('permission:inventory_item,delete');
                Route::get('export', 'InventoryController@item_export')->name('export')->middleware('permission:inventory_item,export');
                Route::get('export-selected', 'InventoryController@item_export_selected')->name('export-selected')->middleware('permission:inventory_item,export');
                Route::get('detail/{id}', 'InventoryController@item_detail')->name('detail')->middleware('permission:inventory_item,view');
                Route::post('variant-combination', 'InventoryController@variant_combination')->name('variant-combination');
                Route::get('print/{item_id}/{type}', 'InventoryController@print')->name('print');
            });
            Route::group(['prefix' => 'storage-unit', 'as' => 'storage-unit.'], function () {
                Route::get('get-stands', 'InventoryController@get_stands')->name('get-stands');
                Route::post('store', 'InventoryController@storage_spaces_store')->name('store')->middleware('permission:inventory_storage_units,add');
                Route::post('update', 'InventoryController@storage_spaces_update')->name('update')->middleware('permission:inventory_storage_units,edit');
                Route::post('delete/{id}', 'InventoryController@storage_spaces_delete')->name('delete')->middleware('permission:inventory_storage_units,delete');
            });
            Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
                Route::get('{tab}', 'InventoryGatepassController@gatepass_list')->name('list');
                Route::post('store', 'InventoryPurchaseController@store')->name('store');
                Route::get('return/{id}', 'InventoryGatepassController@return')->name('return');
                Route::post('return-store', 'InventoryGatepassController@return_store')->name('return-store');
            });
            Route::group(['prefix' => 'purchase', 'as' => 'purchase.'], function () {
                Route::post('items-in-invoice', 'InventoryPurchaseController@items_in_invoice')->name('items-in-invoice')->middleware('permission:inventory_purchase_return,add');;
                Route::get('orders', 'InventoryPurchaseController@purchase_orders')->name('orders')->middleware('permission:inventory_purchase_order,list');;
                Route::get('export_orders', 'InventoryPurchaseController@export_purchase_orders')->name('export-orders')->middleware('permission:inventory_purchase_order,export');;
                Route::post('order-place', 'InventoryPurchaseController@order_place')->name('order-place')->middleware('permission:inventory_purchase_order,add');;
                Route::get('return', 'InventoryPurchaseController@return')->name('return')->middleware('permission:inventory_purchase_return,add');
                Route::post('return-store', 'InventoryPurchaseController@return_store')->name('return-store')->middleware('permission:inventory_purchase_return,add');
                Route::post('order-delete/{id}', 'InventoryPurchaseController@purchase_order_delete')->name('order-delete');
                Route::post('return-delete/{id}', 'InventoryPurchaseController@purchase_return_delete')->name('return-delete');
            });
            Route::group(['prefix' => 'stock', 'as' => 'stock.'], function () {
                Route::get('stock-in-out', 'InventoryStockController@stock_in_out')->name('stock-in-out')->middleware('permission:inventory_stock_in_out,list');;
            });
            Route::group(['prefix' => 'sale', 'as' => 'sale.'], function () {
                Route::get('orders', 'InventoryOrderController@sale_orders')->name('orders');;
                Route::get('order-status/{id}/{status}', 'InventoryOrderController@sale_order_status')->name('order-status')->middleware('permission:inventory_sale_order,status_change');
                Route::get('order-export/{return?}', 'InventoryOrderController@sale_order_export')->name('order-export');
                Route::get('orders-return', 'InventoryOrderController@order_return')->name('orders-return')->middleware('permission:inventory_sale_return,add');
                Route::post('order-details-fetch', 'InventoryOrderController@order_details_fetch')->name('order-details-fetch');
                Route::post('order-delete/{id}', 'InventoryOrderController@sale_order_delete')->name('order-delete');
                Route::post('return-delete/{id}', 'InventoryOrderController@sale_return_delete')->name('return-delete');
            });
            Route::group(['prefix' => 'report', 'as' => 'report.'], function () {
                Route::get('gst/{export?}/{file_type?}', 'InventoryReportController@gst')->name('gst');
                Route::get('sale/{export?}/{file_type?}', 'InventoryReportController@sale')->name('sale');
                Route::get('profit-and-loss/{export?}/{file_type?}', 'InventoryReportController@profit_and_loss')->name('profit-and-loss');
                Route::get('purchase/{export?}/{file_type?}', 'InventoryReportController@purchase')->name('purchase');
                Route::get('stock/{export?}/{file_type?}', 'InventoryReportController@stock')->name('stock');
                Route::get('batch-expiry', 'InventoryReportController@batchExpiry')->name('batch-expiry');
                Route::post('sale-delete/{id}', 'InventoryReportController@sale_delete')->name('sale-delete');
                Route::post('purchase-delete/{id}', 'InventoryReportController@purchase_delete')->name('purchase-delete');
            });
            Route::group(['prefix' => 'category', 'as' => 'category.'], function () {
                Route::get('/', 'InventoryController@category')->name('index');
                Route::post('store', 'InventoryController@storage_spaces_store')->name('store');
                Route::post('update', 'InventoryController@storage_spaces_update')->name('update');
                Route::post('delete/{id}', 'InventoryController@storage_spaces_delete')->name('delete');
            });
        });

        Route::group(['prefix' => 'jobcard', 'as' => 'jobcard.'], function () {
            Route::post('view', 'LibraryController@job_card')->name('view'); // task, view/generate , id
        });
        Route::group(['prefix' => 'sub-module', 'as' => 'sub-module.'], function () {
            Route::get('list/{module?}', 'SubmoduleController@list')->name('list');
            Route::get('enable/{submodule}', 'SubmoduleController@enable')->name('enable');
        });
        Route::get('module/{module_name}', 'SubmoduleController@show_offer')->name('module-offer');

        //  ============================= ACCOUNT MANAGEMENT =================================
        Route::group(['prefix' => 'account', 'as' => 'account.', 'middleware' => ['planwise:account_manage']], function () {
            Route::post('reset_accounts_module', 'AccountController@reset_accounts_module')->name('reset_accounts_module')->middleware('permission:settings_common,reset');
            Route::post('send_otp', 'AccountController@send_otp')->name('send_otp');
            Route::post('fetchEmployees', 'AccountController@fetchEmployees')->name('fetchEmployees');

            Route::group(['prefix' => 'monthly-finance', 'as' => 'monthly-finance.'], function () {
                Route::get('property-valuation', 'MonthlyFinanceController@property_valuation')->name('property-valuation')->middleware('permission:rmf_maintenance_requests,list');
                Route::get('monthly-maintanance', 'MonthlyFinanceController@monthly_maintanance')->name('monthly-maintanance')->middleware('permission:rmf_property_valuation,list');
            });

            // STATEMENTS
            Route::group(['prefix' => 'statement', 'as' => 'statement.'], function () {
                Route::get('trial-balance', 'AccountStatementController@trial_balance')->name('trial-balance')->middleware('permission:statements_trial_balance,list');
            });
            // BANK ACCOUNTS
            Route::group(['prefix' => 'banking', 'as' => 'banking.'], function () {
                // BANK ACCOUNT
                Route::group(['prefix' => 'bank-account', 'as' => 'bank-account.'], function () {
                    Route::get('/', 'BankingController@bank_account')->name('index');
                    Route::get('detail/{id}', 'BankingController@bank_account_detail')->name('detail')->middleware('permission:banking_bank_accounts,view');
                    Route::get('detail-main', 'BankingController@bank_account_detail_main')->name('detail-main')->middleware('permission:banking_bank_accounts,view');
                    Route::post('store', 'BankingController@bank_account_store')->name('store')->middleware('permission:banking_bank_accounts,add');
                    Route::post('update/{id}', 'BankingController@bank_account_update')->name('update')->middleware('permission:banking_bank_accounts,edit');
                    Route::get('delete/{id}', 'BankingController@delete')->name('delete')->middleware('permission:banking_bank_accounts,delete');
                    Route::get('delete-file/{id}', 'BankingController@delete_file')->name('delete-file')->middleware('permission:banking_bank_accounts,transaction_file_delete');
                    Route::post('transaction-import', 'BankingController@transaction_import')->name('transaction-import')->middleware('permission:banking_bank_accounts,upload_transactions');
                    Route::get('export/{bank_id?}', 'BankingController@transaction_export')->name('transaction-export')->middleware('permission:banking_bank_accounts,export_transactions');
                });
                // CASH BOOK
                Route::group(['prefix' => 'cash-book', 'as' => 'cash-book.'], function () {
                    Route::get('/', 'CashBookController@index')->name('index');
                    Route::post('entry', 'CashBookController@entry')->name('entry')->middleware('permission:banking_cash_book,add');
                    Route::post('import-excel', 'CashBookController@import')->name('import')->middleware('permission:banking_cash_book,import');
                    Route::get('export', 'CashBookController@export')->name('export')->middleware('permission:banking_cash_book,export');
                });
                // BANK RECONCILIATION
                Route::group(['prefix' => 'bank-reconciliation', 'as' => 'bank-reconciliation.'], function () {
                    Route::get('/', 'BankReconciliationController@index')->name('index')->middleware('permission:banking_bank_reconciliation,list');
                });
            });

            // SETTING
            Route::group(['prefix' => 'setting', 'as' => 'setting.'], function () {
                Route::get('common-settings', 'AccountSettingController@common_settings')->name('common-settings');
                Route::post('all_update', 'AccountSettingController@all_update')->name('all_update')->middleware('permission:settings_common,edit');
                Route::post('account-option-update', 'AccountSettingController@account_option_update')->name('account-option-update')->middleware('permission:settings_common,edit');
                Route::get('account-option-delete/{id}', 'AccountSettingController@account_option_delete')->name('account-option-delete')->middleware('permission:settings_common,edit');

                // CHART OF ACCOUNT
                Route::group(['prefix' => 'chart-of-account', 'as' => 'chart-of-account.'], function () {
                    Route::get('/', 'AccountSettingController@chart_of_account')->name('index');
                    Route::post('account-store', 'AccountSettingController@account_store')->name('account-store')->middleware('permission:assets_chart_of_accounts,add');
                    Route::post('account-update', 'AccountSettingController@account_update')->name('account-update')->middleware('permission:assets_chart_of_accounts,edit');
                    Route::get('account-delete/{id}', 'AccountSettingController@account_delete')->name('account-delete')->middleware('permission:assets_chart_of_accounts,delete');
                    Route::get('detail/{parent?}/{ledger_type_id?}', 'AccountSettingController@chart_of_account_detail')->name('detail')->middleware('permission:assets_chart_of_accounts,view');
                });
            });
            Route::get('approvals', 'AccountRequestFormController@approvals')->name('approvals');
            Route::post('request_rule_store', 'AccountRequestFormController@request_rule_store')->name('request_rule_store')->middleware('permission:approvals,add');
            Route::get('request_rule_delete/{id}', 'AccountRequestFormController@request_rule_delete')->name('request_rule_delete')->middleware('permission:approvals,delete');
            Route::get('request_rule_edit/{id}', 'AccountRequestFormController@request_rule_edit')->name('request_rule_edit')->middleware('permission:approvals,edit');
            Route::post('request_rule_update', 'AccountRequestFormController@request_rule_update')->name('request_rule_update')->middleware('permission:approvals,edit');
            Route::post('forward_permission_store', 'AccountRequestFormController@forward_permission_store')->name('forward_permission_store')->middleware('permission:approvals,add');

            // REQUEST FORM
            Route::group(['prefix' => 'request-form', 'as' => 'request-form.'], function () {

                // MASTER LEDGER REQUEST FORM 
                Route::group(['prefix' => 'master-ledger', 'as' => 'master-ledger.'], function () {
                    Route::post('store', 'AccountRequestFormController@master_ledger_rf_store')->name('store')->middleware('permission:apporval_form_master_ledger,add');
                    Route::post('update', 'AccountRequestFormController@master_ledger_rf_update')->name('update')->middleware('permission:apporval_form_master_ledger,edit');
                    Route::get('/{id?}/{tab?}', 'AccountRequestFormController@master_ledger')->name('index');
                });

                // JOURNAL ENTRY REQUEST FORM
                Route::group(['prefix' => 'journal-entry', 'as' => 'journal-entry.'], function () {
                    Route::get('/{id?}', 'AccountRequestFormController@journal_entry')->name('index');
                    Route::post('store', 'AccountRequestFormController@journal_entry_rf_store')->name('store')->middleware('permission:apporval_form_journal_entry,add');
                    Route::post('update', 'AccountRequestFormController@journal_entry_rf_update')->name('update')->middleware('permission:apporval_form_journal_entry,edit');
                });
                Route::get('detail/{id}', 'AccountRequestFormController@incoming_rf_details')->name('incoming_rf_details');

                Route::get('incoming-requests', 'AccountRequestFormController@incoming_requests')->name('incoming-requests')->middleware('permission:apporval_form_incoming_requests,list');
                Route::get('incoming-request-reject/{id}', 'AccountRequestFormController@incoming_requests_reject')->name('incoming-requests-reject');
                Route::post('incoming-requests-close', 'AccountRequestFormController@incoming_requests_close')->name('incoming-requests-close');
                Route::post('incoming-request-forward', 'AccountRequestFormController@incoming_request_forward')->name('incoming-requests-forward');
            });

            // MASTER LEDGER 
            Route::group(['prefix' => 'master-ledger', 'as' => 'master-ledger.'], function () {
                Route::post('get-entry-details', 'MasterLedgerController@get_entry_details')->name('get-entry-details');
                Route::post('entry', 'MasterLedgerController@entry')->name('entry');
                Route::get('export', 'MasterLedgerController@export')->name('export');
                Route::post('import', 'MasterLedgerController@import')->name('import');
            });

            // JOURNAL ENTRY 
            Route::group(['prefix' => 'petty-cashbook', 'as' => 'petty-cashbook.'], function () {
                Route::get('/', 'AccountController@petty_cashbook')->name('index');
            });

            Route::group(['prefix' => 'journal-entry', 'as' => 'journal-entry.'], function () {
                Route::get('/', 'AccountController@journal_entry')->name('index');
                Route::get('export', 'AccountController@journal_entry_export')->name('export')->middleware('permission:boa_journal_entry,export');
                Route::post('import', 'AccountController@journal_entry_import')->name('import')->middleware('permission:boa_journal_entry,import');
            });
            Route::group(['prefix' => 'taxation', 'as' => 'taxation.'], function () {
                Route::get('gst/{action?}', 'TaxationController@gst')->name('gst');
            });


            Route::get('management/{tab?}', 'AccountController@add')->name('add')->middleware('permission:boa_master_ledger,add');
            Route::get('status/{id}/{status}', 'AccountController@status')->name('status');
            Route::post('save-info', 'AccountController@save_info')->name('save');
            Route::get('delete/{id}', 'AccountController@delete')->name('delete');
            Route::get('edit/{id}', 'AccountController@edit')->name('edit');
            Route::get('mark-as-paid/{id}', 'AccountController@mark_as_paid')->name('mark-as-paid');
            Route::get('list', 'AccountController@index')->name('list');


            Route::post('ledger_account_type-store', 'AccountController@ledger_account_type_store')->name('ledger_account_type.store');
            Route::post('category-store', 'AccountController@category_store')->name('category.store');
            Route::get('dashboard', 'AccountController@dashboard')->name('dashboard')->middleware('permission:dashboard,view');
            Route::get('report', 'AccountController@report')->name('report');
            Route::group(['prefix' => 'report', 'as' => 'report.'], function () {
                Route::get('tax/{action?}', 'AccountReportController@tax')->name('tax')->middleware('permission:reports_tax_report,list');
                Route::get('audit-logs', 'AccountReportController@audit_logs')->name('audit-logs')->middleware('permission:reports_audit_logs,list');
            });

            Route::get('test-notif', 'AccountController@send_push_notif_to_device')->name('send_push_notif_to_device');

            Route::get('setting', 'AccountController@setting')->name('setting')->middleware('permission:settings_account_type,edit');

            Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
                Route::post('update', 'AccountSettingController@update')->name('update')->middleware('permission:settings_account_type,edit');
            });

            Route::group(['prefix' => 'maintenance', 'as' => 'maintenance.'], function () {
                Route::get('/', 'MaintananceController@index')->name('index');
                Route::get('create', 'MaintananceController@create')->name('create')->middleware('permission:boa_monthly_maintenance,add');
                Route::post('store', 'MaintananceController@store')->name('store')->middleware('permission:boa_monthly_maintenance,add');
                Route::get('edit/{id}', 'MaintananceController@edit')->name('edit')->middleware('permission:boa_monthly_maintenance,edit');
                Route::post('update', 'MaintananceController@update')->name('update')->middleware('permission:boa_monthly_maintenance,edit');
                Route::post('update-entry-price', 'MaintananceController@update_entry_price')->name('update-entry-price')->middleware('permission:boa_monthly_maintenance,edit');
                Route::delete('delete/{id}', 'MaintananceController@destroy')->name('delete')->middleware('permission:boa_monthly_maintenance,delete');
                Route::get('status/{id}/{status}', 'MaintananceController@status')->name('status')->middleware('permission:boa_monthly_maintenance,edit');
                Route::get('mark-paid/{id}', 'MaintananceController@mark_paid')->name('mark-paid')->middleware('permission:boa_monthly_maintenance,mark_paid');
                Route::get('export', 'MaintananceController@export')->name('export')->middleware('permission:boa_monthly_maintenance,export');
                Route::post('import', 'MaintananceController@import')->name('import')->middleware('permission:boa_monthly_maintenance,import');
            });
            Route::group(['prefix' => 'day-book', 'as' => 'day-book.'], function () {
                Route::get('/', 'AccountController@day_book')->name('index');
                Route::post('import-excel', 'AccountController@day_book_excel_import')->name('import-excel')->middleware('permission:boa_day_book,import');
                Route::get('export', 'AccountController@day_book_excel_export')->name('export')->middleware('permission:boa_day_book,export');
            });
        });

        Route::post('project/prog-update', 'ProjectController@prog_update')->name('prog-update');

        Route::group(['prefix' => 'documents', 'as' => 'documents.', 'middleware' => ['module:documents']], function () {
            Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
                Route::get('list/{tab}', 'InventoryGatepassController@gatepass_list')->name('list');
                Route::get('delete/{id}', 'InventoryGatepassController@gatepass_delete')->name('delete');
            });
            Route::group(['prefix' => 'receivable-receipt', 'as' => 'receivable-receipt.'], function () {
                Route::get('create', 'LibraryController@recievable_reciept_create')->name('create');
                Route::post('preview', 'LibraryController@recievable_reciept')->name('preview');
                Route::post('store/{action?}/{task_id?}', 'LibraryController@recievable_store')->name('store');
                Route::get('list', 'DocumentsController@recievable_reciepts_list')->name('list');
                Route::get('delete/{id}', 'DocumentsController@recievable_reciepts_delete')->name('delete');
            });
            Route::group(['prefix' => 'job-card', 'as' => 'job-card.'], function () {
                Route::get('create', 'LibraryController@jobcard_create')->name('create');
                Route::post('store/{action?}/{task_id?}', 'LibraryController@job_card_store')->name('store');
                Route::get('list', 'DocumentsController@job_cards_list')->name('list');
                Route::get('delete/{id}', 'DocumentsController@job_card_delete')->name('delete');
            });
            Route::group(['prefix' => 'service-report', 'as' => 'service-report.'], function () {
                Route::get('create', 'DocumentsController@jobcard_create')->name('create');
                Route::post('store/{action?}/{task_id?}', 'DocumentsController@service_report_store')->name('store');
                Route::get('list', 'DocumentsController@job_cards_list')->name('list');
                Route::get('delete/{id}', 'DocumentsController@job_card_delete')->name('delete');
            });
        });
        Route::post('documents/service-report/store/{action?}/{task_id?}', 'DocumentsController@service_report_store')->name('documents.service-report.store-lead'); // for task service report save without module:documents check because it is used in task details page
        Route::post('documents/receivable-receipt/store/{action?}/{task_id?}', 'LibraryController@recievable_store')->name('documents.receivable-receipt.store-lead'); // for task receivable receipt save without module:documents check because it is used in task details page
        Route::group(['prefix' => 'smart-calendar', 'as' => 'smart-calendar.'], function () {
            Route::get('/', 'SmartCalendarController@index')->name('all');
        });

        Route::group(['prefix' => 'library', 'as' => 'library.', 'middleware' => ['module:library']], function () {
            Route::get('/', 'LibraryController@index')->name('all');
            Route::post('select-template', 'LibraryController@select_template')->name('select-template');
            Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
                // Route::group(['prefix' => 'purchase', 'as' => 'purchase.'], function () {
                Route::get('add/{type}', 'DocumentsController@add_gatepass')->name('add');
                Route::post('store', 'DocumentsController@store_gatepass')->name('store');
                // });
                // Route::group(['prefix' => 'sale', 'as' => 'sale.'], function () {
                //     Route::get('add', 'DocumentsController@add_sale_gatepass')->name('add');
                //     Route::post('store', 'DocumentsController@store_sale_gatepass')->name('store');
                // });
            });
        });
        Route::group(['prefix' => 'project', 'as' => 'project.', 'middleware' => ['planwise:projects_manage']], function () {
            // Route::post('save-team', 'ProjectController@save_team')->name('save-team');// not in use
            Route::get('details/{id}/{tab?}', 'ProjectController@details')->name('details')->middleware('permission:project,view');
            Route::get('settings', 'ProjectController@settings')->name('settings')->middleware('permission:project,settings');
            Route::get('setting-update', 'ProjectController@setting_update')->name('setting.update')->middleware('permission:project,settings');
            // Route::get('delete-comment/{id}', 'ProjectController@delete_comment')->name('delete-comment');
            // Route::post('task-status', 'ProjectController@task_status')->name('task-status'); // not in use
            // Route::post('save-task', 'ProjectController@save_task')->name('save_task'); // not in use
            // Route::post('save-comment', 'ProjectController@save_comment')->name('save_comment'); // not in use

            Route::get('list/{id?}', 'ProjectController@index')->name('all')->middleware('permission:project,list');
            Route::get('dashboard', 'ProjectController@dashboard')->name('dashboard');
            Route::get('add', 'ProjectController@add')->name('add')->middleware('permission:project,add');
            Route::post('save-info', 'ProjectController@save_info')->name('save-info')->middleware('permission:project,add');
            Route::get('edit/{id}', 'ProjectController@edit')->name('edit')->middleware('permission:project,edit');
            Route::get('manage/{id}', 'ProjectController@manage')->name('manage')->middleware('permission:project,edit'); // update
            Route::get('status-change/{id}/{status}', 'ProjectController@status_change')->name('status-change')->middleware('permission:project,status_change');
            Route::get('progress-status-change/{id}', 'ProjectController@progress_status_change')->name('progress-status-change')->middleware('permission:project,status_change');
            Route::get('delete/{id}', 'ProjectController@delete')->name('delete')->middleware('permission:project,delete');

            Route::group(['prefix' => 'milestone', 'as' => 'milestone.'], function () {
                Route::post('store', 'ProjectController@store_milestone')->name('store')->middleware('permission:project_milestone,add');
                Route::get('status-change/{id}', 'ProjectController@milestone_status_change')->name('status-change')->middleware('permission:project_milestone,status_change');
                Route::get('delete/{id}', 'ProjectController@milestone_delete')->name('delete')->middleware('permission:project_milestone,delete');
            });
            Route::group(['prefix' => 'note', 'as' => 'note.'], function () {
                Route::post('store', 'ProjectController@store_note')->name('store')->middleware('permission:project_internal_note,add');;
            });
            Route::group(['prefix' => 'team', 'as' => 'team.'], function () {
                Route::post('udpate-team', 'ProjectController@udpate_team')->name('update');
            });

            Route::group(['prefix' => 'task', 'as' => 'task.'], function () { // add middleware
                Route::get('add/{project_id?}', 'ProjectTaskController@add')->name('add')->middleware('permission:project_task,add');
                Route::post('store', 'ProjectTaskController@store')->name('store')->middleware('permission:project_task,add');
                Route::get('edit/{id}', 'ProjectTaskController@edit')->name('edit')->middleware('permission:project_task,edit');
                Route::post('update', 'ProjectTaskController@update')->name('update')->middleware('permission:project_task,edit');
                Route::post('reassign', 'ProjectTaskController@reassign')->name('reassign')->middleware('permission:project_task,edit');
                Route::get('list/{id?}', 'ProjectController@task_list')->name('list');
                Route::get('detail/{id}', 'ProjectTaskController@detail')->name('detail')->middleware('permission:project_task,view');
                Route::post('delete/{id}', 'ProjectTaskController@delete')->name('delete')->middleware('permission:project_task,delete');
                Route::post('save-progress', 'ProjectTaskController@save_progress')->name('save-progress');
                Route::post('status-update', 'ProjectTaskController@status_update')->name('status.update')->middleware('permission:project_task,status_change');
                Route::post('status-new', 'ProjectTaskController@status_new_save')->name('status.save-new');
                Route::get('export', 'ProjectTaskController@export')->name('export')->middleware('permission:project_task,export');
                Route::get('setting', 'ProjectTaskController@setting')->name('setting')->middleware('permission:project_task,settings');

                // Route::get('workflow-form', 'ProjectTaskController@workflow_form')->name('setting.workflow-form');
                Route::post('setting-update', 'ProjectTaskController@setting_update')->name('setting.update')->middleware('permission:project_task,settings');
                Route::group(['prefix' => 'comment', 'as' => 'comment.'], function () {
                    Route::post('add', 'ProjectTaskController@comment_add')->name('add')->middleware('permission:project_task_update,add');
                    Route::get('delete/{id}', 'ProjectTaskController@comment_delete')->name('delete')->middleware('permission:project_task_update,delete');
                    Route::get('edit/{id}', 'ProjectTaskController@comment_edit')->name('edit')->middleware('permission:project_task_update,edit');
                    Route::post('update', 'ProjectTaskController@comment_update')->name('update')->middleware('permission:project_task_update,edit');;
                    Route::get('pic-delete/{comment_id}/{name}', 'ProjectTaskController@pic_delete')->name('pic-delete');
                });
                Route::group(['prefix' => 'subtask-update', 'as' => 'subtask-update.'], function () {
                    Route::post('add', 'ProjectTaskController@comment_add')->name('add')->middleware('permission:project_subtask_update,add');;
                    Route::get('delete/{id}', 'ProjectTaskController@comment_delete')->name('delete')->middleware('permission:project_subtask_update,delete');;
                    Route::get('edit/{id}', 'ProjectTaskController@comment_edit')->name('edit')->middleware('permission:project_subtask_update,edit');
                    Route::post('update', 'ProjectTaskController@comment_update')->name('update')->middleware('permission:project_subtask_update,edit');;
                    Route::get('pic-delete/{comment_id}/{name}', 'ProjectTaskController@pic_delete')->name('pic-delete');
                });
                Route::group(['prefix' => 'subtask', 'as' => 'subtask.'], function () {
                    Route::post('add', 'ProjectTaskController@subtask_add')->name('add')->middleware('permission:project_subtask,add');;
                    Route::get('tasks/{id?}', 'ProjectTaskController@getTasks')->name('getTasks');
                    Route::get('delete/{id}', 'ProjectTaskController@delete_subtask')->name('delete')->middleware('permission:project_subtask,delete');;
                    Route::get('update-level', 'ProjectTaskController@update_level')->name('update-level');

                    Route::get('edit/{id}', 'ProjectTaskController@edit')->name('edit')->middleware('permission:project_subtask,edit'); //done
                    Route::post('update', 'ProjectTaskController@update')->name('update')->middleware('permission:project_subtask,edit');
                    Route::get('detail/{id}', 'ProjectTaskController@detail')->name('detail')->middleware('permission:project_subtask,view');
                    Route::post('status-update', 'ProjectTaskController@status_update')->name('status.update')->middleware('permission:project_subtask,status_change');
                });
            });
        });
        Route::get('track-location/{staff_id}', 'ServiceController@track_location')->name('track-location');

        Route::get('service/reviews', 'ServiceController@reviews')->name('service.reviews');
        Route::post('service/review-status', 'ServiceController@review_status')->name('service.review-status');
        Route::get('service/lead-settings', 'ServiceController@lead_settings')->name('service.lead-settings');
        Route::post('service/lead-settings', 'ServiceController@lead_settings_update')->name('service.lead-settings.update');
        Route::get('service/lead-subscription', '\App\Http\Controllers\Vendor\LeadSubscriptionController@index')->name('service.lead-subscription');
        Route::post('service/lead-subscription/buy', '\App\Http\Controllers\Vendor\LeadSubscriptionController@buy')->name('service.lead-subscription.buy');
        Route::post('service/lead-subscription/gateway', '\App\Http\Controllers\Vendor\LeadSubscriptionController@initiate_gateway')->name('service.lead-subscription.gateway');
        Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
            Route::get('leads/{id?}/{action?}', 'ServiceController@leads_list')->name('leads_list');
            Route::get('assigned-leads/{id?}/{action?}', 'ServiceController@assigned_leads_list')->name('assigned_leads_list');
            Route::get('leads-dashboard', 'ServiceController@leadsDashboard')->name('leads-dashboard');
            Route::get('report', 'ServiceController@report')->name('report')->middleware('permission:leads_manage,report');
            Route::get('report/{id}', 'ServiceController@staff_report')->name('report.staff')->middleware('permission:leads_manage,report');
            Route::get('accept/{id}', 'ServiceController@accept')->name('accept')->middleware('permission:leads_manage,accept');
            // Route::get('list/{tab}', 'ServiceController@lead_list')->name('list');
            Route::get('send-confirmation-notification', 'ServiceController@send_confirmation_notification')->name('send-confirmation-notification');
            Route::post('save-assignment', 'ServiceController@save_assignment')->name('save-assignment')->middleware('permission:leads_manage,alot');
            Route::get('assigned-services', 'ServiceController@assigned_services')->name('assigned_services');
            Route::get('assigned-projects', 'ServiceController@assigned_projects')->name('assigned_projects');
            Route::post('change-status', 'ServiceController@change_status')->name('change-status')->middleware('permission:leads_manage,status_change');
            Route::get('gatepass-details/{id}', 'ServiceController@details')->name('gatepass-details');
            Route::get('quotations/{id}', 'ServiceController@quotations')->name('quotations');
            Route::post('gatepass-update', 'ServiceController@gatepass_update')->name('gatepass-update')->middleware('permission:leads_gatepass,edit');
            Route::post('gatepass-add', 'ServiceController@gatepass_add')->name('gatepass-add')->middleware('permission:leads_gatepass,add');
            Route::post('quotations-add', 'ServiceController@quotation_add')->name('quotations-add')->middleware('permission:leads_quotation,add');
            Route::post('quotations-update', 'ServiceController@quotation_update')->name('quotations-update')->middleware('permission:leads_quotation,edit');
            Route::get('gatepass-return/{id}', 'ServiceController@gatepass_return')->name('gatepass-return');
            Route::get('delete-quote-item/{id}', 'ServiceController@delete_quote_item')->name('delete-quote-item')->middleware('permission:leads_quotation,edit');
            Route::get('delete-gatepass-item/{id}', 'ServiceController@delete_gatepass_item')->name('delete-gatepass-item')->middleware('permission:leads_gatepass,edit');
            Route::post('cancel', 'ServiceController@cancel')->name('cancel')->middleware('permission:leads_manage,cancel');
            Route::get('lead-details/{id}', 'ServiceController@lead_details')->name('lead-details')->middleware('permission:leads_manage,view');
            Route::get('lead-card/{id}', 'ServiceController@getLeadCard')->name('lead-card');
            Route::get('assignment-logs/{id}', 'ServiceController@assignmentLogs')->name('assignment-logs');
            Route::post('send-completion-otp', 'ServiceController@sendCompletionOtp')->name('send-completion-otp');
            Route::post('add-custom-status', 'ServiceController@addCustomStatus')->name('add-custom-status');
            Route::post('dismiss-leads-guide', 'ServiceController@dismissLeadsGuide')->name('dismiss-leads-guide');
            Route::post('lead-note/add', 'ServiceController@addLeadNote')->name('lead-note.add');
            Route::get('task/{id}/{action}/{acc_id}', 'ServiceController@task_action')->name('task');
        });


        Route::group(['prefix' => 'lead', 'as' => 'lead.'], function () {
            Route::post('job-otp-verify', 'LeadController@job_otp_verify')->name('job-otp-verify');
            Route::post('job-otp-verify2', 'LeadController@job_otp_verify2')->name('job-otp-verify2');
            Route::post('save-comment', 'LeadController@save_comment')->name('save-comment')->middleware('permission:leads_manage,comment');
            Route::post('start-job', 'LeadController@start_job')->name('start-job')->middleware('permission:leads_manage,start_job');
            Route::post('change-job-status', 'LeadController@change_job_status')->name('change-job-status')->middleware('permission:leads_manage,status_change');
            Route::get('convert-to-task/{lead_id}', 'LeadController@convert_to_task')->name('convert-to-task')->middleware('permission:leads_manage,convert_to_task');
            Route::get('convert-to-order/{lead_id}', 'LeadController@convert_to_order')->name('convert-to-order');
            Route::post('convert-to-order-store', 'LeadController@convert_to_order_store')->name('convert-to-order-store');
        });

        Route::group(['prefix' => 'lead', 'as' => 'lead.', 'middleware' => ['planwise:leads_manage']], function () {
            Route::get('list', 'LeadController@index')->name('list');
            Route::get('add', 'LeadController@add')->name('add')->middleware('permission:leads_manage,add');
            Route::post('preview', 'LeadController@preview')->name('preview');
            Route::post('status-change', 'LeadController@status_change')->name('status-change')->middleware('permission:leads_manage,status_change');
            Route::post('save-info', 'LeadController@save_info')->name('save-info')->middleware('permission:leads_manage,add');
            // Route::post('lead_approval', 'LeadController@lead_approval')->name('lead_approval');
            Route::get('delete/{id}', 'LeadController@delete')->name('delete')->middleware('permission:leads_manage,delete');
            Route::get('manage/{id}', 'LeadController@manage')->name('manage')->middleware('permission:leads_manage,edit');
        });

        Route::group(['prefix' => 'quotation', 'as' => 'quotation.', 'middleware' => ['planwise:quotaiton_manage']], function () {
            Route::get('convert-to-bill/{id}', 'QuoteController@convert_to_bill')->name('convert-to-bill')->middleware('permission:quotaiton_manage,convert_to_bill');
            Route::get('list', 'QuoteController@index')->name('list');
            Route::get('send-quote/{id}', 'QuoteController@send_quote')->name('send-quote');
            Route::get('quotation_number_validation', 'QuoteController@quotation_number_validation')->name('quotation_number_validation')->middleware('permission:quotaiton_manage,add');
            Route::get('add', 'QuoteController@add')->name('add')->middleware('permission:quotaiton_manage,add');
            Route::post('status-change', 'QuoteController@status_change')->name('status-change')->middleware('permission:quotaiton_manage,status_chang');
            Route::post('save-info', 'QuoteController@save_info')->name('save-info')->middleware('permission:quotaiton_manage,add');
            Route::post('lead_approval', 'QuoteController@lead_approval')->name('lead_approval');
            Route::get('delete/{id}', 'QuoteController@delete')->name('delete')->middleware('permission:quotaiton_manage,delete');
            Route::get('manage/{id}', 'QuoteController@manage')->name('manage')->middleware('permission:quotaiton_manage,edit');
            Route::get('check-email', 'QuoteController@check_email')->name('check-email');
            Route::post('send-quote-email', 'QuoteController@send_quote_email')->name('send-quote-email');
            Route::get('settings', 'SettingsController@quotation_settings')->name('settings')->middleware('permission:quotaiton_manage,settings');

            Route::post('config-save', 'BusinessSettingsController@config_save')->name('config.save');
            Route::post('signature-save', 'BusinessSettingsController@signature_save')->name('signature.save')->middleware('permission:quotation_sign,add');
            Route::get('signature-delete/{id}', 'BusinessSettingsController@signature_delete')->name('signature.delete')->middleware('permission:quotation_sign,delete');
            Route::post('signature-fetch', 'BusinessSettingsController@signature_fetch')->name('signature.fetch')->middleware('permission:quotation_sign,list');
            Route::post('new-bank-account', 'BusinessSettingsController@new_bank_account')->name('new-bank-account')->middleware('permission:quotation_bank_account,add');;
            Route::get('delete-account/{id}', 'BusinessSettingsController@delete_account')->name('delete-account')->middleware('permission:quotation_bank_account,delete');;
        });
        Route::post('quotation/save-info/{id}', 'QuoteController@save_info')->name('quotation.save-info-task'); // for lead quotation save without permission check because it is used in lead details page and we have given access to view lead details for some roles who don't have permission to manage quotation

        Route::group(['prefix' => 'staff-department', 'as' => 'staff-department.'], function () {
            Route::get('/', 'StaffController@departments')->name('all');
            Route::post('save', 'StaffController@store_department')->name('save')->middleware('permission:staff_department,add');
            Route::get('d-delete/{id}', 'StaffController@delete_department')->name('delete')->middleware('permission:staff_department,delete');
            Route::post('status-change', 'StaffController@status_change')->name('status-change')->middleware('permission:staff_department,status_change');
        });
        Route::get('terms-n-conditions-staff', 'EmployeeController@view_terms_and_conditions')->name('staff.terms-n-conditions');

        // Basic staff — free tier (no HR subscription required), max 10 members
        Route::group(['prefix' => 'basic-staff', 'as' => 'basic-staff.'], function () {
            Route::get('/', 'BasicStaffController@index')->name('index')->middleware('permission:basic_staff_manage,list');
            Route::get('create', 'BasicStaffController@create')->name('create')->middleware('permission:basic_staff_manage,add');
            Route::post('store', 'BasicStaffController@store')->name('store')->middleware('permission:basic_staff_manage,add');
            Route::get('edit/{id}', 'BasicStaffController@edit')->name('edit')->middleware('permission:basic_staff_manage,edit');
            Route::post('update/{id}', 'BasicStaffController@update')->name('update')->middleware('permission:basic_staff_manage,edit');
            Route::delete('delete/{id}', 'BasicStaffController@destroy')->name('delete')->middleware('permission:basic_staff_manage,delete');
            // Roles — reuse paid HR custom-role controller/views, accessible via free-tier permission
            Route::get('roles', 'CustomRoleController@create')->name('roles')->middleware('permission:basic_staff_manage,role_manage');
        });

        Route::group(['prefix' => 'staff', 'as' => 'staff.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('edit/{id}', 'EmployeeController@edit')->name('edit')->middleware('permission:staff_manage,edit');
            Route::delete('delete/{id}', 'EmployeeController@distroy')->name('delete')->middleware('permission:staff_manage,delete');
            Route::get('add-new', 'EmployeeController@add_new')->name('add-new')->middleware('permission:staff_manage,add');
            Route::post('save-info', 'StaffController@save_info')->name('save')->middleware('permission:staff_manage,add');
            Route::get('add', 'StaffController@add')->name('add')->middleware('permission:staff_manage,add');
            Route::get('list', 'EmployeeController@list')->name('list');
            Route::get('settings', 'StaffController@settings')->name('settings')->middleware('permission:staff_manage,settings');
            Route::post('save-settings', 'StaffController@save_settings')->name('settings.save')->middleware('permission:staff_manage,settings');
            Route::get('status/{id}/{status}', 'StaffController@status')->name('status')->middleware('permission:staff_manage,status_change');
            Route::group(['prefix' => 'team', 'as' => 'team.'], function () {
                Route::get('/', 'StaffController@teams')->name('index');
                Route::post('save', 'StaffController@team_save')->name('save')->middleware('permission:staff_team,add');
                Route::get('delete/{id}', 'StaffController@team_delete')->name('delete')->middleware('permission:staff_team,delete');
                Route::get('member-delete/{id}', 'StaffController@team_member_delete')->name('member.delete')->middleware('permission:staff_team,edit');
                Route::get('edit/{id}', 'StaffController@team_edit')->name('edit')->middleware('permission:staff_team,edit');
                Route::post('update', 'StaffController@team_update')->name('update')->middleware('permission:staff_team,edit');
                Route::get('view/{id}', 'StaffController@team_edit')->name('view')->middleware('permission:staff_team,view');
            });
        });

        Route::resource('task-salary-categories', TaskSalaryCategoryController::class);

        Route::group(['prefix' => 'shifts', 'as' => 'shifts.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('/', 'ShiftController@index')->name('index');
            Route::post('store', 'ShiftController@store')->name('store')->middleware('permission:shift_manage,add');
            Route::get('delete/{id}', 'ShiftController@delete')->name('delete')->middleware('permission:shift_manage,delete');
            Route::post('update', 'ShiftController@update')->name('update')->middleware('permission:shift_manage,edit');

            // Live Work Updates + shift change (swap)
            Route::get('live', 'ShiftController@liveWork')->name('live');
            Route::post('swap/store', 'ShiftController@swapStore')->name('swap.store');
            Route::get('swap/{id}/{status}', 'ShiftController@swapStatus')->name('swap.status');
        });
        Route::group(['prefix' => 'asset', 'as' => 'asset.'], function () {
            Route::post('return', 'AssetsController@return_asset')->name('return');
            Route::get('alotted', 'AssetsController@alotted_assets')->name('alotted');
        });

        Route::group(['prefix' => 'asset', 'as' => 'asset.', 'middleware' => ['planwise:account_manage']], function () { // add middleware
            Route::get('/', 'AssetsController@index')->name('index');
            Route::post('store', 'AssetsController@store')->name('store');
            Route::post('alot', 'AssetsController@alotToStaff')->name('alot');
            Route::get('delete/{id}', 'AssetsController@delete')->name('delete');
            Route::post('get-alotment-details', 'AssetsController@get_alotment_details')->name('get-alotment-details');
        });
        Route::group(['prefix' => 'hr', 'as' => 'hr.'], function () {
            Route::get('dashboard', 'HRController@dashboard')->name('dashboard')->middleware('permission:hr_manage,dashboard');;
        });
        Route::group(['prefix' => 'client', 'as' => 'customer.'], function () {
            Route::post('fetch-details', 'CustomerController@fetch_details')->name('fetch-details');
            Route::get('get-matches', 'CustomerController@get_matches')->name('get-matches');
        });
        Route::group(['prefix' => 'client', 'as' => 'customer.', 'middleware' => ['module:client_manage']], function () {
            Route::post('transactions-export', 'CustomerController@transactions_export')->name('transactions.export')->middleware('permission:client_manage,transactions');
            Route::post('leads-export', 'CustomerController@leads_export')->name('leads.export')->middleware('permission:client_manage,leads');
            Route::post('project-export', 'CustomerController@project_export')->name('project.export')->middleware('permission:client_manage,projects');
            Route::post('tasks-export', 'CustomerController@tasks_export')->name('tasks.export')->middleware('permission:client_manage,tasks');
            Route::get('search', 'CustomerController@search')->name('search');
            Route::get('list/{tab?}', 'CustomerController@list')->name('list');
            Route::get('add-new', 'CustomerController@add_new')->name('add')->middleware('permission:client_manage,add');
            Route::post('save', 'CustomerController@save')->name('save')->middleware('permission:client_manage,add');
            Route::get('edit/{id}', 'CustomerController@edit')->name('edit')->middleware('permission:client_manage,edit');
            Route::post('update', 'CustomerController@update')->name('update')->middleware('permission:client_manage,edit');
            Route::get('view/{id}', 'CustomerController@view')->name('view')->middleware('permission:client_manage,view');
            Route::get('delete/{id}', 'CustomerController@delete')->name('delete')->middleware('permission:client_manage,delete');
            Route::post('bulk-delete', 'CustomerController@bulk_delete')->name('bulk-delete')->middleware('permission:client_manage,delete');
            Route::post('upload-excel', 'CustomerController@upload_excel')->name('upload-excel')->middleware('permission:client_manage,import');
            Route::get('get-store-customer', 'CustomerController@get_store_customer')->name('get-store-customer');
            Route::get('export', 'CustomerController@export')->name('export')->middleware('permission:client_manage,export');
            Route::post('upload-excel', 'CustomerController@upload_excel')->name('upload-excel')->middleware('permission:client_manage,import');
            Route::post('comment-save', 'CustomerController@comment_save')->name('comment-save')->middleware('permission:client_manage,comment');
        });
        Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
            Route::post('order-stats', 'DashboardController@order_stats')->name('order-stats');
            Route::post('filter-pnl', 'DashboardController@filter_pnl')->name('filter-pnl');

            Route::get('leads', 'DashboardController@leads_list')->name('leads');
            Route::get('sales/{type?}', 'DashboardController@sales_list')->name('sales');
            Route::get('pending', 'DashboardController@pending_list')->name('pending');
            Route::get('expense', 'DashboardController@expense_list')->name('expense');
            Route::get('tasks', 'DashboardController@tasks_list')->name('tasks');
            Route::get('projects', 'DashboardController@projects_list')->name('projects');
            Route::get('inventory-sales', 'DashboardController@inventory_sale_list')->name('inventory-sales');
        });

        // ECOMMERCE MODULE — item, category, order, addon, coupon, banner, campaign
        require base_path('app/Modules/Ecommerce/routes/vendor.php');

        Route::group(['prefix' => 'custom-role', 'as' => 'custom-role.'], function () {
            Route::get('create', 'CustomRoleController@create')->name('create')->middleware('permission:staff_role,add');
            Route::post('create', 'CustomRoleController@store')->name('store')->middleware('permission:staff_role,add');
            Route::get('edit/{id}', 'CustomRoleController@edit')->name('edit')->middleware('permission:staff_role,edit');
            Route::post('update/{id}', 'CustomRoleController@update')->name('update')->middleware('permission:staff_role,edit');
            Route::delete('delete/{id}', 'CustomRoleController@distroy')->name('delete')->middleware('permission:staff_role,delete');
            //            Route::post('search', 'CustomRoleController@search')->name('search');
        });

        Route::group(['prefix' => 'delivery-man', 'as' => 'delivery-man.', 'middleware' => ['module:deliveryman']], function () {
            Route::get('add', 'DeliveryManController@index')->name('add');
            Route::post('store', 'DeliveryManController@store')->name('store');
            Route::get('list', 'DeliveryManController@list')->name('list');
            Route::get('preview/{id}/{tab?}', 'DeliveryManController@preview')->name('preview');
            Route::get('status/{id}/{status}', 'DeliveryManController@status')->name('status');
            Route::get('earning/{id}/{status}', 'DeliveryManController@earning')->name('earning');
            Route::get('edit/{id}', 'DeliveryManController@edit')->name('edit');
            Route::post('update/{id}', 'DeliveryManController@update')->name('update');
            Route::delete('delete/{id}', 'DeliveryManController@delete')->name('delete');
            //            Route::post('search', 'DeliveryManController@search')->name('search');
            Route::get('get-deliverymen', 'DeliveryManController@get_deliverymen')->name('get-deliverymen');
            Route::post('transation/search', 'DeliveryManController@transaction_search')->name('transaction-search');

            Route::group(['prefix' => 'reviews', 'as' => 'reviews.'], function () {
                Route::get('list', 'DeliveryManController@reviews_list')->name('list');
            });
        });

        Route::group(['prefix' => 'employee', 'as' => 'employee.'], function () {
            Route::post('resign', 'EmployeeController@resign')->name('resign');
            Route::get('resignation-action/{id}/{action}', 'EmployeeController@resignation_action')->name('resignation-action');
        });
        Route::group(['prefix' => 'employee', 'as' => 'employee.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::post('comment-save', 'EmployeeController@comment_save')->name('comment-save')->middleware('permission:staff_manage,comment');
            Route::get('add-new', 'EmployeeController@add_new')->name('add-new')->middleware('permission:staff_manage,add');
            Route::post('add-new', 'EmployeeController@store')->middleware('permission:staff_manage,add');
            Route::get('list', 'EmployeeController@list')->name('list');
            Route::get('edit/{id}', 'EmployeeController@edit')->name('edit')->middleware('permission:staff_manage,edit');
            Route::post('update/{id}', 'EmployeeController@update')->name('update')->middleware('permission:staff_manage,edit');
            Route::delete('delete/{id}', 'EmployeeController@distroy')->name('delete')->middleware('permission:staff_manage,delete');
            Route::get('list-export', 'EmployeeController@list_export')->name('export-employee')->middleware('permission:staff_manage,export');
            Route::get('view/{id}', 'EmployeeController@view')->name('view')->middleware('permission:staff_manage,view');
            Route::get('view-id-card/{id}', 'EmployeeController@view_id_card')->name('view-id-card')->middleware('permission:staff_manage,view');
            Route::get('terminate/{id}', 'EmployeeController@terminate')->name('terminate')->middleware('permission:staff_manage,terminate');
        });




        Route::group(['prefix' => 'wallet', 'as' => 'wallet.', 'middleware' => ['module:wallet']], function () {
            Route::get('/', 'WalletController@index')->name('index');
            Route::get('payment-wallet/{id}', [PaymentController::class, 'payment_wallet'])->name('payment-wallet');
            Route::post('recharge', 'WalletController@recharge')->name('recharge');
            Route::post('wallet-recharge', 'WalletController@wallet_recharge')->name('wallet-recharge');
            Route::post('wallet-recharge-coupon', 'WalletController@wallet_recharge_w_coupon')->name('wallet-recharge-coupon');
            Route::post('request', 'WalletController@w_request')->name('withdraw-request');
            Route::delete('close/{id}', 'WalletController@close_request')->name('close-request');
            Route::get('method-list', 'WalletController@method_list')->name('method-list');
            Route::post('make-collected-cash-payment', 'WalletController@make_payment')->name('make_payment');
            Route::post('make-wallet-adjustment', 'WalletController@make_wallet_adjustment')->name('make_wallet_adjustment');

            Route::get('wallet-payment-list', 'WalletController@wallet_payment_list')->name('wallet_payment_list');
            Route::get('disbursement-list', 'WalletController@getDisbursementList')->name('getDisbursementList');
            Route::get('export', 'WalletController@getDisbursementExport')->name('export');
        });

        Route::group(['prefix' => 'withdraw-method', 'as' => 'wallet-method.', 'middleware' => ['module:wallet']], function () {
            Route::get('/', 'WalletMethodController@index')->name('index');
            Route::post('store/', 'WalletMethodController@store')->name('store');
            Route::get('default/{id}/{default}', 'WalletMethodController@default')->name('default');
            Route::delete('delete/{id}', 'WalletMethodController@delete')->name('delete');
        });

        Route::group(['prefix' => 'requirement', 'as' => 'requirement.'], function () {
            Route::post('submit', 'BusinessSettingsController@submit_requirement')->name('submit');
        });


        Route::group(['prefix' => 'gallery', 'as' => 'gallery.'], function () {
            Route::get('/', 'DashboardController@gallery')->name('all');
            Route::post('store', 'DashboardController@gallery_store')->name('store');
            Route::post('delete', 'DashboardController@gallery_delete')->name('delete');
            Route::post('bulk-delete', 'DashboardController@gallery_bulk_delete')->name('bulk-delete');
        });
        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.'], function () {
            Route::get('generate-bill', 'ServiceController@generate_bill_list')->name('generate-bill.list');
            Route::get('generate-bill/{serviceid}', 'ServiceController@generate_bill')->name('generate-bill');
            Route::get('invoices', 'ServiceController@service_invoices')->name('service-invoices');
        });

        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.'], function () {
            Route::post('signature-save', 'BusinessSettingsController@signature_save')->name('signature.save')->middleware('permission:billing_signatures,add');;
            Route::get('signature-delete/{id}', 'BusinessSettingsController@signature_delete')->name('signature.delete')->middleware('permission:billing_signatures,delete');;
            Route::post('signature-fetch', 'BusinessSettingsController@signature_fetch')->name('signature.fetch');
            Route::post('new-bank-account', 'BusinessSettingsController@new_bank_account')->name('new-bank-account')->middleware('permission:billing_bank_account,add');;
            Route::get('delete-account/{id}', 'BusinessSettingsController@delete_account')->name('delete-account')->middleware('permission:billing_bank_account,delete');;
            Route::post('config-save', 'BusinessSettingsController@config_save')->name('config.save');
        });

        Route::get('menu-preference', 'SettingsController@menu_preference')->name('menu_preference')->middleware('module:menu_preference');
        Route::post('menu-preference-save', 'SettingsController@menu_preference_save')->name('menu_preference_save')->middleware('module:menu_preference');
        Route::get('business-settings/update-active-status', 'BusinessSettingsController@active_status')->name('business-settings.update-active-status')->middleware('module:store_availability');

        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.', 'middleware' => ['module:store_setup']], function () {
            Route::get('tnc-fetch/{id}', 'BusinessSettingsController@tnc_fetch')->name('tnc.fetch');
            Route::get('my-documents', 'BusinessSettingsController@my_documents')->name('my-documents');
            Route::post('update-doc', 'BusinessSettingsController@update_doc')->name('update-doc');
            Route::post('add-document', 'BusinessSettingsController@add_other_document')->name('add-document');
            Route::post('delete-document/{id}', 'BusinessSettingsController@delete_other_document')->name('delete-document');
            Route::post('toggle-document/{id}', 'BusinessSettingsController@toggle_other_document')->name('toggle-document');

            Route::get('tnc-delete/{id}', 'BusinessSettingsController@tnc_delete')->name('tnc.delete');
            Route::post('tnc-save', 'BusinessSettingsController@tnc_save')->name('tnc.save');
            Route::post('tnc-update', 'BusinessSettingsController@tnc_update')->name('tnc.update');


            Route::post('upload-image', 'BusinessSettingsController@uploadImage')->name('image-upload');

            Route::get('about-us', 'BusinessSettingsController@about_us')->name('about-us');
            Route::post('about-us-save', 'BusinessSettingsController@about_us_save')->name('about-us.save');
            Route::get('terms-and-conditions', 'BusinessSettingsController@common_terms_and_conditions')->name('common-terms-and-conditions');
            Route::post('common-tnc-save', 'BusinessSettingsController@common_tnc_save')->name('common-tnc-save');
            Route::post('common-pp-save', 'BusinessSettingsController@common_pp_save')->name('common-pp-save');
            Route::get('settings', 'BusinessSettingsController@terms_and_conditions')->name('terms-and-conditions');
            Route::post('settings-save', 'BusinessSettingsController@terms_and_conditions_save')->name('terms-and-conditions.save');

            Route::post('update-statuses', 'BusinessSettingsController@update_statuses')->name('update-statuses');
            Route::get('store-setup', 'BusinessSettingsController@store_index')->name('store-setup');
            Route::post('edit-leaves', 'BusinessSettingsController@edit_leaves')->name('edit-leaves');
            Route::post('add-schedule', 'BusinessSettingsController@add_schedule')->name('add-schedule');
            Route::get('remove-schedule/{store_schedule}', 'BusinessSettingsController@remove_schedule')->name('remove-schedule');
            Route::get('minimized_menu', 'BusinessSettingsController@minimized_menu')->name('minimized_menu');
            Route::post('update-setup/{store}', 'BusinessSettingsController@store_setup')->name('update-setup');
            Route::post('update-meta-data/{store}', 'BusinessSettingsController@updateStoreMetaData')->name('update-meta-data');
            Route::post('update-social-media/{store}', 'BusinessSettingsController@update_social_media')->name('update-social-media');
            Route::get('toggle-settings-status/{store}/{status}/{menu}', 'BusinessSettingsController@store_status')->name('toggle-settings');

            Route::post('quick-actions-save', 'SettingsController@quick_actions_save')->name('quick_actions_save');
            Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
                Route::post('save', 'SettingsController@common_setting_save')->name('save');

                Route::get('general-settings', 'SettingsController@general_settings')->name('general');
                Route::get('quotation-settings', 'SettingsController@quotation_settings')->name('quotation');
                Route::get('invoice-settings', 'SettingsController@invoice_settings')->name('invoice');
                Route::get('receivable-receipts', 'SettingsController@receivable_receipts')->name('receivable-receipts');
            });
        });


        Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
            Route::get('webpage/{tab?}', 'SettingsController@webpage_settings')->name('webpage');
            Route::post('webpage-update', 'SettingsController@webpage_settings_update')->name('webpage-update');
            Route::post('webpage-template', 'SettingsController@webpage_template_update')->name('webpage-template');
            Route::post('purchase-template', 'SettingsController@purchase_template')->name('purchase-template');

            Route::get('service-setup/{tab?}', 'SettingsController@service_setup')->name('service-setup');

            Route::group(['prefix' => 'domain', 'as' => 'domain.'], function () {
                Route::post('update', 'SettingsController@domain_update')->name('update');
                Route::post('remove', 'SettingsController@domain_remove')->name('remove');
            });
            Route::group(['prefix' => 'general', 'as' => 'general.'], function () {
                Route::get('profile', 'SettingsController@profile_settings')->name('profile'); 
                Route::get('store', 'SettingsController@store_settings')->name('store');
                Route::get('holidays', 'SettingsController@holiday_settings')->name('holidays');
                Route::get('holiday/add', 'SettingsController@holiday_add')->name('holiday.add');
                Route::post('holiday/update', 'SettingsController@holiday_update')->name('holiday.update');
                Route::get('holiday/delete/{id}', 'SettingsController@holiday_delete')->name('holiday.delete');
            });
        });
        Route::get('subscriptions', 'ProfileController@subscriptions')->name('subscriptions')->middleware('module:subscriptions');;

        Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => ['module:subscriptions']], function () {
            Route::get('enable-free-trial', 'ProfileController@enable_free_trial')->name('enable-free-trial');
            Route::post('buy-module', 'ProfileController@buy_module')->name('buy-module');
            Route::post('buy-plan', 'ProfileController@buy_plan')->name('buy-plan');
            Route::post('settings-plans', 'ProfileController@settings_plans')->name('settings-plans');
            Route::post('about/update', 'ProfileController@about_update')->name('about.update');
        });

        Route::get('profile/view', 'ProfileController@view')->name('profile.view');
        Route::get('profile/edit', 'ProfileController@edit')->name('profile.edit');
        Route::post('profile/update', 'ProfileController@update')->name('profile.update');
        Route::post('profile/change-password', 'ProfileController@staff_change_password')->name('profile.change-password');
        Route::post('profile/default-dashboard', 'ProfileController@saveDefaultDashboard')->name('profile.default-dashboard');
        Route::group(['prefix' => 'profile', 'as' => 'profile.', 'middleware' => ['module:bank_info']], function () {
            Route::post('settings-password', 'ProfileController@settings_password_update')->name('settings-password');
            Route::get('bank-view', 'ProfileController@bank_view')->name('bankView');
            Route::get('bank-edit', 'ProfileController@bank_edit')->name('bankInfo');
            Route::post('bank-update', 'ProfileController@bank_update')->name('bank_update');
            Route::post('bank-delete', 'ProfileController@bank_delete')->name('bank_delete');
        });

        Route::group(['prefix' => 'store', 'as' => 'shop.', 'middleware' => ['module:my_shop']], function () {
            Route::get('view', 'RestaurantController@view')->name('view');
            Route::get('edit', 'RestaurantController@edit')->name('edit');
            Route::post('update', 'RestaurantController@update')->name('update');
            Route::post('update-message', 'RestaurantController@update_message')->name('update-message');
        });

        Route::group(['prefix' => 'message', 'as' => 'message.'], function () {
            Route::get('list', 'ConversationController@list')->name('list');
            Route::post('store/{user_id}/{user_type}', 'ConversationController@store')->name('store');
            Route::get('view/{conversation_id}/{user_id}', 'ConversationController@view')->name('view');
        });

        Route::group(['prefix' => 'report', 'as' => 'report.', 'middleware' => ['module:report']], function () {
            Route::post('set-date', 'ReportController@set_date')->name('set-date');
            Route::get('expense-report', 'ReportController@expense_report')->name('expense-report');
            Route::get('expense-export', 'ReportController@expense_export')->name('expense-export');
            Route::post('expense-report-search', 'ReportController@expense_search')->name('expense-report-search');
            Route::get('disbursement-report', 'ReportController@disbursement_report')->name('disbursement-report');
            Route::get('disbursement-report-export/{type}', 'ReportController@disbursement_report_export')->name('disbursement-report-export');
        });
    });
    Route::group(['prefix' => 'ai-chat', 'as' => 'ai-chat.'], function () {
        Route::get('/', 'AIChatController@index')->name('index');
        Route::post('send', 'AIChatController@chat')->name('send');
        Route::get('history', 'AIChatController@history')->name('history');
        Route::post('clear', 'AIChatController@clearMemory')->name('clear');
        Route::post('tts', 'AIChatController@tts')->name('tts');
    });
 
    // hmis ==============================
    if (file_exists(app_path('Modules/HMIS/routes/vendor.php'))) {
        require app_path('Modules/HMIS/routes/vendor.php'); 
    } 
 
    // laundry ============================== 
    require app_path('Modules/Laundry/routes/vendor.php');
 
    // pos ==============================
    require app_path('Modules/POS/routes/vendor.php');

    // pos retail ==============================
    require app_path('Modules/PosRetail/routes/vendor.php');

    // school ==============================
    if (file_exists(app_path('Modules/School/routes/vendor.php'))) {
        require app_path('Modules/School/routes/vendor.php');
    }
});
// DON'T UPLOAD WITHOUT ALL HMIS , LAUNDRY , POS MODULES 

// WebSocketsRouter::webSocket('/service-requests', ServiceReqSocketHandler::class);
