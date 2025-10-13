<?php

namespace App\Jobs;

use App\Mail\AdvisorEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAdvisorEmailJob implements ShouldQueue
{
    use Queueable, SerializesModels,Dispatchable, InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $email, public array $eventData)
    {
        //

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        Mail::to($this->email)->send(new AdvisorEmail($this->eventData));
    }
}
