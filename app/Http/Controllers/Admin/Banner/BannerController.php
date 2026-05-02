<?php

namespace App\Http\Controllers\Admin\Banner;

use App\CentralLogics\Helpers;
use App\Contracts\Repositories\BannerRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Enums\ViewPaths\Admin\Banner as BannerViewPath;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\BannerAddRequest;
use App\Http\Requests\Admin\BannerUpdateRequest;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\User;
use App\Services\BannerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class BannerController extends BaseController
{
    public function __construct(
        protected BannerRepositoryInterface $bannerRepo,
        protected BannerService $bannerService,
        protected TranslationRepositoryInterface $translationRepo,
        protected ZoneRepositoryInterface $zoneRepo
    ) {}


    public function index(?Request $request): View|Collection|LengthAwarePaginator|null
    {
        return $this->getAddView();
    }


    private function getAddView(): View
    {
        $banners = $this->bannerRepo->getListWhere(
            filters: ['module_id' => Config::get('module.current_module_id'), 'created_by' => 'admin'],
            relations: ['module'],
            searchValue: request()->search,
            dataLimit: config('default_pagination')
        );
        $totalBannerCount = \App\Models\Banner::where('module_id', Config::get('module.current_module_id'))
            ->where('created_by', 'admin')
            ->count();
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $zones = $this->zoneRepo->getList();
        $categories = Category::where('module_id', Config::get('module.current_module_id'))->where('status', 1)->get();
        $users = User::where('status', 1)->get();
        // prx( $categories);
        return view(BannerViewPath::INDEX[VIEW], compact('banners', 'totalBannerCount', 'language', 'defaultLang', 'zones', 'categories', 'users'));
    }

    public function vendorApprovals(Request $request): View
    {
        $vendor_banners = \App\Models\Banner::where('created_by', 'store')
            ->with('store')
            ->latest()
            ->paginate(config('default_pagination'));
        return view('admin-views.banner.vendor-approvals', compact('vendor_banners'));
    }

    public function vendorBannerApprove(int $id): RedirectResponse
    {
        \App\Models\Banner::where('id', $id)->where('created_by', 'store')->update(['approval' => 1, 'status' => 1]);
        Toastr::success('Vendor banner approved.');
        return back();
    }

    public function vendorBannerReject(int $id): RedirectResponse
    {
        \App\Models\Banner::where('id', $id)->where('created_by', 'store')->update(['approval' => 2, 'status' => 0]);
        Toastr::success('Vendor banner rejected.');
        return back();
    }

    public function add(BannerAddRequest $request): JsonResponse
    {
        if ($request->user_id || $request->store_id) {
            $this->billToUser($request);
        }

        $banner = $this->bannerRepo->add(data: $this->bannerService->getAddData(request: $request));
        $this->translationRepo->addByModel(request: $request, model: $banner, modelPath: 'App\Models\Banner', attribute: 'title');

        return response()->json();
    }

    public function billToUser(Request $request)
    {
        if ($request->payment_stts == 'Unpaid') {
            $request->validate([
                'user_id' => 'required',
            ], ['user_id.required' => 'Customer field is required']);
        }
        if ($request->banner_type == 'store_wise') {
            $bill_to_type = 'vendor';
            $bill_to = $request->store_id;
        } else {
            $bill_to_type = 'user';
            $bill_to = $request->user_id;
        }

        $invoice = new ManualInvoice;
        $invoice->invoice_id = Helpers::generateInvoiceIdAdmin();
        $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
        $invoice->bill_to = $bill_to;
        $invoice->total_amount =  round(_taxIncludedPrice($request->price, $request->gst_percent, 'actual'));
        $invoice->bill_to_type =  $bill_to_type;
        $invoice->module_id = Config::get('module.current_module_id');
        $invoice->payment_method = 'Cash';
        $invoice->payment_status =  'Paid';
        $invoice->generated_by =  'admin';
        $invoice->reminder_date = null;
        $invoice->payment_date =  date('Y-m-d H:i:s');
        $invoice->financial_year = _currentFinancialYear();
        $invoice->save();

        $InvoiceItem = new InvoiceItem;
        $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
        $InvoiceItem->manual_invoice_id = $invoice->id;
        $InvoiceItem->name = 'Banner - "' . $request->title[0] . '" - ' . $request->validity_count . ' ' . $request->validity_type;
        $InvoiceItem->price = $request->price;
        $InvoiceItem->hsn = $request->hsn;
        $InvoiceItem->tax =  $request->gst_percent;
        $InvoiceItem->tax_amount = _taxPrice($request->price, $request->gst_percent);
        $InvoiceItem->save();

        $data = _createBillPdf($invoice, 'admin');
        $invoice->update(['pdf' => $data['pdf']]);
        try {
        } catch (\Throwable $th) {
            //
        }
    }
    public function getUpdateView(string|int $id): View
    {
        $banner = $this->bannerRepo->getFirstWithoutGlobalScopeWhere(params: ['id' => $id]);
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $zones = $this->zoneRepo->getList();
        $categories = Category::where('module_id', Config::get('module.current_module_id'))->where('status', 1)->get();
        $users = User::where('status', 1)->get();
        return view(BannerViewPath::UPDATE[VIEW], compact('banner', 'language', 'defaultLang', 'zones', 'categories', 'users'));
    }

    public function update(BannerUpdateRequest $request, $id): JsonResponse
    {
        $banner = $this->bannerRepo->getFirstWithoutGlobalScopeWhere(params: ['id' => $id]);
        $banner = $this->bannerRepo->update(id: $id, data: $this->bannerService->getUpdateData(request: $request, banner: $banner));
        $this->translationRepo->updateByModel(request: $request, model: $banner, modelPath: 'App\Models\Banner', attribute: 'title');

        return response()->json();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->bannerRepo->delete(id: $request['id']);
        Toastr::success(translate('messages.banner_deleted_successfully'));
        return back();
    }


    public function getSearchList(Request $request): JsonResponse
    {
        $banners = $this->bannerRepo->getSearchedList(
            searchValue: $request['search'],
            dataLimit: 50
        );

        return response()->json([
            'view' => view(BannerViewPath::SEARCH[VIEW], compact('banners'))->render(),
            'count' => $banners->count()
        ]);
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->bannerRepo->update(id: $request['id'], data: ['status' => $request['status']]);
        Toastr::success(translate('messages.banner_status_updated'));
        return back();
    }

    public function updateFeatured(Request $request): RedirectResponse
    {
        $this->bannerRepo->update(id: $request['id'], data: ['featured' => $request['status']]);
        Toastr::success(translate('messages.banner_featured_status_updated'));
        return back();
    }

    public function updateSortOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->bannerRepo->update(id: $request['id'], data: ['sort_order' => (int) $request['sort_order']]);
        return response()->json(['success' => true]);
    }

    public function cancelPublish(int $id): RedirectResponse
    {
        $this->bannerRepo->update(id: $id, data: ['publish_at' => null]);
        Toastr::success(translate('messages.banner_publish_cancelled'));
        return back();
    }
}
