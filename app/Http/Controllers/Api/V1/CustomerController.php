<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Item;
use App\Models\User;
use App\Models\Zone;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\CentralLogics\SuspiciousActivity;
use App\Models\OrderReference;
use Illuminate\Support\Carbon;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting; 
use App\Models\Coupon;
use App\Models\InvoiceItem;
use App\Models\ManualInvoice;
use App\Models\ServiceInvoice;
use App\Models\Store;
use App\Models\WalletTransaction;
use App\Models\AccountDeletion;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\Password;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Mpdf\Mpdf;


class CustomerController extends Controller
{
    public function address_list(Request $request)
    {
        $limit = $request['limit'] ?? 10;
        $offset = $request['offset'] ?? 1;

        $addresses = CustomerAddress::where('user_id', $request->user()->id)->latest()->paginate($limit, ['*'], 'page', $offset);

        $data =  [
            'total_size' => $addresses->total(),
            'limit' => $limit,
            'offset' => $offset,
            'addresses' => Helpers::address_data_formatting($addresses->items())
        ];
        return response()->json($data, 200);
    }
  public function terms_n_conditions()
    {
        $content = DB::table('data_settings')->where('type', 'admin_landing_page')->where('key', 'terms_and_conditions')->value('value');
        return response()->json($content, 200);

    }
    public function delete_reasons(Request $request)
    {
        $data = DB::table('delete_account_reasons')->where('user_type', 'user')->select('id','reason')->get();
        return response()->json($data, 200);
    }
    
