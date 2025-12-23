<?php

namespace App\Http\Controllers\Front;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Contact;

class McVendorController extends Controller
{
    public function contact()
    {
        return view('front-views.mc_vendor.contact');
    }
    public function send_message(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'message' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $contact = new Contact();
        $contact->name = $request->name;
        $contact->business_name = $request->business_name;
        $contact->type = 'mc_vendor';
        $contact->phone = $request->phone;
        $contact->subject = $request->subject ?? '';
        $contact->message = $request->message;
        $contact->file = $request->hasFile('file') ? Helpers::upload('contact/', 'png', $request->file('file')) : null;
        if ($contact->save()) {
            return response()->json(['message' => "Thank you for contacting MC Vendor Hub Support. We’ve received your request and will get back to you shortly."]);
        } else {
            return response()->json(['message' => "Some Error Occurred"]);
        }
    }
}
