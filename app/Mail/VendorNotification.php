<?php 
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VendorNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $rec_name;
    public $title;
    public $msg;
    public $item_name;
    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($rec_name , $title, $msg, $url)
    {
        $this->rec_name = $rec_name;
        $this->title = $title;
        $this->msg = $msg;
        $this->url = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email-templates.vendor_notification')
                    ->with([
                        'name' => $this->rec_name,
                        'msg' => $this->msg,
                        'url' => $this->url,
                    ])
                    ->subject($this->title);
    }
}
