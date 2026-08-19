<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletPointNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $points;
    protected $reward;
    protected $username;

    public function __construct($points,$reward,$username)
    {
        $this->points = $points;
        $this->reward = $reward;
        $this->username = $username;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $points = $this->points;
        
        return $this->subject('Reward Points')->view('email-templates.wallet-point-notification', ['points' => $points,'reward'=>$this->reward,'username'=>$this->username]);
    }
}
