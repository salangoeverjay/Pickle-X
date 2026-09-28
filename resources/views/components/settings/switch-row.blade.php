{{-- One labelled preference switch; $row = [label, hint, 'on' => bool]. --}}
@props(['key', 'row'])

<div class="flex items-center gap-3.5 border-t border-line py-2.5">
    <div class="flex min-w-0 flex-1 flex-col">
        <span class="text-[15px] font-semibold">{{ $row[0] }}</span>
        <span class="text-[13px] text-ink3">{{ $row[1] }}</span>
    </div>
    <x-switch :on="$row['on']" :label="$row[0]" wire:click="toggle('{{ $key }}')" />
</div>
