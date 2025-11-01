
{{--    View: List With Preview (Livewire)--}}
{{--    Project: EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5)--}}
{{--    Date: 2025-11-01--}}

{{--    Description:--}}
{{--    - Renders a searchable, paginated list and an accessible preview (inline or modal).--}}
{{--    - Useful for PDFs/images with a fallback download link.--}}

{{--    Variables (typical):--}}
{{--    - \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|array $items--}}
{{--    - string|null $previewUrl--}}
{{--    - string|null $previewType  e.g., 'pdf' or 'image'--}}

{{--    Accessibility notes:--}}
{{--    - If using a modal, ensure focus trap, aria-modal="true", and labeled header.--}}
{{--    - For <iframe>/<object> previews, include title attributes and fallback links.--}}
{{--    - Place pagination inside <nav aria-label="Pagination">.--}}


{{-- resources/views/livewire/documents/list-with-preview.blade.php --}}
<div>
<ul class="list-group">
    @foreach ($docs as $doc)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1">{{ $doc['title'] }}</h6>
                @if(!empty($doc['description'])) <small class="text-muted">{{ $doc['description'] }}</small> @endif
            </div>
            <div class="btn-group" role="group" aria-label="Actions for {{ $doc['title'] }}">
                <a class="btn btn-outline-primary" href="{{ $doc['url'] }}" target="_blank" rel="noopener">Open</a>
                <a class="btn btn-outline-secondary" href="{{ $doc['url'] }}" download>Download</a>
            </div>
        </li>
    @endforeach
</ul>

</div>


