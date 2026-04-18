# Store Review Form Implementation - BLACKBOXAI
Status: ✅ **IN PROGRESS**

## Approved Plan Summary
- Create reusable partial: `resources/views/front-views/partials/_store-review-form.blade.php` (modal form).
- Add to ALL 16 templates: `resources/views/front-views/store_webpage/template-1.blade.php` to `template-16.blade.php`.
- Add route: `POST /submit-store-review` → `Front\UserController@submit_service_review`.
- Leverage existing backend (StoreReview model, validation, uploads, rating updates).
- Features: Login required, star rating, multi-file upload, AJAX submit, responsive modal.

## Step-by-Step Tasks

### ✅ Step 1: Create Reusable Review Form Partial
- File: `resources/views/front-views/partials/_store-review-form.blade.php`
- Features: Form fields matching validation, star rating JS, file preview, AJAX submit.

### ⏳ Step 2: Add Route for Web Submission
- File: `routes/web.php`
- Add: `Route::post('submit-store-review', [FrontUserController::class, 'submit_service_review'])->middleware('loginuser')->name('submit-store-review');`

### ☐ Step 3: Update All 16 Templates (Batch)
For each `template-N.blade.php` (N=1-16):
- Add "Give Review" button (e.g. after services/reviews section).
- Include modal + `@include('front-views.partials._store-review-form', ['store' => $store])`.
- Add common JS for stars/AJAX (in <script> before </body>).

**Batch Commands** (after manual):
```
- template-1 to template-16: Add button + include
```

### ☐ Step 4: Test & Cache
```
php artisan storage:link
php artisan route:cache
php artisan view:cache
```
- Test: Visit template → login → "Give Review" → submit → verify DB/ratings.

### ☐ Step 5: Optional Enhancements
- Add to `store_details.blade.php`, `customer-store-page.blade.php`.
- Success toast refresh.

## Current Progress
- [x] Plan finalized & approved
- [ ] Step 1: Partial created
- [ ] Step 2: Route added
- [ ] Step 3: Templates updated (0/16)
- [ ] Step 4: Tested & cached

**Next**: Implement Step 1 → confirm → Step 2 → batch Step 3.

