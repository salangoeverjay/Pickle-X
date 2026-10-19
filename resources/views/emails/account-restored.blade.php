@extends('emails.layout')
@php
    $footerLink = ['Account settings', route('settings')];
    // $kind: auto (ended on its own), early (lifted before its end date) or open (lifted, no end date).
    $end = $kind === 'auto' ? $until : $back;
@endphp

@section('label', 'Account')
@section('why', "This is an email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #D3F347; color: #12170F; font-size: 13px; font-weight: 800;">&#10003;&nbsp;Active again</span></p>
    @if ($kind === 'auto')
        <x-mail.h1>Your suspension ended, {{ $user->firstName() }}</x-mail.h1>
        <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">Your {{ $days }}-day suspension ended this morning, so <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> is active again. You can log in, join open plays and check in at the courts.</p>
    @elseif ($kind === 'early')
        <x-mail.h1>You're back, {{ $user->firstName() }}</x-mail.h1>
        <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">A PickleX admin lifted your suspension early, so <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> is active again. You can log in, join open plays and check in at the courts.</p>
    @else
        <x-mail.h1>You're back, {{ $user->firstName() }}</x-mail.h1>
        <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">A PickleX admin took another look and lifted the suspension on <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong>. You can log in, join open plays and check in at the courts again.</p>
    @endif

    @php
        $details = [
            ['Reason', ucfirst($reason[2])],
            ['Suspended', $at->format('D, M j').' to '.$end->format('D, M j').' ('.$days.' '.Str::plural('day', $days).')'],
        ];
        if ($kind === 'open') {
            $details[] = ['Lifted by', $by && ! Str::startsWith($by, 'PickleX') ? $by.', PickleX admin' : 'A PickleX admin'];
        }
        $details[] = ['Account', $user->email];
    @endphp

    @if ($kind === 'open')
        @include('emails.partials.facts', ['rows' => $details])
        @include('emails.partials.checklist', ['title' => "Everything's where you left it", 'items' => [
            'Your games, stats and rankings.',
            'Your password and passkeys.',
            'Spots you held before were freed, so join again for the nights you want.',
        ]])
    @else
        @include('emails.partials.span', ['dates' => [['Suspended', $at->format('D, M j')], ['Active from', $end->format('D, M j')]],
            'caption' => $kind === 'auto' ? 'Nothing to do on your end. Your password and passkeys work as before.' : 'It was set to end on '.$until->format('D, M j').'.'])
        @include('emails.partials.checklist', ['title' => "Everything's where you left it", 'items' => [
            'Your games, stats and rankings.',
            'Your password and passkeys.',
            // A spam suspension turned the display name off (User::suspend).
            $reasonKey === 'spam' ? 'Your look and avatar.' : 'Your look, avatar and display name.',
        ]])
        @include('emails.partials.facts', ['rows' => $details])
    @endif

    @if ($reasonKey === 'noshow')
        @include('emails.partials.tip', ['title' => "Can't make it to an open play?", 'text' => 'Leave it in the app before it starts so someone else gets the spot.'
            .($kind === 'early' ? ' Spots you held before the suspension were freed, so join again for the nights you want.' : '').' Repeated no-shows can lead to another suspension.'])
    @elseif ($reasonKey === 'conduct')
        @include('emails.partials.tip', ['title' => 'Keep it friendly on court', 'text' => 'Everyone at open play is there to have a good time. Another report could mean a longer suspension.'])
    @endif

    <p style="margin: 0;"><x-mail.button :href="route('member.plays')">Find an open play</x-mail.button></p>
@endsection
