<?php

namespace App\Jobs;

use App\Mail\AdvisorEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Queueable, SerializesModels,Dispatchable, InteractsWithQueue;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $email, public string $type)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if($this->type == "advisor")
        {
            Mail::to($this->email)->send(new AdvisorEmail($this->email));
        }
        else if($this->type == "venue manager"){
            echo "Done";
        }
        else if($this->type == "DSCA"){
            echo "Done";
        }
        else if($this->type == "Rejection"){
            echo "Done";
        }
        else if($this->type == "Sanction"){
            echo "Done";
        }
        else if($this->type == "Cancellation"){
            echo "Done";
        }



    }
}
