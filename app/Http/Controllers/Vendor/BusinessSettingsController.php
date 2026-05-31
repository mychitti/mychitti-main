<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;

use Illuminate\Support\Facades\DB;

use App\Models\StoreSchedule;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;
use App\Models\AccountDetail;
use App\Models\StoreConfig;
use App\Models\StoreDocument;
use App\Models\StoreSignature;
use App\Models\StoreTnc;
use App\Models\TempStoreStatus;
use App\Models\Translation;
use App\Models\VendorEmployee;
use App\Models\VendorRequirement;
use Illuminate\Support\Facades\Validator;

class BusinessSettingsController extends Controller
{
    public function update_statuses(Request $request)
    {
        $store = Helpers::get_store_data();
        $store_statuses = $store->lead_statuses;
        $existingStatuses = array_filter(explode(',', $store_statuses));

        $submittedStatuses = $request->has('statuses') ? $request->statuses : [];

        $addedIds = array_diff($submittedStatuses, $existingStatuses);   // newly added
        $retainedIds = array_intersect($existingStatuses, $submittedStatuses); // existing and still selected
        $removedIds = array_diff($existingStatuses, $submittedStatuses); // removed

        $leadStatuses = array_filter(explode(',', $store->lead_statuses));
        if (!empty($addedIds)) {
            foreach ($addedIds as $id) {
                if (!in_array($id, $leadStatuses)) {
                    $leadStatuses[] = $id;
                }
                $store->lead_statuses = implode(',', $leadStatuses);
                $store->save();
            }
        }
        Toastr::success(translate('messages.updated_successfully'));
        return back();
    }
    public function uploadImage(Request $request)
    {
        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:30720'
        ]);

        if ($request->hasFile('upload')) {
            $imagePath = Helpers::upload('vendor/common/', 'png', $request->file('upload'));

            return response()->json([
                'uploaded' => true,
                'url' => asset('/storage/app/public/vendor/common/' . $imagePath)
            ]);
        }

        return response()->json([
            'uploaded' => false,
            'error' => [
                'message' => 'No file was uploaded.'
            ]
        ], 400);
    }

    public function store_index()
    {
        $store = $shop = Helpers::get_store_data();
        $store = Store::withoutGlobalScope('translate')->findOrFail($store->id);
        $store_documents = StoreDocument::where('store_id', $store->id)->where('status', 1)->get();
        $id_doc = $store_documents->where('doc_type', 'id_doc')->first();
        $gst_doc = $store_documents->where('doc_type', 'gst_doc')->first();
        return view('vendor-views.business-settings.restaurant-index', compact('store', 'shop', 'store_documents', 'id_doc', 'gst_doc'));
    }

    public function store_setup(Store $store, Request $request)
    {
        $request->validate([
            'gst' => 'required_if:gst_status,1',
            'per_km_delivery_charge' => 'required_with:minimum_delivery_charge',
            'minimum_delivery_charge' => 'required_with:per_km_delivery_charge'
        ], [
            'gst.required_if' => translate('messages.gst_can_not_be_empty'),
        ]);

        if (isset($request->maximum_shipping_charge) && ($request->minimum_delivery_charge > $request->maximum_shipping_charge)) {
            Toastr::error(translate('Maximum delivery charge must be greater than minimum delivery charge.'));
            return back();
        }

        $store->minimum_order = $request->minimum_order ?? 0;
        $store->gst = json_encode(['status' => $request->gst_status, 'code' => $request->gst]);
        // $store->delivery_charge = $store->self_delivery_system?$request->delivery_charge??0: $store->delivery_charge;
        $store->minimum_shipping_charge = $store->self_delivery_system ? $request->minimum_delivery_charge ?? 0 : $store->minimum_shipping_charge;
        $store->per_km_shipping_charge = $store->self_delivery_system ? $request->per_km_delivery_charge ?? 0 : $store->per_km_shipping_charge;
        $store->per_km_shipping_charge = $store->self_delivery_system ? $request->per_km_delivery_charge ?? 0 : $store->per_km_shipping_charge;
        $store->maximum_shipping_charge = $store->self_delivery_system ? $request->maximum_shipping_charge ?? 0 : $store->maximum_shipping_charge;
        $store->order_place_to_schedule_interval = $request->order_place_to_schedule_interval;
        $store->delivery_charges_on = $request->delivery_charges_on ?? 0;
        $store->delivery_time = $request->minimum_delivery_time . '-' . $request->maximum_delivery_time . ' ' . $request->delivery_time_type;
        $store->save();
        Toastr::success(translate('messages.store_settings_updated'));
        return back();
    }

    public function edit_leaves(Request $request)
    {
        $store = DB::table('store_configs')->where('store_id', Helpers::get_store_id())->first();
        if ($store) {
            DB::table('store_configs')->where('store_id', Helpers::get_store_id())->update(['cl_for_employees' => $request->cl, 'sl_for_employees' => $request->sl]);
        } else {
            DB::table('store_configs')->insert(['store_id' => Helpers::get_store_id(), 'cl_for_employees' => $request->cl, 'sl_for_employees' => $request->sl, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        }
        Toastr::success('Updated Successfully');
        return back(); 
    }

    public function new_bank_account(Request $request)
    {
        $type = $request->input('type', 'invoice');
        $paymentType = $type === 'pos' ? $request->input('payment_type', 'bank') : 'bank';

        $rules = [
            'type' => 'nullable|string|in:invoice,quotation,pos',
            'payment_type' => 'nullable|string|in:bank,upi',
            'upi_id' => 'nullable|string|max:255',
        ];

        if ($paymentType === 'upi') {
            $rules['upi_id'] = 'required|string|max:255';
        } else {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['account_holder_name'] = 'required|string|max:255';
            $rules['account_number'] = 'required|string|max:255';
            $rules['ifsc_code'] = 'required|string|max:255';
        }

        $request->validate($rules);

        $account = new AccountDetail();
        $account->user_type = 'vendor';
        $account->type =  $type;
        $account->user_id =  Helpers::get_store_id();
        $account->payment_type = $paymentType;
        $account->account_holder_name = $paymentType === 'bank' ? $request->account_holder_name : null;
        $account->account_number = $paymentType === 'bank' ? $request->account_number : null;
        $account->bank_name = $paymentType === 'bank' ? $request->bank_name : null;
        $account->ifsc_code  = $paymentType === 'bank' ? $request->ifsc_code : null;
        $account->upi_id = $request->filled('upi_id') ? $request->upi_id : null;
        if ($request->hasFile('upi_qr_code')) {
            $account->upi_qr_code = Helpers::upload('store/documents/', 'png', $request->file('upi_qr_code'));
        }
        $account->save();
        if ($request->form_type == 'ajax') {
            return response()->json(['status' => true, 'msg' => "Added  Successfully", 'action' => 'add_bankaccount']);
        } else {
            Toastr::success('Added Successfully');
            return back();
        }
        //  bank details update  end==========================
    }
    public function tnc_save(Request $request)
    {
        $tnc = new StoreTnc();
        $tnc->store_id = Helpers::get_store_id();
        $tnc->tnc_for = $request->for;
        $tnc->content = $request->tnc_content;
        $tnc->tnc_type = $request->tnc_type;
        $tnc->save();

        Toastr::success('Added Successfully');
        return redirect()->back();
    }
    public function tnc_fetch(Request $request, $id)
    {
        $content = StoreTnc::find($id)?->content;
        return $content;
    }
    public function tnc_delete(Request $request, $id)
    {
        StoreTnc::find($id)->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }
    public function tnc_update(Request $request)
    {
        $tnc =  StoreTnc::find($request->tnc_id);
        $tnc->tnc_for = $request->for;
        $tnc->content = $request->tnc_content;
        $tnc->save();

        Toastr::success('Updated Successfully');
        return redirect()->back();
    }
    public function config_save(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $store = Store::withoutGlobalScopes()->with('storeConfig')->findOrFail($storeId);

        $allowed = [
            'quotation_footer_line',
            'jrsdctn_quote_status',
            'jrsdctn_quote_statement',
            'jurisdiction_statement_status',
            'returnable_rr_tnc',
            'non_returnable_rr_tnc',
            'returnable_rr_tnc_content',
            'non_returnable_rr_tnc_content',
            'reminder_day_before',
        ];

        // filter only the keys present in request
        $data = $request->only($allowed);

        // only update if something is passed
        if (!empty($data)) {
            StoreConfig::updateOrCreate(
                ['store_id' => $store->id],
                $data
            );
            Toastr::success('Updated Successfully');
        } else {
            Toastr::info('No fields to update');
        }

        return back();
    }
    public function my_documents()
    {
        $store_id = Helpers::get_store_id();
        $store = Store::where('id',  $store_id)->first();
        return view('vendor-views.business-settings.my-documents', compact('store'));
    }
    public function update_doc(Request $request)
    {
        $storeId = Helpers::get_store_id();
        $store = Store::findOrFail($storeId);

        $type = $request->file_type; // id_doc | gst_doc
        $fileFieldFront = $type == 'id_doc' ? 'id_doc' : 'gst_doc';

        // Save number fields if provided
        if ($type == 'gst_doc' && $request->filled('gst_number')) {
            $store->gst_number = $request->gst_number;
            $store->save();
        }
        if ($type == 'id_doc' && $request->filled('id_number')) {
            $store->id_number = $request->id_number;
            $store->save();
        }

        if (!$request->hasFile($fileFieldFront)) {
            Toastr::success("Details updated successfully");
            return back();
        }

        // ✅ Check history
        $hasHistory = StoreDocument::where('store_id', $storeId)
            ->where('doc_type', $type)
            ->exists();

        if (!$hasHistory && !empty($store->$type)) {
            StoreDocument::create([
                'store_id'     => $storeId,
                'doc_type'     => $type,
                'file_path'    => $store->$type,
                'status'       => 0,
                'verified'     => 1,
                'version_type' => 'Initial',
            ]);
        }

        // ✅ Deactivate old documents
        StoreDocument::where('store_id', $storeId)
            ->where('doc_type', $type)
            ->update(['status' => 0]);

        /* =========================
       ✅ UPLOAD FRONT FILE
    ==========================*/
        $file = $request->file($fileFieldFront);
        $extension = $file->getClientOriginalExtension();
        $uploadedPath = Helpers::upload('store/docs/', $extension, $file);

        /* =========================
       ✅ UPLOAD BACK FILE (OPTIONAL)
    ==========================*/
        $backSidePath = null;

        if ($type == 'id_doc' && $request->hasFile('id_doc_back')) {
            $backFile = $request->file('id_doc_back');
            $backExt  = $backFile->getClientOriginalExtension();
            $backSidePath = Helpers::upload('store/docs/', $backExt, $backFile);
        }

        // ✅ Insert BOTH in same row
        StoreDocument::create([
            'store_id'   => $storeId,
            'doc_type'   => $type,
            'file_path' => $uploadedPath,
            'back_side' => $backSidePath,   // ✅ SAME ROW
            'status'    => 1,
        ]);

        $actorType = auth('vendor')->check() ? 'vendor' : 'vendor_employee';
        $actorId   = auth($actorType)->id();
        _logVendorFile($actorType, $actorId, $storeId, $type === 'gst_doc' ? 'store_gst_doc' : 'store_id_doc', 'store/docs/' . $uploadedPath);
        if ($backSidePath) {
            _logVendorFile($actorType, $actorId, $storeId, $type === 'gst_doc' ? 'store_gst_doc_back' : 'store_id_doc_back', 'store/docs/' . $backSidePath);
        }

        // ✅ ADMIN NOTIFICATION
        $msg = "New " . ($type == 'id_doc' ? 'ID Document' : 'GST Document') .
            " uploaded by " . $store->name . ". Please verify the document.";

        $url = route('admin.store.view', [
            'store' => $storeId,
            'tab'   => 'documents'
        ]);

        _inAppNotification("New Vendor Document", $msg, null, 0, $url, 'admin');

        Toastr::success("Document updated successfully");
        return back();
    }


    public function common_terms_and_conditions()
    {
        $store_id = Helpers::get_store_id();
        $store = Store::where('id',  $store_id)->first();
        $tAndCContent = DB::table('vendor_terms_conditions')->where('type', 'for_customer')->where('vendor_id',  $store_id)->first();
        return view('vendor-views.business-settings.common-tnc', compact('tAndCContent', 'store'));
    }
    public function terms_and_conditions_save(Request $request)
    {

        $storeId = Helpers::get_store_id();

        // invoice settings update ==========================
        $data = [
            'invoice_footer_line'     => $request->invoice_footer_line ?? 0,
            'jurisdiction_statement'  => $request->jurisdiction_statement,
            'paid_unpaid_options'  => $request->paid_unpaid_options,
        ];

        if ($request->hasFile('image')) {
            $data['signature'] = Helpers::upload('store/signature/', 'png', $request->file('image'));
        }
        $store = Store::with('storeConfig')->findOrFail($storeId);
        $store->update($data);

        $invoice_status = $request->invoice_sign_status ?? 0;
        $jurisdiction_statement_status = $request->jurisdiction_statement_status ?? 0;
        $tnc_invoice_status = $request->tnc_invoice_status ?? 0;
        $tnc_quotation_status = $request->tnc_quotation_status ?? 0;
        $jurisdiction_statement_status = $request->jurisdiction_statement_status ?? 0;
        $bank_details_status = $request->bank_details_status ?? 0;
        $paid_unpaid_options = $request->paid_unpaid_options ?? 'paid_unpaid';

        $store->storeConfig()->updateOrCreate(
            ['store_id' => $store->id], 
            [
                'paid_unpaid_options' => $paid_unpaid_options,
                'invoice_sign_status' => $invoice_status,
                'jurisdiction_statement_status' => $jurisdiction_statement_status,
                'tnc_invoice_status' => $tnc_invoice_status,
                'tnc_quotation_status' => $tnc_quotation_status,
                'default_invoice_tnc_id' => $request->default_invoice_tnc_id ?: null,
                'bank_details_status' => $bank_details_status,
            ],
        );
        // invoice settings update end ==========================


        // terms and conditions update  ==========================
        $types = [
            'for_customer' => $request->content,
            'for_quotation' => $request->content2,
        ];

        foreach ($types as $type => $content) {
            $data = [
                'vendor_id' => $storeId,
                'type' => $type,
                'terms_n_conditons' => $content,
                'updated_at' => now(),
            ];

            $exists = DB::table('vendor_terms_conditions')
                ->where('vendor_id', $storeId)
                ->where('type', $type)
                ->exists();

            if ($exists) {
                DB::table('vendor_terms_conditions')
                    ->where('vendor_id', $storeId)
                    ->where('type', $type)
                    ->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('vendor_terms_conditions')->insert($data);
            }
        }
        // terms and conditions update  end==========================


        Toastr::success('Saved Successfully');
        return back();
    }
    public function common_tnc_save(Request $request)
    {
        $storeId = Helpers::get_store_id();

        DB::table('vendor_terms_conditions')
            ->updateOrInsert(
                [
                    'vendor_id' => $storeId,
                    'type'      => 'for_customer'
                ],
                [
                    'terms_n_conditons' => $request->content,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );

        Toastr::success('Saved Successfully');
        return back();
    }
    public function common_pp_save(Request $request)
    {
        $storeId = Helpers::get_store_id();

        DB::table('store_privacy_policy')
            ->updateOrInsert(
                [
                    'store_id' => $storeId,
                ],
                [
                    'content' => $request->content,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );

        Toastr::success('Saved Successfully');
        return back();
    }

    public function submit_requirement(Request $request)
    {
        $req = new VendorRequirement();
        $req->store_id = Helpers::get_store_id();
        $req->requirement_type = $request->requirement_type;
        $req->description = $request->description;

        if ($request->hasFile('attachment')) {
            $extension = $request->file('attachment')->getClientOriginalExtension();
            $req->file = $request->has('attachment') ? Helpers::upload('requirements/',  $extension, $request->file('attachment')) : null;
        } else {
            $req->file = null;
        }
        $req->save();

        Toastr::success('Requirement Sent Successfully');
        return back();
    }
    public function delete_account(Request $request)
    {
        AccountDetail::find($request->id)->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }
    public function signature_delete(Request $request, $id)
    {
        StoreSignature::find($id)->delete();
        Toastr::success('Deleted Successfully');
        return back();
    }
    public function signature_fetch(Request $request)
    {
        $sign = StoreSignature::find($request->id);
        return $sign ? asset('storage/app/public/store/signature/') . '/' .  $sign->image : '';
    }
    public function signature_save(Request $request)
    {
        $request->validate([
            'image' => 'required',
            'staff' => 'required',
        ]);

        $sign = new StoreSignature();
        $sign->staff_id = $request->staff;
        $sign->type = $request->type;
        $sign->store_id = Helpers::get_store_id();
        $sign->image = Helpers::upload('store/signature/', 'png', $request->file('image'));
        $sign->save();
        if ($request->form_type == 'ajax') {
            return response()->json(['status' => true, 'msg' => "Added  Successfully", 'action' => 'add_sign']);
        } else {
            Toastr::success('Signature Saved Successfully');
            return back();
        }
    }
    public function terms_and_conditions()
    {
        $tncs = StoreTnc::where('store_id', Helpers::get_store_id())->get();
        $store_id = Helpers::get_store_id();
        $accounts = AccountDetail::where('user_type', 'vendor')->where('user_id',  $store_id)->get();
        $staffs = VendorEmployee::where('store_id',  $store_id)->get();
        // prx($staffs);
        $signatures = StoreSignature::with('employee')->where('store_id', $store_id)->get();
        $store = Store::where('id',  $store_id)->first();
        $tAndCContent = DB::table('vendor_terms_conditions')->where('type', 'for_customer')->where('vendor_id',  $store_id)->first();
        $tAndCContentQuoatation = DB::table('vendor_terms_conditions')->where('type', 'for_quotation')->where('vendor_id',  $store_id)->first();
        return view('vendor-views.business-settings.terms-and-conditions', compact('tncs', 'signatures', 'staffs', 'accounts', 'tAndCContent', 'tAndCContentQuoatation', 'store'));
    }
    public function about_us()
    {
        $about_us = StoreConfig::where('store_id', Helpers::get_store_id())->first();
        if ($about_us) {
            $about_us = $about_us->about_us;
        } else {
            $about_us = '';
        }
        return view('vendor-views.business-settings.about-us', compact('about_us'));
    }
    public function about_us_save(Request $request)
    {
        $about_us = StoreConfig::where('store_id', Helpers::get_store_id())->first();

        if (!$about_us) {
            $about_us = new StoreConfig();
            $about_us->store_id = Helpers::get_store_id();
        }
        $about_us->about_us = $request->content;
        $about_us->save();

        Toastr::success('Saved Successfully');
        return back();
    }
    public function updateStoreMetaData(Store $store, Request $request)
    {
        $request->validate([
            'meta_title' => 'required',
            'meta_description' => 'required',
        ]);

        $store->meta_image = $request->has('meta_image') ? Helpers::update('store/', $store->meta_image, 'png', $request->file('meta_image')) : $store->meta_image;

        $store->meta_title = $request->meta_title;
        $store->meta_description = $request->meta_description;

        $store->save();

        Toastr::success(translate('messages.store') . translate('messages.meta_data_updated'));
        return back();
    }
    public function update_social_media(Store $store, Request $request)
    {

        //social media links
        $store->insta_url = $request->insta_url;
        $store->pinterest_url = $request->pinterest_url;
        $store->fb_url = $request->fb_url;
        $store->twitter_url = $request->twitter_url;
        $store->linkedin_url = $request->linkedin_url;

        $store->save();

        Toastr::success(translate('messages.store') . translate('messages.meta_data_updated'));
        return back();
    }
    public function store_status(Store $store, Request $request)
    {
        if ($request->menu == "schedule_order" && !Helpers::schedule_order()) {
            Toastr::warning(translate('messages.schedule_order_disabled_warning'));
            return back();
        }

        if ((($request->menu == "delivery" && $store->take_away == 0) || ($request->menu == "take_away" && $store->delivery == 0)) &&  $request->status == 0) {
            Toastr::warning(translate('messages.can_not_disable_both_take_away_and_delivery'));
            return back();
        }

        if ((($request->menu == "veg" && $store->non_veg == 0) || ($request->menu == "non_veg" && $store->veg == 0)) &&  $request->status == 0) {
            Toastr::warning(translate('messages.veg_non_veg_disable_warning'));
            return back();
        }

        if ($request->menu == "announcement" &&  $request->status == 1 &&  !isset($store->announcement_message)) {
            Toastr::warning(translate('messages.You_need_to_add_announcement_message_first'));
            return back();
        }

        $store[$request->menu] = $request->status;
        $store->save();
        Toastr::success(translate('messages.store settings updated!'));
        return back();
    }

    public function active_status(Request $request)
    {
        $store = Helpers::get_store_data();
        $store->active = $store->active ? 0 : 1;
        $store->save();
        return response()->json(['message' => $store->active ? translate('messages.store_opened') : translate('messages.store_temporarily_closed')], 200);
    }
    public function minimized_menu(Request $request)
    {
        $pref = $request->pref ? 1 : 0; // 1 = minimized, 0 = expanded

        return response()->json([
            'status' => true,
            'message' => 'Preference saved'
        ])->cookie(
            'vendor_minimized_menu',
            $pref,
            60 * 24 * 360
        );
    }


    public function add_schedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ], [
            'end_time.after' => translate('messages.End time must be after the start time')
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }
        $temp = StoreSchedule::where('day', $request->day)->where('store_id', Helpers::get_store_id())
            ->where(function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    return $query->where('opening_time', '<=', $request->start_time)->where('closing_time', '>=', $request->start_time);
                })->orWhere(function ($query) use ($request) {
                    return $query->where('opening_time', '<=', $request->end_time)->where('closing_time', '>=', $request->end_time);
                });
            })
            ->first();

        if (isset($temp)) {
            return response()->json(['errors' => [
                ['code' => 'time', 'message' => translate('messages.schedule_overlapping_warning')]
            ]]);
        }

        $store = Helpers::get_store_data();
        $store_schedule = StoreSchedule::insert(['store_id' => Helpers::get_store_id(), 'day' => $request->day, 'opening_time' => $request->start_time, 'closing_time' => $request->end_time]);
        return response()->json([
            'view' => view('vendor-views.business-settings.partials._schedule', compact('store'))->render(),
        ]);
    }

    public function remove_schedule($store_schedule)
    {
        $store = Helpers::get_store_data();
        $schedule = StoreSchedule::where('store_id', $store->id)->find($store_schedule);
        if (!$schedule) {
            return response()->json([], 404);
        }
        $schedule->delete();
        return response()->json([
            'view' => view('vendor-views.business-settings.partials._schedule', compact('store'))->render(),
        ]);
    }


    public function site_direction_vendor(Request $request)
    {
        session()->put('site_direction_vendor', ($request->status == 1 ? 'ltr' : 'rtl'));
        return response()->json();
    }
}
