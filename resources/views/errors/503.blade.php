@extends('errors.layout')
@php($home = auth()->check() ? route(auth()->user()->homeRoute()) : route('home'))
@php($down = app()->maintenanceMode()->active() ? app()->maintenanceMode()->data() : [])
@php($since = isset($down['time']) ? Illuminate\Support\Carbon::createFromTimestamp($down['time'])->timezone(config('app.timezone')) : null)
@php($back = $since && ! empty($down['retry']) ? $since->copy()->addSeconds((int) $down['retry']) : null)
@section('code', '503')
@section('hot', 1)
@section('dot', '#F59A3E')
@section('title', 'Courts are being resurfaced.')
@section('kicker', 'Error 503: Maintenance')
@section('pill', 'RESURFACING')
@section('scene', 'A court taped off with cones while it is resurfaced')
@section('foot', '503 Service Unavailable')
@section('lead', 'PickleX is down for scheduled maintenance. Open plays, queues and scores are safe.')
@section('body')
    <div x-data="{ checking: false }" class="contents">
        <div class="flex flex-col gap-2.5 rounded-[20px] border border-line bg-surface px-4.5 py-4">
            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <span class="font-bold">{{ $back && $back->isFuture() ? 'Back by about '.$back->format('g:i A') : 'Back soon' }}</span>
                @if ($since)
                    <span class="font-mono text-xs text-ink3">Started {{ $since->format('g:i A') }}</span>
                @endif
            </div>
            @if ($back && $back->isFuture())
                <div class="h-2 overflow-hidden rounded bg-line"><div class="h-full rounded bg-lime-text" style="width: {{ min(95, round(now()->diffInSeconds($since, true) * 100 / max(1, $since->diffInSeconds($back, true)))) }}%"></div></div>
            @endif
            <span role="status" aria-live="polite" class="text-[13px] text-ink3" x-text="checking ? 'Checking the courts…' : 'Last checked {{ now()->format('g:i A') }}'">Last checked {{ now()->format('g:i A') }}</span>
        </div>
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap">
            <button type="button" x-on:click="checking = true; location.reload()" x-bind:disabled="checking" class="inline-flex h-13.5 items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 cursor-pointer disabled:opacity-70">
                <span x-text="checking ? 'Checking…' : 'Check again'">Check again</span>
            </button>
        </div>
    </div>
@endsection
