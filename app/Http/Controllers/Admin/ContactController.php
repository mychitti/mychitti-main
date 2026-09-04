<?php

namespace App\Http\Controllers\Admin;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\BusinessSetting;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ContactMessageExport;
use App\Models\VendorRequirement;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'mobile_number' => 'required',
            'subject' => 'required',
            'message' => 'required',
            'email' => 'required|email:rfc,dns'
        ], [
            'mobile_number.required' => 'Mobile Number is Empty!',
            'subject.required' => ' Subject is Empty!',
            'message.required' => 'Message is Empty!',

        ]);
        $contact = new Contact;
        $contact->name = $request->name;
        $contact->email = $request->email;
        $contact->mobile_number = $request->mobile_number;
        $contact->subject = $request->subject;
        $contact->message = $request->message;
        $contact->save();

        return response()->json(['success' => 'Your Message Send Successfully']);
    }

    public function list(Request $request)
    {
        $key = explode(' ', $request['search']);
        $contacts = Contact::brand('mychitti')->when(isset($key), function($query) use($key) {
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                    ->orWhere('subject', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
                }
            });
        })
        ->orderBy('created_at', 'desc')
        ->paginate(config('default_pagination'));

        $requirements = VendorRequirement::with("store")->whereHas('store', function ($q) {
            $q->visibleOnMychitti();
        })->get();
        return view('admin-views.contacts.list', compact('contacts', 'requirements'));

    }
    public function exportList(Request $request)
    {
        $key = explode(' ', $request['search']);
        $contacts = Contact::brand('mychitti')->orderBy('name')
        ->when(isset($key), function($query) use($key) {
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                    ->orWhere('subject', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%");
                }
            });
        })
        ->get();
        if($request->type == 'csv'){
            return Excel::download(new ContactMessageExport($contacts,$request['search']), 'Contacts.csv');
        }
        return Excel::download(new ContactMessageExport($contacts,$request['search']), 'Contacts.xlsx');
    }

    public function view($id)
    {
        $contact = Contact::findOrFail($id);
        return view('admin-views.contacts.view', compact('contact'));
    }

    public function update(Request $request, $id)
    {
        $contact = Contact::find($id);
        $contact->feedback = $request->feedback;
        $contact->seen = 1;
        $contact->update();
        Toastr::success('Feedback  Update successfully!');
        return redirect()->route('admin.users.contact.contact-list');
    }

    public function destroy(Request $request)
    {
        $contact = Contact::findOrFail($request->id);
        $contact->delete();
        Toastr::success(translate('messages.contact_deleted_successfully'));
        return back();
    }

    public function send_mail(Request $request, $id) 
    {
        $contact = Contact::findOrFail($id);
        $data = array(
            'reply_message' => $request['mail_body'],
            'name' => $contact->name, 
            'subject' => $request['subject'],
            'customer_subject' => $contact->subject,
            'customer_message' => $contact->message,
        );
        try {
            Mail::send('email-templates.customer-message', $data, function ($message) use ($contact, $request) {
                $message->to($contact['email'], BusinessSetting::where(['key' => 'business_name'])->first()->value)
                    ->subject($request['subject']);
            });

            Contact::where(['id' => $id])->update([
                'reply' => json_encode([
                    'subject' => $request['subject'],
                    'body' => $request['mail_body']
                ]),
                'seen'=>1
            ]);
        } catch (\Throwable $th) {
            prx($th->getMessage());
            Toastr::error(translate('Invalied Email Id!'));
            return back();
        }

        Toastr::success('Mail sent successfully!');
        return back();
    }


}
