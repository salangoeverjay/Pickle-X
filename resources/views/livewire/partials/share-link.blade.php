{{-- Share link for an upcoming open play: the link and a message to paste. Its organizer ($manage) also sets who can see it, guests and reset. --}}
@php($sp = $sharePlay)
@php($url = $sp->linkUrl())
@php($short = preg_replace('#^https?://#', '', $url))
@php($msg = $sp->shareMessage())
@php($invite = $sp->visibility === 'invite')
<div wire:key="share-{{ $sp->id }}" x-data="{
        copy(text, message) {
            try { navigator.clipboard.writeText(text).catch(() => {}) } catch (e) { /* clipboard can be blocked */ }
            $dispatch('toast', { message })
        },
        share() {
            if (navigator.share) { navigator.share({ title: @js($sp->title), text: @js($msg), url: @js($url) }).catch(() => {}); return }
            this.copy(@js($msg), 'Invite message copied, paste it in Messenger, Viber or a group chat')
        },
     }" x-on:keydown.escape.window="$wire.closeShare()" class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
    <button type="button" wire:click="closeShare" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
    <div role="dialog" aria-modal="true" aria-labelledby="shl-title" x-trap.noscroll="true" class="relative flex max-h-[92%] w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:max-h-full md:w-170 md:max-w-full md:rounded-[28px]">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
            <div class="flex min-w-0 flex-col gap-0.5">
                <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">{{ $invite ? 'Share link, invite only' : 'Share link' }}</span>
                <h2 id="shl-title" class="m-0 font-display text-[26px] leading-[1.1] font-extrabold tracking-[-0.03em]">{{ $sp->title }}</h2>
                <span class="truncate text-[13px] text-ink3">{{ $sp->whenLabel() }}, {{ $sp->timeRange() }}, {{ $sp->venue }}</span>
            </div>
            <button type="button" wire:click="closeShare" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97"><x-icon name="x" :size="18" /></button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-5 overflow-y-auto px-6 py-5">
            <section aria-labelledby="shl-link" class="flex flex-col gap-3">
                <h3 id="shl-link" class="m-0 text-[15px] font-extrabold">Link</h3>
                <div class="flex items-center gap-2.5 rounded-2xl border-[1.5px] border-line bg-surface py-1.5 pr-1.5 pl-3.5">
                    <x-icon name="link" :size="18" class="shrink-0 text-ink3" />
                    <span class="min-w-0 flex-1 truncate font-mono text-[15px] font-semibold">{{ $short }}</span>
                    <button type="button" x-on:click="copy(@js($url), 'Link copied')" class="h-10.5 shrink-0 cursor-pointer rounded-full bg-ink px-4 text-sm font-bold text-bg transition hover:brightness-96 active:scale-97">Copy link</button>
                </div>
                <div class="flex flex-col gap-2.5 rounded-[18px] border border-line bg-surface2 p-3.5">
                    <span class="text-xs font-bold text-ink3">Message to send with it</span>
                    <p class="m-0 text-sm leading-normal">{{ $msg }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" x-on:click="copy(@js($msg), 'Invite message copied')" class="inline-flex h-10.5 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-line2 bg-surface px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon name="copy" :size="16" />Copy message</button>
                        <button type="button" x-on:click="share()" class="inline-flex h-10.5 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-line2 bg-surface px-4 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon name="share" :size="16" />Share…</button>
                    </div>
                </div>
            </section>

            @if ($manage)
                <section aria-labelledby="shl-who" class="flex flex-col gap-2.5">
                    <h3 id="shl-who" class="m-0 text-[15px] font-extrabold">Who can see it</h3>
                    <div role="radiogroup" aria-labelledby="shl-who" class="grid grid-cols-2 gap-1 rounded-2xl border border-line bg-surface p-1">
                        @foreach (['public' => 'Everyone', 'invite' => 'Invite only'] as $vis => $label)
                            @php($on = $sp->visibility === $vis)
                            <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="setShareVisibility('{{ $vis }}')"
                                    @class(['h-10.5 cursor-pointer rounded-xl text-sm font-bold transition-colors', 'bg-ink text-bg' => $on, 'text-ink2' => ! $on])>{{ $label }}</button>
                        @endforeach
                    </div>
                    <p class="m-0 text-[13px] leading-[1.45] text-ink2">{{ $invite ? 'Hidden from Open plays. Only people with the link can join.' : 'Listed in Open plays. The link works for anyone.' }}</p>
                </section>
                <section aria-label="Link settings" class="flex flex-col overflow-hidden rounded-[18px] border border-line bg-surface">
                    <div class="flex items-center gap-3.5 border-b border-line px-4 py-3.5">
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5"><span id="shl-g" class="text-sm font-bold">Guests can join without an account</span><span class="text-xs leading-[1.4] text-ink2">{{ $sp->link_guests ? 'They add a name (email optional) and show as guests.' : 'Everyone who opens the link logs in or creates a free account first.' }}</span></div>
                        <x-switch :on="$sp->link_guests" wire:click="toggleLinkGuests" label="Guests can join without an account" />
                    </div>
                    <div class="flex items-center gap-3.5 px-4 py-3.5">
                        <div class="flex min-w-0 flex-1 flex-col gap-0.5"><span class="text-sm font-bold">Reset link</span><span class="text-xs leading-[1.4] text-ink2">The old link stops working. Everyone who already joined stays in.</span></div>
                        <button type="button" wire:click="resetShareLink" class="h-10 shrink-0 cursor-pointer rounded-full border-[1.5px] border-hot-line px-4 text-sm font-bold text-hot transition hover:brightness-96 active:scale-97">Reset</button>
                    </div>
                </section>
            @endif

            <p class="m-0 flex items-start gap-2 text-[13px] leading-[1.45] text-ink3"><x-icon name="alert" :size="16" class="mt-px shrink-0" />
                <span>{{ $manage ? 'They join as a guest, sign up or log in. Logged-in players join right away.' : 'Anyone with the link can see this open play and join'.($sp->link_guests ? ', with or without a PickleX account.' : ' with a PickleX account.') }}</span>
            </p>
        </div>

        <div class="flex shrink-0 gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
            <a href="{{ $url }}" wire:navigate class="inline-flex h-13 items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 px-5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon name="eye" :size="17" />Open link</a>
            <button type="button" wire:click="closeShare" class="h-13 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-bold text-bg transition hover:brightness-96 active:scale-97">Done</button>
        </div>
    </div>
</div>
