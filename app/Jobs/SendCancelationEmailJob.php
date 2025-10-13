<?php

namespace App\Jobs;

use App\Mail\CancelationEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCancelationEmailJob implements ShouldQueue
{
    use Queueable, SerializesModels,Dispatchable, InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public function __construct(public array
                                $recipientEmails, public array $eventData, public string $justification)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        foreach ($this->recipientEmails as $recipientEmail)
            Mail::to($recipientEmail)->send(new CancelationEmail($this->eventData, $this->justification));

    }
}
