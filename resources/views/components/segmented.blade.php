{{-- Radio-style segmented control bound to a Livewire property. options: [value => label]. --}}
@props(['label', 'model', 'options'])

<div class="flex flex-col gap-2">
    <span class="text-[13px] font-bold text-ink2">{{ $label }}</span>
    <div role="radiogroup" aria-label="{{ $label }}" {{ $attributes->merge(['class' => 'flex rounded-2xl border-[1.5px] border-line bg-surface p-1']) }}>
        @foreach ($options as $value => $text)
            <label class="flex min-h-10.5 flex-1 cursor-pointer items-center justify-center rounded-xl px-2 text-center text-sm font-bold text-ink2 transition-colors has-checked:bg-ink has-checked:text-bg has-focus-visible:outline-2 has-focus-visible:outline-focus">
                <input type="radio" value="{{ $value }}" wire:model="{{ $model }}" class="sr-only">{{ $text }}
            </label>
        @endforeach
    </div>
</div>
