<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\Candidate;
use App\Models\Company;

class CandidateContacted extends Mailable
{
    use Queueable, SerializesModels;

    public $candidate;
    public $company;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Company $company, Candidate $candidate)
    {
        $this->candidate = $candidate;
        $this->company = $company;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->text('emails.candidate-contacted');
        // I'd use this if companies had an email address in the system,
        // but they do not right now.
        //          ->replyTo($company->email, $company->name);

    }
}
