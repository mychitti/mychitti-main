<?php

namespace App\Http\Controllers\Admin\Item;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Enums\ExportFileNames\Admin\Category;
use App\Enums\ViewPaths\Admin\Category as CategoryViewPath;
use App\Exports\CategoryExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\CategoryAddRequest;
use App\Http\Requests\Admin\CategoryBulkExportRequest;
use App\Http\Requests\Admin\CategoryBulkImportRequest;
use App\Http\Requests\Admin\CategoryUpdateRequest;
use App\Models\ActionLog;
use App\Models\Category as ModelsCategory;
use App\Models\FeeCategory;
use App\Services\CategoryService;
use App\Traits\ImportExportTrait;
use Brian2694\Toastr\Facades\Toastr;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use OpenSpout\Common\Exception\InvalidArgumentException;
use OpenSpout\Common\Exception\IOException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryController extends BaseController
{
    use ImportExportTrait;

    public function __construct(
        protected CategoryRepositoryInterface    $categoryRepo,
        protected CategoryService                $categoryService,
        protected TranslationRepositoryInterface $translationRepo
    ) {}

    public function index(?Request $request): View|Collection|LengthAwarePaginator|null
    {
        return $this->getCategoryView($request);
    }
    public function category_fee(Request $request)
    {
        $fees = FeeCategory::all();
        return view('admin-views.category.category_fee', compact('fees'));
    }
    public function store_fee(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'fee_percent' => 'required',
            'payment_gateway_fees' => 'required',
        ]);
        $fee = new FeeCategory();
        $fee->name = $request->name;
        $fee->platform_fee = $request->fee_percent;
        $fee->payment_gateway_fee = $request->payment_gateway_fees;
        $fee->total_fee = $request->fee_percent + $request->payment_gateway_fees;
        $fee->save();

        Toastr::success(translate('messages.fee_added_successfully'));
        return back();
    }
    public function delete_fee(Request $request)
    {
        FeeCategory::find($request->id)->delete();
        Toastr::success(translate('messages.fee_deleted_successfully'));
        return back();
    }
    public function update_fee(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'fee_percent' => 'required',
            'payment_gateway_fees' => 'required',
        ]);
        $fee =  FeeCategory::find($request->id);
        $fee->name = $request->name;
        $fee->platform_fee = $request->fee_percent;
        $fee->payment_gateway_fee = $request->payment_gateway_fees;
        $fee->total_fee = $request->fee_percent + $request->payment_gateway_fees;
        $fee->save();

        Toastr::success(translate('messages.fee_updated_successfully'));
        return back();
    }
    public function get_fee_values(Request $request)
    {
        $fee = FeeCategory::find($request->id);
        $fee->url = route('admin.category.update-fee');

        return response()->json($fee);
    }

    private function getCategoryView(Request $request): View
    {
        $categories = $this->categoryRepo->getListWhere(
            searchValue: $request['search'],
            filters: ['position' => $request['position'], 'added_by' => null],
            relations: ['module'],
            dataLimit: config('default_pagination')
        );

        $mainCategories = $this->categoryRepo->getMainList(
            filters: ['position' => 0, 'added_by' => null],
            relations: ['module'],
        );

        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        return view($this->categoryService->getViewByPosition($request['position']), compact('categories', 'language', 'defaultLang', 'mainCategories'));
    }

    /**
     * Subcategories the admin might want under a category they are about to create.
     *
     * Suggestions only — nothing is written here. The admin drops the ones that do not fit and
     * saves the rest with the category, so a wrong guess costs a click rather than a cleanup.
     */
    /**
     * Create the suggested subcategories the admin did not remove.
     *
     * They inherit the parent's image and module: the add form only uploads one picture, and a
     * subcategory with no image at all renders as a broken tile on the storefront.
     */
    private function addSuggestedSubcategories(CategoryAddRequest $request, $category): void
    {
        $names = collect((array) $request->input('sub_names', []))
            ->map(fn($n) => trim((string) $n))
            ->filter(fn($n) => $n !== '' && mb_strlen($n) <= 100)
            ->unique(fn($n) => mb_strtolower($n))
            ->take(20);

        foreach ($names as $name) {
            try {
                $this->categoryRepo->add(data: [
                    'name' => $name,
                    'image' => $category->image,
                    'parent_id' => $category->id,
                    'position' => 1,
                    'module_id' => $category->module_id,
                ]);
            } catch (\Throwable $e) {
                // One bad name must not lose the category that was just created, nor the rest.
                \Illuminate\Support\Facades\Log::warning('Subcategory "' . $name . '" not created: ' . $e->getMessage());
            }
        }
    }

    public function suggestSubcategories(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:100']);
        $name = trim($request->input('name'));

        // Resolved on demand and guarded, the way AIChatController does it: a missing
        // openai-php/client package must not take the category screen down with it.
        if (!class_exists(\OpenAI\Factory::class)) {
            return response()->json(['success' => false, 'message' => translate('AI is not configured on this server.')], 422);
        }

        $system = "You name subcategories for an Indian services and products marketplace.\n"
            . "Given one category name, reply with ONLY a JSON array of 6 to 12 subcategory names.\n"
            . "Rules: each name is 2 to 4 words, title case, specific enough that a customer would "
            . "search for it, no duplicates, no numbering, no explanation, no text outside the array.\n"
            . 'Example for "Repair and Service": '
            . '["AC Repair","Home Appliance Repair","Refrigerator Repair","Washing Machine Repair",'
            . '"Water Purifier Service","Television Repair","Microwave Repair","Geyser Repair"]';

        try {
            $reply = app(\App\Services\OpenAIService::class)->chat(
                messages: [['role' => 'user', 'content' => $name]],
                system: $system,
                maxTokens: 400,
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Subcategory suggestion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => translate('Could not reach the AI service. Add subcategories by hand.'),
            ], 502);
        }

        // Models wrap the array in prose or a code fence often enough that trusting the whole
        // reply to be JSON would fail intermittently, which is worse than never working.
        $json = $reply;
        if (preg_match('/\[.*\]/s', $reply, $m)) {
            $json = $m[0];
        }
        $names = json_decode($json, true);

        if (!is_array($names)) {
            return response()->json([
                'success' => false,
                'message' => translate('The AI reply could not be read. Try again.'),
            ], 422);
        }

        $existing = $this->categoryRepo->getListWhere(filters: ['position' => 1], dataLimit: 'all')
            ->pluck('name')->map(fn($n) => mb_strtolower(trim($n)))->all();

        $names = collect($names)
            ->filter(fn($n) => is_string($n))
            ->map(fn($n) => trim(preg_replace('/\s+/', ' ', $n)))
            ->filter(fn($n) => $n !== '' && mb_strlen($n) <= 100)
            ->unique(fn($n) => mb_strtolower($n))
            // Already in the catalogue under some other parent — offering it again invites a
            // duplicate the admin then has to find and delete.
            ->reject(fn($n) => in_array(mb_strtolower($n), $existing, true))
            ->take(12)
            ->values();

        return response()->json(['success' => true, 'names' => $names]);
    }

    public function add(CategoryAddRequest $request): RedirectResponse
    {
        $parentCategory = $this->categoryRepo->getFirstWhere(params: ['id' => $request['parent_id']]);


        // prx($request->file());
        $request->status = 1;
        $category = $this->categoryRepo->add(
            data: $this->categoryService->getAddData(
                request: $request,
                parentCategory: $parentCategory
            )
        );

        try {
            ActionLog::create([
                'user_id' => auth('admin')->id(),
                'user_type' => 'admin',
                'action' => 'created',
                'model_type' => 'category', // e.g. App\Models\Category
                'model_id' => $category->id,
                'description' => 'Category "' . $category->name . '" created',
                'created_at' => now(),
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }


        // The subcategories the admin kept from the suggestions. Created after the parent so they
        // can point at it, and only for a top-level category — a subcategory of a subcategory is
        // not a shape this catalogue has.
        if ($request['position'] == 0) {
            $this->addSuggestedSubcategories($request, $category);
        }

        $this->translationRepo->addByModel(request: $request, model: $category, modelPath: 'App\Models\Category', attribute: 'name');
        Toastr::success($request['position'] == 0 ?    translate('messages.category_added_successfully') : translate('messages.Sub_category_added_successfully'));
        return back();
    }

    public function getUpdateView(string|int $id): View
    {
        $category = $this->categoryRepo->getFirstWithoutGlobalScopeWhere(params: ['id' => $id]);
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        return view(CategoryViewPath::UPDATE['view'], compact('category', 'language', 'defaultLang'));
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->categoryRepo->update(id: $request['id'], data: ['status' => $request['status']]);
        Toastr::success(translate('messages.category_status_updated'));
        return back();
    }

    public function updateFeatured(Request $request): RedirectResponse
    {
        $this->categoryRepo->update(id: $request['id'], data: ['featured' => $request['featured']]);
        Toastr::success(translate('messages.category_featured_updated'));
        return back();
    }

    public function update(CategoryUpdateRequest $request, string|int $id): RedirectResponse
    {
        $mainCategory = $this->categoryRepo->getFirstWhere(params: ['id' => $id]);
        $category = $this->categoryRepo->update(id: $id, data: $this->categoryService->getUpdateData(request: $request, object: $mainCategory));

        $this->translationRepo->updateByModel(request: $request, model: $category, modelPath: 'App\Models\Category', attribute: 'name');

        try {
            ActionLog::create([
                'user_id' => auth('admin')->id(),
                'user_type' => 'admin',
                'action' => 'updated',
                'model_type' => 'category', // e.g. App\Models\Category
                'model_id' => $category->id,
                'description' => 'Updated category: ' . $category->name,
                'created_at' => now(),
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }


        Toastr::success($category['position'] == 0 ?    translate('messages.category_updated_successfully') : translate('messages.Sub_category_updated_successfully'));
        return redirect()->route('admin.category.add', ['position' => $mainCategory->position]);
    }

    public function delete(Request $request): RedirectResponse
    {
        $cat = ModelsCategory::find($request['id']);

        try {
            ActionLog::create([
                'user_id' => auth('admin')->id(),
                'user_type' => 'admin',
                'action' => 'trashed',
                'model_type' => 'category', // e.g. App\Models\Category
                'model_id' => $cat->id,
                'description' => 'Category "' . $cat->name . '" trashed',
                'created_at' => now(),
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
        $this->categoryRepo->update(id: $request['id'], data: ['deleted_by' => auth('admin')->id()]);

        if ($this->categoryRepo->delete(id: $request['id'])) {
            Toastr::success('Category removed!');
        } else {
            Toastr::warning(translate('messages.remove_sub_categories_first'));
        }
        return back();
    }

    public function getNameList(Request $request): JsonResponse
    {
        $data = $this->categoryRepo->getNameList(request: $request, dataLimit: 8);
        $data[] = (object)['id' => 'all', 'text' => 'All'];
        return response()->json($data);
    }

    public function updatePriority(Request $request): RedirectResponse
    {
        $this->categoryRepo->update(id: $request['category'], data: ['priority' => $request['priority']]);
        Toastr::success(translate('messages.category_priority_updated successfully'));
        return back();
    }

    public function getBulkImportView(): View
    {
        return view(CategoryViewPath::BULK_IMPORT['view']);
    }

    public function importBulkData(CategoryBulkImportRequest $request): RedirectResponse
    {
        $data = $this->categoryService->getImportData(request: $request);

        if (array_key_exists('flag', $data) && $data['flag'] == 'wrong_format') {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }

        if (array_key_exists('flag', $data) && $data['flag'] == 'required_fields') {
            Toastr::error(translate('messages.please_fill_all_required_fields'));
            return back();
        }

        try {
            DB::beginTransaction();
            $this->categoryRepo->addByChunk(data: $data);
            DB::commit();
        } catch (Exception) {
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }

        Toastr::success(translate('messages.category_imported_successfully', ['count' => count($data)]));
        return back();
    }

    public function updateBulkData(CategoryBulkImportRequest $request): RedirectResponse
    {
        $data = $this->categoryService->getImportData(request: $request, toAdd: false);

        if (array_key_exists('flag', $data) && $data['flag'] == 'wrong_format') {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }

        if (array_key_exists('flag', $data) && $data['flag'] == 'required_fields') {
            Toastr::error(translate('messages.please_fill_all_required_fields'));
            return back();
        }

        try {
            DB::beginTransaction();
            $this->categoryRepo->updateByChunk(data: $data);
            DB::commit();
        } catch (Exception) {
            DB::rollBack();
            Toastr::error(translate('messages.failed_to_import_data'));
            return back();
        }

        Toastr::success(translate('messages.category_updated_successfully', ['count' => count($data)]));
        return back();
    }

    public function getBulkExportView(): View
    {
        return view(CategoryViewPath::BULK_EXPORT['view']);
    }

    /**
     * @throws IOException
     * @throws WriterNotOpenedException
     * @throws UnsupportedTypeException
     * @throws InvalidArgumentException
     */
    public function exportBulkData(CategoryBulkExportRequest $request): StreamedResponse|string
    {
        $categories = $this->categoryRepo->getBulkExportList(request: $request);
        return (new FastExcel($this->categoryService->getExportData(collection: $this->exportGenerator(data: $categories))))->download(Category::EXPORT_XLSX);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $categories = $this->categoryRepo->getExportList(request: $request);
        $data = [
            'data' => $categories,
            'search' => $request['search'] ?? null,
        ];

        if ($request['type'] == 'csv') {
            return Excel::download(new CategoryExport($data), Category::EXPORT_CSV);
        }
        return Excel::download(new CategoryExport($data), Category::EXPORT_XLSX);
    }
}
