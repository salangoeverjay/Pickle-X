{{-- Labelled text input. Extra attributes (wire:model, type, placeholder...) go on the <input>. --}}
@props(['label', 'id'])

<div class="flex flex-col gap-2">
    <label for="{{ $id }}" class="text-[13px] font-bold text-ink2">{{ $label }}</label>
    <input id="{{ $id }}" {{ $attributes->merge(['type' => 'text', 'class' => 'input']) }}>
</div>
