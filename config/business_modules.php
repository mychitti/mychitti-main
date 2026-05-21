<?php

return [

    'laundry' => [
        'controllers' => [
            \App\Http\Controllers\Vendor\AccountController::class
                => \App\Modules\Laundry\Controllers\Vendor\AccountController::class,

            \App\Http\Controllers\Vendor\AccountReportController::class
                => \App\Modules\Laundry\Controllers\Vendor\AccountReportController::class,

            \App\Http\Controllers\Vendor\AccountRequestFormController::class
                => \App\Modules\Laundry\Controllers\Vendor\AccountRequestFormController::class,

            \App\Http\Controllers\Vendor\AccountSettingController::class
                => \App\Modules\Laundry\Controllers\Vendor\AccountSettingController::class,

            \App\Http\Controllers\Vendor\AccountStatementController::class
                => \App\Modules\Laundry\Controllers\Vendor\AccountStatementController::class,

            \App\Http\Controllers\Vendor\AddOnController::class
                => \App\Modules\Laundry\Controllers\Vendor\AddOnController::class,

            \App\Http\Controllers\Vendor\AIChatController::class
                => \App\Modules\Laundry\Controllers\Vendor\AIChatController::class,

            \App\Http\Controllers\Vendor\AnalyticsController::class
                => \App\Modules\Laundry\Controllers\Vendor\AnalyticsController::class,

            \App\Http\Controllers\Vendor\AssetsController::class
                => \App\Modules\Laundry\Controllers\Vendor\AssetsController::class,

            \App\Http\Controllers\Vendor\AttendanceController::class
                => \App\Modules\Laundry\Controllers\Vendor\AttendanceController::class,

            \App\Http\Controllers\Vendor\AttendanceExport::class
                => \App\Modules\Laundry\Controllers\Vendor\AttendanceExport::class,

            \App\Http\Controllers\Vendor\BankAccountController::class
                => \App\Modules\Laundry\Controllers\Vendor\BankAccountController::class,

            \App\Http\Controllers\Vendor\BankingController::class
                => \App\Modules\Laundry\Controllers\Vendor\BankingController::class,

            \App\Http\Controllers\Vendor\BankReconciliationController::class
                => \App\Modules\Laundry\Controllers\Vendor\BankReconciliationController::class,

            \App\Http\Controllers\Vendor\BannerController::class
                => \App\Modules\Laundry\Controllers\Vendor\BannerController::class,

            \App\Http\Controllers\Vendor\BasicStaffController::class
                => \App\Modules\Laundry\Controllers\Vendor\BasicStaffController::class,

            \App\Http\Controllers\Vendor\BillingController::class
                => \App\Modules\Laundry\Controllers\Vendor\BillingController::class,

            \App\Http\Controllers\Vendor\BranchController::class
                => \App\Modules\Laundry\Controllers\Vendor\BranchController::class,

            \App\Http\Controllers\Vendor\BusinessSettingsController::class
                => \App\Modules\Laundry\Controllers\Vendor\BusinessSettingsController::class,

            \App\Http\Controllers\Vendor\CampaignController::class
                => \App\Modules\Laundry\Controllers\Vendor\CampaignController::class,

            \App\Http\Controllers\Vendor\CashBookController::class
                => \App\Modules\Laundry\Controllers\Vendor\CashBookController::class,

            \App\Http\Controllers\Vendor\CategoryController::class
                => \App\Modules\Laundry\Controllers\Vendor\CategoryController::class,

            \App\Http\Controllers\Vendor\ConversationController::class
                => \App\Modules\Laundry\Controllers\Vendor\ConversationController::class,

            \App\Http\Controllers\Vendor\CouponController::class
                => \App\Modules\Laundry\Controllers\Vendor\CouponController::class,

            \App\Http\Controllers\Vendor\CustomerController::class
                => \App\Modules\Laundry\Controllers\Vendor\CustomerController::class,

            \App\Http\Controllers\Vendor\CustomRoleController::class
                => \App\Modules\Laundry\Controllers\Vendor\CustomRoleController::class,

            \App\Http\Controllers\Vendor\DashboardController::class
                => \App\Modules\Laundry\Controllers\Vendor\DashboardController::class,

            \App\Http\Controllers\Vendor\DeliveryManController::class
                => \App\Modules\Laundry\Controllers\Vendor\DeliveryManController::class,

            \App\Http\Controllers\Vendor\DocumentsController::class
                => \App\Modules\Laundry\Controllers\Vendor\DocumentsController::class,

            \App\Http\Controllers\Vendor\EmployeeController::class
                => \App\Modules\Laundry\Controllers\Vendor\EmployeeController::class,

            \App\Http\Controllers\Vendor\FormBuilderController::class
                => \App\Modules\Laundry\Controllers\Vendor\FormBuilderController::class,

            \App\Http\Controllers\Vendor\HRController::class
                => \App\Modules\Laundry\Controllers\Vendor\HRController::class,

            \App\Http\Controllers\Vendor\ImpersonateController::class
                => \App\Modules\Laundry\Controllers\Vendor\ImpersonateController::class,

            \App\Http\Controllers\Vendor\InventoryController::class
                => \App\Modules\Laundry\Controllers\Vendor\InventoryController::class,

            \App\Http\Controllers\Vendor\InventoryGatepassController::class
                => \App\Modules\Laundry\Controllers\Vendor\InventoryGatepassController::class,

            \App\Http\Controllers\Vendor\InventoryOrderController::class
                => \App\Modules\Laundry\Controllers\Vendor\InventoryOrderController::class,

            \App\Http\Controllers\Vendor\InventoryPurchaseController::class
                => \App\Modules\Laundry\Controllers\Vendor\InventoryPurchaseController::class,

            \App\Http\Controllers\Vendor\InventoryReportController::class
                => \App\Modules\Laundry\Controllers\Vendor\InventoryReportController::class,

            \App\Http\Controllers\Vendor\InventoryStockController::class
                => \App\Modules\Laundry\Controllers\Vendor\InventoryStockController::class,

            \App\Http\Controllers\Vendor\ItemController::class
                => \App\Modules\Laundry\Controllers\Vendor\ItemController::class,

            \App\Http\Controllers\Vendor\LanguageController::class
                => \App\Modules\Laundry\Controllers\Vendor\LanguageController::class,

            \App\Http\Controllers\Vendor\LeadController::class
                => \App\Modules\Laundry\Controllers\Vendor\LeadController::class,

            \App\Http\Controllers\Vendor\LeadSubscriptionController::class
                => \App\Modules\Laundry\Controllers\Vendor\LeadSubscriptionController::class,

            \App\Http\Controllers\Vendor\LeaveController::class
                => \App\Modules\Laundry\Controllers\Vendor\LeaveController::class,

            \App\Http\Controllers\Vendor\LibraryController::class
                => \App\Modules\Laundry\Controllers\Vendor\LibraryController::class,

            \App\Http\Controllers\Vendor\MaintananceController::class
                => \App\Modules\Laundry\Controllers\Vendor\MaintananceController::class,

            \App\Http\Controllers\Vendor\MasterLedgerController::class
                => \App\Modules\Laundry\Controllers\Vendor\MasterLedgerController::class,

            \App\Http\Controllers\Vendor\MCVendorController::class
                => \App\Modules\Laundry\Controllers\Vendor\MCVendorController::class,

            \App\Http\Controllers\Vendor\MonthlyFinanceController::class
                => \App\Modules\Laundry\Controllers\Vendor\MonthlyFinanceController::class,

            \App\Http\Controllers\Vendor\NotificationController::class
                => \App\Modules\Laundry\Controllers\Vendor\NotificationController::class,

            \App\Http\Controllers\Vendor\OrderController::class
                => \App\Modules\Laundry\Controllers\Vendor\OrderController::class,

            \App\Http\Controllers\Vendor\OrderTypeController::class
                => \App\Modules\Laundry\Controllers\Vendor\OrderTypeController::class,

            \App\Http\Controllers\Vendor\POSController::class
                => \App\Modules\Laundry\Controllers\Vendor\POSController::class,

            \App\Http\Controllers\Vendor\ProfileController::class
                => \App\Modules\Laundry\Controllers\Vendor\ProfileController::class,

            \App\Http\Controllers\Vendor\ProjectController::class
                => \App\Modules\Laundry\Controllers\Vendor\ProjectController::class,

            \App\Http\Controllers\Vendor\ProjectTaskController::class
                => \App\Modules\Laundry\Controllers\Vendor\ProjectTaskController::class,

            \App\Http\Controllers\Vendor\QuoteController::class
                => \App\Modules\Laundry\Controllers\Vendor\QuoteController::class,

            \App\Http\Controllers\Vendor\ReportController::class
                => \App\Modules\Laundry\Controllers\Vendor\ReportController::class,

            \App\Http\Controllers\Vendor\RestaurantController::class
                => \App\Modules\Laundry\Controllers\Vendor\RestaurantController::class,

            \App\Http\Controllers\Vendor\RestaurantTableController::class
                => \App\Modules\Laundry\Controllers\Vendor\RestaurantTableController::class,

            \App\Http\Controllers\Vendor\ReviewController::class
                => \App\Modules\Laundry\Controllers\Vendor\ReviewController::class,

            \App\Http\Controllers\Vendor\SalaryController::class
                => \App\Modules\Laundry\Controllers\Vendor\SalaryController::class,

            \App\Http\Controllers\Vendor\SalespointController::class
                => \App\Modules\Laundry\Controllers\Vendor\SalespointController::class,

            \App\Http\Controllers\Vendor\ServiceController::class
                => \App\Modules\Laundry\Controllers\Vendor\ServiceController::class,

            \App\Http\Controllers\Vendor\SettingsController::class
                => \App\Modules\Laundry\Controllers\Vendor\SettingsController::class,

            \App\Http\Controllers\Vendor\ShiftController::class
                => \App\Modules\Laundry\Controllers\Vendor\ShiftController::class,

            \App\Http\Controllers\Vendor\SmartCalendarController::class
                => \App\Modules\Laundry\Controllers\Vendor\SmartCalendarController::class,

            \App\Http\Controllers\Vendor\StaffController::class
                => \App\Modules\Laundry\Controllers\Vendor\StaffController::class,

            \App\Http\Controllers\Vendor\SubmoduleController::class
                => \App\Modules\Laundry\Controllers\Vendor\SubmoduleController::class,

            \App\Http\Controllers\Vendor\SystemController::class
                => \App\Modules\Laundry\Controllers\Vendor\SystemController::class,

            \App\Http\Controllers\Vendor\TaskController::class
                => \App\Modules\Laundry\Controllers\Vendor\TaskController::class,

            \App\Http\Controllers\Vendor\TaskSalaryCategoryController::class
                => \App\Modules\Laundry\Controllers\Vendor\TaskSalaryCategoryController::class,

            \App\Http\Controllers\Vendor\TaxationController::class
                => \App\Modules\Laundry\Controllers\Vendor\TaxationController::class,

            \App\Http\Controllers\Vendor\VendorEmployeeController::class
                => \App\Modules\Laundry\Controllers\Vendor\VendorEmployeeController::class,

            \App\Http\Controllers\Vendor\WalletController::class
                => \App\Modules\Laundry\Controllers\Vendor\WalletController::class,

            \App\Http\Controllers\Vendor\WalletMethodController::class
                => \App\Modules\Laundry\Controllers\Vendor\WalletMethodController::class,

            \App\Http\Controllers\Vendor\WebsiteSettingsController::class
                => \App\Modules\Laundry\Controllers\Vendor\WebsiteSettingsController::class,

        ],
        'views' => app_path('Modules/Laundry/views'),
    ],

    'pos' => [
        'controllers' => [
            \App\Http\Controllers\Vendor\AccountController::class
                => \App\Modules\POS\Controllers\Vendor\AccountController::class,

            \App\Http\Controllers\Vendor\AccountReportController::class
                => \App\Modules\POS\Controllers\Vendor\AccountReportController::class,

            \App\Http\Controllers\Vendor\AccountRequestFormController::class
                => \App\Modules\POS\Controllers\Vendor\AccountRequestFormController::class,

            \App\Http\Controllers\Vendor\AccountSettingController::class
                => \App\Modules\POS\Controllers\Vendor\AccountSettingController::class,

            \App\Http\Controllers\Vendor\AccountStatementController::class
                => \App\Modules\POS\Controllers\Vendor\AccountStatementController::class,

            \App\Http\Controllers\Vendor\AddOnController::class
                => \App\Modules\POS\Controllers\Vendor\AddOnController::class,

            \App\Http\Controllers\Vendor\AIChatController::class
                => \App\Modules\POS\Controllers\Vendor\AIChatController::class,

            \App\Http\Controllers\Vendor\AnalyticsController::class
                => \App\Modules\POS\Controllers\Vendor\AnalyticsController::class,

            \App\Http\Controllers\Vendor\AssetsController::class
                => \App\Modules\POS\Controllers\Vendor\AssetsController::class,

            \App\Http\Controllers\Vendor\AttendanceController::class
                => \App\Modules\POS\Controllers\Vendor\AttendanceController::class,

            \App\Http\Controllers\Vendor\AttendanceExport::class
                => \App\Modules\POS\Controllers\Vendor\AttendanceExport::class,

            \App\Http\Controllers\Vendor\BankAccountController::class
                => \App\Modules\POS\Controllers\Vendor\BankAccountController::class,

            \App\Http\Controllers\Vendor\BankingController::class
                => \App\Modules\POS\Controllers\Vendor\BankingController::class,

            \App\Http\Controllers\Vendor\BankReconciliationController::class
                => \App\Modules\POS\Controllers\Vendor\BankReconciliationController::class,

            \App\Http\Controllers\Vendor\BannerController::class
                => \App\Modules\POS\Controllers\Vendor\BannerController::class,

            \App\Http\Controllers\Vendor\BasicStaffController::class
                => \App\Modules\POS\Controllers\Vendor\BasicStaffController::class,

            \App\Http\Controllers\Vendor\BillingController::class
                => \App\Modules\POS\Controllers\Vendor\BillingController::class,

            \App\Http\Controllers\Vendor\BranchController::class
                => \App\Modules\POS\Controllers\Vendor\BranchController::class,

            \App\Http\Controllers\Vendor\BusinessSettingsController::class
                => \App\Modules\POS\Controllers\Vendor\BusinessSettingsController::class,

            \App\Http\Controllers\Vendor\CampaignController::class
                => \App\Modules\POS\Controllers\Vendor\CampaignController::class,

            \App\Http\Controllers\Vendor\CashBookController::class
                => \App\Modules\POS\Controllers\Vendor\CashBookController::class,

            \App\Http\Controllers\Vendor\CategoryController::class
                => \App\Modules\POS\Controllers\Vendor\CategoryController::class,

            \App\Http\Controllers\Vendor\ConversationController::class
                => \App\Modules\POS\Controllers\Vendor\ConversationController::class,

            \App\Http\Controllers\Vendor\CouponController::class
                => \App\Modules\POS\Controllers\Vendor\CouponController::class,

            \App\Http\Controllers\Vendor\CustomerController::class
                => \App\Modules\POS\Controllers\Vendor\CustomerController::class,

            \App\Http\Controllers\Vendor\CustomRoleController::class
                => \App\Modules\POS\Controllers\Vendor\CustomRoleController::class,

            \App\Http\Controllers\Vendor\DashboardController::class
                => \App\Modules\POS\Controllers\Vendor\DashboardController::class,

            \App\Http\Controllers\Vendor\DeliveryManController::class
                => \App\Modules\POS\Controllers\Vendor\DeliveryManController::class,

            \App\Http\Controllers\Vendor\DocumentsController::class
                => \App\Modules\POS\Controllers\Vendor\DocumentsController::class,

            \App\Http\Controllers\Vendor\EmployeeController::class
                => \App\Modules\POS\Controllers\Vendor\EmployeeController::class,

            \App\Http\Controllers\Vendor\FormBuilderController::class
                => \App\Modules\POS\Controllers\Vendor\FormBuilderController::class,

            \App\Http\Controllers\Vendor\HRController::class
                => \App\Modules\POS\Controllers\Vendor\HRController::class,

            \App\Http\Controllers\Vendor\ImpersonateController::class
                => \App\Modules\POS\Controllers\Vendor\ImpersonateController::class,

            \App\Http\Controllers\Vendor\InventoryController::class
                => \App\Modules\POS\Controllers\Vendor\InventoryController::class,

            \App\Http\Controllers\Vendor\InventoryGatepassController::class
                => \App\Modules\POS\Controllers\Vendor\InventoryGatepassController::class,

            \App\Http\Controllers\Vendor\InventoryOrderController::class
                => \App\Modules\POS\Controllers\Vendor\InventoryOrderController::class,

            \App\Http\Controllers\Vendor\InventoryPurchaseController::class
                => \App\Modules\POS\Controllers\Vendor\InventoryPurchaseController::class,

            \App\Http\Controllers\Vendor\InventoryReportController::class
                => \App\Modules\POS\Controllers\Vendor\InventoryReportController::class,

            \App\Http\Controllers\Vendor\InventoryStockController::class
                => \App\Modules\POS\Controllers\Vendor\InventoryStockController::class,

            \App\Http\Controllers\Vendor\ItemController::class
                => \App\Modules\POS\Controllers\Vendor\ItemController::class,

            \App\Http\Controllers\Vendor\LanguageController::class
                => \App\Modules\POS\Controllers\Vendor\LanguageController::class,

            \App\Http\Controllers\Vendor\LeadController::class
                => \App\Modules\POS\Controllers\Vendor\LeadController::class,

            \App\Http\Controllers\Vendor\LeadSubscriptionController::class
                => \App\Modules\POS\Controllers\Vendor\LeadSubscriptionController::class,

            \App\Http\Controllers\Vendor\LeaveController::class
                => \App\Modules\POS\Controllers\Vendor\LeaveController::class,

            \App\Http\Controllers\Vendor\LibraryController::class
                => \App\Modules\POS\Controllers\Vendor\LibraryController::class,

            \App\Http\Controllers\Vendor\MaintananceController::class
                => \App\Modules\POS\Controllers\Vendor\MaintananceController::class,

            \App\Http\Controllers\Vendor\MasterLedgerController::class
                => \App\Modules\POS\Controllers\Vendor\MasterLedgerController::class,

            \App\Http\Controllers\Vendor\MCVendorController::class
                => \App\Modules\POS\Controllers\Vendor\MCVendorController::class,

            \App\Http\Controllers\Vendor\MonthlyFinanceController::class
                => \App\Modules\POS\Controllers\Vendor\MonthlyFinanceController::class,

            \App\Http\Controllers\Vendor\NotificationController::class
                => \App\Modules\POS\Controllers\Vendor\NotificationController::class,

            \App\Http\Controllers\Vendor\OrderController::class
                => \App\Modules\POS\Controllers\Vendor\OrderController::class,

            \App\Http\Controllers\Vendor\OrderTypeController::class
                => \App\Modules\POS\Controllers\Vendor\OrderTypeController::class,

            \App\Http\Controllers\Vendor\POSController::class
                => \App\Modules\POS\Controllers\Vendor\POSController::class,

            \App\Http\Controllers\Vendor\ProfileController::class
                => \App\Modules\POS\Controllers\Vendor\ProfileController::class,

            \App\Http\Controllers\Vendor\ProjectController::class
                => \App\Modules\POS\Controllers\Vendor\ProjectController::class,

            \App\Http\Controllers\Vendor\ProjectTaskController::class
                => \App\Modules\POS\Controllers\Vendor\ProjectTaskController::class,

            \App\Http\Controllers\Vendor\QuoteController::class
                => \App\Modules\POS\Controllers\Vendor\QuoteController::class,

            \App\Http\Controllers\Vendor\ReportController::class
                => \App\Modules\POS\Controllers\Vendor\ReportController::class,

            \App\Http\Controllers\Vendor\RestaurantController::class
                => \App\Modules\POS\Controllers\Vendor\RestaurantController::class,

            \App\Http\Controllers\Vendor\RestaurantTableController::class
                => \App\Modules\POS\Controllers\Vendor\RestaurantTableController::class,

            \App\Http\Controllers\Vendor\ReviewController::class
                => \App\Modules\POS\Controllers\Vendor\ReviewController::class,

            \App\Http\Controllers\Vendor\SalaryController::class
                => \App\Modules\POS\Controllers\Vendor\SalaryController::class,

            \App\Http\Controllers\Vendor\SalespointController::class
                => \App\Modules\POS\Controllers\Vendor\SalespointController::class,

            \App\Http\Controllers\Vendor\ServiceController::class
                => \App\Modules\POS\Controllers\Vendor\ServiceController::class,

            \App\Http\Controllers\Vendor\SettingsController::class
                => \App\Modules\POS\Controllers\Vendor\SettingsController::class,

            \App\Http\Controllers\Vendor\ShiftController::class
                => \App\Modules\POS\Controllers\Vendor\ShiftController::class,

            \App\Http\Controllers\Vendor\SmartCalendarController::class
                => \App\Modules\POS\Controllers\Vendor\SmartCalendarController::class,

            \App\Http\Controllers\Vendor\StaffController::class
                => \App\Modules\POS\Controllers\Vendor\StaffController::class,

            \App\Http\Controllers\Vendor\SubmoduleController::class
                => \App\Modules\POS\Controllers\Vendor\SubmoduleController::class,

            \App\Http\Controllers\Vendor\SystemController::class
                => \App\Modules\POS\Controllers\Vendor\SystemController::class,

            \App\Http\Controllers\Vendor\TaskController::class
                => \App\Modules\POS\Controllers\Vendor\TaskController::class,

            \App\Http\Controllers\Vendor\TaskSalaryCategoryController::class
                => \App\Modules\POS\Controllers\Vendor\TaskSalaryCategoryController::class,

            \App\Http\Controllers\Vendor\TaxationController::class
                => \App\Modules\POS\Controllers\Vendor\TaxationController::class,

            \App\Http\Controllers\Vendor\VendorEmployeeController::class
                => \App\Modules\POS\Controllers\Vendor\VendorEmployeeController::class,

            \App\Http\Controllers\Vendor\WalletController::class
                => \App\Modules\POS\Controllers\Vendor\WalletController::class,

            \App\Http\Controllers\Vendor\WalletMethodController::class
                => \App\Modules\POS\Controllers\Vendor\WalletMethodController::class,

            \App\Http\Controllers\Vendor\WebsiteSettingsController::class
                => \App\Modules\POS\Controllers\Vendor\WebsiteSettingsController::class,

        ],
        'views' => app_path('Modules/POS/views'),
    ],

    'hmis' => [
        'controllers' => [
            \App\Http\Controllers\Vendor\AccountController::class
                => \App\Modules\HMIS\Controllers\Vendor\AccountController::class,

            \App\Http\Controllers\Vendor\AccountReportController::class
                => \App\Modules\HMIS\Controllers\Vendor\AccountReportController::class,

            \App\Http\Controllers\Vendor\AccountRequestFormController::class
                => \App\Modules\HMIS\Controllers\Vendor\AccountRequestFormController::class,

            \App\Http\Controllers\Vendor\AccountSettingController::class
                => \App\Modules\HMIS\Controllers\Vendor\AccountSettingController::class,

            \App\Http\Controllers\Vendor\AccountStatementController::class
                => \App\Modules\HMIS\Controllers\Vendor\AccountStatementController::class,

            \App\Http\Controllers\Vendor\AddOnController::class
                => \App\Modules\HMIS\Controllers\Vendor\AddOnController::class,

            \App\Http\Controllers\Vendor\AIChatController::class
                => \App\Modules\HMIS\Controllers\Vendor\AIChatController::class,

            \App\Http\Controllers\Vendor\AnalyticsController::class
                => \App\Modules\HMIS\Controllers\Vendor\AnalyticsController::class,

            \App\Http\Controllers\Vendor\AssetsController::class
                => \App\Modules\HMIS\Controllers\Vendor\AssetsController::class,

            \App\Http\Controllers\Vendor\AttendanceController::class
                => \App\Modules\HMIS\Controllers\Vendor\AttendanceController::class,

            \App\Http\Controllers\Vendor\AttendanceExport::class
                => \App\Modules\HMIS\Controllers\Vendor\AttendanceExport::class,

            \App\Http\Controllers\Vendor\BankAccountController::class
                => \App\Modules\HMIS\Controllers\Vendor\BankAccountController::class,

            \App\Http\Controllers\Vendor\BankingController::class
                => \App\Modules\HMIS\Controllers\Vendor\BankingController::class,

            \App\Http\Controllers\Vendor\BankReconciliationController::class
                => \App\Modules\HMIS\Controllers\Vendor\BankReconciliationController::class,

            \App\Http\Controllers\Vendor\BannerController::class
                => \App\Modules\HMIS\Controllers\Vendor\BannerController::class,

            \App\Http\Controllers\Vendor\BasicStaffController::class
                => \App\Modules\HMIS\Controllers\Vendor\BasicStaffController::class,

            \App\Http\Controllers\Vendor\BillingController::class
                => \App\Modules\HMIS\Controllers\Vendor\BillingController::class,

            \App\Http\Controllers\Vendor\BranchController::class
                => \App\Modules\HMIS\Controllers\Vendor\BranchController::class,

            \App\Http\Controllers\Vendor\BusinessSettingsController::class
                => \App\Modules\HMIS\Controllers\Vendor\BusinessSettingsController::class,

            \App\Http\Controllers\Vendor\CampaignController::class
                => \App\Modules\HMIS\Controllers\Vendor\CampaignController::class,

            \App\Http\Controllers\Vendor\CashBookController::class
                => \App\Modules\HMIS\Controllers\Vendor\CashBookController::class,

            \App\Http\Controllers\Vendor\CategoryController::class
                => \App\Modules\HMIS\Controllers\Vendor\CategoryController::class,

            \App\Http\Controllers\Vendor\ConversationController::class
                => \App\Modules\HMIS\Controllers\Vendor\ConversationController::class,

            \App\Http\Controllers\Vendor\CouponController::class
                => \App\Modules\HMIS\Controllers\Vendor\CouponController::class,

            \App\Http\Controllers\Vendor\CustomerController::class
                => \App\Modules\HMIS\Controllers\Vendor\CustomerController::class,

            \App\Http\Controllers\Vendor\CustomRoleController::class
                => \App\Modules\HMIS\Controllers\Vendor\CustomRoleController::class,

            \App\Http\Controllers\Vendor\DashboardController::class
                => \App\Modules\HMIS\Controllers\Vendor\DashboardController::class,

            \App\Http\Controllers\Vendor\DeliveryManController::class
                => \App\Modules\HMIS\Controllers\Vendor\DeliveryManController::class,

            \App\Http\Controllers\Vendor\DocumentsController::class
                => \App\Modules\HMIS\Controllers\Vendor\DocumentsController::class,

            \App\Http\Controllers\Vendor\EmployeeController::class
                => \App\Modules\HMIS\Controllers\Vendor\EmployeeController::class,

            \App\Http\Controllers\Vendor\FormBuilderController::class
                => \App\Modules\HMIS\Controllers\Vendor\FormBuilderController::class,

            \App\Http\Controllers\Vendor\HRController::class
                => \App\Modules\HMIS\Controllers\Vendor\HRController::class,

            \App\Http\Controllers\Vendor\ImpersonateController::class
                => \App\Modules\HMIS\Controllers\Vendor\ImpersonateController::class,

            \App\Http\Controllers\Vendor\InventoryController::class
                => \App\Modules\HMIS\Controllers\Vendor\InventoryController::class,

            \App\Http\Controllers\Vendor\InventoryGatepassController::class
                => \App\Modules\HMIS\Controllers\Vendor\InventoryGatepassController::class,

            \App\Http\Controllers\Vendor\InventoryOrderController::class
                => \App\Modules\HMIS\Controllers\Vendor\InventoryOrderController::class,

            \App\Http\Controllers\Vendor\InventoryPurchaseController::class
                => \App\Modules\HMIS\Controllers\Vendor\InventoryPurchaseController::class,

            \App\Http\Controllers\Vendor\InventoryReportController::class
                => \App\Modules\HMIS\Controllers\Vendor\InventoryReportController::class,

            \App\Http\Controllers\Vendor\InventoryStockController::class
                => \App\Modules\HMIS\Controllers\Vendor\InventoryStockController::class,

            \App\Http\Controllers\Vendor\ItemController::class
                => \App\Modules\HMIS\Controllers\Vendor\ItemController::class,

            \App\Http\Controllers\Vendor\LanguageController::class
                => \App\Modules\HMIS\Controllers\Vendor\LanguageController::class,

            \App\Http\Controllers\Vendor\LeadController::class
                => \App\Modules\HMIS\Controllers\Vendor\LeadController::class,

            \App\Http\Controllers\Vendor\LeadSubscriptionController::class
                => \App\Modules\HMIS\Controllers\Vendor\LeadSubscriptionController::class,

            \App\Http\Controllers\Vendor\LeaveController::class
                => \App\Modules\HMIS\Controllers\Vendor\LeaveController::class,

            \App\Http\Controllers\Vendor\LibraryController::class
                => \App\Modules\HMIS\Controllers\Vendor\LibraryController::class,

            \App\Http\Controllers\Vendor\MaintananceController::class
                => \App\Modules\HMIS\Controllers\Vendor\MaintananceController::class,

            \App\Http\Controllers\Vendor\MasterLedgerController::class
                => \App\Modules\HMIS\Controllers\Vendor\MasterLedgerController::class,

            \App\Http\Controllers\Vendor\MCVendorController::class
                => \App\Modules\HMIS\Controllers\Vendor\MCVendorController::class,

            \App\Http\Controllers\Vendor\MonthlyFinanceController::class
                => \App\Modules\HMIS\Controllers\Vendor\MonthlyFinanceController::class,

            \App\Http\Controllers\Vendor\NotificationController::class
                => \App\Modules\HMIS\Controllers\Vendor\NotificationController::class,

            \App\Http\Controllers\Vendor\OrderController::class
                => \App\Modules\HMIS\Controllers\Vendor\OrderController::class,

            \App\Http\Controllers\Vendor\OrderTypeController::class
                => \App\Modules\HMIS\Controllers\Vendor\OrderTypeController::class,

            \App\Http\Controllers\Vendor\POSController::class
                => \App\Modules\HMIS\Controllers\Vendor\POSController::class,

            \App\Http\Controllers\Vendor\ProfileController::class
                => \App\Modules\HMIS\Controllers\Vendor\ProfileController::class,

            \App\Http\Controllers\Vendor\ProjectController::class
                => \App\Modules\HMIS\Controllers\Vendor\ProjectController::class,

            \App\Http\Controllers\Vendor\ProjectTaskController::class
                => \App\Modules\HMIS\Controllers\Vendor\ProjectTaskController::class,

            \App\Http\Controllers\Vendor\QuoteController::class
                => \App\Modules\HMIS\Controllers\Vendor\QuoteController::class,

            \App\Http\Controllers\Vendor\ReportController::class
                => \App\Modules\HMIS\Controllers\Vendor\ReportController::class,

            \App\Http\Controllers\Vendor\RestaurantController::class
                => \App\Modules\HMIS\Controllers\Vendor\RestaurantController::class,

            \App\Http\Controllers\Vendor\RestaurantTableController::class
                => \App\Modules\HMIS\Controllers\Vendor\RestaurantTableController::class,

            \App\Http\Controllers\Vendor\ReviewController::class
                => \App\Modules\HMIS\Controllers\Vendor\ReviewController::class,

            \App\Http\Controllers\Vendor\SalaryController::class
                => \App\Modules\HMIS\Controllers\Vendor\SalaryController::class,

            \App\Http\Controllers\Vendor\SalespointController::class
                => \App\Modules\HMIS\Controllers\Vendor\SalespointController::class,

            \App\Http\Controllers\Vendor\ServiceController::class
                => \App\Modules\HMIS\Controllers\Vendor\ServiceController::class,

            \App\Http\Controllers\Vendor\SettingsController::class
                => \App\Modules\HMIS\Controllers\Vendor\SettingsController::class,

            \App\Http\Controllers\Vendor\ShiftController::class
                => \App\Modules\HMIS\Controllers\Vendor\ShiftController::class,

            \App\Http\Controllers\Vendor\SmartCalendarController::class
                => \App\Modules\HMIS\Controllers\Vendor\SmartCalendarController::class,

            \App\Http\Controllers\Vendor\StaffController::class
                => \App\Modules\HMIS\Controllers\Vendor\StaffController::class,

            \App\Http\Controllers\Vendor\SubmoduleController::class
                => \App\Modules\HMIS\Controllers\Vendor\SubmoduleController::class,

            \App\Http\Controllers\Vendor\SystemController::class
                => \App\Modules\HMIS\Controllers\Vendor\SystemController::class,

            \App\Http\Controllers\Vendor\TaskController::class
                => \App\Modules\HMIS\Controllers\Vendor\TaskController::class,

            \App\Http\Controllers\Vendor\TaskSalaryCategoryController::class
                => \App\Modules\HMIS\Controllers\Vendor\TaskSalaryCategoryController::class,

            \App\Http\Controllers\Vendor\TaxationController::class
                => \App\Modules\HMIS\Controllers\Vendor\TaxationController::class,

            \App\Http\Controllers\Vendor\VendorEmployeeController::class
                => \App\Modules\HMIS\Controllers\Vendor\VendorEmployeeController::class,

            \App\Http\Controllers\Vendor\WalletController::class
                => \App\Modules\HMIS\Controllers\Vendor\WalletController::class,

            \App\Http\Controllers\Vendor\WalletMethodController::class
                => \App\Modules\HMIS\Controllers\Vendor\WalletMethodController::class,

            \App\Http\Controllers\Vendor\WebsiteSettingsController::class
                => \App\Modules\HMIS\Controllers\Vendor\WebsiteSettingsController::class,

        ],
        'views' => app_path('Modules/HMIS/views'),
    ],

];
