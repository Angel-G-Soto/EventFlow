{{-- resources/views/livewire/venues/requirements-editor.blade.php --}}
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Requirements for: {{ $venue->name }}</h1>

        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" type="button" wire:click="addRow">
                <i class="bi bi-plus-lg"></i> Add requirement
            </button>
            <button class="btn btn-primary" type="button" wire:click="save">
                <i class="bi bi-save"></i> Save changes
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Global validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the errors below:</strong>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead class="table-light">
            <tr>
                <th style="width: 20%">Name <span class="text-danger">*</span></th>
                <th style="width: 45%">Description</th>
                <th style="width: 25%">Document link (URL)</th>
                <th style="width: 10%" class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rows as $i => $row)
                <tr wire:key="req-{{ $row['uuid'] }}">
                    <td>
                        <input
                            type="text"
                            class="form-control @error("rows.$i.name") is-invalid @enderror"
                            placeholder="e.g., Safety Plan"
                            wire:model.lazy="rows.{{ $i }}.name"
                        >
                        @error("rows.$i.name")
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>

                    <td>
                        <textarea
                            rows="2"
                            class="form-control @error("rows.$i.description") is-invalid @enderror"
                            placeholder="Brief description…"
                            wire:model.lazy="rows.{{ $i }}.description"
                        ></textarea>
                        @error("rows.$i.description")
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>

                    <td>
                        <div class="input-group">
                            <input
                                type="url"
                                class="form-control @error("rows.$i.doc_url") is-invalid @enderror"
                                placeholder="https://…"
                                wire:model.lazy="rows.{{ $i }}.doc_url"
                            >
                            @if (!empty($row['doc_url']))
                                <a class="btn btn-outline-secondary" href="{{ $row['doc_url'] }}" target="_blank" rel="noopener">
                                    Open
                                </a>
                            @endif
                            @error("rows.$i.doc_url")
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </td>

                    <td class="text-end">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            onclick="if(!confirm('Remove this requirement?')) return;"
                            wire:click="removeRow('{{ $row['uuid'] }}')"
                            title="Remove"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-3 text-muted small">
        <span class="text-danger">*</span> Required field. Empty rows are skipped automatically.
    </div>
</div>
