@extends('errors.layout')
@php($wait = max(1, (int) (isset($exception) ? ($exception->getHeaders()['Retry-After'] ?? 30) : 30)))
@section('code', '429')
@section('hot', 1)
@section('dot', '#F59A3E')
@section('title', 'Easy there. One ball at a time.')
@section('kicker', 'Error 429 · Too many requests')
@section('pill', 'SLOW DOWN')
@section('scene', 'A rush of balls flying toward the net at once')
@section('foot', '429 Too Many Requests')
@section('lead', 'You sent a lot of requests in a short time, so we paused things for a moment. This usually happens after refreshing the queue again and again.')
@section('body')
    <div x-data="{ total: {{ $wait }}, left: {{ $wait }} }" x-init="let t = setInterval(() => left > 0 ? left-- : clearInterval(t), 1000)" class="contents">
        <div class="flex items-center gap-4 rounded-[20px] border border-line bg-surface py-3.5 pr-4.5 pl-3.5">
            <div class="relative size-16 shrink-0">
                <svg width="64" height="64" viewBox="0 0 64 64" aria-hidden="true" class="-rotate-90">
                    <circle cx="32" cy="32" r="27" fill="none" stroke="var(--color-line)" stroke-width="6" />
                    <circle cx="32" cy="32" r="27" fill="none" stroke="var(--color-lime-text)" stroke-width="6" stroke-linecap="round" stroke-dasharray="169.6" x-bind:stroke-dashoffset="169.6 * (total - left) / total" class="transition-[stroke-dashoffset] duration-1000 ease-linear" />
                </svg>
                <span class="absolute inset-0 flex items-center justify-center font-mono text-sm font-semibold" x-text="Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0')"></span>
            </div>
            <div role="status" aria-live="polite" class="flex min-w-0 flex-col gap-0.5">
                <span class="font-bold" x-text="left > 0 ? 'You can try again in ' + left + (left === 1 ? ' second' : ' seconds') : 'You\'re good to go'">You can try again soon</span>
                <span class="text-[13px] text-ink3">The court board and queue update live, so there's no need to refresh.</span>
            </div>
        </div>
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap">
            <button type="button" x-show="left > 0" disabled class="inline-flex h-13.5 items-center justify-center rounded-full border-[1.5px] border-line bg-surface2 px-6.5 text-base font-bold text-ink2" x-text="'Try again in ' + Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0')">Try again</button>
            <button type="button" x-show="left === 0" x-cloak x-on:click="location.reload()" class="inline-flex h-13.5 items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 animate-fade cursor-pointer">Try again</button>
            <a href="{{ route('home') }}" class="inline-flex h-13.5 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-transparent px-6 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Back to home</a>
        </div>
    </div>
@endsection
