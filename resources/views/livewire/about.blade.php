@php
    $gut = 'px-4 lg:px-[max(56px,calc((100%-1520px)/2))]';
    $ico = fn ($d, $size = 18) => '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0"><path d="'.$d.'"/></svg>';
    $heart = fn ($size) => '<svg class="px-heart inline-block shrink-0 align-[-0.08em]" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" role="img" aria-label="love"><path d="M12 20.6s-7.7-4.7-9.5-9.4C1.2 7.8 3.3 4.4 6.9 4.4c2.1 0 3.8 1.1 5.1 2.9 1.3-1.8 3-2.9 5.1-2.9 3.6 0 5.7 3.4 4.4 6.8-1.8 4.7-9.5 9.4-9.5 9.4Z" fill="#EF4452"/></svg>';
    $arrow = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 16 16 8M9.5 8H16v6.5"/></svg>';
    $facts = [
        ['M8.5 7 3.5 12l5 5M15.5 7l5 5-5 5M13.5 4.5l-3 15', 'Full stack developer'],
        ['M3.5 8h17v11.5h-17zM9 8V5.5h6V8M3.5 13h17', 'Team Lead at CreativeX Techlabs Inc.'],
        ['M12 21s-6.5-6-6.5-11a6.5 6.5 0 0 1 13 0c0 5-6.5 11-6.5 11ZM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z', 'Panabo City, Davao del Norte'],
        ['M4 6h16v14H4zM4 10h16M8 3.5v4M16 3.5v4', 'Building for the web since 2018'],
    ];
    $stats = [
        ['On PickleX', '1', 'developer behind every screen, from sign-up to the court board', true],
        ['Building for the web', '8 yrs', 'of full stack work, since August 2018', false],
        ['Remote teams', '6', 'companies across the US, Australia and the UK', false],
        ['Team Lead since', '2026', 'at CreativeX Techlabs Inc., after joining as a Senior Full Stack Developer', false],
    ];
    $built = [
        ['M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01', 'The fair queue', 'Rotation, locked partners, sub-outs, check-in, and a queue that keeps up with 100+ players.'],
        ['M3 6h18v12H3zM12 6v12M8 6v12M16 6v12M3 12h5M16 12h5', 'Live court board', 'Courts, scores, winners-stay rules and a read-only view mode you can share by QR.'],
        ['M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4.5 20.5a7.5 7.5 0 0 1 15 0', 'Player profiles', 'Avatars, display names, rankings, played-with stats and dozens of animated looks.'],
        ['M12 3.5 19 6v5.5c0 4.4-3 7.8-7 9-4-1.2-7-4.6-7-9V6Z', 'Organizer and admin tools', 'Open plays, QR payments, rosters, organizer approvals, and developer and sponsor looks.'],
        ['M3.5 6h17v12h-17zM3.5 7l8.5 6.5L20.5 7', 'Emails and error pages', 'Welcome, join, update, recap and reset emails, plus friendly error pages.'],
        ['M20 14.6A8 8 0 0 1 9.4 4 8 8 0 1 0 20 14.6Z', 'Every screen, both themes', 'Desktop and mobile, in light and dark mode.'],
    ];
    $xp = [
        ['Apr 2026 – now', 'Team Lead', 'CreativeX Techlabs Inc. (Remote, Davao)', []],
        ['Nov 2025 – Apr 2026', 'Senior Full Stack Developer', 'CreativeX Techlabs Inc.', []],
        ['From Aug 2018', 'Full stack web developer, remote', 'For companies in the US, Australia and the UK', ['Naxum', 'Courses.com.au', 'Strongwill', 'iTravel Australia', 'IdeaHub', 'Pitched Coders']],
    ];
    $tools = [['Laravel', '#FF2D20'], ['Vue.js', '#42B883'], ['Next.js', 'var(--color-ink)'], ['Laravel Sail', '#38BDF8']];
@endphp

