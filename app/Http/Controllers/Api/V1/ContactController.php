<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function helpAndSupport(Request $request){
        $mail = _footerInfo('email_address');
        $phone = _footerInfo('phone');
            return response()->json(['phone' => $phone, 'mail' => $mail],200);

    }
    public function helpAndSupportForm(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'subject' => 'required',
            'message' => 'required|min:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $contact = new Contact;
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->subject = $request->subject;
        $contact->message = $request->message;
        if ($contact->save()) {
            return response()->json(['message' => "Message sent successfully"],200);
        } else {
            return response()->json(['message' => "Some Error OCcured"],403);
        }
    }
}
