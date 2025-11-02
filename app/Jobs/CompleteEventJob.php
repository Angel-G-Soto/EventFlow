<?php

namespace App\Jobs;

use App\Services\EventCompletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/**
 * When executed, attempt to mark the given Event as "completed"
 * if it is currently "approved" and its end_time has passed.
 */
class CompleteEventJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $eventId) {}

    public function uniqueId(): string
    {
        return 'complete-event:' . $this->eventId;
    }

    public function middleware(): array
    {
        // Prevent concurrent processing for the same event id (e.g., retries)
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function handle(EventCompletionService $service): void
    {
        $service->completeIfPast($this->eventId);
    }
}
