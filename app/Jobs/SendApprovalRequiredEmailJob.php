<?php

namespace App\Jobs;

use App\Mail\DeanOfAdministrationEmail;
use App\Mail\DscaEmail;
use App\Mail\VenueManagerEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendApprovalRequiredEmailJob implements ShouldQueue
{

    use Queueable, SerializesModels,Dispatchable, InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $approverEmail, public string $approverType, public array $eventData)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        if($this->approverType === 'dsca')
            Mail::to($this->approverEmail)->send(new DscaEmail($this->eventData));
        else if($this->approverType === 'venue')
            Mail::to($this->approverEmail)->send(new VenueManagerEmail($this->eventData));
        else if ($this->approverType === 'dean')
            Mail::to($this->approverEmail)->send(new DeanOfAdministrationEmail($this->eventData));
        else
            todo("Implement Error message or return");


    }
}
