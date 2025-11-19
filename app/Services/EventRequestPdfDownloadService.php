<?php

namespace App\Services;

use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class EventRequestPdfDownloadService
{
    public function __construct(
        protected ViewFactory $view,
        protected AuditService $auditService,
    ) {
    }

    public function download(Event $event, ?string $filename = null, string $view = 'livewire.request.activity_request'): StreamedResponse
    {
        // Gate::authorize('downloadEventPdf', $event);

        $event->loadMissing([
            'categories:id,name',
            'venue:id,name,code,description',
            'venue.requirements:id,venue_id,name,description,hyperlink',
            'requester:id,first_name,last_name,email',
            'history.approver:id,first_name,last_name,email,department_id',
            'history.approver.department:id,name',
        ]);

        $historyQuery = $event->history()
            ->with(['approver:id,first_name,last_name,email,department_id', 'approver.department:id,name'])
            ->where('action', '!=', 'pending');

        $venueHistory = (clone $historyQuery)
            ->where('status_when_signed', 'like', '%venue manager%')
            ->orderByDesc('created_at')
            ->first();

        $dscaHistory = (clone $historyQuery)
            ->where('status_when_signed', 'like', '%dsca%')
            ->orderByDesc('created_at')
            ->first();

        $outputName = $filename ?? sprintf('event-request-%s.pdf', $event->id);

        $html = $this->view->make($view, [
            'event' => $event,
            'venueHistory' => $venueHistory,
            'dscaHistory' => $dscaHistory,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');

        $response = response()->streamDownload(
            static function () use ($pdf) {
                echo $pdf->output();
            },
            $outputName,
            ['Content-Type' => 'application/pdf']
        );

        // AUDIT: event PDF downloaded (best-effort)
        try {
            $user = Auth::user();
            if ($user && $user->id) {
                $meta = [
                    'event_id' => (int) $event->id,
                    'filename' => $outputName,
                    'source'   => 'event_pdf_download',
                ];
                $ctx = ['meta' => $meta];
                if (function_exists('request') && request()) {
                    $ctx = $this->auditService->buildContextFromRequest(request(), $meta);
                }

                $this->auditService->logAction(
                    (int) $user->id,
                    'event',
                    'EVENT_PDF_DOWNLOADED',
                    (string) $event->id,
                    $ctx
                );
            }
        } catch (\Throwable) {
            // best-effort
        }

        return $response;
    }

}
