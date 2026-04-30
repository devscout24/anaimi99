<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TempPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $barber;
    public $tempPassword;

    public function __construct($barber, $tempPassword)
    {
        $this->barber      = $barber;
        $this->tempPassword = $tempPassword;
    }

    public function build()
    {
        return $this->subject('Your Temporary Password')
                    ->view('backend.layouts.email_otp_mail.temp_password_mail', [
                        'barber'       => $this->barber,
                        'tempPassword' => $this->tempPassword,
                    ]);
    }
}