<div class="overflow-x-clip">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-line bg-glass backdrop-blur-[14px] lg:h-19 {{ $gut }}">
        <a href="{{ route('home') }}" wire:navigate aria-label="PickleX home" class="text-ink"><x-logo class="text-[27px]" /></a>
        <div class="flex items-center gap-2">
            <x-theme-toggle />
            <a href="{{ $back }}" wire:navigate class="inline-flex h-11 items-center gap-2 rounded-full border border-line2 pr-4.5 pl-3.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg><span class="lg:hidden">Back</span><span class="hidden lg:inline">Back to PickleX</span>
            </a>
        </div>
    </header>

    <main id="main" tabindex="-1" aria-labelledby="ab-h">
        {{-- Hero: intro and the photo --}}
        <section class="relative grid grid-cols-1 items-center gap-6.5 overflow-hidden pt-5 pb-9 lg:grid-cols-[minmax(0,1fr)_minmax(0,540px)] lg:gap-18 lg:pt-12 lg:pb-19 {{ $gut }}">
            <div aria-hidden="true" class="about-grid pointer-events-none absolute inset-0"></div>
            <div class="relative order-2 flex min-w-0 flex-col gap-4.5 lg:order-1 lg:gap-6">
                <div class="inline-flex h-9 animate-rise items-center gap-2.5 self-start rounded-full border border-line bg-surface pr-3.5 pl-3 text-[13px] font-semibold text-ink2">
                    <span class="size-2.25 animate-live rounded-full bg-lime outline-[1.5px] outline-on-lime outline-solid"></span>Meet the person behind the app
                </div>
                <h1 id="ab-h" class="m-0 animate-rise font-display text-[54px] leading-[.92] font-extrabold tracking-[-0.05em] [animation-delay:.06s] lg:text-[104px]">
                    Hi, I'm <span class="highlight-band -ml-1 px-2 text-hl-text">Jayvee.</span>
                </h1>
                <p class="m-0 max-w-160 animate-rise text-base leading-[1.55] text-ink2 [animation-delay:.12s] lg:text-[19px]">I'm the sole developer of PickleX. I designed and built every part of it, from the fair queue and live court board to rankings, QR payments and every animated border, so open play runs smoothly for players and organizers.</p>
                <ul aria-label="About Jayvee" class="m-0 flex animate-rise list-none flex-wrap gap-2 p-0 [animation-delay:.18s]">
                    @foreach ($facts as [$d, $label])
                        <li class="inline-flex min-h-10 items-center gap-2 rounded-full border border-line bg-surface py-2 pr-3.5 pl-3 text-sm font-semibold text-ink">{!! $ico($d, 17) !!}{{ $label }}</li>
                    @endforeach
                </ul>
                <div class="mt-1 flex animate-rise flex-wrap gap-3 [animation-delay:.24s]">
                    <a href="https://creativexph.com/" target="_blank" rel="noopener noreferrer" aria-label="Visit CreativeX Techlabs Inc. (opens in a new tab)"
                       class="inline-flex h-13.5 grow items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 lg:grow-0">Visit CreativeX{!! $arrow !!}</a>
                    <a href="{{ $back }}" wire:navigate class="inline-flex h-13.5 grow items-center justify-center rounded-full border-[1.5px] border-line2 px-5.5 text-base font-bold text-ink transition hover:brightness-96 active:scale-97 lg:grow-0">Back to PickleX</a>
                </div>
            </div>

            <div class="relative order-1 w-full max-w-75 justify-self-center pt-2.5 pr-4 pb-5 pl-2 lg:order-2 lg:max-w-120 lg:pt-5.5 lg:pr-7.5 lg:pb-7.5 lg:pl-5.5">
                <div aria-hidden="true" class="about-ball absolute right-[-2px] bottom-0.5 size-23 animate-spin-slow rounded-full border-2 border-[#12170F] lg:-right-2 lg:-bottom-1.5 lg:size-37.5"></div>
                <figure class="relative m-0 aspect-[2/3] rotate-[1.5deg] animate-rise overflow-hidden rounded-[26px] border-2 border-[#12170F] bg-surface2 shadow-[6px_6px_0_#12170F] [animation-delay:.1s] lg:rotate-2 lg:rounded-[36px] lg:shadow-[10px_10px_0_#12170F] dark:border-line2 dark:shadow-[0_30px_70px_-24px_rgba(212,245,60,.35)]">
                    <img src="/images/jayvee.webp" width="600" height="900" alt="Jayvee Salango getting ready to serve, paddle and ball in hand, on an indoor pickleball court" class="block size-full object-cover object-[50%_40%]">
                    <div aria-hidden="true" class="absolute inset-0 bg-[linear-gradient(180deg,rgba(10,14,11,0)_55%,rgba(10,14,11,.78)_100%)]"></div>
                    <figcaption class="absolute inset-x-4.5 bottom-4.5 flex flex-col gap-0.5 text-white lg:inset-x-6 lg:bottom-6">
                        <span class="font-display text-[22px] leading-[1.05] font-extrabold tracking-[-0.03em] lg:text-[30px]">Jayvee Salango</span>
                        <span class="text-sm font-semibold opacity-88">Full stack developer, Panabo City</span>
                    </figcaption>
                </figure>
                <span class="absolute top-[56%] -left-1 inline-flex h-11.5 -rotate-6 animate-snap items-center gap-2 rounded-full border-2 border-[#12170F] bg-lime pr-4.5 pl-3.5 text-[15px] font-extrabold whitespace-nowrap text-[#12170F] shadow-[4px_4px_0_#12170F] lg:top-[58%] lg:-left-4.5">
                    {!! $ico('M8.5 7 3.5 12l5 5M15.5 7l5 5-5 5M13.5 4.5l-3 15', 18) !!}Sole developer of PickleX
                </span>
            </div>
        </section>

        {{-- At a glance --}}
        <section aria-label="At a glance" class="pb-3 lg:pb-3.5 {{ $gut }}">
            <div class="grid grid-cols-2 gap-3.5 lg:grid-cols-4">
                @foreach ($stats as [$k, $v, $t, $hot])
                    <div @class(['flex flex-col gap-2.5 rounded-[26px] border p-4.5 lg:p-6.5', 'border-lime bg-lime text-on-lime' => $hot, 'border-line bg-surface text-ink' => ! $hot])>
                        <span class="font-mono text-[11px] tracking-[.12em] uppercase opacity-80">{{ $k }}</span>
                        <span class="font-display text-[34px] leading-none font-extrabold tracking-[-0.045em] lg:text-[56px]">{{ $v }}</span>
                        <span class="text-sm leading-[1.45] opacity-90">{{ $t }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- What I built, experience and tools --}}
        <section class="grid grid-cols-1 items-start gap-3.5 pb-3 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)] lg:pb-3.5 {{ $gut }}">
            <div class="flex flex-col gap-5 rounded-[30px] border border-line bg-surface p-5 lg:p-8">
                <div class="flex flex-col gap-1.5">
                    <span class="font-mono text-xs tracking-[.12em] text-ink3 uppercase">Design to deploy, one person</span>
                    <h2 class="m-0 font-display text-[28px] leading-[1.02] font-extrabold tracking-[-0.04em] lg:text-4xl">What I built for PickleX</h2>
                </div>
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach ($built as [$d, $t, $s])
                        <div class="flex items-start gap-3.5 rounded-[22px] border border-line bg-surface2 p-4.5">
                            <span class="flex size-11.5 shrink-0 items-center justify-center rounded-[15px] border-2 border-cta-border bg-lime text-on-lime">{!! $ico($d, 21) !!}</span>
                            <div class="flex min-w-0 flex-col gap-1"><span class="text-base font-extrabold">{{ $t }}</span><span class="text-sm leading-normal text-ink2">{{ $s }}</span></div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3.5">
                <div class="flex flex-col gap-4.5 rounded-[30px] border border-line bg-surface p-5 lg:p-8">
                    <h2 class="m-0 font-display text-[28px] leading-[1.02] font-extrabold tracking-[-0.04em] lg:text-4xl">Experience</h2>
                    <ol class="m-0 flex list-none flex-col p-0">
                        @foreach ($xp as $i => [$when, $role, $org, $cos])
                            @php($last = $i === count($xp) - 1)
                            <li class="grid grid-cols-[18px_minmax(0,1fr)] gap-3.5">
                                <div aria-hidden="true" class="flex flex-col items-center pt-1.25">
                                    <span @class(['size-3.5 shrink-0 rounded-full border-2 border-ink', 'bg-lime' => $i === 0, 'bg-surface' => $i > 0])></span>
                                    @unless ($last)<span class="mt-1 w-0.5 flex-1 bg-line2"></span>@endunless
                                </div>
                                <div @class(['flex min-w-0 flex-col gap-0.75', 'pb-5.5' => ! $last])>
                                    <span class="font-mono text-xs tracking-[.12em] text-ink3 uppercase">{{ $when }}</span>
                                    <span class="text-[17px] leading-[1.3] font-extrabold">{{ $role }}</span>
                                    <span class="text-sm text-ink2">{{ $org }}</span>
                                    @if ($cos)
                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            @foreach ($cos as $co)
                                                <span class="inline-flex h-7.5 items-center rounded-full border border-line bg-surface2 px-3 text-[13px] font-semibold">{{ $co }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="flex flex-col gap-3.5 rounded-[30px] border border-line bg-surface p-5 lg:p-8">
                    <h2 class="m-0 font-display text-[22px] font-extrabold tracking-[-0.03em]">Tools I work with</h2>
                    <ul aria-label="Tools I work with" class="m-0 flex list-none flex-wrap gap-2 p-0">
                        @foreach ($tools as [$n, $c])
                            <li class="inline-flex h-10 items-center gap-2 rounded-full border border-line bg-surface2 pr-4 pl-3 text-sm font-bold"><span aria-hidden="true" class="size-2.5 rounded-[3px]" style="background: {{ $c }}"></span>{{ $n }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- Credits --}}
        <section class="pt-3 pb-9 lg:pt-3.5 lg:pb-16 {{ $gut }}">
            <div class="relative flex flex-col items-start justify-between gap-6.5 overflow-hidden rounded-[28px] bg-ink px-5.5 py-8 text-bg lg:flex-row lg:items-center lg:rounded-[40px] lg:px-16 lg:py-14">
                <div aria-hidden="true" class="about-ball absolute -top-20 -right-17.5 size-42.5 animate-spin-slow rounded-full opacity-90 lg:-top-42.5 lg:-right-27.5 lg:size-70"></div>
                <div class="relative flex max-w-205 flex-col gap-3">
                    <span class="font-mono text-xs tracking-[.12em] uppercase opacity-70">Credits</span>
                    <h2 class="m-0 font-display text-[34px] leading-none font-extrabold tracking-[-0.045em] text-balance lg:text-[58px]">
                        Made with <span class="lg:hidden">{!! $heart(30) !!}</span><span class="hidden lg:inline">{!! $heart(50) !!}</span> by CreativeX Techlabs Inc.
                    </h2>
                    <p class="m-0 max-w-150 text-base leading-[1.55] opacity-82 lg:text-[19px]">PickleX is designed, built and looked after by Jayvee Salango at CreativeX Techlabs Inc. Thanks for playing, and see you in the queue.</p>
                </div>
                <a href="https://creativexph.com/" target="_blank" rel="noopener noreferrer" aria-label="creativexph.com (opens in a new tab)"
                   class="relative inline-flex h-14 shrink-0 items-center justify-center gap-2.5 rounded-full bg-lime px-6.5 text-base font-bold text-on-lime transition hover:brightness-96 active:scale-97">creativexph.com{!! $arrow !!}</a>
            </div>
        </section>
    </main>

    <footer class="flex flex-col items-start justify-between gap-4 border-t border-line pt-7 pb-8 lg:flex-row lg:items-center lg:pt-10 lg:pb-12 {{ $gut }}">
        <x-credit class="text-sm text-ink3" />
        <a href="{{ $back }}" wire:navigate class="inline-flex h-11 items-center rounded-full px-3.5 text-sm font-semibold text-ink2 transition-colors hover:bg-surface2 hover:text-ink">Back to PickleX</a>
    </footer>
</div>
