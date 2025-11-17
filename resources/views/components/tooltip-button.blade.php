@props([
    'text' => '',
    'type' => 'button',
    'placement' => 'top',
])

<button
    {{ $attributes->merge(['type' => $type]) }}
    data-bs-toggle="tooltip"
    data-bs-placement="{{ $placement }}"
    data-bs-title="{{ $text }}"
    title="{{ $text }}"
>
    {{ $slot }}
</button>
