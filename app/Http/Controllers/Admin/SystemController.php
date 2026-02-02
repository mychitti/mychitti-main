<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BusinessSetting;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Order;
use App\Models\SecureFile;
use App\Models\ServiceRequest;
use App\Models\StoreDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Random\Engine\Secure;

class SystemController extends Controller
{

    public function store_data()
    {
        $new_order_count = Order::StoreOrder()->where(['checked' => 0])->count();
        $new_order = Order::StoreOrder()->where(['checked' => 0])->latest()->first();
        $new_parcel_order_count = Order::ParcelOrder()->where(['checked' => 0])->count();
        $new_parcel_order = Order::ParcelOrder()->where(['checked' => 0])->latest()->first();
        $new_enquiry_count = ServiceRequest::where('checked', 0)->count();
        $new_document_count = Helpers::module_permission_check('store_documents') ? StoreDocument::where('verified', 0)->where('checked', 0)->count() : 0;
        $doc = Helpers::module_permission_check('store_documents') ? StoreDocument::where('verified', 0)->first() : null;
        if ($doc) {
            $new_document_url = route('admin.store.view', ['store' =>  $doc->store_id, 'tab' => 'documents']);
        }

        $new_user_count = User::where('checked', 0)->count();
        return response()->json([
            'success' => 1,
            'data' => ['new_user' => $new_user_count, 'new_document' => $new_document_count, 'document_url' => $new_document_url ?? '', 'new_enquiry' => $new_enquiry_count, 'new_order' => $new_order_count > 0 ? $new_order_count : $new_parcel_order_count, 'type' => $new_order_count > 0 ? 'store_order' : 'parcel', 'module_id' => $new_order_count > 0 ? $new_order?->module_id : $new_parcel_order?->module_id]
        ]);
    }

    public function verify_otp(Request $request) // common
    {
        $otp = implode('', $request->otp);
        $phone = BusinessSetting::where('key', 'phone')->first()->value ?? '8777966552';
        $verify = _verify_otp($phone, $otp);
        $verify = 1;
        $file = SecureFile::find($request->file);
        if ($verify) {
            if ($request->has('file')) {
                if($file->file_type == 'api'){
                    $dir = 'app/apis/';
                }
                $filePath = $dir . $file->name;
                // prx($filePath);
                if (Storage::disk('secure')->exists($filePath)) {
                    $url = URL::temporarySignedRoute(
                        'admin.secure.download',
                        now()->addMinutes(1),
                        ['file' => $file->name]
                    );
                    $data = [
                        'user_id' => auth('admin')->id(),
                        'user_type' => 'admin',
                        'action' => 'downloaded file',
                        'description' => 'Downloaded secure file : ' . $filePath,
                    ];
                    _actionLog($data);
                    return response()->json([
                        'status' => true,
                        'download_url' => $url
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'File not found'
                    ]);
                }
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP'
            ]);
        }
    }

    public function send_otp(Request $request) // common
    {
        if ($request->type == 'file_auth') {
            $phone = BusinessSetting::where('key', 'phone')->first()->value ?? '8777966552';
        }
        $fileId = $request->id ?? '';
        $otp  = rand(1000, 9999);
        $sendsms = _send_confirmation_sms('mobile_verification', $phone, $otp);
        $insert  = DB::table('phone_otp')->updateOrInsert([
            'phone' =>  $phone,
        ], [
            'otp' => $otp,
            'created_at' => now()
        ]);
        $data = [
            'user_id' => auth('admin')->id(),
            'user_type' => 'admin',
            'action' => 'requested file',
            'description' => 'Requested to download secure file (Id: ' . $fileId . ')',
        ];
        _actionLog($data);
        if ($insert) {
            return response()->json(['status' => true]);
        } else {
            return response()->json(['status' => false]);
        }
    }
    public function settings()
    {
        return view('admin-views.settings');
    }

    public function settings_update(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:admins,email,' . auth('admin')->id(),
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:admins,phone,' . auth('admin')->id(),
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'l_name.required' => translate('messages.Last name is required!'),
        ]);

        $admin = Admin::find(auth('admin')->id());

        if ($request->has('image')) {
            $image_name = Helpers::update('admin/', $admin->image, 'png', $request->file('image'));
        } else {
            $image_name = $admin['image'];
        }


        $admin->f_name = $request->f_name;
        $admin->l_name = $request->l_name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->image = $image_name;
        $admin->save();
        Toastr::success(translate('messages.admin_updated_successfully'));
        return back();
    }

    public function settings_password_update(Request $request)
    {
        $request->validate([
            'password' => ['required', 'same:confirm_password', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'confirm_password' => 'required',
        ]);

        $admin = Admin::find(auth('admin')->id());
        $admin->password = bcrypt($request['password']);
        $admin->save();
        Toastr::success(translate('messages.admin_password_updated_successfully'));
        return back();
    }

    public function maintenance_mode()
    {
        $maintenance_mode = BusinessSetting::where('key', 'maintenance_mode')->first();
        if (isset($maintenance_mode) == false) {
            DB::table('business_settings')->insert([
                'key' => 'maintenance_mode',
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('business_settings')->where(['key' => 'maintenance_mode'])->update([
                'key' => 'maintenance_mode',
                'value' => $maintenance_mode->value == 1 ? 0 : 1,
                'updated_at' => now(),
            ]);
        }

        if (isset($maintenance_mode) && $maintenance_mode->value) {
            return response()->json(['message' => translate('Maintenance is off.')]);
        }
        return response()->json(['message' => translate('Maintenance is on.')]);
    }

    public function landing_page()
    {
        $landing_page = BusinessSetting::where('key', 'landing_page')->first();
        if (isset($landing_page) == false) {
            DB::table('business_settings')->insert([
                'key' => 'landing_page',
                'value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('business_settings')->where(['key' => 'landing_page'])->update([
                'key' => 'landing_page',
                'value' => $landing_page->value == 1 ? 0 : 1,
                'updated_at' => now(),
            ]);
        }

        if (isset($landing_page) && $landing_page->value) {
            return response()->json(['message' => translate('landing_page_is_off.')]);
        }
        return response()->json(['message' => translate('landing_page_is_on.')]);
    }
}
