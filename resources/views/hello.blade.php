<x-layouts.app title="Bootstrap Test">
    <h1 class="mb-3">Bootstrap 5 + Vite works?</h1>

    <button class="btn btn-primary me-2">Primary button</button>
    <div class="alert alert-primary mt-3">
        If this looks violet, SCSS overrides are working.
    </div>

    <button class="btn btn-outline-secondary mt-3"
            data-bs-toggle="tooltip"
            title="Tooltips require Bootstrap JS init">
        Hover me for tooltip
    </button>

    <button class="btn btn-success mt-3" data-bs-toggle="modal" data-bs-target="#demoModal">
        Open modal
    </button>
    <div class="modal fade" id="demoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modal says hi 👋</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">If this opens, Bootstrap JS is loading correctly.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div></div>
    </div>

    <input class="form-control mt-4" placeholder="Form control should look Bootstrap-y" />

    @push('scripts')
        <script>console.log('Page-specific JS');</script>
    @endpush

    {{-- Or you could use a named slot for scripts: --}}
    {{-- <x-slot:scripts><script>console.log('Page JS');</script></x-slot:scripts> --}}
</x-layouts.app>
