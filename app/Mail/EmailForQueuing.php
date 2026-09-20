<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailForQueuing extends Mailable
{
    use Queueable, SerializesModels;

    protected $details;

    public $fromEmail;

    public function __construct($details)
    {
        $this->details = $details;
        $this->fromEmail = 'no-reply@portfolioinfo.com';
    }

    public function build()
    {
        switch ($this->details['type']) {
            case 'contact_us':
                return $this->subject($this->details['subject'])
                    ->from($this->fromEmail)
                    ->withSwiftMessage(function ($message) {
                        $message->getHeaders()->addTextHeader('INVPORTID', $this->details['unqID']);
                    })
                    ->with(['data' => $this->details])
                    ->view('emails.contact_us');
                break;
            default:
                break;
        }
    }
}
