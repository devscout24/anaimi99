<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientMail extends Mailable
{
use Queueable, SerializesModels;

public $data;
public $attachment;

public function __construct($data, $attachment = null)
{
$this->data = $data;
$this->attachment = $attachment;
}

public function build()
{
$email = $this->subject($this->data['subject'])
->view('backend.layouts.leads.clientmail')
->with('data', $this->data);

if($this->attachment){
$email->attach($this->attachment->getRealPath(), [
'as' => $this->attachment->getClientOriginalName(),
'mime' => $this->attachment->getMimeType(),
]);
}

return $email;
}
}
