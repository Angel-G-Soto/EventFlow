{{--    View: List With Preview (Livewire) --}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5) --}}
{{--    Date: 2025-11-01 --}}

{{--    Description: --}}
{{--    - Renders a searchable, paginated list and an accessible preview (inline or modal). --}}
{{--    - Useful for PDFs/images with a fallback download link. --}}

{{--    Variables (typical): --}}
{{--    - \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|array $items --}}
{{--    - string|null $previewUrl --}}
{{--    - string|null $previewType  e.g., 'pdf' or 'image' --}}

{{--    Accessibility notes: --}}
{{--    - If using a modal, ensure focus trap, aria-modal="true", and labeled header. --}}
{{--    - For <iframe>/<object> previews, include title attributes and fallback links. --}}
{{--    - Place pagination inside <nav aria-label="Pagination">. --}}


{{-- resources/views/livewire/documents/list-with-preview.blade.php --}}
<div>
    <ul class="list-group">
        @forelse ($docs as $doc)
            <li class="list-group-item">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2">
                    <div class="flex-grow-1 text-break">
                        <h6 class="mb-1">{{ $doc['name'] }}</h6>
                        @php $missing = ! $this->documentExists($doc['file_path'] ?? null, 'documents'); @endphp
                        @if ($missing)
                            <p class="mb-0 text-danger small">
                                This document is unavailable. May have been removed due to retention policies or security concerns.
                            </p>
                        @endif
                    </div>
                    <div class="d-grid d-sm-inline-flex ms-sm-auto" role="group"
                        aria-label="Actions for {{ $doc['name'] }}">
                        {{--                    <a class="btn btn-outline-primary" href="{{ $doc['file_path'] }}" target="_blank" rel="noopener">Open</a> --}}
                        <a class="btn btn-primary {{ $missing ? 'disabled' : '' }}"
                            href="{{ $missing ? '#' : route('documents.show', $doc['id']) }}" target="_blank"
                            rel="noopener" aria-disabled="{{ $missing ? 'true' : 'false' }}"
                            tabindex="{{ $missing ? '-1' : '0' }}">
                            <i class="bi bi-filetype-pdf"></i>
                            Open PDF
                        </a>

                        {{--                    <a class="btn btn-outline-secondary" href="{{ $doc['file_path'] }}" download>Download</a> --}}
                    </div>
                </div>
            </li>
            @empty
            <li class="list-group-item">
                <p class="mb-0">No documents were found for this request.</p>
        @endforelse
    </ul>

</div>
