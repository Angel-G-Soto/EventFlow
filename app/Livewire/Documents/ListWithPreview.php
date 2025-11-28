<?php

/**
 * Livewire Component: List With Preview
 *
 * EventFlow (Laravel 12 + Livewire 3 + Bootstrap 5).
 * Lists documents/items and shows an accessible preview (inline or modal).
 *
 * Responsibilities:
 * - Build a paginated list with optional search/filter.
 * - Manage preview state (selected record, preview URL) and close/reset logic.
 * - Emit UI events for modals or toasts if needed.
 *
 * @since   2025-11-01
 */

// app/Livewire/Documents/ListWithPreview.php
namespace App\Livewire\Documents;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;

/**
 * Class ListWithPreview
 *
 * Reusable Livewire component that displays a list and an item preview panel/modal.
 */
class ListWithPreview extends Component
{
/**
 * @var array
 */
    public array $docs = []; // each: ['title' => '', 'url' => '', 'description' => '']

/**
 * Render the list with preview view, including pagination and filters.
 * @return \Illuminate\Contracts\View\View
 */

    public function render()
    {
        return view('livewire.documents.list-with-preview');
    }

    /**
     * Check if a document actually exists on disk before rendering/previewing it.
     *
     * @param string|null $path Relative storage path (e.g. "docs/foo.pdf") or absolute path.
     * @param string|null $disk Storage disk to check (defaults to app default if not provided).
     */
    public function documentExists(?string $path, ?string $disk = null): bool
    {
        if (! $path) {
            return false;
        }

        // Absolute path or already-resolved public path
        if (file_exists($path) || file_exists(public_path($path))) {
            return true;
        }

        $disk ??= config('filesystems.default', 'public');
        // dd($disk, $path, Storage::disk($disk)->exists($path));

        return Storage::disk($disk)->exists($path);
    }
}