    public function remove_account(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reason_id'    => 'required',
            'other_reason' => 'required_if:reason_id,5',
        ],[
            'other_reason.required_if' => 'Other Reason is required'
        ]); 
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();

        if (Order::where('user_id', $user->id)->whereIn('order_status', ['pending', 'accepted', 'confirmed', 'processing', 'handover', 'picked_up'])->count()) {
            return response()->json(['errors' => [['code' => 'on-going', 'message' => translate('messages.Please_complete_your_ongoing_and_accepted_orders')]]], 203);
        }
        AccountDeletion::create([
            'user_type'    => 'user',
            'user_id'      => $user->id,
            'reason_id'    => $request->input('reason_id'),
            'other_reason' => $request->input('other_reason'),
            'deleted_at'   => now(),
        ]); 

        $request->user()->token()->revoke();
        if ($user->userinfo) {
            $user->userinfo->delete();
        }
        $user->delete();
        return response()->json(['message'=> 'Deleted Successfully'], 200);
    }
    public function get_bills(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $serviceInvoices = DB::table('service_invoices')->where('bill_to', $request->user_id)->get();
        $manualInvoices = DB::table('manual_invoices')->where('bill_to', $request->user_id)->whereNot('bill_to_type', 'vendor')->get();

        $invoices = $serviceInvoices->merge($manualInvoices);
        return response()->json($invoices, 200);
    }
    public function download_bill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if ($request->type == 'service') {
            $existingInvoice = ServiceInvoice::where('invoice_id', $request->bill_id)->first();
        } else {

            $existingInvoice = ManualInvoice::where('id', $request->bill_id)->first();
            if (!$existingInvoice) {
                $quotations = InvoiceItem::where('rand_invoice_id',  $existingInvoice->invoice_id)->get();

                $service_det = User::find($existingInvoice->bill_to);

                $type = 'user';
                $address = CustomerAddress::where('user_id', $existingInvoice->bill_to)->first();
                if ($address) {
                    $addr['address'] = $address->house . ', ' . $address->floor . ', ' . $address->road . ', ' . $address->address;
                    $addr['city'] = $address->city;
                } else {
                    $addr['address'] = '';
                    $addr['city'] = '';
                }

                $vendor_contact_det = Store::find($existingInvoice->vendor_id);
                $invoice_id = $existingInvoice->invoice_id;
                $tempDir = storage_path('app/mpdf_temp');
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0775, true);
                }

                $mpdf = new Mpdf([
                    'tempDir' => $tempDir,
                    'margin_left' => 0,
                    'margin_right' => 0,
                    'margin_top' => 0,
                    'margin_bottom' => 0,
                ]);

                $html = View::make('vendor-views.invoice.invoice-manual', compact('addr', 'type', 'vendor_contact_det', 'invoice_id', 'service_det', 'quotations'))->render();

                $mpdf->WriteHTML($html);
                $pdfName = 'invoice_' . date('YmdHis') . '.pdf';

                $fileUrl = Helpers::savePdfToPublic($mpdf, 'invoice', $pdfName);
                return response()->json(['pdf' => asset('storage/app/public/invoice') . '/' . $pdfName], 200);
            }
        }
        if (!$existingInvoice) {
            return response()->json(['pdf' => 'null', 'message' => 'Bill not found'], 200);
        } else {
            return response()->json(['pdf' => asset('storage/app/public/invoice') . '/' . $existingInvoice->pdf], 200);
        }
        // prx($existingInvoice); 
    }

    public function createCustomer($phone)
    {
        $ref_by = null;
        $f_name = 'User';
        $l_name = '';
        $email = '';

        $customer_verification = BusinessSetting::where('key', 'customer_verification')->first()->value;

        $user = User::create([
            'f_name' => $f_name,
            'l_name' => $l_name,
            'email' => $email,
            'phone' => $phone,
            'ref_by' =>   $ref_by,
            'password' => bcrypt($f_name . rand(111, 999)),
        ]);
        $user->ref_code = Helpers::generate_referer_code($user);
        $user->save();

        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;

        if ($customer_verification && env('APP_MODE') != 'demo') {
            $otp_interval_time = 60; //seconds
            $verification_data = DB::table('phone_verifications')->where('phone', $phone)->first();

            if (isset($verification_data) &&  Carbon::parse($verification_data->updated_at)->DiffInSeconds() < $otp_interval_time) {
                $time = $otp_interval_time - Carbon::parse($verification_data->updated_at)->DiffInSeconds();
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' =>  translate('messages.please_try_again_after_') . $time . ' ' . translate('messages.seconds')]);
                return response()->json([
                    'errors' => $errors
                ], 405);
            }

            $otp = rand(1000, 9999);
            DB::table('phone_verifications')->updateOrInsert(
                ['phone' => $phone],
                [
                    'token' => $otp,
                    'otp_hit_count' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $mail_status = Helpers::get_mail_status('registration_otp_mail_status_user');
            if (config('mail.status') && $mail_status == '1') {
                Mail::to($email)->send(new EmailVerification($otp, $f_name));
            }
            //for payment and sms gateway addon
            $published_status = 0;
            $payment_published_status = config('get_payment_publish_status');
            if (isset($payment_published_status[0]['is_published'])) {
                $published_status = $payment_published_status[0]['is_published'];
            }

            if ($published_status == 1) {
                $response = SmsGateway::send($phone, $otp);
            } else {
                $response = SMS_module::send($phone, $otp);
            }
            if ($response != 'success') {
                $errors = [];
                array_push($errors, ['code' => 'otp', 'message' => translate('messages.faield_to_send_sms')]);
                return response()->json([
                    'errors' => $errors
                ], 405);
            }
        }
        try {
            $mail_status = Helpers::get_mail_status('registration_mail_status_user');
            if (config('mail.status') && $email && $mail_status == '1') {
                Mail::to($email)->send(new \App\Mail\CustomerRegistration($f_name . ' ' . $l_name));
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }
    }

    // OTP config
    const OTP_RESEND_COOLDOWN = 60;   // seconds between sends
    const OTP_MAX_SENDS       = 5;    // max sends per hour before hard block
    const OTP_SEND_WINDOW     = 3600; // 1 hour window for send count
    const OTP_MAX_ATTEMPTS    = 5;    // wrong guesses before block
    const OTP_BLOCK_MINUTES   = 30;   // how long block lasts
    const OTP_EXPIRY_MINUTES  = 10;   // OTP valid for 10 minutes

    public function verify_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'otp'   => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $record = DB::table('phone_otp')->where('phone', $request->phone)->first();

        if (!$record) {
            return response()->json(['status' => false, 'error' => 'OTP not found. Please request a new one.'], 403);
        }

        // Check if blocked due to too many wrong attempts
        if ($record->is_blocked && Carbon::parse($record->updated_at)->addMinutes(self::OTP_BLOCK_MINUTES)->isFuture()) {
            $wait = Carbon::parse($record->updated_at)->addMinutes(self::OTP_BLOCK_MINUTES)->diffInMinutes(now()) + 1;
            return response()->json(['status' => false, 'error' => "Too many wrong attempts. Try again in {$wait} minutes."], 429);
        }

        // Check OTP expiry
        if (!$record->expires_at || Carbon::parse($record->expires_at)->isPast()) {
            return response()->json(['status' => false, 'error' => 'OTP has expired. Please request a new one.'], 403);
        }

        // Wrong OTP
        if ($record->otp != $request->otp) {
            $newAttempts = $record->attempt_count + 1;
            $shouldBlock = $newAttempts >= self::OTP_MAX_ATTEMPTS;

            DB::table('phone_otp')->where('phone', $request->phone)->update([
                'attempt_count' => $newAttempts,
                'is_blocked'    => $shouldBlock ? 1 : 0,
                'updated_at'    => now(),
            ]);

            SuspiciousActivity::checkOtp($request->phone, null, $request->ip());

            if ($shouldBlock) {
                return response()->json(['status' => false, 'error' => 'Too many wrong attempts. Your OTP has been blocked for ' . self::OTP_BLOCK_MINUTES . ' minutes.'], 429);
            }

            $remaining = self::OTP_MAX_ATTEMPTS - $newAttempts;
            return response()->json(['status' => false, 'error' => "Invalid OTP. {$remaining} attempt(s) remaining."], 403);
        }

        // Valid — clear the OTP
        DB::table('phone_otp')->where('phone', $request->phone)->update([
            'otp'           => null,
            'attempt_count' => 0,
            'is_blocked'    => 0,
            'expires_at'    => null,
            'updated_at'    => now(),
        ]);

        return response()->json(['verified' => true], 200);
    }

    public function sendotp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $record = DB::table('phone_otp')->where('phone', $request->phone)->first();

        // Block check
        if ($record && $record->is_blocked && Carbon::parse($record->updated_at)->addMinutes(self::OTP_BLOCK_MINUTES)->isFuture()) {
            $wait = Carbon::parse($record->updated_at)->addMinutes(self::OTP_BLOCK_MINUTES)->diffInMinutes(now()) + 1;
            return response()->json(['status' => false, 'message' => "Account temporarily blocked. Try again in {$wait} minutes."], 429);
        }

        // Cooldown between sends
        if ($record && $record->expires_at && Carbon::parse($record->expires_at)->subMinutes(self::OTP_EXPIRY_MINUTES)->addSeconds(self::OTP_RESEND_COOLDOWN)->isFuture()) {
            $wait = self::OTP_RESEND_COOLDOWN - Carbon::parse($record->updated_at)->diffInSeconds(now());
            return response()->json(['status' => false, 'message' => "Please wait {$wait} seconds before requesting a new OTP."], 429);
        }

        // Max sends per hour
        $sendCount = $record ? $record->send_count : 0;
        if ($record && Carbon::parse($record->created_at)->addSeconds(self::OTP_SEND_WINDOW)->isFuture()) {
            if ($sendCount >= self::OTP_MAX_SENDS) {
                SuspiciousActivity::checkOtp($request->phone, null, $request->ip());
                return response()->json(['status' => false, 'message' => 'Maximum OTP requests exceeded. Try again later.'], 429);
            }
        } else {
            // Reset window
            $sendCount = 0;
        }

        $userExists = User::where('phone', $request->phone)->exists();
        if (!$userExists) {
            $this->createcustomer($request->phone);
        }

        $otp     = rand(1000, 9999);
        $expires = now()->addMinutes(self::OTP_EXPIRY_MINUTES);
        $sendsms = _send_confirmation_sms('mobile_verification', $request->phone, $otp);

        DB::table('phone_otp')->updateOrInsert(
            ['phone' => $request->phone],
            [
                'otp'           => $otp,
                'send_count'    => $sendCount + 1,
                'attempt_count' => 0,
                'is_blocked'    => 0,
                'expires_at'    => $expires,
                'created_at'    => $record ? $record->created_at : now(),
                'updated_at'    => now(),
            ]
        );

        SuspiciousActivity::checkOtp($request->phone, null, $request->ip());

        return response()->json([
            'status'     => true,
            'message'    => 'OTP sent successfully.',
            'action'     => 'otp_sent',
            'phone'      => $request->phone,
            'expires_in' => self::OTP_EXPIRY_MINUTES * 60,
        ]);
    }
    public function download_service_bill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $existingInvoice = ServiceInvoice::where('id', $request->bill_id)->first();

        if ($existingInvoice && $existingInvoice->pdf) {
            return response()->json(['pdf' => asset('storage/app/public/invoice') . '/' . $existingInvoice->pdf], 200);
        } else {
            $quotations = InvoiceItem::where('invoice_id',  $existingInvoice->invoice_id)->get();
            $service_det = User::find($existingInvoice->bill_to);
            $vendor_contact_det = Store::find($existingInvoice->vendor_id);
            $invoice_id = $existingInvoice->invoice_id;
            $tempDir = storage_path('app/mpdf_temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0775, true);
            }

            $mpdf = new Mpdf([
                'tempDir' => $tempDir,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
            ]);

            $html = View::make('vendor-views.invoice.invoice', compact('invoice_id', 'vendor_contact_det', 'service_det', 'quotations'))->render();

            $mpdf->WriteHTML($html);
            $pdfName = 'invoice_' . date('YmdHis') . '.pdf';

            $fileUrl = Helpers::savePdfToPublic($mpdf, 'invoice', $pdfName);
            return response()->json(['pdf' => asset('storage/app/public/invoice') . '/' . $pdfName], 200);
        }
    }
    public function add_new_address(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required|max:10',
            'address' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zone = Zone::whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))->get(['id']);
        if (count($zone) == 0) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.service_not_available_in_this_area')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $hasAddress = DB::table('customer_addresses')
            ->where('user_id', $request->user()->id)
            ->exists();
        $address = [
            'user_id' => $request->user()->id,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'floor' => $request->floor,
            'road' => $request->road,
            'house' => $request->house,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'zone_id' => $zone[0]->id,
            'is_default' => $hasAddress ? 0 : 1,
            'created_at' => now(),
            'updated_at' => now()
        ];
        $addressId = DB::table('customer_addresses')->insertGetId($address);
        return response()->json(['message' => translate('messages.successfully_added'), 'id' => $addressId, 'zone_ids' => array_column($zone->toArray(), 'id')], 200);
    }
    public function update_pincode(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pin_code' => ['required', 'regex:/^[1-9][0-9]{5}$/'],
        ], [
            'pin_code.regex' => 'The pin code must be a valid 6-digit Indian pincode.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        DB::table('customer_addresses')->where('id', $id)->update(['pin_code' => $request->pin_code]);
        return response()->json(['message' => translate('messages.updated_successfully')], 200);
    }
    public function update_address(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'contact_person_name' => 'required',
            'address_type' => 'required',
            'contact_person_number' => 'required|max:10',
            'address' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        $zone = Zone::whereContains('coordinates', new Point($request->latitude, $request->longitude, POINT_SRID))->get(['id']);
        if (!$zone) {
            $errors = [];
            array_push($errors, ['code' => 'coordinates', 'message' => translate('messages.service_not_available_in_this_area')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $address = [
            'user_id' => $request->user()->id,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_number' => $request->contact_person_number,
            'address_type' => $request->address_type,
            'address' => $request->address,
            'floor' => $request->floor,
            'road' => $request->road,
            'house' => $request->house,
            'pin_code' => $request->pin_code,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'zone_id' => $zone[0]->id,
            'created_at' => now(),
            'updated_at' => now()
        ];
        DB::table('customer_addresses')->where('id', $id)->update($address);
        return response()->json(['message' => translate('messages.updated_successfully'), 'zone_id' => $zone[0]->id], 200);
    }

    public function get_default_address(Request $request)
    {

        $address = CustomerAddress::where('user_id', $request->user()->id)->where('is_default', 1)->first();

        return response()->json($address, 200);
    }
    public function set_default_address(Request $request, $id)
    {
        $user_id = $request->user()->id;
        DB::transaction(function () use ($user_id, $id) {

            DB::table('customer_addresses')
                ->where('user_id', $user_id)
                ->update(['is_default' => 0]);

            DB::table('customer_addresses')
                ->where('id', $id)
                ->where('user_id', $user_id)
                ->update(['is_default' => 1]);
        });
        return response()->json(['message' => translate('messages.updated_successfully')], 200);
    }
    public function delete_address(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }
        // echo $request->user()->id;die;

        if (DB::table('customer_addresses')->where(['id' => $request['address_id'], 'user_id' => $request->user()->id])->first()) {
            DB::table('customer_addresses')->where(['id' => $request['address_id'], 'user_id' => $request->user()->id])->delete();
            return response()->json(['message' => translate('messages.successfully_removed')], 200);
        }
        return response()->json(['message' => translate('messages.not_found')], 404);
    }

    public function get_order_list(Request $request)
    {
        $orders = Order::where(['user_id' => $request->user()->id])->get();
        return response()->json($orders, 200);
    }

    public function get_order_details(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $details = OrderDetail::where(['order_id' => $request['order_id']])->get();
        foreach ($details as $det) {
            $det['product_details'] = json_decode($det['product_details'], true);
        }
 
        return response()->json($details, 200);
    }

    public function info(Request $request)
    {
        if (!$request->hasHeader('X-localization')) {
            $errors = [];
            array_push($errors, ['code' => 'current_language_key', 'message' => translate('messages.current_language_key_required')]);
            return response()->json([
                'errors' => $errors
            ], 200);
        }

        // Current Language
        $current_language = $request->header('X-localization');
        $user = User::findOrFail($request->user()->id);
        $user->current_language_key = $current_language;
        $user->save();

        $data = $request->user();
        $data['gstNumber'] = $user->gst;
        $data['userinfo'] = $data->userinfo;
        $data['order_count'] = (int)$request->user()->orders->count();
        $data['member_since_days'] = (int)$request->user()->created_at->diffInDays();
        $data['image'] = Helpers::onerror_image_helper($data->image, asset('storage/app/public/profile/') . '/' . $data->image, asset('public/assets/admin/img/160x160/img1.jpg'), 'profile/');
        unset($data['orders']);
        return response()->json($data, 200);
    }

    public function update_profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:users,email,' . $request->user()->id,
            'password' => ['nullable', Password::min(8)],
        ], [
            'f_name.required' => 'First name is required!',
            'l_name.required' => 'Last name is required!',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image = $request->file('image');

        if ($request->has('image')) {
            $imageName = Helpers::update('profile/', $request->user()->image, 'png', $request->file('image'));
        } else {
            $imageName = $request->user()->image;
        }

        if ($request['password'] != null && strlen($request['password']) > 5) {
            $pass = bcrypt($request['password']);
        } else {
            $pass = $request->user()->password;
        }

        // prx($request->all());

        $user = User::where(['id' => $request->user()->id])->first();
        $user->f_name = $request->f_name;
        $user->gst = $request->gstNumber ?? null;
        $user->l_name = $request->l_name;
        $user->email = $request->email;
        $user->image = $imageName;
        $user->password = $pass;
        $user->save();

        if ($user->userinfo) {
            $userinfo = $user->userinfo;
            $userinfo->f_name = $request->f_name;
            $userinfo->l_name = $request->l_name;
            $userinfo->email = $request->email;
            $userinfo->image = $imageName;
            $userinfo->save();
        }
        return response()->json(['message' => translate('messages.successfully_updated')], 200);
    }

    public function update_interest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'interest' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userDetails = [
            'interest' => json_encode($request->interest),
        ];

        User::where(['id' => $request->user()->id])->update($userDetails);

        return response()->json(['message' => translate('messages.interest_updated_successfully')], 200);
    }

    public function update_cm_firebase_token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cm_firebase_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = $request->user();
        DB::table('users')->where('id', $user->id)->update([
            'cm_firebase_token' => $request['cm_firebase_token']
        ]);

        return response()->json(['message' => translate('messages.updated_successfully')], 200);
    }

    public function get_suggested_item(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => 'Zone id is required!']);
            return response()->json([
                'errors' => $errors
            ], 403);
        }


        $zone_id = $request->header('zoneId');

        $interest = $request->user()->interest;
        $interest = isset($interest) ? json_decode($interest) : null;
        // return response()->json($interest, 200);

        $products =  Item::active()->whereHas('store', function ($q) use ($zone_id) {
            $q->whereIn('zone_id', json_decode($zone_id, true));
        })
            ->when(isset($interest), function ($q) use ($interest) {
                return $q->whereIn('category_id', $interest);
            })
            ->whereHas('module.zones', function ($query) use ($zone_id) {
                $query->whereIn('zones.id', json_decode($zone_id, true));
            })
            ->whereHas('store', function ($query) use ($zone_id) {
                $query->when(config('module.current_module_data'), function ($query) {
                    $query->where('module_id', config('module.current_module_data')['id'])->whereHas('zone.modules', function ($query) {
                        $query->where('modules.id', config('module.current_module_data')['id']);
                    });
                })->whereIn('zone_id', json_decode($zone_id, true));
            })
            ->when($interest == null, function ($q) {
                return $q->popular();
            })->limit(5)->get();
        $products = Helpers::product_data_formatting($products, true, false, app()->getLocale());
        return response()->json($products, 200);
    }

    public function update_zone(Request $request)
    {
        if (!$request->hasHeader('zoneId') && is_numeric($request->header('zoneId'))) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }

        $customer = $request->user();
        $customer->zone_id = (int)$request->header('zoneId');
        $customer->save();
        return response()->json([], 200);
    }


    public function review_reminder(Request $request)
    {
        $order = Order::wherehas('OrderReference', function ($query) {
            $query->where('is_reviewed', 0)->where('is_review_canceled', 0);
        })
            ->where('user_id', $request->user()->id)->where('order_status', 'delivered')->where('is_guest', 0)->latest()->select('id')->with('details:id,order_id,item_details')->first();

        if ($order?->details) {
            $images = collect($order->details)->pluck('item_details')->map(function ($itemDetail) {
                $decodeditemDetail = json_decode($itemDetail, true);
                return $decodeditemDetail['image'] ?? null;
            })->filter();
        }

        return response()->json([
            'order_id' => $order?->id ?? null,
            'images' => $images ?? []
        ], 200);
    }

    public function review_reminder_cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        OrderReference::where('order_id', $request->order_id)->update([
            'is_review_canceled' => 1
        ]);
        return response()->json('success', 200);
    }
}
