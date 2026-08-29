<?php

namespace App\Modules\Ecommerce\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Lead;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\Department;
use App\Models\Attendance;
use App\Models\Staff;
use App\Exports\AttendanceExport;
use App\Models\Banner;
use App\Models\BusinessSetting;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\OfferBanner;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\VendorEmployee;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
 function list(Request $request)
    {
        $key = explode(' ', $request['search']);
        $banners=Banner::where('data',Helpers::get_store_id())->where('created_by','store')
        ->when($key, function($query)use($key){
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%". $value."%");
                }
            });
        })
        ->latest()->paginate(config('default_pagination'));
        return view('ecommerce::vendor.banner.index',compact('banners'));
    }


    public function status(Request $request)
    {
        $banner = Banner::findOrFail($request->id);
        $store_id = $request->status;
        $store_ids = json_decode($banner->restaurant_ids);
        if(in_array($store_id, $store_ids))
        {
            unset($store_ids[array_search($store_id, $store_ids)]);
        }
        else
        {
            array_push($store_ids, $store_id);
        }

        $banner->restaurant_ids = json_encode($store_ids);
        $banner->save();
        Toastr::success(translate('messages.capmaign_participation_updated'));
        return back();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'image' => 'required|max:30720',
            'default_link' => 'max:255',
        ]);

        $store = Helpers::get_store_data();
        $banner = new Banner;
        $banner->title = $request->title;
        $banner->type = 'store_wise';
        $banner->zone_id = $store->zone_id;
        $banner->image = Helpers::upload('banner/', 'png', $request->file('image'));
        $banner->data = $store->id;
        $banner->module_id = $store->module_id;
        $banner->default_link = $request->default_link;
        $banner->created_by = 'store';
        $banner->status = 0;
        $banner->approval = 0;
        $banner->save();
        Toastr::success('Banner submitted for admin approval.');
        return back();
    }

    public function edit($id)
    {
        $banner = Banner::withoutGlobalScope('translate')->findOrFail($id);
        return view('ecommerce::vendor.banner.edit', compact('banner'));
    }

    public function status_update(Request $request)
    {
        $banner = Banner::where('id', $request->id)->where('data', Helpers::get_store_id())->firstOrFail();
        if ($banner->approval !== 1) {
            Toastr::error('Banner must be approved by admin before it can be activated.');
            return back();
        }
        $banner->status = $request->status;
        if ($banner->publish_at && $banner->publish_at <= now()) {
            $banner->publish_at = null;
        }
        $banner->save();
        Toastr::success(translate('messages.banner_status_updated'));
        return back();
    }
    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title' => 'required',
            'default_link' => 'max:255',

        ]);
        $banner->title = $request->title;
        $banner->image = $request->has('image') ? Helpers::update('banner/', $banner->image, 'png', $request->file('image')) : $banner->image;
        $banner->default_link = $request->default_link;
        $banner->save();
        Toastr::success(translate('messages.banner_updated_successfully'));
        return back();
    }

    public function delete(Banner $banner)
    {
        if (Storage::disk('public')->exists('banner/' . $banner['image'])) {
            Storage::disk('public')->delete('banner/' . $banner['image']);
        }
        $banner->translations()->delete();
        $banner->delete();
        Toastr::success(translate('messages.banner_deleted_successfully'));
        return back();
    }


    public function offer_banner(Request $request)
    {
        $banners  = OfferBanner::where('store_id', Helpers::get_store_id())->paginate(10);
        return view('ecommerce::vendor.banner.offer_banner', compact('banners'));
    }
    public function store_offer_banner(Request $request)
    {
        $request->validate([
            'banner' => 'required',
        ]);

        $image_name = Helpers::upload('banners/', 'png', $request->file('banner'));
        $banner = new OfferBanner();
        $banner->store_id = Helpers::get_store_id();
        $banner->image = $image_name;
        $banner->title = $request->title;
        $banner->start_date = $request->start_date;
        $banner->end_date = $request->end_date;
        $banner->url = $request->url;
        $banner->save();

        // generate bill 
        $invoice = new ManualInvoice();
        $invoice->invoice_id = Helpers::generateInvoiceIdAdmin();
        $invoice->invoice_serial = BusinessSetting::where('key', 'admin_bill_serial_number')->first()->value - 1;
        $invoice->vendor_id = NULL;
        $invoice->bill_to = Helpers::get_store_id();
        $invoice->bill_to_type = 'vendor';
        $invoice->module_id =  Helpers::get_store_data()->module_id;
        $invoice->total_amount = 0;
        $invoice->payment_method = 'Cash';
        $invoice->tax_type = 'gst';
        $invoice->payment_status =  'Paid';
        $invoice->payment_date =  date('Y-m-d');
        $invoice->generated_by =  'admin';
        $invoice->save();

        $InvoiceItem = new InvoiceItem();
        $InvoiceItem->rand_invoice_id = $invoice->invoice_id;
        $InvoiceItem->manual_invoice_id = $invoice->id;
        $InvoiceItem->name = 'Banner (' . $request->title . ')';
        $InvoiceItem->qty = 1;
        $InvoiceItem->price = 0;
        $InvoiceItem->tax =  0;
        $InvoiceItem->hsn =  '';
        $InvoiceItem->save();

        try {
            $data = _createBillPdf($invoice, 'admin');
            $invoice->update(['pdf' => $data['pdf']]);
        } catch (\Throwable $th) {
            //
        }

        Toastr::success('Offer Banner Saved Successfully');
        return back();
    }
    public function delete_offer_banner(Request $request)
    {
        OfferBanner::where('id', $request->id)->where('store_id', Helpers::get_store_id())->delete();
        Toastr::success('Offer Banner Deleted Successfully');
        return back();
    }
}
