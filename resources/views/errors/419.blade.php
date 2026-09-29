@extends('errors.layout')
@section('code', '419')
@section('title', 'Time out. This page expired.')
@section('kicker', 'Error 419 · Page expired')
@section('pill', 'TIME OUT')
@section('scene', 'A stopwatch in the middle of the court during a time out')
@section('foot', '419 Page Expired')
@section('lead', 'For your security, forms expire after a while without activity. Refresh the page and send it again. If you were typing something, you may need to enter it again.')
@section('body')
    <div class="flex items-start gap-3 rounded-2xl border border-line bg-surface px-4 py-3.5">
        <x-icon name="shield" :size="20" class="mt-px text-ink2" />
        <p class="m-0 text-sm leading-normal text-ink2">This stops anyone else from sending a form from your browser. Your open plays, queue spots and scores aren't affected.</p>
    </div>
    <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap" x-data="{ busy: false }">
        <button type="button" x-on:click="busy = true; location.reload()" x-bind:disabled="busy" class="inline-flex h-13.5 items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97 cursor-pointer disabled:opacity-70">
            <span x-text="busy ? 'Refreshing…' : 'Refresh and try again'">Refresh and try again</span>
        </button>
        <a href="{{ route('login') }}" class="inline-flex h-13.5 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-transparent px-6 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Log in again</a>
    </div>
@endsection
