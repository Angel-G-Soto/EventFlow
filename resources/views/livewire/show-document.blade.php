<div class="container mt-3">
    <h1 class="h4 mb-3">
        {{ $document->name ?? 'Request File ' . $document->id }}
    </h1>

    <iframe
        src="{{ $pdfUrl }}"
        width="100%"
        style="min-height: 80vh; border: none;"
    ></iframe>
</div>
