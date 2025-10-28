
<div class="container py-4">
    <h1 class="h4 mb-3">Filter Sandbox (Arrays Only)</h1>

    <div class="mb-3">
        <livewire:filters.multi-filter
            :organizations="$orgOptions"
            :categories="$catOptions"
            :venues="$venueOptions"
            :auto-apply="true"
        />
    </div>

    <div class="small text-muted mb-2">
        URL CSV — orgs={{ $orgsCsv ?: '∅' }}, cats={{ $catsCsv ?: '∅' }}, venues={{ $venuesCsv ?: '∅' }}
    </div>

    @if(count($events))
        <ul class="list-group">
            @foreach($events as $e)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>#{{ $e['id'] }} {{ $e['title'] }}</strong>
                        <div class="text-muted small">
                            Org: {{ $orgOptions[$e['organization_id']] ?? $e['organization_id'] }} ·
                            Venue: {{ $venueOptions[$e['venue_id']] ?? $e['venue_id'] }} ·
                            Types:
                            @foreach($e['categories'] as $c)
                                <span class="badge bg-light text-dark border">{{ $catOptions[$c] ?? $c }}</span>
                            @endforeach
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <div class="alert alert-light border">No events match your filters.</div>
    @endif
</div>
