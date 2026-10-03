@extends('emails.layout')
@php($footerLink = ['Account settings', route('settings')])

@section('label', 'Account')
@section('why', "This is an email about your PickleX account, so we send it even if you've turned off notifications.")

@section('content')
    <p style="margin: 0 0 22px;"><span style="display: inline-block; padding: 5px 12px 5px 10px; border-radius: 999px; background: #D3F347; color: #12170F; font-size: 13px; font-weight: 800;">&#10003;&nbsp;Active again</span></p>
    <x-mail.h1>You're back, {{ $user->firstName() }}</x-mail.h1>
    <p class="em-ink2" style="margin: 10px 0 22px; font-size: 16px; color: #464D42;">{{ $early ? 'A PickleX admin lifted your suspension'.($until ? ' early' : '').', so' : 'Your suspension ended, so' }} <strong class="em-ink" style="color: #12170F;">{{ $user->email }}</strong> is active again. You can log in, join open plays and check in at the courts.</p>

    @include('emails.partials.span', ['dates' => [['Suspended', $at->format('D, M j')], ['Active from', $back->format('D, M j')]],
        'caption' => $early && $until ? 'It was set to end on '.$until->format('D, M j').'.' : null])

    @include('emails.partials.checklist', ['title' => "Everything's where you left it", 'items' => [
        'Your games, stats and rankings.',
        'Your password and passkeys.',
        'Your look, avatar and display name.',
    ]])

    <p class="em-ink2" style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #464D42;">Details</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px; font-size: 14px;">
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257; width: 90px;">Reason</td><td class="em-ink">{{ $reason[0] }}</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Suspended</td><td class="em-ink">{{ $at->format('D, M j') }} to {{ $back->format('D, M j') }} ({{ $days }} {{ Str::plural('day', $days) }})</td></tr>
        <tr><td class="em-ink3" style="padding: 3px 16px 3px 0; color: #5B6257;">Account</td><td class="em-ink">{{ $user->email }}</td></tr>
    </table>

    @if ($reasonKey === 'noshow')
        <div class="em-lime-soft" style="margin: 0 0 22px; padding: 16px 18px; border-radius: 18px; background: #F0FAC8; border-left: 4px solid #D3F347;">
            <p class="em-ink" style="margin: 0 0 6px; font-weight: 800; color: #12170F;">Can't make it to an open play?</p>
            <p class="em-ink2" style="margin: 0; font-size: 14px; line-height: 1.55; color: #464D42;">Leave it in the app before it starts so someone else gets the spot. Spots you held before the suspension were freed, so join again for the nights you want. Repeated no-shows can lead to another suspension.</p>
        </div>
    @endif

    <p style="margin: 0;"><x-mail.button :href="route('member.plays')">Find an open play</x-mail.button></p>
@endsection
