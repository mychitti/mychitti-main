# Client Management Copy to Admin Panel TODO

## [x] 1. Add routes to routes/admin.php (prefix: 'client', as: 'client.', middleware: module:client_manage)
## [x] 2. Update app/Http/Controllers/Admin/MychittiClientController.php (add/merge vendor CRUD methods: index/list, create/add_new, store/save, edit, update, show/view, destroy/delete, bulk_delete, export, import/upload_excel, get_matches/fetch_details/comment_save)
## [x] 3. Create resources/views/admin-views/client/list.blade.php (copy+adapt from vendor-views/customer/list.blade.php)
## [x] 4. Create resources/views/admin-views/client/forms/client_add.blade.php (copy+adapt from vendor-views/forms/customer_add.blade.php)
## [x] 5. Create resources/views/admin-views/client/view.blade.php (copy+adapt from vendor-views/customer/view.blade.php)
## [x] 6. Add sidebar link in resources/views/layouts/admin/partials/_sidebar_grocery.blade.php (under Billing/Customers?)
## [x] 7. Adapt queries: remove Helpers::get_store_id(), use global StoreCustomer::where('store_id', 0) or User (TBD after reading MychittiClientController)
## [x] 8. Test: php artisan route:clear; cache:clear; view:clear; Access /admin/client/list, CRUD, export/import, permissions.
## [ ] 9. attempt_completion


