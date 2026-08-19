<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\CentralLogics\Helpers;

class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $order_id;
    protected $couponText;
    protected $order;
    public function __construct($order,$couponText)
    {
        
        $this->order_id = $order->id;
        $this->couponText=$couponText;
        $this->order=$order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order_id = $this->order_id;
        $emailServices = Helpers::get_business_settings('mail_config');
       
        $ccEmail=isset($emailServices['mail_cc'])?$emailServices['mail_cc']:null;
        $ccEmailSec=isset($emailServices['mail_cc_sec'])?$emailServices['mail_cc_sec']:null;
      // dd(['order_id' => $order_id,'order'=>$this->order,'coupnText'=>$this->couponText]);
      
        return $this->view('email-templates.customer-order-placed', ['order_id' => $order_id,'order'=>$this->order,'coupnText'=>$this->couponText])->cc([$ccEmail,$ccEmailSec]);
    }
}
