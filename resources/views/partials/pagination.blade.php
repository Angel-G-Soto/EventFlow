@if ($paginator->hasPages())
<nav class="d-flex justify-content-end" aria-label="Pagination">
  <ul class="pagination pagination-sm mb-0">
    {{-- First --}}
    <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
      <button type="button" class="page-link" wire:click="goToPage(1)" @disabled($paginator->onFirstPage())
        aria-label="First page">
        &laquo;
      </button>
    </li>

    {{-- Previous --}}
    <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
      <button type="button" class="page-link" wire:click="goToPage({{ $paginator->currentPage() - 1 }})"
        @disabled($paginator->onFirstPage())
        aria-label="Previous page">
        &lsaquo;
      </button>
    </li>

    {{-- Pagination Elements (numbers + separators) --}}
    @foreach ($elements as $element)
    {{-- "Three Dots" Separator --}}
    @if (is_string($element))
    <li class="page-item disabled" aria-hidden="true"><span class="page-link">{{ $element }}</span></li>
    @endif

    {{-- Array Of Links --}}
    @if (is_array($element))
    @foreach ($element as $page => $url)
    @if ($page == $paginator->currentPage())
    <li class="page-item active" aria-current="page">
      <span class="page-link">{{ $page }}</span>
    </li>
    @else
    <li class="page-item">
      <button type="button" class="page-link" wire:click="goToPage({{ $page }})">
        {{ $page }}
      </button>
    </li>
    @endif
    @endforeach
    @endif
    @endforeach

    {{-- Next --}}
    <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
      <button type="button" class="page-link" wire:click="goToPage({{ $paginator->currentPage() + 1 }})" @disabled(!
        $paginator->hasMorePages())
        aria-label="Next page">
        &rsaquo;
      </button>
    </li>

    {{-- Last --}}
    <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
      <button type="button" class="page-link" wire:click="goToPage({{ $paginator->lastPage() }})" @disabled(!
        $paginator->hasMorePages())
        aria-label="Last page">
        &raquo;
      </button>
    </li>
  </ul>
</nav>
@endif