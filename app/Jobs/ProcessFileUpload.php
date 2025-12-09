<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Event;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\EventService;
use App\Services\NotificationService;
use App\Services\UserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProcessFileUpload implements ShouldQueue
{
    use Queueable;

    protected Collection $documents;
    protected ?int $eventId;
    protected bool $notifyApproverOnClean;

    /**
     * Create a new job instance.
     */
    public function __construct(Collection $documents, ?int $eventId = null, bool $notifyApproverOnClean = false)
    {
        $this->documents = $documents;
        $this->eventId = $eventId;
        $this->notifyApproverOnClean = $notifyApproverOnClean;
    }

    /**
     * Handles scanning and processing of uploaded documents.
     *
     * This method iterates through each document in the `$documents` collection,
     * validates that it is an instance of the `Document` class, and scans it for viruses
     * using `clamdscan`. Based on the scan result, the document is either moved from
     * the temporary uploads storage to the permanent documents storage or deleted if infected.
     *
     */
    public function handle(): void
    {
        $virusDetected = false;

        foreach ($this->documents as $document) {
            // Validate that the collection only has Document elements.
            if (!$document instanceof Document) throw new InvalidArgumentException();

            // Get path to where the document is located.

            $tempRelative = basename($document->getFilePath());
            $path = Storage::disk('uploads_temp')->path($tempRelative);

            $clamdscanPath = rtrim((string) config('services.clamav.scan_path'));

            if (!$clamdscanPath || !file_exists($clamdscanPath)) {
                throw new \RuntimeException("clamdscan executable not found at: {$clamdscanPath}");
            }


            // Create scanning process
            $scan = new Process([config('services.clamav.scan_path'), '--fdpass', $path]);

            // Run process
            $scan->run();

            // Examine output and take decision (move to documents folder or delete)
            if (Str::contains($scan->getOutput(), 'OK'))
            {
                // Move file
                $contents = Storage::disk('uploads_temp')->get($tempRelative);
                Storage::disk('documents')->put($tempRelative, $contents);
                Storage::disk('uploads_temp')->delete($tempRelative);
                $document->file_path = $tempRelative;
                $document->save();
            }
            elseif(Str::contains($scan->getOutput(), 'FOUND'))
            {
                $virusDetected = true;
                // Delete file
                Storage::disk('uploads_temp')->delete($tempRelative);
                $actor = $this->handleInfectedDocument($document, $scan->getOutput());
                app(DocumentService::class)->deleteDocument($document, $actor?->id);
            }
            else throw new ProcessFailedException($scan);
        }

        if ($this->shouldDispatchApprovalNotification($virusDetected)) {
            $this->dispatchApprovalRequiredNotification();
        }
    }

    /**
     * Handle event cancellation, auditing, and notifications when the virus scan flags a document.
     */
    protected function handleInfectedDocument(Document $document, string $scanOutput): ?User
    {
        $document->loadMissing(['event.requester']);
        $event = $document->event;

        if (!$event instanceof Event) {
            Log::warning('ProcessFileUpload: virus detected but document is not linked to an event.', [
                'document_id' => $document->id,
            ]);
            return null;
        }

        $actor = $this->resolveCancellationActor($event);
        if (!$actor instanceof User) {
            Log::warning('ProcessFileUpload: unable to resolve actor for virus cancellation.', [
                'event_id' => $event->id,
                'document_id' => $document->id,
            ]);
            return null;
        }

        $justification = sprintf(
            'Automatically cancelled because the virus scanner flagged "%s" as infected.',
            $document->getNameOfFile()
        );

        try {
            /** @var EventService $eventService */
            $eventService = app(EventService::class);
            $eventService->cancelEventDueToVirus($event, $actor, $document, $scanOutput, $justification);
        } catch (\Throwable $e) {
            Log::error('ProcessFileUpload: failed to cancel event after virus detection.', [
                'event_id' => $event->id,
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        return $actor;
    }

    /**
     * Resolve the actor that will be associated with the automatic cancellation.
     */
    protected function resolveCancellationActor(Event $event): ?User
    {
        $systemUserId = (int) config('eventflow.system_user_id', 0);
        if ($systemUserId > 0) {
            $systemUser = app(UserService::class)->findUserById($systemUserId);
            if ($systemUser) {
                return $systemUser;
            }
        }

        return $event->requester;
    }

    protected function shouldDispatchApprovalNotification(bool $virusDetected): bool
    {
        return $this->notifyApproverOnClean && !$virusDetected && $this->eventId !== null;
    }

    protected function dispatchApprovalRequiredNotification(): void
    {
        if ($this->eventId === null) {
            return;
        }

        $event = Event::find($this->eventId);

        if (!$event instanceof Event) {
            Log::warning('ProcessFileUpload: unable to load event for approval notification.', [
                'event_id' => $this->eventId,
            ]);
            return;
        }

        if (empty($event->organization_advisor_email)) {
            return;
        }

        try {
            $eventDetails = app(EventService::class)->getEventDetails($event);
            app(NotificationService::class)->dispatchApprovalRequiredNotification(
                approverEmail: $event->organization_advisor_email,
                eventDetails: $eventDetails,
            );
        } catch (\Throwable $e) {
            Log::error('ProcessFileUpload: failed to dispatch approval notification.', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
