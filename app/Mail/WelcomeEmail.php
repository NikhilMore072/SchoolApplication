<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $userEmail;
    protected $password;

    public function __construct($userEmail, $password)
    {
        $this->userEmail = $userEmail;
        $this->password = $password;
    }

    public function build()
    {
        return $this->view('emails.welcome')
                    ->with([
                        'userEmail' => $this->userEmail,
                        'password' => $this->password,
                    ]);
    }
}
