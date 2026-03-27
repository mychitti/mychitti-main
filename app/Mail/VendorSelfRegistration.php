<?php

namespace App\Mail;

use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorSelfRegistration extends Mailable
{
    use Queueable, SerializesModels; 

    /** 
     * Create a new message instance.
     *
     * @return void
     */

    protected $status;
    protected $name;

    public function __construct($status, $name)
    {
        $this->status = $status;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $company_name = BusinessSetting::where('key', 'business_name')->first()->value;

        $status = $this->status;
        if($status == 'approved'){
            $data=EmailTemplate::where('type','store')->where('email_type', 'approve')->first();
        }elseif($status == 'denied'){
            $data=EmailTemplate::where('type','store')->where('email_type', 'deny')->first();
        }else{
            $data=EmailTemplate::where('type','store')->where('email_type', 'registration')->first();
        }
        $template=$data?$data->email_template:1;
        $store_name = $this->name; 

        // Default content when no email template is configured
        $defaultTitle = $status == 'approved' ? 'Your store has been approved!'
            : ($status == 'denied' ? 'Store registration update' : 'Welcome to ' . $company_name . '!');
        $defaultBody = $status == 'approved'
            ? 'Congratulations! Your store <b>{storeName}</b> has been approved. You can now login and start receiving leads.'
            : ($status == 'denied'
                ? 'We regret to inform you that your store registration has been denied. Please contact support for more details.'
                : 'Thank you for registering <b>{storeName}</b> on ' . $company_name . '! Your application is under review. Once approved, you will receive login credentials.');

        // Build login URL for approved status
        $loginSlug = Helpers::get_login_url('store_login_url');
        $loginUrl = $loginSlug ? url('login/' . $loginSlug) : url('login');

        if (!$data) {
            $data = (object)[
                'title' => $defaultTitle,
                'body' => $defaultBody,
                'footer_text' => 'Please contact us for any queries, we\'re always happy to help.',
                'copyright_text' => 'Copyright ' . date('Y') . ' ' . $company_name . '. All rights reserved',
                'button_url' => $status == 'approved' ? $loginUrl : null,
                'button_name' => $status == 'approved' ? 'Login to Your Account' : null,
                'logo' => null,
                'icon' => null,
                'image' => null,
                'email_template' => 1,
                'privacy' => 1,
                'refund' => 0, 
                'cancelation' => 0,
                'contact' => 1,
            ];
            $template = 1;
        }

        // For approved status, ensure login button is present
        if ($status == 'approved' && !$data->button_url) {
            $data->button_url = $loginUrl;
            $data->button_name = $data->button_name ?: 'Login to Your Account';
        }

        $title = Helpers::text_variable_data_format( value:$data->title ?? $defaultTitle, store_name:$store_name??'');
        $body = Helpers::text_variable_data_format( value:$data->body ?? $defaultBody, store_name:$store_name??'');
        $footer_text = Helpers::text_variable_data_format( value:$data->footer_text ?? '', store_name:$store_name??'');
        $copyright_text = Helpers::text_variable_data_format( value:$data->copyright_text ?? '', store_name:$store_name??'');

        $subject = $status == 'approved' ? translate('Your_Store_Has_Been_Approved')  :
                   ($status == 'denied' ? translate('Store_Registration_Update') : translate('Welcome_to') . ' ' . $company_name);

        return $this->subject($subject)->view('email-templates.new-email-format-'.$template, ['company_name'=>$company_name,'data'=>$data,'title'=>$title,'body'=>$body,'footer_text'=>$footer_text,'copyright_text'=>$copyright_text]);
    }
}
 