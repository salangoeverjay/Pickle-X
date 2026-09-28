{{-- Branded error page. Sections: code, title, message. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- yieldContent() is already escaped; decode so the <title> isn't escaped twice. --}}
        @include('partials.head', ['title' => html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES).' · PickleX'])
    </head>
    <body>
        @php($home = auth()->check() ? route(auth()->user()->homeRoute()) : route('home'))
        <main id="main" class="flex min-h-dvh flex-col px-4 pt-4 pb-8 lg:px-14 lg:pt-6 lg:pb-14">
            <a href="{{ route('home') }}" aria-label="PickleX home" class="self-start text-ink"><x-logo class="text-[27px]" /></a>

            <div class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:gap-16">
                <div class="flex animate-rise flex-col items-start gap-5">
                    <span class="font-mono text-xs tracking-[.14em] text-ink3 uppercase">Error @yield('code')</span>
                    <h1 class="m-0 font-display text-[44px] leading-[.95] font-extrabold tracking-[-0.045em] text-balance lg:text-7xl">@yield('title')</h1>
                    <p class="m-0 max-w-120 text-[17px] leading-[1.55] text-pretty text-ink2">@yield('message')</p>
                    <div class="flex flex-wrap gap-3 pt-1">
                        <a href="{{ $home }}" class="inline-flex h-13.5 items-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">
                            {{ auth()->check() ? 'Go to my PickleX' : 'Back to home' }}<x-icon name="arrow-right" :size="18" :stroke="2.2" />
                        </a>
                        <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ $home }}')" class="inline-flex h-13.5 cursor-pointer items-center rounded-full border-[1.5px] border-line2 px-5.5 text-base font-semibold text-ink transition hover:brightness-96 active:scale-97">Go back</button>
                    </div>
                </div>

                {{-- An empty court: nobody's playing here. --}}
                <div aria-hidden="true" class="relative h-55 animate-rise [animation-delay:.12s] lg:h-80">
                    <x-court class="absolute inset-0 rounded-[28px] shadow-[0_40px_60px_-34px_rgba(8,30,18,.6)] transform-[perspective(1200px)_rotateX(22deg)_rotateZ(-5deg)]" label="">
                        <div class="absolute inset-0 z-1 flex items-center justify-center bg-[rgba(8,20,14,.28)]">
                            <span class="font-display text-[64px] leading-none font-extrabold tracking-[-0.05em] text-white/92 lg:text-[104px]">@yield('code')</span>
                        </div>
                    </x-court>
                </div>
            </div>
        </main>
    </body>
</html>
