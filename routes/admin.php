<?php

use App\Http\Controllers\Admin\Item\CategoryController;
use Illuminate\Support\Facades\Route;


Route::group(['namespace' => 'Admin', 'as' => 'admin.'], function () {

    Route::group(['prefix' => 'file', 'as' => 'file.'], function () {
        Route::get('add', 'FileController@add')->name('add');
        Route::post('store', 'FileController@store')->name('store');
    }); 
    Route::post('send-otp', 'SystemController@send_otp')->name('send-otp');
    Route::post('verify-otp', 'SystemController@verify_otp')->name('verify-otp'); 
    Route::post('proceed-action', 'AdminActionController@proceed_action')->name('proceed-action');
    Route::get('secure-download/{file}', 'ProtectedFileController@download_file')
        ->name('secure.download')->middleware('signed'); 
 
    Route::group(['prefix' => 'prompt', 'as' => 'prompt.', 'middleware' => ['module:ai_agent']], function () {
        Route::get('/', 'SystemPromptController@index')->name('index')->middleware('permission:system_prompt,list');
        Route::post('store', 'SystemPromptController@store')->name('store')->middleware('permission:system_prompt,add');
        Route::post('update', 'SystemPromptController@update')->name('update')->middleware('permission:system_prompt,edit');
        Route::delete('delete/{prompt}', 'SystemPromptController@delete')->name('delete')->middleware('permission:system_prompt,delete');
    });
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

        Route::group(['prefix' => 'ai-chat', 'as' => 'ai-chat.'], function () {
            Route::get('/', 'AIChatController@index')->name('index')->middleware('permission:ai_chat,view');
            Route::post('send', 'AIChatController@chat')->name('send')->middleware('permission:ai_chat,send');
            Route::get('history', 'AIChatController@history')->name('history')->middleware('permission:ai_chat,view');
            Route::post('clear', 'AIChatController@clearMemory')->name('clear')->middleware('permission:ai_chat,send');
            Route::post('tts', 'AIChatController@tts')->name('tts')->middleware('permission:ai_chat,send');
            Route::get('logs', 'AIChatController@chatLogs')->name('logs')->middleware('permission:ai_chat,logs');
            Route::get('logs/{type}/{id}', 'AIChatController@chatLogDetail')->name('logs.detail')->middleware('permission:ai_chat,logs');
            Route::get('analytics', 'AIChatController@analytics')->name('analytics')->middleware('permission:ai_chat,analytics');
            Route::get('analytics/export', 'AIChatController@exportChatLogs')->name('analytics.export')->middleware('permission:ai_chat,analytics');
        });

        Route::group(['prefix' => 'agent', 'as' => 'agent.', 'middleware' => ['module:ai_agent']], function () {
            // Dashboard / index
            Route::get('/', 'AIAgentSkillController@index')->name('index')->middleware('permission:ai_agent,list');

            // Agent CRUD
            Route::post('store',          'AIAgentSkillController@store')->name('store')->middleware('permission:ai_agent,add');
            Route::get('{id}',            'AIAgentSkillController@show')->name('show')->middleware('permission:ai_agent,view');
            Route::post('{id}/update',    'AIAgentSkillController@update')->name('update')->middleware('permission:ai_agent,edit');
            Route::delete('{id}',         'AIAgentSkillController@destroy')->name('destroy')->middleware('permission:ai_agent,delete');

            // Test console
            Route::post('{id}/test',      'AIAgentSkillController@test')->name('test')->middleware('permission:ai_agent,test');

            // Versioning
            Route::post('{id}/version',   'AIAgentSkillController@bumpVersion')->name('version.bump')->middleware('permission:ai_agent,edit');

            // API Tools
            Route::post('{agentId}/tools',         'AIAgentSkillController@storeApiTool')->name('tools.store')->middleware('permission:ai_agent,edit');
            Route::post('tools/{toolId}/update',   'AIAgentSkillController@updateApiTool')->name('tools.update')->middleware('permission:ai_agent,edit');
            Route::delete('tools/{toolId}',        'AIAgentSkillController@destroyApiTool')->name('tools.destroy')->middleware('permission:ai_agent,delete');

            // Function Schemas
            Route::post('{agentId}/functions',         'AIAgentSkillController@storeFunction')->name('functions.store')->middleware('permission:ai_agent,edit');
            Route::post('functions/{fnId}/update',     'AIAgentSkillController@updateFunction')->name('functions.update')->middleware('permission:ai_agent,edit');
            Route::delete('functions/{fnId}',          'AIAgentSkillController@destroyFunction')->name('functions.destroy')->middleware('permission:ai_agent,delete');

            // Tasks
            Route::post('{agentId}/tasks',         'AIAgentSkillController@storeTask')->name('tasks.store')->middleware('permission:ai_agent,edit');
            Route::post('tasks/{taskId}/update',   'AIAgentSkillController@updateTask')->name('tasks.update')->middleware('permission:ai_agent,edit');
            Route::delete('tasks/{taskId}',        'AIAgentSkillController@destroyTask')->name('tasks.destroy')->middleware('permission:ai_agent,delete');
        });

        Route::group(['prefix' => 'analytics', 'as' => 'analytics.'], function () {
            Route::get('/', 'AnalyticsController@index')->name('index')->middleware('permission:analytics,view');
            Route::get('detail/{type}/{id}', 'AnalyticsController@detail')->name('detail')->middleware('permission:analytics,view');
            Route::get('chart-data', 'AnalyticsController@chartData')->name('chart-data')->middleware('permission:analytics,view');
        });

        Route::group(['prefix' => 'logs', 'as' => 'logs.'], function () {
            Route::get('action-logs', 'DashboardController@action_logs')->name('action-logs')->middleware('permission:action_logs,view');
            Route::get('action-logs/error-logs', 'DashboardController@action_logs')->name('action-logs.errors')->middleware('permission:error_logs,view');
            Route::get('action-logs/admin', 'DashboardController@action_logs')->name('action-logs.admin')->middleware('permission:admin_actions,view');
            Route::post('error-logs/{id}/status', 'DashboardController@update_error_status')->name('error-logs.update-status')->middleware('permission:error_logs,view');
            Route::post('error-logs/bulk-delete', 'DashboardController@bulk_delete_errors')->name('error-logs.bulk-delete')->middleware('permission:error_logs,view');
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
            Route::get('get-items-by-category/{category_id}', 'ItemController@get_items_by_category')->name('get-items-by-category');
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
            Route::get('search-vendors', 'LeadController@search_vendors')->name('search-vendors');
            Route::get('search-clients', 'LeadController@search_clients')->name('search-clients');
            Route::get('search-services', 'LeadController@search_services')->name('search-services');
            Route::get('search-zones', 'LeadController@search_zones')->name('search-zones');
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
            Route::delete('delete-subscription/{id}', 'VendorController@delete_subscription')->name('delete-subscription');
            Route::post('buy-module', 'VendorController@buyModule')->name('buy-module');
            Route::post('buy-plan-store', 'VendorController@buy_plan_for_store')->name('buy-plan-store');
            Route::post('edit-plan-store', 'VendorController@edit_plan_for_store')->name('edit-plan-store');
            Route::get('buy-module-store', 'VendorController@buy_module_for_store')->name('module-store');
            Route::get('all-plans', 'VendorController@allPlans')->name('all-plans');
            // ===============================

            Route::get('add-new/{type?}/{req_id?}', 'PlanController@index')->name('add-new');
            Route::get('status/{id}/{status}', 'PlanController@status')->name('status');
            Route::get('list', 'PlanController@list')->name('list');
            Route::get('requests', 'PlanController@plan_requests')->name('requests');
            Route::get('module-pricing', 'PlanController@module_pricing')->name('module-pricing');
            Route::post('update-modules', 'VendorController@update_modules')->name('update-modules');
            Route::post('duration/store', 'VendorController@store_duration')->name('duration.store');
            Route::post('duration/update/{id}', 'VendorController@update_duration')->name('duration.update');
            Route::get('duration/delete/{id}', 'VendorController@delete_duration')->name('duration.delete');
            Route::post('duration/toggle/{id}', 'VendorController@toggle_duration')->name('duration.toggle');
            Route::post('gst-settings', 'PlanController@save_gst_settings')->name('gst-settings');
            Route::get('stores', 'PlanController@stores')->name('stores');
            Route::post('store', 'PlanController@store')->name('store');
        }); 
        Route::group(['prefix' => 'ticket', 'as' => 'ticket.'], function () {
            Route::get('/', 'SupportTicketController@index')->name('index')->middleware('permission:support_ticket,list');
            Route::get('create', 'SupportTicketController@create')->name('create')->middleware('permission:support_ticket,add');
            Route::post('store', 'SupportTicketController@store')->name('store')->middleware('permission:support_ticket,add');
            Route::get('show/{id}', 'SupportTicketController@show')->name('show')->middleware('permission:support_ticket,view');
            Route::post('reply/{id}', 'SupportTicketController@reply')->name('reply')->middleware('permission:support_ticket,reply');
            Route::post('status/{id}', 'SupportTicketController@updateStatus')->name('status')->middleware('permission:support_ticket,status');
            Route::post('assign/{id}', 'SupportTicketController@assign')->name('assign')->middleware('permission:support_ticket,assign');
            Route::delete('delete/{id}', 'SupportTicketController@delete')->name('delete')->middleware('permission:support_ticket,delete');
            Route::get('search-customers', 'SupportTicketController@searchCustomers')->name('search-customers');
            Route::get('search-vendors', 'SupportTicketController@searchVendors')->name('search-vendors');
        });
        // Route::group(['prefix' => 'account', 'as' => 'account.', 'middleware' => ['module:account']], function () {
        //     Route::get('report', 'AccountController@report')->name('report');
        //     Route::get('list', 'AccountController@index')->name('list');
        //     Route::get('add', 'AccountController@add')->name('add');
        //     Route::post('save-info', 'AccountController@save_info')->name('save');
        //     Route::get('delete/{id}', 'AccountController@delete')->name('delete');
        //     Route::get('edit/{id}', 'AccountController@edit')->name('edit');
        // });
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

        //  ============================= ACCOUNT MANAGEMENT =================================
        Route::group(['prefix' => 'account', 'as' => 'account.', 'middleware' => ['planwise:account_manage']], function () {
            Route::post('reset_accounts_module', 'AccountController@reset_accounts_module')->name('reset_accounts_module')->middleware('permission:settings_common,reset');
            Route::post('send_otp', 'AccountController@send_otp')->name('send_otp');
            Route::post('fetchEmployees', 'AccountController@fetchEmployees')->name('fetchEmployees');
            Route::get('management/{tab?}', 'AccountController@add')->name('add')->middleware('permission:boa_master_ledger,add');
            Route::get('status/{id}/{status}', 'AccountController@status')->name('status');
            Route::post('save-info', 'AccountController@save_info')->name('save');
            Route::get('delete/{id}', 'AccountController@delete')->name('delete');
            Route::get('edit/{id}', 'AccountController@edit')->name('edit');
            Route::get('mark-as-paid/{id}', 'AccountController@mark_as_paid')->name('mark-as-paid');
            Route::get('list', 'AccountController@index')->name('list');
            Route::get('approvals', 'AccountRequestFormController@approvals')->name('approvals');
            Route::post('request_rule_store', 'AccountRequestFormController@request_rule_store')->name('request_rule_store')->middleware('permission:approvals,add');
            Route::get('request_rule_delete/{id}', 'AccountRequestFormController@request_rule_delete')->name('request_rule_delete')->middleware('permission:approvals,delete');
            Route::get('request_rule_edit/{id}', 'AccountRequestFormController@request_rule_edit')->name('request_rule_edit')->middleware('permission:approvals,edit');
            Route::post('request_rule_update', 'AccountRequestFormController@request_rule_update')->name('request_rule_update')->middleware('permission:approvals,edit');
            Route::post('forward_permission_store', 'AccountRequestFormController@forward_permission_store')->name('forward_permission_store')->middleware('permission:approvals,add');
            Route::get('test-notif', 'AccountController@send_push_notif_to_device')->name('send_push_notif_to_device');
            Route::get('setting', 'AccountController@setting')->name('setting')->middleware('permission:settings_account_type,edit');
            Route::post('ledger_account_type-store', 'AccountController@ledger_account_type_store')->name('ledger_account_type.store');
            Route::post('category-store', 'AccountController@category_store')->name('category.store');
            Route::get('dashboard', 'AccountController@dashboard')->name('dashboard')->middleware('permission:dashboard,view');
            Route::get('report', 'AccountController@report')->name('report');


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


            Route::group(['prefix' => 'report', 'as' => 'report.'], function () {
                Route::get('tax/{action?}', 'AccountReportController@tax')->name('tax')->middleware('permission:reports_tax_report,list');
                Route::get('audit-logs/{action?}', 'AccountReportController@audit_logs')->name('audit-logs')->middleware('permission:reports_audit_logs,list');
            });

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
        Route::resource('task-salary-categories', TaskSalaryCategoryController::class);

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

        // GALLERY 

        Route::group(['prefix' => 'gallery', 'as' => 'gallery.'], function () {
            // Route::get('/', 'DashboardController@gallery')->name('all');
            // Route::post('store', 'DashboardController@gallery_store')->name('store');
            // Route::post('delete', 'DashboardController@gallery_delete')->name('delete');
            Route::post('bulk-delete', 'DashboardController@gallery_bulk_delete')->name('bulk-delete');
        });
        // INVENTORY 
        Route::post('inventory/get-item-info', 'InventoryController@get_item_info')->name('inventory.get-item-info');
        Route::group(['prefix' => 'inventory', 'as' => 'inventory.', 'middleware' => ['planwise:inventory_manage']], function () {
            Route::get('settings', 'InventoryController@settings')->name('settings')->middleware('permission:inventory,settings');
            Route::post('settings-save', 'InventoryController@settings_save')->name('settings-save')->middleware('permission:inventory,settings_save');
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
                Route::get('scan-barcode', 'InventoryController@scan_barcode')->name('scan-barcode');
                Route::post('fetch-by-sku', 'InventoryController@fetch_item_by_sku')->name('fetch-by-sku');
                Route::post('update-variant-combination', 'InventoryController@update_variant_combination')->name('update-variant-combination');
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
            });
            Route::group(['prefix' => 'report', 'as' => 'report.'], function () {
                Route::get('gst/{export?}/{file_type?}', 'InventoryReportController@gst')->name('gst');
                Route::get('sale/{export?}/{file_type?}', 'InventoryReportController@sale')->name('sale');
                Route::get('profit-and-loss/{export?}/{file_type?}', 'InventoryReportController@profit_and_loss')->name('profit-and-loss');
                Route::get('purchase/{export?}/{file_type?}', 'InventoryReportController@purchase')->name('purchase');
                Route::get('stock/{export?}/{file_type?}', 'InventoryReportController@stock')->name('stock');
            });
            Route::group(['prefix' => 'category', 'as' => 'category.'], function () {
                Route::get('/', 'InventoryController@category')->name('index');
                Route::post('store', 'InventoryController@storage_spaces_store')->name('store');
                Route::post('update', 'InventoryController@storage_spaces_update')->name('update');
                Route::post('delete/{id}', 'InventoryController@storage_spaces_delete')->name('delete');
            });
        });

        Route::post('inventory/get-item-info', 'InventoryController@get_item_info')->name('inventory.get-item-info');

     
        Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.'], function () {
            Route::post('signature-save', 'BusinessSettingsController@signature_save')->name('signature.save')->middleware('permission:billing_signatures,add');;
            Route::get('signature-delete/{id}', 'BusinessSettingsController@signature_delete')->name('signature.delete')->middleware('permission:billing_signatures,delete');;
            Route::post('signature-fetch', 'BusinessSettingsController@signature_fetch')->name('signature.fetch');
            Route::post('new-bank-account', 'BusinessSettingsController@new_bank_account')->name('new-bank-account')->middleware('permission:billing_bank_account,add');;
            Route::get('delete-account/{id}', 'BusinessSettingsController@delete_account')->name('delete-account')->middleware('permission:billing_bank_account,delete');;
            Route::post('config-save', 'BusinessSettingsController@config_save')->name('config.save');
            Route::post('tnc-fetch/{id}', 'BusinessSettingsController@tnc_fetch')->name('tnc.fetch');
            Route::post('tnc-save', 'BusinessSettingsController@tnc_save')->name('tnc.save')->middleware('permission:billing_tnc,add');
            Route::get('tnc-delete/{id}', 'BusinessSettingsController@tnc_delete')->name('tnc.delete')->middleware('permission:billing_tnc,delete');
            Route::post('tnc-update', 'BusinessSettingsController@tnc_update')->name('tnc.update')->middleware('permission:billing_tnc,edit');
            Route::post('terms-and-conditions-save', 'BusinessSettingsController@terms_and_conditions_save')->name('terms-and-conditions.save');
        });

        // QUOTATION MANAGEMENT 
        Route::group(['prefix' => 'quotation', 'as' => 'quotation.', 'middleware' => ['planwise:quotaiton_manage']], function () {
            Route::get('convert-to-bill/{id}', 'QuoteController@convert_to_bill')->name('convert-to-bill')->middleware('permission:quotaiton_manage,convert_to_bill');
            Route::get('list', 'QuoteController@index')->name('list');
            Route::get('send-quote/{id}', 'QuoteController@send_quote')->name('send-quote');
            Route::post('send-quote-email', 'QuoteController@send_quote_email')->name('send-quote-email');
            Route::get('check-email', 'QuoteController@check_email')->name('check-email');
            Route::get('quotation_number_validation', 'QuoteController@quotation_number_validation')->name('quotation_number_validation')->middleware('permission:quotaiton_manage,add');
            Route::get('add', 'QuoteController@add')->name('add')->middleware('permission:quotaiton_manage,add');
            Route::post('status-change', 'QuoteController@status_change')->name('status-change')->middleware('permission:quotaiton_manage,status_change');
            Route::post('save-info', 'QuoteController@save_info')->name('save-info')->middleware('permission:quotaiton_manage,add');
            Route::post('lead_approval', 'QuoteController@lead_approval')->name('lead_approval');
            Route::get('delete/{id}', 'QuoteController@delete')->name('delete')->middleware('permission:quotaiton_manage,delete');
            Route::get('manage/{id}', 'QuoteController@manage')->name('manage')->middleware('permission:quotaiton_manage,edit');
            Route::get('settings', 'SettingsController@quotation_settings')->name('settings')->middleware('permission:quotaiton_manage,settings');

            Route::post('config-save', 'BusinessSettingsController@config_save')->name('config.save');
            Route::post('signature-save', 'BusinessSettingsController@signature_save')->name('signature.save')->middleware('permission:quotation_sign,add');
            Route::get('signature-delete/{id}', 'BusinessSettingsController@signature_delete')->name('signature.delete')->middleware('permission:quotation_sign,delete');
            Route::post('signature-fetch', 'BusinessSettingsController@signature_fetch')->name('signature.fetch')->middleware('permission:quotation_sign,list');
            Route::post('new-bank-account', 'BusinessSettingsController@new_bank_account')->name('new-bank-account')->middleware('permission:quotation_bank_account,add');;
            Route::get('delete-account/{id}', 'BusinessSettingsController@delete_account')->name('delete-account')->middleware('permission:quotation_bank_account,delete');;
        });

        // LIBRARY 
        Route::group(['prefix' => 'library', 'as' => 'library.'], function () {
            Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
                Route::get('add/{type}', 'DocumentsController@add_gatepass')->name('add');
                Route::post('store', 'DocumentsController@store_gatepass')->name('store');
            });
        });

        //DOCUMENTS 
        Route::group(['prefix' => 'documents', 'as' => 'documents.', 'middleware' => ['module:documents']], function () {
            Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
                Route::get('list/{tab}', 'InventoryGatepassController@gatepass_list')->name('list');
                Route::get('delete/{id}', 'InventoryGatepassController@gatepass_delete')->name('delete');
            });
        });

        // NEW BILLING  
        Route::group(['prefix' => 'billing', 'as' => 'billing.', 'middleware' => ['module:billing']], function () {
            Route::get('manual-bill', 'BillingController@manual_bill')->name('manual-bill');
            Route::post('mark-paid2', 'ServiceController@mark_paid2')->name('mark-paid2');
            Route::get('reminder/{type}/{status}/{id}', 'ServiceController@reminder_status')->name('reminder')->middleware('permission:billing,reminder_update');

            Route::get('create-invoice', 'BillingController@create_invoice')->name('create-invoice');
            Route::get('list', 'BillingController@invoice_list')->name('list');
            Route::get('purchase-bills', 'BillingController@my_bills')->name('my-bills');
            Route::get('settings', 'BillingController@invoice_settings')->name('settings');
            Route::post('validate-invoicenum', 'BillingController@validate_invoicenum')->name('validate-invoicenum');
            Route::get('edit/{id}', 'BillingController@edit')->name('edit');
            Route::post('update-invoice', 'BillingController@update_invoice')->name('update-invoice');
            Route::post('service-update-invoice', 'BillingController@service_update_invoice')->name('service-update-invoice');
            Route::get('edit-service-invoice/{id}', 'BillingController@edit_service_invoice')->name('edit-service-invoice');
            Route::post('save-new-manual-invoice', 'BillingController@save_new_manual_invoice')->name('save-new-manual-invoice');
            Route::post('import-sheet', 'BillingController@importInvoiceSheet')->name('import-sheet');
            Route::post('export', 'BillingController@exportInvoiceSheet')->name('export');
            Route::post('purchase-invoice/save', 'BillingController@save_purchase_invoice')->name('save-purchase-invoice');
            Route::post('purchase-invoice/import', 'BillingController@importPurchaseInvoices')->name('import-purchase-invoices');
            Route::get('purchase-bill/search-bill-from', 'BillingController@search_bill_from')->name('purchase-bill.search-bill-from');
            Route::get('pay-bill/{id}', 'BillingController@pay_bill')->name('pay-bill');
            Route::get('make-payment/{id}', 'BillingController@make_payment')->name('make-payment');
            Route::get('delete/{type}/{id}', 'BillingController@delete')->name('delete');
            Route::get('view-invoice/{id}', 'BillingController@view_invoice')->name('view-invoice');
            Route::get('get-invoices-by-vendor', 'BillingController@get_invoices_by_vendor')->name('get-invoices-by-vendor');
            Route::post('delete-row', 'ServiceController@delete_row')->name('delete-row');
            Route::get('view-invoice/{type}/{invoice_id}', 'BillingController@manual_invoice_view')->name('manual-invoice-view')->middleware('permission:billing,view');

            // PURCHASE INVOICE
            Route::group(['prefix' => 'purchase-invoice', 'as' => 'purchase-invoice.'], function () {
                Route::post('save', 'BillingController@save_purchase_invoice')->name('save')->middleware('permission:purchase_bill,add');
                Route::post('import', 'BillingController@importPurchaseInvoices')->name('import')->middleware('permission:purchase_bill,import');
            });
        });
        Route::get('search-mychitti-clients', 'MychittiClientController@searchMychittiClients')->name('search-mychitti-clients');
        
        Route::group(['prefix' => 'client', 'as' => 'client.'], function () {
            Route::post('fetch-details', 'CustomerController@fetch_details')->name('fetch-details');
            Route::get('get-matches', 'CustomerController@get_matches')->name('get-matches');
        });
        Route::group(['prefix' => 'client', 'as' => 'client.', 'middleware' => ['module:client_manage']], function () {
            Route::get('list/{tab?}', 'MychittiClientController@list')->name('list');
            Route::get('add-new', 'MychittiClientController@add_new')->name('add');
            Route::post('save', 'MychittiClientController@save')->name('save');
            Route::get('edit/{id}', 'MychittiClientController@edit')->name('edit');
            Route::post('update', 'MychittiClientController@update')->name('update');
            Route::get('view/{id}', 'MychittiClientController@view')->name('view');
            Route::post('delete/{id}', 'MychittiClientController@delete')->name('delete');
            Route::post('bulk-delete', 'MychittiClientController@bulk_delete')->name('bulk-delete');
            Route::get('export', 'MychittiClientController@export')->name('export');
            Route::post('upload-excel', 'MychittiClientController@upload_excel')->name('upload-excel');
            Route::post('comment-save', 'MychittiClientController@comment_save')->name('comment-save');
            // Route::post('fetch-details', 'MychittiClientController@fetch_details')->name('fetch-details');
        });
        Route::group(['prefix' => 'billing', 'as' => 'billing.', 'middleware' => ['module:billing']], function () {
            Route::get('/', 'BillingController@billing')->name('index');
            Route::post('save-manual-invoice', 'BillingController@save_manual_invoice')->name('save-manual-invoice');
            Route::get('test-invoice', 'BillingController@test_invoice')->name('test-invoice');
            Route::get('invoice-view/{id}', 'BillingController@invoice_view')->name('invoice-view');
            Route::get('invoice-delete/{type}/{id}', 'BillingController@invoice_delete')->name('invoice-delete');
            Route::post('invoice-bulk-delete', 'BillingController@invoice_bulk_delete')->name('invoice-bulk-delete');
            Route::post('invoice-correction', 'BillingController@invoice_correction')->name('invoice-correction');
        });

        // POS ======================================
        Route::group(['prefix' => 'pos', 'as' => 'pos.', 'middleware' => ['module:pos']], function () {
            Route::get('report/{action?}', 'SalespointController@report')->name('report')->middleware('permission:pos,report');

            Route::get('calendar-export', 'SalespointController@calendar_export')->name('calendar-export')->middleware('permission:pos,report');
        });

        // Store Monetization
        Route::group(['prefix' => 'store-monetization', 'as' => 'store-monetization.'], function () {
            Route::get('/', 'StoreMonetizationController@dashboard')->name('dashboard');
            Route::get('store/{id}', 'StoreMonetizationController@storeDetail')->name('store-detail');
        });

        Route::get('maintenance-mode', 'SystemController@maintenance_mode')->name('maintenance-mode');
        Route::get('landing-page', 'SystemController@landing_page')->name('landing-page');


        // =================== HR MANAGEMENT ======================================       
        Route::get('hr/dashboard', 'HRController@dashboard')->name('hr.dashboard')->middleware('permission:hr_manage,dashboard');;

        // STAFF 
        Route::group(['prefix' => 'staff', 'as' => 'staff.', 'middleware' => ['planwise:hr_manage']], function () {
            //     Route::get('edit/{id}', 'EmployeeController@edit')->name('edit')->middleware('permission:staff_manage,edit');
            //     Route::delete('delete/{id}', 'EmployeeController@distroy')->name('delete')->middleware('permission:staff_manage,delete');
            //     Route::get('add-new', 'EmployeeController@add_new')->name('add-new')->middleware('permission:staff_manage,add');
            //     Route::post('save-info', 'StaffController@save_info')->name('save')->middleware('permission:staff_manage,add');
            //     Route::get('add', 'StaffController@add')->name('add')->middleware('permission:staff_manage,add');
            //     Route::get('list', 'EmployeeController@list')->name('list');
            Route::get('settings', 'StaffController@settings')->name('settings')->middleware('permission:staff_manage,settings');
            Route::post('save-settings', 'StaffController@save_settings')->name('settings.save')->middleware('permission:staff_manage,settings');
            //     Route::get('status/{id}/{status}', 'StaffController@status')->name('status')->middleware('permission:staff_manage,status_change');
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
        // STAFF DEPARTMENT 
        Route::group(['prefix' => 'staff-department', 'as' => 'staff-department.'], function () {
            Route::get('/', 'StaffController@departments')->name('all');
            Route::post('save', 'StaffController@store_department')->name('save')->middleware('permission:staff_department,add');
            Route::get('d-delete/{id}', 'StaffController@delete_department')->name('delete')->middleware('permission:staff_department,delete');
            Route::post('status-change', 'StaffController@status_change')->name('status-change')->middleware('permission:staff_department,status_change');
        });
        // SHIFTS 
        Route::group(['prefix' => 'shifts', 'as' => 'shifts.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('/', 'ShiftController@index')->name('index');
            Route::post('store', 'ShiftController@store')->name('store')->middleware('permission:shift_manage,add');
            Route::get('delete/{id}', 'ShiftController@delete')->name('delete')->middleware('permission:shift_manage,delete');
            Route::post('update', 'ShiftController@update')->name('update')->middleware('permission:shift_manage,edit');
        });
        // ATTENDANCE 
        Route::group(['prefix' => 'attendance', 'as' => 'attendance.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('report', 'AttendanceController@report')->name('report');
            Route::get('export', 'AttendanceController@export')->name('export')->middleware('permission:attendance_report,export');
            Route::get('list', 'AttendanceController@index')->name('all')->middleware('permission:attendance_manage,list');
            Route::post('save', 'AttendanceController@save_att')->name('save')->middleware('permission:attendance_manage,edit');
            Route::get('manage/{id}', 'AttendanceController@manage')->name('manage')->middleware('permission:attendance_manage,view');
        });
        // EMPLOYEE 
        Route::group(['prefix' => 'employee', 'as' => 'employee.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::post('comment-save', 'Employee\EmployeeController@comment_save')->name('comment-save')->middleware('permission:staff_manage,comment');
            Route::get('view/{id}', 'Employee\EmployeeController@view')->name('view')->middleware('permission:staff_manage,view');
            Route::get('view-id-card/{id}', 'Employee\EmployeeController@view_id_card')->name('view-id-card')->middleware('permission:staff_manage,view');
            Route::post('resign/{id}', 'Employee\EmployeeController@resign')->name('resign')->middleware('permission:staff_manage,resignation');
            Route::get('terminate/{id}', 'Employee\EmployeeController@terminate')->name('terminate')->middleware('permission:staff_manage,terminate');
            Route::get('resignation-action/{id}/{action}', 'Employee\EmployeeController@resignation_action')->name('resignation-action')->middleware('permission:staff_manage,resignation');
            Route::get('timecards/{id}', 'Employee\EmployeeController@timecards')->name('timecards')->middleware('permission:staff_manage,view');
        });
        Route::get('employee/clock-in', 'Employee\EmployeeController@clock_in')->name('employee.clockin');
        Route::get('employee/clock-out', 'Employee\EmployeeController@clock_out')->name('employee.clockout');

        // SALARY MANAGMENT      
        Route::group(['prefix' => 'salary', 'as' => 'salary.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('generate-monthly/{month}', 'SalaryController@generate_monthly')->name('generate-monthly')->middleware('permission:salary_manage,generate');
            Route::get('mark-paid/{month}', 'SalaryController@mark_paid')->name('mark-paid')->middleware('permission:salary_manage,mark_paid');
            Route::get('report', 'SalaryController@report')->name('report');
            Route::get('export-salaries', 'SalaryController@export_salaries')->name('export-salaries')->middleware('permission:salary_manage,export');
            Route::get('list', 'SalaryController@index')->name('list');
            Route::get('export', 'SalaryController@export')->name('export')->middleware('permission:salary_manage,export');
            Route::post('get-info', 'SalaryController@get_info')->name('get-info');
            Route::post('pay', 'SalaryController@pay')->name('pay')->middleware('permission:salary_manage,mark_paid');
            Route::get('add', 'SalaryController@add')->name('add-new')->middleware('permission:salary_manage,add');
            Route::get('status/{id}/{status}', 'SalaryController@status')->name('status')->middleware('permission:salary_manage,status_change');
            Route::post('save-info', 'SalaryController@save_info')->name('save')->middleware('permission:salary_manage,edit');
            Route::get('delete/{id}', 'SalaryController@delete')->name('delete')->middleware('permission:salary_manage,delete');
            Route::get('edit/{id}', 'SalaryController@edit')->name('edit')->middleware('permission:salary_manage,edit');

            Route::get('all-advance-requests', 'SalaryController@all_advance_requests')->name('all-advance-requests')->middleware('permission:advance_requests,list');
            Route::get('approve-advance/{id}', 'SalaryController@approve_advance_payment')->name('approve-advance')->middleware('permission:advance_requests,approve');
            Route::get('reject-advance/{id}', 'SalaryController@reject_advance_payment')->name('reject-advance')->middleware('permission:advance_requests,reject');
            Route::post('advance-request/store', 'SalaryController@advance_request_store')->name('advance-request.store');
        });
        Route::get('advance-payment', 'SalaryController@advance_payment')->name('advance-payment');
        Route::get('salary-history', 'SalaryController@my_salary_history')->name('salary-history');

        // LEAVE MANAGEMENT 
        Route::group(['prefix' => 'leave', 'as' => 'leave.', 'middleware' => ['planwise:hr_manage']], function () {
            Route::get('list', 'LeaveController@index')->name('all');
            Route::get('add', 'LeaveController@add')->name('add-new')->middleware('permission:leave_manage,add');
            Route::get('status/{id}/{status}', 'LeaveController@status')->name('status')->middleware('permission:leave_manage,status_change');
            Route::post('save-info', 'LeaveController@save_info')->name('save-info')->middleware('permission:leave_manage,add');
            Route::post('save', 'LeaveController@save_leave')->name('save')->middleware('permission:leave_manage,add');
            Route::get('manage/{id}', 'LeaveController@manage')->name('manage');
        });

        // HOLIDAYS
        Route::group(['prefix' => 'holidays', 'as' => 'holidays.'], function () {
            Route::get('/', 'SettingsController@holiday_settings')->name('index');
            Route::get('add', 'SettingsController@holiday_add')->name('add');
            Route::post('update', 'SettingsController@holiday_update')->name('update');
            Route::get('delete/{id}', 'SettingsController@holiday_delete')->name('delete');
        });
        Route::group(['prefix' => 'jobcard', 'as' => 'jobcard.'], function () {
            Route::post('view', 'LibraryController@job_card')->name('view'); // task, view/generate , id
        });
        Route::group(['prefix' => 'form-builder', 'as' => 'form-builder.'], function () {

            Route::post('save-form', 'FormBuilderController@saveFields')->name('save-form');
            Route::get('get-form/{form_type?}', 'FormBuilderController@getForm')->name('get-form');
        });
        // DOCUMENTS  ===================================
        Route::group(['prefix' => 'documents', 'as' => 'documents.', 'middleware' => ['module:documents']], function () {
            // Route::group(['prefix' => 'gatepass', 'as' => 'gatepass.'], function () {
            //     Route::get('list/{tab}', 'InventoryGatepassController@gatepass_list')->name('list');
            //     Route::get('delete/{id}', 'InventoryGatepassController@gatepass_delete')->name('delete');
            // });
            // Route::group(['prefix' => 'receivable-receipt', 'as' => 'receivable-receipt.'], function () {
            //     Route::get('create', 'LibraryController@recievable_reciept_create')->name('create');
            //     Route::post('preview', 'LibraryController@recievable_reciept')->name('preview');
            //     Route::post('store/{action?}/{task_id?}', 'LibraryController@recievable_store')->name('store');
            //     Route::get('list', 'DocumentsController@recievable_reciepts_list')->name('list');
            //     Route::get('delete/{id}', 'DocumentsController@recievable_reciepts_delete')->name('delete');
            // });
            Route::group(['prefix' => 'job-card', 'as' => 'job-card.'], function () {
                // Route::get('create', 'LibraryController@jobcard_create')->name('create');
                Route::post('store/{action?}/{task_id?}', 'LibraryController@job_card_store')->name('store');
                // Route::get('list', 'DocumentsController@job_cards_list')->name('list');
                // Route::get('delete/{id}', 'DocumentsController@job_card_delete')->name('delete');
            });
             Route::group(['prefix' => 'service-report', 'as' => 'service-report.'], function () {
                // Route::get('create', 'DocumentsController@jobcard_create')->name('create');
                Route::post('store/{action?}/{task_id?}', 'DocumentsController@service_report_store')->name('store');
                // Route::get('list', 'DocumentsController@job_cards_list')->name('list');
                // Route::get('delete/{id}', 'DocumentsController@job_card_delete')->name('delete');
            }); 
        });

        // PROJECT MANAGEMENT ===========================
        Route::group(['prefix' => 'project', 'as' => 'project.', 'middleware' => ['planwise:projects_manage']], function () {
            Route::get('details/{id}/{tab?}', 'ProjectController@details')->name('details')->middleware('permission:project,view');
            Route::get('settings', 'ProjectController@settings')->name('settings')->middleware('permission:project,settings');
            Route::get('setting-update', 'ProjectController@setting_update')->name('setting.update')->middleware('permission:project,settings');

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
        Route::post('documents/receivable-receipt/store/{action?}/{task_id?}', 'LibraryController@recievable_store')->name('documents.receivable-receipt.store-lead'); // for task receivable receipt save without module:documents check because it is used in task details page
        Route::post('documents/service-report/store/{action?}/{task_id?}', 'DocumentsController@service_report_store')->name('documents.service-report.store-lead'); // for task service report save without module:documents check because it is used in task details page
        Route::post('quotation/save-info/{id}', 'QuoteController@save_info')->name('quotation.save-info-task'); // for lead quotation save without permission check because it is used in lead details page and we have given access to view lead details for some roles who don't have permission to manage quotation
        Route::post('billing/save-manual-invoice/{id}', 'ServiceController@save_manual_invoice')->name('billing.save-manual-invoice-lead'); // for task invoice save without permission check because it is used in task details page and we have given access to view task details for some roles who don't have permission to manage billing
        Route::group(['prefix' => 'task', 'as' => 'task.'], function () {
            Route::get('assigned-tasks', 'TaskController@assigned_tasks')->name('assigned_tasks');
            Route::post('otp-send', 'TaskController@task_otp_send')->name('job-otp-verify');
            Route::get('accept/{id}', 'TaskController@accept')->name('accept');
            Route::get('reject/{id}', 'TaskController@reject')->name('reject');
        });

         // TASK CONTROLLER
        Route::group(['prefix' => 'task', 'as' => 'task.'], function () { // add middleware
            // Route::get('add/{project_id?}', 'ProjectTaskController@add')->name('add')->middleware('permission:project_task,add');
            // Route::post('store', 'ProjectTaskController@store')->name('store')->middleware('permission:project_task,add');
            // Route::get('edit/{id}', 'ProjectTaskController@edit')->name('edit')->middleware('permission:project_task,edit');
            // Route::post('update', 'ProjectTaskController@update')->name('update')->middleware('permission:project_task,edit');
            // Route::post('reassign', 'ProjectTaskController@reassign')->name('reassign')->middleware('permission:project_task,edit');
            Route::get('list/{id?}', 'ProjectController@task_list')->name('list');
            // Route::get('detail/{id}', 'ProjectTaskController@detail')->name('detail')->middleware('permission:project_task,view');
            // Route::post('delete/{id}', 'ProjectTaskController@delete')->name('delete')->middleware('permission:project_task,delete');
            // Route::post('save-progress', 'ProjectTaskController@save_progress')->name('save-progress');
            // Route::post('status-update', 'ProjectTaskController@status_update')->name('status.update')->middleware('permission:project_task,status_change');
            // Route::post('status-new', 'ProjectTaskController@status_new_save')->name('status.save-new');
            // Route::get('export', 'ProjectTaskController@export')->name('export')->middleware('permission:project_task,export');
            // Route::get('setting', 'ProjectTaskController@setting')->name('setting')->middleware('permission:project_task,settings');
        });


        
        // TASK MANAGEMENT =============================
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
            Route::group(['prefix' => 'terms-and-conditions', 'as' => 'terms-and-conditions.', 'middleware' => ['module:item']], function () {
                Route::get('/', 'ItemController@terms_and_condtions')->name('index');
                Route::post('/', 'ItemController@terms_and_condtions_store')->name('store');
            });
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
                Route::get('activation-plan/{store}', 'VendorController@activationPlan')->name('activation-plan');
                Route::get('pending-requests', 'VendorController@pending_requests')->name('pending-requests');
                Route::get('deny-requests', 'VendorController@deny_requests')->name('deny-requests');
                Route::get('removal-requests', 'VendorController@removal_requests')->name('removal-requests');
                Route::post('removal-request-action/{id}', 'VendorController@removal_request_action')->name('removal-request-action');
                Route::post('search', 'VendorController@search')->name('search');
                Route::get('duplicates', 'VendorController@duplicates')->name('duplicates');
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

                // Inactive vendor management
                Route::get('inactive', 'VendorController@inactive_vendors')->name('inactive');
                Route::post('notify-inactive/{store_id}', 'VendorController@notify_inactive_vendor')->name('notify-inactive');
                Route::delete('delete-inactive/{store_id}', 'VendorController@delete_inactive_vendor')->name('delete-inactive');


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
            Route::post('edit-leaves', 'BusinessSettingsController@edit_leaves')->name('edit-leaves');
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

            Route::group(['prefix' => 'attendance', 'as' => 'attendance.', 'middleware' => ['planwise:hr_manage']], function () {
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

            Route::group(['prefix' => 'leave', 'as' => 'leave.', 'middleware' => ['planwise:hr_manage']], function () {
                Route::get('list', 'LeaveController@index')->name('all');
                Route::get('add', 'LeaveController@add')->name('add-new');
                Route::get('status/{id}/{status}', 'LeaveController@status')->name('status');
                Route::post('save-info', 'LeaveController@save_info')->name('save-info');
                Route::post('save', 'LeaveController@save_leave')->name('save');

                Route::get('delete/{id}', 'AttendanceController@delete')->name('delete');
                Route::get('manage/{id}', 'LeaveController@manage')->name('manage');
            });
            // Route::group(['prefix' => 'salary', 'as' => 'salary.', 'middleware' => ['planwise:hr_manage']], function () {
            //     Route::get('list', 'SalaryController@index')->name('list');
            //     Route::post('get-info', 'SalaryController@get_info')->name('get-info');

            //     Route::get('add', 'SalaryController@add')->name('add-new');
            //     Route::get('status/{id}/{status}', 'SalaryController@status')->name('status');
            //     Route::post('save-info', 'SalaryController@save_info')->name('save');
            //     Route::get('delete/{id}', 'SalaryController@delete')->name('delete');
            //     Route::get('edit/{id}', 'SalaryController@edit')->name('edit');
            // });

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

        Route::group(['prefix' => 'employee-department', 'as' => 'employee-department.'], function () {
            Route::get('/', 'EmployeeController@departments')->name('all');
            Route::post('save', 'EmployeeController@store_department')->name('save');
            Route::get('d-delete/{id}', 'EmployeeController@delete_department')->name('delete');
            Route::post('status-change', 'EmployeeController@department_status_change')->name('status-change');
        });
    });
});
