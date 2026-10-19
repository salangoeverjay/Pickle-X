{{--
    Branded error page: message on the left, a court scene for the code on the right
    (scene first on phones). Sections: code, title, kicker, pill, dot, scene, foot,
    lead, body (extra blocks and actions). @section('hot', 1) tints the kicker orange.
--}}
@php($code = trim($__env->yieldContent('code')))
@php($hot = $__env->hasSection('hot'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- yieldContent() is already escaped; decode so the <title> isn't escaped twice. --}}
        @include('partials.head', ['title' => html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES).' | PickleX'])
    </head>
    <body>
        <div class="relative flex min-h-dvh flex-col overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[linear-gradient(rgba(18,23,15,.07)_1px,transparent_1px),linear-gradient(90deg,rgba(18,23,15,.07)_1px,transparent_1px)] bg-size-[48px_48px] dark:bg-[linear-gradient(rgba(237,242,227,.05)_1px,transparent_1px),linear-gradient(90deg,rgba(237,242,227,.05)_1px,transparent_1px)]"></div>

            <header class="relative flex shrink-0 items-center justify-between gap-3 px-4 py-2.5 lg:px-12 lg:py-6 2xl:px-16 2xl:py-7">
                <a href="{{ route('home') }}" aria-label="PickleX home" class="text-ink"><x-logo class="text-[25px] lg:text-[28px] 2xl:text-[30px]" /></a>
                <x-theme-toggle />
            </header>

            <main id="main" class="relative grid flex-1 content-start items-center gap-5.5 px-4 pt-1 pb-4.5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.08fr)] lg:content-center lg:gap-18 lg:px-18 lg:pt-2 lg:pb-8 2xl:gap-24 2xl:px-30 2xl:pb-10">
                {{-- Court scene --}}
                <div role="img" aria-label="@yield('scene')" class="relative h-52 w-full animate-rise overflow-hidden rounded-[26px] bg-court-out shadow-[0_40px_60px_-40px_rgba(0,0,0,.55)] lg:order-2 lg:h-[min(560px,62dvh)] lg:rounded-[36px] 2xl:h-[min(680px,66dvh)] 2xl:rounded-[40px]">
                    @include('errors.scene', ['code' => $code])
                    <span class="absolute top-3 left-3 inline-flex h-7.5 items-center gap-2 rounded-full bg-black/35 px-3 font-mono text-[11px] tracking-[.12em] whitespace-nowrap text-white lg:top-5.5 lg:left-5.5 2xl:top-6 2xl:left-6">
                        <span class="size-1.75 rounded-full" style="background: @yield('dot', '#DDF84E')"></span>@yield('pill')
                    </span>
                    <span aria-hidden="true" class="absolute right-4 bottom-3 font-display text-[68px] leading-[.8] font-extrabold tracking-[-0.06em] text-white [text-shadow:0_6px_30px_rgba(0,0,0,.35)] lg:right-7 lg:bottom-5.5 lg:text-[132px] 2xl:right-8 2xl:bottom-6.5 2xl:text-[160px]">{{ $code }}</span>
                </div>

                {{-- Message --}}
                <div class="flex max-w-135 animate-rise flex-col gap-4.5 [animation-delay:.08s] lg:gap-6 2xl:max-w-155 2xl:gap-6.5">
                    <span @class([
                        'inline-flex h-7.5 items-center self-start rounded-full border px-3 font-mono text-[11px] font-semibold tracking-widest whitespace-nowrap uppercase',
                        'border-hot-line bg-hot-soft text-hot' => $hot,
                        'border-line bg-surface text-ink2' => ! $hot,
                    ])>@yield('kicker')</span>
                    <div class="flex flex-col gap-3">
                        <h1 class="m-0 font-display text-[34px] leading-[.98] font-extrabold tracking-[-0.045em] text-balance lg:text-[64px] 2xl:text-[76px]">@yield('title')</h1>
                        <p class="m-0 text-base leading-[1.55] text-pretty text-ink2 lg:text-lg 2xl:text-xl">@yield('lead')</p>
                    </div>
                    @yield('body')
                </div>

                <p class="m-0 pt-1 pb-1.5 text-center text-xs text-ink3 lg:hidden"><x-credit /></p>
            </main>

            <footer class="relative hidden shrink-0 items-center justify-between gap-x-4 gap-y-1.5 px-12 pt-3.5 pb-6.5 text-xs text-ink3 lg:flex 2xl:px-16 2xl:pt-4 2xl:pb-7">
                <x-credit />
                <span class="font-mono">@yield('foot')</span>
            </footer>
        </div>
    </body>
</html>
