{{-- Pill tab group. Children: <x-tab :active="..." wire:click="...">Label</x-tab>. --}}
@props(['label'])

<div role="tablist" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'inline-flex rounded-full border border-line bg-surface p-1']) }}>
    {{ $slot }}
</div>
