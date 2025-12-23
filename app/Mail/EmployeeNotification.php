<?php 
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmployeeNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $rec_name;
    public $title;
    public $msg;
    public $store_name;
    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($rec_name , $title, $msg, $url, $store_name)
    {
        $this->rec_name = $rec_name;
        $this->title = $title;
        $this->msg = $msg;
        $this->url = $url;
        $this->store_name = $store_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email-templates.employee_notification')
                    ->with([
                        'name' => $this->rec_name,
                        'msg' => $this->msg,
                        'url' => $this->url,
                        'store_name' => $this->store_name,
                    ])
                    ->subject($this->title);
    }
}
