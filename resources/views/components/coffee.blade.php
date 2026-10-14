{{-- Buy me a coffee: Jayvee's QR Ph code (InstaPay, BPI). Opened from the sidebar or the mobile account menu with $dispatch('open-coffee'). --}}
<div x-data="{ open: false }" x-on:open-coffee.window="open = true" x-on:keydown.escape.window="open = false">
    <template x-if="open">
        <div class="fixed inset-0 z-66 flex items-end justify-center md:items-center md:p-6">
            <button type="button" x-on:click="open = false" aria-label="Close" class="absolute inset-0 animate-fade cursor-pointer bg-[rgba(18,23,15,.45)] dark:bg-black/62"></button>
            <div role="dialog" aria-modal="true" aria-labelledby="coffee-title" x-trap.noscroll="open" class="relative flex max-h-full w-full animate-rise flex-col overflow-clip rounded-t-[28px] bg-bg shadow-[0_30px_80px_-20px_rgba(0,0,0,.45)] md:w-170 md:max-w-full md:rounded-[28px]">
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-line pt-5 pr-5 pb-4 pl-6">
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <span class="font-mono text-[11px] tracking-[.12em] text-ink3 uppercase">Support PickleX</span>
                        <h2 id="coffee-title" class="m-0 font-display text-[26px] font-extrabold tracking-[-0.03em]">Buy me a coffee</h2>
                    </div>
                    <button type="button" x-on:click="open = false" aria-label="Close" class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-line bg-surface text-ink transition hover:brightness-96 active:scale-97"><x-icon name="x" :size="18" /></button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-4.5 overflow-y-auto px-6 pt-5 pb-5.5">
                    <div class="flex items-center gap-3.5">
                        <img src="/images/jayvee.webp" alt="" class="size-14 shrink-0 rounded-full border-2 border-lime object-cover object-[50%_30%]">
                        <p class="m-0 text-[15px] leading-normal text-ink">Hi, I'm Jayvee. I build and look after PickleX on my own time. If it makes your open plays run smoother, a coffee keeps it going.</p>
                    </div>
                    <div class="flex flex-col items-center gap-5.5 lg:flex-row">
                        <div class="flex shrink-0 flex-col items-center gap-2 rounded-3xl border border-[#DEDCD0] bg-white px-3.5 pt-3.5 pb-3 shadow-[0_16px_36px_-18px_rgba(0,0,0,.35)]">
                            <img src="/images/coffee-qr.svg" alt="QR Ph code (InstaPay) for sending a tip to Jayvee Salango at BPI" class="block size-55 object-contain">
                            <span class="font-mono text-[11px] font-semibold tracking-[.14em] text-on-lime uppercase">QR Ph · InstaPay</span>
                        </div>
                        <div class="flex w-full min-w-0 flex-1 flex-col gap-3.5">
                            <dl class="m-0 flex flex-col overflow-hidden rounded-2xl border border-line bg-surface">
                                @foreach (['Send to' => 'Jayvee', 'Account' => 'BPI ••••&nbsp;427', 'Works with' => 'GCash, Maya, any bank app', 'Amount' => 'Any amount you like'] as $dt => $dd)
                                    <div class="flex items-center justify-between gap-3 border-b border-line px-3.5 py-2.75 last:border-b-0"><dt class="text-[13px] text-ink3">{{ $dt }}</dt><dd class="m-0 text-right text-sm font-bold">{!! $dd !!}</dd></div>
                                @endforeach
                            </dl>
                            <ol class="m-0 flex list-none flex-col gap-2.5 p-0">
                                @foreach ([
                                    'Open GCash, Maya or your bank app and scan the QR. On your phone, save the image and upload it from your gallery.',
                                    'Type any amount. The price of a coffee is plenty. Your app may charge a small InstaPay fee.',
                                    'Check the name reads <strong>Jayvee</strong> (some apps show <strong>JA•••E GO••••A SA••••O</strong>) before you send.',
                                ] as $step)
                                    <li class="flex items-start gap-3 text-sm leading-[1.45]">
                                        <span aria-hidden="true" class="flex size-6 shrink-0 items-center justify-center rounded-full bg-lime text-xs font-extrabold text-on-lime">{{ $loop->iteration }}</span>
                                        <span>{!! $step !!}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                    <p class="m-0 flex items-start gap-2 text-[13px] leading-[1.45] text-ink3"><x-icon name="alert" :size="16" class="mt-px shrink-0" /><span>Tips go to the PickleX developer, not to a club or organizer. Open play fees are still paid to the organizer.</span></p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2.5 border-t border-line bg-bg px-6 pt-3.5 pb-5.5">
                    <a href="/images/jayvee-salango-qr-ph.png" download="jayvee-salango-qr-ph.png" x-on:click="$dispatch('toast', { message: 'QR saved · upload it in GCash, Maya or your bank app' })"
                       class="inline-flex h-13 items-center justify-center gap-2 rounded-full border-[1.5px] border-line2 px-4.5 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon name="download" :size="17" />Save QR</a>
                    <button type="button" x-on:click="open = false" class="h-13 min-w-45 flex-1 cursor-pointer rounded-full bg-ink text-[15px] font-extrabold text-bg transition hover:brightness-96 active:scale-97">Done</button>
                </div>
            </div>
        </div>
    </template>
</div>
