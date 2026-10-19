@extends('errors.layout')
@php($home = auth()->check() ? route(auth()->user()->homeRoute()) : route('home'))
@php($user = auth()->user())
@section('code', '404')
@section('title', 'That one landed out.')
@section('kicker', 'Error 404: Page not found')
@section('pill', 'BALL OUT')
@section('scene', 'A ball flying past the court lines and landing out')
@section('foot', '404 Not Found')
@section('lead', "The link may be old, or the open play ended or was removed.")
@section('body')
    @php($chips = match (true) {
        ! $user => [['Log in', route('login'), 'login'], ['Create an account', route('signup'), 'users']],
        $user->isAdmin() => [['Organizer requests', route('admin.requests'), 'shield']],
        default => [['Open plays', route('member.plays'), 'calendar'], ['Rankings', route('member.rankings'), 'trophy']],
    })
    <div class="flex flex-col gap-2.5">
        <span class="text-[13px] font-bold text-ink2">Try one of these</span>
        <div class="flex flex-wrap gap-2">
            @foreach ($chips as [$label, $url, $icon])
                <a href="{{ $url }}" class="inline-flex h-10 items-center gap-2 rounded-full border border-line bg-surface px-3.5 text-sm font-bold text-ink transition hover:brightness-96 active:scale-97"><x-icon :name="$icon" :size="16" />{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap">
        <a href="{{ $home }}" class="inline-flex h-13.5 items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Back to home</a>
    </div>
@endsection
