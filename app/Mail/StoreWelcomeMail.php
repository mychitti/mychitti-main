<?php

namespace App\Mail;

use App\Models\BusinessSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StoreWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $storeName;

    public function __construct(string $storeName)
    {
        $this->storeName = $storeName;
    }

    public function build()
    {
        $company_name = BusinessSetting::where('key', 'business_name')->value('value') ?? 'MCVendorHub';

        return $this
            ->subject('Welcome to ' . $company_name . ' — Your Store is Ready!')
            ->view('emails.store-welcome', [
                'storeName'    => $this->storeName,
                'company_name' => $company_name,
                'login_url'    => 'https://vendor.mcvendorhub.com/login',
            ]);
    }
}
