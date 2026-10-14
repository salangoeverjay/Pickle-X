{{-- One What's new update: kind badge, New, audience, date, title and body. $page: the larger What's new page version. --}}
@props(['news', 'fresh' => false, 'page' => false])

@php([$kind, $icon, $tone] = App\Models\News::KINDS[$news->kind] ?? App\Models\News::KINDS['feature'])

<div {{ $attributes->class(['flex items-start border border-line bg-surface', 'gap-4 rounded-[22px] p-4.5 lg:p-5' => $page, 'gap-3.5 rounded-[20px] p-4' => ! $page]) }}>
    <span aria-hidden="true" @class(['flex shrink-0 items-center justify-center rounded-[14px] border', $tone, 'size-11' => $page, 'size-10' => ! $page])>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}" /></svg>
    </span>
    <div @class(['flex min-w-0 flex-1 flex-col', 'gap-1.5' => $page, 'gap-1.25' => ! $page])>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5">
            <span class="inline-flex h-6 items-center gap-1.25 rounded-full border px-2.25 text-xs font-extrabold whitespace-nowrap {{ $tone }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}" /></svg>{{ $kind }}
            </span>
            @if ($fresh)
                <span class="inline-flex h-6 items-center rounded-full bg-lime px-2.25 text-xs font-extrabold text-on-lime">New</span>
            @endif
            @if ($news->audience !== 'all')
                <span class="inline-flex h-6 items-center rounded-full border border-line bg-surface2 px-2.25 text-xs font-bold whitespace-nowrap text-ink2">{{ App\Models\News::AUDIENCES[$news->audience] }}</span>
            @endif
            <span class="font-mono text-[13px] text-ink3">{{ $news->dateLabel() }}</span>
        </div>
        @if ($page)
            <h2 class="mt-0.5 mb-0 font-display text-xl leading-[1.2] font-bold tracking-[-0.02em]">{{ $news->title }}</h2>
            <p class="m-0 text-[15px] leading-normal text-ink2">{{ $news->body }}</p>
        @else
            <span class="text-[17px] leading-[1.3] font-bold">{{ $news->title }}</span>
            <span class="text-[14.5px] leading-normal text-ink2">{{ $news->body }}</span>
        @endif
    </div>
</div>
