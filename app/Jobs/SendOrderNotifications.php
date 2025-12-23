<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $title, $msg, $acceptanceId, $to, $url, $userType, $orderId, $phone;

    public function __construct($title, $msg, $acceptanceId, $to, $url, $userType, $orderId, $phone)
    {
        $this->title = $title;
        $this->msg = $msg;
        $this->acceptanceId = $acceptanceId;
        $this->to = $to;
        $this->url = $url;
        $this->userType = $userType;
        $this->orderId = $orderId;
        $this->phone = $phone;
    }

    public function handle()
    {
        _inAppNotification($this->title, $this->msg, $this->acceptanceId, $this->to, $this->url, $this->userType);
        _sendMailToVendor($this->title, $this->msg, $this->to, $this->url);

        $smsTemplate = "Hello! Your order NO " . $this->orderId . " is received. Please review the details on My Chitti Vendor Dashboard and confirm accuracy.";
        _sendSMS($this->phone, $smsTemplate);
    }
}
