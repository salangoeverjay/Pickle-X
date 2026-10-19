@extends('errors.layout')
@php($home = auth()->check() ? route(auth()->user()->homeRoute()) : route('home'))
@section('code', '403')
@section('title', 'This court is reserved.')
@section('kicker', 'Error 403: No access')
@section('pill', 'COURT RESERVED')
@section('scene', 'A court roped off with a lock on the net and a Reserved sign')
@section('foot', '403 Forbidden')
@section('lead')
    @if (auth()->user()?->organizer_status === 'pending')
        You're signed in as {{ auth()->user()->name }}. Organizer tools unlock on your account once a PickleX admin approves your club. You can keep playing in the meantime.
    @elseif (auth()->check())
        You're signed in as {{ auth()->user()->name }}, but this page isn't part of your account.
    @else
        This page is only for signed-in players and organizers.
    @endif
@endsection
@section('body')
    <div class="flex items-start gap-3 rounded-2xl border border-line bg-surface px-4 py-3.5">
        <x-icon name="info" :size="20" class="mt-px text-ink2" />
        <p class="m-0 text-sm leading-normal text-ink2">
            @if (auth()->check())
                Need access? Organizers can switch modes from the menu. To host open plays, apply from <a href="{{ route('settings') }}" class="font-bold text-ink underline">Account settings</a>, or log in with the account that runs this club.
            @else
                Log in with your PickleX account to keep going.
            @endif
        </p>
    </div>
    <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap">
        @auth
            <a href="{{ $home }}" class="inline-flex h-13.5 items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Go to my PickleX</a>
            <form method="POST" action="{{ route('logout') }}" class="contents">
                @csrf
                <button type="submit" class="inline-flex h-13.5 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-transparent px-6 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Switch account</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="inline-flex h-13.5 items-center justify-center gap-2.5 rounded-full border-2 border-cta-border bg-lime px-6.5 text-base font-bold text-on-lime shadow-cta transition hover:brightness-96 active:scale-97">Log in</a>
            <a href="{{ route('home') }}" class="inline-flex h-13.5 cursor-pointer items-center justify-center rounded-full border-[1.5px] border-line2 bg-transparent px-6 text-[15px] font-bold text-ink transition hover:brightness-96 active:scale-97">Back to home</a>
        @endauth
    </div>
@endsection
