<?php

namespace App\Services;

use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventRequestPdfDownloadService
{
    public function __construct(
        protected ViewFactory $view
    ) {
    }

    public function download(Event $event, ?string $filename = null, string $view = 'pdf.request.create'): StreamedResponse
    {
        Gate::authorize('downloadEventPdf', $event);

        $event->loadMissing([
            'categories:id,name',
            'venue:id,name,code,description',
            'venue.requirements:id,venue_id,name,description,hyperlink',
            'requester:id,first_name,last_name,email',
        ]);

        $requirements = optional($event->venue)
            ? $event->venue->requirements->map(function ($req) {
                return [
                    'name' => $req->name,
                    'description' => $req->description,
                    'required' => (bool) ($req->required ?? false),
                ];
            })->toArray()
            : [];

        $requester = $event->requester;
        $payload = [
            'creator_phone_number' => $event->creator_phone_number,
            'creator_institutional_number' => $event->creator_institutional_number,
            'organization_name' => $event->organization_name,
            'organization_advisor_name' => $event->organization_advisor_name,
            'organization_advisor_email' => $event->organization_advisor_email,
            'guest_size' => $event->guest_size,
            'title' => $event->title,
            'description' => $event->description,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'handles_food' => $event->handles_food,
            'use_institutional_funds' => $event->use_institutional_funds,
            'external_guest' => $event->external_guest,
            'venue_name' => optional($event->venue)->name,
            'venue_code' => optional($event->venue)->code,
            'selected_categories' => $event->categories->pluck('name')->toArray(),
            'required_documents' => $requirements,
            'requester_name' => trim(sprintf(
                '%s %s',
                $requester->first_name ?? '',
                $requester->last_name ?? ''
            )) ?: null,
            'requester_email' => $requester?->email,
            'submitted_at' => $event->created_at,
            'status' => $event->status,
            'status_label' => $event->getSimpleStatus(),
        ];

        $outputName = $filename ?? sprintf('event-request-%s.pdf', $event->id);

        $html = $this->view->make($view, [
            'form' => $payload,
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('letter', 'portrait');

        return response()->streamDownload(
            static function () use ($pdf) {
                echo $pdf->output();
            },
            $outputName,
            ['Content-Type' => 'application/pdf']
        );
    }
}
