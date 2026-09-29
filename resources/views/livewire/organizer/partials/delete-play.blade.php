{{-- Delete an upcoming open play: warns who gets told and who has already paid. --}}
@php($joined = $play->active_participants_count)
@php($paid = $play->paid_count)
@php($told = $play->activeParticipants()->whereNotNull('user_id')->where('user_id', '!=', auth()->id())->count())
<div wire:key="delete-{{ $play->id }}" x-data x-on:keydown.escape.window="$wire.cancelDelete()" class="fixed inset-0 z-66 flex items-end justify-center lg:items-center lg:p-6">
    <button type="button" wire:click="cancelDelete" aria-label="Keep open play" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>

    <div role="alertdialog" aria-modal="true" aria-labelledby="del-title" aria-describedby="del-desc" x-trap.noscroll="true"
         class="relative flex max-h-full w-full animate-rise flex-col overflow-hidden rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] lg:w-120 lg:rounded-[28px]">
        <div class="flex flex-col gap-4 overflow-y-auto px-6 pt-6.5 pb-4.5">
            <span class="flex size-13 items-center justify-center rounded-2xl border border-hot-line bg-hot-soft text-hot"><x-icon name="trash" :size="22" /></span>
            <div class="flex flex-col gap-1.5">
                <h2 id="del-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">Delete {{ $play->title }}?</h2>
                <p class="m-0 text-sm text-ink3">{{ $play->whenLabel() }} · {{ $play->timeRange() }} · {{ $play->venue }}</p>
            </div>
            <div class="grid grid-cols-3 gap-2">
                @foreach (['Joined' => $joined, 'Paid' => $paid, 'Fee' => $play->feeLabel()] as $label => $value)
                    <div class="rounded-2xl border border-line bg-surface p-3">
                        <div class="font-mono text-[10px] tracking-widest text-ink3 uppercase">{{ $label }}</div>
                        <div class="font-display text-[26px] font-extrabold tracking-[-0.03em]">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            <p id="del-desc" class="m-0 text-[15px] leading-normal text-ink2">
                {{ $told ? $told.' '.($told === 1 ? 'player who joined gets' : 'players who joined get')." an email that it's cancelled, and it comes off everyone's Open plays." : ($joined ? "It comes off everyone's Open plays. The players on it don't have PickleX accounts, so let them know yourself." : 'No one has joined yet, so nobody needs to be told.') }}
            </p>
            @if ($paid && $play->fee)
                <x-alert>{{ $paid }} {{ $paid === 1 ? 'player has' : 'players have' }} already paid {{ $play->fee }}. PickleX doesn't hold the money, so refund them from your {{ $play->hasPaymentQr() ? $play->payMethodLabel() : 'payment' }} account.</x-alert>
            @endif
            <p class="m-0 text-[13px] text-ink3">This can't be undone. To change the date or time instead, use Edit.</p>
        </div>
        <div class="flex shrink-0 gap-2.5 border-t border-line px-6 pt-3.5 pb-5.5">
            <button type="button" wire:click="cancelDelete" class="h-13 flex-1 cursor-pointer rounded-full border-[1.5px] border-line2 bg-transparent text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Keep open play</button>
            <button type="button" wire:click="deletePlay" wire:loading.attr="disabled" class="h-13 flex-1 cursor-pointer rounded-full bg-hot text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">Delete open play</button>
        </div>
    </div>
</div>
