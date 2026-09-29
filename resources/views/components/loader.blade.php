{{-- Loading screen for logging in, logging out and creating an account (resources/js/loader.js).
     session('loader'): ['kind' => login|logout|signup|signup-org, 'first' => name] flashed on success. --}}
@php($flash = session('loader'))
<div x-data="pxLoader(@js($flash), @js(auth()->user()?->firstName()))" x-on:px-loading.window="start($event.detail.kind, $event.detail.first)">
    <template x-if="k">
        <div x-bind:class="out && 'px-ld-out'" class="px-ld fixed inset-0 z-95 flex items-center justify-center overflow-hidden bg-bg p-6" role="status" aria-live="polite" aria-busy="true" x-bind:aria-label="k.title">
            <div aria-hidden="true" class="px-ld-grid pointer-events-none absolute inset-0"></div>
            <div class="relative flex w-full max-w-105 flex-col items-center gap-5.5 text-center">
                <div aria-hidden="true" class="relative size-25">
                    <svg class="px-ld-spin absolute inset-0" width="100" height="100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="45" fill="none" stroke="var(--color-line)" stroke-width="5" />
                        <path d="M50 5a45 45 0 0 1 45 45" fill="none" stroke="var(--color-lime)" stroke-width="5" stroke-linecap="round" />
                        <path d="M50 5a45 45 0 0 1 45 45" fill="none" stroke="var(--color-cta-border)" stroke-width="1.5" stroke-linecap="round" opacity=".35" />
                    </svg>
                    <div class="px-ld-breathe absolute inset-0">
                        <svg class="absolute inset-5.5" width="56" height="56" viewBox="0 0 48 48">
                            <circle cx="24" cy="15.5" r="10" fill="#F59A3E" fill-opacity=".88" />
                            <circle cx="31.36" cy="19.75" r="10" fill="#E5508A" fill-opacity=".88" />
                            <circle cx="31.36" cy="28.25" r="10" fill="#7C62DA" fill-opacity=".88" />
                            <circle cx="24" cy="32.5" r="10" fill="#3F86E8" fill-opacity=".88" />
                            <circle cx="16.64" cy="28.25" r="10" fill="#2FBFC7" fill-opacity=".88" />
                            <circle cx="16.64" cy="19.75" r="10" fill="#C3EA3B" fill-opacity=".88" />
                            <g fill="#FFFFFF">
                                @foreach ([0, 60, 120, 180, 240, 300] as $deg)
                                    <path transform="rotate({{ $deg }} 24 24)" d="M24 24C22.2 21.4 22.2 18 24 15.4C25.8 18 25.8 21.4 24 24Z" />
                                @endforeach
                                <circle cx="24" cy="24" r="1.8" />
                            </g>
                        </svg>
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <h1 class="m-0 font-display text-[34px] leading-[1.05] font-extrabold tracking-[-0.04em]" x-text="k.title"></h1>
                    <p class="m-0 text-base text-ink2" x-text="k.sub(first)"></p>
                </div>
                <ol class="m-0 flex w-full max-w-80 list-none flex-col gap-2.5 p-0 text-left">
                    <template x-for="(t, i) in k.steps" x-bind:key="t">
                        <li class="px-ld-step flex items-center gap-3" x-bind:style="inAt(i)">
                            <span aria-hidden="true" class="relative size-6 shrink-0">
                                <span class="px-ld-ringw absolute inset-0" x-bind:style="doneAt(i)">
                                    <svg class="px-ld-ring" width="24" height="24" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9" fill="none" stroke="var(--color-line2)" stroke-width="2.4" />
                                        <path d="M12 3a9 9 0 0 1 9 9" fill="none" stroke="var(--color-ink)" stroke-width="2.4" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <span class="px-ld-check absolute inset-0 flex items-center justify-center rounded-full bg-lime text-on-lime" x-bind:style="doneAt(i)"><x-icon name="check" :size="13" :stroke="3.4" /></span>
                            </span>
                            <span class="text-[15px] font-semibold" x-text="t"></span>
                        </li>
                    </template>
                </ol>
                <div aria-hidden="true" class="h-1.5 w-full max-w-80 overflow-hidden rounded-[3px] bg-surface2"><div class="px-ld-bar h-full rounded-[3px] bg-lime" x-bind:style="bar()"></div></div>
                <span class="font-mono text-xs tracking-[.12em] text-ink3 uppercase" x-text="k.kicker"></span>
            </div>
        </div>
    </template>
</div>
