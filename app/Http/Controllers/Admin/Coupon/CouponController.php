<?php

namespace App\Http\Controllers\Admin\Coupon;

use App\Contracts\Repositories\CouponRepositoryInterface;
use App\Contracts\Repositories\TranslationRepositoryInterface;
use App\Contracts\Repositories\ZoneRepositoryInterface;
use App\Enums\ExportFileNames\Admin\Coupon;
use App\Enums\ViewPaths\Admin\Coupon as CouponViewPath;
use App\Exports\CouponExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\CouponAddRequest;
use App\Http\Requests\Admin\CouponUpdateRequest;
use App\Models\CouponCondition;
use App\Models\ServiceCoupon;
use App\Models\Store;
use App\Services\CouponService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CouponController extends BaseController
{
    public function __construct(
        protected CouponRepositoryInterface $couponRepo,
        protected CouponService $couponService,
        protected TranslationRepositoryInterface $translationRepo,
        protected ZoneRepositoryInterface $zoneRepo
    ) {}

    public function index(?Request $request): View|Collection|LengthAwarePaginator|null
    {
        return $this->getAddView($request);
    }

    public function service_coupon_delete(Request $request){
        ServiceCoupon::find($request->id)->delete();
        Toastr::success('Deleted Successfully');
        return redirect()->back();

    }
    public function service_coupon_store(Request $request)
    {
        //   prx(  $request->store_ids);
        $request->validate([
            'store_ids' => 'required_without_all:for_all_existing,for_upcoming',
            'for_all_existing' => 'required_without_all:store_ids,for_upcoming',
            'for_upcoming' => 'required_without_all:store_ids,for_all_existing',
            'amount' => 'required',
            'code' => 'required',
        ]);
        if ($request->has('for_all_existing')) {
            $stores = DB::table('stores')->where('module_id', 6)->get();
            foreach ($stores as $key => $store) {
                $coupon = new ServiceCoupon();
                $coupon->user_type_id = $store->id;
                $coupon->user_type = 'store';
                $coupon->code = $request->code;
                $coupon->amount = $request->amount;
                $coupon->use_limit = $request->limit ?? 1;
                $coupon->save();
            }
        } else {
            if ($request->has('store_ids')) {
                foreach ($request->store_ids as $key => $store_id) {
                    $coupon = new ServiceCoupon();
                    $coupon->user_type_id = $store_id;
                    $coupon->user_type = 'store';
                    $coupon->code = $request->code;
                    $coupon->amount = $request->amount;
                    $coupon->use_limit = $request->limit ?? 1;
                    $coupon->save();
                }
                $stores = DB::table('stores')->whereIn('id', $request->store_ids)->get(); // for notification
                // prx( $stores);
            }
        }
        if ($request->has('for_upcoming')) {
            $coupon = new ServiceCoupon();
            $coupon->user_type_id = 0;
            $coupon->user_type = 'new_stores';
            $coupon->code = $request->code;
            $coupon->amount = $request->amount;
            $coupon->use_limit = $request->limit ?? 1;
            $coupon->save();
        }

        // $store = Store::find($request->store_id);
        if($request->has('store_ids') || $request->has('for_all_existing')){
            foreach ($stores as $key => $store) {
                $smsTemplate = "Great news! You've got a COUPON: " . $request->code . ". Use it now! Check the My Chitti Vendor App for details. - My Chitti Team";
                _sendSMS($store->phone, $smsTemplate);
            }
        }
     
        Toastr::success('Coupon Added Successfully');
        return back();
    }
    public function service_coupon(Request $request)
    {
        $coupons = ServiceCoupon::whereNot('user_type_id', 0)->paginate(10);
        $upcoming_coupons = ServiceCoupon::where('user_type_id', 0)->get();
       
        return view('admin-views.coupon.service_coupon', compact('coupons', 'upcoming_coupons'));
    }
    private function getAddView($request): View
    {
        $coupons = $this->couponRepo->getListWhere(
            searchValue: $request['search'],
            filters: ['created_by' => 'admin', 'module_id' => Config::get('module.current_module_id')],
            relations: ['module'],
            dataLimit: config('default_pagination'),
        );
        $coupon_conditions = CouponCondition::where('status', 1)->get();
        $customer = $request['customer'];
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $zones = $this->zoneRepo->getList();
        return view(CouponViewPath::INDEX[VIEW], compact('coupons', 'language', 'coupon_conditions', 'defaultLang', 'zones', 'customer'));
    }

    public function add(CouponAddRequest $request): RedirectResponse
    {
        $coupon = $this->couponRepo->add(data: $this->couponService->getAddData(request: $request, moduleId: Config::get('module.current_module_id')));
        $this->translationRepo->addByModel(request: $request, model: $coupon, modelPath: 'App\Models\Coupon', attribute: 'title');
        Toastr::success(translate('messages.coupon_added_successfully'));
        return back();
    }

    public function getUpdateView(string|int $id): View
    {
        $coupon = $this->couponRepo->getFirstWithoutGlobalScopeWhere(params: ['id' => $id]);
        $language = getWebConfig('language');
        $defaultLang = str_replace('_', '-', app()->getLocale());
        $zones = $this->zoneRepo->getList();
        $coupon_conditions = CouponCondition::where('status', 1)->get();

        return view(CouponViewPath::UPDATE[VIEW], compact('coupon', 'language', 'coupon_conditions','defaultLang', 'zones'));
    }

    public function update(CouponUpdateRequest $request, $id): RedirectResponse
    {
        $coupon = $this->couponRepo->update(id: $id, data: $this->couponService->getAddData(request: $request, moduleId: Config::get('module.current_module_id')));
        $this->translationRepo->updateByModel(request: $request, model: $coupon, modelPath: 'App\Models\Coupon', attribute: 'title');
        Toastr::success(translate('messages.coupon_updated_successfully'));
        return back();
    }

    public function updateStatus(Request $request): RedirectResponse
    {
        $this->couponRepo->update(id: $request['id'], data: ['status' => $request['status']]);
        Toastr::success(translate('messages.coupon_status_updated'));
        return back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $this->couponRepo->delete(id: $request['id']);
        Toastr::success(translate('messages.coupon_deleted_successfully'));
        return back();
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $coupons = $this->couponRepo->getExportList($request);
        $data = [
            'data' => $coupons,
            'search' => $request['search'] ?? null
        ];
        if ($request['type'] == 'csv') {
            return Excel::download(new CouponExport($data), Coupon::EXPORT_CSV);
        }
        return Excel::download(new CouponExport($data), Coupon::EXPORT_XLSX);
    }
}
