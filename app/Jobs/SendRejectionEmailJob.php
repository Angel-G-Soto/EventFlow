<?php

namespace App\Jobs;

use App\Mail\RejectionEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRejectionEmailJob implements ShouldQueue
{
    use Queueable, SerializesModels,Dispatchable, InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $creatorEmail,
                                public array $eventData, public string $justification)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        Mail::to($this->creatorEmail)->send(new RejectionEmail($this->eventData, $this->justification));
    }
}
