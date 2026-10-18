@extends('errors.layout')
@php($id = error_id())
@section('code', '500')
@section('hot', 1)
@section('dot', '#FF9152')
@section('title', 'We hit the net on that one.')
@section('kicker', 'Error 500 · Server error')
@section('pill', 'NET FAULT')
@section('scene', 'A ball stuck in the net')
@section('foot', '500 Internal Server Error · '.$id)
@section('lead', 'Something broke on our side. Your queue spot and scores are safe. Try again in a moment.')
@section('body')
    <div x-data="{ copied: false }" class="flex items-center gap-3 rounded-2xl border border-line bg-surface py-3 pr-3 pl-4">
        <div class="flex min-w-0 flex-1 flex-col gap-px">
            <span class="text-xs font-bold text-ink3">Error ID</span>
            <span class="truncate font-mono text-[15px] font-semibold">{{ $id }}</span>
            <span class="text-xs text-ink3">{{ now()->format('D, M j · g:i A') }}</span>
        </div>
        <button type="button" aria-label="Copy error ID" x-on:click="navigator.clipboard?.writeText(@js($id)).catch(() => {}); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex h-10 shrink-0 cursor-pointer items-center gap-1.5 rounded-full border-[1.5px] border-line2 bg-transparent px-3.5 text-[13px] font-bold text-ink transition hover:brightness-96 active:scale-97">
            <x-icon name="copy" :size="15" /><span x-text="copied ? 'Copied' : 'Copy'">Copy</span>
        </button>
    </div>
    <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap">
        <button type="button" onclick="location.reload()" class="inline-flex h-13.5 items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 cursor-pointer">Try again</button>
        <a href="mailto:{{ config('mail.from.address') }}?subject={{ rawurlencode('PickleX error '.$id) }}&body={{ rawurlencode("Error ID: {$id}\nWhat I was doing: ") }}" class="inline-flex h-13.5 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-transparent px-6 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Report this problem</a>
    </div>
@endsection
