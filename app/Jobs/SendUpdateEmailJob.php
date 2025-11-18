<?php

namespace App\Jobs;

use App\Mail\SanctionEmail;
use App\Mail\UpdateEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendUpdateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, \Illuminate\Bus\Queueable, SerializesModels;
    public string $creatorEmail;
    public array $eventDetails;
    public string $approverName;
    public string $role;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $creatorEmail,
        array $eventDetails,
        string $approverName,
        string $role)
    {
        //
        $this->creatorEmail = $creatorEmail;
        $this->eventDetails = $eventDetails;
        $this->approverName = $approverName;
        $this->role= $role;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        Mail::to($this->creatorEmail)
            ->send(new UpdateEmail(
                $this->eventDetails,
                $this->approverName,
                $this->role
                ));
    }
}
