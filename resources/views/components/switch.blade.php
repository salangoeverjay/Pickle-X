{{-- On/off switch. Pass wire:click (or onclick) plus :on and a label. --}}
@props(['on' => false, 'label'])

<button type="button" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'flex h-11 w-15 shrink-0 cursor-pointer items-center justify-center']) }}>
    <span @class(['relative h-7.5 w-12.5 rounded-full transition-colors', 'bg-lime' => $on, 'bg-line2' => ! $on])>
        <span @class(['absolute top-1 size-5.5 rounded-full shadow-[0_1px_3px_rgba(0,0,0,.25)] transition-[left]', 'left-6 bg-on-lime' => $on, 'left-1 bg-white' => ! $on])></span>
    </span>
</button>
