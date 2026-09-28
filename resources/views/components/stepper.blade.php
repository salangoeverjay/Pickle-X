{{-- -/+ number control bound to a Livewire property without a round trip. --}}
@props(['label', 'model', 'value', 'min' => 1, 'max' => 20, 'step' => 1])

<div class="flex flex-col gap-2">
    <span id="stepper-{{ $model }}" class="text-[13px] font-bold text-ink2">{{ $label }}</span>
    <div x-data role="group" aria-labelledby="stepper-{{ $model }}" {{ $attributes->merge(['class' => 'flex h-12.5 items-center gap-1.5 rounded-[14px] border-[1.5px] border-line bg-surface px-1.25']) }}>
        <button type="button" x-on:click="$wire.{{ $model }} = Math.max({{ $min }}, $wire.{{ $model }} - {{ $step }})" aria-label="Lower {{ strtolower($label) }}" class="flex size-10 cursor-pointer items-center justify-center rounded-[10px] bg-surface2 text-ink">
            <x-icon name="minus" :size="18" :stroke="2.2" />
        </button>
        <span x-text="$wire.{{ $model }}" class="flex-1 text-center font-mono text-[17px] font-semibold">{{ $value }}</span>
        <button type="button" x-on:click="$wire.{{ $model }} = Math.min({{ $max }}, $wire.{{ $model }} + {{ $step }})" aria-label="Raise {{ strtolower($label) }}" class="flex size-10 cursor-pointer items-center justify-center rounded-[10px] bg-surface2 text-ink">
            <x-icon name="plus" :size="18" :stroke="2.2" />
        </button>
    </div>
</div>
