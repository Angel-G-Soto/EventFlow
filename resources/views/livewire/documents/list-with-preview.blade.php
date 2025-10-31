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


